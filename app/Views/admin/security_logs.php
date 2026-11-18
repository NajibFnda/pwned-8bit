<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-8 border-b-4 border-black pb-4">
    <h1 class="text-2xl sm:text-3xl font-pixel text-black uppercase">Security Audit Log</h1>
    <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">&gt; Pantau aktivitas autentikasi, blokir bot/DDoS, dan aksi admin secara real-time.</p>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="bg-white border-4 border-black p-4 mb-8 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3">
        <span class="text-xl">!</span>
        <span class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('pesan')) ?></span>
    </div>
<?php endif; ?>

<!-- 24-Hour Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">EVENT 24 JAM</span>
        <span class="block text-2xl font-pixel text-black mt-2"><?= number_format((int) ($stats_24h['total'] ?? 0)) ?></span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-blue-600 uppercase tracking-widest">INFO</span>
        <span class="block text-2xl font-pixel text-blue-600 mt-2"><?= number_format((int) ($stats_24h['info'] ?? 0)) ?></span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-yellow-700 uppercase tracking-widest">WARNING</span>
        <span class="block text-2xl font-pixel text-yellow-600 mt-2"><?= number_format((int) ($stats_24h['warning'] ?? 0)) ?></span>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)]">
        <span class="block text-[10px] font-bold text-red-600 uppercase tracking-widest">CRITICAL</span>
        <span class="block text-2xl font-pixel text-red-600 mt-2"><?= number_format((int) ($stats_24h['critical'] ?? 0)) ?></span>
    </div>
</div>

<!-- Toolbar -->
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-end mb-6 gap-4">
    <div>
        <h2 class="font-bold text-black uppercase tracking-widest text-lg">Riwayat Event</h2>
        <p class="text-[10px] font-bold text-gray-600 uppercase mt-1">Total Tercatat: <?= number_format((int) ($total_logs ?? 0)) ?> Entri</p>
    </div>

    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
        <form method="GET" action="<?= base_url('admin/security-logs') ?>" class="flex border-4 border-black bg-white shadow-[4px_4px_0px_rgba(0,0,0,1)]">
            <?php if (!empty($severity_now)): ?>
                <input type="hidden" name="severity" value="<?= esc($severity_now) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= esc($search_q ?? '') ?>" placeholder="Cari event/email/IP..."
                class="px-3 py-2 text-xs font-bold outline-none bg-white w-44 sm:w-52">
            <button type="submit" class="px-3 py-2 bg-black text-white text-xs font-bold uppercase hover:bg-blue-600">CARI</button>
        </form>

        <div class="flex border-4 border-black bg-white shadow-[4px_4px_0px_rgba(0,0,0,1)] text-xs font-bold uppercase">
            <a href="<?= base_url('admin/security-logs') ?>" class="px-3 py-2 border-r-4 border-black <?= empty($severity_now) ? 'bg-black text-white' : 'text-black hover:bg-gray-200' ?>">Semua</a>
            <a href="<?= base_url('admin/security-logs?severity=info') ?>" class="px-3 py-2 border-r-4 border-black <?= ($severity_now ?? '') === 'info' ? 'bg-blue-600 text-white' : 'text-black hover:bg-gray-200' ?>">Info</a>
            <a href="<?= base_url('admin/security-logs?severity=warning') ?>" class="px-3 py-2 border-r-4 border-black <?= ($severity_now ?? '') === 'warning' ? 'bg-yellow-400 text-black' : 'text-black hover:bg-gray-200' ?>">Warning</a>
            <a href="<?= base_url('admin/security-logs?severity=critical') ?>" class="px-3 py-2 <?= ($severity_now ?? '') === 'critical' ? 'bg-red-600 text-white' : 'text-black hover:bg-gray-200' ?>">Critical</a>
        </div>

        <form method="POST" action="<?= base_url('admin/security-logs/clear') ?>" onsubmit="return confirm('Hapus log keamanan yang berumur lebih dari 7 hari?');">
            <?= csrf_field() ?>
            <button type="submit" class="bg-red-600 hover:bg-red-800 text-white px-3 py-2.5 text-[10px] font-pixel uppercase border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                BERSIHKAN (&gt;7 HARI)
            </button>
        </form>
    </div>
</div>

<!-- Table -->
<div class="bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)] border-4 border-black mb-12">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-black">
            <thead class="bg-gray-200 border-b-4 border-black text-[11px] text-black uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-4 py-3 border-r-4 border-black">Waktu</th>
                    <th class="px-4 py-3 border-r-4 border-black">Level</th>
                    <th class="px-4 py-3 border-r-4 border-black">Event</th>
                    <th class="px-4 py-3 border-r-4 border-black">IP Address</th>
                    <th class="px-4 py-3 border-r-4 border-black">Email / Aktor</th>
                    <th class="px-4 py-3">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black font-bold">
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $sev = (string) ($log['severity'] ?? 'info');
                        $sevClass = match ($sev) {
                            'critical' => 'bg-red-600 text-white',
                            'warning'  => 'bg-yellow-300 text-black',
                            default    => 'bg-blue-600 text-white',
                        };
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-r-4 border-black whitespace-nowrap text-[11px] font-mono">
                                <?= esc(date('d M Y H:i:s', strtotime((string) $log['created_at']))) ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black whitespace-nowrap">
                                <span class="<?= $sevClass ?> px-2 py-1 text-[9px] font-pixel border-2 border-black uppercase shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                    <?= strtoupper(esc($sev)) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black whitespace-nowrap font-mono text-[11px] text-black">
                                <?= esc($log['event_type']) ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black whitespace-nowrap font-mono text-[11px] text-blue-700">
                                <?= esc($log['ip_address']) ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black text-[11px]">
                                <?= !empty($log['actor_email']) ? esc($log['actor_email']) : '<span class="text-gray-400">-</span>' ?>
                            </td>
                            <td class="px-4 py-3 text-[11px] text-gray-800">
                                <?= esc($log['detail'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 font-bold uppercase tracking-widest">
                            [!] Belum ada catatan event keamanan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
        $currPage  = (int) ($page ?? 1);
        $pageCount = (int) ($page_count ?? 1);
        $baseQuery = array_filter(['severity' => $severity_now ?? '', 'q' => $search_q ?? '']);
    ?>
    <?php if ($pageCount > 1): ?>
        <div class="p-4 border-t-4 border-black bg-gray-100 flex flex-wrap justify-between items-center gap-3 text-xs font-bold uppercase">
            <span>Halaman <?= $currPage ?> dari <?= $pageCount ?></span>
            <div class="flex gap-2">
                <?php if ($currPage > 1): ?>
                    <a href="<?= base_url('admin/security-logs?' . http_build_query(array_merge($baseQuery, ['page' => $currPage - 1]))) ?>" class="px-3 py-2 bg-white hover:bg-black hover:text-white border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">&lt; PREV</a>
                <?php endif; ?>
                <?php if ($currPage < $pageCount): ?>
                    <a href="<?= base_url('admin/security-logs?' . http_build_query(array_merge($baseQuery, ['page' => $currPage + 1]))) ?>" class="px-3 py-2 bg-white hover:bg-black hover:text-white border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">NEXT &gt;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
