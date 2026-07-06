<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Pembayaran - PWNED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        #area-struk {
            background-image: repeating-linear-gradient(0deg, transparent, transparent 19px, #e5e7eb 19px, #e5e7eb 20px);
            background-size: 100% 20px;
        }
    </style>
</head>
<body class="bg-slate-300 text-black font-mono flex flex-col items-center justify-center min-h-screen p-4 py-12">

    <div class="mb-8 text-center bg-white border-4 border-black p-4 shadow-[8px_8px_0px_rgba(0,0,0,1)] relative">
        <a href="<?= base_url('/') ?>" class="text-2xl font-pixel tracking-widest text-black">
            PWNED
        </a>
        <p class="text-[10px] text-gray-500 mt-2 uppercase font-bold tracking-widest">[ TRANSACTION_RECEIPT ]</p>
    </div>

    <div class="max-w-md w-full space-y-8">
        <div id="area-struk" class="bg-white p-8 border-4 border-black shadow-[8px_8px_0px_rgba(0,0,0,1)] relative">
            <div class="absolute -left-3 top-4 bottom-4 flex flex-col justify-between">
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
            </div>
            <div class="absolute -right-3 top-4 bottom-4 flex flex-col justify-between">
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
                <div class="w-4 h-4 bg-slate-300 rounded-full border-2 border-black"></div>
            </div>

            <div class="text-center mb-6 border-b-4 border-dashed border-black pb-6">
                <h1 class="text-xl font-pixel text-black tracking-widest mb-2">PWNED_HQ</h1>
                <p class="text-xs text-black font-bold uppercase tracking-widest">PAYMENT RECEIPT</p>
            </div>
            
            <div class="space-y-4 text-xs font-bold text-black uppercase tracking-widest mb-6 border-b-4 border-dashed border-black pb-6">
                <div class="flex justify-between">
                    <span>REF_NO:</span>
                    <span>#TRX-<?= str_pad($transaksi['id'], 5, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>DATE:</span>
                    <span><?= date('d M Y, H:i', strtotime($transaksi['tanggal'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>CLIENT:</span>
                    <span><?= esc($transaksi['nama']) ?></span>
                </div>
            </div>

            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center bg-gray-200 p-4 border-2 border-black">
                    <span class="font-bold text-black uppercase tracking-widest text-xs">> PKT_<?= esc($transaksi['paket']) ?></span>
                    <span class="font-pixel text-black text-sm">Rp<?= number_format($transaksi['harga'], 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="text-center text-xs font-bold text-black mt-8 uppercase tracking-widest space-y-2">
                <p>STATUS: <span class="bg-black text-white px-2 py-1">PAID / LUNAS</span></p>
                <div class="border-4 border-yellow-400 bg-yellow-50 px-4 py-3 mt-4 text-left shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    <p class="text-[9px] font-pixel text-yellow-800 uppercase tracking-widest leading-relaxed">
                        [!] YOUR ACCOUNT WILL BE UPDATED SHORTLY
                    </p>
                    <p class="text-[9px] text-yellow-700 font-bold mt-2 normal-case tracking-normal">
                        Paket baru Anda akan diaktifkan setelah dikonfirmasi oleh admin. Proses ini biasanya memakan waktu kurang dari 24 jam.
                    </p>
                </div>
                <p class="pt-4 text-[10px]">THANK YOU FOR YOUR PURCHASE.</p>
                <p class="text-[10px]">END OF TRANSMISSION.</p>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <a href="<?= base_url('/') ?>" class="w-1/3 bg-gray-400 hover:bg-gray-500 border-4 border-black text-black text-center font-pixel text-[10px] py-4 uppercase shadow-[8px_8px_0px_rgba(0,0,0,1)] transition-none">
                < DASHBOARD
            </a>
            <button onclick="downloadPDF()" class="w-2/3 bg-blue-600 hover:bg-blue-800 border-4 border-black text-white text-center font-pixel text-[10px] py-4 uppercase shadow-[8px_8px_0px_rgba(0,0,0,1)] transition-none flex justify-center items-center gap-2">
                <span>DOWNLOAD PDF</span>
            </button>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const elemen = document.getElementById('area-struk');
            const opsi = {
                margin:       [0.5, 0.5, 0.5, 0.5],
                filename:     'Nota_PWNED_TRX<?= $transaksi['id'] ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opsi).from(elemen).save();
        }
    </script>
</body>
</html>