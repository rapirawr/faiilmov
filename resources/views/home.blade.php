@extends('layouts.app')

@section('title', 'faiilmov')

@section('content')
<div class="space-y-8 pb-16">

    <!-- Interactive Hero Carousel Banner -->
    @if(isset($heroFilms) && is_countable($heroFilms) && count($heroFilms) > 0)
        @php
            $heroData = $heroFilms->map(function($f) {
                return [
                    'id' => $f->id,
                    'title' => $f->title,
                    'slug' => $f->slug,
                    'backdrop_url' => $f->backdrop_url ?: $f->poster_url,
                    'backdrop' => $f->backdrop_url ?: $f->poster_url,
                    'poster_url' => $f->poster_url,
                    'rating' => $f->rating,
                    'release_year' => $f->release_year,
                    'subject_type' => $f->subject_type,
                    'synopsis' => $f->synopsis,
                ];
            });
            $heroJson = json_encode($heroData);
        @endphp

        <div id="react-hero-banner" data-films='@json($heroData)'>
            <section x-data="{ 
                         slides: {{ $heroJson }}, 
                         activeIndex: 0, 
                         timer: null,
                         next() { this.activeIndex = (this.activeIndex + 1) % this.slides.length },
                         prev() { this.activeIndex = (this.activeIndex - 1 + this.slides.length) % this.slides.length }
                     }" 
                     x-init="timer = setInterval(() => next(), 6000)"
                     @mouseenter="clearInterval(timer)"
                     @mouseleave="timer = setInterval(() => next(), 6000)"
                     class="relative w-full h-[380px] sm:h-[460px] lg:h-[500px] overflow-hidden bg-dark-950 border-b border-white/10 group mb-8 rounded-3xl">
            
            <!-- Slides Background Backdrop -->
            <template x-for="(slide, index) in slides" :key="slide.id">
                <div x-show="activeIndex === index" 
                     x-transition:enter="transition ease-out duration-700 opacity-0 scale-105"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-500 opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 z-0">
                    <img :src="slide.backdrop || slide.backdrop_url" :alt="slide.title" class="w-full h-full object-cover object-center filter brightness-90">
                    <div class="absolute inset-0 bg-dark-950/20"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-dark-950 via-dark-950/30 to-transparent"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-dark-950/60 via-dark-950/20 to-transparent"></div>
                </div>
            </template>

            <!-- Navigation Controls: Left Arrow (<) and Right Arrow (>) -->
            <button @click="prev()" 
                    class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full glass-chip text-white flex items-center justify-center hover:bg-white/20 transition-all opacity-0 group-hover:opacity-100">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </button>

            <button @click="next()" 
                    class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full glass-chip text-white flex items-center justify-center hover:bg-white/20 transition-all opacity-0 group-hover:opacity-100">
                <i data-lucide="chevron-right" class="w-6 h-6"></i>
            </button>

            <!-- Bottom Overlay Meta Info & Controls -->
            <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-10 h-full flex flex-col justify-end pb-10">
                <template x-for="(slide, index) in slides" :key="'info-' + slide.id">
                    <div x-show="activeIndex === index" 
                         x-transition:enter="transition ease-out duration-500 opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="max-w-2xl space-y-3 glass-panel p-6 sm:p-8 rounded-3xl border border-white/15 backdrop-blur-xl">
                        
                        <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight drop-shadow-md" x-text="slide.title"></h1>

                        <div class="flex items-center gap-3 text-xs text-zinc-300 flex-wrap">
                            <span class="flex items-center gap-1.5 glass-chip text-amber-400 font-bold px-2.5 py-1 rounded-xl">
                                <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400"></i>
                                <span x-text="slide.rating"></span>
                            </span>
                            <span class="flex items-center gap-1 text-zinc-300">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-zinc-400"></i>
                                <span x-text="slide.year"></span>
                            </span>
                            <span class="w-1 h-1 rounded-full bg-zinc-500"></span>
                            <span class="text-zinc-400 font-medium" x-text="slide.genres"></span>
                        </div>

                        <div class="pt-2 flex items-center gap-3">
                            <a :href="'/film/' + slide.slug" 
                               class="px-6 py-2.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-2 shadow-lg">
                                <i data-lucide="play" class="w-4 h-4 fill-zinc-950"></i>
                                <span>Watch Now</span>
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Bottom Right Slide Dots Indicator -->
                <div class="absolute right-6 bottom-8 z-20 flex items-center gap-1.5">
                    <template x-for="(slide, index) in slides" :key="'dot-' + index">
                        <button @click="activeIndex = index" 
                                :class="activeIndex === index ? 'bg-white w-6' : 'bg-white/30 hover:bg-white/60 w-2'" 
                                class="h-2 rounded-full transition-all duration-300 cursor-pointer"></button>
                    </template>
                </div>

            </div>

        </section>
    </div>
