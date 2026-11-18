<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Multi-layer security guard providing atomic file-locked rate limiting,
 * automatic progressive IP bans, persistent MySQL quota tracking,
 * pure-PHP distorted PNG CAPTCHA challenges, honeypot/timing checks,
 * and optional Cloudflare Turnstile verification.
 */
class SecurityGuard
{
    private const CAPTCHA_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    private const CAPTCHA_TTL      = 300; // 5 minutes
    private const BAN_WINDOW       = 300; // 5 minutes violation window
    private const BAN_STRIKE_LIMIT = 8;
    private const BAN_DURATION     = 900; // 15 minutes ban

    /**
     * Common throwaway/disposable domains blocked from creating accounts.
     */
    private const DISPOSABLE_DOMAINS = [
        'mailinator.com', 'yopmail.com', 'yopmail.fr', 'yopmail.net', 'tempmail.com',
        'temp-mail.org', 'temp-mail.io', 'temp-mail.ru', 'guerrillamail.com', 'guerrillamail.net',
        'guerrillamail.org', 'guerrillamail.biz', 'sharklasers.com', 'grr.la', 'pokemail.net',
        '10minutemail.com', '10minutemail.net', '10minutemail.org', 'minutemail.com',
        'throwawaymail.com', 'throwaway.email', 'trashmail.com', 'trashmail.net', 'trashmail.org',
        'dispostable.com', 'getnada.com', 'nada.email', 'mohmal.com', 'maildrop.cc',
        'fakeinbox.com', 'mintemail.com', 'burnermail.io', 'emailondeck.com', 'crazymailing.com',
        'tempinbox.com', 'mailnesia.com', 'mailcatch.com', 'spamgourmet.com', 'mytemp.email',
        'tempmailo.com', 'tempmailaddress.com', 'fakemailgenerator.com', 'harakirimail.com',
        'moakt.com', 'inboxkitten.com', 'dropmail.me', 'incognitomail.com', 'anonbox.net',
        'spambog.com', 'discard.email', 'mailtemp.net', 'tmail.ws', 'tempr.email',
        'mail7.io', 'tempail.com', 'tmpmail.org', 'tmpmail.net', 'luxusmail.org',
        'emailfake.com', 'generator.email', 'mailpoof.com', 'guerrillamailblock.com',
        'example.com', 'example.net', 'example.org', 'test.com', 'invalid', 'localhost',
    ];

