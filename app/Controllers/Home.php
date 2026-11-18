<?php

namespace App\Controllers;

use App\Libraries\SecurityGuard;
use App\Libraries\XonApiService;
use App\Models\SearchHistoryModel;
use Midtrans\Config;
use Midtrans\Snap;

class Home extends BaseController
{
    private const PLAN_PRICES = [
        'plus' => ['bulanan' => 25000, 'tahunan' => 252000],
        'pro'  => ['bulanan' => 50000, 'tahunan' => 504000],
    ];

    public function index()
    {
        $session = session();

        $usage_count    = $this->getGuestUsageCount();
        $usage_limit    = 2;
        $recent_history = [];

        if ($session->get('logged_in')) {
            $user    = $this->syncUserSessionPlan((int) $session->get('id'));
            $user_id = (int) $session->get('id');
            $paket   = $user['subscription_plan'] ?? 'free';

            $usage_limit = match ($paket) {
                'pro'   => 999999,
                'plus'  => 50,
                default => 5,
            };

            $historyModel   = new SearchHistoryModel();
            $usage_count    = $historyModel->countUserSearchesToday($user_id);
            $recent_history = $historyModel->getRecentUniqueEmails($user_id, 5);
        }

        $metricsData = $this->buildMetricsViewData();

        $data = array_merge([
            'status'         => null,
            'email'          => '',
            'details'        => [],
            'usage_count'    => $usage_count, 
            'usage_limit'    => $usage_limit,
            'recent_history' => $recent_history,
        ], $metricsData);

        return view('pwned_search', $data);
    }

    public function cekEmail()
    {
        $session = session();
        $guard   = new SecurityGuard();
        $ipKey   = $guard->getIpKey($this->request);

        $limit = $guard->hitBucket('cek_email_ip', $ipKey, 10, 60, true, $ipKey);
        if (!$limit['allowed']) {
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Terlalu banyak permintaan pengecekan email dalam waktu singkat.',
                $limit['retry_after']
            );
        }

        $is_logged_in = $session->get('logged_in');

        if (!$is_logged_in) {
            $usage_count = $this->getGuestUsageCount();
            $usage_limit = 2;
            
            if ($usage_count >= $usage_limit) {
                return redirect()
                    ->to('/login')
                    ->with('error', 'Batas coba gratis untuk tamu sudah habis. Silakan masuk untuk melanjutkan.');
            }
        } else {
            $user_id = (int) $session->get('id');
            $user    = $this->syncUserSessionPlan($user_id);
            $paket   = $user['subscription_plan'] ?? 'free';

            $usage_limit = match ($paket) {
                'pro'   => 999999,
                'plus'  => 50,
                default => 5,
            };
            $is_premium   = in_array($paket, ['pro', 'plus'], true);
            $historyModel = new SearchHistoryModel();
            $usage_count  = $historyModel->countUserSearchesToday($user_id);

            if (!$is_premium && $usage_count >= $usage_limit) {
                return redirect()
                    ->to('/upgrade')
                    ->with('limit_reached', 'Batas pengecekan gratis Anda hari ini sudah habis. Upgrade untuk akses tanpa batas.');
            }
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'email' => [
                'label'  => 'Alamat Email',
                'rules'  => 'required|valid_email|max_length[254]',
                'errors' => [
                    'required'    => 'Alamat email wajib diisi.',
                    'valid_email' => 'Format email tidak valid. Contoh: nama@domain.com',
                    'max_length'  => 'Email terlalu panjang (maksimal 254 karakter).',
                ],
            ],
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', implode(' ', $validation->getErrors()));
        }

        $emailInput = strtolower(trim($this->request->getPost('email')));

        $status      = 'error';
        $details     = [];
        $totalBreach = 0;
        $apiMessage  = '';

        $xonService = new XonApiService();
        $apiResult  = $xonService->checkEmail($emailInput);

        $status      = $apiResult['status'];        // 'pwned' | 'safe' | 'error'
        $details     = $apiResult['details'];
        $totalBreach = $apiResult['total_breach'];
        $apiMessage  = $apiResult['message'];

        if (in_array($status, ['pwned', 'safe'], true)) {
            if ($is_logged_in) {
                $historyModel->insert([
                    'user_id'       => $user_id,
                    'email_checked' => $emailInput, 
                ]);
            } else {
                $this->incrementGuestUsageCount($usage_count);
            }
            $usage_count++;
        }

        $recent_history = $is_logged_in ? $historyModel->getRecentUniqueEmails($user_id, 5) : [];
        $metricsData    = $this->buildMetricsViewData($xonService);

        $data = array_merge([
            'status'         => $status,
            'email'          => $emailInput,
            'details'        => $details,
            'total_breach'   => $totalBreach,
            'api_message'    => $apiMessage,
            'usage_count'    => $usage_count,
            'usage_limit'    => $usage_limit,
            'recent_history' => $recent_history,
        ], $metricsData);

