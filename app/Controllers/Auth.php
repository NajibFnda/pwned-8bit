<?php

namespace App\Controllers;

use App\Libraries\SecurityGuard;
use App\Models\UserModel;

class Auth extends BaseController
{
    private const ALLOWED_CAPTCHA_SCOPES = ['register', 'contact', 'login'];

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
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        $ipLimit = $guard->hitBucket('login_ip', $ipKey, 8, 60, true, $ipKey);
        if (!$ipLimit['allowed']) {
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
                $this->response->setStatusCode(422);
                return view('v_login', array_merge($this->buildLoginViewData($guard, $ipKey), [
                    'form_error' => 'Kode verifikasi (CAPTCHA) salah atau kedaluwarsa.',
                    'old_email'  => $email,
                ]));
            }
        }

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        if ($user) {
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

                if ($user['role'] === 'admin') {
                    return redirect()->to('/admin');
                }
                return redirect()->to('/');
            }

            $guard->hitBucket('login_fail', $ipKey, 100, 900, false);
            $guard->hitBucket('login_email_fail', $emailKey, 100, 900, false);
            $session->setFlashdata('error', 'Kata sandi yang Anda masukkan salah!');
            return redirect()->to('/login');
        }

        $guard->hitBucket('login_fail', $ipKey, 100, 900, false);
        $guard->hitBucket('login_email_fail', $emailKey, 100, 900, false);
        $session->setFlashdata('error', 'Alamat email tidak ditemukan!');
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
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        // 2. Atomic per-IP attempt rate limit (max 5 attempts / 15 min)
        $attemptLimit = $guard->hitBucket('reg_attempt_15m', $ipKey, 5, 900, true, $ipKey);
        if (!$attemptLimit['allowed']) {
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
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Batas pendaftaran akun sementara dari IP Anda tercapai (maks 3 akun / 15 menit).',
                $dbShort['retry_after']
            );
        }

        $dbDaily = $guard->checkDbQuota('register_ok', $ipKey, 5, 86400);
        if (!$dbDaily['allowed']) {
            $guard->recordStrike($ipKey);
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Batas pendaftaran harian dari IP Anda tercapai (maks 5 akun / 24 jam).',
                $dbDaily['retry_after']
            );
        }

        $nama  = trim((string) $this->request->getPost('nama'));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $pass  = (string) $this->request->getPost('password');

        // 4. Reject instant automated submissions (< 1.5s from form load)
        if (!$guard->verifyFormTimer('register', 1.5)) {
            $guard->recordStrike($ipKey);
            $guard->issueFormTimer('register');
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
                    'is_unique'   => 'Email ini sudah terdaftar. Silakan login.',
                ],
            ],
            'password' => [
                'label'  => 'Kata Sandi',
                'rules'  => 'required|min_length[6]|max_length[128]',
                'errors' => [
                    'required'   => 'Kata sandi wajib diisi.',
                    'min_length' => 'Kata sandi minimal 6 karakter.',
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

        $userModel = new UserModel();
        $userModel->save([
            'nama'              => $nama,
            'email'             => $email,
            'password'          => password_hash($pass, PASSWORD_DEFAULT),
            'role'              => 'user',
            'subscription_plan' => 'free',
            'expire_date'       => null,
        ]);

        $guard->recordDbHit('register_ok', $ipKey);

        return redirect()->to('/login')->with('success', 'Akun berhasil dibuat! Silakan masuk sistem.');
    }

    public function changePassword()
    {
        $guard = new SecurityGuard();
        $ipKey = $guard->getIpKey($this->request);

        if (trim((string) $this->request->getPost('website_url')) !== '') {
            $guard->recordStrike($ipKey);
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120);
        }

        $limit = $guard->hitBucket('change_pwd_ip', $ipKey, 5, 60, true, $ipKey);
        if (!$limit['allowed']) {
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

        if ($email === '' || $oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return redirect()
                ->to('/login?mode=change')
                ->withInput()
                ->with('error', 'Semua kolom ganti sandi wajib diisi.');
        }

        if (strlen($newPassword) < 6) {
            return redirect()
                ->to('/login?mode=change')
                ->withInput()
                ->with('error', 'Kata sandi baru minimal 6 karakter.');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()
                ->to('/login?mode=change')
                ->withInput()
                ->with('error', 'Konfirmasi kata sandi baru tidak cocok.');
        }

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        if (!$user) {
            return redirect()
                ->to('/login?mode=change')
                ->withInput()
                ->with('error', 'Alamat email tidak ditemukan.');
        }

        $storedPassword = (string) $user['password'];
        $isHashed       = password_get_info($storedPassword)['algo'] !== null;
        $passwordValid  = $isHashed
            ? password_verify($oldPassword, $storedPassword)
            : hash_equals($storedPassword, $oldPassword);

        if (!$passwordValid) {
            return redirect()
                ->to('/login?mode=change')
                ->withInput()
                ->with('error', 'Kata sandi lama yang Anda masukkan salah!');
        }

        $userModel->update($user['id'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return redirect()
            ->to('/login')
            ->with('success', 'Kata sandi berhasil diperbarui! Silakan login dengan sandi baru.');
    }

    private function buildRegisterViewData(SecurityGuard $guard): array
    {
        return [
            'turnstile_site_key' => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
        ];
    }

    private function buildLoginViewData(SecurityGuard $guard, string $ipKey): array
    {
        return [
            'show_captcha'       => $guard->peekBucketCount('login_fail', $ipKey, 900) >= 3,
            'turnstile_site_key' => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
        ];
    }
}
