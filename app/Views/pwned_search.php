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
    <style>
        .font-pixel { font-family: 'Press Start 2P', cursive; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-black min-h-screen">

    <!-- Frame Mockup -->
    <div class="w-full bg-[url('<?= base_url('image/background_retro.png') ?>')] bg-repeat border-y-4 border-black flex flex-col relative">

        <!-- Navbar -->
        <nav class="sticky top-0 w-full px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 bg-white z-50">
            <div class="flex items-center space-x-2">
                <span class="text-xl font-pixel text-black">PWNED</span>
            </div>
            
            <div id="nav-container" class=" hidden md:flex justify-center space-x-6 text-sm font-bold uppercase">
                <a href="#" id="nav-home" class="nav-link text-black hover:bg-black hover:text-white px-2 py-1 transition-none">HOME</a>
                <a href="#cek-section" id="nav-cek" class="nav-link text-black hover:bg-black hover:text-white px-2 py-1 transition-none">CHECK EMAIL</a>
                <a href="#statistik-section" id="nav-statistik" class="nav-link text-black hover:bg-black hover:text-white px-2 py-1 transition-none">STATISTICS</a>
                <a href="#tentang-section" id="nav-tentang" class="nav-link text-black hover:bg-black hover:text-white px-2 py-1 transition-none">ABOUT</a>
    
                <?php if(session()->get('role') === 'admin'): ?>
                <a href="<?= base_url('admin') ?>" class="nav-link text-red-600 hover:bg-red-600 hover:text-white px-2 py-1 transition-none">
                    [ADMIN]
                </a>
               <?php endif; ?>
            </div>
            
            <div class="flex justify-end items-center gap-4 font-bold uppercase text-sm">
                <?php if(session()->has('logged_in') && session()->get('logged_in') === true): ?>
                    <div class="flex items-center gap-3 border-r-2 border-black pr-4">
                        <span>
                            USER: <span class="text-blue-600"><?= esc(session()->get('nama') ?? 'User') ?></span>
                        </span>
                        <a href="<?= base_url('logout') ?>" class="text-red-600 hover:bg-red-600 hover:text-white px-2 py-1 transition-none" title="Logout System">
                            LOGOUT
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="text-black hover:bg-black hover:text-white px-2 py-1 transition-none">
                        LOGIN
                    </a>
                <?php endif; ?>
                <?php if(session()->get('role') === 'user'): ?>
                <a href="<?= base_url('upgrade') ?>" class="bg-blue-600 text-white border-2 border-black hover:bg-white hover:text-blue-600 px-3 py-1 text-xs transition-none shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                    UPGRADE
                </a>
                <?php endif; ?>
            </div>
        </nav>

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

        <main class="flex-grow w-full px-8 py-12 space-y-16">
            
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

            <div class="space-y-16">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center p-8 border-4 border-black bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)]">
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

                <div class="bg-black text-white border-4 border-black shadow-[6px_6px_0px_rgba(100,100,100,1)] p-8 grid grid-cols-3 text-center gap-4">
                    <div class="border-r-2 border-dashed border-gray-600">
                        <div class="text-2xl md:text-3xl font-pixel text-blue-400">24B+</div>
                        <div class="text-[10px] text-gray-400 mt-2 uppercase font-bold tracking-widest">Accounts Breached This Year</div>
                    </div>
                    <div class="border-r-2 border-dashed border-gray-600">
                        <div class="text-2xl md:text-3xl font-pixel text-blue-400">48</div>
                        <div class="text-[10px] text-gray-400 mt-2 uppercase font-bold tracking-widest">Breach Sources</div>
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-pixel text-blue-400">100%</div>
                        <div class="text-[10px] text-gray-400 mt-2 uppercase font-bold tracking-widest">Free to Use</div>
                    </div>
                </div>
            </div>


            <div id="cek-section" class="text-center space-y-8 scroll-mt-24 border-t-[4px] border-dashed border-gray-400 pt-16 mt-16">
                <div class="space-y-2">
                    <h2 class="text-xl md:text-2xl font-pixel text-black">CHECK YOUR EMAIL</h2>
                    <p class="text-xs uppercase font-bold text-gray-500 tracking-widest">Enter the email you want to check.</p>
                </div>

                <form action="<?= base_url('cek-email') ?>#hasil-cek" method="POST" class="max-w-2xl mx-auto mt-8 flex flex-col items-center gap-4">
                    <?= csrf_field() ?>
                    <div class="w-full flex flex-col md:flex-row gap-0 border-[4px] border-black p-1 bg-white shadow-[6px_6px_0px_rgba(0,0,0,1)] relative">
                        <input type="email" name="email" placeholder="contoh@email.com" required
                            value="<?= esc($email ?? old('email', '')) ?>"
                            class="w-full px-4 py-3 bg-white text-black border-none focus:outline-none font-bold placeholder-gray-400">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-pixel text-[10px] px-8 py-4 border-l-[4px] border-black whitespace-nowrap transition-none flex-shrink-0">
                            CHECK NOW
                        </button>
                    </div>
                </form>

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

                    <div class="bg-black text-white px-6 py-3 flex justify-between items-center">
                        <span class="text-[10px] font-pixel uppercase tracking-widest">BREACH RECORDS</span>
                        <span class="text-[10px] text-red-400 font-pixel animate-pulse">
                            <?= esc($total_breach ?? 0) ?> SOURCES FOUND
                        </span>
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
                            <span class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">ACTIVE SCANS</span>
                        </div>
                        <div class="font-pixel text-xl"><?= esc($statistik['sumber_aktif'] ?? '48') ?></div>
                        <div class="w-full bg-gray-200 border-2 border-black h-3 mt-2">
                            <div class="bg-gray-500 h-full w-[45%]"></div>
                        </div>
                        <div class="text-[8px] text-gray-400 font-bold uppercase mt-1">LOAD: 45 / 100</div>
                    </div>

                    <div class="border-[4px] border-red-600 p-4 shadow-[4px_4px_0px_rgba(220,38,38,1)] bg-white relative pt-8 flex justify-between h-28">
                        <span class="absolute top-2 left-4 text-[10px] text-red-600 uppercase font-bold tracking-widest">BREACH RATE %</span>
                        <div class="font-pixel text-red-600 text-3xl self-center"><?= esc($statistik['tingkat_kebocoran'] ?? '65%') ?></div>
                        <div class="flex flex-col justify-end text-right">
                            <div class="text-[8px] text-red-600 uppercase font-bold">CRITICAL</div>
                            <div class="text-[8px] text-gray-500 uppercase font-bold">INCREASING</div>
                        </div>
                    </div>

                    <div class="border-[3px] border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,1)] bg-white relative pt-8 flex flex-col justify-between h-28">
                        <span class="absolute top-2 left-4 text-[10px] text-gray-500 uppercase font-bold tracking-widest">TOTAL BREACHED</span>
                        <div class="font-pixel text-xl"><?= esc($statistik['total_akun'] ?? '352K') ?></div>
                        <div class="text-[10px] font-bold mt-2">
                            <span class="bg-red-200 text-red-700 px-1 border border-red-700">+10.2%</span> <span class="text-gray-400">LAST 30 DAYS</span>
                        </div>
                    </div>
                </div>

                <div class="border-[3px] border-black shadow-[6px_6px_0px_rgba(0,0,0,1)] bg-white p-6 mt-8 relative pt-10">
                    <div class="absolute top-3 left-4 text-[10px] text-gray-500 uppercase font-bold tracking-widest">BREACH TRENDS / ANNUAL_DATA</div>
                    <div class="absolute top-3 right-4 text-[10px] text-blue-600 uppercase font-bold tracking-widest">LIVE_FEED: UPDATING...</div>
                    <div class="w-full h-64 mt-4 relative">
                        <div class="absolute inset-0 border-b-2 border-l-2 border-black flex flex-col justify-between pb-6 pl-1 text-[8px] text-gray-400 font-bold">
                            <div class="w-full border-b border-gray-200 border-dashed h-full"></div>
                            <div class="w-full border-b border-gray-200 border-dashed h-full"></div>
                            <div class="w-full border-b border-gray-200 border-dashed h-full"></div>
                            <div class="w-full border-b border-gray-200 border-dashed h-full"></div>
                            <div class="w-full border-b border-gray-200 border-dashed h-full"></div>
                        </div>
                        <canvas id="trenChart" class="relative z-10 pl-6 pb-6 w-full h-full"></canvas>
                    </div>
                    <div class="text-right mt-2 text-[10px] text-gray-500 font-bold uppercase flex justify-end items-center gap-1">
                        <div class="w-2 h-2 bg-blue-600 border border-black"></div> GLOBAL_AVG
                    </div>
                </div>
            </div>

            <hr class="border-t-[4px] border-dashed border-gray-400 my-16">

            <div id="tentang-section" class="scroll-mt-20 border-[4px] border-black p-8 bg-gray-600 text-white shadow-[8px_8px_0px_rgba(100,100,100,1)] mb-10">
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
                                <img src="<?= base_url('image/Nanduy.jpeg') ?>" alt="Foto" class="w-full h-full object-cover filter grayscale contrast-125">
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
        
        <footer class="border-t-4 border-black bg-white p-4 text-center text-xs font-bold uppercase text-gray-500">
            [ PWNED_SYSTEM_v1.0 ] &copy; 2026 | TERMINAL_CLOS    </div>

    <script>
        const ctx = document.getElementById('trenChart').getContext('2d');
        Chart.defaults.font.family = "'JetBrains Mono', monospace";
        Chart.defaults.color = '#9ca3af';

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['17', '18', '19', '20', '21', '22', '23', '24', '25', '26'],
                datasets: [{
                    label: 'Breach Vol',
                    data: [25, 48, 38, 52, 40, 58, 72, 62, 68, 79],
                    borderColor: '#2563eb',
                    borderWidth: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    x: {
                        grid: { display: false, drawBorder: true, borderColor: '#000', borderWidth: 2 },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    },
                    y: { 
                        beginAtZero: true,
                        grid: { display: false, drawBorder: true, borderColor: '#000', borderWidth: 2 },
                        ticks: { display: false }
                    } 
                }
            }
        });
    </script>

    <script>
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = {
            'nav-cek': document.getElementById('cek-section'),
            'nav-statistik': document.getElementById('statistik-section'),
            'nav-tentang': document.getElementById('tentang-section')
        };

        function setActiveLink(activeId) {
            navLinks.forEach(link => {
                if (link.id === activeId) {
                    link.classList.add('bg-black', 'text-white');
                } else {
                    link.classList.remove('bg-black', 'text-white');
                }
            });
        }

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.id !== 'nav-home' && this.id !== '') {
                    setActiveLink(this.id);
                }
            });
        });

        window.addEventListener('scroll', () => {
            let currentActive = 'nav-home';
            const scrollPosition = window.scrollY + 120;

            for (const [navId, section] of Object.entries(sections)) {
                if (section && scrollPosition >= section.offsetTop) {
                    currentActive = navId;
                }
            }

            if ((window.innerHeight + window.scrollY) >= document.documentElement.scrollHeight - 50) {
                currentActive = 'nav-tentang';
            }

            setActiveLink(currentActive);
        });
    </script>