        return view('pwned_search', $data);
    }

    /**
     * Terima pesan/permintaan pengguna dari modal Contact Admin dan simpan ke tabel user_requests.
     */
    public function kirimRequest()
    {
        $session = session();
        $guard   = new SecurityGuard();
        $ipKey   = $guard->getIpKey($this->request);

        if (trim((string) $this->request->getPost('website_url')) !== '') {
            $guard->recordStrike($ipKey);
            return $guard->tooManyRequestsResponse($this->request, 'Permintaan ditolak (deteksi bot).', 120, true);
        }

        if (!$session->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'     => 'error',
                'message'    => 'Silakan login terlebih dahulu sebelum mengirim permintaan ke Admin.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $userId   = (int) $session->get('id');
        $burstLim = $guard->hitBucket('contact_ip_10m', $ipKey, 6, 600, true, $ipKey);
        if (!$burstLim['allowed']) {
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Terlalu banyak percobaan pengiriman dalam waktu singkat.',
                $burstLim['retry_after'],
                true
            );
        }

        $shortLim = $guard->checkDbQuota('contact_req', $ipKey, 3, 600, $userId);
        if (!$shortLim['allowed']) {
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Maksimal 3 pesan dapat dikirim setiap 10 menit.',
                $shortLim['retry_after'],
                true
            );
        }

        $dailyLim = $guard->checkDbQuota('contact_req', $ipKey, 5, 86400, $userId);
        if (!$dailyLim['allowed']) {
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Batas pengiriman pesan harian tercapai (maks 5 permintaan / 24 jam).',
                $dailyLim['retry_after'],
                true
            );
        }

        if (!$guard->verifyHumanChallenge('contact', $this->request)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'     => 'error',
                'message'    => 'Kode verifikasi (CAPTCHA) salah atau kedaluwarsa.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $nama  = mb_substr(trim((string) $this->request->getPost('nama')), 0, 100);
        $email = mb_substr(strtolower(trim((string) $this->request->getPost('email'))), 0, 150);
        $jenis = trim((string) $this->request->getPost('jenis'));
        $pesan = mb_substr(trim((string) $this->request->getPost('pesan')), 0, 1000);

        if ($nama === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'     => 'error',
                'message'    => 'Nama dan alamat email yang valid wajib diisi.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $allowedTypes = ['upgrade_plus', 'upgrade_pro', 'downgrade_free', 'other'];
        if ($jenis === 'report') {
            $jenis = 'other';
            $pesan = '[LAPORAN MASALAH] ' . $pesan;
        } elseif (!in_array($jenis, $allowedTypes, true)) {
            $jenis = 'other';
        }

        $db = \Config\Database::connect();
        $db->table('user_requests')->insert([
            'user_id'       => $userId,
            'jenis_request' => $jenis,
            'pesan'         => $pesan !== '' ? $pesan : '(Dikirim oleh ' . $nama . ')',
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $guard->recordDbHit('contact_req', $ipKey, $userId);

        return $this->response->setJSON([
            'status'     => 'ok',
            'message'    => 'Permintaan Anda berhasil dikirim ke Admin.',
            'csrf_token' => csrf_hash(),
        ]);
    }

    /**
     * Endpoint JSON publik untuk polling real-time grafik Breach Trends & statistik global.
     */
    public function breachTrends()
    {
        $guard = new SecurityGuard();
        $ipKey = $guard->getIpKey($this->request);

        $limit = $guard->hitBucket('trends_ip', $ipKey, 20, 60, true, $ipKey);
        if (!$limit['allowed']) {
            return $guard->tooManyRequestsResponse(
                $this->request,
                'Terlalu banyak permintaan API statistik.',
                $limit['retry_after'],
                true
            );
        }

        $xonService = new XonApiService();
        $metrics    = $xonService->getGlobalMetrics();

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setJSON(array_merge(['status' => 'ok'], $metrics));
    }

    /**
     * Bangun data metrik global & ringkasan kartu statistik untuk view pwned_search.
     */
    private function buildMetricsViewData(?XonApiService $xonService = null): array
    {
        $xonService ??= new XonApiService();
        $metrics = $xonService->getGlobalMetrics();
        $guard   = new SecurityGuard();

        return [
            'global_metrics'     => $metrics,
            'turnstile_site_key' => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
            'statistik'          => [
                'sumber_aktif'      => number_format((int) ($metrics['breaches_count'] ?? 0)),
                'tingkat_kebocoran' => $metrics['yoy_diff_pct'] ?? '+0%',
                'total_akun'        => $metrics['breaches_records_short'] ?? '0',
            ],
        ];
    }


    public function upgrade()
    {
        $session      = session();
        $guard        = new SecurityGuard();
        $isLoggedIn   = (bool) $session->get('logged_in');
        $currentPlan  = 'free';
        $expireDate   = null;
        $pendingPlan  = null;
        $usageCount   = $this->getGuestUsageCount();
        $usageLimit   = 2;
        $transactions = [];

        if ($isLoggedIn) {
            $userId      = (int) $session->get('id');
            $user        = $this->syncUserSessionPlan($userId);
            $currentPlan = $user['subscription_plan'] ?? 'free';
            $expireDate  = $user['expire_date'] ?? null;
            $pendingPlan = $user['pending_plan'] ?? null;

            $usageLimit = match ($currentPlan) {
                'pro'   => 999999,
                'plus'  => 50,
                default => 5,
            };

            $historyModel = new SearchHistoryModel();
            $usageCount   = $historyModel->countUserSearchesToday($userId);

            $transactionModel = new \App\Models\TransactionModel();
            $transactions     = $transactionModel
                ->where('user_id', $userId)
                ->orderBy('tanggal', 'DESC')
                ->limit(5)
                ->findAll();
        }

        return view('v_upgrade', [
            'is_logged_in' => $isLoggedIn,
            'current_plan' => $currentPlan,
            'expire_date'  => $expireDate,
            'pending_plan' => $pendingPlan,
            'usage_count'  => $usageCount,
            'usage_limit'  => $usageLimit,
            'transactions'       => $transactions,
            'prices'             => self::PLAN_PRICES,
            'turnstile_site_key' => $guard->isTurnstileEnabled() ? $guard->getTurnstileSiteKey() : '',
        ]);
    }

    public function prosesUpgrade(?string $paket = null)
    {
        return redirect()
            ->to('/upgrade?contact=1')
            ->with('upgrade_info', 'Pembayaran otomatis belum tersedia saat ini. Silakan ajukan upgrade melalui tombol Contact Admin.');
    }

    public function midtransWebhook()
    {
        Config::$serverKey    = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION') === 'true';

        try {
            $notif = new \Midtrans\Notification();
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setBody($e->getMessage());
        }

        $transaction = $notif->transaction_status;
        $type        = $notif->payment_type;
        $order_id    = $notif->order_id;
        $fraud       = $notif->fraud_status;

        $parts   = explode('-', $order_id);
        $real_id = (count($parts) >= 2 && $parts[0] === 'PWNED') ? (int) $parts[1] : $order_id;

        $transactionModel = new \App\Models\TransactionModel();
        $trx              = $transactionModel->find($real_id);

        if (!$trx) {
            return $this->response->setStatusCode(404)->setBody("Transaction not found");
        }

        $mode = $trx['mode'] ?? 'bulanan';

        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $transactionModel->update($real_id, ['status' => 'challenge']);
                } else {
                    $transactionModel->update($real_id, ['status' => 'paid']);
                    $this->activateUserPlan((int) $trx['user_id'], $trx['paket'], $mode);
                }
            }
        } elseif ($transaction == 'settlement') {
            $transactionModel->update($real_id, ['status' => 'paid']);
            $this->activateUserPlan((int) $trx['user_id'], $trx['paket'], $mode);
        } elseif ($transaction == 'pending') {
            $transactionModel->update($real_id, ['status' => 'pending']);
        } elseif ($transaction == 'deny') {
            $transactionModel->update($real_id, ['status' => 'deny']);
        } elseif ($transaction == 'expire') {
            $transactionModel->update($real_id, ['status' => 'expire']);
        } elseif ($transaction == 'cancel') {
            $transactionModel->update($real_id, ['status' => 'cancel']);
        }

        return $this->response->setStatusCode(200)->setBody("OK");
    }

    public function nota($id_transaksi)
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan masuk untuk melihat nota transaksi.');
        }

        $transactionModel = new \App\Models\TransactionModel();
        $transaksi        = $transactionModel
            ->select('transactions.*, users.nama, users.email, users.expire_date')
            ->join('users', 'users.id = transactions.user_id')
            ->where('transactions.id', (int) $id_transaksi)
            ->first();

        if (!$transaksi || !$this->canAccessTransaction($transaksi)) {
            return redirect()->to('/upgrade')->with('limit_reached', 'Transaksi tidak ditemukan atau bukan milik akun Anda.');
        }

        $snapToken     = $session->getFlashdata('snapToken') ?: $session->get('snap_token_' . $transaksi['id']);
        $paymentMethod = $session->get('payment_method_' . $transaksi['id']) ?? 'qris';

        return view('v_nota', [
            'transaksi'      => $transaksi,
            'snap_token'     => $snapToken,
            'payment_method' => $paymentMethod,
        ]);
    }

    public function confirmUpgrade($id_transaksi)
    {
        return redirect()
            ->to('/upgrade?contact=1')
            ->with('upgrade_info', 'Pembayaran otomatis belum tersedia. Silakan hubungi Admin melalui tombol Contact Admin untuk aktivasi paket.');
    }

    private function getGuestUsageCount(): int
    {
        $session    = session();
        $sessionCnt = (int) ($session->get('guest_usage_count') ?? 0);
        $guard      = new SecurityGuard();
        $ipKey      = $guard->getIpKey($this->request);
        $cacheKey   = 'guest_quota_' . $ipKey . '_' . date('Y-m-d');
        $ipCnt      = (int) (cache()->get($cacheKey) ?? 0);
        $dbInfo     = $guard->checkDbQuota('guest_check', $ipKey, 2, 86400);

        return max($sessionCnt, $ipCnt, (int) ($dbInfo['count'] ?? 0));
    }

    private function incrementGuestUsageCount(int $currentCount): void
    {
        $next     = $currentCount + 1;
        $guard    = new SecurityGuard();
        $ipKey    = $guard->getIpKey($this->request);
        $cacheKey = 'guest_quota_' . $ipKey . '_' . date('Y-m-d');
        session()->set('guest_usage_count', $next);
        cache()->save($cacheKey, $next, 86400);
        $guard->recordDbHit('guest_check', $ipKey);
    }

    public function cancelUpgrade($id_transaksi)
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $transactionModel = new \App\Models\TransactionModel();
        $trx              = $transactionModel->find((int) $id_transaksi);

        if (!$trx || !$this->canAccessTransaction($trx)) {
            return redirect()->to('/upgrade')->with('limit_reached', 'Transaksi tidak valid.');
        }

        if ($trx['status'] === 'pending') {
            $transactionModel->update($trx['id'], ['status' => 'cancel']);
            $userModel = new \App\Models\UserModel();
            $userModel->update((int) $trx['user_id'], ['pending_plan' => null]);
            $session->remove('snap_token_' . $trx['id']);
        }

        return redirect()
            ->to('/upgrade')
            ->with('upgrade_info', 'Transaksi #TRX-' . str_pad((string) $trx['id'], 5, '0', STR_PAD_LEFT) . ' dibatalkan.');
    }

    public function successLokal($id_transaksi)
    {
        return redirect()
            ->to('/upgrade?contact=1')
            ->with('upgrade_info', 'Pembayaran otomatis belum tersedia. Silakan ajukan upgrade melalui Contact Admin.');
    }

    private function canAccessTransaction(array $trx): bool
    {
        $session = session();
        if ($session->get('role') === 'admin') {
            return true;
        }
        return (int) $session->get('id') === (int) ($trx['user_id'] ?? 0);
    }

    private function activateUserPlan(int $userId, string $paket, string $mode = 'bulanan'): void
    {
        $userModel = new \App\Models\UserModel();
        $user      = $userModel->find($userId);

        // Jika memperpanjang paket yang sama dan masih aktif, tambahkan durasi dari expire_date saat ini
        $baseTimestamp = time();
        if (
            $user
            && ($user['subscription_plan'] ?? 'free') === $paket
            && !empty($user['expire_date'])
            && strtotime($user['expire_date']) > time()
        ) {
            $baseTimestamp = strtotime($user['expire_date']);
        }

        $duration    = ($mode === 'tahunan') ? '+1 year' : '+1 month';
        $expireDate  = date('Y-m-d', strtotime($duration, $baseTimestamp));

        $userModel->update($userId, [
            'subscription_plan' => $paket,
            'expire_date'       => $expireDate,
            'pending_plan'      => null,
        ]);

        $session = session();
        if ((int) $session->get('id') === $userId) {
            $session->set([
                'subscription_plan' => $paket,
                'expire_date'       => $expireDate,
            ]);
        }
    }

    /**
     * Sinkronkan subscription_plan & expire_date dari database ke session,
     * serta turunkan otomatis ke 'free' jika masa berlaku paket sudah lewat.
     */
    private function syncUserSessionPlan(int $userId): array
    {
        $userModel = new \App\Models\UserModel();
        $user      = $userModel->find($userId);

        if (!$user) {
            return ['subscription_plan' => 'free', 'expire_date' => null, 'pending_plan' => null];
        }

        $plan       = $user['subscription_plan'] ?? 'free';
        $expireDate = $user['expire_date'] ?? null;

        if ($plan !== 'free' && !empty($expireDate) && strtotime($expireDate) < strtotime('today')) {
            $plan       = 'free';
            $expireDate = null;
            $userModel->update($userId, [
                'subscription_plan' => 'free',
                'expire_date'       => null,
            ]);
        }

        $session = session();
        $session->set([
            'subscription_plan' => $plan,
            'expire_date'       => $expireDate,
        ]);

        $user['subscription_plan'] = $plan;
        $user['expire_date']       = $expireDate;

        return $user;
    }
}