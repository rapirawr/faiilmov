@extends('layouts.app')

@section('title', 'faiilmov — Official MovieBox Web Client')

@section('content')
<div class="space-y-8 pb-16">

    <!-- Interactive Hero Carousel Banner -->
    @if(isset($heroFilms) && count($heroFilms) > 0)
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

        <!-- 1. Popular Series Row -->
        @if(isset($popularSeries) && count($popularSeries) > 0)
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
                            <div class="w-36 sm:w-44 shrink-0 group/card">
                                <a href="{{ route('film.show', $film->slug) }}" class="relative aspect-[2/3] block rounded-2xl overflow-hidden bg-dark-900 mb-2 border border-white/10 group-hover/card:border-white/30 transition-all duration-300 shadow-md">
                                    <img src="{{ $film->thumbnail_url }}" 
                                         alt="{{ $film->title }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="200"
                                         height="300"
                                         class="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500">
                                    
                                    <div class="absolute top-2 right-2 bg-dark-950/80 border border-white/10 px-2.5 py-1 text-[10px] font-bold text-amber-400 rounded-xl flex items-center gap-1 shadow-md">
                                        <i data-lucide="crown" class="w-3 h-3 text-amber-400"></i>
                                        <span>HD</span>
                                    </div>
                                    @if($film->max_resolution)
                                        <div class="absolute bottom-2 left-2 bg-dark-950/80 border border-white/10 px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-lg {{ $film->max_resolution === '4K' ? 'text-violet-300' : 'text-sky-300' }} tracking-wider shadow">
                                            {{ $film->max_resolution }}
                                        </div>
                                    @endif
                                </a>

                                <a href="{{ route('film.show', $film->slug) }}" class="font-semibold text-xs text-zinc-200 group-hover/card:text-white transition-colors truncate block w-full" title="{{ $film->title }}">
                                    {{ $film->title }}
                                </a>
                                <span class="text-[11px] text-zinc-500 block mt-0.5">{{ $film->release_year }}</span>
                            </div>
                        @endforeach
                    </div>

                    <button @click="scrollNext()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-dark-950/80 border border-white/10 text-white flex items-center justify-center hover:bg-white/20 transition-all opacity-0 group-hover:opacity-100 shadow-xl cursor-pointer">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </section>
        @endif

        <!-- 2. Trending Movies Row -->
        @if(isset($trendingMovies) && count($trendingMovies) > 0)
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
                            <div class="w-36 sm:w-44 shrink-0 group/card">
                                <a href="{{ route('film.show', $film->slug) }}" class="relative aspect-[2/3] block rounded-2xl overflow-hidden bg-dark-900 mb-2 border border-white/10 group-hover/card:border-white/30 transition-all duration-300 shadow-md">
                                    <img src="{{ $film->thumbnail_url }}" 
                                         alt="{{ $film->title }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="200"
                                         height="300"
                                         class="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500">
                                    
                                    <div class="absolute top-2 right-2 bg-dark-950/80 border border-white/10 px-2.5 py-1 text-[10px] font-bold text-zinc-200 rounded-xl flex items-center gap-1 shadow-md">
                                        @if($film->view_count > 0)
                                            <i data-lucide="eye" class="w-3 h-3 text-sky-400"></i>
                                            <span>{{ number_format($film->view_count) }}</span>
                                        @else
                                            <i data-lucide="star" class="w-3 h-3 fill-amber-400 text-amber-400"></i>
                                            <span>{{ number_format($film->rating, 1) }}</span>
                                        @endif
                                    </div>
                                    @if($film->max_resolution)
                                        <div class="absolute bottom-2 left-2 bg-dark-950/80 border border-white/10 px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-lg {{ $film->max_resolution === '4K' ? 'text-violet-300' : 'text-sky-300' }} tracking-wider shadow">
                                            {{ $film->max_resolution }}
                                        </div>
                                    @endif
                                </a>

                                <a href="{{ route('film.show', $film->slug) }}" class="font-semibold text-xs text-zinc-200 group-hover/card:text-white transition-colors truncate block w-full" title="{{ $film->title }}">
                                    {{ $film->title }}
                                </a>
                                <span class="text-[11px] text-zinc-500 block mt-0.5">{{ $film->release_year }}</span>
                            </div>
                        @endforeach
                    </div>

                    <button @click="scrollNext()" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-dark-950/80 border border-white/10 text-white flex items-center justify-center hover:bg-white/20 transition-all opacity-0 group-hover:opacity-100 shadow-xl cursor-pointer">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </section>
        @endif

        <!-- 3. Curved Bridge Filter Bar & Full Catalog Grid Section -->
        <section class="space-y-6 pt-4">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight">Full Film Catalog</h2>
                <span class="text-xs text-zinc-400 font-medium">Total: {{ $films->total() }}</span>
            </div>

            <!-- Curved Bridge Component Filter Form -->
            <form action="{{ route('home') }}" method="GET" class="bridge-container p-4 rounded-[2.5rem]">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 items-center">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Cari Film</label>
                        <div class="relative">
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Judul film..." 
                                   class="w-full bg-dark-950/70 text-xs text-white placeholder-zinc-500 pl-9 pr-3 py-2.5 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30 transition-colors">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
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
                            <a href="{{ route('home') }}" class="p-2.5 rounded-2xl glass-card text-zinc-400 hover:text-white border border-white/10" title="Reset Filter">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </a>
                        @endif
                    </div>

                </div>
            </form>

            <!-- Catalog Grid -->
            @if($films->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
                    @foreach($films as $film)
                        <x-film-card :film="$film" />
                    @endforeach
                </div>

                <div class="mt-8 flex justify-center">
                    {{ $films->links() }}
                </div>
            @endif
        </section>

    </div>

</div>
@endsection
