<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-8 border-b-4 border-black pb-4">
    <h1 class="text-2xl sm:text-3xl font-pixel text-black uppercase">Manajemen Kode Redeem</h1>
    <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">&gt; Buat kode voucher yang dapat diklaim user di halaman Upgrade untuk mengaktifkan PLUS / PRO.</p>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="bg-green-100 border-4 border-black p-4 mb-6 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3 text-green-900">
        <span class="font-pixel text-xs">[OK]</span>
        <span class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('pesan')) ?></span>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="bg-red-600 border-4 border-black p-4 mb-6 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3 text-white">
        <span class="font-pixel text-xs">[!]</span>
        <span class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Form Buat Kode Redeem Baru -->
<div class="bg-white border-4 border-black p-6 shadow-[6px_6px_0px_rgba(0,0,0,1)] mb-10">
    <h2 class="font-pixel text-xs text-black uppercase mb-5 border-b-2 border-dashed border-gray-400 pb-3">&gt; BUAT KODE REDEEM BARU</h2>

    <form method="POST" action="<?= base_url('admin/redeem/create') ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <?= csrf_field() ?>

        <div>
            <label for="code" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; KODE (OPSIONAL)</label>
            <input type="text" id="code" name="code" maxlength="32" placeholder="Kosong = Auto Generate"
                class="w-full px-3 py-2.5 bg-white border-4 border-black font-mono font-bold text-xs uppercase placeholder-gray-400 outline-none focus:bg-gray-100">
        </div>

        <div>
            <label for="plan" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; PAKET</label>
            <select id="plan" name="plan" class="w-full px-3 py-2.5 bg-white border-4 border-black font-bold text-xs uppercase outline-none">
                <option value="plus">PLUS (50 Cek/Hari)</option>
                <option value="pro">PRO (Unlimited)</option>
            </select>
        </div>

        <div>
            <label for="duration_days" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; DURASI (HARI)</label>
            <input type="number" id="duration_days" name="duration_days" value="30" min="1" max="3650" required
                class="w-full px-3 py-2.5 bg-white border-4 border-black font-bold text-xs outline-none focus:bg-gray-100">
        </div>

        <div>
            <label for="max_uses" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; MAKS KLAIM</label>
            <input type="number" id="max_uses" name="max_uses" value="1" min="1" max="10000" required
                class="w-full px-3 py-2.5 bg-white border-4 border-black font-bold text-xs outline-none focus:bg-gray-100">
        </div>

        <div>
            <label for="expires_at" class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; KADALUWARSA (OPSIONAL)</label>
            <input type="date" id="expires_at" name="expires_at"
                class="w-full px-3 py-2.5 bg-white border-4 border-black font-bold text-xs outline-none focus:bg-gray-100">
        </div>

        <div class="md:col-span-2 lg:col-span-5 flex justify-end pt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-6 py-3.5 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                + BUAT KODE REDEEM
            </button>
        </div>
    </form>
</div>

