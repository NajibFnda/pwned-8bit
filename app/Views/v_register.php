<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account - PWNED</title>
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
    </style>
</head>
<body class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] text-black font-mono min-h-screen flex flex-col justify-center items-center px-4 py-8">

    <div class="mb-8 text-center bg-white border-4 border-black p-4 shadow-[6px_6px_0px_rgba(255,255,255,1)] inline-block">
        <a href="<?= base_url('/') ?>" class="text-2xl font-pixel tracking-widest text-black">
            PWNED
        </a>
        <p class="text-[10px] text-gray-500 mt-2 uppercase font-bold tracking-widest">[ REGISTER_PORTAL ]</p>
    </div>

    <div class="bg-white p-8 border-4 border-black w-full max-w-md shadow-[8px_8px_0px_rgba(255,255,255,1)]">
        <?php $errorMsg = $form_error ?? session()->getFlashdata('error'); ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="bg-red-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] mb-6 text-xs text-center font-bold uppercase">
                [!] <?= esc($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('auth/saveRegister') ?>" method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Anti-bot honeypot field -->
            <div class="hidden" aria-hidden="true">
                <label for="website_url">Website</label>
                <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off" value="">
            </div>

            <div>
                <label for="nama" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> FULL NAME</label>
                <input type="text" id="nama" name="nama" required value="<?= esc($old_nama ?? old('nama')) ?>" placeholder="Full Name"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <label for="email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> EMAIL ADDRESS</label>
                <input type="email" id="email" name="email" required value="<?= esc($old_email ?? old('email')) ?>" placeholder="contoh@email.com"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <div>
                <label for="password" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> PASSWORD</label>
                <input type="password" id="password" name="password" required minlength="8" placeholder="Min 8 chars (huruf + angka)"
                    class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
            </div>

            <?php if (!empty($turnstile_site_key)): ?>
                <div class="pt-1">
                    <div class="cf-turnstile" data-sitekey="<?= esc($turnstile_site_key) ?>"></div>
                </div>
            <?php else: ?>
                <div>
                    <label for="captcha_code" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> HUMAN VERIFICATION (CAPTCHA)</label>
                    <div class="flex items-center gap-2 mb-2">
                        <img id="reg-captcha-img" src="<?= base_url('captcha/register?t=' . time()) ?>" alt="CAPTCHA Verification"
                            class="border-2 border-black bg-gray-100 h-14 w-[180px] object-contain">
                        <button type="button" onclick="refreshRegCaptcha()"
                            class="px-3 py-3 bg-gray-200 hover:bg-black hover:text-white border-2 border-black font-pixel text-[8px] uppercase tracking-wider">
                            REFRESH
                        </button>
                    </div>
                    <input type="text" id="captcha_code" name="captcha_code" required maxlength="5" autocomplete="off"
                        placeholder="KETIK 5 KARAKTER DI ATAS"
                        class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold uppercase tracking-widest placeholder-gray-400">
                </div>
            <?php endif; ?>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none flex justify-center items-center gap-2 mt-4 tracking-widest">
                REGISTER
            </button>
        </form>

        <div class="mt-8 text-center text-xs text-gray-500 border-t-2 border-dashed border-gray-400 pt-6 font-bold uppercase">
            Already have an account? <br>
            <a href="<?= base_url('login') ?>" class="inline-block mt-2 text-blue-600 hover:bg-blue-600 hover:text-white px-2 py-1">Login here</a>
        </div>
    </div>

    <script>
        function refreshRegCaptcha() {
            const img = document.getElementById('reg-captcha-img');
            if (img) {
                img.src = '<?= base_url('captcha/register') ?>?t=' + Date.now();
            }
        }
    </script>
</body>
</html>
