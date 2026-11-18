<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-8 border-b-4 border-black pb-4">
    <h1 class="text-2xl sm:text-3xl font-pixel text-black uppercase">Pengaturan Email &amp; OTP</h1>
    <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">&gt; Konfigurasi pengiriman kode OTP 6 digit yang kompatibel dengan hosting InfinityFree.</p>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="bg-white border-4 border-black p-4 mb-6 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3">
        <span class="font-pixel text-xs text-green-700">[OK]</span>
        <span class="font-bold text-xs uppercase"><?= esc(session()->getFlashdata('pesan')) ?></span>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error_admin')): ?>
    <div class="bg-red-600 text-white border-4 border-black p-4 mb-6 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3">
        <span class="font-pixel text-xs">[!]</span>
        <span class="font-bold text-xs uppercase"><?= esc(session()->getFlashdata('error_admin')) ?></span>
    </div>
<?php endif; ?>

<?php
    $c = $cfg ?? [];
    $s = $otp_stats ?? ['total_24h' => 0, 'verified_24h' => 0];
    $drv = (string) ($c['driver'] ?? 'off');
?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">STATUS EMAIL OTP</span>
        <span class="inline-block mt-2 px-2.5 py-1 text-xs font-pixel border-2 border-black uppercase <?= !empty($c['is_configured']) ? 'bg-green-600 text-white' : 'bg-gray-300 text-black' ?>">
            <?= !empty($c['is_configured']) ? strtoupper(esc($drv)) . ' AKTIF' : 'BELUM AKTIF' ?>
        </span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-blue-600 uppercase tracking-widest">OTP DIKIRIM (24 JAM)</span>
        <span class="block text-2xl font-pixel text-blue-600 mt-2"><?= number_format((int) $s['total_24h']) ?></span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-green-700 uppercase tracking-widest">OTP TERVERIFIKASI (24 JAM)</span>
        <span class="block text-2xl font-pixel text-green-700 mt-2"><?= number_format((int) $s['verified_24h']) ?></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
    <div class="lg:col-span-2 bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
        <h2 class="font-pixel text-xs uppercase text-black border-b-4 border-black pb-3 mb-5">&gt; KONFIGURASI PENGIRIM OTP</h2>

        <form method="POST" action="<?= base_url('admin/otp-settings/save') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="otp_driver" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; METODE PENGIRIMAN</label>
                    <select id="otp_driver" name="driver" onchange="toggleDriverFields()"
                        class="w-full px-3 py-3 bg-white border-4 border-black text-xs font-bold uppercase outline-none">
                        <option value="off" <?= $drv === 'off' ? 'selected' : '' ?>>OFF (NONAKTIF)</option>
                        <option value="brevo" <?= $drv === 'brevo' ? 'selected' : '' ?>>BREVO API (HTTPS 443 - DISARANKAN)</option>
                        <option value="smtp" <?= $drv === 'smtp' ? 'selected' : '' ?>>SMTP PORT 587 TLS (GMAIL / CUSTOM)</option>
                    </select>
                </div>
                <div>
                    <label for="otp_from_name" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; NAMA PENGIRIM</label>
                    <input type="text" id="otp_from_name" name="from_name" maxlength="60" value="<?= esc($c['from_name'] ?? 'PWNED Security') ?>"
                        class="w-full px-3 py-3 bg-white border-4 border-black text-xs font-bold outline-none">
                </div>
            </div>

            <div>
                <label for="otp_from_email" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; EMAIL PENGIRIM (FROM EMAIL)</label>
                <input type="email" id="otp_from_email" name="from_email" value="<?= esc($c['from_email'] ?? '') ?>" placeholder="contoh: adminmu@gmail.com"
                    class="w-full px-3 py-3 bg-white border-4 border-black text-xs font-bold outline-none">
                <span class="block text-[10px] text-gray-500 font-bold mt-1">Wajib sama dengan Single Sender di Brevo atau alamat Gmail SMTP Anda.</span>
            </div>

            <!-- Brevo Fields -->
            <div id="box-brevo" class="p-4 border-4 border-black bg-blue-50 space-y-3 <?= $drv === 'brevo' ? '' : 'hidden' ?>">
                <div class="font-pixel text-[9px] text-blue-800 uppercase">&gt; CREDENTIAL BREVO API (PORT 443)</div>
                <div>
                    <label for="brevo_api_key" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">BREVO API KEY (v3)</label>
                    <div class="flex border-2 border-black bg-white">
                        <input type="password" id="brevo_api_key" name="brevo_api_key" autocomplete="new-password"
                            placeholder="<?= !empty($c['has_brevo_key']) ? 'Sudah tersimpan (' . (int) ($c['brevo_key_len'] ?? 0) . ' karakter — kosongkan jika tetap)' : 'xkeysib-...' ?>"
                            class="w-full px-3 py-2.5 bg-transparent text-xs font-bold outline-none">
                        <button type="button" onclick="toggleSecretInput('brevo_api_key', this)"
                            class="px-3 bg-gray-100 hover:bg-black hover:text-white border-l-2 border-black text-[10px] font-bold uppercase">LIHAT</button>
                    </div>
                    <span class="block text-[10px] text-gray-600 font-bold mt-1">
                        Demi keamanan, key yang sudah tersimpan sengaja disembunyikan dari kotak input (cukup kosongkan jika tidak mengganti key).
                    </span>
                    <?php
                        $bp = strtolower((string) ($c['brevo_key_prefix'] ?? ''));
                        if (!empty($c['has_brevo_key']) && $bp !== '' && !str_starts_with($bp, 'xkeysib-')):
                    ?>
                        <span class="block text-[10px] text-red-700 font-bold mt-1">
                            [!] Key tersimpan berawalan "<?= esc($c['brevo_key_prefix']) ?>..." — BUKAN API Key v3 ("xkeysib-..."). Di Brevo, buka tab <strong>API Keys</strong> &gt; <strong>Generate a new API key</strong> (bukan tab SMTP / MCP).
                        </span>
                    <?php elseif (!empty($c['has_brevo_key']) && str_starts_with($bp, 'xkeysib-')): ?>
                        <span class="block text-[10px] text-green-700 font-bold mt-1">
                            [OK] Format awalan key tersimpan valid (<?= esc($c['brevo_key_prefix']) ?>...). Jika HTTP 401 "Key not found", key tersebut sudah dihapus/nonaktif di Brevo — buat API Key baru di tab API Keys.
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SMTP Fields -->
            <div id="box-smtp" class="p-4 border-4 border-black bg-yellow-50 space-y-3 <?= $drv === 'smtp' ? '' : 'hidden' ?>">
                <div class="font-pixel text-[9px] text-yellow-800 uppercase">&gt; CREDENTIAL SMTP (PORT 587 TLS)</div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label for="smtp_host" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">SMTP HOST</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?= esc($c['smtp_host'] ?? 'smtp.gmail.com') ?>"
                            class="w-full px-3 py-2.5 bg-white border-2 border-black text-xs font-bold outline-none">
                    </div>
                    <div>
                        <label for="smtp_port" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">PORT (INFINITYFREE: 587)</label>
                        <input type="number" id="smtp_port" name="smtp_port" value="<?= (int) ($c['smtp_port'] ?? 587) ?>"
                            class="w-full px-3 py-2.5 bg-white border-2 border-black text-xs font-bold outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="smtp_user" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">SMTP USERNAME (EMAIL)</label>
                        <input type="text" id="smtp_user" name="smtp_user" value="<?= esc($c['smtp_user'] ?? '') ?>" placeholder="akun@gmail.com"
                            class="w-full px-3 py-2.5 bg-white border-2 border-black text-xs font-bold outline-none">
                    </div>
                    <div>
                        <label for="smtp_pass" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">SMTP APP PASSWORD</label>
                        <div class="flex border-2 border-black bg-white">
                            <input type="password" id="smtp_pass" name="smtp_pass" autocomplete="new-password"
                                placeholder="<?= !empty($c['has_smtp_pass']) ? 'Sudah tersimpan (' . (int) ($c['smtp_pass_len'] ?? 0) . ' karakter)' : '16 huruf App Password Google' ?>"
                                class="w-full px-3 py-2.5 bg-transparent text-xs font-bold outline-none">
                            <button type="button" onclick="toggleSecretInput('smtp_pass', this)"
                                class="px-3 bg-gray-100 hover:bg-black hover:text-white border-l-2 border-black text-[10px] font-bold uppercase">LIHAT</button>
                        </div>
                        <?php if (!empty($c['has_smtp_pass']) && str_contains(strtolower((string) ($c['smtp_host'] ?? '')), 'gmail') && (int) ($c['smtp_pass_len'] ?? 0) !== 16): ?>
                            <span class="block text-[10px] text-red-700 font-bold mt-1">[!] Panjang sandi tersimpan <?= (int) $c['smtp_pass_len'] ?> karakter. App Password Gmail wajib 16 huruf!</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <label class="flex items-start gap-3 p-3 border-2 border-black bg-gray-50 cursor-pointer">
                <input type="checkbox" name="require_register_otp" value="1" <?= !empty($c['require_register_otp']) ? 'checked' : '' ?> class="w-4 h-4 mt-0.5 accent-black">
                <span class="text-xs font-bold uppercase text-black">
                    Wajibkan Verifikasi OTP Email saat Daftar Akun Baru (Register)
                    <span class="block text-[10px] text-gray-500 mt-1 normal-case font-normal">
                        Jika dicentang &amp; pengirim aktif, pendaftar baru wajib memasukkan OTP 6 digit dari inbox-nya sebelum akun disimpan ke database. Fitur "Lupa Sandi via OTP" otomatis aktif setiap kali pengirim aktif.
                    </span>
                </span>
            </label>

            <button type="submit" class="w-full bg-black hover:bg-blue-700 text-white font-pixel text-[10px] px-5 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                SIMPAN PENGATURAN OTP
            </button>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="font-pixel text-[10px] uppercase text-black border-b-4 border-black pb-3">&gt; TEST KIRIM EMAIL OTP</h2>
            <p class="text-[11px] font-bold text-gray-600">Simpan pengaturan terlebih dahulu lalu kirim kode uji coba untuk memastikan hosting berhasil menembus email tujuan.</p>

            <form method="POST" action="<?= base_url('admin/otp-settings/test') ?>" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label for="test_email" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">&gt; EMAIL TUJUAN</label>
                    <input type="email" id="test_email" name="test_email" required placeholder="emailanda@gmail.com"
                        class="w-full px-3 py-2.5 bg-white border-4 border-black text-xs font-bold outline-none">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[9px] px-4 py-3 border-2 border-black uppercase">
                    KIRIM TEST OTP &gt;&gt;
                </button>
            </form>
        </div>

        <div class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)] space-y-3 text-[11px]">
            <h3 class="font-pixel text-[10px] uppercase text-black border-b-2 border-black pb-2">&gt; PANDUAN SINGKAT INFINITYFREE</h3>
            <p class="font-bold text-blue-700 uppercase">1. Brevo (Gratis 300/hari - Paling Stabil):</p>
            <ul class="list-disc pl-4 space-y-1 text-gray-700 font-bold">
                <li>Daftar gratis di <span class="underline">brevo.com</span> (tanpa kartu kredit).</li>
                <li>Menu <em>Senders &amp; IP &gt; Senders</em>: verifikasi email Gmail Anda sebagai pengirim.</li>
                <li>Menu <em>SMTP &amp; API &gt; API Keys</em>: buat API Key (`xkeysib-...`) lalu tempel di form kiri.</li>
            </ul>
            <p class="font-bold text-yellow-700 uppercase pt-2">2. Gmail SMTP (Gratis - Port 587 TLS):</p>
            <ul class="list-disc pl-4 space-y-1 text-gray-700 font-bold">
                <li>Aktifkan 2FA di akun Google Anda, lalu buat <strong>App Password</strong> (16 huruf).</li>
                <li>Pakai Host <code class="bg-gray-200 px-1">smtp.gmail.com</code> &amp; Port <code class="bg-gray-200 px-1">587</code> (port 465 &amp; 25 diblokir InfinityFree).</li>
            </ul>
        </div>
    </div>
</div>

<script>
    function toggleDriverFields() {
        const drv = document.getElementById('otp_driver').value;
        document.getElementById('box-brevo').classList.toggle('hidden', drv !== 'brevo');
        document.getElementById('box-smtp').classList.toggle('hidden', drv !== 'smtp');
    }

    function toggleSecretInput(id, btn) {
        const el = document.getElementById(id);
        if (!el || !btn) return;
        const show = el.type === 'password';
        el.type = show ? 'text' : 'password';
        btn.textContent = show ? 'TUTUP' : 'LIHAT';
    }
</script>

<?= $this->endSection() ?>
