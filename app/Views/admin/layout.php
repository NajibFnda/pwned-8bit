<?php
/**
 * @var string $title
 * @var string $active
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin' ?> - PWNED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-[url('<?= base_url('image/background_retro.png') ?>')] bg-repeat font-sans">

    <!-- Mobile Topbar -->
    <div class="md:hidden sticky top-0 z-30 bg-blue-600 text-white border-b-4 border-black px-4 py-3 flex items-center justify-between">
        <div>
            <span class="text-base font-pixel tracking-wider">PWNED</span>
            <span class="text-[9px] ml-2 uppercase font-bold tracking-widest">[ADMIN]</span>
        </div>
        <button type="button" onclick="toggleAdminSidebar()" class="bg-black text-white font-pixel text-[9px] px-3 py-2 border-2 border-white uppercase">
            MENU
        </button>
    </div>

    <div id="admin-sidebar-backdrop" onclick="toggleAdminSidebar()" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>

    <aside id="admin-sidebar" class="w-64 fixed top-0 left-0 h-screen bg-white border-r-4 border-black flex flex-col z-40 -translate-x-full md:translate-x-0 transition-transform duration-200">

        <div class="flex-shrink-0 px-6 py-5 border-b-4 border-black bg-blue-600 text-white">
            <span class="text-xl font-pixel tracking-wider">PWNED</span>
            <p class="text-[10px] mt-2 tracking-widest uppercase font-bold">> Admin Panel</p>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-4 font-bold uppercase text-sm">

            <!-- USERS -->
            <a href="<?= base_url('admin') ?>"
               class="flex items-center gap-3 px-4 py-3 border-4 transition-none shadow-[4px_4px_0px_rgba(0,0,0,1)]
                      <?= ($active ?? '') === 'users' ? 'bg-black text-white border-black' : 'bg-white border-black text-black hover:bg-gray-200' ?>">
                <span>[U]</span> USERS
            </a>

            <!-- SALES -->
            <a href="<?= base_url('admin/sales') ?>"
               class="flex items-center gap-3 px-4 py-3 border-4 transition-none shadow-[4px_4px_0px_rgba(0,0,0,1)]
                      <?= ($active ?? '') === 'sales' ? 'bg-black text-white border-black' : 'bg-white border-black text-black hover:bg-gray-200' ?>">
                <span>[S]</span> SALES
            </a>

            <!-- REQUESTS -->
            <a href="<?= base_url('admin/requests') ?>"
               class="flex items-center justify-between gap-2 px-4 py-3 border-4 transition-none shadow-[4px_4px_0px_rgba(0,0,0,1)]
                      <?= ($active ?? '') === 'requests' ? 'bg-black text-white border-black' : 'bg-white border-black text-black hover:bg-gray-200' ?>">
                <span class="flex items-center gap-3"><span>[R]</span> REQUESTS</span>
                <span id="notif-badge" class="hidden bg-red-600 text-white font-pixel text-[8px] px-1.5 py-0.5 border border-black">0</span>
            </a>

            <!-- REDEEM CODES -->
            <a href="<?= base_url('admin/redeem') ?>"
               class="flex items-center gap-3 px-4 py-3 border-4 transition-none shadow-[4px_4px_0px_rgba(0,0,0,1)]
                      <?= ($active ?? '') === 'redeem' ? 'bg-black text-white border-black' : 'bg-white border-black text-black hover:bg-gray-200' ?>">
                <span>[K]</span> REDEEM CODE
            </a>

            <!-- SECURITY LOGS -->
            <a href="<?= base_url('admin/security-logs') ?>"
               class="flex items-center gap-3 px-4 py-3 border-4 transition-none shadow-[4px_4px_0px_rgba(0,0,0,1)]
                      <?= ($active ?? '') === 'security_logs' ? 'bg-black text-white border-black' : 'bg-white border-black text-black hover:bg-gray-200' ?>">
                <span>[L]</span> SECURITY LOG
            </a>

        </nav>

        <div class="flex-shrink-0 px-4 py-6 border-t-4 border-black space-y-4 font-bold uppercase text-xs bg-white">
            <a href="<?= base_url('/') ?>"
               class="flex items-center gap-3 px-4 py-3 bg-gray-300 border-4 border-black hover:bg-gray-400 text-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                <span>&lt;</span> HOME
            </a>
            <a href="<?= base_url('logout') ?>"
               class="flex items-center gap-3 px-4 py-3 bg-red-600 border-4 border-black hover:bg-red-800 text-white shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                <span>[X]</span> LOGOUT
            </a>
        </div>
    </aside>

    <main class="md:ml-64 p-4 sm:p-6 md:p-8 min-h-screen">
        <?= $this->renderSection('content') ?>
    </main>

    <script>
        function toggleAdminSidebar() {
            const sb = document.getElementById('admin-sidebar');
            const bd = document.getElementById('admin-sidebar-backdrop');
            if (sb && bd) {
                sb.classList.toggle('-translate-x-full');
                bd.classList.toggle('hidden');
            }
        }

        // Polling badge notifikasi pending setiap 30 detik
        const notifBadge = document.getElementById('notif-badge');

        function fetchNotifCount() {
            if (document.hidden) return;
            fetch('<?= base_url('admin/notif-count') ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.count > 0) {
                        notifBadge.textContent = data.count;
                        notifBadge.classList.remove('hidden');
                    } else {
                        notifBadge.classList.add('hidden');
                    }
                })
                .catch(() => {});
        }

        fetchNotifCount();
        setInterval(fetchNotifCount, 60000);
    </script>

</body>
</html>