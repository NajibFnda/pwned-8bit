<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    // =========================================
    // FITUR LOGIN & LOGOUT
    // =========================================

    // 1. Menampilkan form login
    public function login()
    {
        return view('v_login');
    }

    // 2. Memproses data login
// 2. Memproses data login
// 2. Memproses data login
    public function process()
    {
        $session = session();
        $userModel = new UserModel();

        // Ambil data yang diketik di form
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Cari user berdasarkan email di database
        $user = $userModel->where('email', $email)->first();

        if ($user) {
            // Cocokkan password
            if ($password === $user['password']) {
                
                // Simpan data user ke Session
                $ses_data = [
                    'id'                => $user['id'],
                    'nama'              => $user['nama'],
                    'email'             => $user['email'],
                    'role'              => $user['role'],
                    'subscription_plan' => $user['subscription_plan'],
                    'logged_in'         => TRUE
                ];
                $session->set($ses_data);
                
                // =========================================
                // PENGECEKAN ROLE (ARAHAN REDIRECT)
                // =========================================
                if ($user['role'] === 'admin') {
                    // Jika Admin, lempar ke dashboard admin
                    return redirect()->to('/admin');
                } else {
                    // Jika User biasa, kembalikan ke halaman depan (Home)
                    return redirect()->to('/');
                }
                
            } else {
                $session->setFlashdata('error', 'Kata sandi yang Anda masukkan salah!');
                return redirect()->to('/login');
            }
        } else {
            $session->setFlashdata('error', 'Alamat email tidak ditemukan!');
            return redirect()->to('/login');
        }
    }

    // 3. Memproses logout
    public function logout()
    {
        $session = session();
        $session->destroy();
        return redirect()->to('/login');
    }


    // =========================================
    // FITUR REGISTRASI
    // =========================================

    // 4. Menampilkan form registrasi
    public function register()
    {
        return view('v_register');
    }

    // 5. Memproses penyimpanan data registrasi
    public function saveRegister()
    {
        $userModel = new UserModel();

        $nama  = $this->request->getPost('nama');
        $email = $this->request->getPost('email');
        $pass  = $this->request->getPost('password');

        // Simpan data pendaftar baru ke database
        $userModel->save([
            'nama'              => $nama,
            'email'             => $email,
            'password'          => $pass, 
            'role'              => 'user',         // Hak akses otomatis sebagai user biasa
            'subscription_plan' => 'free',         // Otomatis dapat paket free
            'expire_date'       => null            // Tidak ada kedaluwarsa untuk paket free
        ]);

        // Arahkan kembali ke halaman login dengan membawa pesan sukses
        return redirect()->to('/login')->with('success', 'Akun berhasil dibuat! Silakan masuk sistem.');
    }
}