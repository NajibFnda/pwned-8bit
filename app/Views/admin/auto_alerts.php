<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-8 border-b-4 border-black pb-4">
    <h1 class="text-2xl sm:text-3xl font-pixel text-black uppercase">Auto Alert System</h1>
    <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">&gt; Monitoring kebocoran berkala XposedOrNot &amp; notifikasi otomatis via Brevo / SMTP.</p>
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
    $c   = $cfg ?? [];
    $ov  = $overview ?? [];
    $st  = $ov['stats'] ?? ['total_monitors' => 0, 'active_monitors' => 0, 'due_now' => 0, 'alerts_sent_24h' => 0, 'alerts_failed_24h' => 0];
    $mon = $ov['monitors'] ?? [];
    $lgs = $ov['logs'] ?? [];
?>

<?php if (empty($ov['mailer_active'])): ?>
    <div class="bg-yellow-200 border-4 border-black p-4 mb-6 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex flex-wrap items-center justify-between gap-3">
        <span class="font-bold text-xs uppercase text-black">
            [!] Pengirim email (Brevo / SMTP) belum aktif — alert tidak dapat dikirim ke Inbox pengguna sebelum dikonfigurasi.
        </span>
        <a href="<?= base_url('admin/otp-settings') ?>" class="bg-black text-white font-pixel text-[9px] px-3 py-2 border-2 border-black uppercase hover:bg-blue-700">
            ATUR BREVO / SMTP &gt;&gt;
        </a>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">JALUR EMAIL</span>
        <span class="inline-block mt-2 px-2.5 py-1 text-xs font-pixel border-2 border-black uppercase <?= !empty($ov['mailer_active']) ? 'bg-green-600 text-white' : 'bg-red-600 text-white' ?>">
            <?= !empty($ov['mailer_active']) ? strtoupper(esc((string) ($ov['mailer_driver'] ?? 'OK'))) . ' AKTIF' : 'OFFLINE' ?>
        </span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-blue-600 uppercase tracking-widest">PANTAUAN AKTIF / DUE</span>
        <span class="block text-2xl font-pixel text-blue-600 mt-2">
            <?= number_format((int) $st['active_monitors']) ?>
            <span class="text-xs text-gray-500">/ <?= (int) $st['due_now'] ?> DUE</span>
        </span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-green-700 uppercase tracking-widest">ALERT TERKIRIM (24H)</span>
        <span class="block text-2xl font-pixel text-green-700 mt-2"><?= number_format((int) $st['alerts_sent_24h']) ?></span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-red-600 uppercase tracking-widest">GAGAL KIRIM (24H)</span>
        <span class="block text-2xl font-pixel text-red-600 mt-2"><?= number_format((int) $st['alerts_failed_24h']) ?></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start mb-10">
    <!-- Kiri: Jadwal, Cron Endpoint & Eksekusi Manual -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)]">
            <h2 class="font-pixel text-xs uppercase text-black border-b-4 border-black pb-3 mb-5">&gt; KONFIGURASI PENJADWALAN</h2>

            <form method="POST" action="<?= base_url('admin/auto-alerts/settings') ?>" class="space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="interval_hours" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; INTERVAL CEK ULANG (JAM)</label>
                        <select id="interval_hours" name="interval_hours" class="w-full px-3 py-3 bg-white border-4 border-black text-xs font-bold uppercase outline-none">
                            <?php foreach ([6, 12, 24, 48, 72] as $h): ?>
                                <option value="<?= $h ?>" <?= ((int) ($c['interval_hours'] ?? 24) === $h) ? 'selected' : '' ?>>SETIAP <?= $h ?> JAM</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="batch_size" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-1">&gt; MAKS EMAIL PER CRON RUN</label>
                        <select id="batch_size" name="batch_size" class="w-full px-3 py-3 bg-white border-4 border-black text-xs font-bold uppercase outline-none">
                            <?php foreach ([3, 5, 8, 10] as $b): ?>
                                <option value="<?= $b ?>" <?= ((int) ($c['batch_size'] ?? 5) === $b) ? 'selected' : '' ?>><?= $b ?> EMAIL / RUN (AMAN RATE-LIMIT)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="flex items-start gap-3 p-4 border-4 border-black bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" <?= !empty($c['enabled']) ? 'checked' : '' ?> class="mt-0.5 w-4 h-4 accent-black shrink-0">
                    <div>
                        <span class="block text-xs font-bold uppercase text-black">AKTIFKAN SCHEDULER AUTO ALERT (CRON)</span>
                        <span class="block text-[10px] text-gray-600 font-bold mt-1">
                            Saat aktif, endpoint cron memeriksa email pantauan pengguna PLUS / PRO yang jatuh tempo lalu mengirim notifikasi via Brevo jika ditemukan kebocoran baru.
                        </span>
                    </div>
                </label>

                <button type="submit" class="w-full bg-black hover:bg-blue-700 text-white font-pixel text-[10px] py-3.5 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase transition-none">
                    SIMPAN PENGATURAN AUTO ALERT
                </button>
            </form>
        </div>

        <div class="bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)] space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b-4 border-black pb-3">
                <h2 class="font-pixel text-xs uppercase text-black">&gt; URL CRON OTOMATIS (INFINITYFREE)</h2>
                <form method="POST" action="<?= base_url('admin/auto-alerts/regen-token') ?>" onsubmit="return confirm('Ganti token cron lama dengan yang baru? URL lama akan berhenti bekerja.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="bg-white hover:bg-yellow-300 text-black font-pixel text-[8px] px-2.5 py-1.5 border-2 border-black uppercase">
                        GANTI TOKEN
                    </button>
                </form>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" id="cron-url-input" readonly value="<?= esc($cron_url ?? '') ?>"
                    class="flex-1 px-3 py-2.5 bg-gray-100 border-2 border-black font-mono text-[11px] font-bold select-all outline-none">
                <button type="button" onclick="copyCronUrl()" class="bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[9px] px-4 py-2.5 border-2 border-black uppercase shrink-0">
                    SALIN URL
                </button>
            </div>
            <p id="cron-copy-msg" class="text-[10px] font-bold text-gray-600">
                Pasang URL di layanan cron gratis seperti <strong>cron-job.org</strong> (mis. tiap 1–6 jam) agar pengecekan berjalan otomatis di InfinityFree.
            </p>

            <?php if (!empty($c['last_cron_run_at'])): ?>
                <div class="bg-gray-50 border-2 border-dashed border-black p-3 text-[11px] font-bold">
                    RUN TERAKHIR: <span class="text-blue-700"><?= esc(date('d M Y H:i:s', strtotime((string) $c['last_cron_run_at']))) ?></span>
                    &bull; <?= esc((string) ($c['last_cron_summary'] ?? '-')) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= base_url('admin/auto-alerts/run-now') ?>" class="pt-2 flex flex-wrap items-center justify-between gap-3 border-t-2 border-dashed border-gray-300">
                <?= csrf_field() ?>
                <label class="flex items-center gap-2 text-[11px] font-bold uppercase cursor-pointer">
                    <input type="checkbox" name="force_all" value="1" class="w-4 h-4 accent-black">
                    <span>Cek paksa (abaikan interval <?= (int) ($c['interval_hours'] ?? 24) ?> jam)</span>
                </label>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-pixel text-[9px] px-4 py-3 border-2 border-black uppercase">
                    JALANKAN CEK BATCH SEKARANG &gt;&gt;
                </button>
            </form>
        </div>
    </div>

    <!-- Kanan: Simulasi / Test Kirim Alert Email -->
    <div class="space-y-6">
        <div class="bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)] space-y-4">
            <h2 class="font-pixel text-[10px] uppercase text-black border-b-4 border-black pb-3">&gt; SIMULASI KIRIM ALERT</h2>
            <p class="text-[11px] font-bold text-gray-600">
                Uji template email peringatan kebocoran data baru via Brevo/SMTP tanpa mengubah status data pantauan pengguna.
            </p>
            <form method="POST" action="<?= base_url('admin/auto-alerts/test') ?>" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label for="alert_test_email" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">&gt; EMAIL PENERIMA TEST</label>
                    <input type="email" id="alert_test_email" name="test_email" required placeholder="emailanda@gmail.com"
                        class="w-full px-3 py-2.5 bg-white border-4 border-black text-xs font-bold outline-none">
                </div>
                <div>
                    <label for="sample_monitored" class="block text-[10px] font-bold text-gray-700 uppercase mb-1">&gt; CONTOH EMAIL TERDAMPAK (OPSIONAL)</label>
                    <input type="email" id="sample_monitored" name="sample_monitored" placeholder="contoh@domain.com"
                        class="w-full px-3 py-2.5 bg-white border-4 border-black text-xs font-bold outline-none">
                </div>
                <button type="submit" class="w-full bg-red-600 hover:bg-red-800 text-white font-pixel text-[9px] px-4 py-3 border-2 border-black uppercase">
                    KIRIM SIMULASI ALERT &gt;&gt;
                </button>
            </form>
        </div>

        <div class="bg-white border-4 border-black p-5 shadow-[6px_6px_0px_rgba(0,0,0,1)] space-y-2 text-[11px] font-bold text-gray-700">
            <h3 class="font-pixel text-[10px] uppercase text-black border-b-2 border-black pb-2">&gt; CARA KERJA ANTI-SPAM</h3>
            <p>1. Saat email ditambahkan, sistem mencatat daftar insiden lama sebagai <strong>baseline</strong>.</p>
            <p>2. Notifikasi email HANYA dikirim jika muncul nama insiden <strong>BARU</strong> yang belum pernah dilaporkan.</p>
            <p>3. Email alert hanya dikirim ke <strong>email akun pemilik</strong> agar tidak disalahgunakan untuk spam pihak lain.</p>
        </div>
    </div>
