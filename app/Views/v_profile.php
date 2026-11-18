<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & Riwayat - PWNED</title>
    <?= view('partials/favicon_head') ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        input::-ms-reveal, input::-ms-clear { display: none; }
    </style>
</head>
<body class="bg-[#111] text-black font-mono min-h-screen flex justify-center">

    <div class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] bg-repeat border-y-4 border-black flex flex-col relative min-h-screen">

        <!-- Navbar -->
        <nav class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white border-b-4 border-black z-50">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('/') ?>" class="text-xl font-pixel text-black">PWNED</a>
                <span class="text-[10px] font-bold uppercase text-gray-500">[PROFILE_PORTAL]</span>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 text-xs font-bold uppercase">
                <a href="<?= base_url('/') ?>" class="px-3 py-1.5 border-2 border-black bg-white hover:bg-black hover:text-white">&lt; HOME</a>
                <?php if (session()->get('role') === 'admin'): ?>
                    <a href="<?= base_url('admin') ?>" class="px-3 py-1.5 border-2 border-black bg-red-600 text-white hover:bg-red-800">[ADMIN]</a>
                <?php else: ?>
                    <a href="<?= base_url('upgrade') ?>" class="px-3 py-1.5 border-2 border-black bg-blue-600 text-white hover:bg-blue-800">UPGRADE / REDEEM</a>
                <?php endif; ?>
                <a href="<?= base_url('logout') ?>" class="px-3 py-1.5 border-2 border-black bg-black text-white hover:bg-red-600">LOGOUT</a>
            </div>
        </nav>

        <main class="flex-grow w-full max-w-6xl mx-auto px-4 sm:px-8 py-8 sm:py-10 space-y-10">

            <?php
                $planNow = $current_plan ?? 'free';
                $planBadge = match ($planNow) {
                    'pro'   => 'bg-red-600 text-white',
                    'plus'  => 'bg-blue-600 text-white',
                    default => 'bg-gray-300 text-black',
                };
                $quotaStr = ($planNow === 'pro') ? 'UNLIMITED' : (($usage_today ?? 0) . ' / ' . ($usage_limit ?? 5));
            ?>

            <!-- Header Akun & Statistik Singkat -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">&gt; AKUN AKTIF</span>
                    <div class="font-bold text-sm uppercase mt-2 truncate"><?= esc($user['nama'] ?? session()->get('nama')) ?></div>
                    <div class="text-xs text-blue-600 font-bold truncate mt-1"><?= esc($user['email'] ?? session()->get('email')) ?></div>
                    <div class="mt-2">
                        <?php if (!empty($user['email_verified_at'])): ?>
                            <span class="inline-block px-2 py-0.5 bg-green-600 text-white border border-black text-[9px] font-bold uppercase">[EMAIL TERVERIFIKASI]</span>
                        <?php else: ?>
                            <span class="inline-block px-2 py-0.5 bg-yellow-300 text-black border border-black text-[9px] font-bold uppercase">[BELUM VERIFIKASI OTP]</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">&gt; PAKET LANGGANAN</span>
                    <div class="mt-2">
                        <span class="<?= $planBadge ?> px-3 py-1 font-pixel text-xs border-2 border-black uppercase shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                            <?= strtoupper(esc($planNow)) ?>
                        </span>
                    </div>
                    <div class="text-[10px] font-bold text-gray-600 uppercase mt-3">
                        EXPIRE: <?= !empty($user['expire_date']) ? esc(date('d M Y', strtotime((string) $user['expire_date']))) : 'UNLIMITED' ?>
                    </div>
                </div>

                <div class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">&gt; KUOTA HARI INI</span>
                    <div class="font-pixel text-xl text-blue-600 mt-3"><?= esc($quotaStr) ?></div>
                    <div class="text-[10px] font-bold text-gray-500 uppercase mt-2">RESET SETIAP 00:00</div>
                </div>

                <div class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">&gt; TOTAL CEK EMAIL</span>
                    <div class="font-pixel text-xl text-black mt-3"><?= number_format((int) ($total_searches ?? 0)) ?></div>
                    <div class="text-[10px] font-bold text-gray-500 uppercase mt-2">RIWAYAT TERCATAT</div>
                </div>
            </div>

            <!-- Grid: Ganti Sandi + Status Request & Redeem -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

                <!-- Form Ganti Sandi -->
                <div class="bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)] space-y-5">
                    <div class="border-b-4 border-black pb-3">
                        <h2 class="font-pixel text-xs uppercase text-black">&gt; KEAMANAN &amp; GANTI SANDI</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase mt-1">Minimal 8 karakter dengan kombinasi huruf dan angka.</p>
                    </div>

                    <?php if (session()->getFlashdata('profile_pwd_success')): ?>
                        <div class="bg-green-600 border-2 border-black text-white px-4 py-3 text-xs font-bold uppercase">
                            [OK] <?= esc(session()->getFlashdata('profile_pwd_success')) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('profile_pwd_error')): ?>
                        <div class="bg-red-600 border-2 border-black text-white px-4 py-3 text-xs font-bold uppercase">
                            [!] <?= esc(session()->getFlashdata('profile_pwd_error')) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('profile/password') ?>" class="space-y-4">
                        <?= csrf_field() ?>

                        <div>
                            <label for="current_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; SANDI SAAT INI</label>
                            <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                                <input type="password" id="current_password" name="current_password" required placeholder="Masukkan sandi saat ini"
                                    class="w-full px-3 py-2.5 bg-transparent text-black focus:outline-none font-bold text-sm">
                                <?= view('partials/pwd_toggle_btn', ['target' => 'current_password', 'label' => 'Lihat sandi saat ini']) ?>
                            </div>
                        </div>

                        <div>
                            <label for="profile_new_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; SANDI BARU</label>
                            <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                                <input type="password" id="profile_new_password" name="new_password" required minlength="8" placeholder="Min. 8 karakter (huruf + angka)"
                                    class="w-full px-3 py-2.5 bg-transparent text-black focus:outline-none font-bold text-sm">
                                <?= view('partials/pwd_toggle_btn', ['target' => 'profile_new_password', 'label' => 'Lihat sandi baru']) ?>
                            </div>
                        </div>

                        <div>
                            <label for="profile_confirm_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; KONFIRMASI SANDI BARU</label>
                            <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                                <input type="password" id="profile_confirm_password" name="confirm_password" required minlength="8" placeholder="Ulangi sandi baru"
                                    class="w-full px-3 py-2.5 bg-transparent text-black focus:outline-none font-bold text-sm">
                                <?= view('partials/pwd_toggle_btn', ['target' => 'profile_confirm_password', 'label' => 'Lihat konfirmasi sandi baru']) ?>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-black hover:bg-blue-700 text-white font-pixel text-[10px] px-5 py-3.5 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                            SIMPAN SANDI BARU
                        </button>
                    </form>

                    <?php if (empty($user['email_verified_at']) && !empty($otp_mailer_active)): ?>
                        <div class="border-t-4 border-black pt-5 mt-5 space-y-4">
                            <h3 class="font-pixel text-[10px] uppercase text-black">&gt; VERIFIKASI EMAIL (OTP)</h3>

                            <?php if (session()->getFlashdata('profile_otp_success')): ?>
                                <div class="bg-green-600 border-2 border-black text-white px-3 py-2 text-xs font-bold uppercase">
                                    [OK] <?= esc(session()->getFlashdata('profile_otp_success')) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (session()->getFlashdata('profile_otp_error')): ?>
                                <div class="bg-red-600 border-2 border-black text-white px-3 py-2 text-xs font-bold uppercase">
                                    [!] <?= esc(session()->getFlashdata('profile_otp_error')) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= base_url('profile/send-otp') ?>">
                                <?= csrf_field() ?>
                                <?php $ocd = (int) ($otp_cooldown ?? 0); ?>
                                <button type="submit" id="btn-profile-send-otp" <?= $ocd > 0 ? 'disabled' : '' ?>
                                    class="w-full bg-blue-600 hover:bg-blue-800 disabled:opacity-50 text-white font-pixel text-[9px] px-4 py-3 border-2 border-black uppercase">
                                    <?= $ocd > 0 ? 'KIRIM ULANG OTP (' . $ocd . 'S)' : 'KIRIM KODE OTP VERIFIKASI' ?>
                                </button>
                            </form>

                            <form method="POST" action="<?= base_url('profile/verify-otp') ?>" class="flex gap-2">
                                <?= csrf_field() ?>
                                <input type="text" name="otp_code" required maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="6 DIGIT OTP"
                                    class="flex-1 px-3 py-2.5 bg-white border-2 border-black font-pixel text-xs text-center tracking-widest">
                                <button type="submit" class="bg-black hover:bg-green-700 text-white font-pixel text-[9px] px-4 py-2.5 border-2 border-black uppercase">
                                    VERIFIKASI
                                </button>
                            </form>
                        </div>
                    <?php elseif (!empty($user['email_verified_at'])): ?>
                        <div class="border-t-2 border-dashed border-gray-300 pt-4 text-[11px] font-bold text-green-700 uppercase">
                            [OK] EMAIL TERVERIFIKASI SEJAK <?= esc(date('d M Y H:i', strtotime((string) $user['email_verified_at']))) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Status Request Contact Admin & Riwayat Klaim Redeem -->
                <div class="space-y-6">
                    <div class="bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                        <h2 class="font-pixel text-xs uppercase text-black border-b-4 border-black pb-3 mb-4">&gt; STATUS PENGAJUAN ADMIN</h2>
                        <?php if (!empty($user_requests)): ?>
                            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                                <?php foreach ($user_requests as $req): ?>
                                    <?php
                                        $st = (string) ($req['status'] ?? 'pending');
                                        $stCls = match ($st) {
                                            'approved' => 'bg-green-600 text-white',
                                            'rejected' => 'bg-red-600 text-white',
                                            default    => 'bg-yellow-300 text-black',
                                        };
                                    ?>
                                    <div class="border-2 border-black p-3 bg-gray-50 flex justify-between items-start gap-3">
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold uppercase"><?= esc(str_replace('_', ' ', (string) $req['jenis_request'])) ?></div>
                                            <div class="text-[11px] text-gray-600 mt-1 break-words"><?= esc($req['pesan'] ?? '') ?></div>
                                            <div class="text-[10px] text-gray-400 mt-1"><?= !empty($req['created_at']) ? esc(date('d M Y H:i', strtotime((string) $req['created_at']))) : '' ?></div>
                                        </div>
                                        <span class="<?= $stCls ?> px-2 py-1 font-pixel text-[8px] border-2 border-black uppercase shrink-0"><?= strtoupper(esc($st)) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-xs font-bold text-gray-500 uppercase">[!] Belum ada pengajuan ke Admin.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                        <h2 class="font-pixel text-xs uppercase text-black border-b-4 border-black pb-3 mb-4">&gt; KODE REDEEM DIKLAIM</h2>
                        <?php if (!empty($redeem_claims)): ?>
                            <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                                <?php foreach ($redeem_claims as $cl): ?>
                                    <div class="border-2 border-black p-3 bg-gray-50 flex justify-between items-center gap-3 text-xs font-bold">
                                        <div>
                                            <span class="font-mono text-black"><?= esc($cl['code']) ?></span>
                                            <span class="ml-2 text-blue-600 uppercase"><?= strtoupper(esc($cl['plan'])) ?> (<?= (int) $cl['duration_days'] ?>d)</span>
                                        </div>
                                        <span class="text-[10px] text-gray-500"><?= esc(date('d M Y', strtotime((string) $cl['claimed_at']))) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-xs font-bold text-gray-500 uppercase">[!] Belum pernah mengklaim kode redeem.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Pengecekan Email Lengkap -->
            <div class="bg-white border-4 border-black shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                <div class="px-6 py-4 bg-gray-100 border-b-4 border-black flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <h2 class="font-pixel text-xs uppercase text-black">&gt; RIWAYAT PENGECEKAN EMAIL (50 TERBARU)</h2>
                        <p class="text-[10px] font-bold text-gray-500 uppercase mt-1">Klik CEK LAGI untuk memeriksa ulang kebocoran terbaru.</p>
                    </div>
                    <?php if (!empty($search_history)): ?>
                        <form method="POST" action="<?= base_url('profile/history/clear') ?>" onsubmit="return confirm('Hapus seluruh riwayat pengecekan Anda?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="bg-red-600 hover:bg-red-800 text-white font-pixel text-[9px] px-3 py-2 border-2 border-black uppercase">
                                BERSIHKAN RIWAYAT
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (session()->getFlashdata('history_success')): ?>
                    <div class="m-4 mb-0 bg-green-100 border-2 border-black text-green-900 px-4 py-2.5 text-xs font-bold uppercase">
                        [OK] <?= esc(session()->getFlashdata('history_success')) ?>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-black">
                        <thead class="bg-gray-200 border-b-4 border-black uppercase font-bold">
                            <tr>
                                <th class="px-4 py-3 border-r-4 border-black">No</th>
                                <th class="px-4 py-3 border-r-4 border-black">Email Dicek</th>
                                <th class="px-4 py-3 border-r-4 border-black">Waktu</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-4 divide-black font-bold">
                            <?php if (!empty($search_history)): ?>
                                <?php foreach ($search_history as $idx => $item): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 border-r-4 border-black font-pixel text-[10px]">#<?= $idx + 1 ?></td>
                                        <td class="px-4 py-3 border-r-4 border-black text-blue-700"><?= esc($item['email_checked']) ?></td>
                                        <td class="px-4 py-3 border-r-4 border-black font-mono text-[11px] text-gray-600">
                                            <?= !empty($item['created_at']) ? esc(date('d M Y H:i', strtotime((string) $item['created_at']))) : '-' ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <form method="POST" action="<?= base_url('cek-email') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="email" value="<?= esc($item['email_checked']) ?>">
                                                    <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-800 text-white border-2 border-black text-[9px] font-pixel uppercase">
                                                        CEK LAGI
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= base_url('profile/history/delete/' . (int) $item['id']) ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="px-2.5 py-1.5 bg-white hover:bg-red-600 hover:text-white border-2 border-black text-[9px] font-pixel uppercase">
                                                        HAPUS
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500 uppercase">[!] Belum ada riwayat pengecekan email.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <?= view('partials/pwd_toggle_script') ?>
    <script>
        (function () {
            const rem = <?= (int) ($otp_cooldown ?? 0) ?>;
            const btn = document.getElementById('btn-profile-send-otp');
            if (!btn || rem <= 0) return;
            const endAt = Date.now() + (rem * 1000);
            const tick = () => {
                const left = Math.max(0, Math.ceil((endAt - Date.now()) / 1000));
                if (left <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = 'KIRIM KODE OTP VERIFIKASI';
                } else {
                    btn.textContent = 'KIRIM ULANG OTP (' + left + 'S)';
                }
            };
            const timer = setInterval(tick, 500);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) tick();
            });
        })();
    </script>
</body>
</html>
