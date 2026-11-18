<?php

namespace App\Libraries;

/**
 * Manages CSPRNG 6-digit OTP generation, HMAC-hashed persistence,
 * cooldown/anti-email-bombing quotas, and brute-force lockout protection.
 */
class OtpService
{
    public const OTP_TTL_SECONDS    = 600; // 10 minutes
    public const RESEND_COOLDOWN    = 60;  // 60 seconds per target email
    public const MAX_ATTEMPTS       = 5;   // max wrong guesses per OTP

    private SecurityGuard $guard;
    private OtpMailerService $mailer;

    public function __construct(?SecurityGuard $guard = null, ?OtpMailerService $mailer = null)
    {
        $this->guard  = $guard ?? new SecurityGuard();
        $this->mailer = $mailer ?? new OtpMailerService();
    }

    /**
     * Ensure otp_codes table and optional users.email_verified_at column exist cleanly.
     */
    public function ensureSchema(): void
    {
        $dir    = rtrim(WRITEPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'ratelimit';
        $marker = is_dir($dir) ? ($dir . DIRECTORY_SEPARATOR . '.otp_schema_ready') : null;
        if ($marker && is_file($marker)) {
            return;
        }

        $db = \Config\Database::connect();
        $db->query("
            CREATE TABLE IF NOT EXISTS `otp_codes` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `purpose` VARCHAR(32) NOT NULL,
                `email` VARCHAR(254) NOT NULL,
                `code_hash` CHAR(64) NOT NULL,
                `payload_json` TEXT NULL,
                `ip_hash` CHAR(32) NOT NULL,
                `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `expires_at` DATETIME NOT NULL,
                `verified_at` DATETIME NULL DEFAULT NULL,
                `consumed_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_otp_lookup` (`purpose`, `email`, `consumed_at`, `created_at`),
                KEY `idx_otp_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        try {
            $colExists = $db->query("SHOW COLUMNS FROM `users` LIKE 'email_verified_at'")->getResultArray();
            if (empty($colExists)) {
                $db->query("ALTER TABLE `users` ADD COLUMN `email_verified_at` DATETIME NULL DEFAULT NULL AFTER `password`");
            }
        } catch (\Throwable $e) {
            log_message('warning', '[OtpService] users.email_verified_at check skipped: ' . $e->getMessage());
        }

        if ($marker) {
            @file_put_contents($marker, '1');
        }
    }

    /**
     * Generate a 6-digit OTP, enforce anti-email-bombing quotas, store hashed code, and dispatch email.
     *
     * @return array{ok: bool, retry_after: int, message: string, code_sent: bool}
     */
    public function issueAndSendOtp(
        string $purpose,
        string $email,
        string $recipientName,
        string $ipKey,
        ?array $pendingPayload = null
    ): array {
        $email = strtolower(trim($email));

        if (!$this->mailer->isConfigured()) {
            return [
                'ok'          => false,
                'retry_after' => 0,
                'message'     => 'Layanan pengiriman email OTP belum aktif di sistem.',
                'code_sent'   => false,
            ];
        }

        // 1. Strict anti-email-bombing & outbound quota circuit breakers
        $cooldown = $this->guard->hitBucket('otp_cd_' . $purpose, $email, 1, self::RESEND_COOLDOWN, false);
        if (!$cooldown['allowed']) {
            return [
                'ok'          => false,
                'retry_after' => $cooldown['retry_after'],
                'message'     => 'Tunggu ' . $cooldown['retry_after'] . ' detik sebelum mengirim ulang OTP.',
                'code_sent'   => false,
            ];
        }

        $ipLim = $this->guard->hitBucket('otp_send_ip', $ipKey, 5, 900, true, $ipKey);
        if (!$ipLim['allowed']) {
            return [
                'ok'          => false,
                'retry_after' => $ipLim['retry_after'],
                'message'     => 'Terlalu banyak permintaan kirim OTP dari IP Anda. Coba lagi nanti.',
                'code_sent'   => false,
            ];
        }

        $emailLim = $this->guard->hitBucket('otp_send_email', $email, 3, 900, false);
        if (!$emailLim['allowed']) {
            return [
                'ok'          => false,
                'retry_after' => $emailLim['retry_after'],
                'message'     => 'Batas kirim OTP ke alamat email ini tercapai sementara (maks 3 / 15 menit).',
                'code_sent'   => false,
            ];
        }

        $globalLim = $this->guard->hitBucket('otp_global_15m', 'site', 25, 900, false);
        if (!$globalLim['allowed']) {
            $this->guard->logSecurityEvent('OTP_GLOBAL_CAP', 'critical', $email, 'Batas kirim OTP global 25/15m tercapai');
            return [
                'ok'          => false,
                'retry_after' => $globalLim['retry_after'],
                'message'     => 'Antrean kirim OTP sistem sedang penuh sementara. Silakan coba sebentar lagi.',
                'code_sent'   => false,
            ];
        }

        $this->ensureSchema();
        $db  = \Config\Database::connect();
        $now = time();

        // Invalidate any older unconsumed OTPs for the same purpose + email
        $db->table('otp_codes')
            ->where('purpose', $purpose)
            ->where('email', $email)
            ->where('consumed_at', null)
            ->update(['consumed_at' => date('Y-m-d H:i:s', $now)]);

        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash    = $this->hashOtp($otpCode, $purpose, $email);

        $db->table('otp_codes')->insert([
            'purpose'      => $purpose,
            'email'        => $email,
            'code_hash'    => $hash,
            'payload_json' => $pendingPayload !== null ? json_encode($pendingPayload, JSON_UNESCAPED_UNICODE) : null,
            'ip_hash'      => substr($ipKey, 0, 32),
            'attempts'     => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'expires_at'   => date('Y-m-d H:i:s', $now + self::OTP_TTL_SECONDS),
            'consumed_at'  => null,
            'created_at'   => date('Y-m-d H:i:s', $now),
        ]);
        $rowId = (int) $db->insertID();

        // Probabilistic cleanup of expired/consumed OTP records > 24h
        if (mt_rand(1, 20) === 1) {
            $db->table('otp_codes')
                ->where('created_at <', date('Y-m-d H:i:s', $now - 86400))
                ->delete();
        }

        $sendRes = $this->mailer->sendOtpEmail(
            $email,
            $recipientName,
            $otpCode,
            $purpose,
            (int) ceil(self::OTP_TTL_SECONDS / 60)
        );

        if (!$sendRes['ok']) {
            // Consume failed OTP row so stale hashes aren't left around
            if ($rowId > 0) {
                $db->table('otp_codes')->where('id', $rowId)->update([
                    'consumed_at' => date('Y-m-d H:i:s', $now),
                ]);
            }
            $this->guard->logSecurityEvent(
                'OTP_SEND_FAILED',
                'warning',
                $email,
                'Gagal kirim OTP (' . $purpose . '): ' . ($sendRes['debug'] ?? $sendRes['message'])
            );

            return [
                'ok'          => false,
                'retry_after' => 0,
                'message'     => $sendRes['message'],
                'code_sent'   => false,
            ];
        }

        return [
            'ok'          => true,
            'retry_after' => self::RESEND_COOLDOWN,
            'message'     => 'Kode OTP 6 digit telah dikirim ke ' . $email . '.',
            'code_sent'   => true,
        ];
    }

    /**
     * Fetch payload saved on the most recent register OTP record (for Resend without re-entering password).
     */
    public function getLatestRegisterPayload(string $email): ?array
    {
        $this->ensureSchema();
        $db  = \Config\Database::connect();
        $row = $db->table('otp_codes')
            ->where('purpose', 'register')
            ->where('email', strtolower(trim($email)))
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$row || empty($row['payload_json'])) {
            return null;
        }
        $decoded = json_decode((string) $row['payload_json'], true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Remaining seconds before user can resend OTP to this email+purpose.
     */
    public function getResendCooldownRemaining(string $purpose, string $email): int
    {
        $dir  = rtrim(WRITEPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'ratelimit';
        $file = $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-zA-Z0-9_-]/', '_', 'otp_cd_' . $purpose) . '_' . md5(strtolower(trim($email))) . '.json';
        if (!is_file($file)) {
            return 0;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        $hits = is_array($data['hits'] ?? null) ? $data['hits'] : [];
        if (empty($hits)) {
            return 0;
        }
        $last = (int) end($hits);
        $rem  = ($last + self::RESEND_COOLDOWN) - time();
        return max(0, $rem);
    }

    /**
     * Verify submitted 6-digit OTP code; increments attempts and invalidates after MAX_ATTEMPTS.
     *
     * @return array{ok: bool, message: string, payload: array|null}
     */
    public function verifyOtp(string $purpose, string $email, string $submittedCode, string $ipKey): array
    {
        $email = strtolower(trim($email));
        $code  = preg_replace('/\D/', '', trim($submittedCode));

        if (!preg_match('/^\d{6}$/', $code)) {
            return ['ok' => false, 'message' => 'Kode OTP wajib berupa 6 digit angka.', 'payload' => null];
        }

        $this->ensureSchema();
        $db  = \Config\Database::connect();
        $now = time();

        $row = $db->table('otp_codes')
            ->where('purpose', $purpose)
            ->where('email', $email)
            ->where('consumed_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$row) {
            return [
                'ok'      => false,
                'message' => 'Kode OTP tidak ditemukan atau sudah kedaluwarsa. Silakan kirim ulang OTP.',
                'payload' => null,
            ];
        }

        $expiresTs = strtotime((string) ($row['expires_at'] ?? '')) ?: 0;
        if ($expiresTs < $now) {
            $db->table('otp_codes')->where('id', $row['id'])->update([
                'consumed_at' => date('Y-m-d H:i:s', $now),
            ]);
            return [
                'ok'      => false,
                'message' => 'Kode OTP sudah kedaluwarsa (melebihi 10 menit). Silakan minta kode baru.',
                'payload' => null,
            ];
        }

        $attempts    = (int) ($row['attempts'] ?? 0);
        $maxAttempts = max(1, (int) ($row['max_attempts'] ?? self::MAX_ATTEMPTS));
        if ($attempts >= $maxAttempts) {
            $db->table('otp_codes')->where('id', $row['id'])->update([
                'consumed_at' => date('Y-m-d H:i:s', $now),
            ]);
            return [
                'ok'      => false,
                'message' => 'Terlalu banyak salah memasukkan OTP. Silakan kirim ulang kode baru.',
                'payload' => null,
            ];
        }

        $expectedHash = (string) ($row['code_hash'] ?? '');
        $givenHash    = $this->hashOtp($code, $purpose, $email);

        if (!hash_equals($expectedHash, $givenHash)) {
            $nextAttempts = $attempts + 1;
            $updateData   = ['attempts' => $nextAttempts];
            if ($nextAttempts >= $maxAttempts) {
                $updateData['consumed_at'] = date('Y-m-d H:i:s', $now);
            }
            $db->table('otp_codes')->where('id', $row['id'])->update($updateData);
            $this->guard->recordStrike($ipKey);

            $rem = max(0, $maxAttempts - $nextAttempts);
            $msg = $rem > 0
                ? 'Kode OTP salah. Sisa percobaan: ' . $rem . ' kali.'
                : 'Kode OTP hangus karena 5x salah input. Silakan minta kode OTP baru.';

            return ['ok' => false, 'message' => $msg, 'payload' => null];
        }

        // Valid OTP: consume immediately and stamp verified_at (single-use)
        $nowSql = date('Y-m-d H:i:s', $now);
        $db->table('otp_codes')->where('id', $row['id'])->update([
            'verified_at' => $nowSql,
            'consumed_at' => $nowSql,
        ]);

        $payload = null;
        if (!empty($row['payload_json'])) {
            $decoded = json_decode((string) $row['payload_json'], true);
            $payload = is_array($decoded) ? $decoded : null;
        }

        return ['ok' => true, 'message' => 'Kode OTP valid.', 'payload' => $payload];
    }

    /**
     * Get 24-hour OTP issuance statistics for Admin dashboard.
     *
     * @return array{total_24h: int, verified_24h: int}
     */
    public function getAdminStats24h(): array
    {
        try {
            $this->ensureSchema();
            $db    = \Config\Database::connect();
            $since = date('Y-m-d H:i:s', time() - 86400);
            $total = (int) $db->table('otp_codes')->where('created_at >=', $since)->countAllResults();
            $used  = (int) $db->table('otp_codes')->where('created_at >=', $since)->where('verified_at IS NOT NULL', null, false)->countAllResults();
            return ['total_24h' => $total, 'verified_24h' => $used];
        } catch (\Throwable $e) {
            return ['total_24h' => 0, 'verified_24h' => 0];
        }
    }

    private function hashOtp(string $otpCode, string $purpose, string $email): string
    {
        $envKey = function_exists('env') ? env('encryption.key', '') : '';
        $secret = trim((string) (getenv('encryption.key') ?: ($_ENV['encryption.key'] ?? $envKey)));
        if ($secret === '') {
            $secret = hash('sha256', ROOTPATH . 'pwned_otp_hmac_salt_v1');
        }
        return hash_hmac('sha256', $otpCode . '|' . $purpose . '|' . strtolower(trim($email)), $secret);
    }
}
