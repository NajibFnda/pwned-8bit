<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head >
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWNED - Check Your Data Breach</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php if (!empty($turnstile_site_key)): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-black min-h-screen">

    <!-- Frame Mockup -->
    <div class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] bg-repeat border-y-4 border-black flex flex-col relative">

        <!-- Navbar -->
        <nav class="sticky top-0 w-full px-6 py-4 bg-white border-b-4 border-black z-50">
            <div class="flex justify-between items-center gap-4">
                <div class="flex items-center space-x-2">
                    <span class="text-xl font-pixel text-black">PWNED</span>
                </div>
                
                <div id="nav-container" class="relative hidden md:flex items-center justify-center gap-6 text-sm font-bold uppercase">
                    <span id="nav-indicator" aria-hidden="true" class="absolute top-0 left-0 bg-black pointer-events-none opacity-0 z-0" style="transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1), width 320ms cubic-bezier(0.22, 1, 0.36, 1), height 320ms cubic-bezier(0.22, 1, 0.36, 1), opacity 150ms ease;"></span>
                    <a href="#" id="nav-home" class="nav-link relative z-10 text-black hover:text-blue-600 px-2 py-1 transition-colors duration-200">HOME</a>
                    <a href="#cek-section" id="nav-cek" class="nav-link relative z-10 text-black hover:text-blue-600 px-2 py-1 transition-colors duration-200">CHECK EMAIL</a>
                    <a href="#statistik-section" id="nav-statistik" class="nav-link relative z-10 text-black hover:text-blue-600 px-2 py-1 transition-colors duration-200">STATISTICS</a>
                    <a href="#tentang-section" id="nav-tentang" class="nav-link relative z-10 text-black hover:text-blue-600 px-2 py-1 transition-colors duration-200">ABOUT</a>
        
                    <?php if(session()->get('role') === 'admin'): ?>
                    <a href="<?= base_url('admin') ?>" class="relative z-10 text-red-600 hover:bg-red-600 hover:text-white px-2 py-1 transition-colors duration-200">
                        [ADMIN]
                    </a>
                   <?php endif; ?>
                </div>
                
                <div class="flex justify-end items-center gap-3 font-bold uppercase text-sm">
                    <?php if(session()->has('logged_in') && session()->get('logged_in') === true): ?>
                        <div class="hidden sm:flex items-center gap-3 border-r-2 border-black pr-3">
                            <a href="<?= base_url('profile') ?>" class="hover:underline" title="Lihat Profil &amp; Riwayat">
                                USER: <span class="text-blue-600"><?= esc(session()->get('nama') ?? 'User') ?></span>
                            </a>
                            <a href="<?= base_url('logout') ?>" class="text-red-600 hover:bg-red-600 hover:text-white px-2 py-1 transition-none" title="Logout System">
                                LOGOUT
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="<?= base_url('login') ?>" class="hidden sm:inline-block text-black hover:bg-black hover:text-white px-2 py-1 transition-none">
                            LOGIN
                        </a>
                    <?php endif; ?>
                    <?php if(session()->get('role') === 'user'): ?>
                    <a href="<?= base_url('upgrade') ?>" class="bg-blue-600 text-white border-2 border-black hover:bg-white hover:text-blue-600 px-3 py-1 text-xs transition-none shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                        UPGRADE
                    </a>
                    <?php endif; ?>
                    <button type="button" id="mobile-menu-btn" onclick="toggleMobileMenu()" class="md:hidden border-2 border-black bg-gray-100 hover:bg-black hover:text-white px-2 py-1 font-pixel text-[10px]">
                        MENU
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer -->
            <div id="mobile-menu" class="hidden md:hidden mt-4 pt-4 border-t-2 border-dashed border-black flex flex-col gap-2 text-xs font-bold uppercase">
                <a href="#" onclick="toggleMobileMenu()" class="px-2 py-2 hover:bg-black hover:text-white">HOME</a>
                <a href="#cek-section" onclick="toggleMobileMenu()" class="px-2 py-2 hover:bg-black hover:text-white">CHECK EMAIL</a>
                <a href="#statistik-section" onclick="toggleMobileMenu()" class="px-2 py-2 hover:bg-black hover:text-white">STATISTICS</a>
                <a href="#tentang-section" onclick="toggleMobileMenu()" class="px-2 py-2 hover:bg-black hover:text-white">ABOUT</a>
                <button type="button" onclick="toggleMobileMenu(); openContactModal();" class="text-left px-2 py-2 hover:bg-black hover:text-white uppercase">CONTACT ADMIN</button>
                <?php if(session()->get('role') === 'admin'): ?>
                    <a href="<?= base_url('admin') ?>" class="px-2 py-2 text-red-600 hover:bg-red-600 hover:text-white">[ADMIN]</a>
                <?php endif; ?>
                <?php if(session()->has('logged_in') && session()->get('logged_in') === true): ?>
                    <a href="<?= base_url('profile') ?>" class="px-2 py-2 text-blue-600 hover:bg-blue-600 hover:text-white">PROFIL &amp; RIWAYAT</a>
                    <a href="<?= base_url('logout') ?>" class="px-2 py-2 text-red-600 hover:bg-red-600 hover:text-white">LOGOUT (<?= esc(session()->get('nama') ?? 'User') ?>)</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="px-2 py-2 text-blue-600 hover:bg-blue-600 hover:text-white">LOGIN</a>
                <?php endif; ?>
            </div>
        </nav>

        <?php if (!empty($announcement['active']) && trim((string) ($announcement['message'] ?? '')) !== ''): ?>
            <?php
                $annStyle = (string) ($announcement['style'] ?? 'info');
                $annCls = match ($annStyle) {
                    'danger'  => 'bg-red-600 text-white border-black',
                    'warning' => 'bg-yellow-300 text-black border-black',
                    default   => 'bg-blue-600 text-white border-black',
                };
            ?>
            <div class="<?= $annCls ?> border-b-4 px-4 sm:px-6 py-3 flex items-center justify-between gap-4 font-bold text-xs uppercase">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 bg-black text-white font-pixel text-[9px] border border-white"><?= esc($announcement['title'] ?? 'PENGUMUMAN') ?></span>
                    <span><?= esc($announcement['message']) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-base leading-none font-bold hover:opacity-70" aria-label="Tutup pengumuman">&times;</button>
            </div>
        <?php endif; ?>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="m-6 bg-white border-4 border-black text-black px-6 py-4 flex items-center justify-between shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                <div class="flex items-center gap-3">
                    <span class="text-xl">!</span>
                    <p class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('success')) ?></p>
                </div>
                <button onclick="this.parentElement.style.display='none'" class="font-bold hover:text-red-600 text-xl">&times;</button>
            </div>
        <?php endif; ?>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="m-6 bg-red-50 border-4 border-red-600 text-red-800 px-6 py-4 flex items-center justify-between shadow-[4px_4px_0px_rgba(220,38,38,1)]">
                <div class="flex items-center gap-3">
                    <span class="font-pixel text-[10px] text-red-600 border border-red-600 px-1">ERR</span>
                    <p class="font-bold text-sm uppercase"><?= esc(session()->getFlashdata('error')) ?></p>
                </div>
                <button onclick="this.parentElement.style.display='none'" class="font-bold text-red-600 hover:text-red-800 text-xl">&times;</button>
            </div>
        <?php endif; ?>

        <main class="flex-grow w-full px-4 sm:px-8 py-8 sm:py-12 space-y-12 sm:space-y-16">
            
            <!-- Hero Section -->
            <div id="home-section" class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center pt-4">
                <div class="space-y-6">
                    <h1 class="text-3xl md:text-5xl font-pixel tracking-tighter leading-tight text-black">
                        Is Your Email<br>
                        <span class="text-red-600 block mt-4 text-4xl md:text-6xl">PWNED?</span>
                    </h1>
                    <p class="text-gray-600 font-mono text-sm leading-relaxed font-bold">
                        Check if your email has ever been involved in a data breach.
                    </p>
                    <a href="#cek-section" class="inline-block bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-8 py-4 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                        CHECK EMAIL NOW
                    </a>
                </div>
                <div class="flex justify-center">
                    <div class="border-4 border-black bg-gray-100 p-10 shadow-[6px_6px_0px_rgba(0,0,0,1)] text-center space-y-4">
                        <img src="<?= base_url('image/hacker.png') ?>" alt="Hacker" class="w-24 h-24 mx-auto object-contain">
                        <div class="bg-red-600 text-white text-[10px] font-pixel px-3 py-2 border-2 border-black uppercase tracking-widest shadow-[2px_2px_0px_rgba(0,0,0,1)]">DATA BREACH</div>
                    </div>
                </div>
            </div>

            <div class="space-y-12 sm:space-y-16">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center p-5 sm:p-8 border-4 border-black bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)]">
                    <div class="space-y-4">
                        <h2 class="text-xl font-pixel text-black flex items-center gap-2">
                            <span class="text-red-600">></span> WHAT IS A DATA BREACH?
                        </h2>
                        <p class="text-gray-600 font-mono text-sm leading-relaxed font-bold">
                            A data breach occurs when personal information such as emails, passwords, and other sensitive data is stolen and distributed on the internet without permission.
                        </p>
                    </div>
                    <div class="flex justify-center">
                        <div class="text-center border-4 border-black p-6 bg-gray-100">
                            <img src="<?= base_url('image/Computer1.png') ?>" alt="Computer" class="w-24 h-24 mx-auto object-contain">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 border-[3px] border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center space-y-4 transition-transform hover:-translate-y-1">
                        <img src="<?= base_url('image/shield.png') ?>" alt="Shield" class="w-16 h-16 mx-auto object-contain">
                        <h3 class="font-bold text-black uppercase tracking-widest text-sm">Protect Your Account</h3>
                        <p class="text-gray-500 text-xs font-bold uppercase leading-relaxed">Knowing about a breach early can help you secure important accounts.</p>
                    </div>
                    <div class="bg-white p-6 border-[3px] border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center space-y-4 transition-transform hover:-translate-y-1">
                        <img src="<?= base_url('image/Computer2.png') ?>" alt="Question" class="w-16 h-16 mx-auto object-contain">
                        <h3 class="font-bold text-black uppercase tracking-widest text-sm">Check Easily</h3>
                        <p class="text-gray-500 text-xs font-bold uppercase leading-relaxed">Enter your email and we will check thousands of data breaches.</p>
                    </div>
                    <div class="bg-white p-6 border-[3px] border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] text-center space-y-4 transition-transform hover:-translate-y-1">
                        <img src="<?= base_url('image/FloppyDisk.png') ?>" alt="Trusted Data" class="w-16 h-16 mx-auto object-contain">
                        <h3 class="font-bold text-black uppercase tracking-widest text-sm">Trusted Data</h3>
                        <p class="text-gray-500 text-xs font-bold uppercase leading-relaxed">We use breach data sources that are regularly updated.</p>
                    </div>
                </div>

                <div class="bg-black text-white border-4 border-black shadow-[6px_6px_0px_rgba(100,100,100,1)] p-8 grid grid-cols-1 md:grid-cols-3 text-center gap-6 md:gap-4">
                    <div class="md:border-r-2 md:border-dashed md:border-gray-600">
                        <div class="text-2xl md:text-3xl font-pixel text-blue-400"><?= esc($global_metrics['breaches_records_short'] ?? '11.7B+') ?></div>
                        <div class="text-[10px] text-gray-400 mt-2 uppercase font-bold tracking-widest">Exposed Records Indexed</div>
                    </div>
                    <div class="md:border-r-2 md:border-dashed md:border-gray-600">
                        <div class="text-2xl md:text-3xl font-pixel text-blue-400"><?= esc(number_format((int) ($global_metrics['breaches_count'] ?? 801))) ?></div>
                        <div class="text-[10px] text-gray-400 mt-2 uppercase font-bold tracking-widest">Verified Breach Sources</div>
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-pixel text-blue-400">100%</div>
                        <div class="text-[10px] text-gray-400 mt-2 uppercase font-bold tracking-widest">Free Email Check</div>
                    </div>
                </div>
            </div>


            <div id="cek-section" class="text-center space-y-8 scroll-mt-24 border-t-[4px] border-dashed border-gray-400 pt-16 mt-16">
                <div class="space-y-2">
                    <h2 class="text-xl md:text-2xl font-pixel text-black">CHECK YOUR EMAIL</h2>
                    <p class="text-xs uppercase font-bold text-gray-500 tracking-widest">Enter the email you want to check.</p>
                </div>

                <form id="check-email-form" action="<?= base_url('cek-email') ?>#hasil-cek" method="POST" class="max-w-2xl mx-auto mt-8 flex flex-col items-center gap-4">
                    <?= csrf_field() ?>
                    <div class="w-full flex flex-col md:flex-row gap-0 border-[4px] border-black p-1 bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)] relative">
                        <input type="email" id="input-cek-email" name="email" placeholder="contoh@email.com" required
                            value="<?= esc($email ?? old('email', '')) ?>"
                            class="w-full px-4 py-3 bg-white text-black border-none focus:outline-none font-bold placeholder-gray-400">
                        <button type="submit" id="btn-cek-submit" class="bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-8 py-4 border-t-[4px] md:border-t-0 md:border-l-[4px] border-black whitespace-nowrap transition-none flex-shrink-0">
                            CHECK NOW
                        </button>
                    </div>
                </form>

                <?php if (!empty($recent_history)): ?>
                <div class="max-w-2xl mx-auto flex flex-wrap items-center justify-center gap-2 pt-2">
                    <span class="text-[9px] font-bold uppercase text-gray-500 tracking-widest">RECENT:</span>
                    <?php foreach ($recent_history as $hist): ?>
                        <button type="button" onclick="quickCheckEmail('<?= esc($hist['email_checked'], 'js') ?>')"
                            class="text-[10px] font-bold bg-white hover:bg-black hover:text-white border-2 border-black px-2 py-0.5 transition-none">
                            <?= esc($hist['email_checked']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="flex flex-col items-center text-center mt-6 space-y-2">
                    <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest flex items-center justify-center gap-2 mb-2">
                        <span>Remaining checks today:</span>
                        <?php 
                            $kuota_terpakai = $usage_count ?? 0;
                            $batas_kuota = $usage_limit ?? 5; 
                            $paket = session()->get('subscription_plan') ?? 'free';
                        ?>
                        <?php if ($paket === 'pro'): ?>
                            <span class="text-green-600 font-pixel">Unlimited +</span>
                        <?php else: ?>
                            <span class="text-green-600 font-pixel"><?= esc($batas_kuota - $kuota_terpakai) ?> Times +</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($paket !== 'pro' && $kuota_terpakai >= $batas_kuota): ?>
                        <div class="mt-4 border-2 border-red-600 p-4 inline-block bg-white shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                            <p class="text-sm font-bold text-red-600 uppercase">
                                [ ERROR: QUOTA EXCEEDED ]
                            </p>
                            <p class="text-xs mt-2 uppercase font-bold">
                                <a href="<?= base_url('upgrade') ?>" class="text-blue-600 hover:bg-blue-600 hover:text-white px-1">UPGRADE PLAN</a> OR WAIT 24H.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($status) && $status !== null): ?>

            <hr id="hasil-cek" class="border-t-[4px] border-dashed border-gray-400 my-16 scroll-mt-24">
            
            <div class="space-y-6">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-3 h-3 bg-gray-400"></div>
                    <h2 class="text-sm font-pixel text-gray-400 uppercase tracking-widest">ACCOUNT ANALYSIS</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="border-[3px] border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white relative pt-8">
                        <span class="absolute top-2 left-4 text-[10px] text-gray-500 uppercase font-bold tracking-widest">EMAIL</span>
                        <div class="font-bold text-sm truncate"><?= esc($email) ?></div>
                    </div>

                    <?php if ($status === 'pwned'): ?>
                        <div class="border-[4px] border-red-600 p-4 shadow-[4px_4px_0px_rgba(220,38,38,1)] bg-white relative pt-8 flex items-center justify-between">
                            <span class="absolute top-2 left-4 text-[10px] text-red-600 uppercase font-bold tracking-widest">BREACH STATUS</span>
                            <div class="font-pixel text-red-600 text-lg">PWNED</div>
                            <div class="font-pixel text-red-600 text-lg">!</div>
                        </div>
                        <div class="border-[3px] border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white relative pt-8">
                            <span class="absolute top-2 left-4 text-[10px] text-gray-500 uppercase font-bold tracking-widest">NEXT ACTION</span>
                            <div class="font-bold text-sm text-black">Change password</div>
                        </div>
                    <?php elseif ($status === 'safe'): ?>
                        <div class="border-[4px] border-green-600 p-4 shadow-[4px_4px_0px_rgba(22,163,74,1)] bg-white relative pt-8 flex items-center justify-between">
                            <span class="absolute top-2 left-4 text-[10px] text-green-600 uppercase font-bold tracking-widest">BREACH STATUS</span>
                            <div class="font-pixel text-green-600 text-lg">SAFE</div>
                            <div class="font-pixel text-green-600 text-lg">✓</div>
                        </div>
                        <div class="border-[3px] border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white relative pt-8">
                            <span class="absolute top-2 left-4 text-[10px] text-gray-500 uppercase font-bold tracking-widest">NEXT ACTION</span>
                            <div class="font-bold text-sm text-gray-500">None required</div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($status === 'pwned' && !empty($details)): ?>
                <div class="border-[3px] border-black shadow-[6px_6px_0px_rgba(0,0,0,1)] bg-white mt-8 relative">

                    <div class="bg-black text-white px-6 py-3 flex flex-wrap justify-between items-center gap-3">
                        <span class="text-[10px] font-pixel uppercase tracking-widest">BREACH RECORDS</span>
                        <div class="flex items-center gap-3">
                            <?php if (in_array(session()->get('subscription_plan'), ['pro'], true) || session()->get('role') === 'admin'): ?>
                                <button type="button" onclick="exportBreachCSV()" class="bg-blue-600 hover:bg-blue-700 text-white text-[9px] font-pixel px-3 py-1 border border-white uppercase">
                                    EXPORT CSV
                                </button>
                                <button type="button" onclick="window.print()" class="bg-white hover:bg-gray-200 text-black text-[9px] font-pixel px-3 py-1 border border-white uppercase">
                                    PRINT / PDF
                                </button>
                            <?php else: ?>
                                <a href="<?= base_url('upgrade') ?>" class="bg-gray-800 hover:bg-red-600 text-gray-200 text-[9px] font-pixel px-2 py-1 border border-gray-500 uppercase" title="Upgrade ke PRO untuk Export Audit CSV/PDF">
                                    [PRO: EXPORT]
                                </a>
                            <?php endif; ?>
                            <span class="text-[10px] text-red-400 font-pixel animate-pulse">
                                <?= esc($total_breach ?? 0) ?> SOURCES FOUND
                            </span>
                        </div>
                    </div>

                    <div class="divide-y-2 divide-dashed divide-gray-200">
                        <?php foreach ($details as $i => $detail): ?>
                        <details class="group" <?= ($i === 0) ? 'open' : '' ?>>
                            <summary class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 list-none">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($detail['is_verified'])): ?>
                                        <span class="text-[9px] font-pixel bg-red-100 text-red-700 border border-red-400 px-1.5 py-0.5 shrink-0">VERIFIED</span>
                                    <?php else: ?>
                                        <span class="text-[9px] font-pixel bg-gray-100 text-gray-500 border border-gray-300 px-1.5 py-0.5 shrink-0">UNVERIFIED</span>
                                    <?php endif; ?>
                                    <span class="font-bold text-sm text-black"><?= esc($detail['sumber'] ?? '-') ?></span>
                                    <span class="text-[10px] text-gray-400 hidden md:inline"><?= esc($detail['domain'] ?? '') ?></span>
                                </div>
                                <div class="flex items-center gap-4 shrink-0">
                                    <span class="text-[10px] font-bold text-gray-500 hidden sm:block"><?= esc($detail['tanggal'] ?? '-') ?></span>
                                    <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </summary>

                            <div class="px-6 pb-5 bg-gray-50 border-t border-dashed border-gray-200">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Industry</p>
                                        <p class="text-xs font-bold text-black mt-1"><?= esc($detail['industri'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Breached Records</p>
                                        <p class="text-xs font-bold text-red-600 mt-1"><?= esc($detail['jumlah_data'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Password Risk</p>
                                        <p class="text-xs font-bold text-black mt-1"><?= esc($detail['risiko_password'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Year</p>
                                        <p class="text-xs font-bold text-black mt-1"><?= esc($detail['tanggal'] ?? '-') ?></p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-2">Exposed Data</p>
                                    <div class="flex flex-wrap gap-1">
                                        <?php
                                        $kelasArr = explode(';', $detail['kelas_data'] ?? '');
                                        foreach ($kelasArr as $kelas):
                                            $kelas = trim($kelas);
                                            if ($kelas === '') continue;
                                        ?>
                                        <span class="text-[10px] font-bold bg-black text-white px-2 py-0.5 border border-black"><?= esc($kelas) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <?php if (!empty($detail['deskripsi'])): ?>
                                <div class="mt-4 border-t border-dashed border-gray-200 pt-3">
                                    <p class="text-[10px] text-gray-500 leading-relaxed"><?= esc($detail['deskripsi']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </details>
                        <?php endforeach; ?>
                    </div>


                    <div class="bg-gray-50 border-t-2 border-dashed border-gray-300 px-6 py-3">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">
                            Data source: <a href="https://xposedornot.com" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">XposedOrNot.com</a> — Free Public API
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($status === 'safe'): ?>
                <div class="border-[3px] border-green-600 shadow-[4px_4px_0px_rgba(22,163,74,1)] bg-green-50 p-6 mt-8 text-center">
                    <p class="font-pixel text-green-700 text-sm">✓ ALL CLEAR</p>
                    <p class="font-bold text-sm text-green-800 mt-2"><?= esc($api_message ?? 'Not found in any known breach database.') ?></p>
                </div>
                <?php endif; ?>

                <?php if ($status === 'error'): ?>
                <div class="border-[3px] border-yellow-500 shadow-[4px_4px_0px_rgba(234,179,8,1)] bg-yellow-50 p-6 mt-8">
                    <p class="font-pixel text-yellow-700 text-sm uppercase">[SYSTEM_ERROR]</p>
                    <p class="font-bold text-sm text-yellow-800 mt-2"><?= esc($api_message ?? 'An error occurred while contacting the check service.') ?></p>
                    <p class="text-[10px] text-yellow-600 mt-2 uppercase font-bold">Please try again in a few moments.</p>
                </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <hr class="border-t-[4px] border-dashed border-gray-400 my-16">

            <div id="statistik-section" class="space-y-6 scroll-mt-24">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-3 h-3 bg-gray-400"></div>
                    <h2 class="text-sm font-pixel text-gray-400 uppercase tracking-widest">SYSTEM STATISTICS</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="border-[3px] border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white relative pt-8 flex flex-col justify-between h-28">
                        <div class="flex justify-between w-full absolute top-2 left-0 px-4">
                            <span class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">TRACKED BREACHES</span>
                        </div>
                        <div id="stat-breaches-count" class="font-pixel text-xl"><?= esc($statistik['sumber_aktif'] ?? '0') ?></div>
                        <div class="w-full bg-gray-200 border-2 border-black h-3 mt-2">
                            <div id="stat-load-bar" class="bg-blue-600 h-full transition-all duration-500" style="width: <?= (int) ($global_metrics['load_pct'] ?? 50) ?>%"></div>
                        </div>
                        <div id="stat-last-breach" class="text-[8px] text-gray-500 font-bold uppercase mt-1">LAST ADDED: <?= esc($global_metrics['last_breach_date'] ?? '-') ?></div>
                    </div>

                    <div class="border-[4px] border-red-600 p-4 shadow-[4px_4px_0px_rgba(220,38,38,1)] bg-white relative pt-8 flex justify-between h-28">
                        <span id="stat-year-label" class="absolute top-2 left-4 text-[10px] text-red-600 uppercase font-bold tracking-widest">YOY TREND (<?= esc($global_metrics['current_year'] ?? date('Y')) ?>)</span>
                        <div id="stat-yoy-diff" class="font-pixel text-red-600 text-2xl self-center"><?= esc($statistik['tingkat_kebocoran'] ?? '+0%') ?></div>
                        <div class="flex flex-col justify-end text-right">
                            <div id="stat-trend-level" class="text-[8px] text-red-600 uppercase font-bold"><?= esc($global_metrics['trend_level'] ?? 'CRITICAL') ?></div>
                            <div id="stat-trend-dir" class="text-[8px] text-gray-500 uppercase font-bold"><?= esc($global_metrics['trend_direction'] ?? 'INCREASING') ?></div>
                        </div>
                    </div>

                    <div class="border-[3px] border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white relative pt-8 flex flex-col justify-between h-28">
                        <span class="absolute top-2 left-4 text-[10px] text-gray-500 uppercase font-bold tracking-widest">TOTAL EXPOSED</span>
                        <div id="stat-total-records" class="font-pixel text-xl"><?= esc($statistik['total_akun'] ?? '0') ?></div>
                        <div class="text-[10px] font-bold mt-2">
                            <span id="stat-current-year-count" class="bg-red-200 text-red-700 px-1 border border-red-700">+<?= esc($global_metrics['current_year_count'] ?? 0) ?></span>
                            <span class="text-gray-400">INCIDENTS IN <span id="stat-current-year-badge"><?= esc($global_metrics['current_year'] ?? date('Y')) ?></span></span>
                        </div>
                    </div>
                </div>

                <div class="border-[3px] border-black shadow-[6px_6px_0px_rgba(0,0,0,1)] bg-white p-4 sm:p-6 mt-8 relative">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-3">
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">BREACH TRENDS / ANNUAL_DATA</div>
                        <div id="live-feed-status" class="text-[10px] text-blue-600 uppercase font-bold tracking-widest flex items-center gap-2">
                            <span id="live-feed-dot" class="inline-block w-2 h-2 bg-green-500 border border-black animate-pulse"></span>
                            <span id="live-feed-text">LIVE_FEED: <?= esc(strtoupper($global_metrics['source'] ?? 'LIVE')) ?> • <?= esc($global_metrics['last_breach_date'] ?? '-') ?></span>
                        </div>
                    </div>
                    <div class="w-full h-56 sm:h-64 mt-2 relative">
                        <canvas id="trenChart" class="w-full h-full"></canvas>
                    </div>
                    <div class="mt-3 text-[10px] text-gray-500 font-bold uppercase flex flex-wrap justify-between items-center gap-2">
                        <span class="text-gray-400">SOURCE: XPOSEDORNOT_API_V1</span>
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-blue-600 border border-black inline-block"></span> ANNUAL_BREACHES
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 border-t-2 border-dashed border-gray-500 inline-block"></span> GLOBAL_AVG (<span id="legend-global-avg"><?= esc($global_metrics['chart']['average'] ?? 0) ?></span>/YR)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-t-[4px] border-dashed border-gray-400 my-12 sm:my-16">

            <div id="tentang-section" class="scroll-mt-20 border-[4px] border-black p-5 sm:p-8 bg-gray-600 text-white shadow-[8px_8px_0px_rgba(100,100,100,1)] mb-10">
                <div class="flex items-center gap-2 mb-8">
                    <div class="w-3 h-3 bg-white"></div>
                    <h2 class="text-sm font-pixel text-white uppercase tracking-widest">ABOUT [SYSTEM_INFO]</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-b-2 border-dashed border-gray-600 pb-8">
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-blue-400 uppercase tracking-widest">> What is this?</h3>
                        <p class="text-gray-300 text-xs leading-relaxed text-justify font-mono">
                            PWNED is here to provide a solution for the public to regularly check if their credential data has become a victim of a data breach. Most exposed data comes from mass security breach incidents.
                        </p>
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-blue-400 uppercase tracking-widest">> Why build this?</h3>
                        <p class="text-gray-300 text-xs leading-relaxed text-justify font-mono">
                            This site was built with two main goals. First, to educate the public about the scale of cyber attack dangers. Second, this CodeIgniter 4 based software development project serves as a practical web security case study.
                        </p>
                    </div>
                </div>

                <div class="pt-8">
                    <h3 class="text-sm font-bold text-white uppercase tracking-widest mb-6">> DEV_TEAM</h3>
                    <div class="space-y-6">
                        <div class="border-2 border-white p-4 flex flex-col md:flex-row items-center gap-6 bg-[#111]">
                            <div class="w-24 h-24 border-2 border-white bg-black flex-shrink-0 flex items-center justify-center p-1">
                                <img src="<?= base_url('image/Yona.JPG') ?>" alt="Foto" class="w-full h-full object-cover filter grayscale contrast-125">
                            </div>
                            <div class="space-y-2 text-center md:text-left">
                                <h4 class="text-sm font-bold text-white uppercase">Yonazahran Yoga Meinindra Rizky</h4>
                                <p class="text-[10px] text-blue-400 font-pixel">LEAD / BACKEND</p>
                                <p class="text-gray-400 text-xs leading-relaxed">MVC system architecture, database, and security.</p>
                            </div>
                        </div>
                        <div class="border-2 border-white p-4 flex flex-col md:flex-row items-center gap-6 bg-[#111]">
                            <div class="w-24 h-24 border-2 border-white bg-black flex-shrink-0 flex items-center justify-center p-1">
                                <img src="<?= base_url('image/Baraza.jpeg') ?>" alt="Foto" class="w-full h-full object-cover filter grayscale contrast-125">
                            </div>
                            <div class="space-y-2 text-center md:text-left">
                                <h4 class="text-sm font-bold text-white uppercase">Baraza Nandian Syah</h4>
                                <p class="text-[10px] text-blue-400 font-pixel">UI/UX / FRONTEND</p>
                                <p class="text-gray-400 text-xs leading-relaxed">Designing visual UI/UX and Tailwind CSS components.</p>
                            </div>
                        </div>
                        <div class="border-2 border-white p-4 flex flex-col md:flex-row items-center gap-6 bg-[#111]">
                            <div class="w-24 h-24 border-2 border-white bg-black flex-shrink-0 flex items-center justify-center p-1">
                                <img src="<?= base_url('image/nanduy.jpeg') ?>" alt="Foto" class="w-full h-full object-cover filter grayscale contrast-125">
                            </div>
                            <div class="space-y-2 text-center md:text-left">
                                <h4 class="text-sm font-bold text-white uppercase">Muhammad Najib Finanda</h4>
                                <p class="text-[10px] text-blue-400 font-pixel">UI/UX / FULLSTACK</p>
                                <p class="text-gray-400 text-xs leading-relaxed">CSRF security, validation, and interface design.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
        
        <footer class="border-t-4 border-black bg-white p-4 text-xs font-bold uppercase text-gray-500 flex flex-col sm:flex-row items-center justify-between gap-2 px-8">
            <span>[ PWNED_SYSTEM_v1.0 ] &copy; 2026 | TERMINAL_CLOSED</span>
            <button type="button" onclick="openContactModal()" class="text-blue-600 hover:bg-black hover:text-white px-2 py-1 border-2 border-black text-[10px] font-pixel transition-none">
                CONTACT ADMIN
            </button>
        </footer>
    </div>

    <script>
        (function () {
            const initialMetrics = <?= json_encode($global_metrics ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const initialChart = initialMetrics.chart || { labels: [], full_years: [], values: [], average: 0 };
            let fullYears = initialChart.full_years || [];

            const ctx = document.getElementById('trenChart').getContext('2d');
            Chart.defaults.font.family = "'JetBrains Mono', monospace";
            Chart.defaults.color = '#6b7280';

            const makeAvgSeries = (len, avg) => Array.from({ length: len }, () => avg);

            const trenChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: initialChart.labels || [],
                    datasets: [
                        {
                            label: 'Breach Incidents',
                            data: initialChart.values || [],
                            borderColor: '#2563eb',
                            backgroundColor: '#2563eb',
                            borderWidth: 4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#2563eb',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0,
                            fill: false
                        },
                        {
                            label: 'Global Avg',
                            data: makeAvgSeries((initialChart.values || []).length, initialChart.average || 0),
                            borderColor: '#6b7280',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            tension: 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#111827',
                            borderColor: '#000000',
                            borderWidth: 2,
                            cornerRadius: 0,
                            padding: 10,
                            titleFont: { family: "'JetBrains Mono', monospace", size: 11, weight: 'bold' },
                            bodyFont: { family: "'JetBrains Mono', monospace", size: 11 },
                            callbacks: {
                                title: (items) => {
                                    if (!items.length) return '';
                                    const idx = items[0].dataIndex;
                                    return 'YEAR ' + (fullYears[idx] || items[0].label);
                                },
                                label: (ctxItem) => {
                                    if (ctxItem.datasetIndex === 0) {
                                        return ' Incidents: ' + ctxItem.parsed.y + ' breaches';
                                    }
                                    return ' 10-Yr Avg: ' + ctxItem.parsed.y + ' / yr';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            border: { color: '#000000', width: 2 },
                            grid: { display: false },
                            ticks: { font: { size: 10, weight: 'bold' }, color: '#4b5563' }
                        },
                        y: {
                            beginAtZero: true,
                            border: { color: '#000000', width: 2 },
                            grid: {
                                color: '#e5e7eb',
                                tickBorderDash: [4, 4]
                            },
                            ticks: {
                                precision: 0,
                                font: { size: 9, weight: 'bold' },
                                color: '#6b7280'
                            }
                        }
                    }
                }
            });

            function updateMetricsUI(payload) {
                if (!payload || !payload.chart) return;

                const chartData = payload.chart;
                fullYears = chartData.full_years || [];

                trenChart.data.labels = chartData.labels || [];
                trenChart.data.datasets[0].data = chartData.values || [];
                trenChart.data.datasets[1].data = makeAvgSeries((chartData.values || []).length, chartData.average || 0);
                trenChart.update();

                const setText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el && val !== undefined && val !== null) el.textContent = val;
                };

                setText('stat-breaches-count', Number(payload.breaches_count || 0).toLocaleString('en-US'));
                setText('stat-last-breach', 'LAST ADDED: ' + (payload.last_breach_date || '-'));
                setText('stat-year-label', 'YOY TREND (' + (payload.current_year || '') + ')');
                setText('stat-yoy-diff', payload.yoy_diff_pct || '+0%');
                setText('stat-trend-level', payload.trend_level || 'MODERATE');
                setText('stat-trend-dir', payload.trend_direction || 'INCREASING');
                setText('stat-total-records', payload.breaches_records_short || '0');
                setText('stat-current-year-count', '+' + (payload.current_year_count || 0));
                setText('stat-current-year-badge', payload.current_year || '');
                setText('legend-global-avg', chartData.average ?? 0);

                const loadBar = document.getElementById('stat-load-bar');
                if (loadBar && typeof payload.load_pct === 'number') {
                    loadBar.style.width = payload.load_pct + '%';
                }

                const feedText = document.getElementById('live-feed-text');
                const feedDot = document.getElementById('live-feed-dot');
                if (feedText) {
                    const src = String(payload.source || 'live').toUpperCase();
                    feedText.textContent = 'LIVE_FEED: ' + src + ' • ' + (payload.last_breach_date || '-');
                }
                if (feedDot) {
                    feedDot.className = 'inline-block w-2 h-2 border border-black animate-pulse ' +
                        (payload.source === 'fallback' ? 'bg-yellow-400' : 'bg-green-500');
                }
            }

            async function pollBreachTrends() {
                const feedText = document.getElementById('live-feed-text');
                if (feedText) feedText.textContent = 'LIVE_FEED: SYNCING...';

                try {
                    const res = await fetch('<?= base_url('api/breach-trends') ?>', {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (data && data.status === 'ok') {
                        updateMetricsUI(data);
                    }
                } catch (err) {
                    if (feedText) {
                        feedText.textContent = 'LIVE_FEED: CACHED (' + (initialMetrics.last_breach_date || '-') + ')';
                    }
                }
            }

            // Sinkronisasi berkala setiap 60 detik (backend menjaga rate limit via cache 10 menit)
            setInterval(pollBreachTrends, 60000);
        })();
    </script>

    <script>
        (function () {
            const navContainer = document.getElementById('nav-container');
            const navIndicator = document.getElementById('nav-indicator');
            const navLinks = document.querySelectorAll('.nav-link');
            const sections = {
                'nav-cek': document.getElementById('cek-section'),
                'nav-statistik': document.getElementById('statistik-section'),
                'nav-tentang': document.getElementById('tentang-section')
            };

            let currentActiveId = 'nav-home';
            let clickLockUntil = 0;

            function positionIndicator(targetLink, instant = false) {
                if (!navContainer || !navIndicator || !targetLink || navContainer.offsetParent === null) return;

                if (instant) {
                    const prevTransition = navIndicator.style.transition;
                    navIndicator.style.transition = 'none';
                    navIndicator.style.width = targetLink.offsetWidth + 'px';
                    navIndicator.style.height = targetLink.offsetHeight + 'px';
                    navIndicator.style.transform = `translate3d(${targetLink.offsetLeft}px, ${targetLink.offsetTop}px, 0)`;
                    navIndicator.style.opacity = '1';
                    navIndicator.offsetHeight; // force reflow
                    navIndicator.style.transition = prevTransition;
                    return;
                }

                navIndicator.style.width = targetLink.offsetWidth + 'px';
                navIndicator.style.height = targetLink.offsetHeight + 'px';
                navIndicator.style.transform = `translate3d(${targetLink.offsetLeft}px, ${targetLink.offsetTop}px, 0)`;
                navIndicator.style.opacity = '1';
            }

            function setActiveLink(activeId, instant = false) {
                currentActiveId = activeId;
                let activeEl = null;

                navLinks.forEach(link => {
                    if (link.id === activeId) {
                        link.classList.add('text-white');
                        link.classList.remove('text-black', 'hover:text-blue-600');
                        activeEl = link;
                    } else {
                        link.classList.remove('text-white');
                        link.classList.add('text-black', 'hover:text-blue-600');
                    }
                });

                if (activeEl) {
                    positionIndicator(activeEl, instant);
                }
            }

            function detectActiveSection() {
                let detected = 'nav-home';
                const scrollPosition = window.scrollY + 120;

                for (const [navId, section] of Object.entries(sections)) {
                    if (section && scrollPosition >= section.offsetTop) {
                        detected = navId;
                    }
                }

                if ((window.innerHeight + window.scrollY) >= document.documentElement.scrollHeight - 50) {
                    detected = 'nav-tentang';
                }

                return detected;
            }

            navLinks.forEach(link => {
                link.addEventListener('click', function () {
                    if (this.id) {
                        clickLockUntil = Date.now() + 700;
                        setActiveLink(this.id);
                    }
                });
            });

            window.addEventListener('scroll', () => {
                if (Date.now() < clickLockUntil) return;
                const nextActive = detectActiveSection();
                if (nextActive !== currentActiveId) {
                    setActiveLink(nextActive);
                }
            }, { passive: true });

            window.addEventListener('resize', () => {
                setActiveLink(currentActiveId, true);
            });

            const initialActive = detectActiveSection();
            setActiveLink(initialActive, true);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(() => setActiveLink(currentActiveId, true));
            }
        })();
    </script>

    <div id="contactModal" class="fixed inset-0 z-[999] hidden items-center justify-center p-4" style="background:rgba(0,0,0,0.85)">
        <div class="bg-white border-[6px] border-black w-full max-w-lg shadow-[16px_16px_0px_rgba(0,0,0,0.5)] flex flex-col" style="max-height:90vh;overflow-y:auto">

            <div class="bg-blue-600 border-b-[6px] border-black px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex gap-1">
                        <div class="w-3 h-3 bg-red-500 border-2 border-black"></div>
                        <div class="w-3 h-3 bg-yellow-400 border-2 border-black"></div>
                        <div class="w-3 h-3 bg-green-500 border-2 border-black"></div>
                    </div>
                    <span class="font-pixel text-[10px] text-white uppercase tracking-widest">CONTACT_ADMIN.EXE</span>
                </div>
                <button type="button" onclick="closeContactModal()" class="w-7 h-7 bg-red-500 border-2 border-black text-white font-bold hover:bg-red-700 flex items-center justify-center text-sm">✕</button>
            </div>

            <!-- Body: Form -->
            <div id="contactFormBody" class="p-6 space-y-5 bg-gray-50">
                <?php if (!session()->get('logged_in')): ?>
                    <div class="bg-yellow-50 border-4 border-black p-4 space-y-3 text-center">
                        <p class="font-pixel text-[9px] text-black uppercase">[!] LOGIN DIPERLUKAN</p>
                        <p class="text-xs font-bold text-gray-700 leading-relaxed">Untuk mencegah spam, hanya pengguna yang sudah login yang dapat mengirim pesan atau pengajuan upgrade ke Admin.</p>
                        <a href="<?= base_url('login') ?>" class="inline-block bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[9px] px-5 py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                            LOGIN SEKARANG
                        </a>
                    </div>
                <?php else: ?>
                    <div class="border-l-4 border-blue-600 pl-3 mb-2">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">&gt; Kirim pesan atau permintaan ke admin. Kami akan segera merespons.</p>
                    </div>

                    <div id="contactErrorBox" class="hidden bg-red-100 border-2 border-red-600 text-red-700 px-3 py-2 text-xs font-bold uppercase"></div>
                    <input type="text" id="contact_website_url" tabindex="-1" autocomplete="off" value="" class="hidden">

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Nama</label>
                        <input type="text" id="contact_nama" placeholder="Nama kamu..."
                            value="<?= esc(session()->get('nama') ?? '') ?>"
                            class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Email</label>
                        <input type="email" id="contact_email" placeholder="email@kamu.com"
                            value="<?= esc(session()->get('email') ?? '') ?>"
                            class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Jenis Permintaan</label>
                        <div class="relative border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white">
                            <select id="contact_jenis" class="w-full px-4 py-3 bg-transparent outline-none font-bold text-black uppercase cursor-pointer appearance-none text-sm">
                                <option value="upgrade_plus">Upgrade ke PLUS</option>
                                <option value="upgrade_pro">Upgrade ke PRO</option>
                                <option value="downgrade_free">Downgrade ke FREE</option>
                                <option value="report">Laporkan Masalah</option>
                                <option value="other">Lainnya</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-black border-l-4 border-black">▼</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Pesan</label>
                        <textarea id="contact_pesan" rows="3" placeholder="Tuliskan pesan atau alasanmu di sini..."
                            class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm resize-none"></textarea>
                    </div>

                    <?php if (!empty($turnstile_site_key)): ?>
                        <div class="cf-turnstile" id="contact-turnstile" data-sitekey="<?= esc($turnstile_site_key) ?>"></div>
                    <?php else: ?>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Verifikasi CAPTCHA</label>
                            <div class="flex items-center gap-2 mb-2">
                                <img id="contact-captcha-img" src="" alt="CAPTCHA" class="border-2 border-black bg-gray-100 h-14 w-[180px] object-contain">
                                <button type="button" onclick="refreshContactCaptcha()" class="px-3 py-3 bg-gray-200 hover:bg-black hover:text-white border-2 border-black font-pixel text-[8px] uppercase">REFRESH</button>
                            </div>
                            <input type="text" id="contact_captcha_code" maxlength="5" autocomplete="off" placeholder="KETIK 5 KODE DI ATAS"
                                class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black uppercase tracking-widest placeholder-gray-400 text-sm">
                        </div>
                    <?php endif; ?>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeContactModal()" class="w-1/3 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[9px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                            BATAL
                        </button>
                        <button type="button" id="contactSubmitBtn" onclick="submitContact()" class="w-2/3 bg-black hover:bg-blue-700 border-4 border-black text-white font-pixel text-[9px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                            KIRIM &gt;&gt; ADMIN
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Body: Success -->
            <div id="contactSuccessBody" class="hidden p-8 text-center space-y-5 bg-gray-50">
                <div class="inline-block bg-green-600 text-white font-pixel text-xs px-4 py-2 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)]">
                    [OK] TRANSMITTED
                </div>
                <p id="contactSuccessMsg" class="text-xs font-bold uppercase text-black leading-relaxed">
                    Permintaan Anda berhasil dikirim ke Admin.
                </p>
                <button type="button" onclick="closeContactModal()" class="bg-black hover:bg-blue-700 text-white font-pixel text-[9px] px-6 py-3 border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] uppercase">
                    TUTUP
                </button>
            </div>
        </div>
    </div>

    <script>
        let csrfName = '<?= esc(csrf_token()) ?>';
        let csrfHash = '<?= esc(csrf_hash()) ?>';

        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu) menu.classList.toggle('hidden');
        }

        function quickCheckEmail(email) {
            const input = document.getElementById('input-cek-email');
            const form  = document.getElementById('check-email-form');
            if (input && form) {
                input.value = email;
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        }

        const checkForm = document.getElementById('check-email-form');
        if (checkForm) {
            checkForm.addEventListener('submit', function () {
                const btn = document.getElementById('btn-cek-submit');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'SCANNING...';
                }
            });
        }

        function exportBreachCSV() {
            const targetEmail = <?= json_encode($email ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const records = <?= json_encode($details ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            if (!records.length) return;

            const escCsv = (val) => '"' + String(val ?? '').replace(/"/g, '""') + '"';
            const lines = [
                ['Email', 'Source', 'Domain', 'Year', 'Industry', 'Records', 'Password Risk', 'Verified', 'Exposed Data'].map(escCsv).join(',')
            ];

            records.forEach((r) => {
                lines.push([
                    targetEmail,
                    r.sumber,
                    r.domain,
                    r.tanggal,
                    r.industri,
                    r.jumlah_data,
                    r.risiko_password,
                    r.is_verified ? 'Yes' : 'No',
                    r.kelas_data
                ].map(escCsv).join(','));
            });

            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'PWNED_Audit_' + (targetEmail || 'report').replace(/[^a-zA-Z0-9._-]/g, '_') + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function refreshContactCaptcha() {
            const img = document.getElementById('contact-captcha-img');
            if (img) img.src = '<?= base_url('captcha/contact') ?>?t=' + Date.now();
            const capInput = document.getElementById('contact_captcha_code');
            if (capInput) capInput.value = '';
        }

        function syncCsrfFields(newToken) {
            if (!newToken) return;
            csrfHash = newToken;
            document.querySelectorAll('input[name="' + csrfName + '"]').forEach((el) => {
                el.value = newToken;
            });
        }

        function openContactModal() {
            document.getElementById('contactModal').classList.remove('hidden');
            document.getElementById('contactModal').classList.add('flex');
            document.getElementById('contactFormBody').classList.remove('hidden');
            document.getElementById('contactSuccessBody').classList.add('hidden');

            const errBox = document.getElementById('contactErrorBox');
            if (errBox) errBox.classList.add('hidden');

            const btn = document.getElementById('contactSubmitBtn');
            if (btn) {
                btn.textContent = 'KIRIM >> ADMIN';
                btn.disabled = false;
            }
            const pesanEl = document.getElementById('contact_pesan');
            if (pesanEl) pesanEl.value = '';
            refreshContactCaptcha();
            document.body.style.overflow = 'hidden';
        }

        function closeContactModal() {
            document.getElementById('contactModal').classList.add('hidden');
            document.getElementById('contactModal').classList.remove('flex');
            document.body.style.overflow = '';
        }

        async function submitContact() {
            const nama    = document.getElementById('contact_nama').value.trim();
            const email   = document.getElementById('contact_email').value.trim();
            const jenis   = document.getElementById('contact_jenis').value;
            const pesan   = document.getElementById('contact_pesan').value.trim();
            const trap    = document.getElementById('contact_website_url')?.value || '';
            const capCode = document.getElementById('contact_captcha_code')?.value.trim() || '';
            const tsResp  = document.querySelector('#contactFormBody input[name="cf-turnstile-response"]')?.value || '';
            const errBox  = document.getElementById('contactErrorBox');
            const btn     = document.getElementById('contactSubmitBtn');

            errBox.classList.add('hidden');
            if (!nama || !email) {
                errBox.textContent = 'Nama dan Email wajib diisi.';
                errBox.classList.remove('hidden');
                return;
            }

            btn.textContent = 'MENGIRIM...';
            btn.disabled = true;

            try {
                const body = new URLSearchParams();
                body.append(csrfName, csrfHash);
                body.append('nama', nama);
                body.append('email', email);
                body.append('jenis', jenis);
                body.append('pesan', pesan);
                body.append('website_url', trap);
                if (capCode) body.append('captcha_code', capCode);
                if (tsResp) body.append('cf-turnstile-response', tsResp);

                const res = await fetch('<?= base_url('kirim-request') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: body.toString()
                });
                const data = await res.json();
                if (data.csrf_token) syncCsrfFields(data.csrf_token);

                if (!res.ok || data.status !== 'ok') {
                    errBox.textContent = data.message || 'Gagal mengirim permintaan.';
                    errBox.classList.remove('hidden');
                    btn.textContent = 'KIRIM >> ADMIN';
                    btn.disabled = false;
                    refreshContactCaptcha();
                    return;
                }

                document.getElementById('contactSuccessMsg').textContent = data.message || 'Permintaan Anda berhasil dikirim ke Admin.';
                document.getElementById('contactFormBody').classList.add('hidden');
                document.getElementById('contactSuccessBody').classList.remove('hidden');
            } catch (e) {
                errBox.textContent = 'Terjadi gangguan koneksi. Coba lagi.';
                errBox.classList.remove('hidden');
                btn.textContent = 'KIRIM >> ADMIN';
                btn.disabled = false;
                refreshContactCaptcha();
            }
        }

        document.getElementById('contactModal').addEventListener('click', function(e) {
            if (e.target === this) closeContactModal();
        });
    </script>

</body>
</html>