@endif

    <!-- Content Sections Container -->
    <div class="px-4 sm:px-8 space-y-10">



        <!-- CONTINUE WATCHING Section -->
        @if(isset($continueWatching) && is_countable($continueWatching) && count($continueWatching) > 0)
            <section x-data="{
                        scrollNext() { $refs.watchContainer.scrollBy({ left: 300, behavior: 'smooth' }) }
                     }" 
                     class="space-y-4">
                
                <div class="flex items-center justify-between">
                    <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight flex items-center gap-2">
                        <span>Continue Watching</span>
                    </h2>
                    <a href="{{ route('profile') }}" class="text-xs font-semibold text-zinc-400 hover:text-white transition-colors flex items-center gap-1">
                        <span>More</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <div class="relative group">
                    <div x-ref="watchContainer" class="flex items-center gap-4 overflow-x-auto no-scrollbar scroll-smooth py-2 will-change-transform transform-gpu">
                        @foreach($continueWatching as $history)
                            @if($history && $history->film)
                                @php
                                    $film = $history->film;
                                    $durMin = $film->subject_type === 'series' ? 45 : ($film->duration_minutes ?: 120);
                                    $totalSec = max(1, $durMin * 60);
                                    $progPercent = min(100, max(5, round(($history->progress_seconds / $totalSec) * 100)));
                                    $watchUrl = route('film.watch', $film->slug) . ($film->subject_type === 'series' ? "?season={$history->season_number}&episode={$history->episode_number}" : '');
                                    
                                    $age = $film->content_rating ? strtoupper($film->content_rating) : '13+';
                                    if (in_array($age, ['R', 'NC-17'])) $age = '18+';
                                    if (in_array($age, ['PG', 'G'])) $age = 'SU';
                                    $ageBadgeClass = match($age) {
                                        '18+' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
                                        '16+' => 'bg-orange-500/20 text-orange-300 border-orange-500/40',
                                        '13+' => 'bg-sky-500/20 text-sky-300 border-sky-500/40',
                                        'SU'  => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                                        default => 'bg-zinc-800 text-zinc-300 border-white/20',
                                    };
                                @endphp

                                <div class="w-64 sm:w-72 shrink-0 group/cw">
                                    <a href="{{ $watchUrl }}" class="relative aspect-[16/9] block rounded-2xl overflow-hidden bg-zinc-900 mb-2 border border-white/10 group-hover/cw:border-amber-400/40 transition-all duration-300 shadow-xl group-hover/cw:shadow-amber-500/10">
                                        <img src="{{ $film->backdrop_url ?: $film->thumbnail_url ?: $film->poster_url }}" alt="{{ $film->title }}" class="w-full h-full object-cover group-hover/cw:scale-105 transition-transform duration-400">
                                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/30 to-transparent opacity-80 group-hover/cw:opacity-60 transition-opacity"></div>
                                        
                                        <!-- Play Button Overlay -->
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/cw:opacity-100 transition-opacity bg-black/40 backdrop-blur-[2px]">
                                            <div class="px-4 py-2 rounded-full bg-amber-500 text-zinc-950 shadow-xl scale-95 group-hover/cw:scale-110 transition-transform flex items-center gap-1.5 font-extrabold text-xs">
                                                <i data-lucide="play" class="w-4 h-4 fill-zinc-950 ml-0.5"></i>
                                                <span>Lanjut Nonton</span>
                                            </div>
                                        </div>

                                        <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 z-10">
                                            <span class="px-1.5 py-0.5 rounded-md border text-[9px] font-extrabold uppercase tracking-wider backdrop-blur-md shadow {{ $ageBadgeClass }}">
                                                {{ $age }}
                                            </span>
                                            @if($film->subject_type === 'series')
                                                <span class="px-2 py-0.5 rounded-md bg-amber-500 text-zinc-950 text-[9.5px] font-extrabold uppercase shadow">
                                                    S{{ $history->season_number }} E{{ $history->episode_number }}
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Progress Bar (Wide Netflix Style) -->
                                        <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-white/20 overflow-hidden">
                                            <div class="h-full bg-amber-400 rounded-r-full shadow-lg" style="width: {{ $progPercent }}%"></div>
                                        </div>
                                    </a>

                                    <div class="px-0.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <a href="{{ $watchUrl }}" class="font-serif font-bold text-xs sm:text-sm text-white group-hover/cw:text-amber-300 transition-colors truncate block" title="{{ $film->title }}">
                                                {{ $film->title }}
                                            </a>
                                            <span class="text-amber-400 font-extrabold text-[11px] shrink-0">{{ $progPercent }}%</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <button @click="scrollNext()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-dark-950/80 border border-amber-500/20 text-amber-400 flex items-center justify-center hover:bg-amber-500/20 hover:border-amber-500/40 hover:text-amber-300 transition-all opacity-0 group-hover:opacity-100 shadow-xl cursor-pointer">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </section>
        @endif

        <!-- BECAUSE YOU WATCHED Section (Personalized) -->
        @if(isset($becauseYouWatched) && !empty($becauseYouWatched['source_film']) && $becauseYouWatched['recommendations']->count() > 0)
            <section x-data="{
                        scrollNext() { $refs.becauseContainer.scrollBy({ left: 300, behavior: 'smooth' }) }
                     }" 
                     class="space-y-4">
                
                <div class="flex items-center justify-between">
                    <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight flex items-center gap-2">
                        <span>Karena Anda Menonton: <span class="text-amber-400">{{ Str::limit($becauseYouWatched['source_film']->title, 25) }}</span></span>
                    </h2>
                </div>

                <div class="relative group">
                    <div x-ref="becauseContainer" class="flex items-center gap-4 overflow-x-auto no-scrollbar scroll-smooth py-2 will-change-transform transform-gpu">
                        @foreach($becauseYouWatched['recommendations'] as $film)
                            @if($film)
                                <div class="w-36 sm:w-44 shrink-0">
                                    <x-film-card :film="$film" />
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- COMING SOON Section -->
        @if(isset($comingSoon) && is_countable($comingSoon) && count($comingSoon) > 0)
            <section x-data="{
                        scrollNext() { $refs.comingContainer.scrollBy({ left: 300, behavior: 'smooth' }) }
                     }" 
                     class="space-y-4">
                
                <div class="flex items-center justify-between">
                    <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight flex items-center gap-2">
                        <i data-lucide="calendar-clock" class="w-5 h-5 text-amber-400"></i>
                        <span>Coming Soon</span>
                    </h2>
                </div>

                <div class="relative group">
                    <div x-ref="comingContainer" class="flex items-center gap-4 overflow-x-auto no-scrollbar scroll-smooth py-2 will-change-transform transform-gpu">
                        @foreach($comingSoon as $film)
                            @if($film)
                                <div class="w-36 sm:w-44 shrink-0">
                                    <x-film-card :film="$film" />
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if(isset($featureBanners) && $featureBanners->isNotEmpty())
            <div id="react-feature-banner" data-banners="{{ json_encode($featureBanners->values()) }}"></div>
        @endif

        <!-- Adsterra Native Grid Banner Slot -->
        <x-ad-banner placement="grid" class="max-w-5xl mx-auto" />

        <!-- 1. Popular Series Row -->
        @if(isset($popularSeries) && is_countable($popularSeries) && count($popularSeries) > 0)
            <section x-data="{
                        scrollNext() { $refs.seriesContainer.scrollBy({ left: 300, behavior: 'smooth' }) },
                        scrollPrev() { $refs.seriesContainer.scrollBy({ left: -300, behavior: 'smooth' }) }
                     }" 
                     class="space-y-4">
                
                <div class="flex items-center justify-between">
                    <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight flex items-center gap-2">
                        <span>Popular Series</span>
                    </h2>
                    <a href="{{ route('browse', ['type' => 'series']) }}" class="text-xs font-semibold text-zinc-400 hover:text-white transition-colors flex items-center gap-1">
                        <span>More</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <div class="relative group">
                    <div x-ref="seriesContainer" class="flex items-center gap-4 overflow-x-auto no-scrollbar scroll-smooth py-2 will-change-transform transform-gpu">
                        @foreach($popularSeries as $film)
                            @if($film)
                                <div class="w-36 sm:w-44 shrink-0">
                                    <x-film-card :film="$film" />
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <button @click="scrollNext()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-dark-950/80 border border-white/10 text-white flex items-center justify-center hover:bg-white/20 transition-all opacity-0 group-hover:opacity-100 shadow-xl cursor-pointer">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </section>
        @endif

        <!-- Popular Dracin Row -->
        @if(isset($popularDracin) && is_countable($popularDracin) && count($popularDracin) > 0)
            <section x-data="{
                        scrollNext() { $refs.dracinContainer.scrollBy({ left: 300, behavior: 'smooth' }) },
                        scrollPrev() { $refs.dracinContainer.scrollBy({ left: -300, behavior: 'smooth' }) }
                     }" 
                     class="space-y-4">
                
                <div class="flex items-center justify-between">
                    <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight flex items-center gap-2">
                        <i data-lucide="tv-2" class="w-5 h-5 text-rose-400"></i>
                        <span>Drama China Terpopuler</span>
                    </h2>
                    <a href="{{ route('browse', ['type' => 'dracin']) }}" class="text-xs font-semibold text-rose-400 hover:text-rose-300 transition-colors flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <div class="relative group">
                    <div x-ref="dracinContainer" class="flex items-center gap-4 overflow-x-auto no-scrollbar scroll-smooth py-2 will-change-transform transform-gpu">
                        @foreach($popularDracin as $film)
                            @if($film)
                                <div class="w-36 sm:w-44 shrink-0">
                                    <x-film-card :film="$film" />
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <button @click="scrollNext()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-dark-950/80 border border-white/10 text-white flex items-center justify-center hover:bg-white/20 transition-all opacity-0 group-hover:opacity-100 shadow-xl cursor-pointer">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </section>
        @endif

        <!-- 2. Trending Movies Row -->
        @if(isset($trendingMovies) && is_countable($trendingMovies) && count($trendingMovies) > 0)
            <section x-data="{
                        scrollNext() { $refs.moviesContainer.scrollBy({ left: 300, behavior: 'smooth' }) }
                     }" 
                     class="space-y-4">
                
                <div class="flex items-center justify-between">
                    <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight">Trending & Paling Banyak Dilihat</h2>
                    <a href="{{ route('browse', ['type' => 'movie', 'sort' => 'rating_desc']) }}" class="text-xs font-semibold text-zinc-400 hover:text-white transition-colors flex items-center gap-1">
                        <span>More</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <div class="relative group">
                    <div x-ref="moviesContainer" class="flex items-center gap-4 overflow-x-auto no-scrollbar scroll-smooth py-2 will-change-transform transform-gpu">
                        @foreach($trendingMovies as $film)
                            @if($film)
                                <div class="w-36 sm:w-44 shrink-0">
                                    <x-film-card :film="$film" />
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <button @click="scrollNext()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-dark-950/80 border border-white/10 text-white flex items-center justify-center hover:bg-white/20 transition-all opacity-0 group-hover:opacity-100 shadow-xl cursor-pointer">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </section>
        @endif



        <!-- 3. Curved Bridge Filter Bar & Full Catalog Grid Section -->
        <section id="catalog-section" x-data="catalogAjax()" class="space-y-6 pt-4 relative">

            <!-- Loading Overlay -->
            <div x-show="loading" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-dark-950/70 backdrop-blur-sm z-30 rounded-3xl flex items-center justify-center min-h-[300px]"
                 style="display: none;">
                <div class="flex items-center gap-3 bg-dark-900 border border-white/10 px-5 py-3 rounded-2xl shadow-2xl">
                    <div class="w-5 h-5 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-semibold text-zinc-200">Memuat Katalog...</span>
                </div>
            </div>

            <!-- Curved Bridge Component Filter Form -->
            <form @submit.prevent="submitFilter($event)" action="{{ route('home') }}" method="GET" class="bridge-container p-4 rounded-[2.5rem]">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 items-center">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Cari Film</label>
                        <div class="flex items-center gap-2.5 px-3.5 rounded-2xl border border-white/10 bg-dark-950/70 focus-within:border-white/30 transition-colors shadow-inner">
                            <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-400"></i>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Judul film..." 
                                   class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 py-2.5 border-none outline-none focus:outline-none focus:ring-0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Genre</label>
                        <select name="genre" class="w-full bg-dark-950/70 text-xs text-white px-3 py-2.5 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30 transition-colors truncate">
                            <option value="">Semua Genre</option>
                            @foreach($genres as $g)
                                <option value="{{ $g->slug }}" {{ request('genre') == $g->slug ? 'selected' : '' }}>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Tipe</label>
                        <select name="type" class="w-full bg-dark-950/70 text-xs text-white px-3 py-2.5 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30 transition-colors truncate">
                            <option value="">Semua Tipe</option>
                            <option value="movie" {{ request('type') == 'movie' ? 'selected' : '' }}>Movie</option>
                            <option value="series" {{ request('type') == 'series' ? 'selected' : '' }}>TV Series</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Urutkan</label>
                        <select name="sort" class="w-full bg-dark-950/70 text-xs text-white px-3 py-2.5 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30 transition-colors truncate">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Terbaru</option>
                            <option value="rating_desc" {{ request('sort') == 'rating_desc' ? 'selected' : '' }}>Rating Tertinggi</option>
                            <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>Judul A-Z</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 pt-2 sm:pt-0">
                        <button type="submit" class="w-full py-2.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors flex items-center justify-center gap-1.5 shadow-md cursor-pointer">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>Filter</span>
                        </button>
                        @if(request()->hasAny(['q', 'genre', 'type', 'sort']))
                            <a href="{{ route('home') }}" @click.prevent="fetchCatalog('{{ route('home') }}')" class="p-2.5 rounded-2xl glass-card text-zinc-400 hover:text-white border border-white/10" title="Reset Filter">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </a>
                        @endif
                    </div>

                </div>
            </form>

            <!-- Catalog Content Container (Grid + Pagination) -->
            <div id="catalog-container" @click="handlePaginationClick($event)">
                @include('partials.catalog-grid')
            </div>

        </section>

    </div>

