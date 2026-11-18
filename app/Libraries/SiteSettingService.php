<?php

namespace App\Libraries;

/**
 * Manages global site settings such as the Admin announcement banner on the homepage.
 */
class SiteSettingService
{
    private const CACHE_KEY = 'site_announcement_v1';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Ensure site_settings table exists (idempotent).
     */
    public function ensureTable(): void
    {
        $dir    = rtrim(WRITEPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'ratelimit';
        $marker = is_dir($dir) ? ($dir . DIRECTORY_SEPARATOR . '.settings_ready') : null;
        if ($marker && is_file($marker)) {
            return;
        }

        $db = \Config\Database::connect();
        $db->query("
            CREATE TABLE IF NOT EXISTS `site_settings` (
                `setting_key` VARCHAR(64) NOT NULL,
                `setting_value` TEXT NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        if ($marker) {
            @file_put_contents($marker, '1');
        }
    }

    /**
     * Fetch the active/saved announcement banner configuration (cached).
     *
     * @return array{active: bool, title: string, message: string, style: string, updated_at: string|null}
     */
    public function getAnnouncement(): array
    {
        $default = [
            'active'     => false,
            'title'      => 'PENGUMUMAN',
            'message'    => '',
            'style'      => 'info',
            'updated_at' => null,
        ];

        $cached = cache()->get(self::CACHE_KEY);
        if (is_array($cached)) {
            return array_merge($default, $cached);
        }

        try {
            $this->ensureTable();
            $db  = \Config\Database::connect();
            $row = $db->table('site_settings')->where('setting_key', 'announcement')->get()->getRowArray();

            if ($row && !empty($row['setting_value'])) {
                $decoded = json_decode((string) $row['setting_value'], true);
                if (is_array($decoded)) {
                    $result = array_merge($default, [
                        'active'     => !empty($decoded['active']),
                        'title'      => (string) ($decoded['title'] ?? 'PENGUMUMAN'),
                        'message'    => (string) ($decoded['message'] ?? ''),
                        'style'      => in_array($decoded['style'] ?? 'info', ['info', 'warning', 'danger'], true)
                            ? (string) $decoded['style']
                            : 'info',
                        'updated_at' => $row['updated_at'] ?? null,
                    ]);
                    cache()->save(self::CACHE_KEY, $result, self::CACHE_TTL);
                    return $result;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[SiteSettingService] Read skipped: ' . $e->getMessage());
        }

        cache()->save(self::CACHE_KEY, $default, self::CACHE_TTL);
        return $default;
    }

    /**
     * Save or toggle the global homepage announcement banner and invalidate cache.
     *
     * @return array{ok: bool, message: string}
     */
    public function saveAnnouncement(bool $active, string $title, string $message, string $style): array
    {
        $this->ensureTable();

        $title   = mb_substr(trim($title), 0, 60);
        $message = mb_substr(trim($message), 0, 350);
        if (!in_array($style, ['info', 'warning', 'danger'], true)) {
            $style = 'info';
        }
        if ($title === '') {
            $title = 'PENGUMUMAN';
        }
        if ($active && $message === '') {
            return ['ok' => false, 'message' => 'Isi pesan pengumuman wajib diisi jika banner diaktifkan.'];
        }

        $payload = [
            'active'  => $active,
            'title'   => $title,
            'message' => $message,
            'style'   => $style,
        ];

        $now     = date('Y-m-d H:i:s');
        $json    = (string) json_encode($payload, JSON_UNESCAPED_UNICODE);
        $db      = \Config\Database::connect();
        $exists  = $db->table('site_settings')->where('setting_key', 'announcement')->countAllResults();

        if ($exists > 0) {
            $db->table('site_settings')->where('setting_key', 'announcement')->update([
                'setting_value' => $json,
                'updated_at'    => $now,
            ]);
        } else {
            $db->table('site_settings')->insert([
                'setting_key'   => 'announcement',
                'setting_value' => $json,
                'updated_at'    => $now,
            ]);
        }

        cache()->delete(self::CACHE_KEY);

        return [
            'ok'      => true,
            'message' => $active ? 'Banner pengumuman diaktifkan di halaman beranda.' : 'Banner pengumuman dinonaktifkan.',
        ];
    }
}
