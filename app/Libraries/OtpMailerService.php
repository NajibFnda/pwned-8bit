<?php

namespace App\Libraries;

use Config\Services;

/**
 * Handles OTP & transactional email delivery over InfinityFree-compatible channels:
 * - Brevo REST API (HTTPS port 443 — bypasses shared hosting SMTP restrictions)
 * - SMTP Port 587 STARTTLS (e.g., Gmail App Password via CodeIgniter Email library)
 * - Local non-production outbox log ('log' driver, blocked in production)
 */
class OtpMailerService
{
    private const SETTING_KEY = 'otp_mailer';
    private const CACHE_KEY   = 'otp_mailer_cfg_v1';
    private const CACHE_TTL   = 300;

    /**
     * Load effective email settings (merging DB site_settings with optional .env overrides).
     *
     * @return array{
     *   driver: string,
     *   from_email: string,
     *   from_name: string,
     *   brevo_api_key: string,
     *   smtp_host: string,
     *   smtp_port: int,
     *   smtp_user: string,
     *   smtp_pass: string,
     *   require_register_otp: bool
     * }
     */
    public function getConfig(): array
    {
        $cfg = [
            'driver'               => 'off',
            'from_email'           => '',
            'from_name'            => 'PWNED Security',
            'brevo_api_key'        => '',
            'smtp_host'            => 'smtp.gmail.com',
            'smtp_port'            => 587,
            'smtp_user'            => '',
            'smtp_pass'            => '',
            'require_register_otp' => false,
        ];

        $stored = cache()->get(self::CACHE_KEY);
        if (!is_array($stored)) {
            $stored = $this->readDbConfig();
            cache()->save(self::CACHE_KEY, $stored, self::CACHE_TTL);
        }

        if (!empty($stored)) {
            $cfg['driver']               = in_array($stored['driver'] ?? 'off', ['off', 'brevo', 'smtp', 'log'], true) ? (string) $stored['driver'] : 'off';
            $cfg['from_email']           = trim((string) ($stored['from_email'] ?? ''));
            $cfg['from_name']            = trim((string) ($stored['from_name'] ?? 'PWNED Security')) ?: 'PWNED Security';
            $cfg['brevo_api_key']        = $this->decryptSecret((string) ($stored['brevo_api_key_enc'] ?? ''));
            $cfg['smtp_host']            = trim((string) ($stored['smtp_host'] ?? 'smtp.gmail.com')) ?: 'smtp.gmail.com';
            $cfg['smtp_port']            = (int) ($stored['smtp_port'] ?? 587) ?: 587;
            $cfg['smtp_user']            = trim((string) ($stored['smtp_user'] ?? ''));
            $cfg['smtp_pass']            = $this->decryptSecret((string) ($stored['smtp_pass_enc'] ?? ''));
            $cfg['require_register_otp'] = !empty($stored['require_register_otp']);
        }

        // Allow .env values to override or supply credentials if provided
        $envDriver = strtolower($this->readEnv('MAIL_DRIVER'));
        if (in_array($envDriver, ['off', 'brevo', 'smtp', 'log'], true)) {
            $cfg['driver'] = $envDriver;
        }
        if ($this->readEnv('MAIL_FROM_EMAIL') !== '') {
            $cfg['from_email'] = $this->readEnv('MAIL_FROM_EMAIL');
        }
        if ($this->readEnv('MAIL_FROM_NAME') !== '') {
            $cfg['from_name'] = $this->readEnv('MAIL_FROM_NAME');
        }
        if ($this->readEnv('BREVO_API_KEY') !== '') {
            $cfg['brevo_api_key'] = $this->readEnv('BREVO_API_KEY');
        }
        if ($this->readEnv('SMTP_HOST') !== '') {
            $cfg['smtp_host'] = $this->readEnv('SMTP_HOST');
        }
        if ($this->readEnv('SMTP_PORT') !== '') {
            $cfg['smtp_port'] = (int) $this->readEnv('SMTP_PORT') ?: 587;
        }
        if ($this->readEnv('SMTP_USER') !== '') {
            $cfg['smtp_user'] = $this->readEnv('SMTP_USER');
        }
        if ($this->readEnv('SMTP_PASS') !== '') {
            $cfg['smtp_pass'] = $this->readEnv('SMTP_PASS');
        }
        if ($this->readEnv('OTP_REGISTER_REQUIRED') !== '') {
            $cfg['require_register_otp'] = in_array(strtolower($this->readEnv('OTP_REGISTER_REQUIRED')), ['1', 'true', 'yes', 'on'], true);
        }

        // Never allow log-only pseudo delivery in production
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production' && $cfg['driver'] === 'log') {
            $cfg['driver'] = 'off';
        }

