<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>faiilmov - Watch Movies & Series Online</title>
    <meta name="description" content="Discover, stream, and download latest HD movies and TV series powered by MovieBox API.">
    
    <!-- Fonts: Instrument Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN & Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['"Outfit"', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            900: '#070A11',
                            800: '#0F1523',
                            700: '#182035',
                            600: '#232D48',
                        },
                        accent: {
                            purple: '#7C3AED',
                            indigo: '#6366F1',
                            pink: '#EC4899',
                            cyan: '#06B6D4',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    },
                    keyframes: {
                        glow: {
                            '0%': { boxShadow: '0 0 15px rgba(124, 58, 237, 0.3)' },
                            '100%': { boxShadow: '0 0 30px rgba(99, 102, 241, 0.6)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #070a11; }
        ::-webkit-scrollbar-thumb { background: #232d48; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6366f1; }
        
        .glass-panel {
            background: rgba(15, 21, 35, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .glass-card {
            background: rgba(24, 32, 53, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #a78bfa 0%, #818cf8 50%, #f472b6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-gradient {
            background: linear-gradient(180deg, rgba(7, 10, 17, 0.2) 0%, rgba(7, 10, 17, 0.8) 70%, rgba(7, 10, 17, 1) 100%);
        }
    </style>
</head>

<body class="bg-dark-900 text-slate-100 font-sans antialiased min-h-screen selection:bg-indigo-500 selection:text-white" 
      x-data="movieApp()" x-init="initApp()">

    <!-- Header Navigation -->
    <header class="fixed top-0 left-0 right-0 z-40 glass-panel border-b border-white/10 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            
            <!-- Logo -->
            <a href="#" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-violet-600 via-indigo-500 to-pink-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                    <i data-lucide="clapperboard" class="w-5 h-5 text-white"></i>
                </div>
                <span class="font-display font-bold text-2xl tracking-tight text-white group-hover:opacity-90 transition-opacity">
                    Cine<span class="gradient-text">Stream</span>
                </span>
            </a>

            <!-- Search Bar -->
            <div class="flex-1 max-w-xl relative">
                <div class="relative">
                    <input type="text" 
                           x-model="searchQuery" 
                           @input.debounce.400ms="handleSearch()"
                           placeholder="Search movies, TV series, anime..." 
                           class="w-full bg-dark-800/80 text-sm text-slate-100 placeholder-slate-400 pl-11 pr-10 py-3 rounded-full border border-slate-700/60 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                    <i data-lucide="search" class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                    
                    <button x-show="searchQuery" @click="clearSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Instant Search Dropdown Results -->
                <div x-show="searchResults.length > 0 && searchQuery" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     @click.outside="searchResults = []"
                     class="absolute top-full left-0 right-0 mt-3 glass-panel rounded-2xl p-3 shadow-2xl border border-slate-700/80 max-h-96 overflow-y-auto z-50">
                    
                    <template x-for="item in searchResults" :key="item.subjectId">
                        <div @click="openModal(item.subjectId)" 
                             class="flex items-center gap-4 p-2.5 rounded-xl hover:bg-slate-800/80 cursor-pointer transition-colors group">
                            <img :src="item.cover?.url || getFallbackPoster(item.title)" 
                                 :alt="item.title"
                                 class="w-12 h-16 object-cover rounded-lg shadow-md group-hover:scale-105 transition-transform duration-300">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-white truncate group-hover:text-indigo-400 transition-colors" x-text="item.title"></h4>
                                <div class="flex items-center gap-2 mt-1 text-xs text-slate-400">
                                    <span class="px-2 py-0.5 rounded bg-slate-700/60 text-slate-300" x-text="item.subjectType == 2 ? 'TV Series' : 'Movie'"></span>
                                    <span x-text="item.releaseDate ? item.releaseDate.substring(0,4) : '2024'"></span>
                                    <span class="flex items-center gap-1 text-amber-400">
                                        <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                        <span x-text="item.imdbRatingValue || '8.1'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quick Navigation Pills -->
            <nav class="hidden md:flex items-center gap-1 bg-dark-800/80 p-1.5 rounded-full border border-slate-800">
                <button @click="changeTab('0')" 
                        :class="activeTab === '0' ? 'bg-indigo-600 text-white font-medium shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                        class="px-4 py-2 text-xs rounded-full transition-all duration-200">
                    All Feed
                </button>
                <button @click="changeTab('Movie')" 
                        :class="activeTab === 'Movie' ? 'bg-indigo-600 text-white font-medium shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                        class="px-4 py-2 text-xs rounded-full transition-all duration-200">
                    Movies
                </button>
                <button @click="changeTab('TV')" 
                        :class="activeTab === 'TV' ? 'bg-indigo-600 text-white font-medium shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                        class="px-4 py-2 text-xs rounded-full transition-all duration-200">
                    Series
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Body Container -->
    <main class="pt-20">
        
        <!-- Hero Featured Section -->
        <section class="relative min-h-[550px] lg:min-h-[650px] flex items-end justify-center pb-16 overflow-hidden">
            <!-- Hero Backdrop Image -->
            <div class="absolute inset-0 z-0">
                <img :src="featuredItem?.cover?.url || 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?q=80&w=1920&auto=format&fit=crop'" 
                     class="w-full h-full object-cover object-center filter brightness-90 scale-105 animate-pulse-slow">
                <div class="absolute inset-0 hero-gradient"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-dark-900 via-dark-900/50 to-transparent"></div>
            </div>

            <!-- Hero Content -->
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel border border-indigo-500/30 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-4">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Featured Spotlight</span>
                    </div>

                    <h1 class="font-display font-extrabold text-4xl sm:text-5xl lg:text-6xl text-white tracking-tight leading-tight mb-4"
                        x-text="featuredItem?.title || 'Featured Blockbuster'">
                    </h1>

                    <div class="flex items-center gap-4 text-sm text-slate-300 mb-6 flex-wrap">
                        <span class="flex items-center gap-1 text-amber-400 font-bold">
                            <i data-lucide="star" class="w-4 h-4 fill-amber-400"></i>
                            <span x-text="featuredItem?.imdbRatingValue || '8.8'"></span>
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                        <span class="px-2.5 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 font-medium text-xs border border-indigo-500/30" 
                              x-text="featuredItem?.subjectType == 2 ? 'TV Series' : 'Movie'">
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                        <span x-text="featuredItem?.releaseDate || '2024'"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                        <span class="text-xs px-2 py-0.5 rounded border border-slate-700 text-slate-400">Ultra HD 4K</span>
                    </div>

                    <p class="text-slate-300 text-sm sm:text-base line-clamp-3 leading-relaxed mb-8"
                       x-text="featuredItem?.description || 'Explore high quality movies, stream full episodes in HD, and enjoy seamless playback powered by MovieBox backend API.'">
                    </p>

                    <!-- Hero Actions -->
                    <div class="flex items-center gap-4">
                        <button @click="openModal(featuredItem?.subjectId || '1')" 
                                class="px-7 py-3.5 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-semibold text-sm hover:from-violet-500 hover:to-indigo-500 shadow-xl shadow-indigo-600/30 hover:scale-105 transition-all duration-300 flex items-center gap-2">
                            <i data-lucide="play" class="w-5 h-5 fill-white"></i>
                            <span>Watch Now</span>
                        </button>
                        <button @click="openModal(featuredItem?.subjectId || '1')" 
                                class="px-6 py-3.5 rounded-full glass-panel text-white font-semibold text-sm hover:bg-white/10 transition-all duration-300 flex items-center gap-2 border border-white/15">
                            <i data-lucide="info" class="w-5 h-5 text-slate-300"></i>
                            <span>More Info</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Movie Catalog Grid -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            
            <!-- Section Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="font-display font-bold text-xl sm:text-2xl text-white tracking-tight flex items-center gap-3">
                        <span>Trending Now</span>
                    </h2>
                    <p class="text-slate-400 text-sm mt-1">Handpicked collection updated live from server</p>
                </div>

                <!-- View Switcher -->
                <div class="flex items-center gap-2 bg-dark-800 p-1 rounded-xl border border-slate-800">
                    <button @click="changeTab('0')" class="p-2 text-slate-400 hover:text-white rounded-lg transition-colors">
                        <i data-lucide="flame" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Loading Skeleton Grid -->
            <div x-show="isLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <template x-for="i in 12" :key="i">
                    <div class="glass-card rounded-2xl p-3 animate-pulse">
                        <div class="aspect-[2/3] bg-slate-800/80 rounded-xl mb-3"></div>
                        <div class="h-4 bg-slate-800/80 rounded w-3/4 mb-2"></div>
                        <div class="h-3 bg-slate-800/80 rounded w-1/2"></div>
                    </div>
                </template>
            </div>

            <!-- Movies Grid -->
            <div x-show="!isLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <template x-for="item in displayList" :key="item.subjectId">
                    <div @click="openModal(item.subjectId)" 
                         class="group relative glass-card rounded-2xl p-3 cursor-pointer hover:border-indigo-500/50 hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                        
                        <!-- Poster Image Container -->
                        <div class="relative aspect-[2/3] rounded-xl overflow-hidden mb-3 bg-dark-800">
                            <img :src="item.cover?.url || getFallbackPoster(item.title)" 
                                 :alt="item.title"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            
                            <!-- Badges Overlay -->
                            <div class="absolute top-2 left-2 flex flex-col gap-1">
                                <span class="px-2 py-0.5 rounded-md bg-dark-900/80 backdrop-blur-md text-[10px] font-bold tracking-wider uppercase text-indigo-300 border border-indigo-500/20"
                                      x-text="item.subjectType == 2 ? 'SERIES' : 'MOVIE'">
                                </span>
                            </div>

                            <div class="absolute top-2 right-2 px-2 py-0.5 rounded-md bg-dark-900/80 backdrop-blur-md text-xs font-bold text-amber-400 flex items-center gap-1 border border-amber-500/20">
                                <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                <span x-text="item.imdbRatingValue || '7.9'"></span>
                            </div>

                            <!-- Resolution Badge (bottom-left) -->
                            <div class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-dark-900/80 backdrop-blur-md text-[9px] font-extrabold uppercase tracking-wider border"
                                 :class="getResClass(item)">
                                <span x-text="getResLabel(item)"></span>
                            </div>

                            <!-- Play Hover Effect Overlay -->
                            <div class="absolute inset-0 bg-indigo-950/60 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-600/50 scale-75 group-hover:scale-100 transition-transform duration-300">
                                    <i data-lucide="play" class="w-6 h-6 fill-white ml-0.5"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div>
                            <h3 class="font-semibold text-sm text-slate-100 group-hover:text-indigo-400 transition-colors line-clamp-1" x-text="item.title"></h3>
                            <div class="flex items-center justify-between text-xs text-slate-400 mt-1">
                                <span x-text="item.releaseDate ? item.releaseDate.substring(0,4) : '2024'"></span>
                                <span class="text-[11px] text-slate-500" x-text="getResLabel(item)"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

    </main>

    <!-- Details & Stream Modal -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-dark-900/90 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        
        <div @click.outside="closeModal()" 
             class="relative w-full max-w-4xl glass-panel rounded-3xl overflow-hidden border border-slate-700/80 shadow-2xl my-8">
            
            <!-- Close Button -->
            <button @click="closeModal()" class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full bg-dark-800/80 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <!-- Loading Spinner in Modal -->
            <div x-show="isModalLoading" class="py-24 flex flex-col items-center justify-center gap-4">
                <div class="w-12 h-12 border-4 border-indigo-500/30 border-t-indigo-600 rounded-full animate-spin"></div>
                <p class="text-slate-400 text-sm">Fetching movie streams & resources...</p>
            </div>

            <!-- Modal Content -->
            <div x-show="!isModalLoading">
                
                <!-- Player / Trailer Header -->
                <div class="relative bg-black aspect-video w-full flex items-center justify-center overflow-hidden">
                    <template x-if="activeResourceUrl">
                        <video :src="activeResourceUrl" controls autoplay class="w-full h-full object-contain"></video>
                    </template>
                    <template x-if="!activeResourceUrl">
                        <div class="relative w-full h-full">
                            <img :src="modalData?.cover?.url || getFallbackPoster(modalData?.title)" class="w-full h-full object-cover filter blur-sm opacity-50">
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
                                <div class="w-16 h-16 rounded-full bg-indigo-600/80 text-white flex items-center justify-center shadow-lg shadow-indigo-600/50">
                                    <i data-lucide="play" class="w-8 h-8 fill-white"></i>
                                </div>
                                <h3 class="text-lg font-bold text-white" x-text="modalData?.title"></h3>
                                <p class="text-xs text-slate-300 max-w-md">Pilih kualitas video di bawah ini untuk memulai pemutaran streaming atau download.</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Detail Body -->
                <div class="p-6 sm:p-8">
                    
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-6">
                        <div>
                            <h2 class="font-display font-bold text-2xl sm:text-3xl text-white mb-2" x-text="modalData?.title"></h2>
                            <div class="flex items-center gap-3 text-xs text-slate-400 flex-wrap">
                                <span class="flex items-center gap-1 text-amber-400 font-bold">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400"></i>
                                    <span x-text="modalData?.imdbRatingValue || '8.0'"></span>
                                </span>
                                <span>•</span>
                                <span x-text="modalData?.releaseDate || '2024'"></span>
                                <span>•</span>
                                <span x-text="modalData?.duration || '120 min'"></span>
                                <span>•</span>
                                <span class="px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-semibold" x-text="modalData?.genre || 'Action, Drama'"></span>
                            </div>
                        </div>
                    </div>

                    <p class="text-slate-300 text-sm leading-relaxed mb-6" x-text="modalData?.description || 'No description available for this title.'"></p>

                    <!-- Stream / Download Resource Links -->
                    <div class="border-t border-slate-800 pt-6">
                        <h4 class="font-display font-semibold text-sm text-white mb-4 flex items-center gap-2">
                            <i data-lucide="download" class="w-4 h-4 text-indigo-400"></i>
                            <span>Resource Stream & Download Links</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto pr-1">
                            <template x-for="res in resourceList" :key="res.id || res.url">
                                <div class="glass-card p-3 rounded-xl flex items-center justify-between hover:border-indigo-500/40 transition-colors">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-800 text-indigo-300" x-text="res.resolution + 'p' || '1080p'"></span>
                                            <span class="text-xs text-slate-300 font-medium" x-text="res.format || 'MP4'"></span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-1" x-text="res.size ? (res.size / 1048576).toFixed(1) + ' MB' : 'Direct Link'"></div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button @click="playVideo(res.resourceLink || res.url || res.playUrl)" 
                                                class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-500 transition-colors flex items-center gap-1">
                                            <i data-lucide="play" class="w-3.5 h-3.5 fill-white"></i>
                                            <span>Play</span>
                                        </button>
                                        <a :href="res.resourceLink || res.url || res.playUrl" target="_blank" download
                                           class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition-colors">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-dark-900 py-8 mt-16 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4">
            <p>© 2026 faiilmov. Powered by MovieBox Backend API Client.</p>
        </div>
    </footer>

    <!-- Alpine App Script -->
    <script>
        function movieApp() {
            return {
                searchQuery: '',
                activeTab: '0',
                isLoading: false,
                displayList: [],
                searchResults: [],
                featuredItem: null,
                
                // Modal State
                showModal: false,
                isModalLoading: false,
                modalData: null,
                resourceList: [],
                activeResourceUrl: null,

                async initApp() {
                    this.loadFeed('0');
                    setTimeout(() => lucide.createIcons(), 200);
                },

                async loadFeed(tabId) {
                    this.isLoading = true;
                    try {
                        const res = await fetch(`/moviebox/homepage?tabId=${tabId}&page=1`);
                        const data = await res.json();
                        
                        let items = [];
                        if (data.operatingTabs && data.operatingTabs.length > 0) {
                            data.operatingTabs.forEach(tab => {
                                if (tab.subjects) items = items.concat(tab.subjects);
                            });
                        } else if (data.subjects) {
                            items = data.subjects;
                        }

                        this.displayList = items;
                        if (items.length > 0 && !this.featuredItem) {
                            this.featuredItem = items[0];
                        }
                    } catch (e) {
                        console.error('Failed to load feed:', e);
                    } finally {
                        this.isLoading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                async handleSearch() {
                    if (!this.searchQuery.trim()) {
                        this.searchResults = [];
                        return;
                    }

                    try {
                        const res = await fetch(`/moviebox/search?q=${encodeURIComponent(this.searchQuery)}`);
                        const data = await res.json();
                        this.searchResults = data.list || [];
                    } catch (e) {
                        console.error('Search error:', e);
                    } finally {
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.searchResults = [];
                },

                changeTab(tabId) {
                    this.activeTab = tabId;
                    this.loadFeed(tabId);
                },

                async openModal(subjectId) {
                    this.showModal = true;
                    this.isModalLoading = true;
                    this.modalData = null;
                    this.resourceList = [];
                    this.activeResourceUrl = null;

                    try {
                        const [detailRes, resourceRes] = await Promise.all([
                            fetch(`/moviebox/detail/${subjectId}`),
                            fetch(`/moviebox/resources/${subjectId}`)
                        ]);

                        this.modalData = await detailRes.json();
                        const resources = await resourceRes.json();

                        if (resources.list) {
                            this.resourceList = resources.list;
                        } else if (Array.isArray(resources)) {
                            this.resourceList = resources;
                        }
                    } catch (e) {
                        console.error('Failed to fetch details:', e);
                    } finally {
                        this.isModalLoading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                closeModal() {
                    this.showModal = false;
                    this.activeResourceUrl = null;
                },

                playVideo(url) {
                    this.activeResourceUrl = url;
                },

                getFallbackPoster(title) {
                    return `https://images.unsplash.com/photo-1518676599602-2170de9df05d?q=80&w=400&auto=format&fit=crop`;
                },

                getResLabel(item) {
                    const raw = (item.sharpness || item.quality || item.maxResolution || item.resolution || '').toString().toLowerCase();
                    if (raw.includes('4k') || raw.includes('2160') || raw.includes('uhd')) return '4K';
                    if (raw.includes('1080') || raw.includes('fhd')) return '1080P';
                    if (raw.includes('720') || raw.includes('hd')) return '720P';
                    if (raw.includes('480')) return '480P';
                    return '1080P'; // default fallback
                },

                getResClass(item) {
                    const label = this.getResLabel(item);
                    if (label === '4K') return 'text-violet-300 border-violet-500/30';
                    return 'text-sky-300 border-sky-500/30';
                }
            }
        }
    </script>
</body>
</html>
