<!-- Left Sidebar Navigation Component -->
<aside class="fixed top-16 left-0 bottom-0 z-30 w-60 bg-dark-950/90 backdrop-blur-xl border-r border-white/10 p-4 flex flex-col justify-between overflow-y-auto space-y-6 transition-transform duration-300 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    
    <div class="space-y-6">
        <!-- Primary Navigation Menu -->
        @php
            $isHome = request()->routeIs('home');
            $isTvShow = request()->routeIs('browse') && request('type') === 'series';
            $isAnimation = request()->routeIs('browse') && request('genre') === 'animation';
            $isMostWatched = request()->routeIs('browse') && request('sort') === 'rating_desc';
            $isMovie = request()->routeIs('browse') && !$isTvShow && !$isAnimation && !$isMostWatched;
        @endphp
        <div class="space-y-1.5">
            <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-xs transition-all {{ $isHome ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="home" class="w-4 h-4 {{ $isHome ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Home</span>
            </a>

            <a href="{{ route('browse', ['type' => 'series']) }}" class="flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-xs transition-all {{ $isTvShow ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="tv" class="w-4 h-4 {{ $isTvShow ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Series</span>
            </a>

            <a href="{{ route('browse', ['type' => 'movie']) }}" class="flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-xs transition-all {{ $isMovie ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="clapperboard" class="w-4 h-4 {{ $isMovie ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Movie</span>
            </a>

            <a href="{{ route('browse', ['genre' => 'animation']) }}" class="flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-xs transition-all {{ $isAnimation ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="sparkles" class="w-4 h-4 {{ $isAnimation ? 'text-white' : 'text-zinc-400' }}"></i>
                <span>Animation</span>
            </a>

            <a href="{{ route('browse', ['sort' => 'rating_desc']) }}" class="flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-xs transition-all {{ $isMostWatched ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-md' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
                <i data-lucide="flame" class="w-4 h-4 {{ $isMostWatched ? 'text-amber-400' : 'text-zinc-400' }}"></i>
                <span>Most Watched</span>
            </a>
        </div>
    </div>

    <!-- Bottom Sidebar Get App Widget Card -->
    <div class="glass-panel p-4 rounded-3xl border border-white/10 space-y-3">
        <span class="text-xs font-bold text-white block">Get faiilmov</span>
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('browse') }}" class="px-3 py-2 rounded-2xl bg-white text-zinc-950 text-[10px] font-bold flex items-center justify-center gap-1 hover:bg-zinc-200 transition-colors shadow-sm">
                <i data-lucide="smartphone" class="w-3 h-3"></i>
                <span>Mobile</span>
            </a>
            <a href="{{ route('browse') }}" class="px-3 py-2 rounded-2xl bg-dark-900 text-zinc-300 text-[10px] font-semibold flex items-center justify-center gap-1 border border-white/10 hover:text-white">
                <i data-lucide="laptop" class="w-3 h-3"></i>
                <span>macOS</span>
            </a>
        </div>
    </div>

</aside>

<!-- Overlay Backdrop for Mobile Sidebar -->
<div x-show="sidebarOpen" 
     @click="sidebarOpen = false" 
     class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm lg:hidden" 
     style="display: none;"></div>
