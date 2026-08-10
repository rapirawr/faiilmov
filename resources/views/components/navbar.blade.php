<!-- Top Navigation Header Component -->
<header class="fixed top-0 left-0 right-0 z-40 h-20 bg-dark-950/80 backdrop-blur-xl border-b border-white/10 flex items-center justify-between px-2.5 sm:px-8 gap-2 sm:gap-4 pointer-events-none [&>*]:pointer-events-auto shadow-md">
    
    <!-- Left: Circular Toggle & Brand Logo -->
    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <button @click="sidebarOpen = !sidebarOpen" class="w-10 h-10 shrink-0 rounded-full bg-zinc-800/80 hover:bg-zinc-700 text-zinc-300 hover:text-white flex items-center justify-center border border-white/10 transition-colors shadow-sm lg:hidden">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <a href="{{ route('home') }}" class="hidden sm:flex items-center gap-2.5 group">
            <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="h-8 w-auto object-contain transition-transform group-hover:scale-105">
            <span class="font-serif font-extrabold text-xl tracking-tight text-white group-hover:text-amber-400 transition-colors">
                faiil<span class="text-zinc-400 font-sans font-bold">mov</span>
            </span>
        </a>
    </div>

    <!-- Center: Compact Capsule Autocomplete Search Bar with Expand/Collapse Animation -->
    <div class="relative flex-1 max-w-xl mx-1 sm:mx-4 flex justify-start sm:justify-center min-w-0"
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
              :class="isExpanded ? 'w-full max-w-[500px]' : 'w-[145px] xs:w-[185px] sm:w-[260px]'"
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
             x-cloak
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
                                    <a :href="pFilm.url" @click="saveHistoryTerm(pFilm.title); window.location.href = pFilm.url" class="flex items-center gap-3 p-2 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors group">
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
                                   @click="saveHistoryTerm(query); window.location.href = item.url"
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
                                                  :class="item.type === 'series' ? 'bg-teal-500/20 text-teal-300 border border-teal-500/20' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/20'"
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
                   @click="saveHistoryTerm(query); window.location.href = '{{ route('browse') }}?q=' + encodeURIComponent(query)"
                   class="text-[11px] text-zinc-400 hover:text-white transition-colors flex items-center gap-1.5">
                    <i data-lucide="search" class="w-3 h-3"></i>
                    <span>Lihat semua hasil untuk "<span class="text-white font-semibold" x-text="query"></span>"</span>
                </a>
            </div>

        </div>
    </div>

    <!-- Right Action Buttons: Capsule Pill & Popovers -->
    <div class="flex items-center gap-2.5">


        <a href="{{ route('download.app') }}" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 transition-all text-xs font-semibold shadow-sm" title="App Mobile Flutter">
            <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
            <span>App Mobile</span>
        </a>

        @auth
            @php
                $activeProfile = Auth::user()->activeProfile();
                $userProfiles = Auth::user()->profiles;
            @endphp

            <!-- Notification Bell Dropdown -->
            <div class="relative"
                 x-data="{ 
                     open: false, 
                     unreadCount: 0, 
                     items: [], 
                     loading: false,
                     init() {
                         this.fetchRecent();
                         setInterval(() => this.fetchRecent(), 30000);
                     },
                     async fetchRecent() {
                         try {
                             let res = await fetch('{{ route('notifications.recent') }}');
                             if (res.ok) {
                                 let data = await res.json();
                                 this.unreadCount = data.unread_count;
                                 this.items = data.notifications;
                             }
                         } catch (e) {}
                     },
                     async markAllRead() {
                         try {
                             let res = await fetch('{{ route('notifications.read-all') }}', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                     'Accept': 'application/json'
                                 }
                             });
                             if (res.ok) {
                                 this.unreadCount = 0;
                                 this.items.forEach(i => i.is_read = true);
                             }
                         } catch(e) {}
                     },
                     async handleClick(notif) {
                         if (!notif.is_read) {
                             fetch('/notifications/' + notif.id + '/read', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                     'Accept': 'application/json'
                                 }
                             });
                         }
                         if (notif.url) {
                             window.location.href = notif.url;
                         }
                     }
                 }"
                 @click.outside="open = false">
                
                <button @click="open = !open; if(open) fetchRecent()" 
                        class="relative w-9 h-9 rounded-full bg-zinc-800/80 hover:bg-zinc-700 text-zinc-300 hover:text-white flex items-center justify-center border border-white/10 transition-all cursor-pointer">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    
                    <!-- Unread Badge -->
                    <span x-show="unreadCount > 0" 
                          x-cloak
                          x-text="unreadCount > 9 ? '9+' : unreadCount"
                          class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full bg-red-500 text-white font-extrabold text-[9px] min-w-[16px] text-center shadow-lg animate-pulse">
                    </span>
                </button>

                <!-- Notifications Dropdown Panel -->
                <div x-show="open" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                     class="absolute right-0 top-full mt-3 w-80 sm:w-96 bg-zinc-900/95 border border-white/15 rounded-2xl shadow-2xl backdrop-blur-2xl overflow-hidden z-50">
                    
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="bell" class="w-4 h-4 text-amber-400"></i>
                            <span class="font-bold text-xs text-white">Notifikasi</span>
                            <span x-show="unreadCount > 0" x-text="unreadCount + ' Baru'" class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-semibold border border-amber-500/30"></span>
                        </div>
                        <button @click="markAllRead()" class="text-[11px] font-semibold text-zinc-400 hover:text-white transition-colors cursor-pointer">
                            Tandai semua dibaca
                        </button>
                    </div>

                    <!-- List -->
                    <div class="max-h-80 overflow-y-auto divide-y divide-white/5 no-scrollbar">
                        <template x-if="items.length === 0">
                            <div class="p-6 text-center text-xs text-zinc-500 space-y-1">
                                <i data-lucide="bell-off" class="w-8 h-8 mx-auto text-zinc-600 mb-2"></i>
                                <p class="font-medium text-zinc-400">Tidak ada notifikasi</p>
                                <p class="text-[10px]">Semua pembaruan film & ulasan akan muncul di sini</p>
                            </div>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <div @click="handleClick(item)" 
                                 :class="!item.is_read ? 'bg-amber-500/5 hover:bg-amber-500/10' : 'hover:bg-white/5'"
                                 class="p-3.5 flex items-start gap-3 transition-colors cursor-pointer group">
                                <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center text-xs mt-0.5"
                                     :class="{
                                         'bg-amber-500/20 text-amber-400 border border-amber-500/30': item.type === 'new_film',
                                         'bg-sky-500/20 text-sky-400 border border-sky-500/30': item.type === 'review_reply',
                                         'bg-purple-500/20 text-purple-400 border border-purple-500/30': item.type !== 'new_film' && item.type !== 'review_reply'
                                     }">
                                    <template x-if="item.type === 'new_film'">
                                        <i data-lucide="film" class="w-4 h-4"></i>
                                    </template>
                                    <template x-if="item.type === 'review_reply'">
                                        <i data-lucide="message-square" class="w-4 h-4"></i>
                                    </template>
                                    <template x-if="item.type !== 'new_film' && item.type !== 'review_reply'">
                                        <i data-lucide="bell" class="w-4 h-4"></i>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-zinc-200 group-hover:text-white line-clamp-2" :class="{ 'font-semibold': !item.is_read }" x-text="item.message"></p>
                                    <span class="text-[10px] text-zinc-500 mt-1 block" x-text="item.time_ago"></span>
                                </div>
                                <div x-show="!item.is_read" class="w-2 h-2 rounded-full bg-amber-400 shrink-0 mt-1.5 shadow-glow"></div>
                            </div>
                        </template>
                    </div>

                    <!-- Footer Link -->
                    <div class="p-2.5 border-t border-white/10 text-center bg-zinc-950/50">
                        <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition-colors inline-flex items-center gap-1">
                            <span>Lihat Semua Notifikasi</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

