<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - PWNED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] text-black font-mono min-h-screen flex flex-col justify-center items-center px-4">

    <div class="mb-8 text-center bg-white border-4 border-black p-4 shadow-[6px_6px_0px_rgba(255,255,255,1)] inline-block">
        <a href="<?= base_url('/') ?>" class="text-2xl font-pixel tracking-widest text-black">
            PWNED
        </a>
        <p class="text-[10px] text-gray-500 mt-2 uppercase font-bold tracking-widest">[ LOGIN_PORTAL ]</p>
    </div>

    <div class="bg-white p-8 border-4 border-black w-full max-w-md shadow-[8px_8px_0px_rgba(255,255,255,1)]">
        
        <?php if(session()->getFlashdata('error')): ?>
            <div class="bg-red-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] mb-6 text-sm text-center font-bold uppercase animate-pulse">
                [!] <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('auth/process') ?>" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <div>
                <label for="email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> ALAMAT EMAIL</label>
                <input type="email" id="email" name="email" required placeholder="contoh@gmail.com"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <label for="password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> KATA SANDI</label>
                <input type="password" id="password" name="password" required placeholder="********"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none flex justify-center items-center gap-2 mt-4">
                MASUK
            </button>
        </form>
        <div class="mt-8 text-center text-xs text-gray-500 border-t-2 border-dashed border-gray-400 pt-6 space-y-4 font-bold uppercase">
            <div>
                Belum punya akun? <br>
                <a href="<?= base_url('register') ?>" class="inline-block mt-2 text-blue-600 hover:bg-blue-600 hover:text-white px-2 py-1">Daftar sekarang</a>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="inline-block hover:bg-gray-200 text-gray-600 px-2 py-1">< KEMBALI KE HOME</a>
            </div>
        </div>

    </div>

</body>
</html>