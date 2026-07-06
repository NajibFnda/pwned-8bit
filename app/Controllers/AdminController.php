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

public function index()
    {
        $session = session();
        if ($session->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $transactionModel = new \App\Models\TransactionModel();
        
        $filter_paket = $this->request->getGet('paket'); 
        
        if ($filter_paket && in_array($filter_paket, ['free', 'plus', 'pro'])) {
            $users = $this->userModel->where('subscription_plan', $filter_paket)->findAll();
        } else {
            $users = $this->userModel->findAll();
        }

        $db = \Config\Database::connect();
        $query = $db->query("SELECT SUM(harga) as total_uang FROM transactions");
        $hasil = $query->getRow();
        $total_pendapatan = $hasil->total_uang ?? 0;

        $data = [
            'title'             => 'Manajemen Pengguna', 
            'active'            => 'users', 
            'users'             => $users,
            'real_transactions' => $transactionModel->getTransactionsWithUser(),
            'total_pendapatan'  => $total_pendapatan,
            'filter_aktif'      => $filter_paket
        ];

        return view('admin/user_list', $data);
    }
    public function updateSubscription($id)
    {
        $plan = $this->request->getPost('subscription_plan');
        $expire = $this->request->getPost('expire_date');

        $this->userModel->update($id, [
            'subscription_plan' => $plan,
            'expire_date' => $expire,
            'pending_plan' => null,  // hapus pending jika admin edit manual
        ]);

        return redirect()->to('/admin')->with('pesan', 'Status langganan berhasil diperbarui!');
    }

    public function approvePendingPlan($id)
    {
        $session = session();
        if ($session->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $user = $this->userModel->find((int) $id);
        if (!$user || empty($user['pending_plan'])) {
            return redirect()->to('/admin')->with('pesan', 'Tidak ada pending plan untuk disetujui.');
        }

        $newPlan = $user['pending_plan'];

        // Ambil transaksi terakhir user untuk menentukan mode (bulanan/tahunan)
        $transactionModel = new \App\Models\TransactionModel();
        $lastTrx = $transactionModel
            ->where('user_id', (int) $id)
            ->where('paket', $newPlan)
            ->orderBy('tanggal', 'DESC')
            ->first();

        $mode = $lastTrx['mode'] ?? 'bulanan';
        $expire_date = ($mode === 'tahunan')
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        // Update plan aktif + hapus pending
        $this->userModel->update((int) $id, [
            'subscription_plan' => $newPlan,
            'expire_date'       => $expire_date,
            'pending_plan'      => null,
        ]);

        return redirect()->to('/admin')
            ->with('pesan', 'Plan ' . strtoupper($newPlan) . ' berhasil diaktifkan untuk user #' . $id . '!');
    }

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