<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Terapkan security headers standar untuk produksi & dev
        $this->response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->response->setHeader('X-Content-Type-Options', 'nosniff');
        $this->response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->response->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (strtolower((string) $request->getHeaderLine('X-Forwarded-Proto')) === 'https')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

        if ($isHttps && defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            $this->response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://challenges.cloudflare.com https://app.midtrans.com https://app.sandbox.midtrans.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' blob: https://cdn.jsdelivr.net https://challenges.cloudflare.com",
            "frame-src 'self' https://challenges.cloudflare.com https://app.midtrans.com https://app.sandbox.midtrans.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
        $this->response->setHeader('Content-Security-Policy', $csp);
    }
}
