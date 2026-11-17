<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Upgrade - PWNED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-[#111] text-black font-mono min-h-screen pb-8 pt-0 flex justify-center">

    <div class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] bg-repeat border-y-4 border-black flex flex-col relative">

        <nav class="px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 bg-white border-b-4 border-black z-50">
            <div class="flex items-center space-x-2">
                <span class="text-xl font-pixel text-black">PWNED</span>
            </div>
            
            <div id="nav-container" class="flex justify-center space-x-6 text-sm font-bold uppercase">
                <a href="<?= base_url('/') ?>" class="nav-link text-black hover:bg-black hover:text-white px-2 py-1 transition-none">< BACK TO HOME</a>
            </div>
            
            <div class="flex justify-end items-center gap-3 font-bold uppercase text-sm">
                <button type="button" onclick="openContactModal()" class="bg-blue-600 hover:bg-blue-800 text-white px-3 py-1.5 text-[10px] font-pixel border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)] transition-none uppercase">
                    CONTACT ADMIN
                </button>
                <span class="bg-black text-white px-4 py-1 text-xs font-pixel tracking-widest shadow-[4px_4px_0px_rgba(100,100,100,1)] border-2 border-white outline outline-2 outline-black">
                    PREMIUM
                </span>
            </div>
        </nav>

        <?php if(session()->getFlashdata('limit_reached')): ?>
            <div class="m-6 mb-0 bg-red-600 border-4 border-black text-white px-6 py-4 flex items-center justify-between shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-pixel">!</span>
                    <p class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('limit_reached')) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if(session()->getFlashdata('upgrade_info')): ?>
            <div class="m-6 mb-0 bg-blue-600 border-4 border-black text-white px-6 py-4 flex items-center justify-between shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-pixel">i</span>
                    <p class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('upgrade_info')) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <main class="flex-grow w-full px-4 sm:px-8 py-8 sm:py-12 space-y-10 sm:space-y-12">

            <!-- Current License Status -->
            <div class="max-w-4xl mx-auto bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)] flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <?php if (!empty($is_logged_in)): ?>
                    <?php
                        $planNow = $current_plan ?? 'free';
                        $planBadgeClass = match ($planNow) {
                            'pro'   => 'bg-red-600 text-white',
                            'plus'  => 'bg-blue-600 text-white',
                            default => 'bg-gray-200 text-black',
                        };
                        $quotaText = ($planNow === 'pro') ? 'UNLIMITED' : (($usage_count ?? 0) . ' / ' . ($usage_limit ?? 5) . ' TODAY');
                    ?>
                    <div class="space-y-1">
                        <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">&gt; CURRENT_LICENSE_STATUS</div>
                        <div class="flex flex-wrap items-center gap-3 mt-1">
                            <span class="<?= $planBadgeClass ?> border-2 border-black px-3 py-1 font-pixel text-[10px] uppercase">
                                <?= esc(strtoupper($planNow)) ?> PLAN
                            </span>
                            <span class="text-xs font-bold uppercase text-black">
                                QUOTA: <span class="text-blue-600"><?= esc($quotaText) ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="text-left md:text-right">
                        <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">&gt; VALID_UNTIL</div>
                        <div class="font-pixel text-xs text-black mt-1">
                            <?= !empty($expire_date) ? esc(strtoupper(date('d M Y', strtotime($expire_date)))) : 'UNLIMITED (FREE TIER)' ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="space-y-1">
                        <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">&gt; GUEST_SESSION</div>
                        <p class="text-xs font-bold text-black uppercase">Masuk terlebih dahulu untuk melakukan upgrade dan mengaktifkan lisensi.</p>
                    </div>
                    <a href="<?= base_url('login') ?>" class="bg-black hover:bg-blue-700 text-white font-pixel text-[10px] px-5 py-3 border-2 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                        LOGIN NOW &gt;&gt;
                    </a>
                <?php endif; ?>
            </div>

            <!-- Manual Upgrade via Contact Admin Banner -->
            <div class="max-w-4xl mx-auto bg-yellow-100 border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)] flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="space-y-1">
                    <div class="text-[10px] font-pixel text-yellow-900 uppercase tracking-widest">[!] MANUAL UPGRADE MODE (GATEWAY OFFLINE)</div>
                    <p class="text-xs font-bold text-black uppercase leading-relaxed">
                        Pembayaran otomatis sedang dinonaktifkan sementara. Untuk upgrade ke paket <span class="text-blue-600">PLUS</span> atau <span class="text-red-600">PRO</span>, silakan ajukan langsung melalui tombol <b>CONTACT ADMIN</b>.
                    </p>
                </div>
                <button type="button" onclick="openContactModal()" class="flex-shrink-0 bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-5 py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase transition-none">
                    CONTACT ADMIN &gt;&gt;
                </button>
            </div>
            
            <div class="text-center space-y-6">
                <h1 class="text-2xl md:text-3xl font-pixel tracking-tighter leading-tight text-black">
                    UPGRADE SYSTEM ACCESS
                </h1>
                <p class="text-xs uppercase font-bold text-gray-500 tracking-widest max-w-xl mx-auto border-y-2 border-dashed border-gray-400 py-4">
                    Choose the license plan that suits your data monitoring needs. Cancellation can be done anytime via the admin terminal.
                </p>
                
                <div class="inline-flex items-center bg-gray-100 p-1 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] mt-4">
                    <button id="btn-bulanan" class="px-6 py-2 text-xs font-pixel bg-black text-white transition-none uppercase">1 MONTH</button>
                    <button id="btn-tahunan" class="px-6 py-2 text-xs font-pixel text-black hover:bg-gray-300 transition-none uppercase">1 YEAR</button>
                </div>
                <div class="text-[10px] text-green-600 font-bold tracking-widest mt-2 uppercase animate-pulse">
                    > SAVE 16% ON ANNUAL LICENSE <
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10 max-w-4xl mx-auto items-stretch">
                
                <div class="bg-white border-4 border-black p-5 sm:p-8 shadow-[8px_8px_0px_rgba(0,0,0,1)] relative flex flex-col justify-between hover:-translate-y-1 hover:shadow-[12px_12px_0px_rgba(0,0,0,1)] transition-transform">
                    <div class="absolute -top-4 -right-4 bg-blue-600 text-white text-[10px] font-pixel uppercase px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                        RECOMMENDED
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-b-4 border-dashed border-gray-400 pb-4">
                            <h3 class="text-xl font-pixel text-black uppercase">PWNED PLUS</h3>
                            <p class="text-[10px] text-gray-500 mt-2 font-bold uppercase tracking-widest">Standard monitoring access</p>
                        </div>
                        
                        <div class="py-2">
                            <span id="price-plus" class="text-3xl font-pixel text-blue-600">Rp25K</span>
                            <span class="text-gray-400 text-xs font-bold uppercase">/mo</span>
                            <div id="note-plus" class="text-[10px] text-gray-400 mt-2 font-bold uppercase hidden">> BILLED Rp252K/YR</div>
                        </div>

                        <div class="space-y-4 pt-2">
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block bg-gray-100 p-2 border-l-4 border-black">MODULES:</span>
                            <ul class="space-y-3 text-xs text-black font-bold uppercase">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600">[+]</span>
                                    <span>QUOTA 50 EMAIL CHECKS / DAY</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600">[+]</span>
                                    <span>AUTO ALERT SYSTEM (EMAIL)</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="pt-8">
                        <?php if (empty($is_logged_in)): ?>
                            <a href="<?= base_url('login') ?>" class="block text-center w-full bg-blue-600 hover:bg-blue-800 text-white py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] text-[10px] font-pixel uppercase transition-none">
                                LOGIN TO INSTALL PLUS
                            </a>
                        <?php elseif (($current_plan ?? 'free') === 'plus'): ?>
                            <button onclick="bukaModalPembayaran('plus')" class="block text-center w-full bg-green-600 hover:bg-green-700 text-white py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] text-[10px] font-pixel uppercase transition-none">
                                EXTEND PLUS LICENSE
                            </button>
                        <?php else: ?>
                            <button onclick="bukaModalPembayaran('plus')" class="block text-center w-full bg-blue-600 hover:bg-blue-800 text-white py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] text-[10px] font-pixel uppercase transition-none">
                                INSTALL PLUS
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-black text-white border-4 border-black p-5 sm:p-8 shadow-[8px_8px_0px_rgba(100,100,100,1)] relative flex flex-col justify-between hover:-translate-y-1 hover:shadow-[12px_12px_0px_rgba(100,100,100,1)] transition-transform">
                    
                    <div class="space-y-6">
                        <div class="border-b-4 border-dashed border-gray-600 pb-4">
                            <h3 class="text-xl font-pixel text-red-500 uppercase">PWNED PRO</h3>
                            <p class="text-[10px] text-gray-400 mt-2 font-bold uppercase tracking-widest">Unlimited intelligence access</p>
                        </div>
                        
                        <div class="py-2">
                            <span id="price-pro" class="text-3xl font-pixel text-red-500">Rp50K</span>
                            <span class="text-gray-500 text-xs font-bold uppercase">/mo</span>
                            <div id="note-pro" class="text-[10px] text-gray-500 mt-2 font-bold uppercase hidden">> BILLED Rp504K/YR</div>
                        </div>

                        <div class="space-y-4 pt-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block bg-[#222] p-2 border-l-4 border-red-500">MODULES:</span>
                            <ul class="space-y-3 text-xs text-gray-300 font-bold uppercase">
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500">[+]</span>
                                    <span>UNLIMITED SEARCH QUOTA</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500">[+]</span>
                                    <span>DEEP SEARCH DATA (PWD LOGS)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500">[+]</span>
                                    <span>PDF/CSV SECURITY AUDIT EXPORT</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="pt-8">
                        <?php if (empty($is_logged_in)): ?>
                            <a href="<?= base_url('login') ?>" class="block text-center w-full bg-red-600 hover:bg-red-800 text-white py-3 border-4 border-white shadow-[4px_4px_0px_rgba(255,255,255,1)] text-[10px] font-pixel uppercase transition-none">
                                LOGIN TO INSTALL PRO
                            </a>
                        <?php elseif (($current_plan ?? 'free') === 'pro'): ?>
                            <button onclick="bukaModalPembayaran('pro')" class="block text-center w-full bg-green-600 hover:bg-green-700 text-white py-3 border-4 border-white shadow-[4px_4px_0px_rgba(255,255,255,1)] text-[10px] font-pixel uppercase transition-none">
                                EXTEND PRO LICENSE
                            </button>
                        <?php else: ?>
                            <button onclick="bukaModalPembayaran('pro')" class="block text-center w-full bg-red-600 hover:bg-red-800 text-white py-3 border-4 border-white shadow-[4px_4px_0px_rgba(255,255,255,1)] text-[10px] font-pixel uppercase transition-none">
                                INSTALL PRO
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <?php if (!empty($is_logged_in) && !empty($transactions)): ?>
            <div class="max-w-4xl mx-auto bg-white border-4 border-black p-6 shadow-[8px_8px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center justify-between border-b-4 border-dashed border-gray-400 pb-4 mb-4">
                    <h2 class="font-pixel text-xs text-black uppercase">&gt; TRANSACTION_HISTORY</h2>
                    <span class="text-[10px] font-bold text-gray-500 uppercase">LAST 5 INVOICES</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-bold uppercase">
                        <thead class="bg-gray-100 border-2 border-black text-[10px] text-gray-600">
                            <tr>
                                <th class="px-3 py-2 border-r-2 border-black">REF</th>
                                <th class="px-3 py-2 border-r-2 border-black">PLAN</th>
                                <th class="px-3 py-2 border-r-2 border-black">AMOUNT</th>
                                <th class="px-3 py-2 border-r-2 border-black">STATUS</th>
                                <th class="px-3 py-2 text-right">ACTION</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-black border-2 border-black">
                            <?php foreach ($transactions as $trx): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r-2 border-black">#TRX-<?= str_pad((string) $trx['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td class="px-3 py-3 border-r-2 border-black">
                                    <?= esc(strtoupper($trx['paket'])) ?> (<?= ($trx['mode'] ?? 'bulanan') === 'tahunan' ? '1Y' : '1M' ?>)
                                </td>
                                <td class="px-3 py-3 border-r-2 border-black">Rp<?= number_format((int) $trx['harga'], 0, ',', '.') ?></td>
                                <td class="px-3 py-3 border-r-2 border-black">
                                    <?php if ($trx['status'] === 'paid' || $trx['status'] === 'settlement'): ?>
                                        <span class="bg-green-200 text-green-800 px-2 py-0.5 border border-green-800 text-[10px]">PAID</span>
                                    <?php elseif ($trx['status'] === 'pending'): ?>
                                        <span class="bg-yellow-200 text-yellow-800 px-2 py-0.5 border border-yellow-800 text-[10px]">PENDING</span>
                                    <?php else: ?>
                                        <span class="bg-red-200 text-red-800 px-2 py-0.5 border border-red-800 text-[10px]"><?= esc(strtoupper($trx['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <?php if ($trx['status'] === 'pending'): ?>
                                            <button type="button" onclick="openContactModal('upgrade_<?= esc($trx['paket'], 'js') ?>', 'Konfirmasi pembayaran #TRX-<?= str_pad((string) $trx['id'], 5, '0', STR_PAD_LEFT) ?> (Rp<?= number_format((int) $trx['harga'], 0, ',', '.') ?>)')" class="bg-blue-600 hover:bg-blue-800 text-white px-3 py-1 border-2 border-black text-[10px] uppercase">CONTACT ADMIN</button>
                                            <form action="<?= base_url('upgrade/nota/' . $trx['id'] . '/cancel') ?>" method="POST" class="inline" onsubmit="return confirm('Batalkan transaksi ini?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="bg-gray-200 hover:bg-red-600 hover:text-white text-black px-2 py-1 border-2 border-black text-[10px]">BATAL</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?= base_url('upgrade/nota/' . $trx['id']) ?>" class="bg-black hover:bg-blue-700 text-white px-3 py-1 border-2 border-black text-[10px]">NOTA</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
        </main>

        <footer class="border-t-4 border-black bg-white p-4 text-center text-xs font-bold uppercase text-gray-500">
            [ PWNED_SYSTEM_v1.0 ] &copy; 2026 | PRICING_MODULE
        </footer>
    </div>

    <script>
        const btnBulanan = document.getElementById('btn-bulanan');
        const btnTahunan = document.getElementById('btn-tahunan');
        const pricePlus = document.getElementById('price-plus');
        const pricePro = document.getElementById('price-pro');
        const notePlus = document.getElementById('note-plus');
        const notePro = document.getElementById('note-pro');

        const HARGA = {
            plus: { bulanan: 25000, tahunan: 252000 },
            pro:  { bulanan: 50000, tahunan: 504000 }
        };

        let modeAktif = 'bulanan';
        let metodeAktif = 'qris';

        btnBulanan.addEventListener('click', () => {
            modeAktif = 'bulanan';
            btnBulanan.className = "px-6 py-2 text-xs font-pixel bg-black text-white transition-none uppercase";
            btnTahunan.className = "px-6 py-2 text-xs font-pixel text-black hover:bg-gray-300 transition-none uppercase";
            pricePlus.textContent = "Rp25K";
            pricePro.textContent = "Rp50K";
            notePlus.classList.add('hidden');
            notePro.classList.add('hidden');
        });

        btnTahunan.addEventListener('click', () => {
            modeAktif = 'tahunan';
            btnTahunan.className = "px-6 py-2 text-xs font-pixel bg-black text-white transition-none uppercase";
            btnBulanan.className = "px-6 py-2 text-xs font-pixel text-black hover:bg-gray-300 transition-none uppercase";
            pricePlus.textContent = "Rp21K";
            pricePro.textContent = "Rp42K";
            notePlus.classList.remove('hidden');
            notePro.classList.remove('hidden');
        });
    </script>

    <div id="modal-pembayaran" class="fixed inset-0 z-50 hidden flex justify-center items-center bg-black bg-opacity-80 transition-opacity p-4">
        <div class="bg-white border-[6px] border-black w-full max-w-lg p-0 shadow-[12px_12px_0px_rgba(255,255,255,0.2)] relative flex flex-col max-h-[90vh] overflow-y-auto">
            <div class="bg-blue-600 border-b-[6px] border-black p-2 flex justify-between items-center text-white">
                <div class="font-pixel text-[10px] uppercase tracking-widest pl-2">PAYMENT_GATEWAY.EXE</div>
                <button type="button" onclick="tutupModal()" class="w-6 h-6 bg-red-500 border-2 border-black flex items-center justify-center font-bold hover:bg-red-700">X</button>
            </div>

            <div class="p-6">
                <div class="border-4 border-black p-4 mb-4 flex justify-between items-center bg-gray-100">
                    <div>
                        <span class="block text-[10px] text-gray-500 uppercase font-bold tracking-widest">> SELECTED_PLAN</span>
                        <span id="modal-paket-nama" class="font-pixel text-xs text-black mt-2 block">PLAN</span>
                        <span id="modal-durasi" class="text-[10px] text-gray-500 font-bold uppercase mt-1 block">DURATION: 1 MONTH</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-[10px] text-gray-500 uppercase font-bold tracking-widest">> TOTAL_AMOUNT</span>
                        <div id="modal-harga" class="text-xl font-pixel text-blue-600 mt-2 block">Rp0</div>
                    </div>
                </div>

                <div class="bg-yellow-100 border-4 border-black p-3 mb-4 text-[10px] font-bold uppercase text-yellow-900 leading-relaxed">
                    [!] Payment Gateway otomatis belum tersedia. Silakan lakukan pembayaran manual lalu hubungi Admin melalui tombol <b>CONTACT ADMIN</b> di bawah untuk aktivasi paket.
                </div>

                <div class="grid grid-cols-2 gap-0 border-4 border-black mb-4 bg-black">
                    <button type="button" onclick="pilihMetode('qris')" id="btn-qris" class="metode-btn py-3 bg-white text-black text-xs font-bold uppercase transition-none border-r-4 border-black">
                        QRIS
                    </button>
                    <button type="button" onclick="pilihMetode('bank')" id="btn-bank" class="metode-btn py-3 bg-gray-400 hover:bg-gray-200 text-black text-xs font-bold uppercase transition-none">
                        BANK TFR
                    </button>
                </div>

                <div class="min-h-[150px] border-4 border-dashed border-gray-400 p-4 bg-gray-50 flex flex-col justify-center">
                    <div id="konten-qris" class="konten-pembayaran flex flex-col items-center justify-center">
                        <div class="w-32 h-32 bg-white flex items-center justify-center border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] mb-2">
                            <img src="<?= base_url('image/qr.png') ?>" alt="QRIS Payment" class="w-full h-full mx-auto object-contain">
                        </div>
                        <p class="text-[10px] text-center text-gray-600 font-bold uppercase tracking-widest">SCAN QRIS • KONFIRMASI VIA CONTACT ADMIN</p>
                    </div>

                    <div id="konten-bank" class="konten-pembayaran hidden flex flex-col justify-center">
                        <p class="text-[10px] font-bold text-gray-600 mb-3 text-center uppercase tracking-widest">TRANSFER TO VIRTUAL ACCOUNT</p>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center bg-white p-2.5 border-2 border-black">
                                <span class="font-bold text-black uppercase text-xs">BCA VA</span>
                                <span class="font-mono text-xs text-blue-600 font-bold">8077 0812 3456</span>
                            </div>
                            <div class="flex justify-between items-center bg-white p-2.5 border-2 border-black">
                                <span class="font-bold text-black uppercase text-xs">BNI VA</span>
                                <span class="font-mono text-xs text-blue-600 font-bold">829 0812 3456 78</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <button type="button" disabled class="w-full bg-gray-300 border-4 border-gray-500 text-gray-500 text-center font-pixel text-[9px] py-3 uppercase cursor-not-allowed">
                        [ PEMBAYARAN OTOMATIS NONAKTIF ]
                    </button>
                    <div class="flex gap-3">
                        <button type="button" onclick="tutupModal()" class="w-1/3 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[10px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                            CANCEL
                        </button>
                        <button type="button" onclick="bukaContactDariModal()" class="w-2/3 bg-blue-600 hover:bg-blue-800 border-4 border-black text-white text-center font-pixel text-[10px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] flex justify-center items-center gap-2">
                            <span>CONTACT ADMIN</span>
                            <span>&gt;&gt;</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Contact Admin -->
    <div id="contactModal" class="fixed inset-0 z-[999] hidden items-center justify-center p-4" style="background:rgba(0,0,0,0.85)">
        <div class="bg-white border-[6px] border-black w-full max-w-lg shadow-[16px_16px_0px_rgba(0,0,0,0.5)] flex flex-col" style="max-height:90vh;overflow-y:auto">
            <div class="bg-blue-600 border-b-[6px] border-black px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex gap-1">
                        <div class="w-3 h-3 bg-red-500 border-2 border-black"></div>
                        <div class="w-3 h-3 bg-yellow-400 border-2 border-black"></div>
                        <div class="w-3 h-3 bg-green-500 border-2 border-black"></div>
                    </div>
                    <span class="font-pixel text-[10px] text-white uppercase tracking-widest">CONTACT_ADMIN.EXE</span>
                </div>
                <button type="button" onclick="closeContactModal()" class="w-7 h-7 bg-red-500 border-2 border-black text-white font-bold hover:bg-red-700 flex items-center justify-center text-sm">✕</button>
            </div>

            <div id="contactFormBody" class="p-6 space-y-5 bg-gray-50">
                <div class="border-l-4 border-blue-600 pl-3 mb-2">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">&gt; Ajukan upgrade paket atau kirim pesan ke Admin. Admin akan memverifikasi dan mengaktifkan paket Anda.</p>
                </div>

                <div id="contactErrorBox" class="hidden bg-red-100 border-2 border-red-600 text-red-700 px-3 py-2 text-xs font-bold uppercase"></div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Nama</label>
                    <input type="text" id="contact_nama" placeholder="Nama kamu..."
                        value="<?= esc(session()->get('nama') ?? '') ?>"
                        class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Email Akun</label>
                    <input type="email" id="contact_email" placeholder="email@kamu.com"
                        value="<?= esc(session()->get('email') ?? '') ?>"
                        class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Jenis Permintaan</label>
                    <div class="relative border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white">
                        <select id="contact_jenis" class="w-full px-4 py-3 bg-transparent outline-none font-bold text-black uppercase cursor-pointer appearance-none text-sm">
                            <option value="upgrade_plus">Upgrade ke PLUS</option>
                            <option value="upgrade_pro">Upgrade ke PRO</option>
                            <option value="downgrade_free">Downgrade ke FREE</option>
                            <option value="report">Laporkan Masalah</option>
                            <option value="other">Lainnya</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-black border-l-4 border-black">▼</div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Pesan / Konfirmasi</label>
                    <textarea id="contact_pesan" rows="4" placeholder="Tuliskan paket, durasi (1 Bulan / 1 Tahun), atau catatan pembayaran..."
                        class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm resize-none"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeContactModal()" class="w-1/3 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[9px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                        BATAL
                    </button>
                    <button type="button" id="contactSubmitBtn" onclick="submitContact()" class="w-2/3 bg-black hover:bg-blue-700 border-4 border-black text-white font-pixel text-[9px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                        KIRIM &gt;&gt; ADMIN
                    </button>
                </div>
            </div>

            <div id="contactSuccessBody" class="hidden p-8 text-center space-y-5 bg-gray-50">
                <div class="inline-block bg-green-600 text-white font-pixel text-xs px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    [OK] TRANSMITTED
                </div>
                <p id="contactSuccessMsg" class="text-xs font-bold uppercase text-black leading-relaxed">
                    Permintaan upgrade berhasil dikirim ke Admin.
                </p>
                <button type="button" onclick="closeContactModal()" class="bg-black hover:bg-blue-700 text-white font-pixel text-[9px] px-6 py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                    TUTUP
                </button>
            </div>
        </div>
    </div>

    <script>
        let paketTerpilih = 'plus';
        let csrfName = '<?= esc(csrf_token()) ?>';
        let csrfHash = '<?= esc(csrf_hash()) ?>';

        function bukaModalPembayaran(paket) {
            paketTerpilih = paket;
            const totalBayar = HARGA[paket][modeAktif];

            document.getElementById('modal-paket-nama').innerText =
                paket.toUpperCase() + '_' + (modeAktif === 'tahunan' ? '1Y' : '1M');
            document.getElementById('modal-durasi').innerText =
                'DURATION: ' + (modeAktif === 'tahunan' ? '1 YEAR (12 MONTHS)' : '1 MONTH');

            let rupiah = totalBayar.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });
            document.getElementById('modal-harga').innerText = rupiah.replace(',00', '');

            pilihMetode('qris');
            document.getElementById('modal-pembayaran').classList.remove('hidden');
        }

        function tutupModal() {
            document.getElementById('modal-pembayaran').classList.add('hidden');
        }

        function pilihMetode(metode) {
            metodeAktif = metode;

            let btns = document.querySelectorAll('.metode-btn');
            btns.forEach(btn => {
                btn.className = "metode-btn py-3 bg-gray-400 hover:bg-gray-200 text-black text-xs font-bold uppercase transition-none border-r-4 border-black";
            });
            document.getElementById('btn-bank').classList.remove('border-r-4');
            document.getElementById('btn-bank').classList.remove('border-black');

            let btnAktif = document.getElementById('btn-' + metode);
            btnAktif.className = "metode-btn py-3 bg-white text-black text-xs font-bold uppercase transition-none border-r-4 border-black";
            if (metode === 'bank') {
                btnAktif.className = "metode-btn py-3 bg-white text-black text-xs font-bold uppercase transition-none";
            }

            let kontens = document.querySelectorAll('.konten-pembayaran');
            kontens.forEach(konten => {
                konten.classList.add('hidden');
            });

            document.getElementById('konten-' + metode).classList.remove('hidden');
        }

        function bukaContactDariModal() {
            const totalBayar = HARGA[paketTerpilih][modeAktif];
            const durasiLabel = modeAktif === 'tahunan' ? '1 Tahun' : '1 Bulan';
            const hargaLabel = 'Rp' + totalBayar.toLocaleString('id-ID');
            tutupModal();
            openContactModal(
                'upgrade_' + paketTerpilih,
                'Halo Admin, saya ingin mengajukan upgrade ke paket ' + paketTerpilih.toUpperCase() + ' (' + durasiLabel + ' - ' + hargaLabel + ') via ' + metodeAktif.toUpperCase() + '.'
            );
        }

        function openContactModal(defaultJenis = 'upgrade_plus', defaultPesan = '') {
            document.getElementById('contactModal').classList.remove('hidden');
            document.getElementById('contactModal').classList.add('flex');
            document.getElementById('contactFormBody').classList.remove('hidden');
            document.getElementById('contactSuccessBody').classList.add('hidden');
            document.getElementById('contactErrorBox').classList.add('hidden');

            const btn = document.getElementById('contactSubmitBtn');
            btn.textContent = 'KIRIM >> ADMIN';
            btn.disabled = false;

            if (defaultJenis) {
                document.getElementById('contact_jenis').value = defaultJenis;
            }
            document.getElementById('contact_pesan').value = defaultPesan;
            document.body.style.overflow = 'hidden';
        }

        function closeContactModal() {
            document.getElementById('contactModal').classList.add('hidden');
            document.getElementById('contactModal').classList.remove('flex');
            document.body.style.overflow = '';
        }

        async function submitContact() {
            const nama   = document.getElementById('contact_nama').value.trim();
            const email  = document.getElementById('contact_email').value.trim();
            const jenis  = document.getElementById('contact_jenis').value;
            const pesan  = document.getElementById('contact_pesan').value.trim();
            const errBox = document.getElementById('contactErrorBox');
            const btn    = document.getElementById('contactSubmitBtn');

            errBox.classList.add('hidden');
            if (!nama || !email) {
                errBox.textContent = 'Nama dan Email wajib diisi.';
                errBox.classList.remove('hidden');
                return;
            }

            btn.textContent = 'MENGIRIM...';
            btn.disabled = true;

            try {
                const body = new URLSearchParams();
                body.append(csrfName, csrfHash);
                body.append('nama', nama);
                body.append('email', email);
                body.append('jenis', jenis);
                body.append('pesan', pesan);

                const res = await fetch('<?= base_url('kirim-request') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body.toString()
                });
                const data = await res.json();
                if (data.csrf_token) csrfHash = data.csrf_token;

                if (!res.ok || data.status !== 'ok') {
                    errBox.textContent = data.message || 'Gagal mengirim permintaan.';
                    errBox.classList.remove('hidden');
                    btn.textContent = 'KIRIM >> ADMIN';
                    btn.disabled = false;
                    return;
                }

                document.getElementById('contactSuccessMsg').textContent = data.message || 'Permintaan Anda berhasil dikirim ke Admin.';
                document.getElementById('contactFormBody').classList.add('hidden');
                document.getElementById('contactSuccessBody').classList.remove('hidden');
            } catch (e) {
                errBox.textContent = 'Terjadi gangguan koneksi. Coba lagi.';
                errBox.classList.remove('hidden');
                btn.textContent = 'KIRIM >> ADMIN';
                btn.disabled = false;
            }
        }

        document.getElementById('contactModal').addEventListener('click', function(e) {
            if (e.target === this) closeContactModal();
        });

        if (new URLSearchParams(window.location.search).get('contact') === '1') {
            openContactModal();
        }
    </script>
</body>
</html>