<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel | faiilmov')</title>
    
    <!-- Favicon & App Icon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-zinc-950 text-zinc-100 font-['Plus_Jakarta_Sans',sans-serif] antialiased min-h-screen selection:bg-white selection:text-black">

    <div x-data="{ 
            sidebarOpen: false, 
            searchQuery: '',
            openGroups: {
                content: true,
                moderation: true,
                system: true
            }
         }" class="flex min-h-screen">
        
        <!-- Sidebar Navigation -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" 
               class="-translate-x-full lg:translate-x-0 fixed inset-y-0 left-0 z-50 w-72 bg-zinc-900/95 border-r border-white/10 backdrop-blur-2xl transition-transform duration-300 ease-in-out flex flex-col justify-between shadow-2xl">
            
            <div class="flex-1 overflow-y-auto px-4 py-5 space-y-5 scrollbar-thin scrollbar-thumb-zinc-800">
                
                <!-- Brand Header -->
                <div class="flex items-center gap-3 px-2 pb-2 border-b border-white/10">
                    <img src="{{ asset('images/logo.png') }}" alt="FAIIlMOV" class="h-10 w-auto object-contain rounded-xl">
                    <div class="flex-1 min-w-0">
                        <h2 class="font-['Outfit'] font-extrabold text-base tracking-wide text-white uppercase truncate">FAIIlMOV</h2>
                        <p class="text-[10px] font-extrabold tracking-wider text-zinc-400 uppercase">ADMIN PANEL</p>
                    </div>
                    <button @click="sidebarOpen = false" class="lg:hidden text-zinc-400 hover:text-white p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Search Input Bar -->
                <div class="flex items-center gap-2.5 px-3.5 rounded-full border border-white/15 bg-zinc-950/90 focus-within:border-white/50 transition-colors">
                    <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-400"></i>
                    <input type="text" 
                           x-model="searchQuery"
                           placeholder="Search movies..." 
                           class="w-full min-w-0 bg-transparent py-2 text-xs text-white placeholder-zinc-400 border-none outline-none focus:outline-none focus:ring-0">
                    
                    <kbd x-show="!searchQuery" class="shrink-0 flex items-center gap-1 pointer-events-none text-[10px] font-semibold text-zinc-400 bg-white/10 px-1.5 py-0.5 rounded-md border border-white/15 font-sans">
                        <span class="text-[9px]">Ctrl</span>
                        <span class="font-mono font-bold">K</span>
                    </kbd>
                    
                    <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="shrink-0 text-zinc-400 hover:text-white p-1" style="display: none;">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-4">
                    
                    <!-- Dashboard Overview Pill -->
                    <a href="{{ route('admin.dashboard') }}" 
                       x-show="!searchQuery || 'dashboard overview'.includes(searchQuery.toLowerCase())"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-full text-xs font-bold transition-all border shadow-sm {{ request()->routeIs('admin.dashboard') ? 'bg-white text-black border-white shadow-white/10 font-extrabold' : 'bg-zinc-950/60 border-white/10 text-zinc-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center {{ request()->routeIs('admin.dashboard') ? 'bg-black text-white' : 'bg-white/10 text-zinc-300' }}">
                            <i data-lucide="home" class="w-3.5 h-3.5"></i>
                        </div>
                        <span>Dashboard Overview</span>
                    </a>

                    <!-- Group 1: Pelayanan / Manajemen Konten -->
                    <div x-show="!searchQuery || 'film genre aktor cast'.includes(searchQuery.toLowerCase())" class="space-y-1.5">
                        <button @click="openGroups.content = !openGroups.content" 
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-xs font-bold text-white transition-colors cursor-pointer border border-white/5">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="film" class="w-4 h-4 text-zinc-300"></i>
                                <span>Manajemen Konten</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full bg-white/15 text-white text-[10px] font-extrabold border border-white/10">3</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200" :class="openGroups.content ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        <!-- Tree Links -->
                        <div x-show="openGroups.content" x-transition class="pl-5 border-l-2 border-zinc-800 ml-4 space-y-1 pt-1">
                            <a href="{{ route('admin.films.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.films.index') || request()->routeIs('admin.films.edit') || request()->routeIs('admin.films.create') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="film" class="w-3.5 h-3.5"></i>
                                <span>Semua Film</span>
                            </a>

                            <a href="{{ route('admin.films.content_rating') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.films.content_rating') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                <span>Rating Massal</span>
                            </a>

                            <a href="{{ route('admin.genres.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.genres.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="tags" class="w-3.5 h-3.5"></i>
                                <span>Genre Film</span>
                            </a>

                            <a href="{{ route('admin.actors.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.actors.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                <span>Aktor & Cast</span>
                            </a>
                        </div>
                    </div>

                    <!-- Group 2: Moderasi & User -->
                    <div x-show="!searchQuery || 'ulasan user pengguna moderasi watch party nobar'.includes(searchQuery.toLowerCase())" class="space-y-1.5">
                        <button @click="openGroups.moderation = !openGroups.moderation" 
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-xs font-bold text-white transition-colors cursor-pointer border border-white/5">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="shield" class="w-4 h-4 text-zinc-300"></i>
                                <span>Moderasi & User</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full bg-white/15 text-white text-[10px] font-extrabold border border-white/10">3</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200" :class="openGroups.moderation ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        <!-- Tree Links -->
                        <div x-show="openGroups.moderation" x-transition class="pl-5 border-l-2 border-zinc-800 ml-4 space-y-1 pt-1">
                            <a href="{{ route('admin.watch_parties.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.watch_parties.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="tv" class="w-3.5 h-3.5"></i>
                                <span>Watch Parties</span>
                            </a>

                            <a href="{{ route('admin.reviews.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.reviews.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                <span>Moderasi Ulasan</span>
                            </a>

                            <a href="{{ route('admin.users.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.users.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                <span>Kelola Pengguna</span>
                            </a>
                        </div>
                    </div>

                    <!-- Group 3: Sistem & Log -->
                    <div x-show="!searchQuery || 'settings pengaturan log activity audit changelog rilis release updates'.includes(searchQuery.toLowerCase())" class="space-y-1.5">
                        <button @click="openGroups.system = !openGroups.system" 
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-xs font-bold text-white transition-colors cursor-pointer border border-white/5">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="settings" class="w-4 h-4 text-zinc-300"></i>
                                <span>Sistem & Log</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full bg-white/15 text-white text-[10px] font-extrabold border border-white/10">3</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200" :class="openGroups.system ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        <!-- Tree Links -->
                        <div x-show="openGroups.system" x-transition class="pl-5 border-l-2 border-zinc-800 ml-4 space-y-1 pt-1">
                            <a href="{{ route('admin.changelogs.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.changelogs.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="file-clock" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>Changelog & Updates</span>
                            </a>

                            <a href="{{ route('admin.activity_logs.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.activity_logs.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                <span>Activity Audit Log</span>
                            </a>

                            <a href="{{ route('admin.settings.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors {{ request()->routeIs('admin.settings.*') ? 'text-white font-bold bg-white/15' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                                <span class="text-zinc-600 font-mono text-[10px]">└</span>
                                <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                                <span>Pengaturan Umum</span>
                            </a>
                        </div>
                    </div>

                </nav>
            </div>

            <!-- Bottom User Profile Footer Tile -->
            <div class="p-4 border-t border-white/10 bg-zinc-950/90">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-white/10 border border-white/20 text-white font-extrabold text-xs flex items-center justify-center shrink-0">
                            {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-white text-xs truncate">{{ Auth::user()->name ?? 'Administrator' }}</p>
                            <p class="text-[10px] text-zinc-400 truncate">{{ Auth::user()->email ?? 'admin@faiilmov.com' }}</p>
                        </div>
                    </div>
                    
                    <a href="{{ route('home') }}" class="p-2 rounded-xl bg-white/5 text-zinc-300 hover:text-white hover:bg-white/15 transition-colors" title="Kembali ke Situs Utama">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 lg:pl-72 flex flex-col min-h-screen">
            
            <!-- Top Navbar -->
            <header class="h-16 bg-zinc-900/50 border-b border-white/10 backdrop-blur-md sticky top-0 z-40 px-4 sm:px-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-white/5">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <h1 class="font-['Outfit'] font-bold text-lg text-white">@yield('page_title', 'Admin Dashboard')</h1>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
                        <div class="w-7 h-7 rounded-lg bg-white text-black font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <span class="text-xs font-medium text-zinc-200 hidden sm:inline">{{ Auth::user()->name ?? 'Admin' }}</span>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
                
                <!-- Flash Alerts -->
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-400 shrink-0"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