<?= csrf_hash() ?>
<?= csrf_field() ?>

<?= base_url("cek-email-ajax") ?>

</body>
</html>


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
            <button onclick="closeContactModal()" class="w-7 h-7 bg-red-500 border-2 border-black text-white font-bold hover:bg-red-700 flex items-center justify-center text-sm">✕</button>
        </div>

        <!-- Body: Form -->
        <div id="contactFormBody" class="p-6 space-y-5 bg-gray-50">
            <div class="border-l-4 border-blue-600 pl-3 mb-2">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">&gt; Kirim pesan atau permintaan ke admin. Kami akan segera merespons.</p>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Nama</label>
                <input type="text" id="contact_nama" placeholder="Nama kamu..."
                    class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2">&gt; Email</label>
                <input type="email" id="contact_email" placeholder="email@kamu.com"
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
                <textarea id="contact_pesan" rows="4" placeholder="Tuliskan pesan atau alasanmu di sini..."
                    class="w-full px-4 py-3 bg-white border-4 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] outline-none font-bold text-black placeholder-gray-400 text-sm resize-none"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button onclick="closeContactModal()" class="w-1/3 bg-gray-300 hover:bg-gray-400 border-4 border-black text-black font-pixel text-[9px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                    BATAL
                </button>
                <button onclick="submitContact()" class="w-2/3 bg-black hover:bg-blue-700 border-4 border-black text-white font-pixel text-[9px] py-4 uppercase shadow-[4px_4px_0px_rgba(0,0,0,1)] transition-none">
                    KIRIM &gt;&gt; ADMIN
                </button>
            </div>
        </div>

        </div>
    </div>
