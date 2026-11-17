<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PWNED</title>
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
        
        <?php if(session()->getFlashdata('success')): ?>
            <div class="bg-green-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] mb-6 text-sm text-center font-bold uppercase">
                [OK] <?= esc(session()->getFlashdata('success')) ?>
            </div>
        <?php endif; ?>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="bg-red-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] mb-6 text-sm text-center font-bold uppercase animate-pulse">
                [!] <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>
        
        <form id="form-login" action="<?= base_url('auth/process') ?>" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <div>
                <label for="email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> EMAIL ADDRESS</label>
                <input type="email" id="email" name="email" required placeholder="contoh@gmail.com"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <label for="password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest">> PASSWORD</label>
                    <button type="button" onclick="toggleMode('change')" class="text-[10px] font-bold text-blue-600 hover:underline uppercase">
                        Ganti Sandi?
                    </button>
                </div>
                <input type="password" id="password" name="password" required placeholder="********"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none flex justify-center items-center gap-2 mt-4">
                LOGIN
            </button>
        </form>

        <form id="form-change-pwd" action="<?= base_url('auth/changePassword') ?>" method="POST" class="space-y-5 hidden">
            <?= csrf_field() ?>

            <div class="border-b-2 border-dashed border-gray-400 pb-3 flex justify-between items-center">
                <span class="font-pixel text-[10px] text-black uppercase">&gt; GANTI KATA SANDI</span>
                <button type="button" onclick="toggleMode('login')" class="text-[10px] font-bold text-red-600 hover:underline uppercase">
                    &lt; BATAL
                </button>
            </div>

            <div>
                <label for="cp_email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> EMAIL ADDRESS</label>
                <input type="email" id="cp_email" name="email" required value="<?= esc(old('email')) ?>" placeholder="contoh@gmail.com"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <label for="old_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> SANDI LAMA</label>
                <input type="password" id="old_password" name="old_password" required placeholder="Masukkan sandi saat ini"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <label for="new_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> SANDI BARU</label>
                <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Min. 6 karakter"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <label for="confirm_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> KONFIRMASI SANDI BARU</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Ulangi sandi baru"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <button type="submit"
                class="w-full bg-black hover:bg-blue-700 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none flex justify-center items-center gap-2 mt-4">
                SIMPAN SANDI BARU
            </button>
        </form>

        <div class="mt-8 text-center text-xs text-gray-500 border-t-2 border-dashed border-gray-400 pt-6 space-y-4 font-bold uppercase">
            <div class="flex justify-center gap-4">
                <button type="button" id="btn-switch-mode" onclick="toggleMode('change')" class="text-black hover:bg-black hover:text-white px-2 py-1 border border-black text-[10px]">
                    GANTI SANDI
                </button>
                <a href="<?= base_url('register') ?>" class="text-blue-600 hover:bg-blue-600 hover:text-white px-2 py-1 border border-blue-600 text-[10px]">REGISTER NOW</a>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="inline-block hover:bg-gray-200 text-gray-600 px-2 py-1">&lt; BACK TO HOME</a>
            </div>
        </div>

    </div>

    <script>
        function toggleMode(mode) {
            const formLogin  = document.getElementById('form-login');
            const formChange = document.getElementById('form-change-pwd');
            const btnSwitch  = document.getElementById('btn-switch-mode');

            if (mode === 'change') {
                formLogin.classList.add('hidden');
                formChange.classList.remove('hidden');
                btnSwitch.textContent = 'KEMBALI KE LOGIN';
                btnSwitch.onclick = () => toggleMode('login');
            } else {
                formChange.classList.add('hidden');
                formLogin.classList.remove('hidden');
                btnSwitch.textContent = 'GANTI SANDI';
                btnSwitch.onclick = () => toggleMode('change');
            }
        }

        if (new URLSearchParams(window.location.search).get('mode') === 'change') {
            toggleMode('change');
        }
    </script>

</body>
</html>