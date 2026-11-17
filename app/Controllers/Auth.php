<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{

    public function login()
    {
        return view('v_login');
    }


    public function process()
    {
        $session   = session();
        $throttler = \Config\Services::throttler();
        $ipHash    = md5((string) $this->request->getIPAddress());

        if ($throttler->check('login_' . $ipHash, 10, MINUTE) === false) {
            $session->setFlashdata('error', 'Terlalu banyak percobaan login. Silakan tunggu 1 menit.');
            return redirect()->to('/login');
        }

        $userModel = new UserModel();

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            $session->setFlashdata('error', 'Email dan kata sandi wajib diisi.');
            return redirect()->to('/login');
        }

        $user = $userModel->where('email', $email)->first();

        if ($user) {
            $storedPassword = (string) $user['password'];
            $isHashed       = password_get_info($storedPassword)['algo'] !== null;
            $passwordValid  = $isHashed
                ? password_verify($password, $storedPassword)
                : hash_equals($storedPassword, $password);

            if ($passwordValid) {
                // Upgrade password legacy plaintext ke hash aman saat login berhasil
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

            $session->setFlashdata('error', 'Kata sandi yang Anda masukkan salah!');
            return redirect()->to('/login');
        }

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
        return view('v_register');
    }

    public function saveRegister()
    {
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

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()
                ->to('/register')
                ->withInput()
                ->with('error', implode(' ', $validation->getErrors()));
        }

        $userModel = new UserModel();
        $nama      = trim((string) $this->request->getPost('nama'));
        $email     = strtolower(trim((string) $this->request->getPost('email')));
        $pass      = (string) $this->request->getPost('password');

        $userModel->save([
            'nama'              => $nama,
            'email'             => $email,
            'password'          => password_hash($pass, PASSWORD_DEFAULT),
            'role'              => 'user',
            'subscription_plan' => 'free',
            'expire_date'       => null,
        ]);

        return redirect()->to('/login')->with('success', 'Akun berhasil dibuat! Silakan masuk sistem.');
    }

    public function changePassword()
    {
        $session   = session();
        $throttler = \Config\Services::throttler();
        $ipHash    = md5((string) $this->request->getIPAddress());

        if ($throttler->check('change_pwd_' . $ipHash, 5, MINUTE) === false) {
            return redirect()
                ->to('/login?mode=change')
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan. Silakan tunggu 1 menit.');
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
}