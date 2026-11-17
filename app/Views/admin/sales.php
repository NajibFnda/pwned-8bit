<?php
/**
 * @var float $total_pendapatan
 * @var array $pendapatan_bulanan
 * @var array $real_transactions
 */
?>
<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-8 border-b-4 border-black pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-pixel text-black uppercase">Data Penjualan</h1>
        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">> Riwayat transaksi dan ringkasan pendapatan sistem.</p>
    </div>
    <a href="<?= base_url('admin/export-csv') ?>" class="bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-4 py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
        EXPORT CSV
    </a>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="bg-white border-4 border-black p-4 mb-6 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3">
        <span class="text-xl font-pixel">!</span>
        <span class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('pesan')) ?></span>
    </div>
<?php endif; ?>

<div class="bg-black text-white border-4 border-black shadow-[6px_6px_0px_rgba(100,100,100,1)] p-6 mb-8 max-w-sm relative">
    <div class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-3">> TOTAL PENDAPATAN (PAID)</div>
    <div class="text-2xl font-pixel text-blue-400">
        Rp <?= number_format($total_pendapatan ?? 0, 0, ',', '.') ?>
    </div>
</div>

<div class="bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)] border-4 border-black mb-12">
    <div class="px-6 py-4 border-b-4 border-black bg-gray-200">
        <h2 class="font-pixel text-black text-sm uppercase">Riwayat Transaksi</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-black font-bold">
            <thead class="bg-gray-200 border-b-4 border-black text-xs text-black uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-6 py-4 border-r-4 border-black">No</th>
                    <th class="px-6 py-4 border-r-4 border-black">Nama User</th>
                    <th class="px-6 py-4 border-r-4 border-black">Paket</th>
                    <th class="px-6 py-4 border-r-4 border-black">Durasi</th>
                    <th class="px-6 py-4 border-r-4 border-black">Harga</th>
                    <th class="px-6 py-4 border-r-4 border-black">Status</th>
                    <th class="px-6 py-4">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black">
                <?php if (empty($real_transactions)): ?>
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400 font-bold uppercase">[!] Belum ada transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($real_transactions as $i => $trx): ?>
                        <?php
                            $st = strtolower((string) ($trx['status'] ?? 'pending'));
                            $isPaid = in_array($st, ['paid', 'settlement'], true);
                            $isTahunan = ($trx['mode'] ?? '') === 'tahunan' || $trx['harga'] >= 250000;
                        ?>
                        <tr class="hover:bg-gray-100 transition-none">
                            <td class="px-6 py-4 text-gray-500 font-pixel text-xs border-r-4 border-black"><?= $i + 1 ?></td>
                            <td class="px-6 py-4 uppercase tracking-widest border-r-4 border-black"><?= esc($trx['nama']) ?></td>
                            <td class="px-6 py-4 border-r-4 border-black">
                                <span class="px-2 py-1 text-[10px] font-pixel uppercase border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]
                                    <?= $trx['paket'] === 'pro' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white' ?>">
                                    <?= esc($trx['paket']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold uppercase tracking-widest border-r-4 border-black <?= $isTahunan ? 'text-blue-600' : 'text-gray-500' ?>">
                                <?= $isTahunan ? 'Tahunan' : 'Bulanan' ?>
                            </td>
                            <td class="px-6 py-4 font-pixel text-xs text-blue-600 border-r-4 border-black">Rp <?= number_format($trx['harga'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 border-r-4 border-black">
                                <?php if ($isPaid): ?>
                                    <span class="bg-green-200 text-green-800 px-2 py-1 text-[10px] border-2 border-black uppercase">PAID</span>
                                <?php elseif ($st === 'pending'): ?>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="bg-yellow-200 text-yellow-800 px-2 py-1 text-[10px] border-2 border-black uppercase">PENDING</span>
                                        <form action="<?= base_url('admin/sales/verify/' . (int) $trx['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Verifikasi pembayaran transaksi ini dan aktifkan paket user?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white px-2 py-1 text-[9px] font-pixel border-2 border-black uppercase">
                                                ✓ VERIFIKASI
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="bg-red-200 text-red-800 px-2 py-1 text-[10px] border-2 border-black uppercase"><?= esc(strtoupper($st)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 uppercase tracking-widest text-[10px]"><?= date('d M Y, H:i', strtotime($trx['tanggal'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>