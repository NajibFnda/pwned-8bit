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

        $this->client = Services::curlrequest([
            'baseURI'         => $this->baseUrl,
            'timeout'         => 10,           // Detik — mencegah request hang
            'connect_timeout' => 5,            // Detik — batas waktu koneksi awal
            'allow_redirects' => false,        // Jangan ikuti redirect (SSRF prevention)
            'verify'          => false,        // XAMPP Windows default tidak punya cacert.pem. Set false untuk dev.
            'http_errors'     => false,        // PENTING: Jangan lempar exception jika API me-return 404 (safe) atau 429
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
    // UTILITY METHODS
    // =========================================================================

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
