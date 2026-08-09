<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'faiilmov | Database Film & Streaming')</title>
    
    <!-- Favicon & App Icon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Google Fonts: Instrument Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #09090b; }
        ::-webkit-scrollbar-thumb { background: #272730; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #71717a; }
        
        .glass-panel {
            background: rgba(12, 12, 16, 0.92);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.5rem;
        }
        
        .glass-card {
            background: rgba(24, 24, 32, 0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
        }

        .glass-chip {
            background: rgba(9, 9, 11, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.875rem;
        }

        .bridge-container {
            background: rgba(18, 18, 23, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

<body class="bg-dark-950 text-zinc-200 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-white selection:text-zinc-950 relative overflow-x-hidden"
      x-data="{ sidebarOpen: false }">

    <!-- Neutral Gray Ambient Background Mesh Blobs -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-zinc-700/10 blur-[140px]"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 rounded-full bg-zinc-600/10 blur-[140px]"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 rounded-full bg-zinc-800/15 blur-[140px]"></div>
    </div>

    <!-- Toast Notification -->
    @if(session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 4000)"
             class="fixed top-20 right-6 z-50 glass-panel border border-emerald-500/40 text-emerald-400 px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Top Navigation Header Component -->
    <x-navbar />

    @unless(View::hasSection('hide_sidebar'))
        <!-- Left Sidebar Navigation Component -->
        <x-sidebar />
    @endunless

    <!-- Main Content Body -->
    <main class="pt-20 sm:pt-24 {{ View::hasSection('hide_sidebar') ? '' : 'lg:pl-64' }} flex-grow relative z-10">
        @yield('content')
    </main>

    <!-- Footer Component -->
    <x-footer />

    <!-- Persistent Global Cross-Page Floating Mini Player Component -->
    <x-global-mini-player />

    <!-- Reusable Welcome / Benefit Info Modal Component -->
    <x-welcome-modal />

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });

        function searchAutocomplete() {
            return {
                query: '{{ request('q') }}',
                suggestions: [],
                popularFilms: [],
                searchHistory: [],
                showPanel: false,
                focusedIndex: -1,
                debounceTimer: null,

                init() {
                    this.loadHistory();
                    this.fetchPopular();
                },

                loadHistory() {
                    try {
                        const raw = localStorage.getItem('faii_search_history');
                        this.searchHistory = raw ? JSON.parse(raw) : [];
                    } catch (e) {
                        this.searchHistory = [];
                    }
                },

                saveHistoryTerm(term) {
                    if (!term || !term.trim()) return;
                    const clean = term.trim();
                    this.searchHistory = [clean, ...this.searchHistory.filter(item => item.toLowerCase() !== clean.toLowerCase())].slice(0, 6);
                    try {
                        localStorage.setItem('faii_search_history', JSON.stringify(this.searchHistory));
                    } catch (e) {}
                },

                removeHistoryItem(index) {
                    this.searchHistory.splice(index, 1);
                    try {
                        localStorage.setItem('faii_search_history', JSON.stringify(this.searchHistory));
                    } catch (e) {}
                },

                clearHistory() {
                    this.searchHistory = [];
                    try {
                        localStorage.removeItem('faii_search_history');
                    } catch (e) {}
                },

                useHistoryTerm(term) {
                    this.query = term;
                    this.onInput();
                    this.$refs.searchInput.focus();
                },

                async fetchPopular() {
                    try {
                        const res = await fetch('/search/autocomplete?popular=1', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (res.ok) {
                            this.popularFilms = await res.json();
                        }
                    } catch (e) {}
                },

                onInput() {
                    clearTimeout(this.debounceTimer);
                    if (this.query.trim().length < 2) {
                        this.suggestions = [];
                        return;
                    }

                    this.debounceTimer = setTimeout(() => {
                        this.fetchSuggestions();
                    }, 300);
                },

                async fetchSuggestions() {
                    try {
                        const res = await fetch(`/search/autocomplete?q=${encodeURIComponent(this.query)}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (res.ok) {
                            this.suggestions = await res.json();
                            this.focusedIndex = -1;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    } catch (e) {
                        console.error('Autocomplete fetch error:', e);
                    }
                },

                openPanel() {
                    this.showPanel = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                closePanel() {
                    this.showPanel = false;
                    this.focusedIndex = -1;
                },

                clearSearch() {
                    this.query = '';
                    this.suggestions = [];
                },

                navigateDown() {
                    if (!this.showPanel || this.suggestions.length === 0) return;
                    if (this.focusedIndex < this.suggestions.length - 1) {
                        this.focusedIndex++;
                    } else {
                        this.focusedIndex = 0;
                    }
                },

                navigateUp() {
                    if (!this.showPanel || this.suggestions.length === 0) return;
                    if (this.focusedIndex > 0) {
                        this.focusedIndex--;
                    } else {
                        this.focusedIndex = this.suggestions.length - 1;
                    }
                },

                selectFocused() {
                    if (this.query.trim()) {
                        this.saveHistoryTerm(this.query);
                    }
                    if (this.showPanel && this.focusedIndex >= 0 && this.suggestions[this.focusedIndex]) {
                        window.location.href = this.suggestions[this.focusedIndex].url;
                    } else if (this.query.trim()) {
                        window.location.href = `{{ route('browse') }}?q=${encodeURIComponent(this.query)}`;
                    }
                },

                highlightMatch(text) {
                    if (!this.query) return text;
                    const escaped = this.query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                    const regex = new RegExp(`(${escaped})`, 'gi');
                    return text.replace(regex, '<span class="font-extrabold text-amber-300 underline decoration-amber-400/50">$1</span>');
                }
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
