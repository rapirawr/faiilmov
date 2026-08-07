<!-- Top Navigation Header Component -->
<header class="fixed top-0 left-0 right-0 z-40 h-20 bg-dark-950/80 backdrop-blur-xl border-b border-white/10 flex items-center justify-between px-4 sm:px-8 gap-4 pointer-events-none [&>*]:pointer-events-auto shadow-md">
    
    <!-- Left: Circular Toggle & Brand Logo -->
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="w-10 h-10 shrink-0 rounded-full bg-zinc-800/80 hover:bg-zinc-700 text-zinc-300 hover:text-white flex items-center justify-center border border-white/10 transition-colors shadow-sm lg:hidden">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <a href="{{ route('home') }}" class="hidden sm:flex items-center gap-2.5 group">
            <span class="font-serif font-extrabold text-xl tracking-tight text-white group-hover:text-amber-400 transition-colors">
                faiil<span class="text-zinc-400 font-sans font-bold">mov</span>
            </span>
        </a>
    </div>

    <!-- Center: Compact Capsule Autocomplete Search Bar with Expand/Collapse Animation -->
    <div class="relative flex-1 max-w-xl mx-2 sm:mx-4 flex justify-center"
         x-data="{ 
             ...searchAutocomplete(), 
             isExpanded: false, 
             expandSearch() { 
                 this.isExpanded = true; 
                 this.openPanel(); 
             }, 
             collapseSearch() { 
                 if (this.query.trim().length === 0) { 
                     this.isExpanded = false; 
                 } 
                 this.closePanel(); 
             } 
         }"  
         @click.outside="collapseSearch()"
         @keydown.escape.window="collapseSearch(); $refs.searchInput.blur()"
         @keydown.window.ctrl.k.prevent="$refs.searchInput.focus(); $refs.searchInput.select(); expandSearch()"
         @keydown.window.cmd.k.prevent="$refs.searchInput.focus(); $refs.searchInput.select(); expandSearch()">
        
        <form :action="'{{ route('browse') }}'" 
              method="GET" 
              class="relative flex items-center bg-dark-900/90 backdrop-blur-md rounded-full border border-white/10 focus-within:border-white/30 focus-within:bg-dark-950 transition-all duration-300 ease-out shadow-inner overflow-hidden"
              :class="isExpanded ? 'w-full max-w-[500px]' : 'w-[220px] sm:w-[260px]'"
              @submit="selectFocused()">
            <!-- Search Icon (Left) -->
            <i data-lucide="search" class="w-4 h-4 text-zinc-400 shrink-0 ml-3.5 pointer-events-none transition-colors duration-200"></i>
            
            <!-- Input Field (Center) -->
            <input type="text"
                   name="q"
                   x-ref="searchInput"
                   x-model="query"
                   @input="onInput()"
                   @keydown.arrow-down.prevent="navigateDown()"
                   @keydown.arrow-up.prevent="navigateUp()"
                   @keydown.enter.prevent="selectFocused()"
                   @focus="expandSearch()"
                   @blur="collapseSearch()"
                   placeholder="Cari film..."
                   autocomplete="off"
                   class="min-w-0 flex-1 bg-transparent text-xs text-zinc-100 placeholder-zinc-500 px-2 py-2 outline-none">
            
            <!-- Right Section: Clear Button or Ctrl K Badge -->
            <div class="flex items-center gap-1.5 mr-2 shrink-0">
                <!-- Clear Button (muncul saat ada text) -->
                <button type="button" 
                        x-show="query.length > 0" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                        @click="clearSearch()"
                        class="text-zinc-500 hover:text-white transition-colors p-1 cursor-pointer flex items-center justify-center">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>

                <!-- Shortcut Badge Indicator (Cmd/Ctrl K) - fade out saat expanded -->
                <kbd x-show="query.length === 0 && !isExpanded" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="hidden sm:flex items-center gap-1 pointer-events-none text-[10px] font-semibold text-zinc-400 bg-white/10 px-2 py-0.5 rounded-md border border-white/15 font-sans">
                    <i data-lucide="command" class="w-3 h-3"></i>
                    <span>K</span>
                </kbd>
            </div>
        </form>

        <!-- Active Search Modal Panel Dropdown -->
        <div x-show="showPanel"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1 scale-98"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 translate-y-1 scale-98"
             class="fixed inset-x-3 top-20 mt-1 sm:absolute sm:inset-auto sm:top-full sm:left-0 sm:right-0 sm:mt-2 bg-dark-950/95 backdrop-blur-2xl rounded-2xl border border-white/10 shadow-2xl overflow-hidden z-[200]"
             style="display: none;">
            
            <div class="p-4 space-y-4 max-h-[70vh] sm:max-h-[26rem] overflow-y-auto no-scrollbar">
                
                <!-- STATE 1: Empty Query - Show Search History & Popular Films -->
                <template x-if="query.trim().length === 0">
                    <div class="space-y-4">
                        <!-- Search History Section -->
                        <div x-show="searchHistory.length > 0" class="space-y-2">
                            <div class="flex items-center justify-between px-1">
                                <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="history" class="w-3.5 h-3.5 text-zinc-400"></i>
                                    <span>Riwayat Pencarian</span>
                                </span>
                                <button type="button" @click="clearHistory()" class="text-[10px] font-semibold text-zinc-500 hover:text-zinc-300 transition-colors cursor-pointer">
                                    Hapus Semua
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <template x-for="(term, idx) in searchHistory" :key="idx">
                                    <div class="group/h flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-xs text-zinc-200 cursor-pointer">
                                        <span @click="useHistoryTerm(term)" x-text="term" class="font-medium"></span>
                                        <button type="button" @click.stop="removeHistoryItem(idx)" class="text-zinc-500 hover:text-red-400 transition-colors">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Popular Recommendations Section -->
                        <div x-show="popularFilms.length > 0" class="space-y-2">
                            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5 px-1">
                                <i data-lucide="flame" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>Film Popular Saat Ini</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <template x-for="pFilm in popularFilms" :key="pFilm.id">
                                    <a :href="pFilm.url" @click="saveHistoryTerm(pFilm.title); closePanel()" class="flex items-center gap-3 p-2 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors group">
                                        <img :src="pFilm.poster" :alt="pFilm.title" class="w-9 h-12 object-cover rounded-xl shrink-0 bg-dark-800">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold text-white truncate group-hover:text-amber-300 transition-colors" x-text="pFilm.title"></p>
                                            <div class="flex items-center gap-2 text-[10px] text-zinc-400 mt-0.5">
                                                <span x-text="pFilm.year"></span>
                                                <template x-if="pFilm.rating > 0">
                                                    <span class="text-amber-400 font-bold flex items-center gap-0.5">
                                                        <i data-lucide="star" class="w-2.5 h-2.5 fill-amber-400"></i>
                                                        <span x-text="pFilm.rating.toFixed(1)"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- STATE 2: Has Query - Show Live Relevant Results -->
                <template x-if="query.trim().length > 0">
                    <div class="space-y-2">
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5 px-1">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-sky-400"></i>
                            <span>Hasil Pencarian Relevan</span>
                        </span>
                        
                        <div x-show="suggestions.length === 0" class="py-6 text-center text-xs text-zinc-500">
                            Mencari film...
                        </div>

                        <div class="space-y-1">
                            <template x-for="(item, index) in suggestions" :key="item.id">
                                <a :href="item.url"
                                   @click="saveHistoryTerm(query); closePanel()"
                                   @mouseenter="focusedIndex = index"
                                   :class="focusedIndex === index ? 'bg-white/10' : 'hover:bg-white/5'"
                                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl transition-colors cursor-pointer group">
                                    
                                    <!-- Poster Thumbnail -->
                                    <img :src="item.poster" :alt="item.title"
                                         class="w-9 h-13 object-cover rounded-xl shrink-0 bg-dark-800 shadow">

                                    <!-- Film Info -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-white truncate" x-html="highlightMatch(item.title)"></p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] text-zinc-400" x-text="item.year"></span>
                                            <span class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-md"
                                                  :class="item.type === 'series' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/20' : 'bg-zinc-700/60 text-zinc-300 border border-white/10'"
                                                  x-text="item.type === 'series' ? 'Series' : 'Movie'">
                                            </span>
                                            <template x-if="item.rating > 0">
                                                <span class="text-[10px] text-amber-400 font-bold flex items-center gap-0.5">
                                                    <i data-lucide="star" class="w-2.5 h-2.5 fill-amber-400 inline"></i>
                                                    <span x-text="item.rating.toFixed(1)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-zinc-600 group-hover:text-zinc-300 transition-colors shrink-0"></i>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

            </div>

            <!-- Footer: See all results link -->
            <div x-show="query.trim().length > 0" class="border-t border-white/10 px-4 py-2.5 bg-dark-900/50">
                <a :href="'{{ route('browse') }}?q=' + encodeURIComponent(query)"
                   @click="saveHistoryTerm(query); closePanel()"
                   class="text-[11px] text-zinc-400 hover:text-white transition-colors flex items-center gap-1.5">
                    <i data-lucide="search" class="w-3 h-3"></i>
                    <span>Lihat semua hasil untuk "<span class="text-white font-semibold" x-text="query"></span>"</span>
                </a>
            </div>

        </div>
    </div>

    <!-- Right Action Buttons: Capsule Pill -->
    <div class="flex items-center gap-2.5">


        @auth
            <a href="{{ route('profile') }}" class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-zinc-800/80 hover:bg-zinc-700 border border-white/10 transition-all shadow-sm">
                <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center font-bold text-xs text-zinc-950 shadow">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <span class="text-xs font-semibold text-white hidden lg:inline">{{ Auth::user()->name }}</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-zinc-800/80 hover:bg-zinc-700 text-zinc-200 hover:text-white transition-all text-xs font-semibold border border-white/10 shadow-sm">
                <i data-lucide="user" class="w-3.5 h-3.5 text-zinc-400"></i>
                <span>Log in</span>
            </a>
        @endauth
    </div>

</header>

