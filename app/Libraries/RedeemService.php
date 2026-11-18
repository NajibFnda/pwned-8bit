<?php

namespace App\Libraries;

/**
 * Manages subscription redeem codes and atomic user redemptions.
 */
class RedeemService
{
    private const CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * Ensure redeem_codes and redeem_claims tables exist (idempotent).
     */
    public function ensureTables(): void
    {
        $dir    = rtrim(WRITEPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'ratelimit';
        $marker = is_dir($dir) ? ($dir . DIRECTORY_SEPARATOR . '.redeem_ready') : null;
        if ($marker && is_file($marker)) {
            return;
        }

        $db = \Config\Database::connect();
        $db->query("
            CREATE TABLE IF NOT EXISTS `redeem_codes` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(32) NOT NULL,
                `plan` ENUM('plus','pro') NOT NULL DEFAULT 'plus',
                `duration_days` INT UNSIGNED NOT NULL DEFAULT 30,
                `max_uses` INT UNSIGNED NOT NULL DEFAULT 1,
                `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `expires_at` DATETIME NULL DEFAULT NULL,
                `created_by` INT UNSIGNED NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_redeem_code` (`code`),
                KEY `idx_redeem_active` (`is_active`, `expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `redeem_claims` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `redeem_code_id` INT UNSIGNED NOT NULL,
                `user_id` INT UNSIGNED NOT NULL,
                `claimed_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_code_user` (`redeem_code_id`, `user_id`),
                KEY `idx_claim_user` (`user_id`),
                CONSTRAINT `fk_claim_code` FOREIGN KEY (`redeem_code_id`) REFERENCES `redeem_codes` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_claim_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        if ($marker) {
            @file_put_contents($marker, '1');
        }
    }

    /**
     * Generate or validate and persist a new redeem code created by Admin.
     *
     * @return array{ok: bool, message: string, code?: string}
     */
    public function createCode(
        string $rawCode,
        string $plan,
        int $durationDays,
        int $maxUses,
        ?string $expiresAt,
        int $adminId
    ): array {
        $this->ensureTables();

        if (!in_array($plan, ['plus', 'pro'], true)) {
            return ['ok' => false, 'message' => 'Paket redeem harus PLUS atau PRO.'];
        }
        if ($durationDays < 1 || $durationDays > 3650) {
            return ['ok' => false, 'message' => 'Durasi harus antara 1 hingga 3650 hari.'];
        }
        if ($maxUses < 1 || $maxUses > 10000) {
            return ['ok' => false, 'message' => 'Kuota pemakaian harus antara 1 hingga 10.000.'];
        }

        $code = strtoupper(trim($rawCode));
        if ($code === '') {
            $code = $this->generateUniqueCode($plan);
        } else {
            if (!preg_match('/^[A-Z0-9-]{4,32}$/', $code)) {
                return ['ok' => false, 'message' => 'Kode redeem hanya boleh huruf kapital, angka, dan tanda (-) sepanjang 4-32 karakter.'];
            }
        }

        $expNormalized = null;
        if ($expiresAt !== null && trim($expiresAt) !== '') {
            $ts = strtotime(trim($expiresAt));
            if ($ts === false) {
                return ['ok' => false, 'message' => 'Format tanggal kadaluwarsa kode tidak valid.'];
            }
            // If only YYYY-MM-DD was provided, set end of day
            $expNormalized = strlen(trim($expiresAt)) <= 10
                ? date('Y-m-d 23:59:59', $ts)
                : date('Y-m-d H:i:s', $ts);
        }

        $db       = \Config\Database::connect();
        $existing = $db->table('redeem_codes')->where('code', $code)->get()->getRowArray();
        if ($existing) {
            return ['ok' => false, 'message' => 'Kode redeem "' . $code . '" sudah pernah dibuat. Gunakan kode lain.'];
        }

        $db->table('redeem_codes')->insert([
            'code'          => $code,
            'plan'          => $plan,
            'duration_days' => $durationDays,
            'max_uses'      => $maxUses,
            'used_count'    => 0,
            'is_active'     => 1,
            'expires_at'    => $expNormalized,
            'created_by'    => $adminId > 0 ? $adminId : null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok'      => true,
            'message' => 'Kode redeem ' . $code . ' (' . strtoupper($plan) . ', ' . $durationDays . ' hari, kuota ' . $maxUses . 'x) berhasil dibuat.',
            'code'    => $code,
        ];
    }

    /**
     * Atomically redeem a code for an authenticated user, activating/extending their subscription.
     *
     * @return array{ok: bool, message: string, plan?: string, expire_date?: string}
     */
    public function redeemForUser(int $userId, string $rawCode): array
    {
        $this->ensureTables();
        $code = strtoupper(trim($rawCode));

        if ($code === '' || !preg_match('/^[A-Z0-9-]{4,32}$/', $code)) {
            return ['ok' => false, 'message' => 'Format kode redeem tidak valid.'];
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $codeRow = $db->query('SELECT * FROM `redeem_codes` WHERE `code` = ? FOR UPDATE', [$code])->getRowArray();
            if (!$codeRow) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Kode redeem tidak ditemukan atau salah.'];
            }

            if ((int) ($codeRow['is_active'] ?? 0) !== 1) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Kode redeem ini sedang dinonaktifkan oleh Admin.'];
            }

            if (!empty($codeRow['expires_at']) && strtotime((string) $codeRow['expires_at']) < time()) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Masa berlaku kode redeem ini sudah habis.'];
            }

            if ((int) $codeRow['used_count'] >= (int) $codeRow['max_uses']) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Kuota pemakaian kode redeem ini sudah habis.'];
            }

            $alreadyClaimed = $db->table('redeem_claims')
                ->where('redeem_code_id', (int) $codeRow['id'])
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            if ($alreadyClaimed) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Anda sudah pernah mengklaim kode redeem ini sebelumnya.'];
            }

            $userRow = $db->query('SELECT id, subscription_plan, expire_date FROM `users` WHERE `id` = ? FOR UPDATE', [$userId])->getRowArray();
            if (!$userRow) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Akun pengguna tidak ditemukan.'];
            }

            $targetPlan   = (string) $codeRow['plan'];
            $durationDays = max(1, (int) $codeRow['duration_days']);
            $currentPlan  = (string) ($userRow['subscription_plan'] ?? 'free');
            $currentExpTs = !empty($userRow['expire_date']) ? strtotime((string) $userRow['expire_date']) : 0;
            $hasActivePro = $currentPlan === 'pro' && ($currentExpTs === 0 || $currentExpTs > time());

            if ($hasActivePro && $targetPlan === 'plus') {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Akun Anda sedang aktif di paket PRO, sehingga tidak dapat diturunkan dengan kode PLUS.'];
            }

            $baseTs = time();
            if ($currentPlan === $targetPlan && $currentExpTs > time()) {
                $baseTs = $currentExpTs;
            }
            $newExpireDate = date('Y-m-d', strtotime('+' . $durationDays . ' days', $baseTs));

            $db->table('redeem_claims')->insert([
                'redeem_code_id' => (int) $codeRow['id'],
                'user_id'        => $userId,
                'claimed_at'     => date('Y-m-d H:i:s'),
            ]);

            $db->query(
                'UPDATE `redeem_codes` SET `used_count` = `used_count` + 1 WHERE `id` = ? AND `used_count` < `max_uses`',
                [(int) $codeRow['id']]
            );
            if ($db->affectedRows() < 1) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Kuota pemakaian kode redeem sudah habis.'];
            }

            $db->table('users')->where('id', $userId)->update([
                'subscription_plan' => $targetPlan,
                'expire_date'       => $newExpireDate,
                'pending_plan'      => null,
            ]);

            $db->transCommit();

            // Sync active browser session immediately
            $session = \Config\Services::session();
            if ((int) $session->get('id') === $userId) {
                $session->set([
                    'subscription_plan' => $targetPlan,
                    'expire_date'       => $newExpireDate,
                ]);
            }

            return [
                'ok'          => true,
                'message'     => 'Kode ' . $code . ' berhasil diklaim! Paket ' . strtoupper($targetPlan) . ' aktif hingga ' . date('d M Y', strtotime($newExpireDate)) . '.',
                'plan'        => $targetPlan,
                'expire_date' => $newExpireDate,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[RedeemService] Error claiming code: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Gagal memproses klaim kode redeem. Silakan coba lagi.'];
        }
    }

    /**
     * Fetch all redeem codes with recent claims for Admin panel.
     *
     * @return array{codes: array<int, array<string, mixed>>, claims: array<int, array<string, mixed>>}
     */
    public function getAdminOverview(): array
    {
        $this->ensureTables();
        $db = \Config\Database::connect();

        $codes = $db->table('redeem_codes')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $claims = $db->table('redeem_claims c')
            ->select('c.*, rc.code, rc.plan, rc.duration_days, u.nama, u.email')
            ->join('redeem_codes rc', 'rc.id = c.redeem_code_id')
            ->join('users u', 'u.id = c.user_id')
            ->orderBy('c.claimed_at', 'DESC')
            ->limit(25)
            ->get()
            ->getResultArray();

        return ['codes' => $codes, 'claims' => $claims];
    }

    public function toggleCode(int $id): ?array
    {
        $this->ensureTables();
        $db  = \Config\Database::connect();
        $row = $db->table('redeem_codes')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return null;
        }
        $next = ((int) ($row['is_active'] ?? 0) === 1) ? 0 : 1;
        $db->table('redeem_codes')->where('id', $id)->update(['is_active' => $next]);
        $row['is_active'] = $next;
        return $row;
    }

    public function deleteCode(int $id): ?array
    {
        $this->ensureTables();
        $db  = \Config\Database::connect();
        $row = $db->table('redeem_codes')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return null;
        }
        $db->table('redeem_claims')->where('redeem_code_id', $id)->delete();
        $db->table('redeem_codes')->where('id', $id)->delete();
        return $row;
    }

    private function generateUniqueCode(string $plan): string
    {
        $db       = \Config\Database::connect();
        $alphabet = self::CODE_ALPHABET;
        $maxIdx   = strlen($alphabet) - 1;
        $prefix   = $plan === 'pro' ? 'PRO' : 'PLUS';

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $part1 = '';
            $part2 = '';
            for ($i = 0; $i < 4; $i++) {
                $part1 .= $alphabet[random_int(0, $maxIdx)];
                $part2 .= $alphabet[random_int(0, $maxIdx)];
            }
            $candidate = 'PWN-' . $prefix . '-' . $part1 . $part2;
            $exists    = $db->table('redeem_codes')->where('code', $candidate)->countAllResults();
            if ($exists === 0) {
                return $candidate;
            }
        }

        return 'PWN-' . strtoupper(bin2hex(random_bytes(5)));
    }
}
