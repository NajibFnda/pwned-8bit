<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Premium - PWNED</title>
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
                <a href="<?= base_url('/') ?>" class="nav-link text-black hover:bg-black hover:text-white px-2 py-1 transition-none">< KEMBALI KE HOME</a>
            </div>
            
            <div class="flex justify-end items-center gap-4 font-bold uppercase text-sm">
                <span class="bg-black text-white px-4 py-1 text-xs font-pixel tracking-widest shadow-[4px_4px_0px_rgba(100,100,100,1)] border-2 border-white outline outline-2 outline-black">
                    PREMIUM
                </span>
            </div>
        </nav>

        <?php if(session()->getFlashdata('limit_reached')): ?>
            <div class="m-6 bg-red-600 border-4 border-black text-white px-6 py-4 flex items-center justify-between shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-pixel">!</span>
                    <p class="font-bold text-sm uppercase"><?= session()->getFlashdata('limit_reached') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <main class="flex-grow w-full px-8 py-12 space-y-16">
            
            <div class="text-center space-y-6">
                <h1 class="text-2xl md:text-3xl font-pixel tracking-tighter leading-tight text-black">
                    UPGRADE SYSTEM ACCESS
                </h1>
                <p class="text-xs uppercase font-bold text-gray-500 tracking-widest max-w-xl mx-auto border-y-2 border-dashed border-gray-400 py-4">
                    Pilih paket lisensi yang sesuai dengan kebutuhan pengawasan data Anda. Pembatalan dapat dilakukan kapan saja melalui terminal admin.
                </p>
                
                <div class="inline-flex items-center bg-gray-100 p-1 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] mt-4">
                    <button id="btn-bulanan" class="px-6 py-2 text-xs font-pixel bg-black text-white transition-none uppercase">1 BULAN</button>
                    <button id="btn-tahunan" class="px-6 py-2 text-xs font-pixel text-black hover:bg-gray-300 transition-none uppercase">1 TAHUN</button>
                </div>
                <div class="text-[10px] text-green-600 font-bold tracking-widest mt-2 uppercase animate-pulse">
                    > HEMAT 16% UNTUK LISENSI TAHUNAN <
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-w-4xl mx-auto items-stretch">
                
                <div class="bg-white border-4 border-black p-8 shadow-[8px_8px_0px_rgba(0,0,0,1)] relative flex flex-col justify-between hover:-translate-y-1 hover:shadow-[12px_12px_0px_rgba(0,0,0,1)] transition-transform">
                    <div class="absolute -top-4 -right-4 bg-blue-600 text-white text-[10px] font-pixel uppercase px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                        RECOMMENDED
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-b-4 border-dashed border-gray-400 pb-4">
                            <h3 class="text-xl font-pixel text-black uppercase">PWNED PLUS</h3>
                            <p class="text-[10px] text-gray-500 mt-2 font-bold uppercase tracking-widest">Akses standar pengawasan</p>
                        </div>
                        
                        <div class="py-2">
                            <span id="price-plus" class="text-3xl font-pixel text-blue-600">Rp25K</span>
                            <span class="text-gray-400 text-xs font-bold uppercase">/bln</span>
                            <div id="note-plus" class="text-[10px] text-gray-400 mt-2 font-bold uppercase hidden">> DITAGIH Rp252K/THN</div>
                        </div>

                        <div class="space-y-4 pt-2">
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block bg-gray-100 p-2 border-l-4 border-black">MODULES:</span>
                            <ul class="space-y-3 text-xs text-black font-bold uppercase">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600">[+]</span>
                                    <span>KUOTA 50 CEK EMAIL / HARI</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600">[+]</span>
                                    <span>AUTO ALERT SYSTEM (EMAIL)</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="pt-8">
                        <?php if(session()->get('subscription_plan') === 'plus'): ?>
                            <button disabled class="block text-center w-full bg-gray-200 text-gray-500 py-3 border-4 border-gray-400 text-xs font-bold uppercase cursor-not-allowed">
                                LISENSI AKTIF
                            </button>
                        <?php else: ?>
                            <button onclick="bukaModalPembayaran('plus')" class="block text-center w-full bg-blue-600 hover:bg-blue-800 text-white py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] text-[10px] font-pixel uppercase transition-none">
                                INSTALL PLUS
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-black text-white border-4 border-black p-8 shadow-[8px_8px_0px_rgba(100,100,100,1)] relative flex flex-col justify-between hover:-translate-y-1 hover:shadow-[12px_12px_0px_rgba(100,100,100,1)] transition-transform">
                    
                    <div class="space-y-6">
                        <div class="border-b-4 border-dashed border-gray-600 pb-4">
                            <h3 class="text-xl font-pixel text-red-500 uppercase">PWNED PRO</h3>
                            <p class="text-[10px] text-gray-400 mt-2 font-bold uppercase tracking-widest">Akses intelijen tak terbatas</p>
                        </div>
                        
                        <div class="py-2">
                            <span id="price-pro" class="text-3xl font-pixel text-red-500">Rp50K</span>
                            <span class="text-gray-500 text-xs font-bold uppercase">/bln</span>
                            <div id="note-pro" class="text-[10px] text-gray-500 mt-2 font-bold uppercase hidden">> DITAGIH Rp500K/THN</div>
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
                        <?php if(session()->get('subscription_plan') === 'pro'): ?>
                            <button disabled class="block text-center w-full bg-[#333] text-gray-500 py-3 border-4 border-gray-600 text-xs font-bold uppercase cursor-not-allowed">
                                LISENSI AKTIF
                            </button>
                        <?php else: ?>
                            <button onclick="bukaModalPembayaran('pro')" class="block text-center w-full bg-red-600 hover:bg-red-800 text-white py-3 border-4 border-white shadow-[4px_4px_0px_rgba(255,255,255,1)] text-[10px] font-pixel uppercase transition-none">
                                INSTALL PRO
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
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
            plus: { bulanan: 25000, tahunan: 21000 },
            pro:  { bulanan: 50000, tahunan: 42000 }
        };

        let modeAktif = 'bulanan';

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
        <div class="bg-white border-[6px] border-black w-full max-w-lg p-0 shadow-[12px_12px_0px_rgba(255,255,255,0.2)] relative flex flex-col">
            <div class="bg-blue-600 border-b-[6px] border-black p-2 flex justify-between items-center text-white">
                <div class="font-pixel text-[10px] uppercase tracking-widest pl-2">PAYMENT_GATEWAY.EXE</div>
                <button onclick="tutupModal()" class="w-6 h-6 bg-red-500 border-2 border-black flex items-center justify-center font-bold hover:bg-red-700">X</button>
            </div>

            <div class="p-6">
                <div class="border-4 border-black p-4 mb-6 flex justify-between items-center bg-gray-100">
                    <div>
                        <span class="block text-[10px] text-gray-500 uppercase font-bold tracking-widest">> INVOICE_ID</span>
                        <span id="modal-paket-nama" class="font-pixel text-xs text-black mt-2 block">PAKET</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-[10px] text-gray-500 uppercase font-bold tracking-widest">> AMOUNT</span>
                        <div id="modal-harga" class="text-xl font-pixel text-blue-600 mt-2 block">Rp0</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-0 border-4 border-black mb-6 bg-black">
                    <button onclick="pilihMetode('qris')" id="btn-qris" class="metode-btn py-3 bg-white text-black text-xs font-bold uppercase transition-none border-r-4 border-black">
                        QRIS
                    </button>
                    <button onclick="pilihMetode('bank')" id="btn-bank" class="metode-btn py-3 bg-gray-400 hover:bg-gray-200 text-black text-xs font-bold uppercase transition-none">
                        BANK TRF
                    </button>
                </div>

                <div class="min-h-[160px] border-4 border-dashed border-gray-400 p-4 bg-gray-50 flex flex-col justify-center">
                    
                    <div id="konten-qris" class="konten-pembayaran flex flex-col items-center justify-center">
                        <div class="w-40 h-40 bg-white flex items-center justify-center border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] mb-4">
                            <img src="<?= base_url('image/qr.png') ?>" alt="Hacker" class="w-full h-full mx-auto object-contain">
                        </div>
                        <p class="text-[10px] text-center text-gray-600 font-bold uppercase tracking-widest">SCAN QR CODE TO PAY</p>
                    </div>


                    <div id="konten-bank" class="konten-pembayaran hidden flex flex-col justify-center">
                        <p class="text-[10px] font-bold text-gray-600 mb-4 text-center uppercase tracking-widest">TRANSFER TO VIRTUAL ACCOUNT</p>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center bg-white p-3 border-2 border-black">
                                <span class="font-bold text-black uppercase text-xs">BCA</span>
                                <span class="font-mono text-xs text-blue-600 font-bold">8077 0812 3456</span>
                            </div>
                            <div class="flex justify-between items-center bg-white p-3 border-2 border-black">
                                <span class="font-bold text-black uppercase text-xs">BNI</span>
                                <span class="font-mono text-xs text-blue-600 font-bold">829 0812 3456 78</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 mt-8">
                    <button onclick="tutupModal()" class="w-1/3 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[10px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                        CANCEL
                    </button>
                    <a href="#" id="btn-konfirmasi" class="w-2/3 bg-blue-600 hover:bg-blue-800 border-4 border-black text-white text-center font-pixel text-[10px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] flex justify-center items-center gap-2">
                        <span>CONFIRM</span>
                        <span>>></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bukaModalPembayaran(paket) {
            const harga = HARGA[paket][modeAktif];
            const totalTahunan = harga * 12;

            document.getElementById('modal-paket-nama').innerText =
                paket.toUpperCase() + '_' + (modeAktif === 'tahunan' ? '1Y' : '1M');

            const nilaiDitampilkan = modeAktif === 'tahunan' ? totalTahunan : harga;
            let rupiah = nilaiDitampilkan.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });
            document.getElementById('modal-harga').innerText = rupiah.replace(',00', '');

            document.getElementById('btn-konfirmasi').href =
                "<?= base_url('upgrade/proses/') ?>" + paket + "?mode=" + modeAktif;

            pilihMetode('qris');
            document.getElementById('modal-pembayaran').classList.remove('hidden');
        }

        function tutupModal() {
            document.getElementById('modal-pembayaran').classList.add('hidden');
        }

        function pilihMetode(metode) {
            let btns = document.querySelectorAll('.metode-btn');
            btns.forEach(btn => {
                btn.className = "metode-btn py-3 bg-gray-400 hover:bg-gray-200 text-black text-xs font-bold uppercase transition-none border-r-4 border-black";
            });
            document.getElementById('btn-bank').classList.remove('border-r-4');
            document.getElementById('btn-bank').classList.remove('border-black');

            let btnAktif = document.getElementById('btn-' + metode);
            btnAktif.className = "metode-btn py-3 bg-white text-black text-xs font-bold uppercase transition-none border-r-4 border-black";
            if(metode === 'bank') {
                 btnAktif.className = "metode-btn py-3 bg-white text-black text-xs font-bold uppercase transition-none";
            }

            let kontens = document.querySelectorAll('.konten-pembayaran');
            kontens.forEach(konten => {
                konten.classList.add('hidden');
            });

            document.getElementById('konten-' + metode).classList.remove('hidden');
        }
    </script>
</body>
</html>