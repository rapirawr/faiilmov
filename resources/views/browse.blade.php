@extends('layouts.app')

@section('title', 'faiilmov — Katalog & Filter Film')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    
    <!-- Page Title -->
    <div class="border-b border-white/10 pb-4">
        <h1 class="font-serif font-bold text-3xl sm:text-5xl text-white tracking-tight">Katalog & Jelajah Film</h1>
        <p class="text-zinc-400 text-xs sm:text-sm mt-2">Cari dan filter koleksi database film lengkap berdasarkan kriteria Anda.</p>
    </div>

    <!-- Type Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar">
        <a href="{{ route('browse', request()->except('type')) }}" 
           class="px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 {{ !request('type') ? 'bg-white text-zinc-950 shadow-md' : 'bg-dark-900 text-zinc-400 hover:text-white border border-white/10' }}">
            Semua
        </a>
        <a href="{{ route('browse', array_merge(request()->except('type'), ['type' => 'series'])) }}" 
           class="px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 {{ request('type') === 'series' ? 'bg-purple-500 text-white shadow-md' : 'bg-dark-900 text-zinc-400 hover:text-white border border-white/10' }}">
            📺 Series
        </a>
        <a href="{{ route('browse', array_merge(request()->except('type'), ['type' => 'dracin'])) }}" 
           class="px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 {{ request('type') === 'dracin' ? 'bg-rose-500 text-white shadow-md' : 'bg-dark-900 text-zinc-400 hover:text-white border border-white/10' }}">
            🌸 Dracin
        </a>
        <a href="{{ route('browse', array_merge(request()->except('type'), ['type' => 'movie'])) }}" 
           class="px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 {{ request('type') === 'movie' ? 'bg-blue-500 text-white shadow-md' : 'bg-dark-900 text-zinc-400 hover:text-white border border-white/10' }}">
            🎬 Movie
        </a>
    </div>

    <!-- Curved Bridge Filter Bar Form -->
    <form action="{{ route('browse') }}" method="GET" class="bridge-container p-4 rounded-[2.5rem] shadow-xl">
        @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 items-center">
            
            <!-- Search Query -->
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Pencarian</label>
                <div class="flex items-center gap-2.5 px-3.5 rounded-2xl border border-white/10 bg-dark-950/70 backdrop-blur-md focus-within:border-white/30 transition-colors shadow-inner">
                    <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-400"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Judul film..." 
                           class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 py-2.5 border-none outline-none focus:outline-none focus:ring-0">
                </div>
            </div>

            <!-- Genre Select -->
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Genre</label>
                <select name="genre" class="w-full bg-dark-950/70 backdrop-blur-md text-xs text-white px-3 py-2.5 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30 truncate">
                    <option value="">Semua Genre</option>
                    @foreach($genres as $g)
                        <option value="{{ $g->slug }}" {{ request('genre') == $g->slug ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Min Rating Select -->
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Minimal Rating</label>
                <select name="min_rating" class="w-full bg-dark-950/70 backdrop-blur-md text-xs text-white px-3 py-2.5 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30 truncate">
                    <option value="">Semua Rating</option>
                    <option value="4.5" {{ request('min_rating') == '4.5' ? 'selected' : '' }}>⭐ 4.5 ke atas</option>
                    <option value="4.0" {{ request('min_rating') == '4.0' ? 'selected' : '' }}>⭐ 4.0 ke atas</option>
                    <option value="3.0" {{ request('min_rating') == '3.0' ? 'selected' : '' }}>⭐ 3.0 ke atas</option>
                </select>
            </div>

            <!-- Sorting Select -->
            <div>
                <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1 px-1">Urutkan</label>
                <select name="sort" class="w-full bg-dark-950/70 backdrop-blur-md text-xs text-white px-3 py-2.5 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30 truncate">
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Terbaru</option>
                    <option value="rating_desc" {{ request('sort') == 'rating_desc' ? 'selected' : '' }}>Rating Tertinggi</option>
                    <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>Judul (A-Z)</option>
                </select>
            </div>

            <!-- Submit Filter Button -->
            <div class="flex items-end gap-2 pt-2 sm:pt-0">
                <button type="submit" class="w-full py-2.5 bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs rounded-2xl transition-colors flex items-center justify-center gap-1.5 shadow-md cursor-pointer">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['q', 'genre', 'min_rating', 'sort']))
                    <a href="{{ route('browse') }}" class="p-2.5 glass-card text-zinc-400 hover:text-white rounded-2xl border border-white/10 transition-colors" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>

        </div>
    </form>

    <!-- AI Interpretation Badge (shown when AI successfully interpreted the query) -->
    @if(!empty($aiInterpretation) && !empty($searchQuery))
        <div class="glass-chip p-4 rounded-2xl border border-amber-500/30 bg-amber-500/5">
            <div class="flex items-start gap-3">
                <i data-lucide="sparkles" class="w-5 h-5 text-amber-400 shrink-0 mt-0.5"></i>
                <div class="flex-1 space-y-1">
                    <p class="text-xs font-bold text-amber-300">AI menerjemahkan pencarian Anda:</p>
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        @if(!empty($aiInterpretation['genres']))
                            @foreach($aiInterpretation['genres'] as $genre)
                                <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-200 border border-amber-500/30">{{ $genre }}</span>
                            @endforeach
                        @endif
                        @if(!empty($aiInterpretation['type']))
                            <span class="px-2.5 py-1 rounded-lg bg-sky-500/20 text-sky-200 border border-sky-500/30">{{ ucfirst($aiInterpretation['type']) }}</span>
                        @endif
                        @if(!empty($aiInterpretation['min_rating']))
                            <span class="px-2.5 py-1 rounded-lg bg-green-500/20 text-green-200 border border-green-500/30">Rating ≥ {{ $aiInterpretation['min_rating'] }}</span>
                        @endif
                        @if(!empty($aiInterpretation['mood_keywords']))
                            @foreach($aiInterpretation['mood_keywords'] as $keyword)
                                <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-200 border border-amber-500/30">{{ $keyword }}</span>
                            @endforeach
                        @endif
                        @if(!empty($aiInterpretation['similar_to_title']))
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-200 border border-emerald-500/30">Mirip: {{ $aiInterpretation['similar_to_title'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Gray Glass Catalog Film Grid -->
    @if($films->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            @foreach($films as $film)
                <x-film-card :film="$film" />
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12 flex justify-center">
            {{ $films->links() }}
        </div>
    @else
        <div class="glass-panel p-12 rounded-3xl text-center border border-white/10 max-w-3xl mx-auto my-6">
            <i data-lucide="search-x" class="w-12 h-12 text-zinc-500 mx-auto mb-3"></i>
            <h3 class="font-serif font-bold text-lg text-white mb-1">
                @if(!empty($searchQuery))
                    Tidak ada film ditemukan untuk "<span class="text-amber-400">{{ $searchQuery }}</span>"
                @else
                    Tidak ada film ditemukan
                @endif
            </h3>
            <p class="text-xs text-zinc-400 max-w-md mx-auto mb-6">Pastikan ejaan kata kunci sudah benar atau coba gunakan kata kunci yang lebih umum.</p>
            
            @if(isset($suggestedFilms) && $suggestedFilms->count() > 0)
                <div class="border-t border-white/10 pt-6 text-left">
                    <h4 class="text-xs font-bold text-zinc-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
                        <span>Rekomendasi Film Populer</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                        @foreach($suggestedFilms as $sFilm)
                            <a href="{{ route('film.show', $sFilm->slug) }}" class="group block">
                                <div class="aspect-[2/3] rounded-xl overflow-hidden bg-dark-900 mb-2 border border-white/10 group-hover:border-white/30 transition-all">
                                    <img src="{{ $sFilm->thumbnail_url }}" alt="{{ $sFilm->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                                <span class="text-xs font-semibold text-zinc-300 group-hover:text-white line-clamp-1 block">{{ $sFilm->title }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Separate Section: AI Recommendations based on Mood / Genre -->
    @if(isset($aiRecommendations) && $aiRecommendations->count() > 0)
        <div class="mt-14 pt-8 border-t border-white/10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-serif text-lg font-bold text-white flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-5 h-5 text-amber-400"></i>
                        <span>Rekomendasi AI (Serupa Berdasarkan Mood & Genre)</span>
                    </h3>
                    <p class="text-xs text-zinc-400 mt-1">Daftar judul berikut dipilih oleh AI berdasarkan nuansa & genre dari kata kunci "{{ $searchQuery }}"</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                @foreach($aiRecommendations as $aiFilm)
                    <x-film-card :film="$aiFilm" />
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
