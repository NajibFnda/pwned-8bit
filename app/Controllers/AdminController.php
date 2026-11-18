<?php
namespace App\Controllers;
use App\Libraries\RedeemService;
use App\Libraries\SecurityGuard;
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
        $search_q     = trim((string) $this->request->getGet('q'));

        $builder = $this->userModel;
        if ($filter_paket && in_array($filter_paket, ['free', 'plus', 'pro'], true)) {
            $builder = $builder->where('subscription_plan', $filter_paket);
        }
        if ($search_q !== '') {
            $builder = $builder->groupStart()
                ->like('nama', $search_q)
                ->orLike('email', $search_q)
                ->groupEnd();
        }

        $users         = $builder->orderBy('id', 'DESC')->paginate(25, 'users');
        $pager         = $this->userModel->pager;
        $totalUsers    = (new UserModel())->countAllResults();
        $filteredTotal = (int) $pager->getTotal('users');

        $db = \Config\Database::connect();
        $query = $db->query("SELECT SUM(harga) as total_uang FROM transactions WHERE status IN ('paid', 'settlement')");
        $hasil = $query->getRow();
        $total_pendapatan = $hasil->total_uang ?? 0;

        $data = [
            'title'             => 'Manajemen Pengguna',
            'active'            => 'users',
            'users'             => $users,
            'pager'             => $pager,
            'total_users'       => $totalUsers,
            'filtered_total'    => $filteredTotal,
            'per_page'          => 25,
            'search_q'          => $search_q,
            'real_transactions' => $transactionModel->getTransactionsWithUser(),
            'total_pendapatan'  => $total_pendapatan,
            'filter_aktif'      => $filter_paket,
        ];

        return view('admin/user_list', $data);
    }

    public function users()
    {
        return $this->index();
    }

    public function deleteUser($id)
    {
        $session = session();
        if ($session->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $targetId = (int) $id;
        if ($targetId === (int) $session->get('id')) {
            return redirect()->to('/admin')->with('pesan', 'Akun admin yang sedang aktif tidak bisa dihapus.');
        }

        $target = $this->userModel->find($targetId);
        if (!$target) {
            return redirect()->to('/admin')->with('pesan', 'Pengguna tidak ditemukan.');
        }
        if (($target['role'] ?? 'user') === 'admin') {
            return redirect()->to('/admin')->with('pesan', 'Akun admin tidak diizinkan dihapus.');
        }

        $this->deleteUserRecords([$targetId]);
        (new SecurityGuard())->logSecurityEvent(
            'ADMIN_DELETE_USER',
            'warning',
            session()->get('email'),
            'Menghapus user #' . $targetId . ' (' . ($target['email'] ?? '') . ')',
            $this->request
        );

        return redirect()->to('/admin')->with('pesan', 'Akun #' . $targetId . ' (' . $target['email'] . ') berhasil dihapus.');
    }

    public function cleanupSpamUsers()
    {
        $session = session();
        if ($session->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $db         = \Config\Database::connect();
        $candidates = $db->table('users u')
            ->select('u.id, u.nama, u.email')
            ->where('u.role', 'user')
            ->where('u.subscription_plan', 'free')
            ->where("NOT EXISTS (SELECT 1 FROM transactions t WHERE t.user_id = u.id)", null, false)
            ->get()
            ->getResultArray();

        $spamIds = [];
        foreach ($candidates as $row) {
            $nama  = (string) ($row['nama'] ?? '');
            $email = (string) ($row['email'] ?? '');
            $local = explode('@', $email)[0] ?? '';

            // Match automated bot strings: random alphanumeric 10-16 char username & random alnum local-part without spaces
            $isRandomName  = (bool) preg_match('/^[A-Za-z0-9]{10,16}$/', $nama);
            $isRandomEmail = (bool) preg_match('/^[a-z0-9]{10,16}$/', $local);
            $hasBotDigits  = (bool) (preg_match('/[0-9]/', $nama) || preg_match('/[0-9]/', $local));

            if ($isRandomName && $isRandomEmail && $hasBotDigits) {
                $spamIds[] = (int) $row['id'];
            }
        }

        if (empty($spamIds)) {
            return redirect()->to('/admin')->with('pesan', 'Tidak ada akun spam bot yang terdeteksi.');
        }

        $this->deleteUserRecords($spamIds);
        (new SecurityGuard())->logSecurityEvent(
            'ADMIN_CLEANUP_SPAM',
            'warning',
            session()->get('email'),
            'Membersihkan ' . count($spamIds) . ' akun spam bot',
            $this->request
        );

        return redirect()->to('/admin')->with('pesan', count($spamIds) . ' akun spam bot berhasil dibersihkan.');
    }

    private function deleteUserRecords(array $userIds): void
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds), static fn($v) => $v > 0));
        if (empty($userIds)) {
            return;
        }

        $db          = \Config\Database::connect();
        $hasClaims   = $db->tableExists('redeem_claims');
        foreach (array_chunk($userIds, 200) as $chunk) {
            if ($hasClaims) {
                $db->table('redeem_claims')->whereIn('user_id', $chunk)->delete();
            }
            $db->table('user_requests')->whereIn('user_id', $chunk)->delete();
            $db->table('search_history')->whereIn('user_id', $chunk)->delete();
            $db->table('transactions')->whereIn('user_id', $chunk)->delete();
            $db->table('users')->whereIn('id', $chunk)->delete();
        }

        // Compact next AUTO_INCREMENT back down to MAX(id) + 1 after deleting tail/spam rows
        try {
            $db->query('ALTER TABLE `users` AUTO_INCREMENT = 1');
        } catch (\Throwable $e) {
            // Non-fatal if shared host restricts ALTER TABLE privileges
        }
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

        (new SecurityGuard())->logSecurityEvent(
            'ADMIN_UPDATE_PLAN',
            'info',
            session()->get('email'),
            'Mengubah paket user #' . (int) $id . ' -> ' . strtoupper($plan),
            $this->request
        );

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

            (new SecurityGuard())->logSecurityEvent(
                'ADMIN_VERIFY_TRX',
                'info',
                session()->get('email'),
                'Verifikasi transaksi #' . (int) $id . ' (' . strtoupper((string) $trx['paket']) . ')',
                $this->request
            );
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

        (new SecurityGuard())->logSecurityEvent(
            'ADMIN_APPROVE_REQ',
            'info',
            session()->get('email'),
            'Menyetujui request #' . (int) $id . ' (' . $jenis . ') untuk user #' . (int) $req['user_id'],
            $this->request
        );

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

        (new SecurityGuard())->logSecurityEvent(
            'ADMIN_REJECT_REQ',
            'info',
            session()->get('email'),
            'Menolak request #' . (int) $id,
            $this->request
        );

        return redirect()->to('/admin/requests')->with('pesan', 'Request #' . (int) $id . ' ditolak.');
    }

    public function securityLogs()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $severity = trim((string) $this->request->getGet('severity'));
        $search   = trim((string) $this->request->getGet('q'));
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));

        $guard  = new SecurityGuard();
        $result = $guard->getSecurityAuditPage($severity, $search, $page, 30);

        return view('admin/security_logs', [
            'title'         => 'Security Audit Log',
            'active'        => 'security_logs',
            'logs'          => $result['logs'],
            'total_logs'    => $result['total'],
            'page'          => $result['page'],
            'page_count'    => $result['page_count'],
            'stats_24h'     => $result['stats_24h'],
            'severity_now'  => $severity,
            'search_q'      => $search,
        ]);
    }

    public function clearOldSecurityLogs()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $guard   = new SecurityGuard();
        $deleted = $guard->clearOldSecurityLogs(7);
        $guard->logSecurityEvent(
            'ADMIN_CLEAR_LOGS',
            'info',
            session()->get('email'),
            'Membersihkan ' . $deleted . ' entri log keamanan (> 7 hari)',
            $this->request
        );

        return redirect()->to('/admin/security-logs')->with('pesan', $deleted . ' log lama (> 7 hari) berhasil dibersihkan.');
    }

    public function redeemCodes()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $overview = (new RedeemService())->getAdminOverview();

        return view('admin/redeem', [
            'title'  => 'Kode Redeem',
            'active' => 'redeem',
            'codes'  => $overview['codes'],
            'claims' => $overview['claims'],
        ]);
    }

    public function createRedeemCode()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $rawCode      = (string) $this->request->getPost('code');
        $plan         = (string) $this->request->getPost('plan');
        $durationDays = (int) $this->request->getPost('duration_days');
        $maxUses      = (int) $this->request->getPost('max_uses');
        $expiresAt    = (string) $this->request->getPost('expires_at');
        $adminId      = (int) session()->get('id');

        $result = (new RedeemService())->createCode(
            $rawCode,
            $plan,
            $durationDays,
            $maxUses,
            $expiresAt,
            $adminId
        );

        if ($result['ok']) {
            (new SecurityGuard())->logSecurityEvent(
                'ADMIN_CREATE_REDEEM',
                'info',
                session()->get('email'),
                $result['message'],
                $this->request
            );
            return redirect()->to('/admin/redeem')->with('pesan', $result['message']);
        }

        return redirect()->to('/admin/redeem')->with('error', $result['message']);
    }

    public function toggleRedeemCode($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $row = (new RedeemService())->toggleCode((int) $id);
        if (!$row) {
            return redirect()->to('/admin/redeem')->with('error', 'Kode redeem tidak ditemukan.');
        }

        $statusLabel = ((int) $row['is_active'] === 1) ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';
        (new SecurityGuard())->logSecurityEvent(
            'ADMIN_TOGGLE_REDEEM',
            'info',
            session()->get('email'),
            'Kode ' . ($row['code'] ?? '') . ' -> ' . $statusLabel,
            $this->request
        );

        return redirect()->to('/admin/redeem')->with('pesan', 'Kode ' . $row['code'] . ' berhasil ' . strtolower($statusLabel) . '.');
    }

    public function deleteRedeemCode($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/')->with('error', 'Akses ditolak!');
        }

        $row = (new RedeemService())->deleteCode((int) $id);
        if (!$row) {
            return redirect()->to('/admin/redeem')->with('error', 'Kode redeem tidak ditemukan.');
        }

        (new SecurityGuard())->logSecurityEvent(
            'ADMIN_DELETE_REDEEM',
            'warning',
            session()->get('email'),
            'Menghapus kode redeem ' . ($row['code'] ?? ''),
            $this->request
        );

        return redirect()->to('/admin/redeem')->with('pesan', 'Kode redeem ' . $row['code'] . ' berhasil dihapus.');
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