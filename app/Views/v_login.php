<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PWNED</title>
    <?= view('partials/favicon_head') ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php if (!empty($turnstile_site_key)): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        input::-ms-reveal, input::-ms-clear { display: none; }
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

        <?php $loginErrorMsg = $form_error ?? session()->getFlashdata('error'); ?>
        <?php if(!empty($loginErrorMsg)): ?>
            <div class="bg-red-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] mb-6 text-sm text-center font-bold uppercase animate-pulse">
                [!] <?= esc($loginErrorMsg) ?>
            </div>
        <?php endif; ?>
        
        <form id="form-login" action="<?= base_url('auth/process') ?>" method="POST" class="space-y-6">
            <?= csrf_field() ?>
            <div class="hidden" aria-hidden="true">
                <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
            </div>

            <div>
                <label for="email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> EMAIL ADDRESS</label>
                <input type="email" id="email" name="email" required value="<?= esc($old_email ?? old('email')) ?>" placeholder="contoh@gmail.com"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <div class="flex justify-between items-center mb-2 gap-2">
                    <label for="password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest">> PASSWORD</label>
                    <div class="flex items-center gap-3">
                        <a href="<?= base_url('forgot-password') ?>" class="text-[10px] font-bold text-red-600 hover:underline uppercase">
                            Lupa Sandi? (OTP)
                        </a>
                        <button type="button" onclick="toggleMode('change')" class="text-[10px] font-bold text-blue-600 hover:underline uppercase">
                            Ganti Sandi?
                        </button>
                    </div>
                </div>
                <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                    <input type="password" id="password" name="password" required placeholder="********"
                        class="w-full px-4 py-3 bg-transparent text-black focus:outline-none font-bold placeholder-gray-400">
                    <?= view('partials/pwd_toggle_btn', ['target' => 'password', 'label' => 'Lihat sandi']) ?>
                </div>
            </div>

            <?php if (!empty($show_captcha)): ?>
                <?php if (!empty($turnstile_site_key)): ?>
                    <div class="cf-turnstile" data-sitekey="<?= esc($turnstile_site_key) ?>"></div>
                <?php else: ?>
                    <div>
                        <label for="login_captcha_code" class="block text-[10px] font-bold text-red-700 uppercase tracking-widest mb-2">> VERIFIKASI KEAMANAN</label>
                        <div class="flex items-center gap-2 mb-2">
                            <img id="login-captcha-img" src="<?= base_url('captcha/login?t=' . time()) ?>" alt="CAPTCHA"
                                class="border-2 border-black bg-gray-100 h-14 w-[180px] object-contain">
                            <button type="button" onclick="document.getElementById('login-captcha-img').src='<?= base_url('captcha/login') ?>?t='+Date.now()"
                                class="px-3 py-3 bg-gray-200 hover:bg-black hover:text-white border-2 border-black font-pixel text-[8px] uppercase">
                                REFRESH
                            </button>
                        </div>
                        <input type="text" id="login_captcha_code" name="captcha_code" required maxlength="5" autocomplete="off"
                            placeholder="KETIK 5 KODE DI ATAS"
                            class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold uppercase tracking-widest placeholder-gray-400">
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none flex justify-center items-center gap-2 mt-4">
                LOGIN
            </button>
        </form>

        <form id="form-change-pwd" action="<?= base_url('auth/changePassword') ?>" method="POST" class="space-y-5 hidden">
            <?= csrf_field() ?>
            <div class="hidden" aria-hidden="true">
                <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
            </div>

            <div class="border-b-2 border-dashed border-gray-400 pb-3 flex justify-between items-center">
                <span class="font-pixel text-[10px] text-black uppercase">&gt; GANTI KATA SANDI</span>
                <button type="button" onclick="toggleMode('login')" class="text-[10px] font-bold text-red-600 hover:underline uppercase">
                    &lt; BATAL
                </button>
            </div>

            <div>
                <label for="cp_email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> EMAIL ADDRESS</label>
                <input type="email" id="cp_email" name="email" required value="<?= esc($old_cp_email ?? old('email')) ?>" placeholder="contoh@gmail.com"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <label for="old_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> SANDI LAMA</label>
                <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                    <input type="password" id="old_password" name="old_password" required placeholder="Masukkan sandi saat ini"
                        class="w-full px-4 py-3 bg-transparent text-black focus:outline-none font-bold placeholder-gray-400">
                    <?= view('partials/pwd_toggle_btn', ['target' => 'old_password', 'label' => 'Lihat sandi lama']) ?>
                </div>
            </div>

            <div>
                <label for="new_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> SANDI BARU</label>
                <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                    <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Min. 8 karakter (huruf + angka)"
                        class="w-full px-4 py-3 bg-transparent text-black focus:outline-none font-bold placeholder-gray-400">
                    <?= view('partials/pwd_toggle_btn', ['target' => 'new_password', 'label' => 'Lihat sandi baru']) ?>
                </div>
            </div>

            <div>
                <label for="confirm_password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> KONFIRMASI SANDI BARU</label>
                <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Ulangi sandi baru"
                        class="w-full px-4 py-3 bg-transparent text-black focus:outline-none font-bold placeholder-gray-400">
                    <?= view('partials/pwd_toggle_btn', ['target' => 'confirm_password', 'label' => 'Lihat konfirmasi sandi']) ?>
                </div>
            </div>

            <?php if (!empty($turnstile_site_key)): ?>
                <div class="cf-turnstile" data-sitekey="<?= esc($turnstile_site_key) ?>"></div>
            <?php else: ?>
                <div>
                    <label for="cp_captcha_code" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> VERIFIKASI KEAMANAN (CAPTCHA)</label>
                    <div class="flex items-center gap-2 mb-2">
                        <img id="cp-captcha-img" src="" alt="CAPTCHA"
                            class="border-2 border-black bg-gray-100 h-14 w-[180px] object-contain">
                        <button type="button" onclick="refreshCpCaptcha()"
                            class="px-3 py-3 bg-gray-200 hover:bg-black hover:text-white border-2 border-black font-pixel text-[8px] uppercase">
                            REFRESH
                        </button>
                    </div>
                    <input type="text" id="cp_captcha_code" name="captcha_code" required maxlength="5" autocomplete="off"
                        placeholder="KETIK 5 KODE DI ATAS"
                        class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold uppercase tracking-widest placeholder-gray-400">
                </div>
            <?php endif; ?>

            <button type="submit"
                class="w-full bg-black hover:bg-blue-700 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none flex justify-center items-center gap-2 mt-4">
                SIMPAN SANDI BARU
            </button>
        </form>

        <div class="mt-8 text-center text-xs text-gray-500 border-t-2 border-dashed border-gray-400 pt-6 space-y-4 font-bold uppercase">
            <div class="flex flex-wrap justify-center gap-3">
                <a href="<?= base_url('forgot-password') ?>" class="text-red-600 hover:bg-red-600 hover:text-white px-2 py-1 border border-red-600 text-[10px]">LUPA SANDI (OTP)</a>
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

    <?= view('partials/pwd_toggle_script') ?>
    <script>
        function refreshCpCaptcha() {
            const img = document.getElementById('cp-captcha-img');
            if (img) {
                img.src = '<?= base_url('captcha/changepwd') ?>?t=' + Date.now();
            }
        }

        function toggleMode(mode) {
            const formLogin  = document.getElementById('form-login');
            const formChange = document.getElementById('form-change-pwd');
            const btnSwitch  = document.getElementById('btn-switch-mode');

            if (mode === 'change') {
                formLogin.classList.add('hidden');
                formChange.classList.remove('hidden');
                btnSwitch.textContent = 'KEMBALI KE LOGIN';
                btnSwitch.onclick = () => toggleMode('login');
                refreshCpCaptcha();
            } else {
                formChange.classList.add('hidden');
                formLogin.classList.remove('hidden');
                btnSwitch.textContent = 'GANTI SANDI';
                btnSwitch.onclick = () => toggleMode('change');
            }
        }

        const openChange = <?= !empty($open_change_mode) ? 'true' : 'false' ?>;
        if (openChange || new URLSearchParams(window.location.search).get('mode') === 'change') {
            toggleMode('change');
        }
    </script>

</body>
</html>