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
        $query = $db->query("SELECT SUM(harga) as total_uang FROM transactions WHERE status IN ('paid', 'settlement')");
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

    public function users()
    {
        return $this->index();
    }

    public function updateSubscription($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $plan   = $this->request->getPost('subscription_plan');
        $expire = $this->request->getPost('expire_date');

        if (!in_array($plan, ['free', 'plus', 'pro'], true)) {
            return redirect()->to('/admin')->with('pesan', 'Paket tidak valid.');
        }

        $this->userModel->update($id, [
            'subscription_plan' => $plan,
            'expire_date'       => ($plan === 'free' || empty($expire)) ? null : $expire,
            'pending_plan'      => null,
        ]);

        return redirect()->to('/admin')->with('pesan', 'Status langganan berhasil diperbarui!');
    }



public function sales()
{
    $session = session();
    if ($session->get('role') !== 'admin') {
        return redirect()->to('/')->with('error', 'Akses ditolak!');
    }

    $transactionModel = new \App\Models\TransactionModel();
    $db = \Config\Database::connect();

    $total_pendapatan = $db->query("SELECT SUM(harga) as total FROM transactions WHERE status IN ('paid', 'settlement')")->getRow()->total ?? 0;

    $pendapatan_bulanan = $db->query("
        SELECT DATE_FORMAT(tanggal, '%Y-%m') as bulan,
               SUM(harga) as total,
               COUNT(*) as jumlah_transaksi
        FROM transactions
        WHERE status IN ('paid', 'settlement')
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

    public function verifyTransaction($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $transactionModel = new \App\Models\TransactionModel();
        $trx              = $transactionModel->find((int) $id);

        if (!$trx) {
            return redirect()->to('/admin/sales')->with('pesan', 'Transaksi tidak ditemukan.');
        }

        if (($trx['status'] ?? 'pending') === 'pending') {
            $transactionModel->update((int) $id, ['status' => 'paid']);

            $user     = $this->userModel->find((int) $trx['user_id']);
            $baseTime = time();
            if (
                $user
                && ($user['subscription_plan'] ?? 'free') === $trx['paket']
                && !empty($user['expire_date'])
                && strtotime($user['expire_date']) > time()
            ) {
                $baseTime = strtotime($user['expire_date']);
            }
            $duration   = (($trx['mode'] ?? 'bulanan') === 'tahunan') ? '+1 year' : '+1 month';
            $expireDate = date('Y-m-d', strtotime($duration, $baseTime));

            $this->userModel->update((int) $trx['user_id'], [
                'subscription_plan' => $trx['paket'],
                'expire_date'       => $expireDate,
                'pending_plan'      => null,
            ]);
        }

        return redirect()->to('/admin/sales')->with('pesan', 'Transaksi #' . (int) $id . ' berhasil diverifikasi LUNAS.');
    }

public function exportCSV()
{
    $session = session();
    if ($session->get('role') !== 'admin') {
        return redirect()->to('/')->with('error', 'Akses ditolak!');
    }

    $transactionModel = new \App\Models\TransactionModel();
    $transactions = $transactionModel
        ->select('transactions.*, users.nama, users.email')
        ->join('users', 'users.id = transactions.user_id')
        ->orderBy('transactions.tanggal', 'DESC')
        ->findAll();

    $csvContent = "No,Nama User,Email,Paket,Durasi,Harga,Status,Tanggal Transaksi\n";

    foreach ($transactions as $i => $trx) {
        $durasi = (isset($trx['mode']) && $trx['mode'] === 'tahunan') ? 'Tahunan' : 'Bulanan';
        $baris = [
            $i + 1, $trx['nama'], $trx['email'], strtoupper($trx['paket']),
            $durasi, $trx['harga'], strtoupper($trx['status'] ?? 'pending'), date('d-m-Y H:i', strtotime($trx['tanggal']))
        ];
        $csvContent .= implode(',', array_map(fn($k) => '"' . str_replace('"', '""', $k) . '"', $baris)) . "\n";
    }

    return $this->response
        ->setHeader('Content-Type', 'text/csv; charset=utf-8')
        ->setHeader('Content-Disposition', 'attachment; filename=Data_Penjualan_PWNED_' . date('Y-m-d') . '.csv')
        ->setBody($csvContent);
}

    public function requests()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $db   = \Config\Database::connect();
        $rows = $db->table('user_requests')
            ->select('user_requests.*, users.nama, users.email, users.subscription_plan as plan_now')
            ->join('users', 'users.id = user_requests.user_id')
            ->orderBy('user_requests.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $r) {
            $st = $r['status'] ?? 'pending';
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
        }

        return view('admin/requests', [
            'title'    => 'Inbox Requests',
            'active'   => 'requests',
            'requests' => $rows,
            'counts'   => $counts,
        ]);
    }

    public function approveRequest($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $db  = \Config\Database::connect();
        $req = $db->table('user_requests')->where('id', (int) $id)->get()->getRowArray();

        if (!$req) {
            return redirect()->to('/admin/requests')->with('pesan', 'Request tidak ditemukan.');
        }

        $db->table('user_requests')->where('id', (int) $id)->update([
            'status'     => 'approved',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $planMap = [
            'upgrade_plus'   => ['plan' => 'plus', 'expire' => date('Y-m-d', strtotime('+1 month'))],
            'upgrade_pro'    => ['plan' => 'pro',  'expire' => date('Y-m-d', strtotime('+1 month'))],
            'downgrade_free' => ['plan' => 'free', 'expire' => null],
        ];

        $jenis = $req['jenis_request'] ?? 'other';
        if (isset($planMap[$jenis])) {
            $this->userModel->update((int) $req['user_id'], [
                'subscription_plan' => $planMap[$jenis]['plan'],
                'expire_date'       => $planMap[$jenis]['expire'],
                'pending_plan'      => null,
            ]);
        }

        return redirect()->to('/admin/requests')->with('pesan', 'Request #' . (int) $id . ' berhasil disetujui.');
    }

    public function rejectRequest($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $db = \Config\Database::connect();
        $db->table('user_requests')->where('id', (int) $id)->update([
            'status'     => 'rejected',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/requests')->with('pesan', 'Request #' . (int) $id . ' ditolak.');
    }

    public function getNotifCount()
    {
        if (session()->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['count' => 0]);
        }

        $db    = \Config\Database::connect();
        $count = $db->table('user_requests')->where('status', 'pending')->countAllResults();

        return $this->response->setJSON(['count' => $count]);
    }
}