</div>

<script>
    function openContactModal() {
        document.getElementById('contactModal').classList.remove('hidden');
        document.getElementById('contactModal').classList.add('flex');
        document.getElementById('contactFormBody').classList.remove('hidden');
        document.getElementById('contactSuccessBody').classList.add('hidden');

        document.getElementById('contact_nama').value = '';
        document.getElementById('contact_email').value = '';
        document.getElementById('contact_pesan').value = '';
        document.body.style.overflow = 'hidden';
    }

    function closeContactModal() {
        document.getElementById('contactModal').classList.add('hidden');
        document.getElementById('contactModal').classList.remove('flex');
        document.body.style.overflow = '';
    }

    function submitContact() {
        const nama  = document.getElementById('contact_nama').value.trim();
        const email = document.getElementById('contact_email').value.trim();
        const pesan = document.getElementById('contact_pesan').value.trim();

        if (!nama || !email) {
            alert('Nama dan Email wajib diisi.');
            return;
        }

        const btn = event.target;
        btn.textContent = 'MENGIRIM...';
        btn.disabled = true;

        setTimeout(() => {
            document.getElementById('contactFormBody').classList.add('hidden');
            document.getElementById('contactSuccessBody').classList.remove('hidden');
        }, 1200);
    }

    document.getElementById('contactModal').addEventListener('click', function(e) {
        if (e.target === this) closeContactModal();
    });
</script>