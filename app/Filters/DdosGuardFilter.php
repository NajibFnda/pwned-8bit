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
        $uri    = $request->getUri();
        $rawPath = method_exists($uri, 'getRoutePath') ? (string) $uri->getRoutePath() : (string) $uri->getPath();
        $path   = ltrim((string) preg_replace('#^index\.php/?#i', '', trim($rawPath, '/')), '/');
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

        // 1b. Block oversized / exploit query strings & headless empty-UA POST floods before MySQL
        $queryStr     = (string) $uri->getQuery();
        $decodedQuery = $queryStr !== '' ? rawurldecode(str_replace('+', ' ', $queryStr)) : '';
        if (
            strlen($queryStr) > 512
            || ($decodedQuery !== '' && preg_match('/(union\s+select|benchmark\s*\(|sleep\s*\(|<script|\.\.\/\.\.)/i', $decodedQuery))
        ) {
            $guard->recordStrike($ipKey);
            return service('response')
                ->setStatusCode(403)
                ->setBody('Forbidden.');
        }
        if ($method === 'POST' && $ua === '') {
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

        // 3b. Zero-DB Proof-of-Work verification endpoint
        if ($path === '__shield-verify') {
            if ($method !== 'POST') {
                return service('response')->setStatusCode(405)->setBody('Method Not Allowed');
            }
            $powTry = $guard->hitBucket('pow_verify_ip', $ipKey, 20, 60, true, $ipKey);
            if (!$powTry['allowed']) {
                return $guard->tooManyRequestsResponse($request, 'Terlalu banyak percobaan verifikasi.', $powTry['retry_after'], true);
            }
            return $guard->verifyAndIssuePowCookie($request);
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

        // 6. Multi-IP distributed surge detector & Admin Under-Attack PoW shield
        $hasClearance = $guard->hasValidPowClearance($request);
        if (!$hasClearance) {
            $siteSurge = $guard->hitBucket('global_site_10s', 'site', 120, 10, false);
            if (!$siteSurge['allowed']) {
                if ($guard->triggerAutoShield(300)) {
                    $guard->logSecurityEvent(
                        'SURGE_AUTO_SHIELD',
                        'critical',
                        null,
                        'Lonjakan multi-IP (>120 req/10d) mengaktifkan mode Proof-of-Work otomatis 5 menit.',
                        $request
                    );
                }
                return $guard->powChallengeResponse($request);
            }
            if ($guard->isUnderAttackMode()) {
                return $guard->powChallengeResponse($request);
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
