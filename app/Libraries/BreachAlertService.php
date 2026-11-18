<?php

namespace App\Libraries;

/**
 * Auto Alert System (Email):
 * Manages user email watchlists, detects newly discovered data breaches vs baseline,
 * and dispatches HTML + plaintext email notifications via OtpMailerService (Brevo/SMTP).
 */
class BreachAlertService
{
    private const SETTING_KEY = 'auto_alert_cfg';
    private const CACHE_KEY   = 'auto_alert_cfg_v1';
    private const CACHE_TTL   = 300;

    private OtpMailerService $mailer;
    private XonApiService $xon;

    public function __construct(?OtpMailerService $mailer = null, ?XonApiService $xon = null)
    {
        $this->mailer = $mailer ?? new OtpMailerService();
        $this->xon    = $xon ?? new XonApiService();
    }

    /**
     * Ensure monitored_emails and breach_alert_logs tables exist cleanly (idempotent).
     */
    public function ensureSchema(): void
    {
        $dir    = rtrim(WRITEPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'ratelimit';
        $marker = is_dir($dir) ? ($dir . DIRECTORY_SEPARATOR . '.breach_alert_schema_ready') : null;
        if ($marker && is_file($marker)) {
            return;
        }

        (new SiteSettingService())->ensureTable();
        $db = \Config\Database::connect();

        $db->query("
            CREATE TABLE IF NOT EXISTS `monitored_emails` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `email` VARCHAR(254) NOT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `last_status` VARCHAR(16) NOT NULL DEFAULT 'pending',
                `last_breach_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `last_breach_names_json` TEXT NULL,
                `alerted_breach_names_json` TEXT NULL,
                `last_error` VARCHAR(255) NULL DEFAULT NULL,
                `last_checked_at` DATETIME NULL DEFAULT NULL,
                `last_alerted_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_monitor_user_email` (`user_id`, `email`),
                KEY `idx_monitor_due` (`is_active`, `last_checked_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `breach_alert_logs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `monitor_id` BIGINT UNSIGNED NOT NULL,
                `user_id` INT UNSIGNED NOT NULL,
                `monitored_email` VARCHAR(254) NOT NULL,
                `recipient_email` VARCHAR(254) NOT NULL,
                `new_breaches_json` TEXT NOT NULL,
                `new_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `delivery_status` VARCHAR(16) NOT NULL,
                `delivery_debug` VARCHAR(255) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_alert_user_created` (`user_id`, `created_at`),
                KEY `idx_alert_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        if ($marker) {
            @file_put_contents($marker, '1');
        }
    }

    /**
     * Maximum monitored emails allowed for a given subscription tier / role.
     */
    public function getMonitorQuota(string $plan, string $role = 'user'): int
    {
        if ($role === 'admin') {
            return 50;
        }

        return match (strtolower($plan)) {
            'pro'  => 25,
            'plus' => 5,
            default => 0,
        };
    }

    /**
     * Check whether a user record is currently eligible for automated scheduled monitoring.
     */
    public function isUserEligibleForAutoAlert(array $user): bool
    {
        if (($user['role'] ?? 'user') === 'admin') {
            return true;
        }

        $plan = strtolower((string) ($user['subscription_plan'] ?? 'free'));
        if (!in_array($plan, ['plus', 'pro'], true)) {
            return false;
        }

        $expireDate = trim((string) ($user['expire_date'] ?? ''));
        if ($expireDate === '') {
            return true;
        }

        return strtotime($expireDate) >= strtotime('today');
    }

    /**
     * Load Auto Alert configuration (auto-generating a persistent cron token on first access).
     *
     * @return array{
     *   enabled: bool,
     *   interval_hours: int,
     *   batch_size: int,
     *   cron_token: string,
     *   last_cron_run_at: string|null,
     *   last_cron_summary: string
     * }
     */
    public function getSettings(): array
    {
        $cached = cache()->get(self::CACHE_KEY);
        if (is_array($cached) && !empty($cached['cron_token'])) {
            return $cached;
        }

        $this->ensureSchema();
        $db  = \Config\Database::connect();
        $row = $db->table('site_settings')->where('setting_key', self::SETTING_KEY)->get()->getRowArray();

        $decoded = (!empty($row['setting_value'])) ? json_decode((string) $row['setting_value'], true) : [];
        if (!is_array($decoded)) {
            $decoded = [];
        }

        $token = trim((string) ($decoded['cron_token'] ?? ''));
        $needsSave = false;
        if ($token === '' || strlen($token) < 32) {
            $token = bin2hex(random_bytes(24));
            $needsSave = true;
        }

        $cfg = [
            'enabled'           => array_key_exists('enabled', $decoded) ? !empty($decoded['enabled']) : true,
            'interval_hours'    => $this->clampInt((int) ($decoded['interval_hours'] ?? 24), 6, 168),
            'batch_size'        => $this->clampInt((int) ($decoded['batch_size'] ?? 5), 1, 10),
            'cron_token'        => $token,
            'last_cron_run_at'  => !empty($decoded['last_cron_run_at']) ? (string) $decoded['last_cron_run_at'] : null,
            'last_cron_summary' => (string) ($decoded['last_cron_summary'] ?? ''),
        ];

        if ($needsSave || !$row) {
            $this->persistSettings($cfg);
        } else {
            cache()->save(self::CACHE_KEY, $cfg, self::CACHE_TTL);
        }

        return $cfg;
    }

    /**
     * Save admin settings for Auto Alert scheduling.
     *
     * @return array{ok: bool, message: string}
     */
    public function saveSettings(array $input): array
    {
        $current = $this->getSettings();
        $updated = [
            'enabled'           => !empty($input['enabled']),
            'interval_hours'    => $this->clampInt((int) ($input['interval_hours'] ?? 24), 6, 168),
            'batch_size'        => $this->clampInt((int) ($input['batch_size'] ?? 5), 1, 10),
            'cron_token'        => $current['cron_token'],
            'last_cron_run_at'  => $current['last_cron_run_at'],
            'last_cron_summary' => $current['last_cron_summary'],
        ];

        $this->persistSettings($updated);

        return [
            'ok'      => true,
            'message' => 'Pengaturan Auto Alert berhasil diperbarui ('
                . ($updated['enabled'] ? 'AKTIF' : 'NONAKTIF')
                . ', interval ' . $updated['interval_hours'] . ' jam, batch ' . $updated['batch_size'] . ' email).',
        ];
    }

    /**
     * Rotate the cron endpoint secret token.
     */
    public function regenerateCronToken(): string
    {
        $cfg               = $this->getSettings();
        $cfg['cron_token'] = bin2hex(random_bytes(24));
        $this->persistSettings($cfg);

        return $cfg['cron_token'];
    }

    /**
     * Verify a provided cron token in constant time.
     */
    public function verifyCronToken(string $provided): bool
    {
        $provided = trim($provided);
        if ($provided === '') {
            return false;
        }
        $expected = $this->getSettings()['cron_token'] ?? '';

        return $expected !== '' && hash_equals($expected, $provided);
    }

    /**
     * Fetch monitored emails belonging to a user.
     *
     * @return list<array<string, mixed>>
     */
    public function listUserMonitors(int $userId): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();

        return $db->table('monitored_emails')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Fetch recent delivered/attempted alert logs for a user.
     *
     * @return list<array<string, mixed>>
     */
    public function listUserAlertLogs(int $userId, int $limit = 10): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();

        return $db->table('breach_alert_logs')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Check whether a specific email is already on the user's watchlist.
     */
    public function isEmailMonitoredByUser(int $userId, string $email): bool
    {
        $email = strtolower(trim($email));
        if ($userId <= 0 || $email === '') {
            return false;
        }

        try {
            $this->ensureSchema();
            $db = \Config\Database::connect();
            return $db->table('monitored_emails')
                ->where('user_id', $userId)
                ->where('email', $email)
                ->countAllResults() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Add an email to the user's Auto Alert watchlist and capture its initial baseline.
     *
     * @return array{ok: bool, message: string, monitor_id?: int}
     */
    public function addMonitor(int $userId, string $email, string $plan, string $role = 'user', ?array $preFetchedCheck = null): array
    {
        $this->ensureSchema();
        $email = strtolower(trim($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            return ['ok' => false, 'message' => 'Format alamat email pantauan tidak valid.'];
        }

        $quota = $this->getMonitorQuota($plan, $role);
        if ($quota <= 0) {
            return ['ok' => false, 'message' => 'Fitur Auto Alert Email khusus pengguna PLUS, PRO, atau Admin.'];
        }

        $db = \Config\Database::connect();
        $existing = $db->table('monitored_emails')
            ->where('user_id', $userId)
            ->where('email', $email)
            ->get()
            ->getRowArray();

        if ($existing) {
            if ((int) ($existing['is_active'] ?? 0) === 0) {
                $db->table('monitored_emails')->where('id', (int) $existing['id'])->update([
                    'is_active'  => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                return ['ok' => true, 'message' => 'Pemantauan untuk ' . $email . ' diaktifkan kembali.', 'monitor_id' => (int) $existing['id']];
            }
            return ['ok' => false, 'message' => 'Email ' . $email . ' sudah ada di daftar pantauan Anda.'];
        }

        $currentCount = (int) $db->table('monitored_emails')->where('user_id', $userId)->countAllResults();
        if ($currentCount >= $quota) {
            return ['ok' => false, 'message' => 'Kuota email pantauan paket ' . strtoupper($plan) . ' penuh (' . $currentCount . '/' . $quota . ').'];
        }

        $apiResult = $preFetchedCheck ?? $this->xon->checkEmail($email);
        $now       = date('Y-m-d H:i:s');
        $status    = $apiResult['status'] ?? 'error';
        $details   = is_array($apiResult['details'] ?? null) ? $apiResult['details'] : [];
        $names     = $this->extractBreachNames($details);
        $namesJson = json_encode($names, JSON_UNESCAPED_UNICODE);

        $insert = [
            'user_id'                   => $userId,
            'email'                     => $email,
            'is_active'                 => 1,
            'last_status'               => in_array($status, ['safe', 'pwned', 'error'], true) ? $status : 'pending',
            'last_breach_count'         => count($names),
            'last_breach_names_json'    => $status === 'error' ? null : $namesJson,
            'alerted_breach_names_json' => $status === 'error' ? null : $namesJson,
            'last_error'                => $status === 'error' ? mb_substr((string) ($apiResult['message'] ?? 'API error'), 0, 250) : null,
            'last_checked_at'           => $status === 'error' ? null : $now,
            'last_alerted_at'           => null,
            'created_at'                => $now,
            'updated_at'                => $now,
        ];

        $db->table('monitored_emails')->insert($insert);
        $monitorId = (int) $db->insertID();

        if ($status === 'error') {
            return [
                'ok'         => true,
                'monitor_id' => $monitorId,
                'message'    => 'Email ' . $email . ' ditambahkan ke pantauan. Scan awal akan dilanjutkan pada siklus berikutnya (' . ($apiResult['message'] ?? 'API sibuk') . ').',
            ];
        }

        $baselineNote = count($names) > 0
            ? 'Baseline tercatat: ' . count($names) . ' insiden lama. Anda akan menerima email otomatis jika muncul kebocoran BARU.'
            : 'Baseline tercatat: 0 insiden (AMAN). Anda akan menerima email otomatis jika email ini bocor di masa depan.';

        return [
            'ok'         => true,
            'monitor_id' => $monitorId,
            'message'    => 'Pemantauan aktif untuk ' . $email . '. ' . $baselineNote,
        ];
    }

    /**
     * Pause or resume a monitored email owned by the user (IDOR-safe).
     *
     * @return array{ok: bool, message: string}
     */
    public function toggleMonitor(int $userId, int $monitorId): array
    {
        $this->ensureSchema();
        $db  = \Config\Database::connect();
        $row = $db->table('monitored_emails')
            ->where('id', $monitorId)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return ['ok' => false, 'message' => 'Data email pantauan tidak ditemukan.'];
        }

        $nextActive = ((int) $row['is_active'] === 1) ? 0 : 1;
        $db->table('monitored_emails')->where('id', $monitorId)->update([
            'is_active'  => $nextActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok'      => true,
            'message' => 'Pemantauan ' . $row['email'] . ' kini ' . ($nextActive === 1 ? 'DIAKTIFKAN' : 'DIJEDA') . '.',
        ];
    }

    /**
     * Delete a monitored email (IDOR-safe when $userId > 0; admin can pass null).
     *
     * @return array{ok: bool, message: string}
     */
    public function deleteMonitor(?int $userId, int $monitorId): array
    {
        $this->ensureSchema();
        $db      = \Config\Database::connect();
        $builder = $db->table('monitored_emails')->where('id', $monitorId);
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }

        $row = $builder->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Data email pantauan tidak ditemukan.'];
        }

        $db->table('monitored_emails')->where('id', $monitorId)->delete();

        return ['ok' => true, 'message' => 'Email ' . $row['email'] . ' dihapus dari daftar pantauan.'];
    }

    /**
     * Manually trigger a live breach check for a single monitored row.
     *
     * @return array{ok: bool, message: string, new_count: int, alert_sent: bool}
     */
    public function checkMonitorById(int $monitorId, ?int $restrictUserId = null): array
    {
        $this->ensureSchema();
        $db = \Config\Database::connect();

        $builder = $db->table('monitored_emails m')
            ->select('m.*, u.email AS owner_email, u.nama AS owner_nama, u.subscription_plan, u.role, u.expire_date')
            ->join('users u', 'u.id = m.user_id')
            ->where('m.id', $monitorId);

        if ($restrictUserId !== null) {
            $builder->where('m.user_id', $restrictUserId);
        }

        $row = $builder->get()->getRowArray();
        if (!$row) {
            return ['ok' => false, 'message' => 'Data pemantauan tidak ditemukan.', 'new_count' => 0, 'alert_sent' => false];
        }

        $owner = [
            'id'                => (int) $row['user_id'],
            'email'             => (string) ($row['owner_email'] ?? ''),
            'nama'              => (string) ($row['owner_nama'] ?? ''),
            'subscription_plan' => (string) ($row['subscription_plan'] ?? 'free'),
            'role'              => (string) ($row['role'] ?? 'user'),
            'expire_date'       => $row['expire_date'] ?? null,
        ];

        $allowAlert = $this->isUserEligibleForAutoAlert($owner);
        $res        = $this->checkSingleMonitor($row, $owner, $allowAlert);

        if ($res['status'] === 'error') {
            return ['ok' => false, 'message' => 'Gagal memeriksa API: ' . $res['message'], 'new_count' => 0, 'alert_sent' => false];
        }

        $msg = 'Pengecekan ' . $row['email'] . ' selesai: '
            . ($res['total_count'] > 0 ? $res['total_count'] . ' insiden tercatat' : 'AMAN (0 insiden)') . '.';
        if ($res['baseline']) {
            $msg .= ' Baseline awal tersimpan.';
        } elseif ($res['new_count'] > 0) {
            $msg .= $res['alert_sent']
                ? ' Terdeteksi ' . $res['new_count'] . ' insiden BARU — email alert telah dikirim ke ' . $owner['email'] . '!'
                : ' Terdeteksi ' . $res['new_count'] . ' insiden BARU, namun kirim email gagal: ' . $res['alert_error'];
        } else {
            $msg .= ' Tidak ada kebocoran baru sejak cek terakhir.';
        }

        return [
            'ok'         => true,
            'message'    => $msg,
            'new_count'  => $res['new_count'],
            'alert_sent' => $res['alert_sent'],
        ];
    }

    /**
     * Opportunistically sync a monitored email when the user already searched it via /cek-email
     * (uses 0 extra XposedOrNot API requests and triggers an alert if new breaches appeared).
     */
    public function syncFromSearch(int $userId, string $email, array $apiResult): ?array
    {
        if ($userId <= 0 || !in_array($apiResult['status'] ?? '', ['safe', 'pwned'], true)) {
            return null;
        }

        try {
            $this->ensureSchema();
            $db  = \Config\Database::connect();
            $row = $db->table('monitored_emails m')
                ->select('m.*, u.email AS owner_email, u.nama AS owner_nama, u.subscription_plan, u.role, u.expire_date')
                ->join('users u', 'u.id = m.user_id')
                ->where('m.user_id', $userId)
                ->where('m.email', strtolower(trim($email)))
                ->where('m.is_active', 1)
                ->get()
                ->getRowArray();

            if (!$row) {
                return null;
            }

            $owner = [
                'id'                => (int) $row['user_id'],
                'email'             => (string) ($row['owner_email'] ?? ''),
                'nama'              => (string) ($row['owner_nama'] ?? ''),
                'subscription_plan' => (string) ($row['subscription_plan'] ?? 'free'),
                'role'              => (string) ($row['role'] ?? 'user'),
                'expire_date'       => $row['expire_date'] ?? null,
            ];

            if (!$this->isUserEligibleForAutoAlert($owner)) {
                return null;
            }

            return $this->checkSingleMonitor($row, $owner, true, $apiResult);
        } catch (\Throwable $e) {
            log_message('warning', '[BreachAlertService] syncFromSearch failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute a rate-limit-safe batch check over active due monitored emails.
     *
     * @return array{
     *   checked: int,
     *   skipped_disabled: bool,
     *   alerts_sent: int,
     *   alerts_failed: int,
     *   api_errors: int,
     *   new_breaches_total: int,
     *   details: list<string>
     * }
     */
    public function runDueChecks(?int $overrideLimit = null, bool $ignoreInterval = false): array
    {
        $this->ensureSchema();
        $cfg = $this->getSettings();

        if (!$ignoreInterval && empty($cfg['enabled'])) {
            return [
                'checked'            => 0,
                'skipped_disabled'   => true,
                'alerts_sent'        => 0,
                'alerts_failed'      => 0,
                'api_errors'         => 0,
                'new_breaches_total' => 0,
                'details'            => ['Auto Alert scheduler sedang dinonaktifkan oleh Admin.'],
            ];
        }

        $limit         = $this->clampInt($overrideLimit ?? $cfg['batch_size'], 1, 10);
        $intervalHours = $cfg['interval_hours'];
        $cutoff        = date('Y-m-d H:i:s', time() - ($intervalHours * 3600));
        $today         = date('Y-m-d');

        $db      = \Config\Database::connect();
        $builder = $db->table('monitored_emails m')
            ->select('m.*, u.email AS owner_email, u.nama AS owner_nama, u.subscription_plan, u.role, u.expire_date')
            ->join('users u', 'u.id = m.user_id')
            ->where('m.is_active', 1)
            ->groupStart()
                ->where('u.role', 'admin')
                ->orGroupStart()
                    ->whereIn('u.subscription_plan', ['plus', 'pro'])
                    ->groupStart()
                        ->where('u.expire_date IS NULL', null, false)
                        ->orWhere('u.expire_date >=', $today)
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd();

        if (!$ignoreInterval) {
            $builder->groupStart()
                ->where('m.last_checked_at IS NULL', null, false)
                ->orWhere('m.last_checked_at <=', $cutoff)
            ->groupEnd();
        }

        $rows = $builder
            ->orderBy('m.last_checked_at', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $summary = [
            'checked'            => 0,
            'skipped_disabled'   => false,
            'alerts_sent'        => 0,
            'alerts_failed'      => 0,
            'api_errors'         => 0,
            'new_breaches_total' => 0,
            'details'            => [],
        ];

        $totalRows = count($rows);
        foreach ($rows as $idx => $row) {
            $owner = [
                'id'                => (int) $row['user_id'],
                'email'             => (string) ($row['owner_email'] ?? ''),
                'nama'              => (string) ($row['owner_nama'] ?? ''),
                'subscription_plan' => (string) ($row['subscription_plan'] ?? 'free'),
                'role'              => (string) ($row['role'] ?? 'user'),
                'expire_date'       => $row['expire_date'] ?? null,
            ];

            $res = $this->checkSingleMonitor($row, $owner, true);
            $summary['checked']++;

            if ($res['status'] === 'error') {
                $summary['api_errors']++;
                $summary['details'][] = $row['email'] . ': ERROR (' . $res['message'] . ')';
            } elseif ($res['new_count'] > 0 && !$res['baseline']) {
                $summary['new_breaches_total'] += $res['new_count'];
                if ($res['alert_sent']) {
                    $summary['alerts_sent']++;
                    $summary['details'][] = $row['email'] . ': +' . $res['new_count'] . ' insiden baru -> alert terkirim ke ' . $owner['email'];
                } else {
                    $summary['alerts_failed']++;
                    $summary['details'][] = $row['email'] . ': +' . $res['new_count'] . ' insiden baru -> gagal kirim (' . $res['alert_error'] . ')';
                }
            } else {
                $summary['details'][] = $row['email'] . ': OK (' . $res['total_count'] . ' insiden, tidak ada baru)';
            }

            // Stay safely below XposedOrNot free limit (~2 req/sec) between batch calls
            if ($idx < $totalRows - 1) {
                usleep(600000);
            }
        }

        $cfg['last_cron_run_at']  = date('Y-m-d H:i:s');
        $cfg['last_cron_summary'] = sprintf(
            'Dicek: %d | Alert Terkirim: %d | Gagal: %d | Error API: %d',
            $summary['checked'],
            $summary['alerts_sent'],
            $summary['alerts_failed'],
            $summary['api_errors']
        );
        $this->persistSettings($cfg);

        return $summary;
    }

    /**
     * Send a realistic test/simulation Auto Alert email without mutating breach history.
     *
     * @return array{ok: bool, message: string, debug: string}
     */
    public function sendTestAlertEmail(string $recipientEmail, string $recipientName, string $sampleMonitoredEmail): array
    {
        $sampleBreaches = [
            [
                'sumber'          => 'SIMULASI-BREACH-2026',
                'tanggal'         => date('Y'),
                'domain'          => 'contoh-platform.test',
                'industri'        => 'Information Technology',
                'kelas_data'      => 'Email addresses; Passwords; Usernames',
                'jumlah_data'     => '1,250,000',
                'risiko_password' => 'EasyToCrack',
                'is_verified'     => true,
                'deskripsi'       => 'Ini adalah email uji coba Auto Alert PWNED untuk memastikan jalur pengiriman Brevo/SMTP masuk ke Inbox Anda.',
            ],
        ];

        return $this->dispatchAlertEmail($recipientEmail, $recipientName, $sampleMonitoredEmail, $sampleBreaches, true);
    }

    /**
     * Aggregate admin monitoring dashboard overview & recent tables.
     *
     * @return array{
     *   mailer_active: bool,
     *   mailer_driver: string,
     *   stats: array{total_monitors: int, active_monitors: int, due_now: int, alerts_sent_24h: int, alerts_failed_24h: int},
     *   monitors: list<array<string, mixed>>,
     *   logs: list<array<string, mixed>>
     * }
     */
    public function getAdminOverview(): array
    {
        $this->ensureSchema();
        $cfg   = $this->getSettings();
        $db    = \Config\Database::connect();
        $today = date('Y-m-d');
        $since = date('Y-m-d H:i:s', time() - 86400);
        $cutoff = date('Y-m-d H:i:s', time() - ($cfg['interval_hours'] * 3600));

        $totalMonitors  = (int) $db->table('monitored_emails')->countAllResults();
        $activeMonitors = (int) $db->table('monitored_emails')->where('is_active', 1)->countAllResults();

        $dueNow = (int) $db->table('monitored_emails m')
            ->join('users u', 'u.id = m.user_id')
            ->where('m.is_active', 1)
            ->groupStart()
                ->where('u.role', 'admin')
                ->orGroupStart()
                    ->whereIn('u.subscription_plan', ['plus', 'pro'])
                    ->groupStart()
                        ->where('u.expire_date IS NULL', null, false)
                        ->orWhere('u.expire_date >=', $today)
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->groupStart()
                ->where('m.last_checked_at IS NULL', null, false)
                ->orWhere('m.last_checked_at <=', $cutoff)
            ->groupEnd()
            ->countAllResults();

        $sent24h = (int) $db->table('breach_alert_logs')
            ->where('delivery_status', 'sent')
            ->where('created_at >=', $since)
            ->countAllResults();

        $failed24h = (int) $db->table('breach_alert_logs')
            ->where('delivery_status', 'failed')
            ->where('created_at >=', $since)
            ->countAllResults();

        $monitors = $db->table('monitored_emails m')
            ->select('m.*, u.nama AS owner_nama, u.email AS owner_email, u.subscription_plan, u.role')
            ->join('users u', 'u.id = m.user_id')
            ->orderBy('m.updated_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        $logs = $db->table('breach_alert_logs l')
            ->select('l.*, u.nama AS owner_nama')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->orderBy('l.created_at', 'DESC')
            ->limit(30)
            ->get()
            ->getResultArray();

        $mailerCfg = $this->mailer->getConfig();

        return [
            'mailer_active' => $this->mailer->isConfigured($mailerCfg),
            'mailer_driver' => (string) ($mailerCfg['driver'] ?? 'off'),
            'stats'         => [
                'total_monitors'    => $totalMonitors,
                'active_monitors'   => $activeMonitors,
                'due_now'           => $dueNow,
                'alerts_sent_24h'   => $sent24h,
                'alerts_failed_24h' => $failed24h,
            ],
            'monitors' => $monitors,
            'logs'     => $logs,
        ];
    }

    /**
     * Compare current breaches for a single monitor row, persist updates, and email alerts when new breaches emerge.
     *
     * @return array{
     *   status: string,
     *   message: string,
     *   total_count: int,
     *   new_count: int,
     *   baseline: bool,
     *   alert_sent: bool,
     *   alert_error: string
     * }
     */
    private function checkSingleMonitor(array $row, array $owner, bool $allowAlert, ?array $preFetchedCheck = null): array
    {
        $db         = \Config\Database::connect();
        $now        = date('Y-m-d H:i:s');
        $monitorId  = (int) $row['id'];
        $targetMail = (string) $row['email'];

        $apiResult = $preFetchedCheck ?? $this->xon->checkEmail($targetMail);
        $status    = (string) ($apiResult['status'] ?? 'error');

        if ($status === 'error') {
            $errMsg = mb_substr((string) ($apiResult['message'] ?? 'API error'), 0, 250);
            $db->table('monitored_emails')->where('id', $monitorId)->update([
                'last_error'      => $errMsg,
                'last_checked_at' => $now,
                'updated_at'      => $now,
            ]);

            return [
                'status'      => 'error',
                'message'     => $errMsg,
                'total_count' => (int) ($row['last_breach_count'] ?? 0),
                'new_count'   => 0,
                'baseline'    => false,
                'alert_sent'  => false,
                'alert_error' => '',
            ];
        }

        $details   = is_array($apiResult['details'] ?? null) ? $apiResult['details'] : [];
        $names     = $this->extractBreachNames($details);
        $namesJson = json_encode($names, JSON_UNESCAPED_UNICODE);

        $hasBaseline = $row['alerted_breach_names_json'] !== null && $row['alerted_breach_names_json'] !== '';
        if (!$hasBaseline) {
            $db->table('monitored_emails')->where('id', $monitorId)->update([
                'last_status'               => $status,
                'last_breach_count'         => count($names),
                'last_breach_names_json'    => $namesJson,
                'alerted_breach_names_json' => $namesJson,
                'last_error'                => null,
                'last_checked_at'           => $now,
                'updated_at'                => $now,
            ]);

            return [
                'status'      => $status,
                'message'     => 'Baseline tercatat.',
                'total_count' => count($names),
                'new_count'   => 0,
                'baseline'    => true,
                'alert_sent'  => false,
                'alert_error' => '',
            ];
        }

        $alertedNames = json_decode((string) $row['alerted_breach_names_json'], true);
        if (!is_array($alertedNames)) {
            $alertedNames = [];
        }
        $alertedMap = [];
        foreach ($alertedNames as $knownName) {
            $alertedMap[(string) $knownName] = true;
        }

        $newBreaches = [];
        foreach ($details as $item) {
            $source = (string) ($item['sumber'] ?? '');
            if ($source !== '' && !isset($alertedMap[$source])) {
                $newBreaches[] = $item;
            }
        }

        $alertSent   = false;
        $alertError  = '';
        $updateFields = [
            'last_status'            => $status,
            'last_breach_count'      => count($names),
            'last_breach_names_json' => $namesJson,
            'last_error'             => null,
            'last_checked_at'        => $now,
            'updated_at'             => $now,
        ];

        if ($allowAlert && !empty($newBreaches)) {
            $ownerEmail = strtolower(trim((string) ($owner['email'] ?? '')));
            $ownerName  = trim((string) ($owner['nama'] ?? 'Pengguna PWNED'));

            if ($ownerEmail !== '' && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
                $send = $this->dispatchAlertEmail($ownerEmail, $ownerName, $targetMail, $newBreaches, false);

                $compactNew = array_map(static function (array $b): array {
                    return [
                        'sumber'  => $b['sumber'] ?? 'Unknown',
                        'tanggal' => $b['tanggal'] ?? '-',
                        'domain'  => $b['domain'] ?? '-',
                        'risiko'  => $b['risiko_password'] ?? '-',
                    ];
                }, array_slice($newBreaches, 0, 15));

                $db->table('breach_alert_logs')->insert([
                    'monitor_id'        => $monitorId,
                    'user_id'           => (int) $row['user_id'],
                    'monitored_email'   => $targetMail,
                    'recipient_email'   => $ownerEmail,
                    'new_breaches_json' => (string) json_encode($compactNew, JSON_UNESCAPED_UNICODE),
                    'new_count'         => count($newBreaches),
                    'delivery_status'   => $send['ok'] ? 'sent' : 'failed',
                    'delivery_debug'    => mb_substr((string) ($send['debug'] ?: $send['message']), 0, 250),
                    'created_at'        => $now,
                ]);

                if ($send['ok']) {
                    $alertSent = true;
                    $mergedAck = array_values(array_unique(array_merge($alertedNames, $names)));
                    $updateFields['alerted_breach_names_json'] = json_encode($mergedAck, JSON_UNESCAPED_UNICODE);
                    $updateFields['last_alerted_at']           = $now;
                } else {
                    $alertError = $send['message'];
                }
            }
        }

        $db->table('monitored_emails')->where('id', $monitorId)->update($updateFields);

        return [
            'status'      => $status,
            'message'     => 'OK',
            'total_count' => count($names),
            'new_count'   => count($newBreaches),
            'baseline'    => false,
            'alert_sent'  => $alertSent,
            'alert_error' => $alertError,
        ];
    }

    /**
     * Format and dispatch the Auto Alert email via OtpMailerService.
     *
     * @param list<array<string, mixed>> $breaches
     * @return array{ok: bool, message: string, debug: string}
     */
    private function dispatchAlertEmail(
        string $toEmail,
        string $toName,
        string $monitoredEmail,
        array $breaches,
        bool $isTest = false
    ): array {
        $count        = count($breaches);
        $safeName     = htmlspecialchars($toName !== '' ? $toName : 'Pengguna PWNED', ENT_QUOTES, 'UTF-8');
        $safeMonitored = htmlspecialchars($monitoredEmail, ENT_QUOTES, 'UTF-8');
        $profileUrl   = rtrim((string) base_url('profile#auto-alert-section'), '/');
        $safeUrl      = htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8');
        $prefix       = $isTest ? '[TEST] ' : '';
        $subject      = $prefix . '[PWNED ALERT] ' . $count . ' Kebocoran Data Baru Terdeteksi (' . $monitoredEmail . ')';

        $testBannerHtml = $isTest
            ? '<div style="background:#fef08a;border:3px solid #000000;padding:10px 12px;margin:14px 0;font-size:11px;font-weight:bold;">[SIMULASI / TEST] Email ini dikirim untuk menguji kesiapan Auto Alert System PWNED melalui Brevo/SMTP.</div>'
            : '';

        $rowsHtml = '';
        $rowsText = '';
        foreach (array_slice($breaches, 0, 10) as $b) {
            $src  = htmlspecialchars((string) ($b['sumber'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
            $dom  = htmlspecialchars((string) ($b['domain'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $date = htmlspecialchars((string) ($b['tanggal'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $risk = htmlspecialchars((string) ($b['risiko_password'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $data = htmlspecialchars((string) ($b['kelas_data'] ?? '-'), ENT_QUOTES, 'UTF-8');

            $rowsHtml .= <<<HTML
<div style="border:2px solid #000000;background:#fff1f2;padding:10px 12px;margin-bottom:10px;">
  <div style="font-weight:bold;font-size:13px;color:#b91c1c;">[!] {$src} ({$date})</div>
  <div style="font-size:11px;color:#111827;margin-top:4px;">Domain: <strong>{$dom}</strong> &bull; Risiko Password: <strong>{$risk}</strong></div>
  <div style="font-size:11px;color:#374151;margin-top:4px;">Data Terdampak: {$data}</div>
</div>
HTML;

            $rowsText .= "- " . ($b['sumber'] ?? 'Unknown') . " (" . ($b['tanggal'] ?? '-') . ") | Domain: "
                . ($b['domain'] ?? '-') . " | Risiko: " . ($b['risiko_password'] ?? '-') . " | Data: "
                . ($b['kelas_data'] ?? '-') . "\n";
        }

        if ($count > 10) {
            $more = $count - 10;
            $rowsHtml .= '<p style="font-size:11px;font-weight:bold;color:#4b5563;">...dan ' . $more . ' insiden lainnya. Cek detail lengkap di dashboard PWNED.</p>';
            $rowsText .= "...dan {$more} insiden lainnya.\n";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:monospace;color:#111827;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:4px solid #000000;box-shadow:6px 6px 0 #000000;padding:24px;">
    <div style="background:#dc2626;color:#ffffff;padding:12px 14px;font-weight:bold;font-size:14px;letter-spacing:1.5px;border-bottom:3px solid #000000;">
      PWNED // AUTO ALERT SYSTEM
    </div>
    {$testBannerHtml}
    <p style="font-size:13px;line-height:1.6;margin:16px 0 8px;">Halo <strong>{$safeName}</strong>,</p>
    <p style="font-size:13px;line-height:1.6;margin:0 0 14px;">
      Sistem pemantauan otomatis mendeteksi <strong>{$count} kebocoran data baru</strong> untuk email pantauan:
      <span style="display:inline-block;background:#eff6ff;border:2px solid #000000;padding:2px 8px;font-weight:bold;color:#1d4ed8;">{$safeMonitored}</span>
    </p>
    {$rowsHtml}
    <div style="background:#f9fafb;border:2px dashed #000000;padding:12px;margin-top:16px;font-size:11px;line-height:1.6;">
      <strong>LANGKAH MITIGASI DISARANKAN:</strong><br>
      1. Segera ganti kata sandi pada layanan terdampak (dan layanan lain jika memakai sandi yang sama).<br>
      2. Aktifkan Autentikasi Dua Faktor (2FA) pada akun email utama Anda.<br>
      3. Waspada email phishing yang mencatut nama platform terkait.
    </div>
    <div style="margin-top:18px;text-align:center;">
      <a href="{$safeUrl}" style="display:inline-block;background:#000000;color:#ffffff;text-decoration:none;padding:10px 18px;font-weight:bold;font-size:12px;border:2px solid #000000;">
        KELOLA PEMANTAUAN DI PROFIL &gt;&gt;
      </a>
    </div>
  </div>
</body>
</html>
HTML;

        $text = "{$prefix}PWNED // AUTO ALERT SYSTEM\n\n"
            . "Halo {$toName},\n"
            . "Terdeteksi {$count} kebocoran data baru untuk email pantauan: {$monitoredEmail}\n\n"
            . $rowsText . "\n"
            . "Segera ganti password layanan terkait dan aktifkan 2FA.\n"
            . "Kelola pantauan: {$profileUrl}\n";

        return $this->mailer->sendEmail($toEmail, $toName, $subject, $html, $text);
    }

    /**
     * Extract unique non-empty breach source labels from XonApiService details.
     *
     * @param list<array<string, mixed>> $details
     * @return list<string>
     */
    private function extractBreachNames(array $details): array
    {
        $names = [];
        foreach ($details as $d) {
            $name = trim((string) ($d['sumber'] ?? ''));
            if ($name !== '' && $name !== 'Unknown') {
                $names[$name] = true;
            }
        }

        return array_keys($names);
    }

    /**
     * Persist Auto Alert configuration to site_settings and clear cache.
     */
    private function persistSettings(array $cfg): void
    {
        $this->ensureSchema();
        $db     = \Config\Database::connect();
        $now    = date('Y-m-d H:i:s');
        $json   = (string) json_encode($cfg, JSON_UNESCAPED_UNICODE);
        $exists = $db->table('site_settings')->where('setting_key', self::SETTING_KEY)->countAllResults();

        if ($exists > 0) {
            $db->table('site_settings')->where('setting_key', self::SETTING_KEY)->update([
                'setting_value' => $json,
                'updated_at'    => $now,
            ]);
        } else {
            $db->table('site_settings')->insert([
                'setting_key'   => self::SETTING_KEY,
                'setting_value' => $json,
                'updated_at'    => $now,
            ]);
        }

        cache()->save(self::CACHE_KEY, $cfg, self::CACHE_TTL);
    }

    private function clampInt(int $val, int $min, int $max): int
    {
        return max($min, min($max, $val));
    }
}