<!-- Daftar Kode Redeem -->
<div class="bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)] border-4 border-black mb-10">
    <div class="px-6 py-4 bg-gray-100 border-b-4 border-black flex justify-between items-center">
        <h2 class="font-bold text-black uppercase tracking-widest text-sm">Daftar Kode Redeem (<?= count($codes ?? []) ?>)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-black">
            <thead class="bg-gray-200 border-b-4 border-black uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-4 py-3 border-r-4 border-black">No</th>
                    <th class="px-4 py-3 border-r-4 border-black">Kode</th>
                    <th class="px-4 py-3 border-r-4 border-black">Paket & Durasi</th>
                    <th class="px-4 py-3 border-r-4 border-black">Pemakaian</th>
                    <th class="px-4 py-3 border-r-4 border-black">Batas Kode</th>
                    <th class="px-4 py-3 border-r-4 border-black">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black font-bold">
                <?php if (!empty($codes)): ?>
                    <?php $totalCodes = count($codes); ?>
                    <?php foreach ($codes as $idx => $c): ?>
                        <?php
                            $isExpired = !empty($c['expires_at']) && strtotime((string) $c['expires_at']) < time();
                            $isFull    = (int) $c['used_count'] >= (int) $c['max_uses'];
                            $isActive  = (int) $c['is_active'] === 1;

                            if (!$isActive) {
                                $statusText = 'NONAKTIF';
                                $statusCls  = 'bg-gray-300 text-black';
                            } elseif ($isExpired) {
                                $statusText = 'EXPIRED';
                                $statusCls  = 'bg-yellow-300 text-black';
                            } elseif ($isFull) {
                                $statusText = 'HABIS';
                                $statusCls  = 'bg-orange-400 text-black';
                            } else {
                                $statusText = 'AKTIF';
                                $statusCls  = 'bg-green-600 text-white';
                            }

                            $planCls = ($c['plan'] === 'pro') ? 'bg-red-600 text-white' : 'bg-blue-600 text-white';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-r-4 border-black font-pixel text-[10px]">#<?= $totalCodes - $idx ?></td>
                            <td class="px-4 py-3 border-r-4 border-black font-mono text-sm">
                                <div class="flex items-center gap-2">
                                    <span><?= esc($c['code']) ?></span>
                                    <button type="button" onclick="navigator.clipboard?.writeText('<?= esc($c['code']) ?>'); this.textContent='COPIED!'; setTimeout(()=>this.textContent='COPY',1200);"
                                        class="px-2 py-1 text-[9px] bg-gray-200 hover:bg-black hover:text-white border-2 border-black uppercase">
                                        COPY
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black">
                                <span class="<?= $planCls ?> px-2 py-1 text-[9px] font-pixel border-2 border-black uppercase"><?= strtoupper(esc($c['plan'])) ?></span>
                                <span class="ml-2 uppercase text-[11px]"><?= (int) $c['duration_days'] ?> Hari</span>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black font-mono">
                                <?= (int) $c['used_count'] ?> / <?= (int) $c['max_uses'] ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black text-[11px] uppercase">
                                <?= !empty($c['expires_at']) ? esc(date('d M Y', strtotime((string) $c['expires_at']))) : '<span class="text-gray-400">Tanpa Batas</span>' ?>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black">
                                <span class="<?= $statusCls ?> px-2 py-1 text-[9px] font-pixel border-2 border-black uppercase"><?= $statusText ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form method="POST" action="<?= base_url('admin/redeem/toggle/' . (int) $c['id']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2.5 py-1.5 bg-white hover:bg-black hover:text-white border-2 border-black text-[9px] font-pixel uppercase">
                                            <?= $isActive ? 'MATIKAN' : 'AKTIFKAN' ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= base_url('admin/redeem/delete/' . (int) $c['id']) ?>" onsubmit="return confirm('Hapus kode redeem <?= esc($c['code']) ?>?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-800 text-white border-2 border-black text-[9px] font-pixel uppercase">
                                            HAPUS
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500 uppercase tracking-widest">
                            [!] Belum ada kode redeem yang dibuat.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Riwayat Klaim Terbaru -->
<div class="bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)] border-4 border-black mb-12">
    <div class="px-6 py-4 bg-gray-100 border-b-4 border-black">
        <h2 class="font-bold text-black uppercase tracking-widest text-sm">Riwayat Klaim User Terbaru</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-black">
            <thead class="bg-gray-200 border-b-4 border-black uppercase font-bold">
                <tr>
                    <th class="px-4 py-3 border-r-4 border-black">Waktu Klaim</th>
                    <th class="px-4 py-3 border-r-4 border-black">User</th>
                    <th class="px-4 py-3 border-r-4 border-black">Kode</th>
                    <th class="px-4 py-3">Hadiah Paket</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black font-bold">
                <?php if (!empty($claims)): ?>
                    <?php foreach ($claims as $cl): ?>
                        <tr>
                            <td class="px-4 py-3 border-r-4 border-black font-mono"><?= esc(date('d M Y H:i', strtotime((string) $cl['claimed_at']))) ?></td>
                            <td class="px-4 py-3 border-r-4 border-black">
                                <div class="uppercase"><?= esc($cl['nama']) ?></div>
                                <div class="text-[10px] text-blue-600"><?= esc($cl['email']) ?></div>
                            </td>
                            <td class="px-4 py-3 border-r-4 border-black font-mono"><?= esc($cl['code']) ?></td>
                            <td class="px-4 py-3 uppercase"><?= strtoupper(esc($cl['plan'])) ?> (<?= (int) $cl['duration_days'] ?> Hari)</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 uppercase">[!] Belum ada riwayat klaim kode redeem.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
