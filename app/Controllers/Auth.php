<?php

namespace App\Controllers;

use App\Libraries\OtpMailerService;
use App\Libraries\OtpService;
use App\Libraries\SecurityGuard;
use App\Models\UserModel;

class Auth extends BaseController
{
    private const ALLOWED_CAPTCHA_SCOPES = ['register', 'contact', 'login', 'changepwd', 'forgot'];
    private const DUMMY_BCRYPT_HASH      = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    /**
     * Serve single-use pure-PHP distorted PNG CAPTCHA image for protected forms.
     */
    public function captcha(string $scope = 'register')
    {
        $guard = new SecurityGuard();
        $ipKey = $guard->getIpKey($this->request);

        $bucket = $guard->hitBucket('captcha_gen', $ipKey, 30, 60, true, $ipKey);
        if (!$bucket['allowed']) {
            return $guard->tooManyRequestsResponse($this->request, 'Terlalu sering memuat ulang CAPTCHA.', $bucket['retry_after'], true);
        }

        if (!in_array($scope, self::ALLOWED_CAPTCHA_SCOPES, true)) {
            $scope = 'register';
        }

        $png = $guard->generateCaptchaPng($scope);

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'image/png')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setBody($png);
    }

    public function login()
    {
        $guard = new SecurityGuard();
        $ipKey = $guard->getIpKey($this->request);
        $guard->issueFormTimer('login');

        return view('v_login', $this->buildLoginViewData($guard, $ipKey));
    }

    public function process()
    {
        $session = session();
        $guard   = new SecurityGuard();
        $ipKey   = $guard->getIpKey($this->request);

        if (trim((string) $this->request->getPost('website_url')) !== '') {
            $guard->recordStrike($ipKey);
            $guard->logSecurityEvent('HONEYPOT_TRAP', 'warning', null, 'Honeypot terpicu di form login', $this->request);
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        $ipLimit = $guard->hitBucket('login_ip', $ipKey, 8, 60, true, $ipKey);
        if (!$ipLimit['allowed']) {
            $guard->logSecurityEvent('RATE_LIMIT_LOGIN', 'warning', null, 'Melebihi batas percobaan login IP (8/menit)', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Terlalu banyak percobaan login dari IP Anda. Silakan tunggu 1 menit.',
                $ipLimit['retry_after']
            );
        }

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            $session->setFlashdata('error', 'Email dan kata sandi wajib diisi.');
            return redirect()->to('/login');
        }

        // Per-target-account lock after 5 failed attempts in 15 minutes
        $emailKey = md5($email . '|' . $ipKey);
        if ($guard->peekBucketCount('login_email_fail', $emailKey, 900) >= 5) {
            $guard->recordStrike($ipKey);
            $guard->logSecurityEvent('LOGIN_LOCKED', 'critical', $email, 'Akun dikunci 15 menit setelah 5 gagal login', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Akun ini terkunci sementara karena terlalu banyak percobaan sandi yang salah (15 menit).',
                900
            );
        }

        // Enforce CAPTCHA on login once 3+ failures occurred from this IP
        if ($guard->peekBucketCount('login_fail', $ipKey, 900) >= 3) {
            if (!$guard->verifyHumanChallenge('login', $this->request)) {
                $guard->issueFormTimer('login');
                $guard->logSecurityEvent('LOGIN_CAPTCHA_FAIL', 'warning', $email, 'Gagal verifikasi CAPTCHA saat login', $this->request);
                $this->response->setStatusCode(422);
                return view('v_login', array_merge($this->buildLoginViewData($guard, $ipKey), [
                    'form_error' => 'Kode verifikasi (CAPTCHA) salah atau kedaluwarsa.',
                    'old_email'  => $email,
                ]));
            }
        }

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        // Constant-time timing balance so attackers cannot probe whether email exists
        if (!$user) {
            password_verify($password, self::DUMMY_BCRYPT_HASH);
            $guard->hitBucket('login_fail', $ipKey, 100, 900, false);
            $guard->hitBucket('login_email_fail', $emailKey, 100, 900, false);
            $guard->logSecurityEvent('LOGIN_FAILED', 'warning', $email, 'Percobaan login gagal (kredensial tidak cocok)', $this->request);
            $session->setFlashdata('error', 'Email atau kata sandi yang Anda masukkan salah!');
            return redirect()->to('/login');
        }

        $storedPassword = (string) $user['password'];
        $isHashed       = password_get_info($storedPassword)['algo'] !== null;
        $passwordValid  = $isHashed
            ? password_verify($password, $storedPassword)
            : hash_equals($storedPassword, $password);

        if ($passwordValid) {
            $updateData = [];
            if (!$isHashed || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $plan       = $user['subscription_plan'] ?? 'free';
            $expireDate = $user['expire_date'] ?? null;
            if ($plan !== 'free' && !empty($expireDate) && strtotime($expireDate) < strtotime('today')) {
                $plan                        = 'free';
                $expireDate                  = null;
                $updateData['subscription_plan'] = 'free';
                $updateData['expire_date']       = null;
            }

            if (!empty($updateData)) {
                $userModel->update($user['id'], $updateData);
            }

            $session->regenerate();
            $session->set([
                'id'                => $user['id'],
                'nama'              => $user['nama'],
                'email'             => $user['email'],
                'role'              => $user['role'],
                'subscription_plan' => $plan,
                'expire_date'       => $expireDate,
                'logged_in'         => true,
            ]);

            $guard->logSecurityEvent('LOGIN_SUCCESS', 'info', $email, 'Login berhasil (' . ($user['role'] ?? 'user') . ')', $this->request);

            if ($user['role'] === 'admin') {
                return redirect()->to('/admin');
            }
            return redirect()->to('/');
        }

        $guard->hitBucket('login_fail', $ipKey, 100, 900, false);
        $guard->hitBucket('login_email_fail', $emailKey, 100, 900, false);
        $guard->logSecurityEvent('LOGIN_FAILED', 'warning', $email, 'Percobaan login gagal (kredensial tidak cocok)', $this->request);
        $session->setFlashdata('error', 'Email atau kata sandi yang Anda masukkan salah!');
        return redirect()->to('/login');
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        return redirect()->to('/login');
    }

    public function register()
    {
        $guard = new SecurityGuard();
        $guard->issueFormTimer('register');

        return view('v_register', $this->buildRegisterViewData($guard));
    }

    public function saveRegister()
    {
        $guard = new SecurityGuard();
        $ipKey = $guard->getIpKey($this->request);

        // 1. Honeypot trap
        if (trim((string) $this->request->getPost('website_url')) !== '') {
            $guard->recordStrike($ipKey);
            $guard->logSecurityEvent('HONEYPOT_TRAP', 'warning', null, 'Honeypot terpicu di registrasi', $this->request);
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        // 2. Atomic per-IP attempt rate limit (max 5 attempts / 15 min)
        $attemptLimit = $guard->hitBucket('reg_attempt_15m', $ipKey, 5, 900, true, $ipKey);
        if (!$attemptLimit['allowed']) {
            $guard->logSecurityEvent('REGISTER_BLOCKED', 'warning', null, 'Melebihi batas percobaan register (5/15m)', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Terlalu banyak percobaan pendaftaran akun dari IP Anda.',
                $attemptLimit['retry_after']
            );
        }

        // 3. Persistent MySQL registration quota (max 3 accounts / 15 min AND 5 / 24h per IP)
        $dbShort = $guard->checkDbQuota('register_ok', $ipKey, 3, 900);
        if (!$dbShort['allowed']) {
            $guard->recordStrike($ipKey);
            $guard->logSecurityEvent('REGISTER_BLOCKED', 'warning', null, 'Kuota register 15m terlampaui', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Batas pendaftaran akun sementara dari IP Anda tercapai (maks 3 akun / 15 menit).',
                $dbShort['retry_after']
            );
        }

        $dbDaily = $guard->checkDbQuota('register_ok', $ipKey, 5, 86400);
        if (!$dbDaily['allowed']) {
            $guard->recordStrike($ipKey);
            $guard->logSecurityEvent('REGISTER_BLOCKED', 'warning', null, 'Kuota register harian terlampaui', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Batas pendaftaran harian dari IP Anda tercapai (maks 5 akun / 24 jam).',
                $dbDaily['retry_after']
            );
        }

        // 3b. Global site-wide distributed botnet signup circuit breakers (across ALL IPs combined)
        $globalRegAttempt = $guard->hitBucket('global_reg_attempt', 'site', 20, 900, false);
        if (!$globalRegAttempt['allowed']) {
            $guard->logSecurityEvent('REGISTER_GLOBAL_CAP', 'critical', null, 'Burst pendaftaran global tercapai (20/15m)', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Pendaftaran akun sedang dibatasi sementara karena lonjakan traffic. Coba beberapa menit lagi.',
                $globalRegAttempt['retry_after']
            );
        }

        $globalDbShort = $guard->checkDbGlobalQuota('register_ok', 12, 900);
        if (!$globalDbShort['allowed']) {
            $guard->logSecurityEvent('REGISTER_GLOBAL_CAP', 'critical', null, 'Kuota pendaftaran global 15m tercapai (12/15m)', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Antrean pendaftaran akun sedang penuh sementara. Silakan coba lagi sebentar.',
                $globalDbShort['retry_after']
            );
        }

        $globalDbDaily = $guard->checkDbGlobalQuota('register_ok', 25, 86400);
        if (!$globalDbDaily['allowed']) {
            $guard->logSecurityEvent('REGISTER_GLOBAL_CAP', 'critical', null, 'Kuota pendaftaran global harian tercapai (25/24j)', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Batas pendaftaran akun harian sistem tercapai sementara.',
                $globalDbDaily['retry_after']
            );
        }

        $nama  = trim((string) $this->request->getPost('nama'));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $pass  = (string) $this->request->getPost('password');

        // 4. Reject instant automated submissions (< 1.5s from form load)
        if (!$guard->verifyFormTimer('register', 1.5)) {
            $guard->recordStrike($ipKey);
            $guard->issueFormTimer('register');
            $guard->logSecurityEvent('REGISTER_BLOCKED', 'warning', $email, 'Submit registrasi terlalu cepat (<1.5s)', $this->request);
            $this->response->setStatusCode(422);
            return view('v_register', array_merge($this->buildRegisterViewData($guard), [
                'form_error' => 'Pengiriman terlalu cepat atau sesi formulir kedaluwarsa. Silakan ulangi dengan wajar.',
                'old_nama'   => $nama,
                'old_email'  => $email,
            ]));
        }

        // 5. Verify single-use PNG CAPTCHA (or Cloudflare Turnstile if configured)
        if (!$guard->verifyHumanChallenge('register', $this->request)) {
            $guard->issueFormTimer('register');
            $guard->logSecurityEvent('REGISTER_CAPTCHA_FAIL', 'warning', $email, 'CAPTCHA registrasi salah/kedaluwarsa', $this->request);
            $this->response->setStatusCode(422);
            return view('v_register', array_merge($this->buildRegisterViewData($guard), [
                'form_error' => 'Kode verifikasi (CAPTCHA) salah atau kedaluwarsa.',
                'old_nama'   => $nama,
                'old_email'  => $email,
            ]));
        }

        // 6. Field validation
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama' => [
                'label'  => 'Nama Lengkap',
                'rules'  => 'required|min_length[2]|max_length[100]',
                'errors' => [
                    'required'   => 'Nama lengkap wajib diisi.',
                    'min_length' => 'Nama minimal 2 karakter.',
                ],
            ],
            'email' => [
                'label'  => 'Alamat Email',
                'rules'  => 'required|valid_email|max_length[254]|is_unique[users.email]',
                'errors' => [
                    'required'    => 'Alamat email wajib diisi.',
                    'valid_email' => 'Format email tidak valid.',
                    'is_unique'   => 'Email ini sudah terdaftar atau tidak dapat digunakan. Silakan login atau pakai email lain.',
                ],
            ],
            'password' => [
                'label'  => 'Kata Sandi',
                'rules'  => 'required|min_length[8]|max_length[128]',
                'errors' => [
                    'required'   => 'Kata sandi wajib diisi.',
                    'min_length' => 'Kata sandi minimal 8 karakter.',
                ],
            ],
        ]);

        $isValid = $validation->withRequest($this->request)->run();
        if (!$isValid || preg_match('/(https?:\/\/|www\.)/i', $nama)) {
            $errMsg = !$isValid
                ? implode(' ', $validation->getErrors())
                : 'Nama tidak boleh berisi tautan/URL.';

            $guard->issueFormTimer('register');
            $this->response->setStatusCode(422);
            return view('v_register', array_merge($this->buildRegisterViewData($guard), [
                'form_error' => $errMsg,
                'old_nama'   => $nama,
                'old_email'  => $email,
            ]));
        }

        // 7. Validate real email domain (block disposable mailers & invalid MX records)
        $domainCheck = $guard->validateEmailDomain($email);
        if (!$domainCheck['valid']) {
            $guard->issueFormTimer('register');
            $guard->logSecurityEvent('DISPOSABLE_EMAIL_BLOCKED', 'warning', $email, $domainCheck['reason'], $this->request);
            $this->response->setStatusCode(422);
            return view('v_register', array_merge($this->buildRegisterViewData($guard), [
                'form_error' => $domainCheck['reason'],
                'old_nama'   => $nama,
                'old_email'  => $email,
            ]));
        }

        // 8. Enforce strong password complexity
        $pwdCheck = $guard->validatePasswordStrength($pass);
        if (!$pwdCheck['valid']) {
            $guard->issueFormTimer('register');
            $this->response->setStatusCode(422);
            return view('v_register', array_merge($this->buildRegisterViewData($guard), [
                'form_error' => $pwdCheck['reason'],
                'old_nama'   => $nama,
                'old_email'  => $email,
            ]));
        }

        $passwordHash = password_hash($pass, PASSWORD_DEFAULT);
        $mailer       = new OtpMailerService();

        if ($mailer->isRegisterOtpRequired()) {
            $otpService = new OtpService($guard, $mailer);
            $otpRes     = $otpService->issueAndSendOtp('register', $email, $nama, $ipKey, [
                'nama'          => $nama,
                'email'         => $email,
                'password_hash' => $passwordHash,
            ]);

            if (!$otpRes['ok']) {
                if (($otpRes['retry_after'] ?? 0) > 0) {
                    return $guard->tooManyRequestsResponse($this->request, $otpRes['message'], (int) $otpRes['retry_after']);
                }
                $guard->issueFormTimer('register');
                $this->response->setStatusCode(422);
                return view('v_register', array_merge($this->buildRegisterViewData($guard), [
                    'form_error' => $otpRes['message'] . ' Silakan coba lagi atau hubungi Admin.',
                    'old_nama'   => $nama,
                    'old_email'  => $email,
                ]));
            }

            session()->set('pending_otp_register', ['email' => $email, 'nama' => $nama]);
            $guard->logSecurityEvent('REGISTER_OTP_SENT', 'info', $email, 'Kode OTP registrasi dikirim', $this->request);

            return redirect()
                ->to('/verify-otp')
                ->with('otp_info', 'Kode OTP 6 digit telah dikirim ke ' . $email . '. Periksa Inbox / folder Spam.');
        }

        $userModel = new UserModel();
        $userModel->save([
            'nama'              => $nama,
            'email'             => $email,
            'password'          => $passwordHash,
            'role'              => 'user',
            'subscription_plan' => 'free',
            'expire_date'       => null,
        ]);

        $guard->recordDbHit('register_ok', $ipKey);
        $guard->logSecurityEvent('REGISTER_SUCCESS', 'info', $email, 'Akun baru berhasil dibuat', $this->request);

        return redirect()->to('/login')->with('success', 'Akun berhasil dibuat! Silakan masuk sistem.');
    }

    public function verifyRegisterOtp()
    {
        $pending = session()->get('pending_otp_register');
        if (!is_array($pending) || empty($pending['email'])) {
            return redirect()->to('/register')->with('error', 'Sesi pendaftaran OTP tidak ditemukan atau sudah selesai.');
        }

        $email      = (string) $pending['email'];
        $otpService = new OtpService();

        return view('v_verify_otp', [
            'email'              => $email,
            'cooldown_remaining' => $otpService->getResendCooldownRemaining('register', $email),
        ]);
    }

    public function submitVerifyRegisterOtp()
    {
        $session = session();
        $pending = $session->get('pending_otp_register');
        if (!is_array($pending) || empty($pending['email'])) {
            return redirect()->to('/register')->with('error', 'Sesi verifikasi OTP sudah kedaluwarsa. Silakan daftar ulang.');
        }

        $guard = new SecurityGuard();
        $ipKey = $guard->getIpKey($this->request);
        $email = strtolower(trim((string) $pending['email']));

        $limit = $guard->hitBucket('otp_verify_ip', $ipKey, 10, 300, true, $ipKey);
        if (!$limit['allowed']) {
            return $guard->tooManyRequestsResponse($this->request, 'Terlalu banyak percobaan verifikasi OTP.', $limit['retry_after']);
        }

        $code       = (string) $this->request->getPost('otp_code');
        $otpService = new OtpService($guard);
        $verify     = $otpService->verifyOtp('register', $email, $code, $ipKey);

        if (!$verify['ok']) {
            $guard->logSecurityEvent('REGISTER_OTP_FAILED', 'warning', $email, $verify['message'], $this->request);
            $this->response->setStatusCode(422);
            return view('v_verify_otp', [
                'email'              => $email,
                'form_error'         => $verify['message'],
                'cooldown_remaining' => $otpService->getResendCooldownRemaining('register', $email),
            ]);
        }

        $payload   = $verify['payload'];
        $userModel = new UserModel();
        if (!is_array($payload) || empty($payload['nama']) || empty($payload['password_hash'])) {
            return redirect()->to('/register')->with('error', 'Data pendaftaran kedaluwarsa. Silakan daftar ulang.');
        }

        if ($userModel->where('email', $email)->first()) {
            $session->remove('pending_otp_register');
            return redirect()->to('/login')->with('success', 'Email ini sudah terdaftar dan aktif. Silakan masuk.');
        }

        $userModel->save([
            'nama'              => (string) $payload['nama'],
            'email'             => $email,
            'password'          => (string) $payload['password_hash'],
            'email_verified_at' => date('Y-m-d H:i:s'),
            'role'              => 'user',
            'subscription_plan' => 'free',
            'expire_date'       => null,
        ]);

        $session->remove('pending_otp_register');
        $guard->recordDbHit('register_ok', $ipKey);
        $guard->logSecurityEvent('REGISTER_SUCCESS', 'info', $email, 'Akun baru dibuat & terverifikasi via OTP email', $this->request);

        return redirect()->to('/login')->with('success', 'Email terverifikasi & akun berhasil dibuat! Silakan masuk.');
    }

    public function resendRegisterOtp()
    {
        $session = session();
        $pending = $session->get('pending_otp_register');
        if (!is_array($pending) || empty($pending['email'])) {
            return redirect()->to('/register')->with('error', 'Sesi pendaftaran OTP tidak ditemukan.');
        }

        $guard      = new SecurityGuard();
        $ipKey      = $guard->getIpKey($this->request);
        $email      = strtolower(trim((string) $pending['email']));
        $otpService = new OtpService($guard);
        $payload    = $otpService->getLatestRegisterPayload($email);

        if (!$payload) {
            return redirect()->to('/register')->with('error', 'Data pendaftaran sudah tidak tersedia. Silakan isi form daftar kembali.');
        }

        $res = $otpService->issueAndSendOtp('register', $email, (string) ($payload['nama'] ?? ''), $ipKey, $payload);
        if (!$res['ok']) {
            if (($res['retry_after'] ?? 0) > 0 && str_contains($res['message'], 'IP Anda')) {
                return $guard->tooManyRequestsResponse($this->request, $res['message'], (int) $res['retry_after']);
            }
            return redirect()->to('/verify-otp')->with('otp_error', $res['message']);
        }

        $guard->logSecurityEvent('REGISTER_OTP_RESENT', 'info', $email, 'OTP registrasi dikirim ulang', $this->request);
        return redirect()->to('/verify-otp')->with('otp_info', 'Kode OTP baru telah dikirim ke ' . $email . '.');
    }

    public function forgotPassword()
    {
        $guard      = new SecurityGuard();
        $mailer     = new OtpMailerService();
        $otpService = new OtpService($guard, $mailer);
        $guard->issueFormTimer('forgot');

        $sessionEmail = (string) (session()->get('forgot_otp_email') ?? '');
        $step         = ($this->request->getGet('step') === 'reset' && $sessionEmail !== '') ? 'reset' : 'request';

        return view('v_forgot_password', [
            'step'               => $step,
            'email'              => $sessionEmail,
            'mailer_configured'  => $mailer->isConfigured(),
            'turnstile_site_key' => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
            'cooldown_remaining' => $sessionEmail !== '' ? $otpService->getResendCooldownRemaining('forgot_pwd', $sessionEmail) : 0,
        ]);
    }

    public function sendForgotOtp()
    {
        $guard  = new SecurityGuard();
        $ipKey  = $guard->getIpKey($this->request);
        $mailer = new OtpMailerService();

        if (trim((string) $this->request->getPost('website_url')) !== '') {
            $guard->recordStrike($ipKey);
            $guard->logSecurityEvent('HONEYPOT_TRAP', 'warning', null, 'Honeypot terpicu di forgot password', $this->request);
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        $limit = $guard->hitBucket('forgot_ip', $ipKey, 6, 900, true, $ipKey);
        if (!$limit['allowed']) {
            return $guard->tooManyRequestsResponse($this->request, 'Terlalu banyak permintaan reset sandi. Tunggu sebentar.', $limit['retry_after']);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $renderReqError = function (string $msg) use ($guard, $mailer, $email) {
            $guard->issueFormTimer('forgot');
            $this->response->setStatusCode(422);
            return view('v_forgot_password', [
                'step'               => 'request',
                'email'              => $email,
                'form_error'         => $msg,
                'mailer_configured'  => $mailer->isConfigured(),
                'turnstile_site_key' => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
                'cooldown_remaining' => 0,
            ]);
        };

        if (!$guard->verifyHumanChallenge('forgot', $this->request)) {
            return $renderReqError('Kode verifikasi (CAPTCHA) salah atau kedaluwarsa.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $renderReqError('Masukkan alamat email yang valid.');
        }

        if (!$mailer->isConfigured()) {
            return $renderReqError('Pengiriman email OTP belum diaktifkan oleh Admin. Gunakan "Ganti Sandi" jika Anda masih ingat sandi lama.');
        }

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        if ($user) {
            $otpService = new OtpService($guard, $mailer);
            $sendRes    = $otpService->issueAndSendOtp('forgot_pwd', $email, (string) ($user['nama'] ?? ''), $ipKey);
            if ($sendRes['ok']) {
                $guard->logSecurityEvent('FORGOT_OTP_SENT', 'info', $email, 'OTP reset sandi dikirim', $this->request);
            } elseif (($sendRes['retry_after'] ?? 0) > 0 && str_contains($sendRes['message'], 'IP Anda')) {
                return $guard->tooManyRequestsResponse($this->request, $sendRes['message'], (int) $sendRes['retry_after']);
            }
        } else {
            // Constant-work simulation against timing enumeration
            hash_hmac('sha256', $email, 'dummy_salt');
            usleep(120000);
            $guard->logSecurityEvent('FORGOT_OTP_UNKNOWN', 'info', $email, 'Permintaan OTP reset untuk email tidak terdaftar (disamarkan)', $this->request);
        }

        session()->set('forgot_otp_email', $email);

        return redirect()
            ->to('/forgot-password?step=reset')
            ->with('otp_info', 'Jika alamat email tersebut terdaftar, kode OTP 6 digit telah dikirim. Periksa kotak masuk atau folder Spam.');
    }

    public function resetForgotPassword()
    {
        $session = session();
        $guard   = new SecurityGuard();
        $ipKey   = $guard->getIpKey($this->request);
        $mailer  = new OtpMailerService();

        if (trim((string) $this->request->getPost('website_url')) !== '') {
            $guard->recordStrike($ipKey);
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        $limit = $guard->hitBucket('forgot_reset_ip', $ipKey, 8, 900, true, $ipKey);
        if (!$limit['allowed']) {
            return $guard->tooManyRequestsResponse($this->request, 'Terlalu banyak percobaan reset sandi.', $limit['retry_after']);
        }

        $email       = strtolower(trim((string) ($this->request->getPost('email') ?: ($session->get('forgot_otp_email') ?? ''))));
        $otpCode     = (string) $this->request->getPost('otp_code');
        $newPassword = (string) $this->request->getPost('new_password');
        $confirmPwd  = (string) $this->request->getPost('confirm_password');
        $otpService  = new OtpService($guard, $mailer);

        $renderResetErr = function (string $msg) use ($mailer, $otpService, $email) {
            $this->response->setStatusCode(422);
            return view('v_forgot_password', [
                'step'               => 'reset',
                'email'              => $email,
                'form_error'         => $msg,
                'mailer_configured'  => $mailer->isConfigured(),
                'turnstile_site_key' => '',
                'cooldown_remaining' => $email !== '' ? $otpService->getResendCooldownRemaining('forgot_pwd', $email) : 0,
            ]);
        };

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $renderResetErr('Alamat email tidak valid.');
        }

        $pwdCheck = $guard->validatePasswordStrength($newPassword);
        if (!$pwdCheck['valid']) {
            return $renderResetErr($pwdCheck['reason']);
        }
        if ($newPassword !== $confirmPwd) {
            return $renderResetErr('Konfirmasi kata sandi baru tidak cocok.');
        }

        $verify = $otpService->verifyOtp('forgot_pwd', $email, $otpCode, $ipKey);
        if (!$verify['ok']) {
            $guard->logSecurityEvent('FORGOT_OTP_FAILED', 'warning', $email, $verify['message'], $this->request);
            return $renderResetErr($verify['message']);
        }

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();
        if (!$user) {
            return $renderResetErr('Kode OTP tidak valid atau sudah kedaluwarsa.');
        }

        $updateData = ['password' => password_hash($newPassword, PASSWORD_DEFAULT)];
        if (empty($user['email_verified_at'])) {
            $updateData['email_verified_at'] = date('Y-m-d H:i:s');
        }
        $userModel->update($user['id'], $updateData);

        $session->remove('forgot_otp_email');
        $session->regenerate();
        $guard->logSecurityEvent('PASSWORD_RESET_OTP', 'info', $email, 'Kata sandi direset menggunakan OTP email', $this->request);

        return redirect()->to('/login')->with('success', 'Kata sandi berhasil direset via OTP! Silakan masuk dengan sandi baru.');
    }

    public function changePassword()
    {
        $guard = new SecurityGuard();
        $ipKey = $guard->getIpKey($this->request);

        if (trim((string) $this->request->getPost('website_url')) !== '') {
            $guard->recordStrike($ipKey);
            $guard->logSecurityEvent('HONEYPOT_TRAP', 'warning', null, 'Honeypot terpicu di ganti sandi', $this->request);
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        $limit = $guard->hitBucket('change_pwd_ip', $ipKey, 5, 60, true, $ipKey);
        if (!$limit['allowed']) {
            $guard->logSecurityEvent('RATE_LIMIT_CHANGEPWD', 'warning', null, 'Batas percobaan ganti sandi tercapai', $this->request);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Terlalu banyak percobaan ubah kata sandi. Silakan tunggu 1 menit.',
                $limit['retry_after']
            );
        }

        $email           = strtolower(trim((string) $this->request->getPost('email')));
        $oldPassword     = (string) $this->request->getPost('old_password');
        $newPassword     = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        $renderChangeError = function (string $msg) use ($guard, $ipKey, $email) {
            $guard->issueFormTimer('login');
            $this->response->setStatusCode(422);
            return view('v_login', array_merge($this->buildLoginViewData($guard, $ipKey), [
                'form_error'       => $msg,
                'old_cp_email'     => $email,
                'open_change_mode' => true,
            ]));
        };

        if (!$guard->verifyHumanChallenge('changepwd', $this->request)) {
            $guard->logSecurityEvent('CHANGEPWD_CAPTCHA_FAIL', 'warning', $email, 'CAPTCHA ganti sandi salah/kedaluwarsa', $this->request);
            return $renderChangeError('Kode verifikasi (CAPTCHA) salah atau kedaluwarsa.');
        }

        if ($email === '' || $oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return $renderChangeError('Semua kolom ganti sandi wajib diisi.');
        }

        $pwdCheck = $guard->validatePasswordStrength($newPassword);
        if (!$pwdCheck['valid']) {
            return $renderChangeError($pwdCheck['reason']);
        }

        if ($newPassword !== $confirmPassword) {
            return $renderChangeError('Konfirmasi kata sandi baru tidak cocok.');
        }

        if ($newPassword === $oldPassword) {
            return $renderChangeError('Kata sandi baru tidak boleh sama dengan kata sandi lama.');
        }

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        if (!$user) {
            password_verify($oldPassword, self::DUMMY_BCRYPT_HASH);
            $guard->logSecurityEvent('CHANGEPWD_FAILED', 'warning', $email, 'Percobaan ganti sandi gagal', $this->request);
            return $renderChangeError('Email atau kata sandi lama tidak sesuai.');
        }

        $storedPassword = (string) $user['password'];
        $isHashed       = password_get_info($storedPassword)['algo'] !== null;
        $passwordValid  = $isHashed
            ? password_verify($oldPassword, $storedPassword)
            : hash_equals($storedPassword, $oldPassword);

        if (!$passwordValid) {
            $guard->logSecurityEvent('CHANGEPWD_FAILED', 'warning', $email, 'Percobaan ganti sandi gagal', $this->request);
            return $renderChangeError('Email atau kata sandi lama tidak sesuai.');
        }

        $userModel->update($user['id'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $guard->logSecurityEvent('CHANGE_PASSWORD', 'info', $email, 'Kata sandi akun diperbarui', $this->request);

        return redirect()
            ->to('/login')
            ->with('success', 'Kata sandi berhasil diperbarui! Silakan login dengan sandi baru.');
    }

    private function buildRegisterViewData(SecurityGuard $guard): array
    {
        $mailer = new OtpMailerService();
        return [
            'turnstile_site_key'    => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
            'register_otp_required' => $mailer->isRegisterOtpRequired(),
        ];
    }

    private function buildLoginViewData(SecurityGuard $guard, string $ipKey): array
    {
        $mailer = new OtpMailerService();
        return [
            'show_captcha'       => $guard->peekBucketCount('login_fail', $ipKey, 900) >= 3,
            'turnstile_site_key' => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
            'otp_mailer_active'  => $mailer->isConfigured(),
        ];
    }
}