</div>

@push('scripts')
<script>
function catalogAjax() {
    return {
        loading: false,

        async fetchCatalog(url) {
            this.loading = true;
            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const html = await response.text();
                    const container = document.getElementById('catalog-container');
                    if (container) {
                        container.innerHTML = html;
                        window.history.pushState(null, '', url);
                        
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }

                        const section = document.getElementById('catalog-section');
                        if (section) {
                            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                }
            } catch (error) {
                console.error('Error fetching catalog:', error);
            } finally {
                this.loading = false;
            }
        },

        handlePaginationClick(e) {
            const link = e.target.closest('a');
            if (link && link.href) {
                const isPaginationLink = link.closest('nav') || 
                                         link.getAttribute('rel') === 'prev' || 
                                         link.getAttribute('rel') === 'next' ||
                                         link.href.includes('page=');
                if (isPaginationLink) {
                    e.preventDefault();
                    this.fetchCatalog(link.href);
                }
            }
        },

        submitFilter(e) {
            const form = e.target;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            for (const [key, value] of Array.from(params.entries())) {
                if (!value.trim()) {
                    params.delete(key);
                }
            }

            const url = `${form.action}?${params.toString()}`;
            this.fetchCatalog(url);
        }
    };
}

window.addEventListener('popstate', () => {
    const catalogSection = document.getElementById('catalog-section');
    if (catalogSection && window.Alpine) {
        const alpineData = Alpine.$data(catalogSection);
        if (alpineData && alpineData.fetchCatalog) {
            alpineData.fetchCatalog(window.location.href);
        }
    }
});
</script>
@endpush
@endsection
