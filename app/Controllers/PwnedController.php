<?php

namespace App\Controllers;

class PwnedController extends BaseController
{
    public function cekEmail()
    {
        $data = [
            'status'    => $status, 
            'email'     => $email,  
            'details'   => $details, 
            'statistik' => [
                'sumber_aktif'      => '48',
                'tingkat_kebocoran' => 'Kritis',
                'total_akun'        => '352K+'
            ]
        ];

        return view('pwned_search', $data);
    }
}