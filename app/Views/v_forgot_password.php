<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Sandi OTP - PWNED</title>
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
<body class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] text-black font-mono min-h-screen flex flex-col justify-center items-center px-4 py-8">

    <div class="mb-8 text-center bg-white border-4 border-black p-4 shadow-[6px_6px_0px_rgba(255,255,255,1)] inline-block">
        <a href="<?= base_url('/') ?>" class="text-2xl font-pixel tracking-widest text-black">PWNED</a>
        <p class="text-[10px] text-gray-500 mt-2 uppercase font-bold tracking-widest">[ FORGOT_PASSWORD_OTP ]</p>
    </div>

    <div class="bg-white p-8 border-4 border-black w-full max-w-md shadow-[8px_8px_0px_rgba(255,255,255,1)] space-y-6">
        <div class="border-b-2 border-dashed border-gray-400 pb-3 flex justify-between items-center">
            <h1 class="font-pixel text-[10px] uppercase text-black">&gt; RESET SANDI VIA OTP EMAIL</h1>
            <a href="<?= base_url('login') ?>" class="text-[10px] font-bold text-blue-600 hover:underline uppercase">&lt; LOGIN</a>
        </div>

        <?php if (empty($mailer_configured)): ?>
            <div class="bg-yellow-300 border-2 border-black text-black px-4 py-3 text-xs font-bold uppercase">
                [INFO] Layanan pengiriman email OTP belum diaktifkan oleh Admin. Anda tetap dapat mengganti sandi di halaman Login menggunakan sandi lama.
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('otp_info')): ?>
            <div class="bg-blue-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-xs font-bold uppercase">
                [INFO] <?= esc(session()->getFlashdata('otp_info')) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($form_error)): ?>
            <div class="bg-red-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-xs font-bold uppercase">
                [!] <?= esc($form_error) ?>
            </div>
        <?php endif; ?>

        <?php if (($step ?? 'request') === 'reset'): ?>
            <form method="POST" action="<?= base_url('forgot-password/reset') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div class="hidden" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
                </div>

                <div>
                    <label for="reset_email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; EMAIL AKUN</label>
                    <input type="email" id="reset_email" name="email" required value="<?= esc($email ?? '') ?>"
                        class="w-full px-4 py-3 bg-gray-100 text-black border-2 border-black font-bold text-sm">
                </div>

                <div>
                    <label for="otp_code" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; KODE OTP (6 DIGIT)</label>
                    <input type="text" id="otp_code" name="otp_code" required maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code"
                        placeholder="123456"
                        class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-pixel text-center text-base tracking-[0.4em] placeholder-gray-300">
                </div>

                <div>
                    <label for="forgot_new_pwd" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; SANDI BARU</label>
                    <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                        <input type="password" id="forgot_new_pwd" name="new_password" required minlength="8" placeholder="Min. 8 karakter (huruf + angka)"
                            class="w-full px-4 py-3 bg-transparent text-black focus:outline-none font-bold text-sm">
                        <?= view('partials/pwd_toggle_btn', ['target' => 'forgot_new_pwd', 'label' => 'Lihat sandi baru']) ?>
                    </div>
                </div>

                <div>
                    <label for="forgot_confirm_pwd" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; KONFIRMASI SANDI BARU</label>
                    <div class="flex border-2 border-black bg-white focus-within:bg-gray-100">
                        <input type="password" id="forgot_confirm_pwd" name="confirm_password" required minlength="8" placeholder="Ulangi sandi baru"
                            class="w-full px-4 py-3 bg-transparent text-black focus:outline-none font-bold text-sm">
                        <?= view('partials/pwd_toggle_btn', ['target' => 'forgot_confirm_pwd', 'label' => 'Lihat konfirmasi sandi baru']) ?>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-black hover:bg-blue-700 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                    VERIFIKASI OTP &amp; SIMPAN SANDI
                </button>
            </form>

            <div class="border-t-2 border-dashed border-gray-400 pt-4 text-center">
                <a href="<?= base_url('forgot-password') ?>" class="text-[10px] font-bold text-blue-600 hover:underline uppercase">
                    KIRIM ULANG / GANTI EMAIL &gt;&gt;
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= base_url('forgot-password/send') ?>" class="space-y-5">
                <?= csrf_field() ?>
                <div class="hidden" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
                </div>

                <div>
                    <label for="forgot_email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; ALAMAT EMAIL TERDAFTAR</label>
                    <input type="email" id="forgot_email" name="email" required value="<?= esc($email ?? '') ?>" placeholder="contoh@gmail.com"
                        class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold placeholder-gray-400">
                </div>

                <?php if (!empty($turnstile_site_key)): ?>
                    <div class="cf-turnstile" data-sitekey="<?= esc($turnstile_site_key) ?>"></div>
                <?php else: ?>
                    <div>
                        <label for="forgot_captcha" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; VERIFIKASI KEAMANAN (CAPTCHA)</label>
                        <div class="flex items-center gap-2 mb-2">
                            <img id="forgot-captcha-img" src="<?= base_url('captcha/forgot?t=' . time()) ?>" alt="CAPTCHA"
                                class="border-2 border-black bg-gray-100 h-14 w-[180px] object-contain">
                            <button type="button" onclick="document.getElementById('forgot-captcha-img').src='<?= base_url('captcha/forgot') ?>?t='+Date.now()"
                                class="px-3 py-3 bg-gray-200 hover:bg-black hover:text-white border-2 border-black font-pixel text-[8px] uppercase">REFRESH</button>
                        </div>
                        <input type="text" id="forgot_captcha" name="captcha_code" required maxlength="5" autocomplete="off"
                            placeholder="KETIK 5 KODE DI ATAS"
                            class="w-full px-4 py-3 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-bold uppercase tracking-widest placeholder-gray-400">
                    </div>
                <?php endif; ?>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                    KIRIM KODE OTP &gt;&gt;
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?= view('partials/pwd_toggle_script') ?>
</body>
</html>