</div>

<!-- Daftar Email Dipantau (Semua User) -->
<div class="bg-white border-4 border-black shadow-[6px_6px_0px_rgba(0,0,0,1)] mb-10">
    <div class="px-6 py-4 bg-gray-100 border-b-4 border-black flex flex-wrap justify-between items-center gap-3">
        <h2 class="font-pixel text-xs uppercase text-black">&gt; DAFTAR EMAIL DIPANTAU (50 TERBARU)</h2>
        <span class="text-[10px] font-bold text-gray-500 uppercase">TOTAL: <?= number_format((int) $st['total_monitors']) ?> RECORD</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-black">
            <thead class="bg-gray-200 border-b-4 border-black uppercase font-bold">
                <tr>
                    <th class="px-4 py-3 border-r-4 border-black">Pemilik</th>
                    <th class="px-4 py-3 border-r-4 border-black">Email Dipantau</th>
                    <th class="px-4 py-3 border-r-4 border-black">Status</th>
                    <th class="px-4 py-3 border-r-4 border-black">Cek Terakhir</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black font-bold">
                <?php if (!empty($mon)): ?>
                    <?php foreach ($mon as $m): ?>
                        <?php
                            $stLabel = strtoupper((string) ($m['last_status'] ?? 'pending'));
                            $stCls   = match ($m['last_status'] ?? 'pending') {
                                'pwned' => 'bg-red-600 text-white',
                                'safe'  => 'bg-green-600 text-white',
                                'error' => 'bg-yellow-300 text-black',
                                default => 'bg-gray-200 text-black',
                            };
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-r-4 border-black">
                                <div class="uppercase"><?= esc($m['owner_nama'] ?? '-') ?></div>
                                <div class="text-[10px] text-gray-500"><?= esc($m['owner_email'] ?? '-') ?> (<?= strtoupper(esc((string) ($m['role'] === 'admin' ? 'admin' : ($m['subscription_plan'] ?? 'free')))) ?>)</div>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black text-blue-700 font-mono">
                                <?= esc($m['email']) ?>
                                <?php if (empty($m['is_active'])): ?>
                                    <span class="ml-1 bg-gray-300 text-black px-1.5 py-0.5 text-[9px] border border-black">PAUSED</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black">
                                <span class="<?= $stCls ?> px-2 py-0.5 border border-black text-[9px] font-pixel uppercase">
                                    <?= esc($stLabel) ?> (<?= (int) ($m['last_breach_count'] ?? 0) ?>)
                                </span>
                                <?php if (!empty($m['last_error'])): ?>
                                    <div class="text-[10px] text-red-600 mt-1"><?= esc($m['last_error']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black text-[11px] font-mono text-gray-600">
                                <?= !empty($m['last_checked_at']) ? esc(date('d M Y H:i', strtotime((string) $m['last_checked_at']))) : 'BELUM DICEK' ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form method="POST" action="<?= base_url('admin/auto-alerts/monitor/' . (int) $m['id'] . '/check') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-800 text-white border-2 border-black text-[9px] font-pixel uppercase">
                                            CEK
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= base_url('admin/auto-alerts/monitor/' . (int) $m['id'] . '/delete') ?>" onsubmit="return confirm('Hapus email pantauan ini?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2.5 py-1.5 bg-white hover:bg-red-600 hover:text-white border-2 border-black text-[9px] font-pixel uppercase">
                                            HAPUS
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 uppercase">[!] Belum ada email yang didaftarkan untuk Auto Alert.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Log Pengiriman Alert -->
<div class="bg-white border-4 border-black shadow-[6px_6px_0px_rgba(0,0,0,1)]">
    <div class="px-6 py-4 bg-gray-100 border-b-4 border-black">
        <h2 class="font-pixel text-xs uppercase text-black">&gt; LOG PENGIRIMAN EMAIL ALERT (30 TERBARU)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-black">
            <thead class="bg-gray-200 border-b-4 border-black uppercase font-bold">
                <tr>
                    <th class="px-4 py-3 border-r-4 border-black">Waktu</th>
                    <th class="px-4 py-3 border-r-4 border-black">Email Dipantau</th>
                    <th class="px-4 py-3 border-r-4 border-black">Dikirim Ke</th>
                    <th class="px-4 py-3 border-r-4 border-black">Insiden Baru</th>
                    <th class="px-4 py-3">Status Pengiriman</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black font-bold">
                <?php if (!empty($lgs)): ?>
                    <?php foreach ($lgs as $log): ?>
                        <?php
                            $okLog = ($log['delivery_status'] ?? '') === 'sent';
                            $items = json_decode((string) ($log['new_breaches_json'] ?? '[]'), true);
                            $names = is_array($items) ? array_column($items, 'sumber') : [];
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-r-4 border-black text-[11px] font-mono text-gray-600">
                                <?= esc(date('d M Y H:i', strtotime((string) $log['created_at']))) ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black text-blue-700 font-mono"><?= esc($log['monitored_email']) ?></td>
                            <td class="px-4 py-3 border-r-4 border-black font-mono"><?= esc($log['recipient_email']) ?></td>
                            <td class="px-4 py-3 border-r-4 border-black">
                                <span class="text-red-600">+<?= (int) ($log['new_count'] ?? 0) ?></span>
                                <?php if (!empty($names)): ?>
                                    <span class="text-[10px] text-gray-600 block"><?= esc(implode(', ', array_slice($names, 0, 4))) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="<?= $okLog ? 'bg-green-600 text-white' : 'bg-red-600 text-white' ?> px-2 py-0.5 border border-black text-[9px] font-pixel uppercase">
                                    <?= $okLog ? 'SENT' : 'FAILED' ?>
                                </span>
                                <?php if (!empty($log['delivery_debug'])): ?>
                                    <div class="text-[10px] text-gray-500 mt-1"><?= esc($log['delivery_debug']) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 uppercase">[!] Belum ada notifikasi kebocoran baru yang dipicu.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function copyCronUrl() {
        const inp = document.getElementById('cron-url-input');
        const msg = document.getElementById('cron-copy-msg');
        if (!inp) return;
        inp.select();
        inp.setSelectionRange(0, 99999);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(inp.value).then(() => {
                msg.textContent = '[OK] URL Cron berhasil disalin ke clipboard!';
                msg.className = 'text-[10px] font-bold text-green-700';
            });
        } else {
            document.execCommand('copy');
            msg.textContent = '[OK] URL Cron berhasil disalin!';
            msg.className = 'text-[10px] font-bold text-green-700';
        }
    }
</script>

<?= $this->endSection() ?>
