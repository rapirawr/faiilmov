<!-- Left Sidebar Navigation Component -->
<aside class="-translate-x-full lg:translate-x-0 fixed top-0 lg:top-20 left-0 bottom-0 z-50 lg:z-30 w-64 bg-dark-950/95 backdrop-blur-xl border-r border-white/10 p-5 flex flex-col justify-between overflow-y-auto space-y-6 transition-transform duration-300"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    
    <div class="space-y-6">
        <!-- Mobile Sidebar Brand Header & Close Button -->
        <div class="flex items-center justify-between pb-3 border-b border-white/10 lg:hidden">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="h-8 w-auto object-contain transition-transform group-hover:scale-105">
                <span class="font-serif font-extrabold text-xl tracking-tight text-white group-hover:text-amber-400 transition-colors">
                    faiil<span class="text-zinc-400 font-sans font-bold">mov</span>
                </span>
            </a>
            <button type="button" @click="sidebarOpen = false" class="p-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Primary Navigation Menu (Dynamic Drag & Drop Reordered) -->
        @php
            $sidebarMenus = \App\Services\NavigationService::getSidebarMenu();
            $currentUrl = request()->fullUrl();
            $currentPath = request()->path();
        @endphp
        <div class="space-y-2">
            @foreach($sidebarMenus as $menu)
                @php
                    if (!($menu['is_active'] ?? true)) continue;

                    // Visibility check
                    $visibility = $menu['visibility'] ?? 'all';
                    if ($visibility === 'auth_only' && !Auth::check()) continue;
                    if ($visibility === 'guest_only' && Auth::check()) continue;

                    $menuUrl = $menu['url'] ?? '/';
                    $menuRoute = $menu['route'] ?? '';
                    $menuIcon = $menu['icon'] ?? 'compass';
                    $menuBadge = $menu['badge'] ?? '';
                    $menuTarget = $menu['target'] ?? '_self';

                    // Active state detection
                    $isActive = false;
                    if ($menuUrl === '/' || $menuRoute === 'home') {
                        $isActive = request()->routeIs('home');
                    } elseif ($menuRoute && request()->routeIs($menuRoute)) {
                        $isActive = true;
                    } elseif (strpos($menuUrl, 'type=series') !== false && request()->routeIs('browse') && request('type') === 'series') {
                        $isActive = true;
                    } elseif (strpos($menuUrl, 'type=movie') !== false && request()->routeIs('browse') && request('type') === 'movie') {
                        $isActive = true;
                    } elseif (strpos($menuUrl, 'genre=animation') !== false && request()->routeIs('browse') && request('genre') === 'animation') {
                        $isActive = true;
                    } elseif (strpos($menuUrl, 'sort=rating_desc') !== false && request()->routeIs('browse') && request('sort') === 'rating_desc') {
                        $isActive = true;
                    } elseif (strpos($menuUrl, '/dracin') !== false && (request()->routeIs('dracin.*') || (request()->routeIs('browse') && request('type') === 'dracin'))) {
                        $isActive = true;
                    } elseif ($currentUrl === url($menuUrl) || ($menuUrl !== '/' && request()->is(ltrim($menuUrl, '/')))) {
                        $isActive = true;
                    }
                @endphp

                <a href="{{ url($menuUrl) }}" 
                   target="{{ $menuTarget }}"
                   class="flex items-center justify-between px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ $isActive ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <i data-lucide="{{ $menuIcon }}" class="w-5 h-5 shrink-0 {{ $isActive ? 'text-white' : ($menuIcon === 'flame' || $menuIcon === 'history' ? 'text-amber-400' : 'text-zinc-400') }}"></i>
                        <span class="truncate">{{ $menu['label'] }}</span>
                    </div>

                    @if(!empty($menuBadge))
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono shrink-0 ml-2">
                            {{ $menuBadge }}
                        </span>
                    @endif
                </a>
            @endforeach

            <!-- Gabung Room Nonton Bareng Modal Trigger -->
            <div x-data="{ joinModalOpen: false }" class="pt-2">
                <button type="button" @click="joinModalOpen = true" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 font-bold text-sm border border-amber-500/30 transition-all shadow-md cursor-pointer group">
                    <div class="flex items-center gap-3.5">
                        <i data-lucide="users" class="w-5 h-5 text-amber-400"></i>
                        <span>Gabung Room</span>
                    </div>
                </button>

                <!-- Modal Popup Gabung Room Nonton Bareng (Teleported to body) -->
                <template x-teleport="body">
                    <div x-show="joinModalOpen" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
                         style="display: none;">
                        
                        <div @click.outside="joinModalOpen = false" 
                             class="w-full max-w-md glass-panel p-6 sm:p-8 rounded-3xl border border-white/20 shadow-2xl space-y-6 text-left relative">
                            
                            <button type="button" @click="joinModalOpen = false" class="absolute top-5 right-5 text-zinc-400 hover:text-white p-1 cursor-pointer transition-colors">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>

                            <div class="space-y-1.5">
                                <h3 class="font-serif font-bold text-2xl text-white flex items-center gap-2.5">
                                    <i data-lucide="users" class="w-6 h-6 text-amber-400"></i>
                                    <span>Gabung Room Nonton Bareng</span>
                                </h3>
                                <p class="text-xs text-zinc-400">Masukkan kode room 6 karakter yang diberikan oleh Host.</p>
                            </div>

                            <form @submit.prevent="let code = $refs.roomCodeInputSidebar.value.trim().toUpperCase(); if(code) { window.location.href = '/watch-party/' + code; }" class="space-y-5">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-zinc-300">Kode Room</label>
                                    <input type="text" 
                                           x-ref="roomCodeInputSidebar"
                                           placeholder="Contoh: X7K2P9" 
                                           required 
                                           maxlength="10"
                                           class="w-full uppercase font-mono tracking-widest text-center text-xl font-bold bg-zinc-900/90 text-amber-400 placeholder-zinc-600 px-4 py-3.5 rounded-2xl border border-white/15 focus:outline-none focus:border-amber-400 shadow-inner">
                                </div>

                                <button type="submit" class="w-full py-3.5 rounded-2xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer shadow-xl">
                                    <span>Masuk ke Room</span>
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </button>
                            </form>

                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Bottom Sidebar Get App Widget Card (Dynamic CMS) -->
    @php
        $sidebarWidget = \App\Services\NavigationService::getSidebarWidget();
    @endphp
    @if($sidebarWidget['is_active'] ?? true)
        <div class="glass-panel p-4 rounded-3xl border border-white/10 space-y-3">
            <span class="text-xs font-bold text-white block">{{ $sidebarWidget['title'] ?? 'Get faiilmov' }}</span>
            <div class="grid {{ (!empty($sidebarWidget['button2_active'])) ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
                <a href="{{ url($sidebarWidget['button_url'] ?? '/download-app') }}" class="px-3 py-2 rounded-2xl bg-white text-zinc-950 text-[10px] font-bold flex items-center justify-center gap-1.5 hover:bg-zinc-200 transition-colors shadow-sm">
                    <i data-lucide="{{ $sidebarWidget['button_icon'] ?? 'smartphone' }}" class="w-3.5 h-3.5"></i>
                    <span>{{ $sidebarWidget['button_text'] ?? 'Mobile' }}</span>
                </a>

                @if(!empty($sidebarWidget['button2_active']))
                    <a href="{{ url($sidebarWidget['button2_url'] ?? '#') }}" class="px-3 py-2 rounded-2xl bg-dark-900 text-zinc-300 text-[10px] font-semibold flex items-center justify-center gap-1.5 border border-white/10 hover:text-white hover:bg-white/5 transition-colors">
                        <i data-lucide="{{ $sidebarWidget['button2_icon'] ?? 'laptop' }}" class="w-3.5 h-3.5"></i>
                        <span>{{ $sidebarWidget['button2_text'] ?? 'macOS' }}</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

</aside>

<!-- Overlay Backdrop for Mobile Sidebar -->
<div x-show="sidebarOpen" 
     x-cloak
     @click="sidebarOpen = false" 
     class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden" 
     style="display: none;"></div>
