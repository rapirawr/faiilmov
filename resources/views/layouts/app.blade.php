<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Google Search Console Verification -->
    <meta name="google-site-verification" content="O5FIi4EuweW7xm1i2EspXhFlPbSIPOx4ZQ3gZMp1wmM">

    <!-- SEO Meta Tags & Schema.org JSON-LD -->
    @if(request()->routeIs('film.show', 'film.watch') && isset($film) && $film instanceof \App\Models\Film)
        <x-seo-meta :film="$film" />
    @elseif(View::hasSection('title'))
        <x-seo-meta 
            :title="View::getSection('title')" 
            :description="View::hasSection('meta_description') ? View::getSection('meta_description') : null" 
            :keywords="View::hasSection('meta_keywords') ? View::getSection('meta_keywords') : null" 
            :image="View::hasSection('og_image') ? View::getSection('og_image') : null" 
            :type="View::hasSection('og_type') ? View::getSection('og_type') : null" 
            :url="View::hasSection('canonical') ? View::getSection('canonical') : null" 
        />
        @hasSection('schema_org')
            @yield('schema_org')
        @endif
    @else
        <x-seo-meta />
    @endif

    @php
        $siteGlobalSetting = \App\Models\SiteSetting::current();
    @endphp

    <!-- Favicon & App Icon -->
    <link rel="icon" type="image/png" href="{{ $siteGlobalSetting->favicon_url }}">
    <link rel="apple-touch-icon" href="{{ $siteGlobalSetting->logo_url }}">
    <link rel="shortcut icon" href="{{ $siteGlobalSetting->favicon_url }}">

    @if($siteGlobalSetting->page_transition_enabled)
        @if($siteGlobalSetting->page_transition_gif_isload_url)
            <!-- Preload isLoad Transition Animation -->
            <link rel="preload" as="image" href="{{ $siteGlobalSetting->page_transition_gif_isload_url }}" fetchpriority="high">
        @endif
        @if($siteGlobalSetting->page_transition_gif_loaded_url)
            <!-- Preload load Transition Animation -->
            <link rel="preload" as="image" href="{{ $siteGlobalSetting->page_transition_gif_loaded_url }}" fetchpriority="high">
        @endif

        @if($siteGlobalSetting->page_transition_gif_isload_url || $siteGlobalSetting->page_transition_gif_loaded_url)
            <!-- Early Head Sync: Keep loader solid active while browser is loading next page -->
            <script>
                (function() {
                    try {
                        if (sessionStorage.getItem('faiilmov_nav_loading') === '1') {
                            document.documentElement.classList.add('faiilmov-nav-loading');
                        }
                    } catch(e) {}
                })();
            </script>
            <style>
                .faiilmov-nav-loading #page-transition-loader {
                    opacity: 1 !important;
                    visibility: visible !important;
                    pointer-events: auto !important;
                }
            </style>
        @endif
    @endif

    <!-- Dynamic CMS Theme Custom Properties -->
    <style>
        :root {
            --site-primary-color: {{ $siteGlobalSetting->primary_color ?: '#ffffff' }};
            --site-secondary-color: {{ $siteGlobalSetting->secondary_color ?: '#a1a1aa' }};
            --site-bg-color: {{ $siteGlobalSetting->background_color ?: '#09090b' }};
        }
    </style>
    
    <!-- Google Fonts: Instrument Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    
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
        @font-face {
            font-family: 'Chillax';
            src: url('{{ asset('fonts/chillax/Chillax-Bold.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/chillax/Chillax-Bold.woff') }}') format('woff'),
                 url('{{ asset('fonts/chillax/Chillax-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-display: swap;
            font-style: normal;
        }
        @font-face {
            font-family: 'Chillax';
            src: url('{{ asset('fonts/chillax/Chillax-Semibold.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/chillax/Chillax-Semibold.woff') }}') format('woff'),
                 url('{{ asset('fonts/chillax/Chillax-Semibold.ttf') }}') format('truetype');
            font-weight: 600;
            font-display: swap;
            font-style: normal;
        }
        @font-face {
            font-family: 'Chillax';
            src: url('{{ asset('fonts/chillax/Chillax-Medium.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/chillax/Chillax-Medium.woff') }}') format('woff'),
                 url('{{ asset('fonts/chillax/Chillax-Medium.ttf') }}') format('truetype');
            font-weight: 500;
            font-display: swap;
            font-style: normal;
        }
        @font-face {
            font-family: 'Chillax';
            src: url('{{ asset('fonts/chillax/Chillax-Regular.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/chillax/Chillax-Regular.woff') }}') format('woff'),
                 url('{{ asset('fonts/chillax/Chillax-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-display: swap;
            font-style: normal;
        }
        .font-chillax {
            font-family: 'Chillax', 'Outfit', sans-serif !important;
        }
    </style>
    
    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Pusher / Echo Global Config -->
    <script>
        window.PUSHER_CONFIG = {
            key: '{{ config('broadcasting.connections.pusher.key', '84a6e3fa24e4374c43b5') }}',
            cluster: '{{ config('broadcasting.connections.pusher.options.cluster', 'ap1') }}'
        };
    </script>

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

    <!-- Adsterra Popunder / OnClick Injection -->
    <x-ad-popunder />
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

    @if($siteGlobalSetting->maintenance_mode && auth()->check() && (auth()->user()->is_admin || (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin())))
        <!-- Floating Admin Maintenance Mode Badge Indicator -->
        <div class="fixed top-24 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-2xl bg-red-600/90 text-white font-bold text-xs shadow-2xl backdrop-blur-xl border border-white/20 flex items-center gap-2 pointer-events-auto">
            <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
            <i data-lucide="shield-alert" class="w-4 h-4"></i>
            <span>Mode Maintenance Aktif (Pengunjung melihat 503 • Anda melihat sebagai Admin)</span>
            <a href="{{ route('admin.settings.index') }}" class="underline ml-1 hover:text-zinc-200">CMS &rarr;</a>
        </div>
    @endif

    @unless(View::hasSection('hide_navbar'))
        <!-- Top Navigation Header Component (Includes Integrated Top Broadcast Bar) -->
        <x-navbar />
    @endunless

    <!-- Global Floating Widgets & Popup Modals -->
    <x-page-elements-renderer :section="'body'" />

    @unless(View::hasSection('hide_sidebar'))
        <!-- Left Sidebar Navigation Component -->
        <x-sidebar />
    @endunless

    <!-- Main Content Body -->
    <main class="{{ View::hasSection('hide_navbar') ? 'pt-0' : 'pt-20 sm:pt-24' }} {{ View::hasSection('hide_sidebar') ? '' : 'lg:pl-64' }} flex-grow relative z-10">
        <!-- In-Content Custom HTML / Iframe / Embed Blocks -->
        <x-page-elements-renderer :section="'content_blocks'" />

        @yield('content')
    </main>

    @unless(View::hasSection('hide_footer'))
        <!-- Footer Component -->
        <x-footer />
    @endunless

    <!-- Persistent Global Cross-Page Floating Mini Player Component -->
    <x-global-mini-player />

    @unless(View::hasSection('hide_welcome_modal') || View::hasSection('hide_navbar'))
        <!-- Reusable Welcome / Benefit Info Modal Component -->
        <x-welcome-modal />
    @endunless

    <!-- Real-time Device Push Notification & In-App Toast Manager -->
    <x-device-notification-manager />

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
    <!-- Global React Modals Container -->
    <div id="react-film-request-modal" data-csrf="{{ csrf_token() }}"></div>
    <div id="react-visual-search-modal" data-csrf="{{ csrf_token() }}"></div>
    <div id="react-create-collection-modal" data-csrf="{{ csrf_token() }}"></div>

    <!-- Adsterra Social Bar & Anti-Adblock Module -->
    <x-ad-social-bar />
    <x-anti-adblock />

    @if($siteGlobalSetting->page_transition_enabled && ($siteGlobalSetting->page_transition_gif_isload_url || $siteGlobalSetting->page_transition_gif_loaded_url))
        <!-- Fullscreen Page Transition Loader Overlay (Bulletproof & Non-Glitching) -->
        <div id="page-transition-loader" 
             class="fixed inset-0 z-[999999] flex items-center justify-center bg-black opacity-0 invisible pointer-events-none transition-all duration-200 ease-out transform-gpu will-change-[opacity,visibility]"
             aria-hidden="true">
            <div class="relative flex items-center justify-center w-48 h-48 p-4 transform-gpu">
                @if($siteGlobalSetting->page_transition_gif_isload_url)
                    <img id="trans-img-isload"
                         src="{{ $siteGlobalSetting->page_transition_gif_isload_url }}" 
                         alt="Loading..." 
                         decoding="async"
                         loading="eager"
                         class="absolute max-w-[150px] max-h-[150px] sm:max-w-[180px] sm:max-h-[180px] object-contain drop-shadow-2xl select-none pointer-events-none transition-opacity duration-300 opacity-100 transform-gpu">
                @endif

                @if($siteGlobalSetting->page_transition_gif_loaded_url)
                    <img id="trans-img-loaded"
                         src="{{ $siteGlobalSetting->page_transition_gif_loaded_url }}" 
                         alt="Loaded..." 
                         decoding="async"
                         loading="eager"
                         class="absolute max-w-[150px] max-h-[150px] sm:max-w-[180px] sm:max-h-[180px] object-contain drop-shadow-2xl select-none pointer-events-none transition-opacity duration-300 opacity-0 transform-gpu">
                @endif
            </div>
        </div>

        <script>
            (function() {
                const loader = document.getElementById('page-transition-loader');
                if (!loader) return;

                const imgIsload = document.getElementById('trans-img-isload');
                const imgLoaded = document.getElementById('trans-img-loaded');

                let hideTimer = null;
                let isShowing = false;

                function showTransition() {
                    if (isShowing) return;
                    isShowing = true;

                    // Set session flag so incoming page keeps the overlay seamless from frame 0
                    try {
                        sessionStorage.setItem('faiilmov_nav_loading', '1');
                    } catch(e) {}

                    // Reset initial images state (isLoad visible, loaded hidden)
                    if (imgIsload) imgIsload.classList.replace('opacity-0', 'opacity-100');
                    if (imgLoaded) imgLoaded.classList.replace('opacity-100', 'opacity-0');

                    // Reveal overlay
                    loader.classList.remove('invisible', 'opacity-0', 'pointer-events-none');
                    loader.classList.add('opacity-100', 'pointer-events-auto');

                    // Safety timeout (5s) in case navigation is cancelled or network fails
                    if (hideTimer) clearTimeout(hideTimer);
                    hideTimer = setTimeout(forceHide, 5000);
                }

                function finishTransition() {
                    let wasNavigating = false;
                    try {
                        wasNavigating = sessionStorage.getItem('faiilmov_nav_loading') === '1';
                        sessionStorage.removeItem('faiilmov_nav_loading');
                    } catch(e) {}

                    // If incoming page was part of a transition and has 'load' GIF
                    if (wasNavigating && imgLoaded && imgIsload) {
                        // Switch from isLoad to load GIF smoothly
                        imgIsload.classList.replace('opacity-100', 'opacity-0');
                        imgLoaded.classList.replace('opacity-0', 'opacity-100');

                        // Let the 'load' celebration animation play for 600ms, then fade out
                        setTimeout(function() {
                            forceHide();
                        }, 600);
                    } else if (wasNavigating) {
                        // Minimal smooth delay so the page doesn't pop abruptly
                        setTimeout(function() {
                            forceHide();
                        }, 250);
                    } else {
                        forceHide();
                    }
                }

                function forceHide() {
                    isShowing = false;
                    if (hideTimer) clearTimeout(hideTimer);

                    try {
                        sessionStorage.removeItem('faiilmov_nav_loading');
                    } catch(e) {}
                    document.documentElement.classList.remove('faiilmov-nav-loading');

                    loader.classList.remove('opacity-100', 'pointer-events-auto');
                    loader.classList.add('opacity-0', 'pointer-events-none');

                    // Completely halt GPU decoding frames when invisible
                    setTimeout(function() {
                        if (!isShowing && !document.documentElement.classList.contains('faiilmov-nav-loading')) {
                            loader.classList.add('invisible');
                        }
                    }, 250);
                }

                // Check when page is genuinely finished loading
                if (document.readyState === 'complete') {
                    finishTransition();
                } else {
                    window.addEventListener('load', finishTransition);
                    // Fallback in case external 3rd party scripts take too long
                    setTimeout(finishTransition, 2500);
                }

                // Back / Forward cache support
                window.addEventListener('pageshow', function(e) {
                    if (e.persisted) {
                        forceHide();
                    }
                });

                // User dismissals
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') forceHide();
                });
                loader.addEventListener('click', forceHide);

                // Smart click listener strictly for real HTML navigations
                document.addEventListener('click', function(e) {
                    // Ignore if other JS called preventDefault
                    if (e.defaultPrevented) return;

                    const link = e.target.closest('a');
                    if (!link) return;

                    if (link.getAttribute('role') === 'button' || link.hasAttribute('onclick')) return;
                    if (link.hasAttribute('data-no-transition') || link.closest('[data-no-transition]') || link.closest('[role="dialog"]')) return;

                    const href = link.getAttribute('href');
                    if (!href) return;

                    const cleanHref = href.trim();
                    if (cleanHref === '' || cleanHref === '#' || cleanHref.startsWith('#') || 
                        cleanHref.startsWith('javascript:') || cleanHref.startsWith('mailto:') || 
                        cleanHref.startsWith('tel:') || cleanHref.startsWith('blob:')) {
                        return;
                    }

                    if (link.target && link.target !== '_self') return;
                    if (link.hasAttribute('download')) return;

                    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;

                    try {
                        const targetUrl = new URL(link.href, window.location.origin);
                        if (targetUrl.origin !== window.location.origin) return;
                        if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search) {
                            return;
                        }

                        showTransition();
                    } catch (err) {}
                });

                window.addEventListener('show-page-transition', showTransition);
                window.addEventListener('hide-page-transition', forceHide);
            })();
        </script>
    @endif

    @stack('scripts')
</body>
</html>
