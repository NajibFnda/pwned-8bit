<?php

namespace App\Controllers;

use App\Libraries\XonApiService;
use App\Models\SearchHistoryModel;

class Home extends BaseController
{
        public function index()
        {
        $session = session();
        
        $usage_count = $session->get('guest_usage_count') ?? 0;
        $usage_limit = 2; 


        if ($session->get('logged_in')) {
            $user_id = $session->get('id');
            $paket = $session->get('subscription_plan');
            $usage_limit = 5;
            
            if ($paket === 'plus') {
                $usage_limit = 50;
            } elseif ($paket === 'pro') {
                $usage_limit = 999999; 
            }

            $historyModel = new SearchHistoryModel();
            $usage_count = $historyModel->countUserSearchesToday($user_id);
        }

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

    public function cekEmail()
    {
        $session = session();

        $is_logged_in = $session->get('logged_in');

        if (!$is_logged_in) {
            $usage_count = $session->get('guest_usage_count') ?? 0;
            $usage_limit = 2;
            
            if ($usage_count >= $usage_limit) {
                return redirect()
                    ->to('/login')
                    ->with('error', 'Batas coba gratis untuk tamu sudah habis. Silakan masuk untuk melanjutkan.');
            }
        } else {
            $user_id = (int) $session->get('id');
            $paket   = $session->get('subscription_plan') ?? 'free';

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
                $session->set('guest_usage_count', $usage_count + 1);
            }
            $usage_count++;
        }

        $data = [
            'status'       => $status,
            'email'        => $emailInput,
            'details'      => $details,
            'total_breach' => $totalBreach,
            'api_message'  => $apiMessage,
            'usage_count'  => $usage_count,
            'usage_limit'  => $usage_limit,
            'statistik'    => [
                'sumber_aktif'      => '48',
                'tingkat_kebocoran' => 'Critical',
                'total_akun'        => '352K+',
            ],
        ];

        return view('pwned_search', $data);
    }


    public function upgrade()
    {
        return view('v_upgrade');
    }

    public function prosesUpgrade($paket)
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userId = (int) $session->get('id');
        $mode   = $this->request->getGet('mode') ?? 'bulanan';

        $hargaTabel = [
            'plus' => ['bulanan' => 25000,  'tahunan' => 252000],
            'pro'  => ['bulanan' => 50000,  'tahunan' => 504000],
        ];

        if (!isset($hargaTabel[$paket]) || !in_array($mode, ['bulanan', 'tahunan'], true)) {
            return redirect()
                ->to('/upgrade')
                ->with('limit_reached', 'Paket atau mode langganan tidak valid.');
        }

        $harga       = $hargaTabel[$paket][$mode];
        $expire_date = ($mode === 'tahunan')
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        $transactionModel = new \App\Models\TransactionModel();
        $id_transaksi     = $transactionModel->insert([
            'user_id' => $userId,
            'paket'   => $paket,
            'mode'    => $mode,
            'harga'   => $harga,
            'tanggal' => date('Y-m-d H:i:s'),
        ]);

        // Simpan sebagai pending — admin harus approve dulu sebelum plan aktif
        $userModel = new \App\Models\UserModel();
        $userModel->update($userId, [
            'pending_plan' => $paket,
        ]);
        // TIDAK update subscription_plan dan TIDAK update session di sini
        // Plan baru aktif setelah admin approve

        return redirect()->to('/upgrade/nota/' . $id_transaksi);
    }

    public function nota($id_transaksi)
    {
        $transactionModel = new \App\Models\TransactionModel();
        $transaksi = $transactionModel
            ->select('transactions.*, users.nama, users.email')
            ->join('users', 'users.id = transactions.user_id')
            ->where('transactions.id', (int) $id_transaksi)
            ->first();

        if (!$transaksi) {
            return redirect()->to('/');
        }

        return view('v_nota', ['transaksi' => $transaksi]);
    }
}