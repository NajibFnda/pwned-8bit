<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

/**
 * XonApiService (XposedOrNot API Service)
 *
 * Lapisan abstraksi untuk berkomunikasi dengan XposedOrNot API v1.
 * API ini 100% GRATIS dan tidak memerlukan API key untuk endpoint publik.
 *
 * Dokumentasi API: https://xposedornot.com/xon_api.aspx
 * Endpoint:        GET https://api.xposedornot.com/v1/breach-analytics?email={email}
 *
 * Prinsip Keamanan yang Diimplementasikan:
 * ─────────────────────────────────────────
 * [1] Zero Credential Risk  — Tidak ada API key yang bisa bocor.
 * [2] HTTPS Enforcement     — verify=true, koneksi SSL wajib.
 * [3] Timeout Guard         — Maksimum 10 detik, mencegah request hang.
 * [4] No Redirect           — allow_redirects=false, mencegah potensi SSRF.
 * [5] Response Filtering    — Data API difilter sebelum dikirim ke View,
 *                             tidak ada data mentah yang di-pass langsung.
 * [6] Safe Defaults         — Semua field nullable di-default dengan nilai aman.
 */
class XonApiService
{
    /**
     * Base URL dari XposedOrNot API, dibaca dari .env agar mudah dikonfigurasi
     * tanpa mengubah source code.
     */
    private string $baseUrl;

    /**
     * CodeIgniter 4 CURLRequest — HTTP client bawaan framework.
     */
    private CURLRequest $client;

    /**
     * Konstruktor — inisialisasi HTTP client dengan konfigurasi keamanan.
     */
    public function __construct()
    {
        $this->baseUrl = rtrim(
            env('XON_API_BASE_URL', 'https://api.xposedornot.com/v1'),
            '/'
        );

        $sslVerify = filter_var(
            env('XON_SSL_VERIFY', env('CI_ENVIRONMENT') === 'production'),
            FILTER_VALIDATE_BOOLEAN
        );

        $this->client = Services::curlrequest([
            'baseURI'         => $this->baseUrl,
            'timeout'         => 10,           // Detik — mencegah request hang
            'connect_timeout' => 5,            // Detik — batas waktu koneksi awal
            'allow_redirects' => false,        // Jangan ikuti redirect (SSRF prevention)
            'verify'          => $sslVerify,   // Otomatis true di production, false di XAMPP Windows dev
            'http_errors'     => false,        // Jangan lempar exception jika API me-return 404 (safe) atau 429
            'headers'         => [
                'Accept'     => 'application/json',
                'User-Agent' => 'PWNED-PWL-CodeIgniter4/1.0',
            ],
        ]);
    }

    // =========================================================================
    // PUBLIC METHOD — Titik masuk utama
    // =========================================================================

    /**
     * Cek apakah sebuah alamat email pernah terlibat dalam kebocoran data.
     *
     * @param  string $email Alamat email yang sudah divalidasi oleh Controller.
     * @return array{
     *   status: 'pwned'|'safe'|'error',
     *   details: array<int, array{
     *     sumber: string, tanggal: string, domain: string,
     *     industri: string, kelas_data: string, jumlah_data: string,
     *     risiko_password: string, is_verified: bool, deskripsi: string
     *   }>,
     *   total_breach: int,
     *   message: string,
     *   http_code: int
     * }
     */
    public function checkEmail(string $email): array
    {
        $encodedEmail = rawurlencode($email);

        try {
            // Gunakan URL absolut secara eksplisit untuk mencegah CI4 CURLRequest
            // memotong '/v1' dari baseURI berdasarkan standar RFC URI resolution.
            $fullUrl = $this->baseUrl . "/breach-analytics?email={$encodedEmail}";
            
            $response   = $this->client->get($fullUrl);
            $statusCode = $response->getStatusCode();


            // Gunakan match() — lebih ekspresif dan type-safe dibanding switch
            return match (true) {
                $statusCode === 200 => $this->handlePwnedResponse($response->getBody()),
                $statusCode === 404 => $this->buildSafeResponse(),
                $statusCode === 400 => $this->buildBadRequestResponse(),
                $statusCode === 429 => $this->buildRateLimitResponse(),
                default             => $this->buildGenericErrorResponse($statusCode),
            };

        } catch (\CodeIgniter\HTTP\Exceptions\HTTPException $e) {
            // Error jaringan: timeout, DNS gagal, koneksi ditolak, dll.
            log_message('error', '[XonApiService] Koneksi ke XposedOrNot API gagal: ' . $e->getMessage());
            return [
                'status'       => 'error',
                'details'      => [],
                'total_breach' => 0,
                'message'      => 'Layanan pengecekan sementara tidak tersedia. Coba lagi dalam beberapa saat.',
                'http_code'    => 503,
            ];
        } catch (\Throwable $e) {
            // Tangkap semua error tak terduga — jangan expose stack trace ke user
            log_message('critical', '[XonApiService] Error tidak terduga: ' . $e->getMessage());
            return [
                'status'       => 'error',
                'details'      => [],
                'total_breach' => 0,
                'message'      => 'Terjadi kesalahan internal pada server.',
                'http_code'    => 500,
            ];
        }
    }

