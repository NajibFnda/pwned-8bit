<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - PWNED</title>
    <?= view('partials/favicon_head') ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] text-black font-mono min-h-screen flex flex-col justify-center items-center px-4 py-8">

    <div class="mb-8 text-center bg-white border-4 border-black p-4 shadow-[6px_6px_0px_rgba(255,255,255,1)] inline-block">
        <a href="<?= base_url('/') ?>" class="text-2xl font-pixel tracking-widest text-black">PWNED</a>
        <p class="text-[10px] text-gray-500 mt-2 uppercase font-bold tracking-widest">[ VERIFY_OTP.EXE ]</p>
    </div>

    <div class="bg-white p-8 border-4 border-black w-full max-w-md shadow-[8px_8px_0px_rgba(255,255,255,1)] space-y-6">
        <div class="border-b-2 border-dashed border-gray-400 pb-4">
            <h1 class="font-pixel text-xs uppercase text-black">&gt; VERIFIKASI EMAIL PENDAFTARAN</h1>
            <p class="text-xs font-bold text-gray-600 mt-2">
                KODE 6 DIGIT DIKIRIM KE: <span class="text-blue-700 break-all"><?= esc($email ?? '') ?></span>
            </p>
        </div>

        <?php if (session()->getFlashdata('otp_info')): ?>
            <div class="bg-blue-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-xs font-bold uppercase">
                [INFO] <?= esc(session()->getFlashdata('otp_info')) ?>
            </div>
        <?php endif; ?>

        <?php $err = $form_error ?? session()->getFlashdata('otp_error'); ?>
        <?php if (!empty($err)): ?>
            <div class="bg-red-600 border-2 border-black text-white px-4 py-3 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-xs font-bold uppercase">
                [!] <?= esc($err) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('verify-otp') ?>" class="space-y-5">
            <?= csrf_field() ?>
            <div>
                <label for="otp_code" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; KODE OTP (6 DIGIT)</label>
                <input type="text" id="otp_code" name="otp_code" required maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code"
                    placeholder="123456"
                    class="w-full px-4 py-4 bg-white text-black border-2 border-black focus:outline-none focus:bg-gray-100 font-pixel text-center text-lg tracking-[0.4em] placeholder-gray-300">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-6 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                VERIFIKASI &amp; AKTIFKAN AKUN &gt;&gt;
            </button>
        </form>

        <div class="border-t-2 border-dashed border-gray-400 pt-5 flex flex-col gap-3 text-center">
            <form method="POST" action="<?= base_url('verify-otp/resend') ?>">
                <?= csrf_field() ?>
                <?php $cd = (int) ($cooldown_remaining ?? 0); ?>
                <button type="submit" id="btn-resend-otp" <?= $cd > 0 ? 'disabled' : '' ?>
                    class="w-full bg-gray-200 hover:bg-black hover:text-white disabled:opacity-50 text-black font-pixel text-[9px] px-4 py-3 border-2 border-black uppercase">
                    <?= $cd > 0 ? 'KIRIM ULANG (' . $cd . 'S)' : 'KIRIM ULANG KODE OTP' ?>
                </button>
            </form>

            <a href="<?= base_url('register') ?>" class="text-[10px] font-bold text-gray-600 hover:text-black uppercase">&lt; DAFTAR DENGAN EMAIL LAIN</a>
        </div>
    </div>

    <script>
        (function () {
            const rem = <?= (int) ($cooldown_remaining ?? 0) ?>;
            const btn = document.getElementById('btn-resend-otp');
            if (!btn || rem <= 0) return;
            const endAt = Date.now() + (rem * 1000);
            const tick = () => {
                const left = Math.max(0, Math.ceil((endAt - Date.now()) / 1000));
                if (left <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = 'KIRIM ULANG KODE OTP';
                } else {
                    btn.textContent = 'KIRIM ULANG (' + left + 'S)';
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
