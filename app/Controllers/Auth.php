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
        $session = session();
        $userModel = new UserModel();

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $userModel->where('email', $email)->first();

        if ($user) {
            if ($password === $user['password']) {
                
                $ses_data = [
                    'id'                => $user['id'],
                    'nama'              => $user['nama'],
                    'email'             => $user['email'],
                    'role'              => $user['role'],
                    'subscription_plan' => $user['subscription_plan'],
                    'logged_in'         => TRUE
                ];
                $session->set($ses_data);
                
                if ($user['role'] === 'admin') {
                    return redirect()->to('/admin');
                } else {
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
        $userModel = new UserModel();

        $nama  = $this->request->getPost('nama');
        $email = $this->request->getPost('email');
        $pass  = $this->request->getPost('password');

        $userModel->save([
            'nama'              => $nama,
            'email'             => $email,
            'password'          => $pass, 
            'role'              => 'user',      
            'subscription_plan' => 'free',       
            'expire_date'       => null            
        ]);

        return redirect()->to('/login')->with('success', 'Akun berhasil dibuat! Silakan masuk sistem.');
    }
}