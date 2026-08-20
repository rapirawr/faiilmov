<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Admin Panel | faiilmov')</title>
    
    <!-- Favicon & App Icon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Preload Chillax Brand Font -->
    <link rel="preload" href="{{ asset('fonts/chillax/Chillax-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    
    <!-- Chillax Font Definition -->
    <style>
        @font-face {
            font-family: 'Chillax';
            src: url('{{ asset('fonts/chillax/Chillax-Variable.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/chillax/Chillax-Variable.woff') }}') format('woff'),
                 url('{{ asset('fonts/chillax/Chillax-Variable.ttf') }}') format('truetype');
            font-weight: 200 700;
            font-display: swap;
            font-style: normal;
        }
        .font-chillax {
            font-family: 'Chillax', 'Outfit', sans-serif !important;
        }
    </style>
    
    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <style>
        [x-cloak] { display: none !important; }
        .glass-shell {
            background: rgba(18, 18, 20, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .admin-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .admin-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }
        .admin-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
        }
        .admin-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 font-['Plus_Jakarta_Sans',sans-serif] antialiased min-h-screen selection:bg-rose-500 selection:text-white"
      x-data="adminShell()"
      @keydown.ctrl.k.window.prevent="$refs.sidebarSearchInput?.focus(); sidebarOpen = true"
      @keydown.cmd.k.window.prevent="$refs.sidebarSearchInput?.focus(); sidebarOpen = true"
      @keydown.escape.window="closeAllModals()">

    <div class="flex min-h-screen bg-zinc-950">
        
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false" 
             class="fixed inset-0 z-40 bg-black/80 backdrop-blur-sm lg:hidden"
             x-cloak>
        </div>

        <!-- ==================== SIDEBAR SHELL ==================== -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" 
               class="fixed inset-y-0 left-0 z-50 w-72 bg-zinc-950 border-r border-zinc-800/80 transition-transform duration-300 ease-in-out flex flex-col justify-between shadow-2xl">
            
            <div class="flex flex-col flex-1 min-h-0">
                <!-- Brand Header -->
                <div class="p-4 pb-3 border-b border-zinc-800/80 flex items-center justify-between">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 group">
                        <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="h-8 w-auto object-contain transition-transform group-hover:scale-105 shrink-0">
                        <div>
                            <h1 class="font-chillax font-bold text-base tracking-tight text-zinc-100 group-hover:text-zinc-300 transition-colors">faiilmov</h1>
                            <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-wider">ADMIN PANEL</p>
                        </div>
                    </a>

                    <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-xl text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Real-time Live Filter in Sidebar -->
                <div class="px-4 pt-3 pb-1">
                    <div class="relative w-full flex items-center group">
                        <i data-lucide="search" class="w-4 h-4 text-zinc-400 absolute left-3.5 pointer-events-none group-focus-within:text-zinc-200 transition-colors"></i>
                        <input type="text" 
                               x-model="sidebarQuery"
                               x-ref="sidebarSearchInput"
                               @keydown.escape.prevent="sidebarQuery = ''; $refs.sidebarSearchInput.blur()"
                               placeholder="Cari menu sidebar..." 
                               autocomplete="off"
                               class="w-full pl-9 pr-14 py-2 rounded-full bg-zinc-900/90 border border-zinc-800 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700 transition-all font-sans">
                        
                        <div class="absolute right-2.5 flex items-center gap-1">
                            <button type="button" 
                                    x-show="sidebarQuery" 
                                    x-cloak
                                    @click="sidebarQuery = ''; $refs.sidebarSearchInput.focus()"
                                    class="p-0.5 text-zinc-400 hover:text-zinc-100 transition-colors cursor-pointer"
                                    title="Hapus pencarian">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                            <kbd x-show="!sidebarQuery" class="flex items-center gap-0.5 text-[9px] font-mono font-bold bg-zinc-800/90 px-1.5 py-0.5 rounded border border-zinc-700/60 text-zinc-400 pointer-events-none">
                                <span>Ctrl K</span>
                            </kbd>
                        </div>
                    </div>
                </div>

                <!-- Scrollable Navigation Menu -->
                <div class="flex-1 overflow-y-auto px-4 py-2 space-y-3 admin-scrollbar">
                    
                    <!-- Dashboard Overview Pill -->
                    <div x-show="matches('Dashboard Overview Beranda Home')">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-full text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-zinc-100 text-zinc-950 shadow-md font-bold' : 'bg-transparent text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center {{ request()->routeIs('admin.dashboard') ? 'bg-zinc-950 text-zinc-100' : 'bg-zinc-900 text-zinc-400 border border-zinc-800' }}">
                                <i data-lucide="home" class="w-3.5 h-3.5"></i>
                            </div>
                            <span class="font-bold text-xs tracking-wide">Dashboard Overview</span>
                        </a>
                    </div>

                    <!-- Group 1: MANAJEMEN KONTEN -->
                    <div x-data="{ open: {{ (request()->routeIs('admin.films.*') || request()->routeIs('admin.genres.*') || request()->routeIs('admin.actors.*') || request()->routeIs('admin.page_elements.*') || request()->routeIs('admin.collections.*')) ? 'true' : 'true' }} }" 
                         x-show="groupHasMatch(['Manajemen Konten', 'Semua Film Katalog Movie Series Dracin', 'Cari & Impor Film Moviebox Dracin Anichin Importer', 'Smart Collections Koleksi AI Franchise Curation', 'Elemen & Widget CMS Studio Floating Popup Broadcast Custom', 'Request Film Permintaan Permohonan', 'Banner Fitur CMS Header Hero', 'Rating Massal Content Rating Batas Usia Sensor', 'Genre Film Kategori Tag', 'Aktor & Cast Pemeran Artis Pemain'])" 
                         x-init="$watch('sidebarQuery', q => { if (q && q.trim()) open = true; })"
                         class="space-y-1">
                        <button @click="open = !open" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-2xl bg-zinc-900/90 border border-zinc-800/80 hover:bg-zinc-850 text-zinc-200 text-xs font-bold transition-all cursor-pointer">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="film" class="w-4 h-4 text-zinc-400"></i>
                                <span>Manajemen Konten</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-300 text-[10px] font-bold flex items-center justify-center">9</span>
                                <i data-lucide="chevron-up" :class="open ? '' : 'rotate-180'" class="w-4 h-4 text-zinc-400 transition-transform duration-200"></i>
                            </div>
                        </button>

                        <div x-show="open" class="ml-6 pl-3 border-l border-zinc-800 space-y-1 py-1">
                            <!-- Semua Film -->
                            <div x-show="matches('Semua Film Katalog Movie Series Dracin')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.films.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.films.index') || request()->routeIs('admin.films.create') || request()->routeIs('admin.films.edit') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="clapperboard" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Semua Film</span>
                                </a>
                            </div>

                            <!-- Cari & Impor Film -->
                            <div x-show="matches('Cari & Impor Film Moviebox Dracin Anichin Importer')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.films.importer') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.films.importer') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="download-cloud" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Cari & Impor Film</span>
                                </a>
                            </div>

                            <!-- Smart Collections (AI Curation) -->
                            <div x-show="matches('Smart Collections Koleksi AI Franchise Curation')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.collections.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.collections.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="sparkles" class="w-4 h-4 text-zinc-400"></i>
                                    <span class="flex items-center gap-1.5">
                                        Smart Collections
                                        <span class="px-1 py-0.2 rounded text-[9px] font-mono bg-zinc-800 text-zinc-400 border border-zinc-700">AI</span>
                                    </span>
                                </a>
                            </div>

                            <!-- Elemen & Widget (CMS Studio) -->
                            <div x-show="matches('Elemen & Widget CMS Studio Floating Popup Broadcast Custom')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.page_elements.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.page_elements.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="layout-template" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Elemen & Widget (CMS)</span>
                                </a>
                            </div>

                            <!-- Request Film -->
                            <div x-show="matches('Request Film Permintaan Permohonan')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.film-requests.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.film-requests.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="inbox" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Request Film</span>
                                    </div>
                                    @if(($adminPendingRequestsCount ?? 0) > 0)
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                            {{ $adminPendingRequestsCount }}
                                        </span>
                                    @endif
                                </a>
                            </div>

                            <!-- Banner Fitur (CMS) -->
                            <div x-show="matches('Banner Fitur CMS Header Hero')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.feature-banners.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.feature-banners.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="layout-template" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Banner Fitur (CMS)</span>
                                    </div>
                                </a>
                            </div>

                            <!-- Rating Massal -->
                            <div x-show="matches('Rating Massal Content Rating Batas Usia Sensor')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.films.content_rating') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.films.content_rating') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="shield-alert" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Rating Massal</span>
                                </a>
                            </div>

                            <!-- Genre Film -->
                            <div x-show="matches('Genre Film Kategori Tag')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.genres.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.genres.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="tags" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Genre Film</span>
                                </a>
                            </div>

                            <!-- Aktor & Cast -->
                            <div x-show="matches('Aktor & Cast Pemeran Artis Pemain')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.actors.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.actors.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="users" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Aktor & Cast</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Group 2: MODERASI & USER -->
                    <div x-data="{ open: {{ (request()->routeIs('admin.watch_parties.*') || request()->routeIs('admin.reviews.*') || request()->routeIs('admin.comments.*') || request()->routeIs('admin.users.*') || request()->routeIs('admin.notifications.*')) ? 'true' : 'true' }} }" 
                         x-show="groupHasMatch(['Moderasi & User', 'Watch Parties Nobar Live Room Ruangan', 'Moderasi Ulasan Review Komentar Laporan Report', 'Komentar Episode Series Diskusi Spoiler', 'Kelola Pengguna User Akun Ban Banned Role Admin', 'Broadcast Notifikasi Push Notification Pesan Blast'])" 
                         x-init="$watch('sidebarQuery', q => { if (q && q.trim()) open = true; })"
                         class="space-y-1">
                        <button @click="open = !open" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-2xl bg-zinc-900/90 border border-zinc-800/80 hover:bg-zinc-850 text-zinc-200 text-xs font-bold transition-all cursor-pointer">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="shield" class="w-4 h-4 text-zinc-400"></i>
                                <span>Moderasi & User</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-300 text-[10px] font-bold flex items-center justify-center">5</span>
                                <i data-lucide="chevron-up" :class="open ? '' : 'rotate-180'" class="w-4 h-4 text-zinc-400 transition-transform duration-200"></i>
                            </div>
                        </button>

                        <div x-show="open" class="ml-6 pl-3 border-l border-zinc-800 space-y-1 py-1">
                            <!-- Watch Parties -->
                            <div x-show="matches('Watch Parties Nobar Live Room Ruangan')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.watch_parties.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.watch_parties.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="tv" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Watch Parties</span>
                                    </div>
                                    @if(isset($adminActiveWatchPartiesCount) && $adminActiveWatchPartiesCount > 0)
                                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                            {{ $adminActiveWatchPartiesCount }} Live
                                        </span>
                                    @endif
                                </a>
                            </div>

                            <!-- Moderasi Ulasan -->
                            <div x-show="matches('Moderasi Ulasan Review Komentar Laporan Report')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.reviews.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.reviews.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="message-square" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Moderasi Ulasan</span>
                                    </div>
                                    @if(isset($adminPendingReportsCount) && $adminPendingReportsCount > 0)
                                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                            {{ $adminPendingReportsCount }}
                                        </span>
                                    @endif
                                </a>
                            </div>

                            <!-- Komentar Episode -->
                            <div x-show="matches('Komentar Episode Series Diskusi Spoiler')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.comments.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.comments.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="messages-square" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Komentar Episode</span>
                                    </div>
                                </a>
                            </div>

                            <!-- Kelola Pengguna -->
                            <div x-show="matches('Kelola Pengguna User Akun Ban Banned Role Admin')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.users.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="user-check" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Kelola Pengguna</span>
                                </a>
                            </div>

                            <!-- Broadcast Notifikasi -->
                            <div x-show="matches('Broadcast Notifikasi Push Notification Pesan Blast')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.notifications.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.notifications.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="bell-ring" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Broadcast Notifikasi</span>
                                </a>
                            </div>

                            <!-- Gamification & Badges CMS -->
                            <div x-show="matches('Gamification Badges Lencana XP Leaderboard Peringkat Wrapped Cinephile')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.gamification.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.gamification.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="trophy" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Gamification & Badges</span>
                                    </div>
                                    <span class="px-1.5 py-0.2 rounded-full text-[9px] font-mono font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">
                                        XP
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Group 3: SISTEM & LOG -->
                    <div x-data="{ open: {{ (request()->routeIs('admin.api_tester.*') || request()->routeIs('admin.scripts.*') || request()->routeIs('admin.changelogs.*') || request()->routeIs('admin.activity_logs.*') || request()->routeIs('admin.app_release.*') || request()->routeIs('admin.navigation.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.ads.*')) ? 'true' : 'true' }} }" 
                         x-show="groupHasMatch(['Sistem & Log', 'API Tester Docs Endpoint Swagger Dokumentasi', 'PHP Script Runner Eksekusi Script Terminal Artisan', 'Changelog & Updates Rilis Versi Pembaruan Update', 'Activity Audit Log Aktivitas Riwayat Log Admin', 'Rilis APK Mobile Download Android App Release', 'Kelola Menu Sidebar Navigasi Navigation Urutan', 'Pengaturan Umum Settings Konfigurasi Web Website', 'Manajemen Iklan Ads Adsterra Banner Popunder Socialbar'])" 
                         x-init="$watch('sidebarQuery', q => { if (q && q.trim()) open = true; })"
                         class="space-y-1">
                        <button @click="open = !open" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-2xl bg-zinc-900/90 border border-zinc-800/80 hover:bg-zinc-850 text-zinc-200 text-xs font-bold transition-all cursor-pointer">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="settings" class="w-4 h-4 text-zinc-400"></i>
                                <span>Sistem & Log</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-300 text-[10px] font-bold flex items-center justify-center">{{ Auth::user()?->isAdministrator() ? '8' : '2' }}</span>
                                <i data-lucide="chevron-up" :class="open ? '' : 'rotate-180'" class="w-4 h-4 text-zinc-400 transition-transform duration-200"></i>
                            </div>
                        </button>

                        <div x-show="open" class="ml-6 pl-3 border-l border-zinc-800 space-y-1 py-1">
                            @if(Auth::user()?->isAdministrator())
                                <!-- API Tester & Docs -->
                                <div x-show="matches('API Tester Docs Endpoint Swagger Dokumentasi')" class="relative flex items-center">
                                    <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                    <a href="{{ route('admin.api_tester.index') }}" 
                                       class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.api_tester.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                        <i data-lucide="terminal" class="w-4 h-4 text-zinc-400"></i>
                                        <span>API Tester & Docs</span>
                                    </a>
                                </div>

                                <!-- PHP Script Runner -->
                                <div x-show="matches('PHP Script Runner Eksekusi Script Terminal Artisan')" class="relative flex items-center">
                                    <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                    <a href="{{ route('admin.scripts.index') }}" 
                                       class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.scripts.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                        <i data-lucide="code" class="w-4 h-4 text-zinc-400"></i>
                                        <span>PHP Script Runner</span>
                                    </a>
                                </div>
                            @endif

                            <!-- Changelog & Updates -->
                            <div x-show="matches('Changelog & Updates Rilis Versi Pembaruan Update')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.changelogs.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.changelogs.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="file-clock" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Changelog & Updates</span>
                                </a>
                            </div>

                            <!-- Activity Audit Log -->
                            <div x-show="matches('Activity Audit Log Aktivitas Riwayat Log Admin')" class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.activity_logs.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.activity_logs.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                    <i data-lucide="history" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Activity Audit Log</span>
                                </a>
                            </div>

                            @if(Auth::user()?->isAdministrator())
                                <!-- Rilis APK Mobile -->
                                <div x-show="matches('Rilis APK Mobile Download Android App Release')" class="relative flex items-center">
                                    <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                    <a href="{{ route('admin.app_release.index') }}" 
                                       class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.app_release.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                        <i data-lucide="smartphone" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Rilis APK Mobile</span>
                                    </a>
                                </div>

                                <!-- Kelola Menu Sidebar -->
                                <div x-show="matches('Kelola Menu Sidebar Navigasi Navigation Urutan')" class="relative flex items-center">
                                    <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                    <a href="{{ route('admin.navigation.index') }}" 
                                       class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.navigation.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                        <i data-lucide="layout-grid" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Kelola Menu Sidebar</span>
                                    </a>
                                </div>

                                <!-- Pengaturan Umum -->
                                <div x-show="matches('Pengaturan Umum Settings Konfigurasi Web Website')" class="relative flex items-center">
                                    <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                    <a href="{{ route('admin.settings.index') }}" 
                                       class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.settings.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                        <i data-lucide="sliders" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Pengaturan Umum</span>
                                    </a>
                                </div>

                                <!-- Manajemen Iklan -->
                                <div x-show="matches('Manajemen Iklan Ads Adsterra Banner Popunder Socialbar')" class="relative flex items-center">
                                    <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                    <a href="{{ route('admin.ads.index') }}" 
                                       class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.ads.*') ? 'text-zinc-100 font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/40' }}">
                                        <div class="flex items-center gap-2.5">
                                            <i data-lucide="dollar-sign" class="w-4 h-4 text-zinc-400"></i>
                                            <span>Manajemen Iklan</span>
                                        </div>
                                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">
                                            ADS
                                        </span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Empty state if no menu matches -->
                    <div x-show="sidebarQuery.trim() && !matches('Dashboard Overview Beranda Home') && !groupHasMatch(['Manajemen Konten', 'Semua Film Katalog Movie Series Dracin', 'Cari & Impor Film Moviebox Dracin Anichin Importer', 'Request Film Permintaan Permohonan', 'Banner Fitur CMS Header Hero', 'Rating Massal Content Rating Batas Usia Sensor', 'Genre Film Kategori Tag', 'Aktor & Cast Pemeran Artis Pemain', 'Moderasi & User', 'Watch Parties Nobar Live Room Ruangan', 'Moderasi Ulasan Review Komentar Laporan Report', 'Kelola Pengguna User Akun Ban Banned Role Admin', 'Broadcast Notifikasi Push Notification Pesan Blast', 'Sistem & Log', 'API Tester Docs Endpoint Swagger Dokumentasi', 'PHP Script Runner Eksekusi Script Terminal Artisan', 'Changelog & Updates Rilis Versi Pembaruan Update', 'Activity Audit Log Aktivitas Riwayat Log Admin', 'Rilis APK Mobile Download Android App Release', 'Kelola Menu Sidebar Navigasi Navigation Urutan', 'Pengaturan Umum Settings Konfigurasi Web Website', 'Manajemen Iklan Ads Adsterra Banner Popunder Socialbar'])" 
                         class="py-6 text-center text-xs text-zinc-500 space-y-2"
                         x-cloak>
                        <i data-lucide="search-x" class="w-6 h-6 mx-auto text-zinc-600"></i>
                        <p>Tidak ada menu yang cocok</p>
                        <button type="button" @click="sidebarQuery = ''" class="px-2.5 py-1 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300 text-[11px] font-semibold hover:bg-zinc-800 hover:text-zinc-100 transition-colors cursor-pointer">
                            Reset Pencarian
                        </button>
                    </div>

                </div>
            </div>

            <!-- Bottom User Profile Footer Tile -->
            <div class="p-3 border-t border-zinc-800/80 bg-zinc-950">
                <div class="flex items-center justify-between p-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full overflow-hidden bg-zinc-800 shrink-0 border border-zinc-700 flex items-center justify-center">
                            <img src="{{ Auth::user()?->avatar_url }}" alt="{{ Auth::user()?->name }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode(Auth::user()?->name ?? 'Admin') }}';">
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <p class="font-bold text-zinc-100 text-xs truncate">{{ Auth::user()?->name ?? 'Admin' }}</p>
                                @if(Auth::user()?->isAdministrator())
                                    <span class="px-1.5 py-0.2 rounded text-[8px] font-extrabold uppercase bg-zinc-800 text-zinc-300 border border-zinc-700">Superadmin</span>
                                @else
                                    <span class="px-1.5 py-0.2 rounded text-[8px] font-extrabold uppercase bg-zinc-800 text-zinc-300 border border-zinc-700">Admin</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-zinc-400 truncate">{{ Auth::user()?->email }}</p>
                        </div>
                    </div>
                    
                    <a href="{{ route('home') }}" class="p-2 text-zinc-400 hover:text-zinc-100 transition-colors" title="Kunjungi Situs Publik" target="_blank">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- ==================== MAIN CONTENT AREA ==================== -->
        <div class="flex-1 lg:pl-72 flex flex-col min-h-screen min-w-0">
            
            <!-- Sticky Top Navbar -->
            <header class="h-16 bg-zinc-950/80 border-b border-white/10 backdrop-blur-xl sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between gap-4">
                
                <!-- Left: Mobile Toggle & Title Panel -->
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-zinc-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    
                    <!-- Title Panel -->
                    <div class="flex items-center gap-2.5 min-w-0">
                        <h1 class="text-sm sm:text-base font-bold text-white font-['Outfit'] tracking-tight truncate">
                            @yield('page_title', 'Dashboard')
                        </h1>
                    </div>
                </div>

                <!-- Right: Quick Actions, Activity Bell, and Admin Profile Dropdown -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <!-- Activity Log Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                @click.outside="open = false"
                                class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white border border-white/10 transition-colors relative cursor-pointer"
                                title="Aktivitas Admin Terbaru">
                            <i data-lucide="bell" class="w-4 h-4"></i>
                            @if(isset($adminPendingReportsCount) && $adminPendingReportsCount > 0)
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500"></span>
                            @endif
                        </button>

                        <!-- Activity Dropdown Menu -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-zinc-900 border border-white/15 shadow-2xl overflow-hidden z-50 p-2 text-white"
                             x-cloak>
                            <div class="flex items-center justify-between p-2.5 border-b border-white/10">
                                <span class="font-bold text-xs uppercase tracking-wider text-zinc-300">Aktivitas Terkini</span>
                                <a href="{{ route('admin.activity_logs.index') }}" class="text-[11px] text-rose-400 hover:text-rose-300 font-semibold">Lihat Semua</a>
                            </div>
                            
                            <div class="divide-y divide-white/5 max-h-72 overflow-y-auto admin-scrollbar py-1">
                                @if(isset($adminRecentActivityLogs) && count($adminRecentActivityLogs) > 0)
                                    @foreach($adminRecentActivityLogs as $log)
                                        <div class="p-2.5 hover:bg-white/5 rounded-xl transition-colors text-xs space-y-1">
                                            <div class="flex items-center justify-between text-[10px] text-zinc-400">
                                                <span class="font-semibold text-zinc-300">{{ $log->admin->name ?? 'System Admin' }}</span>
                                                <span>{{ $log->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-zinc-200 line-clamp-2 leading-relaxed">{{ $log->description ?: $log->action }}</p>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="py-6 text-center text-xs text-zinc-500">
                                        Belum ada aktivitas admin tercatat.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Admin Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                @click.outside="open = false"
                                class="flex items-center gap-2.5 bg-white/5 hover:bg-white/10 px-2.5 py-1.5 rounded-2xl border border-white/10 transition-all cursor-pointer">
                            <div class="w-7 h-7 rounded-full overflow-hidden bg-zinc-800 border border-white/15 shrink-0 flex items-center justify-center">
                                <img src="{{ Auth::user()?->avatar_url }}" alt="{{ Auth::user()?->name }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode(Auth::user()?->name ?? 'Admin') }}';">
                            </div>
                            <span class="text-xs font-bold text-white hidden md:inline">{{ Auth::user()->name ?? 'Admin' }}</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-zinc-400"></i>
                        </button>

                        <!-- User Profile Menu -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 rounded-2xl bg-zinc-900 border border-white/15 shadow-2xl p-1.5 text-white z-50"
                             x-cloak>
                            <div class="p-2.5 border-b border-white/10 flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full overflow-hidden bg-zinc-800 border border-zinc-700 shrink-0 flex items-center justify-center">
                                    <img src="{{ Auth::user()?->avatar_url }}" alt="{{ Auth::user()?->name }}" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-white truncate">{{ Auth::user()?->name ?? 'Administrator' }}</p>
                                    <p class="text-[10px] text-zinc-400 truncate">{{ Auth::user()?->email ?? 'admin@faiilmov.my.id' }}</p>
                                </div>
                            </div>

                            <div class="py-1 space-y-0.5 text-xs">
                                <a href="{{ route('profile') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-zinc-300 hover:text-white hover:bg-white/10 transition-colors">
                                    <i data-lucide="user" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Profil Saya</span>
                                </a>
                                <a href="{{ route('admin.activity_logs.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-zinc-300 hover:text-white hover:bg-white/10 transition-colors">
                                    <i data-lucide="history" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Riwayat Aksi</span>
                                </a>
                                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-zinc-300 hover:text-white hover:bg-white/10 transition-colors">
                                    <i data-lucide="settings" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Pengaturan</span>
                                </a>
                            </div>

                            <div class="pt-1 border-t border-white/10">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 text-xs font-bold transition-colors cursor-pointer">
                                        <i data-lucide="log-out" class="w-4 h-4"></i>
                                        <span>Keluar (Logout)</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            <!-- ==================== PAGE MAIN CONTAINER ==================== -->
            <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto space-y-6">
                
                <!-- Flash Toast Alerts -->
                @if(session('success'))
                    <div x-data="{ show: true }" 
                         x-show="show" 
                         x-init="setTimeout(() => show = false, 5000)"
                         x-transition
                         class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-emerald-500/5">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                            <span class="font-semibold">{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-400 hover:text-white p-1">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" 
                         x-show="show" 
                         x-transition
                         class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-rose-500/5">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-400 shrink-0"></i>
                            <span class="font-semibold">{{ session('error') }}</span>
                        </div>
                        <button @click="show = false" class="text-rose-400 hover:text-white p-1">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Admin Footer -->
            <footer class="py-6 border-t border-white/5 text-center text-xs text-zinc-500">
                <p>&copy; {{ date('Y') }} <strong>faiilmov CMS</strong> &bull; Workspace v2.5 &bull; All Rights Reserved.</p>
            </footer>

        </div>
    </div>

    <!-- React Global Modal Portal Container -->
    <div id="react-global-modal"></div>

    @stack('scripts')

    <script>
        function adminShell() {
            return {
                sidebarOpen: false,
                sidebarQuery: '',
                matches(text) {
                    if (!this.sidebarQuery || !this.sidebarQuery.trim()) return true;
                    const q = this.sidebarQuery.toLowerCase().trim();
                    const words = q.split(/\s+/).filter(Boolean);
                    const target = String(text || '').toLowerCase();
                    return words.every(w => target.includes(w));
                },
                groupHasMatch(items) {
                    if (!this.sidebarQuery || !this.sidebarQuery.trim()) return true;
                    return Array.isArray(items) && items.some(t => this.matches(t));
                },
                closeAllModals() {
                    this.sidebarOpen = false;
                }
            };
        }

        const initLucideIcons = () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        };

        document.addEventListener('DOMContentLoaded', initLucideIcons);
        window.addEventListener('load', initLucideIcons);
        document.addEventListener('alpine:initialized', initLucideIcons);
        document.addEventListener('alpine:navigated', initLucideIcons);
        // Fallback polling for dynamically rendered components
        setTimeout(initLucideIcons, 300);
        setTimeout(initLucideIcons, 1000);
    </script>
    @stack('scripts')
</body>
</html>
