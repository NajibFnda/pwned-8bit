<?php
/**
 * @var array
 * @var float
 * @var string|null
 */
?>
<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-8 border-b-4 border-black pb-4">
    <h1 class="text-3xl font-pixel text-black uppercase">Manajemen Pengguna</h1>
    <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest">> Atur hak akses dan masa berlaku langganan.</p>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="bg-white border-4 border-black p-4 mb-8 shadow-[4px_4px_0px_rgba(0,0,0,1)] flex items-center gap-3">
        <span class="text-xl">!</span>
        <span class="font-bold text-sm uppercase"><?= session()->getFlashdata('pesan') ?></span>
    </div>
<?php endif; ?>

<div class="flex flex-col lg:flex-row justify-between items-start lg:items-end mb-6 gap-4">
    <div>
        <h2 class="font-bold text-black uppercase tracking-widest text-lg">Daftar Pengguna</h2>
        <p class="text-[10px] font-bold text-gray-600 uppercase mt-1">Total Terdaftar: <?= number_format((int) ($total_users ?? count($users))) ?> Akun</p>
    </div>

    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
        <form method="GET" action="<?= base_url('admin') ?>" class="flex border-4 border-black bg-white shadow-[4px_4px_0px_rgba(0,0,0,1)]">
            <?php if (!empty($filter_aktif)): ?>
                <input type="hidden" name="paket" value="<?= esc($filter_aktif) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= esc($search_q ?? '') ?>" placeholder="Cari nama/email..."
                class="px-3 py-2 text-xs font-bold outline-none bg-white w-44 sm:w-52">
            <button type="submit" class="px-3 py-2 bg-black text-white text-xs font-bold uppercase hover:bg-blue-600">CARI</button>
        </form>

        <div class="flex border-4 border-black bg-white shadow-[4px_4px_0px_rgba(0,0,0,1)] text-xs font-bold uppercase">
            <a href="<?= base_url('admin') ?>" class="px-3 py-2 border-r-4 border-black <?= empty($filter_aktif) ? 'bg-black text-white' : 'text-black hover:bg-gray-200' ?>">Semua</a>
            <a href="<?= base_url('admin?paket=free') ?>" class="px-3 py-2 border-r-4 border-black <?= (isset($filter_aktif) && $filter_aktif == 'free') ? 'bg-gray-300 text-black' : 'text-black hover:bg-gray-200' ?>">Free</a>
            <a href="<?= base_url('admin?paket=plus') ?>" class="px-3 py-2 border-r-4 border-black <?= (isset($filter_aktif) && $filter_aktif == 'plus') ? 'bg-blue-600 text-white' : 'text-black hover:bg-gray-200' ?>">Plus</a>
            <a href="<?= base_url('admin?paket=pro') ?>" class="px-3 py-2 <?= (isset($filter_aktif) && $filter_aktif == 'pro') ? 'bg-red-600 text-white' : 'text-black hover:bg-gray-200' ?>">Pro</a>
        </div>

        <form method="POST" action="<?= base_url('admin/users/cleanup-spam') ?>" onsubmit="return confirm('Bersihkan semua akun spam bot (free tanpa transaksi & pola acak)? Akun normal & pro tidak akan terhapus.');">
            <?= csrf_field() ?>
            <button type="submit" class="bg-red-600 hover:bg-red-800 text-white px-3 py-2.5 text-[10px] font-pixel uppercase border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                HAPUS SPAM BOT
            </button>
        </form>
    </div>
</div>
 
