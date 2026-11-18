<?php

namespace App\Filters;

use App\Libraries\SecurityGuard;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Global L7 HTTP Flood / DDoS & Abuse Shield executed before CSRF and controllers.
 * Drops automated floods with HTTP 429 before initializing MySQL or heavy views.
 */
class DdosGuardFilter implements FilterInterface
{
    private const BLOCKED_SCANNER_UAS = '/(sqlmap|nikto|nuclei|masscan|acunetix|dirbuster|gobuster|wpscan|zgrab|nessus|havij)/i';

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$request instanceof IncomingRequest) {
            return null;
        }

        $guard  = new SecurityGuard();
        $ipKey  = $guard->getIpKey($request);
        $path   = trim($request->getUri()->getPath(), '/');
        $method = strtoupper($request->getMethod());

        // Webhook endpoint uses its own cryptographic verification
        if ($path === 'midtrans/webhook') {
            return null;
        }

        // 1. Block known automated vulnerability scanners immediately
        $ua = trim((string) $request->getHeaderLine('User-Agent'));
        if ($ua !== '' && preg_match(self::BLOCKED_SCANNER_UAS, $ua)) {
            $guard->recordStrike($ipKey);
            return service('response')
                ->setStatusCode(403)
                ->setBody('Forbidden.');
        }

        // 2. Reject spoofed cross-origin POSTs when Origin header is present
        if ($method === 'POST' && $request->hasHeader('Origin')) {
            $originHost  = strtolower((string) parse_url($request->getHeaderLine('Origin'), PHP_URL_HOST));
            $currentHost = strtolower((string) $request->getUri()->getHost());
            if ($originHost !== '' && $currentHost !== '' && $originHost !== $currentHost) {
                $guard->recordStrike($ipKey);
                return $guard->tooManyRequestsResponse($request, 'Origin permintaan tidak valid.', 60);
            }
        }

        // 3. Enforce active temporary IP auto-ban first (zero heavy I/O)
        $banRemaining = $guard->getBanRemaining($ipKey);
        if ($banRemaining > 0) {
            return $guard->tooManyRequestsResponse(
                $request,
                'Akses IP dibatasi sementara oleh perlindungan anti-DDoS & anti-spam.',
                $banRemaining
            );
        }

        // 4. Global L7 burst + sustained traffic rate limits per IP
        $burst = $guard->hitBucket('global_10s', $ipKey, 30, 10, true, $ipKey);
        if (!$burst['allowed']) {
            return $guard->tooManyRequestsResponse(
                $request,
                'Permintaan terlalu cepat (deteksi flood L7). Mohon perlambat akses Anda.',
                $burst['retry_after']
            );
        }

        $minute = $guard->hitBucket('global_60s', $ipKey, 100, 60, true, $ipKey);
        if (!$minute['allowed']) {
            return $guard->tooManyRequestsResponse(
                $request,
                'Batas permintaan per menit tercapai. Silakan tunggu sejenak.',
                $minute['retry_after']
            );
        }

        // 5. Global POST flood limit across all dynamic forms/endpoints
        if ($method === 'POST') {
            $postLimit = $guard->hitBucket('global_post_60s', $ipKey, 15, 60, true, $ipKey);
            if (!$postLimit['allowed']) {
                return $guard->tooManyRequestsResponse(
                    $request,
                    'Terlalu banyak pengiriman data dalam 1 menit.',
                    $postLimit['retry_after']
                );
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
