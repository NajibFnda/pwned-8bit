<?php
/**
 * Halaman Notifikasi Request User — Admin Panel
 * (Tampilan dummy dengan data hardcoded)
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
    .notif-row:nth-child(1){ animation-delay:.05s }
    .notif-row:nth-child(2){ animation-delay:.10s }
    .notif-row:nth-child(3){ animation-delay:.15s }
    .notif-row:nth-child(4){ animation-delay:.20s }
    .notif-row:nth-child(5){ animation-delay:.25s }
    .notif-row:nth-child(6){ animation-delay:.30s }
</style>

<!-- Header -->
<div class="mb-8 border-b-4 border-black pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-pixel text-black uppercase">Inbox Requests</h1>
        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">> Permintaan yang dikirim oleh pengguna.</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="bg-yellow-400 text-black font-pixel text-[9px] px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] animate-pulse">
            3 PENDING
        </span>
        <span class="bg-gray-200 text-black font-pixel text-[9px] px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
            6 TOTAL
        </span>
    </div>
</div>

<!-- Stats bar -->
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center">
        <div class="font-pixel text-2xl text-yellow-500">3</div>
        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-500 mt-1">Menunggu</div>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center">
        <div class="font-pixel text-2xl text-green-600">2</div>
        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-500 mt-1">Disetujui</div>
    </div>
    <div class="bg-white border-4 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center">
        <div class="font-pixel text-2xl text-gray-500">1</div>
        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-500 mt-1">Ditolak</div>
    </div>
</div>

<!-- Notification Cards -->
<div class="space-y-4">

    <?php
    // ── DUMMY DATA ─────────────────────────────────────────────────────────────
    $dummyRequests = [
        [
            'id'        => 9,
            'nama'      => 'Budi Santoso',
            'email'     => 'budi@gmail.com',
            'plan_now'  => 'free',
            'jenis'     => 'upgrade_pro',
            'pesan'     => 'Saya butuh akses unlimited untuk riset keamanan kampus saya.',
            'waktu'     => '2 menit yang lalu',
            'status'    => 'pending',
            'is_new'    => true,
        ],
        [
            'id'        => 8,
            'nama'      => 'Siti Rahayu',
            'email'     => 'siti.rahayu@yahoo.com',
            'plan_now'  => 'free',
            'jenis'     => 'upgrade_plus',
            'pesan'     => 'Butuh lebih dari 5 pengecekan per hari untuk pekerjaan.',
            'waktu'     => '18 menit yang lalu',
            'status'    => 'pending',
            'is_new'    => true,
        ],
        [
            'id'        => 7,
            'nama'      => 'Ahmad Fauzi',
            'email'     => 'ahmad.f@company.id',
            'plan_now'  => 'plus',
            'jenis'     => 'upgrade_pro',
            'pesan'     => 'Perlu akses PRO untuk tim IT perusahaan kami (10 orang).',
            'waktu'     => '1 jam yang lalu',
            'status'    => 'pending',
            'is_new'    => true,
        ],
        [
            'id'        => 6,
            'nama'      => 'Dewi Lestari',
            'email'     => 'dewilestari@email.com',
            'plan_now'  => 'pro',
            'jenis'     => 'other',
            'pesan'     => 'Saya mengalami error saat mencoba cek email. Mohon bantuannya.',
            'waktu'     => '3 jam yang lalu',
            'status'    => 'approved',
            'is_new'    => false,
        ],
        [
            'id'        => 5,
            'nama'      => 'Rizki Pratama',
            'email'     => 'rizkip@student.ac.id',
            'plan_now'  => 'plus',
            'jenis'     => 'upgrade_pro',
            'pesan'     => '',
            'waktu'     => 'Kemarin, 21:45',
            'status'    => 'approved',
            'is_new'    => false,
        ],
        [
            'id'        => 4,
            'nama'      => 'Linda Kurnia',
            'email'     => 'linda.k@hotmail.com',
            'plan_now'  => 'free',
            'jenis'     => 'upgrade_plus',
            'pesan'     => 'Minta upgrade gratis dong admin hehe.',
            'waktu'     => 'Kemarin, 14:20',
            'status'    => 'rejected',
            'is_new'    => false,
        ],
    ];

    $jenisLabel = [
        'upgrade_plus'   => ['label'=>'⬆ Upgrade → PLUS',   'color'=>'text-blue-600'],
        'upgrade_pro'    => ['label'=>'⬆⬆ Upgrade → PRO',   'color'=>'text-red-600'],
        'downgrade_free' => ['label'=>'⬇ Downgrade → FREE', 'color'=>'text-gray-600'],
        'report'         => ['label'=>'⚠ Laporan Masalah',  'color'=>'text-orange-500'],
        'other'          => ['label'=>'✉ Lainnya',           'color'=>'text-gray-500'],
    ];

    $planColor = [
        'pro'  => 'bg-red-600 text-white',
        'plus' => 'bg-blue-600 text-white',
        'free' => 'bg-gray-300 text-black',
    ];
    ?>

    <?php foreach ($dummyRequests as $i => $req): ?>
    <div class="notif-row bg-white border-4 <?= $req['status'] === 'pending' ? 'border-yellow-400' : 'border-black' ?> shadow-[4px_4px_0px_rgba(0,0,0,1)] relative overflow-hidden">

        <!-- New badge -->
        <?php if ($req['is_new']): ?>
        <div class="absolute top-0 right-0 bg-red-600 text-white font-pixel text-[8px] px-2 py-1 border-l-2 border-b-2 border-black">
            NEW
        </div>
        <?php endif; ?>

        <div class="p-5 flex flex-col md:flex-row md:items-start gap-5">

            <!-- Avatar placeholder -->
            <div class="flex-shrink-0 w-12 h-12 bg-gray-200 border-4 border-black flex items-center justify-center font-pixel text-sm text-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                <?= strtoupper(substr($req['nama'], 0, 1)) ?>
            </div>

            <!-- Content -->
            <div class="flex-1 space-y-2">
                <!-- Top row -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-bold text-black uppercase tracking-widest text-sm"><?= esc($req['nama']) ?></span>
                    <span class="text-[10px] text-blue-600 font-bold"><?= esc($req['email']) ?></span>
                    <span class="<?= $planColor[$req['plan_now']] ?> text-[9px] font-pixel px-2 py-0.5 border-2 border-black uppercase">
                        <?= strtoupper($req['plan_now']) ?>
                    </span>
                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest ml-auto">#<?= $req['id'] ?> &bull; <?= $req['waktu'] ?></span>
                </div>

                <!-- Request type -->
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">REQUEST:</span>
                    <span class="text-xs font-bold uppercase <?= $jenisLabel[$req['jenis']]['color'] ?> tracking-widest">
                        <?= $jenisLabel[$req['jenis']]['label'] ?>
                    </span>
                </div>

                <!-- Message -->
                <?php if (!empty($req['pesan'])): ?>
                <div class="bg-gray-100 border-l-4 border-gray-400 px-3 py-2 text-xs text-gray-600 font-bold italic">
                    "<?= esc($req['pesan']) ?>"
                </div>
                <?php endif; ?>
            </div>

            <!-- Action / Status -->
            <div class="flex-shrink-0 flex flex-col items-end gap-2">
                <?php if ($req['status'] === 'pending'): ?>
                    <span class="bg-yellow-400 text-black font-pixel text-[9px] px-3 py-1 border-2 border-black uppercase shadow-[2px_2px_0px_rgba(0,0,0,1)] animate-pulse">
                        PENDING
                    </span>
                    <div class="flex gap-2 mt-1">
                        <button onclick="showAction(<?= $req['id'] ?>, 'approve')"
                            class="bg-green-600 hover:bg-green-800 text-white px-3 py-2 text-[9px] font-pixel uppercase border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)] transition-none">
                            ✓ APPROVE
                        </button>
                        <button onclick="showAction(<?= $req['id'] ?>, 'reject')"
                            class="bg-red-600 hover:bg-red-800 text-white px-3 py-2 text-[9px] font-pixel uppercase border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)] transition-none">
                            ✗ REJECT
                        </button>
                    </div>
                <?php elseif ($req['status'] === 'approved'): ?>
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

</div>

<!-- Confirm Action Modal -->
<div id="actionModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(0,0,0,0.75)">
    <div class="bg-white border-[6px] border-black w-full max-w-sm shadow-[12px_12px_0px_rgba(0,0,0,0.4)]">
        <div id="actionModalHeader" class="border-b-[6px] border-black px-5 py-3 flex items-center justify-between">
            <span class="font-pixel text-[10px] text-white uppercase tracking-widest">KONFIRMASI AKSI</span>
            <button onclick="closeActionModal()" class="w-6 h-6 bg-red-500 border-2 border-black text-white font-bold flex items-center justify-center text-xs">✕</button>
        </div>
        <div class="p-6 space-y-4">
            <p id="actionModalText" class="text-sm font-bold text-black uppercase tracking-widest"></p>
            <div class="flex gap-3">
                <button onclick="closeActionModal()" class="w-1/2 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[9px] py-3 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    BATAL
                </button>
                <button onclick="confirmAction()" id="confirmBtn" class="w-1/2 border-4 border-black text-white font-pixel text-[9px] py-3 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    KONFIRMASI
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let pendingAction  = null;
    let pendingReqId   = null;

    function showAction(id, action) {
        pendingReqId   = id;
        pendingAction  = action;
        const modal    = document.getElementById('actionModal');
        const header   = document.getElementById('actionModalHeader');
        const text     = document.getElementById('actionModalText');
        const btn      = document.getElementById('confirmBtn');

        if (action === 'approve') {
            header.className = 'border-b-[6px] border-black px-5 py-3 flex items-center justify-between bg-green-600';
            text.textContent = 'Setujui request #' + id + '? Subscription user akan diperbarui otomatis.';
            btn.className    = 'w-1/2 border-4 border-black text-white font-pixel text-[9px] py-3 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-green-600 hover:bg-green-800';
            btn.textContent  = '✓ APPROVE';
        } else {
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

    function confirmAction() {
        // Simulasi: tampilkan alert dan reload (dummy)
        const msg = pendingAction === 'approve'
            ? '✓ Request #' + pendingReqId + ' berhasil DISETUJUI!'
            : '✗ Request #' + pendingReqId + ' telah DITOLAK.';
        closeActionModal();
        // Tampilkan flash banner dummy di atas halaman
        const flash = document.createElement('div');
        flash.className = 'fixed top-4 right-4 z-[9999] bg-white border-4 border-black px-6 py-4 shadow-[6px_6px_0px_rgba(0,0,0,1)] font-bold text-sm uppercase tracking-widest max-w-xs';
        flash.innerHTML = msg + '<br><span class="text-[9px] text-gray-400 font-normal normal-case tracking-normal">(Demo – tidak tersimpan ke database)</span>';
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 3500);
    }
</script>

<?= $this->endSection() ?>
