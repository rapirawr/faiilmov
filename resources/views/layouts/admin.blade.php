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
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
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
      @keydown.window.ctrl.k.prevent="openSearchModal()"
      @keydown.window.cmd.k.prevent="openSearchModal()"
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
                            <h1 class="font-extrabold text-sm tracking-wider text-white uppercase font-['Outfit'] group-hover:text-amber-400 transition-colors">FAIILMOV</h1>
                            <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-wider">ADMIN PANEL</p>
                        </div>
                    </a>

                    <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Global Search Trigger Pill -->
                <div class="px-4 pt-3 pb-1">
                    <button @click="openSearchModal()" 
                            type="button"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-full bg-zinc-900/90 border border-zinc-800 text-zinc-400 hover:text-zinc-200 hover:border-zinc-700 transition-all text-xs group cursor-pointer">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="search" class="w-4 h-4 text-zinc-400"></i>
                            <span class="text-zinc-400 text-xs">Search movies...</span>
                        </div>
                        <kbd class="flex items-center gap-0.5 text-[10px] font-mono font-bold bg-zinc-800/90 px-2 py-0.5 rounded-md border border-zinc-700/60 text-zinc-300">
                            <span>Ctrl K</span>
                        </kbd>
                    </button>
                </div>

                <!-- Scrollable Navigation Menu -->
                <div class="flex-1 overflow-y-auto px-4 py-2 space-y-3 admin-scrollbar">
                    
                    <!-- Dashboard Overview Pill -->
                    <div>
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-full text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-white text-zinc-950 shadow-md font-bold' : 'bg-transparent text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center {{ request()->routeIs('admin.dashboard') ? 'bg-zinc-950 text-white' : 'bg-zinc-900 text-zinc-400 border border-zinc-800' }}">
                                <i data-lucide="home" class="w-3.5 h-3.5"></i>
                            </div>
                            <span class="font-bold text-xs tracking-wide">Dashboard Overview</span>
                        </a>
                    </div>

                    <!-- Group 1: MANAJEMEN KONTEN -->
                    <div x-data="{ open: {{ (request()->routeIs('admin.films.*') || request()->routeIs('admin.genres.*') || request()->routeIs('admin.actors.*')) ? 'true' : 'true' }} }" class="space-y-1">
                        <button @click="open = !open" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-2xl bg-zinc-900/90 border border-zinc-800/80 hover:bg-zinc-850 text-white text-xs font-bold transition-all cursor-pointer">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="film" class="w-4 h-4 text-zinc-300"></i>
                                <span>Manajemen Konten</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-300 text-[10px] font-bold flex items-center justify-center">4</span>
                                <i data-lucide="chevron-up" :class="open ? '' : 'rotate-180'" class="w-4 h-4 text-zinc-400 transition-transform duration-200"></i>
                            </div>
                        </button>

                        <div x-show="open" class="ml-6 pl-3 border-l border-zinc-800 space-y-1 py-1">
                            <!-- Semua Film -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.films.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.films.index') || request()->routeIs('admin.films.create') || request()->routeIs('admin.films.edit') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="clapperboard" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Semua Film</span>
                                </a>
                            </div>

                            <!-- Request Film -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.film-requests.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.film-requests.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="inbox" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Request Film</span>
                                    </div>
                                    @if(($adminPendingRequestsCount ?? 0) > 0)
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500 text-black">
                                            {{ $adminPendingRequestsCount }}
                                        </span>
                                    @endif
                                </a>
                            </div>

                            <!-- Banner Fitur (CMS) -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.feature-banners.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.feature-banners.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="layout-template" class="w-4 h-4 text-zinc-400"></i>
                                        <span>Banner Fitur (CMS)</span>
                                    </div>
                                </a>
                            </div>

                            <!-- Rating Massal -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.films.content_rating') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.films.content_rating') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="shield-alert" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Rating Massal</span>
                                </a>
                            </div>

                            <!-- Genre Film -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.genres.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.genres.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="tags" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Genre Film</span>
                                </a>
                            </div>

                            <!-- Aktor & Cast -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.actors.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.actors.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="users" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Aktor & Cast</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Group 2: MODERASI & USER -->
                    <div x-data="{ open: {{ (request()->routeIs('admin.watch_parties.*') || request()->routeIs('admin.reviews.*') || request()->routeIs('admin.users.*') || request()->routeIs('admin.notifications.*')) ? 'true' : 'true' }} }" class="space-y-1">
                        <button @click="open = !open" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-2xl bg-zinc-900/90 border border-zinc-800/80 hover:bg-zinc-850 text-white text-xs font-bold transition-all cursor-pointer">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="shield" class="w-4 h-4 text-zinc-400"></i>
                                <span>Moderasi & User</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-300 text-[10px] font-bold flex items-center justify-center">4</span>
                                <i data-lucide="chevron-up" :class="open ? '' : 'rotate-180'" class="w-4 h-4 text-zinc-400 transition-transform duration-200"></i>
                            </div>
                        </button>

                        <div x-show="open" class="ml-6 pl-3 border-l border-zinc-800 space-y-1 py-1">
                            <!-- Watch Parties -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.watch_parties.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.watch_parties.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
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
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.reviews.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.reviews.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
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

                            <!-- Kelola Pengguna -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.users.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="user-check" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Kelola Pengguna</span>
                                </a>
                            </div>

                            <!-- Broadcast Notifikasi -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.notifications.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.notifications.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="bell-ring" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Broadcast Notifikasi</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Group 3: SISTEM & LOG -->
                    <div x-data="{ open: {{ (request()->routeIs('admin.api_tester.*') || request()->routeIs('admin.scripts.*') || request()->routeIs('admin.changelogs.*') || request()->routeIs('admin.activity_logs.*') || request()->routeIs('admin.app_release.*') || request()->routeIs('admin.navigation.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.ads.*')) ? 'true' : 'true' }} }" class="space-y-1">
                        <button @click="open = !open" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-2xl bg-zinc-900/90 border border-zinc-800/80 hover:bg-zinc-850 text-white text-xs font-bold transition-all cursor-pointer">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="settings" class="w-4 h-4 text-zinc-400"></i>
                                <span>Sistem & Log</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-800 text-zinc-300 text-[10px] font-bold flex items-center justify-center">8</span>
                                <i data-lucide="chevron-up" :class="open ? '' : 'rotate-180'" class="w-4 h-4 text-zinc-400 transition-transform duration-200"></i>
                            </div>
                        </button>

                        <div x-show="open" class="ml-6 pl-3 border-l border-zinc-800 space-y-1 py-1">
                            <!-- API Tester & Docs -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.api_tester.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.api_tester.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="terminal" class="w-4 h-4 text-zinc-400"></i>
                                    <span>API Tester & Docs</span>
                                </a>
                            </div>

                            <!-- PHP Script Runner -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.scripts.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.scripts.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="code" class="w-4 h-4 text-zinc-400"></i>
                                    <span>PHP Script Runner</span>
                                </a>
                            </div>

                            <!-- Changelog & Updates -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.changelogs.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.changelogs.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="file-clock" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Changelog & Updates</span>
                                </a>
                            </div>

                            <!-- Activity Audit Log -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.activity_logs.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.activity_logs.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="history" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Activity Audit Log</span>
                                </a>
                            </div>

                            <!-- Rilis APK Mobile -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.app_release.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.app_release.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="smartphone" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Rilis APK Mobile</span>
                                </a>
                            </div>

                            <!-- Kelola Menu Sidebar -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.navigation.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.navigation.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="layout-grid" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Kelola Menu Sidebar</span>
                                </a>
                            </div>

                            <!-- Pengaturan Umum -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.settings.index') }}" 
                                   class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.settings.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <i data-lucide="sliders" class="w-4 h-4 text-zinc-400"></i>
                                    <span>Pengaturan Umum</span>
                                </a>
                            </div>

                            <!-- Manajemen Iklan -->
                            <div class="relative flex items-center">
                                <span class="absolute -left-3 top-1/2 -translate-y-1/2 w-2 h-[1px] bg-zinc-800"></span>
                                <a href="{{ route('admin.ads.index') }}" 
                                   class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.ads.*') ? 'text-white font-bold bg-zinc-900/80' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/40' }}">
                                    <div class="flex items-center gap-2.5">
                                        <i data-lucide="dollar-sign" class="w-4 h-4 text-amber-400"></i>
                                        <span>Manajemen Iklan</span>
                                    </div>
                                    <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        ADS
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Bottom User Profile Footer Tile -->
            <div class="p-3 border-t border-zinc-800/80 bg-zinc-950">
                <div class="flex items-center justify-between p-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-zinc-800 text-white font-bold text-xs flex items-center justify-center shrink-0 border border-zinc-700">
                            {{ strtoupper(substr(Auth::user()?->name ?? 'RA', 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-white text-xs truncate">{{ Auth::user()?->name ?? 'Rafi Af' }}</p>
                            <p class="text-[11px] text-zinc-400 truncate">{{ Auth::user()?->email ?? 'rabdillahf09@gmail.com' }}</p>
                        </div>
                    </div>
                    
                    <a href="{{ route('home') }}" class="p-2 text-zinc-400 hover:text-white transition-colors" title="Kunjungi Situs Publik" target="_blank">
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
                    
                    <!-- Search Button on Mobile/Tablet -->
                    <button @click="openSearchModal()" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white border border-white/10 transition-colors sm:hidden">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>

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
                            <div class="w-7 h-7 rounded-xl bg-white text-zinc-950 font-black text-xs flex items-center justify-center shadow-sm">
                                {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
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
                            <div class="p-2.5 border-b border-white/10">
                                <p class="font-bold text-xs text-white truncate">{{ Auth::user()?->name ?? 'Administrator' }}</p>
                                <p class="text-[10px] text-zinc-400 truncate">{{ Auth::user()?->email ?? 'admin@faiilmov.my.id' }}</p>
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

    <!-- ==================== GLOBAL QUICK SEARCH MODAL (Ctrl + K) ==================== -->
    <div x-show="searchModalOpen" 
         class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:p-6 md:p-20 overflow-y-auto"
         x-cloak>
        
        <!-- Backdrop -->
        <div x-show="searchModalOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeSearchModal()" 
             class="fixed inset-0 bg-black/80 backdrop-blur-md"></div>

        <!-- Dialog Box -->
        <div x-show="searchModalOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative w-full max-w-2xl bg-zinc-900 border border-white/20 rounded-3xl shadow-2xl overflow-hidden z-10 text-white">
            
            <!-- Search Header Bar -->
            <div class="p-4 border-b border-white/10 flex items-center gap-3">
                <i data-lucide="search" class="w-5 h-5 text-zinc-400"></i>
                <input x-ref="searchInput"
                       type="text" 
                       x-model="searchQuery" 
                       @input.debounce.300ms="executeSearch()"
                       placeholder="Ketik judul film, nama pengguna, atau menu admin..." 
                       class="w-full bg-transparent border-none text-sm text-white placeholder-zinc-500 focus:outline-none focus:ring-0">
                
                <span x-show="isSearching" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <kbd @click="closeSearchModal()" class="px-2 py-1 rounded-lg bg-white/10 text-zinc-400 text-[10px] font-mono cursor-pointer hover:text-white">ESC</kbd>
            </div>

            <!-- Search Results Body -->
            <div class="max-h-96 overflow-y-auto p-4 space-y-4 admin-scrollbar">
                
                <!-- Initial State / Suggestions -->
                <div x-show="!searchQuery && searchResults.menus.length === 0" class="py-8 text-center text-xs text-zinc-500 space-y-2">
                    <i data-lucide="sparkles" class="w-8 h-8 text-zinc-600 mx-auto"></i>
                    <p class="font-semibold text-zinc-400">Pencarian Cepat Admin</p>
                    <p>Ketik apa saja untuk mencari film, data user, atau navigasi ke menu CMS.</p>
                </div>

                <!-- Navigation Menus Section -->
                <div x-show="searchResults.menus && searchResults.menus.length > 0" class="space-y-1.5">
                    <div class="text-[10px] font-extrabold uppercase tracking-widest text-zinc-500 px-2">Menu Halaman</div>
                    <template x-for="menu in searchResults.menus" :key="menu.url">
                        <a :href="menu.url" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-white/10 transition-colors text-xs text-zinc-200 hover:text-white group">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-zinc-300 group-hover:text-white">
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="font-bold" x-text="menu.title"></span>
                            </div>
                            <span class="text-[10px] font-mono text-zinc-500 uppercase" x-text="menu.category"></span>
                        </a>
                    </template>
                </div>

                <!-- Films Section -->
                <div x-show="searchResults.films && searchResults.films.length > 0" class="space-y-1.5">
                    <div class="text-[10px] font-extrabold uppercase tracking-widest text-zinc-500 px-2">Katalog Film & Dracin</div>
                    <template x-for="film in searchResults.films" :key="film.id">
                        <a :href="film.url" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-white/10 transition-colors text-xs text-zinc-200 hover:text-white group">
                            <div class="flex items-center gap-3 min-w-0">
                                <img :src="film.poster" class="w-8 h-11 object-cover rounded-lg bg-zinc-800 shrink-0">
                                <div class="min-w-0">
                                    <p class="font-bold truncate text-white" x-text="film.title"></p>
                                    <p class="text-[10px] text-zinc-400" x-text="film.year + ' &bull; ' + film.type"></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded-lg bg-white/10 text-[10px] font-bold text-zinc-300 group-hover:bg-white group-hover:text-black">Edit</span>
                        </a>
                    </template>
                </div>

                <!-- Users Section -->
                <div x-show="searchResults.users && searchResults.users.length > 0" class="space-y-1.5">
                    <div class="text-[10px] font-extrabold uppercase tracking-widest text-zinc-500 px-2">Pengguna</div>
                    <template x-for="user in searchResults.users" :key="user.id">
                        <a :href="user.url" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-white/10 transition-colors text-xs text-zinc-200 hover:text-white group">
                            <div class="flex items-center gap-3 min-w-0">
                                <img :src="user.avatar" class="w-8 h-8 rounded-full bg-zinc-800 shrink-0">
                                <div class="min-w-0">
                                    <p class="font-bold truncate text-white" x-text="user.name"></p>
                                    <p class="text-[10px] text-zinc-400" x-text="user.email"></p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-zinc-400 group-hover:text-white">Detail</span>
                        </a>
                    </template>
                </div>

                <!-- Empty Search Result -->
                <div x-show="searchQuery && !isSearching && searchResults.films.length === 0 && searchResults.users.length === 0 && searchResults.menus.length === 0" 
                     class="py-8 text-center text-xs text-zinc-500">
                    Tidak ditemukan data atau menu yang cocok dengan "<span x-text="searchQuery" class="text-white font-semibold"></span>".
                </div>

            </div>

            <!-- Footer Hint -->
            <div class="p-3 bg-zinc-950 border-t border-white/10 flex items-center justify-between text-[11px] text-zinc-500">
                <span>Tekan <kbd class="px-1.5 py-0.5 bg-white/10 rounded text-[10px] font-mono text-zinc-300">ESC</kbd> untuk menutup</span>
                <span>Faiilmov CMS Search</span>
            </div>

        </div>
    </div>

    @stack('scripts')

    <script>
        function adminShell() {
            return {
                sidebarOpen: false,
                searchModalOpen: false,
                searchQuery: '',
                isSearching: false,
                searchResults: {
                    films: [],
                    users: [],
                    menus: []
                },
                openSearchModal() {
                    this.searchModalOpen = true;
                    this.$nextTick(() => {
                        if (this.$refs.searchInput) {
                            this.$refs.searchInput.focus();
                        }
                    });
                },
                closeSearchModal() {
                    this.searchModalOpen = false;
                    this.searchQuery = '';
                    this.searchResults = { films: [], users: [], menus: [] };
                },
                closeAllModals() {
                    this.searchModalOpen = false;
                    this.sidebarOpen = false;
                },
                async executeSearch() {
                    const q = this.searchQuery.trim();
                    if (!q) {
                        this.searchResults = { films: [], users: [], menus: [] };
                        return;
                    }
                    this.isSearching = true;
                    try {
                        const res = await fetch(`/admin/quick-search?q=${encodeURIComponent(q)}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.searchResults = data;
                            this.$nextTick(() => {
                                if (window.lucide) {
                                    lucide.createIcons();
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Quick search error:', e);
                    } finally {
                        this.isSearching = false;
                    }
                }
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
