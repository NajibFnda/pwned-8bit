<?php
namespace App\Controllers;
use App\Models\UserModel;


class AdminController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // Tampilkan daftar semua user
public function index()
    {
        $session = session();
        if ($session->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $transactionModel = new \App\Models\TransactionModel();
        
        // --- FITUR FILTER USER ---
        $filter_paket = $this->request->getGet('paket'); // Menangkap /admin?paket=pro
        
        if ($filter_paket && in_array($filter_paket, ['free', 'plus', 'pro'])) {
            // Jika ada filter, ambil user dengan paket tersebut
            $users = $this->userModel->where('subscription_plan', $filter_paket)->findAll();
        } else {
            // Jika tidak, ambil semua
            $users = $this->userModel->findAll();
        }

        // --- FITUR MENGHITUNG PENDAPATAN ---
        // Menggunakan query builder untuk menjumlahkan kolom 'harga'
        $db = \Config\Database::connect();
        $query = $db->query("SELECT SUM(harga) as total_uang FROM transactions");
        $hasil = $query->getRow();
        $total_pendapatan = $hasil->total_uang ?? 0;

        $data = [
            'title'             => 'Manajemen Pengguna',   // ⬅️ baru
            'active'            => 'users', 
            'users'             => $users,
            'real_transactions' => $transactionModel->getTransactionsWithUser(),
            'total_pendapatan'  => $total_pendapatan,
            'filter_aktif'      => $filter_paket // Untuk memberi tahu view filter apa yang sedang aktif
        ];

        return view('admin/user_list', $data);
    }
       // Proses update status langganan dari form
    public function updateSubscription($id)
    {
        // Ambil data dari form (jenis paket dan tanggal kedaluwarsa)
        $plan = $this->request->getPost('subscription_plan');
        $expire = $this->request->getPost('expire_date');

        // Update ke database
        $this->userModel->update($id, [
            'subscription_plan' => $plan,
            'expire_date' => $expire
        ]);

        // Kembalikan ke halaman admin dengan pesan sukses
        return redirect()->to('/admin')->with('pesan', 'Status langganan berhasil diperbarui!');
    }
    // ==========================================
// DATA PENJUALAN
// ==========================================
public function sales()
{
    $session = session();
    if ($session->get('role') !== 'admin') {
        return redirect()->to('/')->with('error', 'Akses ditolak!');
    }

    $transactionModel = new \App\Models\TransactionModel();
    $db = \Config\Database::connect();

    $total_pendapatan = $db->query("SELECT SUM(harga) as total FROM transactions")->getRow()->total ?? 0;

    $pendapatan_bulanan = $db->query("
        SELECT DATE_FORMAT(tanggal, '%Y-%m') as bulan,
               SUM(harga) as total,
               COUNT(*) as jumlah_transaksi
        FROM transactions
        GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
        ORDER BY bulan ASC
    ")->getResult();

    $data = [
        'title'              => 'Data Penjualan',
        'active'             => 'sales',
        'real_transactions'  => $transactionModel->getTransactionsWithUser(),
        'total_pendapatan'   => $total_pendapatan,
        'pendapatan_bulanan' => $pendapatan_bulanan,
    ];

    return view('admin/sales', $data);
}

public function exportCSV()
{
    $session = session();
    if ($session->get('role') !== 'admin') {
        return redirect()->to('/')->with('error', 'Akses ditolak!');
    }

    $transactionModel = new \App\Models\TransactionModel();
    $transactions = $transactionModel->getTransactionsWithUser();

    $csvContent = "No,Nama User,Email,Paket,Durasi,Harga,Tanggal Transaksi\n";

    foreach ($transactions as $i => $trx) {
        $durasi = (isset($trx['mode']) && $trx['mode'] === 'tahunan') ? 'Tahunan' : 'Bulanan';
        $baris = [
            $i + 1, $trx['nama'], $trx['email'], strtoupper($trx['paket']),
            $durasi, $trx['harga'], date('d-m-Y H:i', strtotime($trx['tanggal']))
        ];
        $csvContent .= implode(',', array_map(fn($k) => '"' . str_replace('"', '""', $k) . '"', $baris)) . "\n";
    }

    return $this->response
        ->setHeader('Content-Type', 'text/csv; charset=utf-8')
        ->setHeader('Content-Disposition', 'attachment; filename=Data_Penjualan_PWNED_' . date('Y-m-d') . '.csv')
        ->setBody($csvContent);
}
}