    /**
     * Major verified mail providers allowed without extra live DNS latency.
     */
    private const TRUSTED_EMAIL_PROVIDERS = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.id', 'ymail.com',
        'outlook.com', 'hotmail.com', 'live.com', 'msn.com', 'icloud.com',
        'me.com', 'mac.com', 'proton.me', 'protonmail.com', 'zoho.com',
        'gmx.com', 'aol.com', 'yandex.com', 'mail.ru',
    ];

    private const WEAK_PASSWORDS = [
        '12345678', '123456789', '1234567890', 'password', 'password1', 'password123',
        'qwerty123', 'admin123', 'admin1234', 'abc12345', 'welcome1', 'iloveyou1',
    ];

    /**
     * 5x7 crisp bitmap glyphs for unambiguous uppercase alphanumeric characters.
     */
    private const BITMAP_FONT = [
        '2' => ['01110', '10001', '00001', '00110', '01000', '10000', '11111'],
        '3' => ['11110', '00001', '00001', '01110', '00001', '00001', '11110'],
        '4' => ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
        '5' => ['11111', '10000', '11110', '00001', '00001', '10001', '01110'],
        '6' => ['01110', '10000', '10000', '11110', '10001', '10001', '01110'],
        '7' => ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
        '8' => ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
        '9' => ['01110', '10001', '10001', '01111', '00001', '00001', '01110'],
        'A' => ['01110', '10001', '10001', '11111', '10001', '10001', '10001'],
        'B' => ['11110', '10001', '10001', '11110', '10001', '10001', '11110'],
        'C' => ['01111', '10000', '10000', '10000', '10000', '10000', '01111'],
        'D' => ['11110', '10001', '10001', '10001', '10001', '10001', '11110'],
        'E' => ['11111', '10000', '10000', '11110', '10000', '10000', '11111'],
        'F' => ['11111', '10000', '10000', '11110', '10000', '10000', '10000'],
        'G' => ['01111', '10000', '10000', '10111', '10001', '10001', '01110'],
        'H' => ['10001', '10001', '10001', '11111', '10001', '10001', '10001'],
        'J' => ['00111', '00010', '00010', '00010', '10010', '10010', '01100'],
        'K' => ['10001', '10010', '10100', '11000', '10100', '10010', '10001'],
        'L' => ['10000', '10000', '10000', '10000', '10000', '10000', '11111'],
        'M' => ['10001', '11011', '10101', '10101', '10001', '10001', '10001'],
        'N' => ['10001', '11001', '10101', '10011', '10001', '10001', '10001'],
        'P' => ['11110', '10001', '10001', '11110', '10000', '10000', '10000'],
        'Q' => ['01110', '10001', '10001', '10001', '10101', '10010', '01101'],
        'R' => ['11110', '10001', '10001', '11110', '10100', '10010', '10001'],
        'S' => ['01111', '10000', '10000', '01110', '00001', '00001', '11110'],
        'T' => ['11111', '00100', '00100', '00100', '00100', '00100', '00100'],
        'U' => ['10001', '10001', '10001', '10001', '10001', '10001', '01110'],
        'V' => ['10001', '10001', '10001', '10001', '01010', '01010', '00100'],
        'W' => ['10001', '10001', '10001', '10101', '10101', '11011', '10001'],
        'X' => ['10001', '10001', '01010', '00100', '01010', '10001', '10001'],
        'Y' => ['10001', '10001', '01010', '00100', '00100', '00100', '00100'],
        'Z' => ['11111', '00001', '00010', '00100', '01000', '10000', '11111'],
    ];

    /**
     * Resolve normalized client IP address, aggregating IPv6 addresses to /64
     * so rotating lower-64-bit interface IDs cannot bypass rate limits.
     */
    public function getIpKey(?IncomingRequest $request = null): string
    {
        $request ??= service('request');
        $rawIp     = trim((string) $request->getIPAddress());
        if ($rawIp === '') {
            $rawIp = '0.0.0.0';
        }

        $packed = @inet_pton($rawIp);
        if ($packed !== false && strlen($packed) === 16) {
            // Normalize IPv6 to /64 prefix
            $prefix = substr($packed, 0, 8) . str_repeat("\x00", 8);
            $rawIp  = inet_ntop($prefix) ?: $rawIp;
        }

        return md5($rawIp);
    }

    /**
     * Atomic file-locked sliding-window rate limiter safe against concurrent bursts.
     *
     * @return array{allowed: bool, remaining: int, retry_after: int, count: int}
     */
    public function hitBucket(
        string $scope,
        string $identifier,
        int $maxHits,
        int $windowSec,
        bool $recordViolation = true,
        ?string $banIpKey = null
    ): array {
        $dir = $this->ensureRateLimitDir();
        $now = time();

        if ($dir === null) {
            return ['allowed' => true, 'remaining' => $maxHits, 'retry_after' => 0, 'count' => 0];
        }

        $safeScope = preg_replace('/[^a-zA-Z0-9_-]/', '_', $scope);
        $file      = $dir . DIRECTORY_SEPARATOR . $safeScope . '_' . md5($identifier) . '.json';

        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return ['allowed' => true, 'remaining' => $maxHits, 'retry_after' => 0, 'count' => 0];
        }

        $allowed    = true;
        $retryAfter = 0;
        $remaining  = $maxHits;
        $count      = 0;

        if (@flock($fp, LOCK_EX)) {
            $raw  = stream_get_contents($fp);
            $data = $raw ? json_decode($raw, true) : null;
            $hits = is_array($data['hits'] ?? null) ? $data['hits'] : [];

            $cutoff = $now - $windowSec;
            $hits   = array_values(array_filter($hits, static fn($ts) => is_int($ts) && $ts >= $cutoff));

            if (count($hits) >= $maxHits) {
                $allowed    = false;
                $oldest     = (int) $hits[0];
                $retryAfter = max(1, ($oldest + $windowSec) - $now);
                $remaining  = 0;
                $count      = count($hits);
            } else {
                $hits[]    = $now;
                $remaining = max(0, $maxHits - count($hits));
                $count     = count($hits);

                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode(['hits' => $hits, 'updated' => $now]));
                fflush($fp);
            }

            flock($fp, LOCK_UN);
        }
        fclose($fp);

        if (!$allowed && $recordViolation && $banIpKey !== null) {
            $this->recordStrike($banIpKey);
        }

        // Probabilistically prune stale bucket files to keep disk usage tiny
        if (mt_rand(1, 200) === 1) {
            $this->pruneStaleBucketFiles($dir, $now - 86400);
        }

        return [
            'allowed'     => $allowed,
            'remaining'   => $remaining,
            'retry_after' => $retryAfter,
            'count'       => $count,
        ];
    }

    /**
     * Peek current hit count inside a sliding window without incrementing or locking.
     */
    public function peekBucketCount(string $scope, string $identifier, int $windowSec): int
    {
        $dir = $this->ensureRateLimitDir();
        if ($dir === null) {
            return 0;
        }

        $safeScope = preg_replace('/[^a-zA-Z0-9_-]/', '_', $scope);
        $file      = $dir . DIRECTORY_SEPARATOR . $safeScope . '_' . md5($identifier) . '.json';
        if (!is_file($file)) {
            return 0;
        }

        $raw  = @file_get_contents($file);
        $data = $raw ? json_decode($raw, true) : null;
        $hits = is_array($data['hits'] ?? null) ? $data['hits'] : [];

        $cutoff = time() - $windowSec;
        return count(array_filter($hits, static fn($ts) => is_int($ts) && $ts >= $cutoff));
    }

    /**
     * Check whether an IP key is currently auto-banned after repeated violations.
     */
    public function getBanRemaining(string $ipKey): int
    {
        $dir = $this->ensureRateLimitDir();
        if ($dir === null) {
            return 0;
        }

        $file = $dir . DIRECTORY_SEPARATOR . 'ban_' . $ipKey . '.json';
        if (!is_file($file)) {
            return 0;
        }

        $raw = @file_get_contents($file);
        if (!$raw) {
            return 0;
        }

        $data        = json_decode($raw, true);
        $bannedUntil = (int) ($data['banned_until'] ?? 0);
        $now         = time();

        return $bannedUntil > $now ? ($bannedUntil - $now) : 0;
    }

    /**
     * Record a rate-limit or bot strike for an IP; bans temporarily once threshold is reached.
     */
    public function recordStrike(string $ipKey): int
    {
        $dir = $this->ensureRateLimitDir();
        if ($dir === null) {
            return 0;
        }

        $now  = time();
        $file = $dir . DIRECTORY_SEPARATOR . 'ban_' . $ipKey . '.json';
        $fp   = @fopen($file, 'c+');
        if (!$fp) {
            return 0;
        }

        $bannedFor = 0;
        if (@flock($fp, LOCK_EX)) {
            $raw         = stream_get_contents($fp);
            $data        = $raw ? json_decode($raw, true) : [];
            $strikes     = is_array($data['strikes'] ?? null) ? $data['strikes'] : [];
            $bannedUntil = (int) ($data['banned_until'] ?? 0);

            $cutoff  = $now - self::BAN_WINDOW;
            $strikes = array_values(array_filter($strikes, static fn($ts) => is_int($ts) && $ts >= $cutoff));
            $strikes[] = $now;

            if ($bannedUntil <= $now && count($strikes) >= self::BAN_STRIKE_LIMIT) {
                $bannedUntil  = $now + self::BAN_DURATION;
                $strikes      = [];
                $newlyBanned  = true;
            } else {
                $newlyBanned  = false;
            }

            $bannedFor = $bannedUntil > $now ? ($bannedUntil - $now) : 0;

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode([
                'strikes'      => $strikes,
                'banned_until' => $bannedUntil,
                'updated'      => $now,
            ]));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        if (!empty($newlyBanned)) {
            $this->logSecurityEvent(
                'DDOS_AUTO_BAN',
                'critical',
                null,
                'IP diblokir otomatis 15 menit setelah melewati batas pelanggaran rate-limit.'
            );
        }

        return $bannedFor;
    }

    /**
     * Check persistent MySQL quota for critical actions (register, contact_request).
     *
     * @return array{allowed: bool, count: int, retry_after: int}
     */
    public function checkDbQuota(string $action, string $ipKey, int $maxHits, int $windowSec, ?int $actorId = null): array
    {
        try {
            $this->ensureRateLimitTable();
            $db     = \Config\Database::connect();
            $since  = date('Y-m-d H:i:s', time() - $windowSec);

            $builder = $db->table('rate_limits')
                ->where('action', $action)
                ->where('created_at >=', $since);

            if ($actorId !== null && $actorId > 0) {
                $builder->groupStart()
                    ->where('ip_hash', $ipKey)
                    ->orWhere('actor_id', $actorId)
                    ->groupEnd();
            } else {
                $builder->where('ip_hash', $ipKey);
            }

            $rows  = $builder->orderBy('created_at', 'ASC')->get()->getResultArray();
            $count = count($rows);

            if ($count >= $maxHits) {
                $oldestTs   = strtotime((string) ($rows[0]['created_at'] ?? 'now')) ?: time();
                $retryAfter = max(1, ($oldestTs + $windowSec) - time());

                return ['allowed' => false, 'count' => $count, 'retry_after' => $retryAfter];
            }

            return ['allowed' => true, 'count' => $count, 'retry_after' => 0];
        } catch (\Throwable $e) {
            log_message('error', '[SecurityGuard] DB quota check skipped: ' . $e->getMessage());
            return ['allowed' => true, 'count' => 0, 'retry_after' => 0];
        }
    }

    /**
     * Persist an action occurrence into the database rate_limits table.
     */
    public function recordDbHit(string $action, string $ipKey, ?int $actorId = null): void
    {
        try {
            $this->ensureRateLimitTable();
            $db = \Config\Database::connect();
            $db->table('rate_limits')->insert([
                'action'     => substr($action, 0, 40),
                'ip_hash'    => substr($ipKey, 0, 32),
                'actor_id'   => $actorId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (mt_rand(1, 25) === 1) {
                $db->table('rate_limits')
                    ->where('created_at <', date('Y-m-d H:i:s', time() - 172800))
                    ->delete();
            }
        } catch (\Throwable $e) {
            log_message('error', '[SecurityGuard] DB quota write skipped: ' . $e->getMessage());
        }
    }

    /**
     * Issue a server-side form render timestamp in session for anti-bot speed checks.
     */
    public function issueFormTimer(string $formKey): void
    {
        $session           = \Config\Services::session();
        $timers            = (array) ($session->get('sec_form_timers') ?? []);
        $timers[$formKey]  = microtime(true);
        $session->set('sec_form_timers', $timers);
    }

    /**
     * Validate that a form submission was neither instantaneous (< $minSeconds) nor expired.
     */
    public function verifyFormTimer(string $formKey, float $minSeconds = 1.5, float $maxSeconds = 7200.0): bool
    {
        $session = \Config\Services::session();
        $timers  = (array) ($session->get('sec_form_timers') ?? []);
        $issued  = isset($timers[$formKey]) ? (float) $timers[$formKey] : 0.0;

        if ($issued <= 0.0) {
            return false;
        }

        $elapsed = microtime(true) - $issued;
        return $elapsed >= $minSeconds && $elapsed <= $maxSeconds;
    }

    /**
     * Check whether Cloudflare Turnstile keys are configured in environment.
     */
    public function isTurnstileEnabled(): bool
    {
        return $this->getTurnstileSiteKey() !== '' && $this->getTurnstileSecretKey() !== '';
    }

    public function getTurnstileSiteKey(): string
    {
        $envFallback = function_exists('env') ? env('TURNSTILE_SITE_KEY', '') : '';
        return trim((string) (getenv('TURNSTILE_SITE_KEY') ?: ($_ENV['TURNSTILE_SITE_KEY'] ?? $envFallback)));
    }

    private function getTurnstileSecretKey(): string
    {
        $envFallback = function_exists('env') ? env('TURNSTILE_SECRET_KEY', '') : '';
        return trim((string) (getenv('TURNSTILE_SECRET_KEY') ?: ($_ENV['TURNSTILE_SECRET_KEY'] ?? $envFallback)));
    }

    /**
     * Verify human challenge: Cloudflare Turnstile if configured, otherwise single-use session PNG CAPTCHA.
     */
    public function verifyHumanChallenge(string $scope, IncomingRequest $request): bool
    {
        if ($this->isTurnstileEnabled()) {
            $token = trim((string) $request->getPost('cf-turnstile-response'));
            if ($token === '') {
                return false;
            }
            return $this->verifyTurnstileToken($token, (string) $request->getIPAddress());
        }

        $input = trim((string) $request->getPost('captcha_code'));
        return $this->verifyCaptcha($scope, $input);
    }

    /**
     * Generate a distorted pure-PHP PNG CAPTCHA (using zlib, no GD extension required)
     * and store its single-use HMAC hash in the server session.
     */
    public function generateCaptchaPng(string $scope = 'register'): string
    {
        $code     = '';
        $alphabet = self::CAPTCHA_ALPHABET;
        $maxIdx   = strlen($alphabet) - 1;
        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, $maxIdx)];
        }

        $session            = \Config\Services::session();
        $challenges         = (array) ($session->get('sec_captcha_challenges') ?? []);
        $challenges[$scope] = [
            'hash'    => hash('sha256', strtoupper($code) . '|' . $scope),
            'expires' => time() + self::CAPTCHA_TTL,
        ];
        $session->set('sec_captcha_challenges', $challenges);

        return $this->renderDistortedPng($code);
    }

    /**
     * Consume and validate a session CAPTCHA challenge strictly once.
     */
    public function verifyCaptcha(string $scope, string $userInput): bool
    {
        $session    = \Config\Services::session();
        $challenges = (array) ($session->get('sec_captcha_challenges') ?? []);
        $challenge  = $challenges[$scope] ?? null;

        // Immediately invalidate so one challenge cannot be brute-forced across requests
        unset($challenges[$scope]);
        $session->set('sec_captcha_challenges', $challenges);

        if (!is_array($challenge)) {
            return false;
        }

        $expires = (int) ($challenge['expires'] ?? 0);
        if ($expires < time()) {
            return false;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $userInput));
        if (strlen($normalized) !== 5) {
            return false;
        }

        $expected = (string) ($challenge['hash'] ?? '');
        $actual   = hash('sha256', $normalized . '|' . $scope);

        return $expected !== '' && hash_equals($expected, $actual);
    }

    /**
     * Render a crisp retro 180x56 RGB PNG directly with pure PHP + zlib.
     */
    private function renderDistortedPng(string $code): string
    {
        $w = 180;
        $h = 56;

        // Allocate RGB byte buffer filled with off-white retro paper background (244, 245, 248)
        $canvas = str_repeat("\xF4\xF5\xF8", $w * $h);

        $setPixel = static function (string &$buf, int $x, int $y, int $r, int $g, int $b) use ($w, $h): void {
            if ($x < 0 || $x >= $w || $y < 0 || $y >= $h) {
                return;
            }
            $pos         = ($y * $w + $x) * 3;
            $buf[$pos]     = chr($r);
            $buf[$pos + 1] = chr($g);
            $buf[$pos + 2] = chr($b);
        };

        $drawLine = static function (string &$buf, int $x1, int $y1, int $x2, int $y2, int $r, int $g, int $b, int $thick = 1) use ($setPixel): void {
            $steps = max(1, abs($x2 - $x1), abs($y2 - $y1));
            for ($i = 0; $i <= $steps; $i++) {
                $px = (int) round($x1 + ($x2 - $x1) * ($i / $steps));
                $py = (int) round($y1 + ($y2 - $y1) * ($i / $steps));
                for ($dy = 0; $dy < $thick; $dy++) {
                    $setPixel($buf, $px, $py + $dy, $r, $g, $b);
                }
            }
        };

        // Subtle pixel grid
        for ($gx = 0; $gx < $w; $gx += 12) {
            $drawLine($canvas, $gx, 0, $gx, $h - 1, 224, 228, 236);
        }
        for ($gy = 0; $gy < $h; $gy += 12) {
            $drawLine($canvas, 0, $gy, $w - 1, $gy, 224, 228, 236);
        }

        // Background speckle noise
        for ($n = 0; $n < 220; $n++) {
            $setPixel(
                $canvas,
                random_int(1, $w - 2),
                random_int(1, $h - 2),
                random_int(140, 200),
                random_int(140, 200),
                random_int(150, 210)
            );
        }

        // Render distorted bitmap glyphs
        $palette = [
            [17, 24, 39],   // near black
            [29, 78, 216],  // retro blue
            [185, 28, 28],  // alert red
        ];

        $len    = strlen($code);
        $startX = 16;
        $stepX  = 31;

        for ($idx = 0; $idx < $len; $idx++) {
            $ch    = $code[$idx];
            $glyph = self::BITMAP_FONT[$ch] ?? self::BITMAP_FONT['X'];
            $color = $palette[random_int(0, count($palette) - 1)];
            $scale = 4;
            $baseX = $startX + ($idx * $stepX) + random_int(-2, 2);
            $baseY = 14 + random_int(-5, 5);
            $shear = random_int(-1, 1);

            for ($row = 0; $row < 7; $row++) {
                $bits = $glyph[$row];
                for ($col = 0; $col < 5; $col++) {
                    if ($bits[$col] !== '1') {
                        continue;
                    }
                    $px0 = $baseX + ($col * $scale) + (int) round(($row - 3) * $shear * 0.6);
                    $py0 = $baseY + ($row * $scale);
                    for ($sy = 0; $sy < $scale; $sy++) {
                        for ($sx = 0; $sx < $scale; $sx++) {
                            $setPixel($canvas, $px0 + $sx, $py0 + $sy, $color[0], $color[1], $color[2]);
                        }
                    }
                }
            }
        }

        // Crossing noise lines to frustrate simple OCR segmentation while keeping glyphs clear
        for ($l = 0; $l < 3; $l++) {
            $c = [120, 130, 145];
            $drawLine(
                $canvas,
                random_int(0, 30),
                random_int(4, $h - 5),
                random_int($w - 30, $w - 1),
                random_int(4, $h - 5),
                $c[0],
                $c[1],
                $c[2],
                1
            );
        }

        // Border frame
        $drawLine($canvas, 0, 0, $w - 1, 0, 0, 0, 0, 2);
        $drawLine($canvas, 0, $h - 2, $w - 1, $h - 2, 0, 0, 0, 2);

        // Assemble PNG raw scanlines with filter byte 0x00 per row
        $rowBytes  = $w * 3;
        $scanlines = '';
        for ($y = 0; $y < $h; $y++) {
            $scanlines .= "\x00" . substr($canvas, $y * $rowBytes, $rowBytes);
        }

        $ihdr = pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0);
        $idat = gzcompress($scanlines, 6);

        return "\x89PNG\r\n\x1a\n"
            . $this->pngChunk('IHDR', $ihdr)
            . $this->pngChunk('IDAT', $idat)
            . $this->pngChunk('IEND', '');
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    }

    private function verifyTurnstileToken(string $token, string $remoteIp): bool
    {
        $secret = $this->getTurnstileSecretKey();
        if ($secret === '' || !function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $remoteIp,
            ]),
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);

        if (!$raw) {
            return false;
        }

        $decoded = json_decode($raw, true);
        return !empty($decoded['success']);
    }

    /**
     * Build an immediate, lightweight HTTP 429 Too Many Requests response without touching DB.
     */
    public function tooManyRequestsResponse(
        IncomingRequest $request,
        string $message,
        int $retryAfter = 60,
        bool $forceJson = false
    ): ResponseInterface {
        $response = service('response');
        $response->setStatusCode(429)
            ->setHeader('Retry-After', (string) max(1, $retryAfter))
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        $isJson = $forceJson
            || $request->isAJAX()
            || str_contains(strtolower((string) $request->getHeaderLine('Accept')), 'application/json');

        if ($isJson) {
            $payload = [
                'status'      => 'error',
                'code'        => 429,
                'message'     => $message,
                'retry_after' => max(1, $retryAfter),
            ];
            // Only compute csrf_token safely if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $payload['csrf_token'] = csrf_hash();
            }
            return $response->setJSON($payload);
        }

        $safeMsg = esc($message);
        $homeUrl = esc(base_url('/'));
        $waitSec = max(1, $retryAfter);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 Too Many Requests - PWNED</title>
    <style>
        body { font-family: monospace; background: #111; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 16px; }
        .box { max-width: 480px; width: 100%; background: #fff; color: #000; border: 4px solid #000; box-shadow: 8px 8px 0 #dc2626; padding: 24px; }
        .badge { display: inline-block; background: #dc2626; color: #fff; padding: 6px 10px; font-weight: bold; font-size: 12px; border: 2px solid #000; margin-bottom: 14px; }
        h1 { font-size: 18px; margin: 0 0 12px; text-transform: uppercase; }
        p { font-size: 13px; line-height: 1.5; margin: 0 0 18px; color: #1f2937; font-weight: bold; }
        a { display: inline-block; background: #000; color: #fff; text-decoration: none; padding: 10px 16px; font-weight: bold; font-size: 12px; border: 2px solid #000; }
        a:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="box">
        <div class="badge">[HTTP 429 // SECURITY_SHIELD]</div>
        <h1>Terlalu Banyak Permintaan</h1>
        <p>{$safeMsg}<br><br>Silakan tunggu sekitar {$waitSec} detik sebelum mencoba kembali.</p>
        <a href="{$homeUrl}">&lt; KEMBALI KE BERANDA</a>
    </div>
</body>
</html>
HTML;

        return $response->setContentType('text/html', 'UTF-8')->setBody($html);
    }

    private function ensureRateLimitDir(): ?string
    {
        $dir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'ratelimit';
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return null;
            }
            @file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess', "Order allow,deny\nDeny from all\n");
            @file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.html', '');
        }
        return $dir;
    }

    private function ensureRateLimitTable(): void
    {
        $dir    = $this->ensureRateLimitDir();
        $marker = $dir ? ($dir . DIRECTORY_SEPARATOR . '.db_ready') : null;
        if ($marker && is_file($marker)) {
            return;
        }

        $db = \Config\Database::connect();
        $db->query("
            CREATE TABLE IF NOT EXISTS `rate_limits` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `action` VARCHAR(40) NOT NULL,
                `ip_hash` CHAR(32) NOT NULL,
                `actor_id` INT UNSIGNED NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_rate_lookup` (`action`, `ip_hash`, `created_at`),
                KEY `idx_rate_actor` (`action`, `actor_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        if ($marker) {
            @file_put_contents($marker, '1');
        }
    }

    /**
     * Validate email domain authenticity: block throwaway domains & check live DNS MX/A records.
     *
     * @return array{valid: bool, reason: string}
     */
    public function validateEmailDomain(string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'reason' => 'Format alamat email tidak valid.'];
        }

        $atPos  = strrpos($email, '@');
        $domain = $atPos !== false ? substr($email, $atPos + 1) : '';
        if ($domain === '' || !str_contains($domain, '.') || str_starts_with($domain, '.') || str_ends_with($domain, '.')) {
            return ['valid' => false, 'reason' => 'Domain alamat email tidak valid.'];
        }

        if (str_ends_with($domain, '.local') || str_ends_with($domain, '.test') || str_ends_with($domain, '.invalid')) {
            return ['valid' => false, 'reason' => 'Domain email lokal/uji coba tidak diperbolehkan.'];
        }

        foreach (self::DISPOSABLE_DOMAINS as $blocked) {
            if ($domain === $blocked || str_ends_with($domain, '.' . $blocked)) {
                return ['valid' => false, 'reason' => 'Email sementara/disposable (' . $domain . ') tidak diperbolehkan. Gunakan email permanen.'];
            }
        }

        if (in_array($domain, self::TRUSTED_EMAIL_PROVIDERS, true)) {
            return ['valid' => true, 'reason' => ''];
        }

        // Verify live MX/A records only if server DNS itself is reachable (prevents false rejects offline)
        if (function_exists('checkdnsrr') && @checkdnsrr('gmail.com', 'MX')) {
            $hasMx = @checkdnsrr($domain, 'MX');
            $hasA  = $hasMx ? true : @checkdnsrr($domain, 'A');
            if (!$hasMx && !$hasA) {
                return ['valid' => false, 'reason' => 'Domain email (' . $domain . ') tidak ditemukan atau tidak menerima email (MX record tidak valid).'];
            }
        }

        return ['valid' => true, 'reason' => ''];
    }

    /**
     * Enforce strong password requirements (>= 8 chars, mixed letters + numbers, non-trivial).
     *
     * @return array{valid: bool, reason: string}
     */
    public function validatePasswordStrength(string $password): array
    {
        $len = strlen($password);
        if ($len < 8 || $len > 128) {
            return ['valid' => false, 'reason' => 'Kata sandi wajib terdiri dari 8 hingga 128 karakter.'];
        }

        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'reason' => 'Kata sandi wajib mengandung kombinasi huruf dan angka.'];
        }

        if (in_array(strtolower($password), self::WEAK_PASSWORDS, true)) {
            return ['valid' => false, 'reason' => 'Kata sandi terlalu umum. Gunakan kombinasi yang lebih aman.'];
        }

        return ['valid' => true, 'reason' => ''];
    }

    /**
     * Record a security/audit event in MySQL security_logs for Admin visibility.
     */
    public function logSecurityEvent(
        string $eventType,
        string $severity = 'info',
        ?string $actorEmail = null,
        string $detail = '',
        ?IncomingRequest $request = null
    ): void {
        try {
            $this->ensureSecurityLogsTable();
            $request ??= service('request');
            $rawIp = $request instanceof IncomingRequest ? trim((string) $request->getIPAddress()) : 'cli';
            if ($rawIp === '') {
                $rawIp = '0.0.0.0';
            }

            if (!in_array($severity, ['info', 'warning', 'critical'], true)) {
                $severity = 'info';
            }

            $db = \Config\Database::connect();
            $db->table('security_logs')->insert([
                'event_type'  => substr(strtoupper(trim($eventType)), 0, 50),
                'severity'    => $severity,
                'actor_email' => $actorEmail !== null && $actorEmail !== '' ? mb_substr(strtolower(trim($actorEmail)), 0, 150) : null,
                'ip_address'  => substr($rawIp, 0, 64),
                'detail'      => mb_substr(trim($detail), 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            if (mt_rand(1, 30) === 1) {
                $db->table('security_logs')
                    ->where('created_at <', date('Y-m-d H:i:s', time() - 2592000))
                    ->delete();
            }
        } catch (\Throwable $e) {
            log_message('error', '[SecurityGuard] Audit log write skipped: ' . $e->getMessage());
        }
    }

    private function ensureSecurityLogsTable(): void
    {
        $dir    = $this->ensureRateLimitDir();
        $marker = $dir ? ($dir . DIRECTORY_SEPARATOR . '.logs_ready') : null;
        if ($marker && is_file($marker)) {
            return;
        }

        $db = \Config\Database::connect();
        $db->query("
            CREATE TABLE IF NOT EXISTS `security_logs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `event_type` VARCHAR(50) NOT NULL,
                `severity` ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
                `actor_email` VARCHAR(150) NULL DEFAULT NULL,
                `ip_address` VARCHAR(64) NOT NULL,
                `detail` VARCHAR(255) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_sec_event_time` (`event_type`, `created_at`),
                KEY `idx_sec_severity_time` (`severity`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        if ($marker) {
            @file_put_contents($marker, '1');
        }
    }

    /**
     * Fetch paginated security audit logs and 24-hour severity counters for Admin Panel.
     *
     * @return array{logs: array<int, array<string, mixed>>, total: int, page: int, page_count: int, stats_24h: array<string, int>}
     */
    public function getSecurityAuditPage(string $severity = '', string $search = '', int $page = 1, int $perPage = 30): array
    {
        $this->ensureSecurityLogsTable();
        $db   = \Config\Database::connect();
        $page = max(1, $page);

        $since24h = date('Y-m-d H:i:s', time() - 86400);
        $statsRows = $db->table('security_logs')
            ->select('severity, COUNT(*) AS cnt')
            ->where('created_at >=', $since24h)
            ->groupBy('severity')
            ->get()
            ->getResultArray();

        $stats24h = ['total' => 0, 'info' => 0, 'warning' => 0, 'critical' => 0];
        foreach ($statsRows as $sr) {
            $sev = (string) ($sr['severity'] ?? 'info');
            $cnt = (int) ($sr['cnt'] ?? 0);
            if (isset($stats24h[$sev])) {
                $stats24h[$sev] = $cnt;
            }
            $stats24h['total'] += $cnt;
        }

        $applyFilters = static function ($builder) use ($severity, $search): void {
            if ($severity !== '' && in_array($severity, ['info', 'warning', 'critical'], true)) {
                $builder->where('severity', $severity);
            }
            if ($search !== '') {
                $builder->groupStart()
                    ->like('event_type', $search)
                    ->orLike('actor_email', $search)
                    ->orLike('ip_address', $search)
                    ->orLike('detail', $search)
                    ->groupEnd();
            }
        };

        $countBuilder = $db->table('security_logs');
        $applyFilters($countBuilder);
        $total = (int) $countBuilder->countAllResults();

        $pageCount = max(1, (int) ceil($total / $perPage));
        if ($page > $pageCount) {
            $page = $pageCount;
        }
        $offset = ($page - 1) * $perPage;

        $rowsBuilder = $db->table('security_logs');
        $applyFilters($rowsBuilder);
        $logs = $rowsBuilder
            ->orderBy('id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return [
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'page_count' => $pageCount,
            'stats_24h'  => $stats24h,
        ];
    }

    /**
     * Delete old security logs beyond retention days and return affected rows.
     */
    public function clearOldSecurityLogs(int $olderThanDays = 7): int
    {
        $this->ensureSecurityLogsTable();
        $db     = \Config\Database::connect();
        $cutoff = date('Y-m-d H:i:s', time() - (max(1, $olderThanDays) * 86400));
        $db->table('security_logs')->where('created_at <', $cutoff)->delete();
        return (int) $db->affectedRows();
    }

    private function pruneStaleBucketFiles(string $dir, int $olderThan): void
    {
        $files = @glob($dir . DIRECTORY_SEPARATOR . '*.json');
        if (!$files) {
            return;
        }
        foreach ($files as $file) {
            if (@filemtime($file) !== false && @filemtime($file) < $olderThan) {
                @unlink($file);
            }
        }
    }
}
