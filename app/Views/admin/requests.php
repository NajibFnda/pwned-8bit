<?php
/**
 * Halaman Inbox Requests User — Admin Panel
 * @var array $requests
 * @var array $counts
 */
?>
<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<style>
    @keyframes fadeInUp {
        from { opacity:0; transform:translateY(16px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .notif-row { animation: fadeInUp .35s ease both; }
</style>

<div class="mb-8 border-b-4 border-black pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-pixel text-black uppercase">Inbox Requests</h1>
        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">&gt; Permintaan yang dikirim oleh pengguna.</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="bg-yellow-400 text-black font-pixel text-[9px] px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
            <?= (int) ($counts['pending'] ?? 0) ?> PENDING
        </span>
        <span class="bg-gray-200 text-black font-pixel text-[9px] px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
            <?= count($requests ?? []) ?> TOTAL
        </span>
    </div>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="bg-white border-4 border-black p-4 mb-6 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3">
        <span class="text-xl font-pixel">!</span>
        <span class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('pesan')) ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center">
        <div class="font-pixel text-2xl text-yellow-500"><?= (int) ($counts['pending'] ?? 0) ?></div>
        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-500 mt-1">Menunggu</div>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center">
        <div class="font-pixel text-2xl text-green-600"><?= (int) ($counts['approved'] ?? 0) ?></div>
        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-500 mt-1">Disetujui</div>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center">
        <div class="font-pixel text-2xl text-gray-500"><?= (int) ($counts['rejected'] ?? 0) ?></div>
        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-500 mt-1">Ditolak</div>
    </div>
</div>

<div class="space-y-4">
    <?php
    $jenisLabel = [
        'upgrade_plus'   => ['label' => 'Upgrade -> PLUS',   'color' => 'text-blue-600'],
        'upgrade_pro'    => ['label' => 'Upgrade -> PRO',    'color' => 'text-red-600'],
        'downgrade_free' => ['label' => 'Downgrade -> FREE', 'color' => 'text-gray-600'],
        'other'          => ['label' => 'Lainnya / Laporan', 'color' => 'text-gray-700'],
    ];
    $planColor = [
        'pro'  => 'bg-red-600 text-white',
        'plus' => 'bg-blue-600 text-white',
        'free' => 'bg-gray-300 text-black',
    ];
    ?>

    <?php if (empty($requests)): ?>
        <div class="bg-white border-4 border-black p-12 text-center shadow-[4px_4px_0px_rgba(0,0,0,1)]">
            <p class="font-bold text-gray-500 uppercase tracking-widest">[!] Belum ada permintaan dari pengguna.</p>
        </div>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
            <?php
                $planNow = $req['plan_now'] ?? 'free';
                $jenis   = $req['jenis_request'] ?? 'other';
                $status  = $req['status'] ?? 'pending';
            ?>
            <div class="notif-row bg-white border-4 <?= $status === 'pending' ? 'border-yellow-400' : 'border-black' ?> shadow-[4px_4px_0px_rgba(0,0,0,1)] relative overflow-hidden">
                <div class="p-5 flex flex-col md:flex-row md:items-start gap-5">
                    <div class="flex-shrink-0 w-12 h-12 bg-gray-200 border-4 border-black flex items-center justify-center font-pixel text-sm text-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                        <?= esc(strtoupper(substr($req['nama'] ?? 'U', 0, 1))) ?>
                    </div>

                    <div class="flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-bold text-black uppercase tracking-widest text-sm"><?= esc($req['nama'] ?? 'User') ?></span>
                            <span class="text-[10px] text-blue-600 font-bold"><?= esc($req['email'] ?? '-') ?></span>
                            <span class="<?= $planColor[$planNow] ?? 'bg-gray-300 text-black' ?> text-[9px] font-pixel px-2 py-0.5 border-2 border-black uppercase">
                                <?= esc(strtoupper($planNow)) ?>
                            </span>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest ml-auto">
                                #<?= (int) $req['id'] ?> &bull; <?= !empty($req['created_at']) ? date('d M Y, H:i', strtotime($req['created_at'])) : '-' ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">REQUEST:</span>
                            <span class="text-xs font-bold uppercase <?= $jenisLabel[$jenis]['color'] ?? 'text-gray-700' ?> tracking-widest">
                                <?= esc($jenisLabel[$jenis]['label'] ?? strtoupper($jenis)) ?>
                            </span>
                        </div>

                        <?php if (!empty($req['pesan'])): ?>
                            <div class="bg-gray-100 border-l-4 border-gray-400 px-3 py-2 text-xs text-gray-700 font-bold">
                                "<?= esc($req['pesan']) ?>"
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-shrink-0 flex flex-col items-end gap-2">
                        <?php if ($status === 'pending'): ?>
                            <span class="bg-yellow-400 text-black font-pixel text-[9px] px-3 py-1 border-2 border-black uppercase shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                PENDING
                            </span>
                            <div class="flex gap-2 mt-1">
                                <button type="button" onclick="showAction(<?= (int) $req['id'] ?>, 'approve')"
                                    class="bg-green-600 hover:bg-green-800 text-white px-3 py-2 text-[9px] font-pixel uppercase border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)] transition-none">
                                    ✓ APPROVE
                                </button>
                                <button type="button" onclick="showAction(<?= (int) $req['id'] ?>, 'reject')"
                                    class="bg-red-600 hover:bg-red-800 text-white px-3 py-2 text-[9px] font-pixel uppercase border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)] transition-none">
                                    ✗ REJECT
                                </button>
                            </div>
                        <?php elseif ($status === 'approved'): ?>
                            <span class="bg-green-600 text-white font-pixel text-[9px] px-3 py-1 border-2 border-black uppercase shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                ✓ APPROVED
                            </span>
                        <?php else: ?>
                            <span class="bg-gray-500 text-white font-pixel text-[9px] px-3 py-1 border-2 border-black uppercase shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                ✗ REJECTED
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="actionModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(0,0,0,0.75)">
    <div class="bg-white border-[6px] border-black w-full max-w-sm shadow-[12px_12px_0px_rgba(0,0,0,0.4)]">
        <div id="actionModalHeader" class="border-b-[6px] border-black px-5 py-3 flex items-center justify-between">
            <span class="font-pixel text-[10px] text-white uppercase tracking-widest">KONFIRMASI AKSI</span>
            <button type="button" onclick="closeActionModal()" class="w-6 h-6 bg-red-500 border-2 border-black text-white font-bold flex items-center justify-center text-xs">✕</button>
        </div>
        <form id="actionForm" method="POST" action="" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <p id="actionModalText" class="text-sm font-bold text-black uppercase tracking-widest"></p>
            <div class="flex gap-3">
                <button type="button" onclick="closeActionModal()" class="w-1/2 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[9px] py-3 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    BATAL
                </button>
                <button type="submit" id="confirmBtn" class="w-1/2 border-4 border-black text-white font-pixel text-[9px] py-3 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    KONFIRMASI
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function showAction(id, action) {
        const modal  = document.getElementById('actionModal');
        const header = document.getElementById('actionModalHeader');
        const text   = document.getElementById('actionModalText');
        const btn    = document.getElementById('confirmBtn');
        const form   = document.getElementById('actionForm');

        if (action === 'approve') {
            form.action      = "<?= base_url('admin/requests/approve/') ?>" + id;
            header.className = 'border-b-[6px] border-black px-5 py-3 flex items-center justify-between bg-green-600';
            text.textContent = 'Setujui request #' + id + '? Paket pengguna akan diperbarui jika terkait langganan.';
            btn.className    = 'w-1/2 border-4 border-black text-white font-pixel text-[9px] py-3 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-green-600 hover:bg-green-800';
            btn.textContent  = '✓ APPROVE';
        } else {
            form.action      = "<?= base_url('admin/requests/reject/') ?>" + id;
            header.className = 'border-b-[6px] border-black px-5 py-3 flex items-center justify-between bg-red-600';
            text.textContent = 'Tolak request #' + id + '? Tindakan ini tidak bisa dibatalkan.';
            btn.className    = 'w-1/2 border-4 border-black text-white font-pixel text-[9px] py-3 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-red-600 hover:bg-red-800';
            btn.textContent  = '✗ REJECT';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeActionModal() {
        document.getElementById('actionModal').classList.add('hidden');
        document.getElementById('actionModal').classList.remove('flex');
    }
</script>

<?= $this->endSection() ?>
