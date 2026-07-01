<?php

namespace App\Controllers;

class PwnedController extends BaseController
{
    public function cekEmail()
    {
        // ... kode logika tim kamu untuk menangkap input email ...
        // ... kode validasi pencarian data breach ...
        
        // Contoh data hasil pemrosesan (apakah statusnya 'safe' atau 'pwned')
        // Contoh data hasil pemrosesan (Silakan sesuaikan nama variabelnya dengan kode di atasnya)
        $data = [
            'status'    => $status, // ganti dengan variabel status hasil cek milikmu
            'email'     => $email,  // ganti dengan variabel email input milikmu (TIDAK BOLEH PAKAI SPASI)
            'details'   => $details, // ganti dengan variabel detail kebocoran milikmu
            'statistik' => [
                'sumber_aktif'      => '48',
                'tingkat_kebocoran' => 'Kritis',
                'total_akun'        => '352K+'
            ]
        ];

        // Taruh return view di bawah array $data
        return view('pwned_search', $data);
    }
}