<script>
function navProfileState() {
    return {
        open: false,
        showPinModal: false,
        targetProfileId: null,
        targetProfileName: '',
        enteredPin: '',
        pinError: '',
        isSubmitting: false,

        selectSubProfile(id, name, hasPin) {
            this.open = false;
            if (hasPin) {
                this.targetProfileId = id;
                this.targetProfileName = name;
                this.enteredPin = '';
                this.pinError = '';
                this.showPinModal = true;
            } else {
                this.performSwitch(id, null);
            }
        },

        async performSwitch(id, pin) {
            this.isSubmitting = true;
            this.pinError = '';

            try {
                const url = id ? '/profiles/' + id + '/switch' : '/profiles/switch-main';
                const bodyData = new FormData();
                bodyData.append('_token', '{{ csrf_token() }}');
                if (pin) {
                    bodyData.append('pin', pin);
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: bodyData
                });

                const data = await res.json();
                if (res.ok && data.status === 'ok') {
                    window.location.reload();
                } else {
                    this.pinError = data.message || 'PIN profil salah.';
                    this.isSubmitting = false;
                }
            } catch (err) {
                this.pinError = 'Terjadi kesalahan sistem.';
                this.isSubmitting = false;
            }
        }
    };
}
</script>

            <!-- Profile & Multi-Account Switcher Dropdown -->
            <div class="relative" x-data="navProfileState()" @click.outside="open = false">
                <button @click="open = !open" 
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-800/80 hover:bg-zinc-700 border border-white/10 transition-all shadow-sm cursor-pointer group">
                    
                    <!-- Avatar Thumbnail -->
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center font-bold text-xs text-black shadow overflow-hidden shrink-0">
                        @if($activeProfile)
                            @if($activeProfile->avatar)
                                <img src="{{ $activeProfile->avatar }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($activeProfile->name, 0, 2)) }}
                            @endif
                        @elseif(Auth::user()->avatar)
                            <img src="{{ Auth::user()->avatar }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        @endif
                    </div>

                    <!-- Active Profile Name & Badge -->
                    <div class="flex items-center gap-1.5 text-left hidden lg:flex">
                        <span class="text-xs font-bold text-white max-w-[100px] truncate">
                            {{ $activeProfile ? $activeProfile->name : Auth::user()->name }}
                        </span>
                        @if($activeProfile && $activeProfile->is_child)
                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-purple-500/20 text-purple-300 border border-purple-500/30">Anak</span>
                        @elseif($activeProfile)
                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-zinc-700 text-zinc-300">Profil</span>
                        @else
                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">Utama</span>
                        @endif
                    </div>

                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-zinc-400 group-hover:text-white transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>

                <!-- Profile Dropdown Menu -->
                <div x-show="open" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                     class="absolute right-0 top-full mt-3 w-64 bg-zinc-900/95 border border-white/15 rounded-2xl shadow-2xl backdrop-blur-2xl overflow-hidden z-50">
                    
                    <!-- Current Selected Profile Header -->
                    <div class="px-4 py-3 border-b border-white/10 bg-white/5">
                        <p class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider mb-1">Profil Aktif</p>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-amber-500 text-black flex items-center justify-center font-bold text-xs overflow-hidden shrink-0">
                                @if($activeProfile && $activeProfile->avatar)
                                    <img src="{{ $activeProfile->avatar }}" class="w-full h-full object-cover">
                                @elseif(!$activeProfile && Auth::user()->avatar)
                                    <img src="{{ Auth::user()->avatar }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($activeProfile ? $activeProfile->name : Auth::user()->name, 0, 2)) }}
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-white truncate">{{ $activeProfile ? $activeProfile->name : Auth::user()->name }}</p>
                                <p class="text-[10px] text-zinc-400 truncate">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Profiles List (Sub Accounts) -->
                    <div class="p-2 space-y-1 border-b border-white/10">
                        <p class="text-[10px] uppercase font-bold text-zinc-500 px-2 pt-1 pb-1 tracking-wider">Ganti Profil</p>
                        
                        <!-- Main Account Option -->
                        <button type="button" 
                                @click="selectSubProfile(null, '{{ addslashes(Auth::user()->name) }}', false)"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-xl transition-colors cursor-pointer {{ !$activeProfile ? 'bg-amber-500/10 border border-amber-500/20 text-amber-300' : 'hover:bg-white/5 text-zinc-300' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-6 h-6 rounded-full bg-zinc-700 text-white flex items-center justify-center text-[10px] font-bold overflow-hidden shrink-0">
                                    @if(Auth::user()->avatar)
                                        <img src="{{ Auth::user()->avatar }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                    @endif
                                </div>
                                <span class="text-xs font-semibold truncate">{{ Auth::user()->name }} (Utama)</span>
                            </div>
                            @if(!$activeProfile)
                                <i data-lucide="check" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                            @endif
                        </button>

                        <!-- Sub Profiles Options -->
                        @foreach($userProfiles as $p)
                            <button type="button" 
                                    @click="selectSubProfile({{ $p->id }}, '{{ addslashes($p->name) }}', {{ !empty($p->pin) ? 'true' : 'false' }})"
                                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-xl transition-colors cursor-pointer {{ $activeProfile && $activeProfile->id == $p->id ? 'bg-amber-500/10 border border-amber-500/20 text-amber-300' : 'hover:bg-white/5 text-zinc-300' }}">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-6 h-6 rounded-full bg-zinc-800 border border-white/20 text-white flex items-center justify-center text-[10px] font-bold overflow-hidden shrink-0">
                                        @if($p->avatar)
                                            <img src="{{ $p->avatar }}" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($p->name, 0, 2)) }}
                                        @endif
                                    </div>
                                    <span class="text-xs font-medium truncate">{{ $p->name }}</span>
                                    @if($p->is_child)
                                        <span class="text-[8px] font-extrabold uppercase px-1 rounded bg-purple-500/20 text-purple-300">Anak</span>
                                    @endif
                                    @if(!empty($p->pin))
                                        <i data-lucide="lock" class="w-3 h-3 text-amber-400 shrink-0"></i>
                                    @endif
                                </div>
                                @if($activeProfile && $activeProfile->id == $p->id)
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                                @endif
                            </button>
                        @endforeach

                        <a href="{{ route('profiles.index') }}" class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-amber-400 hover:bg-amber-500/10 transition-colors">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            <span>Kelola & Pilih Profil...</span>
                        </a>
                    </div>

                    <!-- Actions List -->
                    <div class="p-2 space-y-0.5">
                        <a href="{{ route('profile') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs text-zinc-300 hover:text-white hover:bg-white/5 transition-colors">
                            <i data-lucide="user-cog" class="w-4 h-4 text-zinc-400"></i>
                            <span>Akun</span>
                        </a>

                        <a href="{{ route('notifications.index') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs text-zinc-300 hover:text-white hover:bg-white/5 transition-colors">
                            <i data-lucide="bell" class="w-4 h-4 text-zinc-400"></i>
                            <span>Halaman Notifikasi</span>
                        </a>

                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs text-amber-300 hover:bg-amber-500/10 transition-colors font-semibold">
                                <i data-lucide="shield" class="w-4 h-4 text-amber-400"></i>
                               <span>Admin Panel</span>
                            </a>
                        @endif
                    </div>

                    <!-- Logout -->
                    <div class="p-2 border-t border-white/10 bg-zinc-950/40">
                        @if($activeProfile)
                            <form action="{{ route('profiles.switch-main') }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        title="Beralih ke Akun Utama terlebih dahulu sebelum dapat Keluar"
                                        class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-xs font-semibold text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-400"></i>
                                        <span>Beralih ke Utama utk Logout</span>
                                    </div>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs font-semibold text-red-400 hover:bg-red-500/10 transition-colors cursor-pointer">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                    <span>Keluar (Log Out)</span>
                                </button>
                            </form>
                        @endif
                    </div>

                </div>

                <!-- Global Navbar PIN Modal -->
                <template x-teleport="body">
                    <div x-show="showPinModal" 
                         x-cloak
                         x-transition.opacity
                         class="fixed inset-0 z-[999] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                        
                        <div @click.outside="showPinModal = false" 
                             class="bg-zinc-900 border border-white/15 rounded-3xl max-w-sm w-full p-6 space-y-5 shadow-2xl relative text-center">
                            
                            <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 mx-auto flex items-center justify-center">
                                <i data-lucide="lock" class="w-6 h-6"></i>
                            </div>

                            <div class="space-y-1">
                                <h3 class="font-bold text-white text-lg font-['Outfit']">Masukkan PIN Profil</h3>
                                <p class="text-xs text-zinc-400">Profil <span class="text-white font-bold" x-text="targetProfileName"></span> dilindungi dengan 4-digit PIN.</p>
                            </div>

                            <div class="space-y-3">
                                <input type="password" 
                                       x-model="enteredPin"
                                       @keyup.enter="performSwitch(targetProfileId, enteredPin)"
                                       maxlength="4" 
                                       placeholder="****" 
                                       class="w-full text-center bg-zinc-950 border border-white/15 rounded-2xl px-4 py-3 text-xl font-mono text-white placeholder-zinc-600 focus:outline-none focus:border-amber-400 tracking-[0.5em]">
                                
                                <p x-show="pinError" class="text-xs text-red-400 font-medium" x-text="pinError"></p>
                            </div>

                            <div class="flex items-center justify-center gap-3 pt-2">
                                <button type="button" @click="showPinModal = false" class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold text-zinc-300 transition-all cursor-pointer">
                                    Batal
                                </button>
                                <button type="button" 
                                        @click="performSwitch(targetProfileId, enteredPin)" 
                                        :disabled="isSubmitting"
                                        class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-xs font-extrabold shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                    <span x-text="isSubmitting ? 'Verifikasi...' : 'Masuk'"></span>
                                    <i x-show="!isSubmitting" data-lucide="arrow-right" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        @else
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-zinc-800/80 hover:bg-zinc-700 text-zinc-200 hover:text-white transition-all text-xs font-semibold border border-white/10 shadow-sm">
                <i data-lucide="user" class="w-3.5 h-3.5 text-zinc-400"></i>
                <span>Log in</span>
            </a>
        @endauth
    </div>

</header>

