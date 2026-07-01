<?php

namespace App\Controllers;

use App\Models\SearchHistoryModel; // <-- Wajib dipanggil untuk akses tabel history

class Home extends BaseController
{
    // ==========================================
    // 1. TAMPILAN HALAMAN UTAMA (Saat pertama kali dibuka)
    // ==========================================
    public function index()
    {
        $session = session();
        $usage_count = 0;
        $usage_limit = 5; // Default free

        // Jika user sudah login, cek dia sudah melakukan berapa pencarian hari ini
        if ($session->get('logged_in')) {
            $user_id = $session->get('id');
            $paket = $session->get('subscription_plan');
            
            // --- TAMBAHAN BARU: Sesuaikan limit awal berdasarkan paket ---
            if ($paket === 'plus') {
                $usage_limit = 50;
            } elseif ($paket === 'pro') {
                $usage_limit = 999999; // Pro unlimited
            }
            // -------------------------------------------------------------

            $historyModel = new SearchHistoryModel();
            $usage_count = $historyModel->countUserSearchesToday($user_id);
        }

        // Siapkan data default pembungkus awal
        $data = [
            'status'      => null,
            'email'       => '',
            'details'     => [],
            'usage_count' => $usage_count, 
            'usage_limit' => $usage_limit, 
            'statistik'   => [
                'sumber_aktif'      => '48',
                'tingkat_kebocoran' => 'Kritis',
                'total_akun'        => '352K+'
            ]
        ];

        return view('pwned_search', $data);
    }

    // ==========================================
    // 2. FUNGSI CEK EMAIL (Saat tombol ditekan)
    // ==========================================
    public function cekEmail()
    {
        $session = session();
        $emailInput = $this->request->getPost('email');
        
        $usage_count = 0;
        $usage_limit = 5; // Default untuk free user
        $is_premium = false;

        // -- LOGIKA PEMBATASAN KUOTA --
        if ($session->get('logged_in')) {
            $user_id = $session->get('id');
            $paket = $session->get('subscription_plan');
            $is_premium = ($paket === 'pro' || $paket === 'plus');

            // Sesuaikan batas kuota berdasarkan paket (sama seperti di index())
            if ($paket === 'plus') {
                $usage_limit = 50;
            } elseif ($paket === 'pro') {
                $usage_limit = 999999; // Pro = unlimited
            }

            $historyModel = new \App\Models\SearchHistoryModel();
            
            // Hitung sudah berapa kali user ini mencari email HARI INI
            $usage_count = $historyModel->countUserSearchesToday($user_id);

            // Jika limit habis dan BUKAN premium, tolak pencarian dan ARAHKAN KE UPGRADE!
            // Untuk user free: blokir jika usage_count >= 5
            if ($usage_count >= $usage_limit && !$is_premium) {
                
                // Melempar user ke halaman upgrade dengan membawa pesan khusus
                return redirect()->to('/upgrade')->with('limit_reached', 'Waduh! Batas pengecekan gratis Anda hari ini sudah habis. Upgrade sekarang untuk akses tanpa batas.');
                
            }
        } else {
            // Jika tamu (belum login) mencoba mencari email, arahkan ke login
            return redirect()->to('/login')->with('error', 'Silakan masuk ke sistem terlebih dahulu untuk mengecek email.');
        }
        // -- SIMULASI LOGIK PENGECEKAN DATABASE / API (Kode Asli Milikmu) --
        if (strpos($emailInput, 'bocor') !== false || $emailInput == 'admin@gmail.com') {
            $status = 'pwned';
            $details = [
                [
                    'sumber' => 'Tokopedia Breach',
                    'tanggal' => '2020-05-12',
                    'jenis' => 'Email, Password, Nama Lengkap'
                ],
                [
                    'sumber' => 'Zynga Leak',
                    'tanggal' => '2019-09-03',
                    'jenis' => 'Email, Password, Username'
                ]
            ];
        } else {
            $status = 'safe';
            $details = [];
        }

        // -- CATAT HISTORY SETELAH PENCARIAN BERHASIL --
        if ($session->get('logged_in')) {
            $historyModel->insert([
                'user_id'       => $user_id,
                'email_checked' => $emailInput
            ]);
            // Tambah 1 ke angka hitungan agar tampilan di layar langsung ter-update (contoh: dari 0 jadi 1)
            $usage_count++; 
        }

        // Siapkan kembali data untuk dikirim ke view beserta hasilnya
        $data = [
            'status'      => $status,
            'email'       => $emailInput,
            'details'     => $details,
            'usage_count' => $usage_count,
            'usage_limit' => $usage_limit,
            'statistik'   => [
                'sumber_aktif'      => '48',
                'tingkat_kebocoran' => 'Kritis',
                'total_akun'        => '352K+'
            ]
        ];

        return view('pwned_search', $data);
    }

    // ==========================================
    // 3. FUNGSI HALAMAN UPGRADE
    // ==========================================
    public function upgrade()
    {
        return view('v_upgrade');
    }

    // ==========================================
    // 4. FUNGSI PROSES UPGRADE (Simulasi Transaksi)
    // ==========================================
public function prosesUpgrade($paket)
{
    $session = session();
    if (!$session->get('logged_in')) return redirect()->to('/login');

    $userId = $session->get('id');

    // Ambil mode dari query string (?mode=tahunan atau ?mode=bulanan)
    $mode = $this->request->getGet('mode') ?? 'bulanan';

    // Tabel harga RESMI di server — jangan pernah percaya harga dari frontend/JS
    $hargaTabel = [
        'plus' => ['bulanan' => 25000, 'tahunan' => 252000], // 21.000 x 12
        'pro'  => ['bulanan' => 50000, 'tahunan' => 504000], // 42.000 x 12
    ];

    // Validasi paket & mode supaya tidak bisa dimanipulasi lewat URL
    if (!isset($hargaTabel[$paket]) || !in_array($mode, ['bulanan', 'tahunan'])) {
        return redirect()->to('/upgrade')->with('limit_reached', 'Paket atau mode langganan tidak valid.');
    }

    $harga = $hargaTabel[$paket][$mode];

    // Masa aktif ditentukan oleh MODE, bukan nama paket
    $expire_date = ($mode === 'tahunan')
        ? date('Y-m-d', strtotime('+1 year'))
        : date('Y-m-d', strtotime('+1 month'));

    // Catat Transaksi
    $transactionModel = new \App\Models\TransactionModel();
    $id_transaksi = $transactionModel->insert([
        'user_id' => $userId,
        'paket'   => $paket,
        'mode'    => $mode,     // simpan juga mode-nya, berguna untuk nota & admin
        'harga'   => $harga,
        'tanggal' => date('Y-m-d H:i:s')
    ]);

    // Update User
    $userModel = new \App\Models\UserModel();
    $userModel->update($userId, [
        'subscription_plan' => $paket,
        'expire_date'       => $expire_date
    ]);
    $session->set('subscription_plan', $paket);

    return redirect()->to('/upgrade/nota/' . $id_transaksi);
}

    // Fungsi Baru: Menampilkan Halaman Nota
    public function nota($id_transaksi)
    {
        $transactionModel = new \App\Models\TransactionModel();
        // Ambil data transaksi beserta nama & email user
        $transaksi = $transactionModel->select('transactions.*, users.nama, users.email')
                                      ->join('users', 'users.id = transactions.user_id')
                                      ->where('transactions.id', $id_transaksi)
                                      ->first();

        if (!$transaksi) return redirect()->to('/');

        return view('v_nota', ['transaksi' => $transaksi]);
    }
}