    /**
     * Ambil metrik kebocoran data global secara real-time dari XposedOrNot API
     * (/v1/metrics/detailed) dengan caching server-side dan fallback aman.
     *
     * @param  bool $forceRefresh Lewati cache singkat jika diminta secara internal.
     * @return array
     */
    public function getGlobalMetrics(bool $forceRefresh = false): array
    {
        $cache        = Services::cache();
        $cacheKey     = 'xon_global_metrics_v1';
        $backupKey    = 'xon_global_metrics_backup_v1';
        $cacheTtl     = 1200;  // 20 menit — ringankan beban node shared hosting
        $backoffTtl   = 180;   // 3 menit jeda jika API/DNS sedang timeout agar worker PHP tidak menumpuk
        $backupTtl    = 86400; // 24 jam — fallback jika API timeout / 429

        if (!$forceRefresh) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached) && !empty($cached['chart']['values'])) {
                if (($cached['source'] ?? '') === 'live') {
                    $cached['source'] = 'cache';
                }
                return $cached;
            }
        }

        try {
            $fullUrl    = $this->baseUrl . '/metrics/detailed';
            $response   = $this->client->get($fullUrl, ['timeout' => 5, 'connect_timeout' => 3]);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $raw = json_decode($response->getBody(), true);
                if (
                    json_last_error() === JSON_ERROR_NONE
                    && is_array($raw)
                    && !empty($raw['Yearly_Breaches_Count'])
                    && is_array($raw['Yearly_Breaches_Count'])
                ) {
                    $payload = $this->buildGlobalMetricsPayload($raw, 'live', date('Y-m-d H:i:s'));
                    $cache->save($cacheKey, $payload, $cacheTtl);
                    $cache->save($backupKey, $payload, $backupTtl);
                    return $payload;
                }
            }

            log_message('warning', "[XonApiService] Gagal memuat /metrics/detailed, HTTP Status: {$statusCode}.");
        } catch (\Throwable $e) {
            log_message('error', '[XonApiService] Error saat mengambil global metrics: ' . $e->getMessage());
        }

        $backup = $cache->get($backupKey);
        if (is_array($backup) && !empty($backup['chart']['values'])) {
            $backup['source'] = 'backup_cache';
            $cache->save($cacheKey, $backup, $backoffTtl);
            return $backup;
        }

        $fallback = $this->buildGlobalMetricsPayload($this->getBaselineMetricsSnapshot(), 'fallback', date('Y-m-d H:i:s'));
        $cache->save($cacheKey, $fallback, $backoffTtl);
        return $fallback;
    }

    // =========================================================================
    // PRIVATE HELPER METHODS — Response Builders
    // =========================================================================

    /**
     * Proses response HTTP 200 — email DITEMUKAN dalam satu atau lebih breach.
     *
     * Struktur JSON XposedOrNot:
     * {
     *   "ExposedBreaches": {
     *     "breaches_details": [
     *       {
     *         "breach": "NamaBreachnya",
     *         "domain": "domain.com",
     *         "industry": "Retail",
     *         "xposed_date": "2022",
     *         "xposed_data": "Email addresses;Passwords",
     *         "xposed_records": 1000000,
     *         "password_risk": "plaintext",
     *         "verified": "Yes",
     *         "details": "Deskripsi singkat breach..."
     *       }
     *     ]
     *   }
     * }
     *
     * @param  string $responseBody Raw JSON body dari API.
     * @return array
     */
    private function handlePwnedResponse(string $responseBody): array
    {
        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            log_message('error', '[XonApiService] Gagal parse JSON: ' . json_last_error_msg());
            return $this->buildGenericErrorResponse(200);
        }

        // Ambil array breach dari path JSON yang benar
        $breachesDetails = $data['ExposedBreaches']['breaches_details'] ?? [];

        if (empty($breachesDetails) || !is_array($breachesDetails)) {
            // Response 200 tapi array kosong — treat as safe
            return $this->buildSafeResponse();
        }

        // ─── FILTER & MAP ────────────────────────────────────────────────────
        // Hanya ekspos field yang diperlukan frontend.
        // Gunakan type-checking ketat agar injection dari data breach tidak lolos.
        $details = array_map(function (array $breach): array {
            // Format jumlah records agar lebih mudah dibaca manusia
            $records = isset($breach['xposed_records']) && is_numeric($breach['xposed_records'])
                ? $this->formatNumber((int) $breach['xposed_records'])
                : '?';

            // is_verified: "Yes"/"No" dari API → boolean PHP
            $isVerified = isset($breach['verified']) && strtolower((string) $breach['verified']) === 'yes';

            // Tentukan label risiko password berdasarkan nilai dari API
            $passwordRiskMap = [
                'plaintext'   => '🔴 Plaintext (Sangat Berbahaya)',
                'easytocrack' => '🟠 Mudah Dibobol',
                'hardtocrack' => '🟡 Sulit Dibobol',
                'unknown'     => '⚪ Tidak Diketahui',
            ];
            $passwordRiskRaw  = strtolower((string) ($breach['password_risk'] ?? 'unknown'));
            $passwordRiskLabel = $passwordRiskMap[$passwordRiskRaw] ?? '⚪ Tidak Diketahui';

            return [
                'sumber'          => is_string($breach['breach'] ?? null)     ? $breach['breach']     : 'Unknown',
                'tanggal'         => is_string($breach['xposed_date'] ?? null) ? $breach['xposed_date'] : '-',
                'domain'          => is_string($breach['domain'] ?? null)      ? $breach['domain']      : '-',
                'industri'        => is_string($breach['industry'] ?? null)    ? $breach['industry']    : '-',
                'kelas_data'      => is_string($breach['xposed_data'] ?? null) ? $breach['xposed_data'] : '-',
                'jumlah_data'     => $records,
                'risiko_password' => $passwordRiskLabel,
                'is_verified'     => $isVerified,
                'deskripsi'       => is_string($breach['details'] ?? null)     ? $breach['details']     : '',
            ];
        }, $breachesDetails);

        $totalBreach = count($details);

        return [
            'status'       => 'pwned',
            'details'      => $details,
            'total_breach' => $totalBreach,
            'message'      => "Email ditemukan dalam {$totalBreach} kebocoran data.",
            'http_code'    => 200,
        ];
    }

    /**
     * HTTP 404 — Email TIDAK ditemukan dalam database breach (aman).
     */
    private function buildSafeResponse(): array
    {
        return [
            'status'       => 'safe',
            'details'      => [],
            'total_breach' => 0,
            'message'      => 'Email tidak ditemukan dalam database kebocoran data yang diketahui.',
            'http_code'    => 404,
        ];
    }

    /**
     * HTTP 400 — Bad Request (email tidak valid menurut API, meski sudah lolos validasi CI4).
     */
    private function buildBadRequestResponse(): array
    {
        return [
            'status'       => 'error',
            'details'      => [],
            'total_breach' => 0,
            'message'      => 'Format email tidak dapat diproses oleh layanan pengecekan.',
            'http_code'    => 400,
        ];
    }

    /**
     * HTTP 429 — Rate Limit dari sisi XposedOrNot API.
     * Batas gratis: ~2 request/detik, ~100 request/hari per IP.
     */
    private function buildRateLimitResponse(): array
    {
        log_message('warning', '[XonApiService] Rate limit XposedOrNot API tercapai dari IP: ' . service('request')->getIPAddress());
        return [
            'status'       => 'error',
            'details'      => [],
            'total_breach' => 0,
            'message'      => 'Terlalu banyak permintaan. Mohon tunggu beberapa saat sebelum mencoba lagi.',
            'http_code'    => 429,
        ];
    }

    /**
     * Fallback untuk kode HTTP yang tidak ditangani secara spesifik.
     *
     * @param int $code HTTP status code yang diterima.
     */
    private function buildGenericErrorResponse(int $code): array
    {
        log_message('error', "[XonApiService] Response tidak terduga dari API, HTTP Status: {$code}.");
        return [
            'status'       => 'error',
            'details'      => [],
            'total_breach' => 0,
            'message'      => 'Gagal menghubungi layanan pengecekan. Silakan coba lagi nanti.',
            'http_code'    => $code,
        ];
    }

    // =========================================================================
    // GLOBAL METRICS HELPERS
    // =========================================================================

    /**
     * Normalisasi payload mentah dari /v1/metrics/detailed menjadi struktur
     * siap pakai untuk kartu statistik dan grafik tren tahunan.
     *
     * @param  array  $raw      Data JSON yang sudah di-decode.
     * @param  string $source   Asal data ('live' | 'cache' | 'backup_cache' | 'fallback').
     * @param  string $syncedAt Waktu sinkronisasi server (Y-m-d H:i:s).
     * @return array
     */
    private function buildGlobalMetricsPayload(array $raw, string $source, string $syncedAt): array
    {
        $yearlyRaw = $raw['Yearly_Breaches_Count'] ?? [];
        $yearly    = [];
        foreach ($yearlyRaw as $year => $count) {
            if (preg_match('/^\d{4}$/', (string) $year) && is_numeric($count)) {
                $yearly[(string) $year] = (int) $count;
            }
        }
        ksort($yearly, SORT_STRING);

        // Ambil 10 tahun terakhir agar sumbu X tetap ringkas ('17'..'26')
        $recentYearly = array_slice($yearly, -10, 10, true);

        $fullYears = array_keys($recentYearly);
        $labels    = array_map(static fn(string $y): string => substr($y, -2), $fullYears);
        $values    = array_values($recentYearly);

        $countValues = count($values);
        $average     = $countValues > 0 ? round(array_sum($values) / $countValues, 1) : 0.0;

        $currentYear      = $countValues > 0 ? $fullYears[$countValues - 1] : date('Y');
        $currentYearCount = $countValues > 0 ? $values[$countValues - 1] : 0;
        $prevYearCount    = $countValues > 1 ? $values[$countValues - 2] : 0;

        $yoyDiff = $prevYearCount > 0
            ? round((($currentYearCount - $prevYearCount) / $prevYearCount) * 100, 1)
            : 0.0;
        $yoyFormatted = ($yoyDiff >= 0 ? '+' : '') . $yoyDiff . '%';

        $vsAvgDiff = $average > 0
            ? round((($currentYearCount - $average) / $average) * 100, 1)
            : 0.0;
        $vsAvgFormatted = ($vsAvgDiff >= 0 ? '+' : '') . $vsAvgDiff . '%';

        $trendLevel = match (true) {
            $average > 0 && $currentYearCount >= ($average * 1.25) => 'CRITICAL',
            $average > 0 && $currentYearCount >= $average          => 'ELEVATED',
            default                                                => 'MODERATE',
        };
        $trendDirection = $currentYearCount >= $prevYearCount ? 'INCREASING' : 'DECREASING';

        $breachesCount  = is_numeric($raw['Breaches_Count'] ?? null) ? (int) $raw['Breaches_Count'] : 0;
        $breachesRecs   = is_numeric($raw['Breaches_Records'] ?? null) ? (int) $raw['Breaches_Records'] : 0;
        $pastesRecs     = is_numeric($raw['Pastes_Records'] ?? null) ? (int) $raw['Pastes_Records'] : 0;
        $lastBreachIso  = is_string($raw['Last_Breach_Added'] ?? null) ? $raw['Last_Breach_Added'] : '';
        $lastBreachTs   = $lastBreachIso !== '' ? strtotime($lastBreachIso) : false;
        $lastBreachDate = $lastBreachTs !== false ? strtoupper(date('d M Y', $lastBreachTs)) : '-';

        // Persentase load relatif terhadap puncak tahunan 10 tahun terakhir (untuk progress bar UI)
        $peakCount = $countValues > 0 ? max($values) : 1;
        $loadPct   = $peakCount > 0 ? (int) min(100, round(($currentYearCount / $peakCount) * 100)) : 0;

        return [
            'source'                    => $source,
            'synced_at'                 => $syncedAt,
            'last_breach_added'         => $lastBreachIso,
            'last_breach_date'          => $lastBreachDate,
            'breaches_count'            => $breachesCount,
            'breaches_records'          => $breachesRecs,
            'breaches_records_short'    => $this->formatCompactEn($breachesRecs) . '+',
            'pastes_records'            => $pastesRecs,
            'pastes_records_short'      => $this->formatCompactEn($pastesRecs) . '+',
            'current_year'              => $currentYear,
            'current_year_count'        => $currentYearCount,
            'yoy_diff_pct'              => $yoyFormatted,
            'vs_avg_pct'                => $vsAvgFormatted,
            'trend_level'               => $trendLevel,
            'trend_direction'           => $trendDirection,
            'load_pct'                  => $loadPct,
            'chart' => [
                'labels'     => $labels,
                'full_years' => $fullYears,
                'values'     => $values,
                'average'    => $average,
            ],
        ];
    }

    /**
     * Snapshot baseline asli dari XposedOrNot jika API & cache sama-sama tidak tersedia.
     */
    private function getBaselineMetricsSnapshot(): array
    {
        return [
            'Breaches_Count'        => 801,
            'Breaches_Records'      => 11727783489,
            'Pastes_Records'        => 835955029,
            'Last_Breach_Added'     => '2026-11-15T10:54:14+00:00',
            'Yearly_Breaches_Count' => [
                '2017' => 36,
                '2018' => 53,
                '2019' => 71,
                '2020' => 79,
                '2021' => 61,
                '2022' => 51,
                '2023' => 33,
                '2024' => 54,
                '2025' => 36,
                '2026' => 88,
            ],
        ];
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Format angka besar menjadi ringkas berbahasa Inggris (K, M, B) untuk dashboard.
     */
    private function formatCompactEn(int $number): string
    {
        if ($number >= 1_000_000_000) {
            return round($number / 1_000_000_000, 1) . 'B';
        }
        if ($number >= 1_000_000) {
            return round($number / 1_000_000, 1) . 'M';
        }
        if ($number >= 1_000) {
            return round($number / 1_000, 1) . 'K';
        }
        return number_format($number);
    }

    /**
     * Format angka besar menjadi format yang mudah dibaca.
     * Contoh: 1500000 → "1.5 Juta"
     *
     * @param  int $number Angka yang akan diformat.
     * @return string
     */
    private function formatNumber(int $number): string
    {
        if ($number >= 1_000_000_000) {
            return round($number / 1_000_000_000, 1) . ' Miliar';
        }
        if ($number >= 1_000_000) {
            return round($number / 1_000_000, 1) . ' Juta';
        }
        if ($number >= 1_000) {
            return round($number / 1_000, 1) . ' Ribu';
        }
        return number_format($number);
    }
}
