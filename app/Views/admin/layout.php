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

    <aside class="w-64 fixed top-0 left-0 h-screen bg-white border-r-4 border-black flex flex-col z-40">

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

    <main class="ml-64 p-8 min-h-screen">
        <?= $this->renderSection('content') ?>
    </main>

    <script>
        // Polling badge notifikasi pending setiap 30 detik
        const notifBadge = document.getElementById('notif-badge');

        function fetchNotifCount() {
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
        setInterval(fetchNotifCount, 30000);
    </script>

</body>
</html>