<div class="bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)] border-4 border-black mb-12">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-black">
            <thead class="bg-gray-200 border-b-4 border-black text-xs text-black uppercase font-bold tracking-wider">
                <tr>
                    <th class="px-6 py-4 border-r-4 border-black">ID</th>
                    <th class="px-6 py-4 border-r-4 border-black">Informasi Akun</th>
                    <th class="px-6 py-4 border-r-4 border-black">Langganan</th>
                    <th class="px-6 py-4 border-r-4 border-black">Batas Waktu</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y-4 divide-black font-bold">
                <?php if (!empty($users)): ?>
                    <?php
                        $currPageNum   = isset($pager) ? $pager->getCurrentPage('users') : 1;
                        $perPageNum    = (int) ($per_page ?? 25);
                        $filteredCount = (int) ($filtered_total ?? count($users));
                        $rowOffset     = ($currPageNum - 1) * $perPageNum;
                    ?>
                    <?php foreach ($users as $idx => $user): ?>
                        <?php $displayNo = max(1, $filteredCount - $rowOffset - $idx); ?>
                        <tr class="hover:bg-gray-100 transition-none">
                            <td class="px-6 py-4 font-pixel text-xs border-r-4 border-black" title="DB ID #<?= (int) $user['id'] ?>">#<?= $displayNo ?></td>
                            <td class="px-6 py-4 border-r-4 border-black">
                                <div class="text-black uppercase tracking-widest"><?= esc($user['nama'] ?? 'User') ?></div>
                                <div class="text-[10px] text-blue-600 uppercase mt-1"><?= esc($user['email']) ?></div>
                            </td>

                            <!-- Kolom Langganan -->
                            <td class="px-6 py-4 border-r-4 border-black">
                                <?php
                                $planNow = $user['subscription_plan'] ?? 'free';

                                $badgeClass = [
                                    'pro'  => 'bg-red-600 text-white',
                                    'plus' => 'bg-blue-600 text-white',
                                    'free' => 'bg-gray-300 text-black',
                                ];
                                ?>
                                <span class="<?= $badgeClass[$planNow] ?? 'bg-gray-300 text-black' ?> px-2 py-1 text-[10px] font-pixel border-2 border-black uppercase tracking-widest shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                    <?= strtoupper($planNow) ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 text-gray-600 border-r-4 border-black uppercase tracking-widest text-[10px]">
                                <?= !empty($user['expire_date']) ? date('d M Y', strtotime($user['expire_date'])) : '<span class="text-gray-400">Selamanya</span>' ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        onclick="openModal(<?= $user['id'] ?>, '<?= $user['subscription_plan'] ?>', '<?= $user['expire_date'] ?>', '<?= esc($user['email']) ?>')"
                                        class="bg-white text-black hover:bg-black hover:text-white px-3 py-2 text-[10px] font-pixel uppercase transition-none border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                        EDIT
                                    </button>
                                    <?php if (($user['role'] ?? 'user') !== 'admin' && (int) $user['id'] !== (int) session()->get('id')): ?>
                                        <form method="POST" action="<?= base_url('admin/users/delete/' . (int) $user['id']) ?>" onsubmit="return confirm('Hapus akun <?= esc($user['email']) ?>?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="bg-red-600 hover:bg-red-800 text-white px-3 py-2 text-[10px] font-pixel uppercase transition-none border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                                HAPUS
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-bold uppercase tracking-widest">
                            [!] Belum ada data pengguna yang sesuai.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
        $currPage  = isset($pager) ? $pager->getCurrentPage('users') : 1;
        $pageCount = isset($pager) ? $pager->getPageCount('users') : 1;
        $baseQuery = array_filter(['paket' => $filter_aktif ?? '', 'q' => $search_q ?? '']);
    ?>
    <?php if ($pageCount > 1): ?>
        <div class="p-4 border-t-4 border-black bg-gray-100 flex flex-wrap justify-between items-center gap-3 text-xs font-bold uppercase">
            <span>Halaman <?= $currPage ?> dari <?= $pageCount ?></span>
            <div class="flex gap-2">
                <?php if ($currPage > 1): ?>
                    <a href="<?= base_url('admin?' . http_build_query(array_merge($baseQuery, ['page_users' => $currPage - 1]))) ?>" class="px-3 py-2 bg-white hover:bg-black hover:text-white border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">&lt; PREV</a>
                <?php endif; ?>
                <?php if ($currPage < $pageCount): ?>
                    <a href="<?= base_url('admin?' . http_build_query(array_merge($baseQuery, ['page_users' => $currPage + 1]))) ?>" class="px-3 py-2 bg-white hover:bg-black hover:text-white border-2 border-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">NEXT &gt;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>


<div id="editModal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex justify-center items-center p-4">
    <div class="bg-white border-[6px] border-black w-full max-w-md p-0 shadow-[12px_12px_0px_rgba(255,255,255,0.2)] relative flex flex-col">
        <div class="bg-blue-600 border-b-[6px] border-black p-2 flex justify-between items-center text-white">
            <div class="font-pixel text-[10px] uppercase tracking-widest pl-2">USER_MANAGER.EXE</div>
            <button onclick="closeModal()" class="w-6 h-6 bg-red-500 border-2 border-black flex items-center justify-center font-bold hover:bg-red-700">X</button>
        </div>

        <form id="formEdit" method="POST" action="" class="p-6 space-y-6 bg-gray-50">
            <?= csrf_field() ?>
            
            <div class="border-4 border-black p-4 mb-4 bg-white flex justify-between items-center">
                <div>
                    <span class="block text-[10px] text-gray-500 uppercase font-bold tracking-widest">> TARGET_USER</span>
                    <span id="modalUserEmail" class="font-pixel text-[10px] text-black mt-2 block">user@email.com</span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> PILIH PAKET</label>
                <div class="relative border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white">
                    <select name="subscription_plan" id="inputPlan" class="w-full px-4 py-3 bg-transparent outline-none font-bold text-black uppercase cursor-pointer appearance-none">
                        <option value="free">FREE</option>
                        <option value="plus">PLUS</option>
                        <option value="pro">PRO</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-black border-l-4 border-black">▼</div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">> BATAS WAKTU (EXPIRE DATE)</label>
                <input type="date" name="expire_date" id="inputExpire" class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black uppercase cursor-pointer">
                <p class="text-[10px] text-gray-500 mt-3 font-bold uppercase tracking-widest border-l-4 border-gray-400 pl-2">> Biarkan kosong untuk paket FREE.</p>
            </div>

            <div class="pt-4 flex gap-4 mt-8">
                <button type="button" onclick="closeModal()" class="w-1/3 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[10px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    BATAL
                </button>
                <button type="submit" class="w-2/3 bg-blue-600 hover:bg-blue-800 border-4 border-black text-white font-pixel text-[10px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    SIMPAN >>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    const formEdit = document.getElementById('formEdit');
    const inputPlan = document.getElementById('inputPlan');
    const inputExpire = document.getElementById('inputExpire');
    const modalUserEmail = document.getElementById('modalUserEmail');

    function openModal(id, plan, expire, email) {
        formEdit.action = "<?= base_url('admin/update-subscription/') ?>" + id;
        modalUserEmail.textContent = email;
        inputPlan.value = plan;
        inputExpire.value = expire && expire !== '0000-00-00 00:00:00' ? expire.split(' ')[0] : '';
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }
</script>

<?= $this->endSection() ?>