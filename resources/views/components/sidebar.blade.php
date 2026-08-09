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

        <!-- Primary Navigation Menu -->
        @php
            $isHome = request()->routeIs('home');
            $isTvShow = request()->routeIs('browse') && request('type') === 'series';
            $isAnimation = request()->routeIs('browse') && request('genre') === 'animation';
            $isMostWatched = request()->routeIs('browse') && request('sort') === 'rating_desc';
            $isMovie = request()->routeIs('browse') && !$isTvShow && !$isAnimation && !$isMostWatched;
        @endphp
        <div class="space-y-2">
            <a href="{{ route('home') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ $isHome ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="home" class="w-5 h-5 {{ $isHome ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Home</span>
            </a>

            <a href="{{ route('browse', ['type' => 'series']) }}" class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ $isTvShow ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="tv" class="w-5 h-5 {{ $isTvShow ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Series</span>
            </a>

            <a href="{{ route('browse', ['type' => 'movie']) }}" class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ $isMovie ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="clapperboard" class="w-5 h-5 {{ $isMovie ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Movie</span>
            </a>

            <a href="{{ route('browse', ['genre' => 'animation']) }}" class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ $isAnimation ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="sparkles" class="w-5 h-5 {{ $isAnimation ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Animation</span>
            </a>

            <a href="{{ route('browse', ['sort' => 'rating_desc']) }}" class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ $isMostWatched ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="flame" class="w-5 h-5 {{ $isMostWatched ? 'text-amber-400' : 'text-zinc-400' }}"></i>
                <span>Most Watched</span>
            </a>

            <a href="{{ route('changelog') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('changelog') ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="sparkles" class="w-5 h-5 text-amber-400"></i>
                <span>Changelog</span>
            </a>

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

    <!-- Bottom Sidebar Get App Widget Card -->
    <div class="glass-panel p-4 rounded-3xl border border-white/10 space-y-3">
        <span class="text-xs font-bold text-white block">Get faiilmov</span>
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('download.app') }}" class="px-3 py-2 rounded-2xl bg-white text-zinc-950 text-[10px] font-bold flex items-center justify-center gap-1 hover:bg-zinc-200 transition-colors shadow-sm">
                <i data-lucide="smartphone" class="w-3 h-3"></i>
                <span>Mobile</span>
            </a>
            {{-- <a href="{{ route('browse') }}" class="px-3 py-2 rounded-2xl bg-dark-900 text-zinc-300 text-[10px] font-semibold flex items-center justify-center gap-1 border border-white/10 hover:text-white">
                <i data-lucide="laptop" class="w-3 h-3"></i>
                <span>macOS</span>
            </a> --}}
        </div>
    </div>

</aside>

<!-- Overlay Backdrop for Mobile Sidebar -->
<div x-show="sidebarOpen" 
     x-cloak
     @click="sidebarOpen = false" 
     class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden" 
     style="display: none;"></div>
