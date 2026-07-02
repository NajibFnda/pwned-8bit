<?php
/**
 * @var float $total_pendapatan
 * @var array $pendapatan_bulanan
 * @var array $real_transactions
 */
?>
<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-8 border-b-4 border-black pb-4">
    <h1 class="text-3xl font-pixel text-black uppercase">Data Penjualan</h1>
    <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">> Riwayat transaksi dan ringkasan pendapatan sistem.</p>
</div>

<div class="bg-black text-white border-4 border-black shadow-[6px_6px_0px_rgba(100,100,100,1)] p-6 mb-8 max-w-sm relative">
    <div class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-3">> TOTAL PENDAPATAN KESELURUHAN</div>
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
                    <th class="px-6 py-4">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black">
                <?php if (empty($real_transactions)): ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 font-bold uppercase">[!] Belum ada transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($real_transactions as $i => $trx): ?>
                        <tr class="hover:bg-gray-100 transition-none">
                            <td class="px-6 py-4 text-gray-500 font-pixel text-xs border-r-4 border-black"><?= $i + 1 ?></td>
                            <td class="px-6 py-4 uppercase tracking-widest border-r-4 border-black"><?= esc($trx['nama']) ?></td>
                            <td class="px-6 py-4 border-r-4 border-black">
                                <span class="px-2 py-1 text-[10px] font-pixel uppercase border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]
                                    <?= $trx['paket'] === 'pro' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white' ?>">
                                    <?= esc($trx['paket']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold uppercase tracking-widest border-r-4 border-black <?= $trx['harga'] >= 250000 ? 'text-blue-600' : 'text-gray-500' ?>">
                                <?= $trx['harga'] >= 250000 ? 'Tahunan' : 'Bulanan' ?>
                            </td>
                            <td class="px-6 py-4 font-pixel text-xs text-blue-600 border-r-4 border-black">Rp <?= number_format($trx['harga'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 text-gray-500 uppercase tracking-widest text-[10px]"><?= date('d M Y, H:i', strtotime($trx['tanggal'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>