        if ($cfg['driver'] === 'smtp' && $cfg['from_email'] === '' && filter_var($cfg['smtp_user'], FILTER_VALIDATE_EMAIL)) {
            $cfg['from_email'] = $cfg['smtp_user'];
        }

        return $cfg;
    }

    /**
     * Safe configuration view for Admin panel (never returns raw API keys or SMTP passwords).
     */
    public function getPublicConfig(): array
    {
        $cfg = $this->getConfig();
        return [
            'driver'               => $cfg['driver'],
            'from_email'           => $cfg['from_email'],
            'from_name'            => $cfg['from_name'],
            'has_brevo_key'        => $cfg['brevo_api_key'] !== '',
            'brevo_key_len'        => strlen($cfg['brevo_api_key']),
            'smtp_host'            => $cfg['smtp_host'],
            'smtp_port'            => $cfg['smtp_port'],
            'smtp_user'            => $cfg['smtp_user'],
            'has_smtp_pass'        => $cfg['smtp_pass'] !== '',
            'smtp_pass_len'        => strlen(preg_replace('/\s+/', '', $cfg['smtp_pass']) ?? $cfg['smtp_pass']),
            'require_register_otp' => $cfg['require_register_otp'],
            'is_configured'        => $this->isConfigured($cfg),
        ];
    }

    public function isConfigured(?array $cfg = null): bool
    {
        $cfg ??= $this->getConfig();
        return match ($cfg['driver']) {
            'brevo' => $cfg['brevo_api_key'] !== '' && filter_var($cfg['from_email'], FILTER_VALIDATE_EMAIL) !== false,
            'smtp'  => $cfg['smtp_host'] !== '' && $cfg['smtp_user'] !== '' && $cfg['smtp_pass'] !== '' && filter_var($cfg['from_email'], FILTER_VALIDATE_EMAIL) !== false,
            'log'   => !defined('ENVIRONMENT') || ENVIRONMENT !== 'production',
            default => false,
        };
    }

    public function isRegisterOtpRequired(): bool
    {
        $cfg = $this->getConfig();
        return $this->isConfigured($cfg) && !empty($cfg['require_register_otp']);
    }

    /**
     * Persist Admin email/OTP settings safely into site_settings (preserving omitted secrets).
     *
     * @return array{ok: bool, message: string}
     */
    public function saveSettings(array $input): array
    {
        $driver = in_array($input['driver'] ?? 'off', ['off', 'brevo', 'smtp'], true)
            ? (string) $input['driver']
            : 'off';

        $current = $this->readDbConfig();

        $fromEmail = strtolower(trim((string) ($input['from_email'] ?? '')));
        $fromName  = mb_substr(trim((string) ($input['from_name'] ?? 'PWNED Security')), 0, 60) ?: 'PWNED Security';
        $smtpHost  = mb_substr(trim((string) ($input['smtp_host'] ?? 'smtp.gmail.com')), 0, 120) ?: 'smtp.gmail.com';
        $smtpPort  = (int) ($input['smtp_port'] ?? 587);
        if (!in_array($smtpPort, [587, 465, 2525, 25], true)) {
            $smtpPort = 587;
        }
        $smtpUser = trim((string) ($input['smtp_user'] ?? ''));

        $newBrevoKey = trim((string) ($input['brevo_api_key'] ?? ''));
        $brevoEnc    = $newBrevoKey !== ''
            ? $this->encryptSecret($newBrevoKey)
            : (string) ($current['brevo_api_key_enc'] ?? '');

        $newSmtpPass = trim((string) ($input['smtp_pass'] ?? ''));
        if ($newSmtpPass !== '' && str_contains(strtolower($smtpHost), 'gmail')) {
            $compactPass = preg_replace('/\s+/', '', $newSmtpPass);
            if ($compactPass !== null && strlen($compactPass) === 16) {
                $newSmtpPass = $compactPass;
            }
        }
        $smtpPassEnc = $newSmtpPass !== ''
            ? $this->encryptSecret($newSmtpPass)
            : (string) ($current['smtp_pass_enc'] ?? '');

        if ($driver === 'smtp' && $fromEmail === '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = strtolower($smtpUser);
        }

        if ($driver === 'brevo') {
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'message' => 'From Email pengirim wajib valid (harus sama dengan Single Sender di akun Brevo).'];
            }
            if ($brevoEnc === '' && $this->readEnv('BREVO_API_KEY') === '') {
                return ['ok' => false, 'message' => 'Brevo API Key wajib diisi saat memilih driver Brevo API.'];
            }
        }

        if ($driver === 'smtp') {
            if ($smtpHost === '' || $smtpUser === '') {
                return ['ok' => false, 'message' => 'SMTP Host dan SMTP Username wajib diisi.'];
            }
            if (strtolower($smtpUser) === strtolower($smtpHost)) {
                return ['ok' => false, 'message' => 'SMTP Username harus diisi alamat email login (contoh: emailanda@gmail.com), bukan alamat host "' . $smtpHost . '".'];
            }
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'message' => 'From Email atau SMTP Username harus berupa alamat email yang valid.'];
            }
            if ($smtpPassEnc === '' && $this->readEnv('SMTP_PASS') === '') {
                return ['ok' => false, 'message' => 'SMTP Password / App Password wajib diisi.'];
            }
        }

        $payload = [
            'driver'               => $driver,
            'from_email'           => $fromEmail,
            'from_name'            => $fromName,
            'brevo_api_key_enc'    => $brevoEnc,
            'smtp_host'            => $smtpHost,
            'smtp_port'            => $smtpPort,
            'smtp_user'            => $smtpUser,
            'smtp_pass_enc'        => $smtpPassEnc,
            'require_register_otp' => !empty($input['require_register_otp']),
        ];

        $settings = new SiteSettingService();
        $settings->ensureTable();
        $db     = \Config\Database::connect();
        $now    = date('Y-m-d H:i:s');
        $json   = (string) json_encode($payload, JSON_UNESCAPED_UNICODE);
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

        cache()->delete(self::CACHE_KEY);

        return [
            'ok'      => true,
            'message' => $driver === 'off'
                ? 'Pengiriman Email OTP dinonaktifkan.'
                : 'Pengaturan Email OTP (' . strtoupper($driver) . ') berhasil disimpan.',
        ];
    }

    /**
     * Send formatted 6-digit OTP email.
     *
     * @return array{ok: bool, message: string, debug: string}
     */
    public function sendOtpEmail(string $toEmail, string $toName, string $otpCode, string $purpose, int $ttlMinutes = 10): array
    {
        $purposeTitle = match ($purpose) {
            'register'     => 'Verifikasi Pendaftaran Akun Baru',
            'forgot_pwd'   => 'Reset Kata Sandi Akun',
            'verify_email' => 'Verifikasi Alamat Email Profil',
            default        => 'Verifikasi Keamanan Akun',
        };

        $safeName = htmlspecialchars($toName !== '' ? $toName : 'Pengguna PWNED', ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
        $subject  = '[PWNED] Kode OTP Anda: ' . $otpCode . ' (' . $purposeTitle . ')';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:monospace;color:#111827;">
  <div style="max-width:520px;margin:0 auto;background:#ffffff;border:4px solid #000000;box-shadow:6px 6px 0 #000000;padding:24px;">
    <div style="background:#000000;color:#ffffff;padding:10px 14px;font-weight:bold;font-size:14px;letter-spacing:2px;">
      PWNED // VERIFIKASI OTP KEAMANAN
    </div>
    <p style="font-size:13px;line-height:1.6;margin:18px 0 8px;">Halo <strong>{$safeName}</strong>,</p>
    <p style="font-size:13px;line-height:1.6;margin:0 0 16px;">
      Berikut adalah kode verifikasi OTP untuk <strong>{$purposeTitle}</strong>:
    </p>
    <div style="background:#eff6ff;border:3px solid #000000;padding:16px;text-align:center;margin:18px 0;">
      <span style="font-size:30px;font-weight:bold;letter-spacing:8px;color:#1d4ed8;">{$safeCode}</span>
    </div>
    <p style="font-size:12px;line-height:1.5;color:#374151;margin:0 0 12px;">
      Kode ini berlaku selama <strong>{$ttlMinutes} menit</strong> dan hanya dapat digunakan satu kali.
    </p>
    <p style="font-size:11px;line-height:1.5;color:#dc2626;font-weight:bold;border-top:2px dashed #9ca3af;padding-top:12px;margin:16px 0 0;">
      PERINGATAN KEAMANAN: Jangan berikan kode OTP ini kepada siapa pun termasuk pihak yang mengaku admin. Jika Anda tidak melakukan permintaan ini, abaikan email ini.
    </p>
  </div>
</body>
</html>
HTML;

        $text = "PWNED // {$purposeTitle}\n\n"
            . "Halo {$toName},\n"
            . "Kode OTP Anda: {$otpCode}\n"
            . "Berlaku selama {$ttlMinutes} menit (1x pakai).\n\n"
            . "Jangan bagikan kode ini kepada siapa pun.";

        return $this->sendEmail($toEmail, $toName, $subject, $html, $text);
    }

    /**
     * Send an email using the active configured InfinityFree-safe transport.
     *
     * @return array{ok: bool, message: string, debug: string}
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): array
    {
        $cfg = $this->getConfig();
        if (!$this->isConfigured($cfg)) {
            return [
                'ok'      => false,
                'message' => 'Layanan pengiriman email OTP belum dikonfigurasi oleh Admin.',
                'debug'   => 'driver=' . $cfg['driver'],
            ];
        }

        return match ($cfg['driver']) {
            'brevo' => $this->sendViaBrevo($cfg, $toEmail, $toName, $subject, $htmlBody, $textBody),
            'smtp'  => $this->sendViaSmtp($cfg, $toEmail, $toName, $subject, $htmlBody, $textBody),
            'log'   => $this->sendToDevOutbox($cfg, $toEmail, $toName, $subject, $textBody),
            default => ['ok' => false, 'message' => 'Driver email tidak aktif.', 'debug' => 'off'],
        };
    }

    private function sendViaBrevo(array $cfg, string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): array
    {
        try {
            $client = Services::curlrequest([
                'timeout'         => 8,
                'connect_timeout' => 4,
                'verify'          => true,
                'allow_redirects' => false,
                'http_errors'     => false,
            ], null, null, false);

            $payload = [
                'sender' => [
                    'name'  => $cfg['from_name'],
                    'email' => $cfg['from_email'],
                ],
                'to' => [
                    [
                        'email' => $toEmail,
                        'name'  => $toName !== '' ? $toName : $toEmail,
                    ],
                ],
                'subject'     => $subject,
                'htmlContent' => $htmlBody,
                'textContent' => $textBody,
            ];

            $res = $client->post('https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'accept'       => 'application/json',
                    'api-key'      => $cfg['brevo_api_key'],
                    'content-type' => 'application/json',
                ],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $status = $res->getStatusCode();
            $body   = (string) $res->getBody();

            if ($status >= 200 && $status < 300) {
                return ['ok' => true, 'message' => 'Email terkirim via Brevo API.', 'debug' => 'HTTP ' . $status];
            }

            $decoded = json_decode($body, true);
            $errMsg  = is_array($decoded) ? (string) ($decoded['message'] ?? $body) : $body;
            log_message('error', '[OtpMailerService] Brevo HTTP ' . $status . ': ' . $errMsg);

            return [
                'ok'      => false,
                'message' => 'Gagal mengirim email OTP melalui Brevo API.',
                'debug'   => 'HTTP ' . $status . ': ' . mb_substr($errMsg, 0, 200),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[OtpMailerService] Brevo exception: ' . $e->getMessage());
            return [
                'ok'      => false,
                'message' => 'Koneksi ke server email gagal.',
                'debug'   => $e->getMessage(),
            ];
        }
    }

    private function sendViaSmtp(array $cfg, string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): array
    {
        try {
            $email = Services::email(null, false);
            $port  = (int) $cfg['smtp_port'];
            $crypto = match ($port) {
                465     => 'ssl',
                default => 'tls',
            };

            $smtpPass = (string) $cfg['smtp_pass'];
            if (str_contains(strtolower((string) $cfg['smtp_host']), 'gmail')) {
                $compact = preg_replace('/\s+/', '', $smtpPass);
                if ($compact !== null && strlen($compact) === 16) {
                    $smtpPass = $compact;
                }
            }

            $email->initialize([
                'protocol'    => 'smtp',
                'SMTPHost'    => $cfg['smtp_host'],
                'SMTPUser'    => $cfg['smtp_user'],
                'SMTPPass'    => $smtpPass,
                'SMTPPort'    => $port,
                'SMTPCrypto'  => $crypto,
                'SMTPTimeout' => 8,
                'mailType'    => 'html',
                'charset'     => 'UTF-8',
                'wordWrap'    => true,
                'CRLF'        => "\r\n",
                'newline'     => "\r\n",
            ]);

            $email->clear(true);
            $email->setFrom($cfg['from_email'], $cfg['from_name']);
            $email->setTo($toEmail);
            $email->setSubject($subject);
            $email->setMessage($htmlBody);
            $email->setAltMessage($textBody);

            if ($email->send(false)) {
                return ['ok' => true, 'message' => 'Email terkirim via SMTP (' . $cfg['smtp_host'] . ':' . $port . ').', 'debug' => 'SMTP OK'];
            }

            $dbg = trim(strip_tags((string) $email->printDebugger(['headers'])));
            log_message('error', '[OtpMailerService] SMTP gagal: ' . mb_substr($dbg, 0, 500));

            $shortDbg = $dbg;
            if (preg_match('/(Failed to authenticate[^\n\r]*|Unable to send email[^\n\r]*|\b5\d{2}[\s-].*)/i', $dbg, $dm)) {
                $shortDbg = trim($dm[1]);
            } elseif (mb_strlen($dbg) > 220) {
                $shortDbg = mb_substr($dbg, -220);
            }

            return [
                'ok'      => false,
                'message' => 'Gagal mengirim email via SMTP Port ' . $port . '.',
                'debug'   => mb_substr($shortDbg, 0, 220),
            ];
        } catch (\Throwable $e) {
            log_message('error', '[OtpMailerService] SMTP exception: ' . $e->getMessage());
            return [
                'ok'      => false,
                'message' => 'Koneksi SMTP gagal: ' . $e->getMessage(),
                'debug'   => $e->getMessage(),
            ];
        }
    }

    private function sendToDevOutbox(array $cfg, string $toEmail, string $toName, string $subject, string $textBody): array
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            return ['ok' => false, 'message' => 'Driver log nonaktif di produksi.', 'debug' => 'prod_block'];
        }

        $file = rtrim(WRITEPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'otp_dev_outbox.json';
        $entry = [
            'time'    => date('Y-m-d H:i:s'),
            'from'    => $cfg['from_email'] ?: 'dev@localhost',
            'to'      => $toEmail,
            'name'    => $toName,
            'subject' => $subject,
            'text'    => $textBody,
        ];
        @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

        return ['ok' => true, 'message' => 'Email dicatat ke dev outbox.', 'debug' => 'dev_log'];
    }

    private function readDbConfig(): array
    {
        try {
            $settings = new SiteSettingService();
            $settings->ensureTable();
            $db  = \Config\Database::connect();
            $row = $db->table('site_settings')->where('setting_key', self::SETTING_KEY)->get()->getRowArray();
            if ($row && !empty($row['setting_value'])) {
                $decoded = json_decode((string) $row['setting_value'], true);
                return is_array($decoded) ? $decoded : [];
            }
        } catch (\Throwable $e) {
            log_message('error', '[OtpMailerService] DB config read skipped: ' . $e->getMessage());
        }
        return [];
    }

    private function readEnv(string $key): string
    {
        $fallback = function_exists('env') ? env($key, '') : '';
        return trim((string) (getenv($key) ?: ($_ENV[$key] ?? $fallback)));
    }

    private function getCipherKey(): string
    {
        $encKey = $this->readEnv('encryption.key');
        if ($encKey !== '') {
            return hash('sha256', $encKey, true);
        }
        $dbHost = $this->readEnv('database.default.hostname');
        $dbUser = $this->readEnv('database.default.username');
        return hash('sha256', $dbHost . '|' . $dbUser . '|' . ROOTPATH . 'pwned_mail_cipher_v1', true);
    }

    private function encryptSecret(string $plain): string
    {
        if ($plain === '' || !function_exists('openssl_encrypt')) {
            return $plain === '' ? '' : 'b64:' . base64_encode($plain);
        }
        $iv  = random_bytes(16);
        $raw = openssl_encrypt($plain, 'AES-256-CBC', $this->getCipherKey(), OPENSSL_RAW_DATA, $iv);
        if ($raw === false) {
            return 'b64:' . base64_encode($plain);
        }
        return 'aes:' . base64_encode($iv . $raw);
    }

    private function decryptSecret(string $stored): string
    {
        if ($stored === '') {
            return '';
        }
        if (str_starts_with($stored, 'b64:')) {
            $dec = base64_decode(substr($stored, 4), true);
            return $dec !== false ? $dec : '';
        }
        if (str_starts_with($stored, 'aes:') && function_exists('openssl_decrypt')) {
            $bin = base64_decode(substr($stored, 4), true);
            if ($bin !== false && strlen($bin) > 16) {
                $iv  = substr($bin, 0, 16);
                $raw = substr($bin, 16);
                $dec = openssl_decrypt($raw, 'AES-256-CBC', $this->getCipherKey(), OPENSSL_RAW_DATA, $iv);
                if ($dec !== false) {
                    return $dec;
                }
            }
            return '';
        }
        return $stored;
    }
}
