@extends('layouts.admin')

@section('title', 'CMS Rating Usia (Age Rate) | faiiladmin')
@section('page_title', 'CMS Manajemen Rating Usia (Age Rate)')

@section('content')
<div class="space-y-6" x-data="ageRatingCms()">

    <!-- ==================== HEADER SECTION ==================== -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-zinc-900/80 border border-zinc-800 p-5 rounded-2xl backdrop-blur-xl">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-white font-chillax tracking-tight">CMS Rating Usia (Age Classification)</h1>
                    <p class="text-xs text-zinc-400">Pusat kontrol klasifikasi batas usia penonton (Parental Control & Sensor) untuk seluruh katalog konten.</p>
                </div>
            </div>
        </div>

        <!-- Global Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Modal Style Customizer Trigger -->
            <button type="button" @click="openStyleModal()" 
                    class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-500/15 via-purple-500/15 to-sky-500/15 hover:from-amber-500/25 hover:via-purple-500/25 hover:to-sky-500/25 text-amber-300 border border-amber-500/40 text-xs font-bold flex items-center gap-2 transition-all shadow-lg shadow-amber-500/5 cursor-pointer">
                <i data-lucide="palette" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>Kustomisasi Style Rating</span>
            </button>

            <!-- Modal Legend Trigger -->
            <button type="button" @click="showLegendModal = true" 
                    class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-zinc-700 text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer">
                <i data-lucide="info" class="w-3.5 h-3.5 text-zinc-400"></i>
                <span>Standar Sensor</span>
            </button>

            <!-- Auto-Rate All Unrated -->
            <form action="{{ route('admin.films.auto_rate_all') }}" method="POST" onsubmit="return confirm('Deteksi dan tetapkan rating usia secara otomatis untuk {{ $stats['unrated'] }} film yang belum dikategorikan?')">
                @csrf
                <input type="hidden" name="only_unrated" value="1">
                <button type="submit" 
                        class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="wand-2" class="w-3.5 h-3.5"></i>
                    <span>Auto-Rate Unrated ({{ $stats['unrated'] }})</span>
                </button>
            </form>

            <!-- Auto-Rate Overwrite All (Full DB) -->
            <form action="{{ route('admin.films.auto_rate_all') }}" method="POST" onsubmit="return confirm('PERINGATAN: Ini akan mendeteksi ulang dan memperbarui rating usia untuk SELURUH film di basis data. Lanjutkan?')">
                @csrf
                <input type="hidden" name="only_unrated" value="0">
                <button type="submit" 
                        class="px-3.5 py-2 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/30 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Auto-Rate ALL</span>
                </button>
            </form>


        </div>
    </div>

    <!-- ==================== KPI STATS & DISTRIBUTION ==================== -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        
        <!-- Total Films & Progress -->
        <div class="col-span-2 sm:col-span-3 lg:col-span-2 p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-400">Kelengkapan Rating Usia</span>
                <span class="text-xs font-mono font-bold text-amber-400">{{ $stats['rated_percentage'] }}%</span>
            </div>
            <div class="my-2">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-white font-['Outfit'] tracking-tight">{{ number_format($stats['rated']) }}</span>
                    <span class="text-xs text-zinc-500 font-mono">/ {{ number_format($stats['total']) }} Film</span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full h-2 bg-zinc-800 rounded-full mt-2 overflow-hidden flex">
                    <div class="bg-emerald-500 transition-all duration-500" style="width: {{ $stats['total'] > 0 ? ($stats['su'] / $stats['total']) * 100 : 0 }}%" title="SU (Semua Umur)"></div>
                    <div class="bg-sky-500 transition-all duration-500" style="width: {{ $stats['total'] > 0 ? ($stats['r13'] / $stats['total']) * 100 : 0 }}%" title="13+ (Remaja)"></div>
                    <div class="bg-amber-500 transition-all duration-500" style="width: {{ $stats['total'] > 0 ? ($stats['r16'] / $stats['total']) * 100 : 0 }}%" title="16+ (Dewasa Muda)"></div>
                    <div class="bg-rose-500 transition-all duration-500" style="width: {{ $stats['total'] > 0 ? ($stats['r18'] / $stats['total']) * 100 : 0 }}%" title="18+ (Dewasa)"></div>
                </div>
            </div>
            <p class="text-[11px] text-zinc-400">
                @if($stats['unrated'] > 0)
                    <strong class="text-amber-400 font-semibold">{{ $stats['unrated'] }} film</strong> belum memiliki klasifikasi usia.
                @else
                    <strong class="text-emerald-400 font-semibold">100% film</strong> telah memiliki klasifikasi usia.
                @endif
            </p>
        </div>

        <!-- SU (Semua Umur) -->
        <a href="{{ route('admin.films.content_rating', ['filter' => 'SU']) }}" 
           class="p-4 rounded-2xl border transition-all flex flex-col justify-between group {{ request('filter') === 'SU' ? 'bg-emerald-500/10 border-emerald-500/50 ring-1 ring-emerald-500/30' : 'bg-zinc-900/60 border-zinc-800 hover:border-emerald-500/40' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 font-mono">SU / G</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <div class="my-1">
                <span class="text-2xl font-black text-white font-['Outfit'] tracking-tight group-hover:text-emerald-300 transition-colors">{{ number_format($stats['su']) }}</span>
            </div>
            <span class="text-[10px] text-zinc-400">Semua Umur / Anak</span>
        </a>

        <!-- 13+ (Remaja) -->
        <a href="{{ route('admin.films.content_rating', ['filter' => '13+']) }}" 
           class="p-4 rounded-2xl border transition-all flex flex-col justify-between group {{ request('filter') === '13+' ? 'bg-sky-500/10 border-sky-500/50 ring-1 ring-sky-500/30' : 'bg-zinc-900/60 border-zinc-800 hover:border-sky-500/40' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-sky-400 font-mono">13+ / PG-13</span>
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
            </div>
            <div class="my-1">
                <span class="text-2xl font-black text-white font-['Outfit'] tracking-tight group-hover:text-sky-300 transition-colors">{{ number_format($stats['r13']) }}</span>
            </div>
            <span class="text-[10px] text-zinc-400">Remaja 13 Tahun+</span>
        </a>

        <!-- 16+ (Dewasa Muda) -->
        <a href="{{ route('admin.films.content_rating', ['filter' => '16+']) }}" 
           class="p-4 rounded-2xl border transition-all flex flex-col justify-between group {{ request('filter') === '16+' ? 'bg-amber-500/10 border-amber-500/50 ring-1 ring-amber-500/30' : 'bg-zinc-900/60 border-zinc-800 hover:border-amber-500/40' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 font-mono">16+ / 17+</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
            <div class="my-1">
                <span class="text-2xl font-black text-white font-['Outfit'] tracking-tight group-hover:text-amber-300 transition-colors">{{ number_format($stats['r16']) }}</span>
            </div>
            <span class="text-[10px] text-zinc-400">Dewasa Muda 16+</span>
        </a>

        <!-- 18+ (Dewasa) -->
        <a href="{{ route('admin.films.content_rating', ['filter' => '18+']) }}" 
           class="p-4 rounded-2xl border transition-all flex flex-col justify-between group {{ request('filter') === '18+' ? 'bg-rose-500/10 border-rose-500/50 ring-1 ring-rose-500/30' : 'bg-zinc-900/60 border-zinc-800 hover:border-rose-500/40' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-400 font-mono">18+ / 21+</span>
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            </div>
            <div class="my-1">
                <span class="text-2xl font-black text-white font-['Outfit'] tracking-tight group-hover:text-rose-300 transition-colors">{{ number_format($stats['r18']) }}</span>
            </div>
            <span class="text-[10px] text-zinc-400">Dewasa / Eksplisit</span>
        </a>

    </div>

    <!-- ==================== SEARCH & FILTERS BAR ==================== -->
    <div class="p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-3">
        <form method="GET" action="{{ route('admin.films.content_rating') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- Search Text -->
            <div class="lg:col-span-4 relative flex items-center">
                <i data-lucide="search" class="w-4 h-4 text-zinc-400 absolute left-3.5 pointer-events-none"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari judul film, sinopsis, tahun..." 
                       class="w-full bg-zinc-950 border border-zinc-800 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition-colors">
            </div>

            <!-- Filter Rating Usia -->
            <div class="lg:col-span-2">
                <select name="filter" 
                        onchange="this.form.submit()"
                        class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition-colors cursor-pointer">
                    <option value="all" {{ request('filter') === 'all' || !request('filter') ? 'selected' : '' }}>Semua Klasifikasi</option>
                    <option value="unrated" {{ request('filter') === 'unrated' ? 'selected' : '' }}>⚠️ Hanya Unrated ({{ $stats['unrated'] }})</option>
                    <option value="rated" {{ request('filter') === 'rated' ? 'selected' : '' }}>✓ Sudah Terklasifikasi</option>
                    <option value="SU" {{ request('filter') === 'SU' ? 'selected' : '' }}>🟢 SU (Semua Umur)</option>
                    <option value="13+" {{ request('filter') === '13+' ? 'selected' : '' }}>🔵 13+ (Remaja)</option>
                    <option value="16+" {{ request('filter') === '16+' ? 'selected' : '' }}>🟡 16+ (Dewasa Muda)</option>
                    <option value="18+" {{ request('filter') === '18+' ? 'selected' : '' }}>🔴 18+ (Dewasa)</option>
                </select>
            </div>

            <!-- Filter Tipe Film -->
            <div class="lg:col-span-2">
                <select name="type" 
                        onchange="this.form.submit()"
                        class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition-colors cursor-pointer">
                    <option value="all" {{ request('type') === 'all' || !request('type') ? 'selected' : '' }}>Semua Format</option>
                    <option value="movie" {{ request('type') === 'movie' ? 'selected' : '' }}>🎬 Movie (Film)</option>
                    <option value="series" {{ request('type') === 'series' ? 'selected' : '' }}>📺 TV Series</option>
                    <option value="dracin" {{ request('type') === 'dracin' ? 'selected' : '' }}>🐉 Drama China (Dracin)</option>
                </select>
            </div>

            <!-- Filter Genre -->
            <div class="lg:col-span-2">
                <select name="genre_id" 
                        onchange="this.form.submit()"
                        class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition-colors cursor-pointer">
                    <option value="all">Semua Genre</option>
                    @foreach($genres as $genre)
                        <option value="{{ $genre->id }}" {{ request('genre_id') == $genre->id ? 'selected' : '' }}>
                            {{ $genre->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sorting -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <select name="sort" 
                        onchange="this.form.submit()"
                        class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 transition-colors cursor-pointer">
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Terbaru Ditambah</option>
                    <option value="unrated_first" {{ request('sort') === 'unrated_first' ? 'selected' : '' }}>Unrated Teratas</option>
                    <option value="title_asc" {{ request('sort') === 'title_asc' ? 'selected' : '' }}>Judul (A-Z)</option>
                    <option value="year_desc" {{ request('sort') === 'year_desc' ? 'selected' : '' }}>Tahun Rilis (Baru)</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Terlama</option>
                </select>

                @if(request()->anyFilled(['search', 'filter', 'type', 'genre_id', 'sort']))
                    <a href="{{ route('admin.films.content_rating') }}" 
                       class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors shrink-0"
                       title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- ==================== MAIN DATA TABLE ==================== -->
    <div class="bg-zinc-900/60 border border-zinc-800 rounded-2xl overflow-hidden shadow-2xl">
        
        <!-- Table Header Bar -->
        <div class="p-4 bg-zinc-900/90 border-b border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-xs font-semibold text-zinc-300 cursor-pointer select-none">
                    <input type="checkbox" 
                           @change="toggleSelectAll($event.target.checked)" 
                           :checked="isAllSelected()"
                           class="w-4 h-4 rounded bg-zinc-950 border-zinc-700 text-amber-500 focus:ring-0 cursor-pointer">
                    <span>Pilih Semua (<span x-text="selectedFilmIds.length"></span>/{{ $films->count() }})</span>
                </label>

                <span class="text-xs text-zinc-500">&bull;</span>
                <span class="text-xs text-zinc-400">Menampilkan {{ $films->count() }} film</span>
            </div>

            <!-- Page Auto Detect -->
            <button type="button" 
                    @click="autoDetectCurrentPage()" 
                    :disabled="isDetectingAll"
                    class="px-3.5 py-1.5 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/40 font-bold text-xs transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50 self-start sm:self-auto">
                <i data-lucide="wand-2" class="w-3.5 h-3.5 text-purple-400" :class="isDetectingAll ? 'animate-spin' : ''"></i>
                <span x-text="isDetectingAll ? 'Mendeteksi Seluruh Halaman...' : 'Auto-Detect Halaman Ini'"></span>
            </button>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-950/80 text-zinc-400 uppercase text-[10px] font-bold font-mono tracking-wider border-b border-zinc-800">
                    <tr>
                        <th class="px-3 py-3 w-10 text-center">#</th>
                        <th class="px-3 py-3">Film & Informasi</th>
                        <th class="px-3 py-3 w-32">Format & Genre</th>
                        <th class="px-3 py-3 text-center w-36">Status Rating Usia</th>
                        <th class="px-3 py-3 text-center w-52">Pilih Rating Cepat</th>
                        <th class="px-3 py-3 text-right w-28">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/80">
                    @forelse($films as $film)
                        <tr class="hover:bg-zinc-800/30 transition-colors group" 
                            id="film-row-{{ $film->id }}"
                            x-data="{ 
                                currentRating: '{{ $film->content_rating ?? '' }}', 
                                isSaving: false,
                                isDetecting: false,
                                saveSuccess: false
                            }"
                            @age-rate-updated.window="if ($event.detail.id === {{ $film->id }}) { currentRating = $event.detail.rating || ''; saveSuccess = true; setTimeout(() => saveSuccess = false, 2000); }">
                            
                            <!-- Checkbox -->
                            <td class="px-3 py-3 text-center">
                                <input type="checkbox" 
                                       :value="{{ $film->id }}" 
                                       x-model="selectedFilmIds"
                                       class="w-4 h-4 rounded bg-zinc-950 border-zinc-700 text-amber-500 focus:ring-0 cursor-pointer">
                            </td>

                            <!-- Film Info -->
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $film->poster_url }}" 
                                         alt="{{ $film->title }}" 
                                         referrerpolicy="no-referrer" 
                                         class="w-10 h-14 object-cover rounded-lg shrink-0 bg-zinc-950 border border-zinc-800 shadow-md">
                                    <div class="min-w-0 space-y-0.5 max-w-sm">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.films.edit', $film->id) }}" 
                                               target="_blank"
                                               class="font-bold text-white text-xs hover:text-amber-400 transition-colors truncate">
                                                {{ $film->title }}
                                            </a>
                                            <span class="text-[10px] font-mono text-zinc-400 shrink-0">({{ $film->release_year }})</span>
                                        </div>
                                        <p class="text-[11px] text-zinc-400 line-clamp-1">
                                            {{ \Illuminate\Support\Str::words($film->synopsis ?: 'Tidak ada sinopsis tersedia.', 10, '...') }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Format & Genres -->
                            <td class="px-3 py-3 space-y-1">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase font-mono {{ $film->subject_type === 'series' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : ($film->subject_type === 'dracin' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30') }}">
                                        {{ $film->subject_type }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($film->genres->take(2) as $g)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-zinc-800 text-zinc-400 border border-zinc-700/80">
                                            {{ $g->name }}
                                        </span>
                                    @empty
                                        <span class="text-[10px] text-zinc-500">-</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Current Status Badge (Dynamic Styled via Config) -->
                            <td class="px-3 py-3 text-center">
                                <div class="inline-flex items-center justify-center">
                                    <!-- Dynamic Badge -->
                                    <span :style="getBadgeStyle(currentRating)"
                                          :class="[
                                              styleConfig.border_radius || 'rounded-lg',
                                              styleConfig.font_weight || 'font-black',
                                              'inline-flex items-center justify-center px-2.5 py-1 text-xs font-mono whitespace-nowrap transition-all duration-200 shadow-sm'
                                          ]"
                                          x-text="getBadgeLabel(currentRating)">
                                    </span>
                                </div>
                            </td>

                            <!-- Instant Rating Switcher Buttons -->
                            <td class="px-3 py-3 text-center">
                                <div class="inline-flex items-center p-1 rounded-xl bg-zinc-950 border border-zinc-800 gap-1 shadow-inner">
                                    
                                    <!-- SU -->
                                    <button type="button" 
                                            @click="setRating({{ $film->id }}, 'SU', $data)"
                                            :disabled="isSaving"
                                            :class="currentRating === 'SU' || currentRating === 'G' || currentRating === 'PG' ? 'bg-emerald-500 text-zinc-950 font-extrabold shadow-md shadow-emerald-500/30 scale-105' : 'text-zinc-400 hover:text-emerald-300 hover:bg-emerald-500/10'"
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-mono transition-all cursor-pointer font-bold">
                                        SU
                                    </button>

                                    <!-- 13+ -->
                                    <button type="button" 
                                            @click="setRating({{ $film->id }}, '13+', $data)"
                                            :disabled="isSaving"
                                            :class="currentRating === '13+' ? 'bg-sky-500 text-zinc-950 font-extrabold shadow-md shadow-sky-500/30 scale-105' : 'text-zinc-400 hover:text-sky-300 hover:bg-sky-500/10'"
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-mono transition-all cursor-pointer font-bold">
                                        13+
                                    </button>

                                    <!-- 16+ -->
                                    <button type="button" 
                                            @click="setRating({{ $film->id }}, '16+', $data)"
                                            :disabled="isSaving"
                                            :class="currentRating === '16+' ? 'bg-amber-500 text-zinc-950 font-extrabold shadow-md shadow-amber-500/30 scale-105' : 'text-zinc-400 hover:text-amber-300 hover:bg-amber-500/10'"
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-mono transition-all cursor-pointer font-bold">
                                        16+
                                    </button>

                                    <!-- 18+ -->
                                    <button type="button" 
                                            @click="setRating({{ $film->id }}, '18+', $data)"
                                            :disabled="isSaving"
                                            :class="currentRating === '18+' ? 'bg-rose-500 text-white font-extrabold shadow-md shadow-rose-500/30 scale-105' : 'text-zinc-400 hover:text-rose-300 hover:bg-rose-500/10'"
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-mono transition-all cursor-pointer font-bold">
                                        18+
                                    </button>

                                    <!-- Clear / Unrated -->
                                    <button type="button" 
                                            @click="setRating({{ $film->id }}, 'unrated', $data)"
                                            :disabled="isSaving"
                                            title="Set ke Unrated"
                                            :class="!currentRating ? 'bg-zinc-800 text-zinc-200 font-extrabold' : 'text-zinc-500 hover:text-zinc-300 hover:bg-zinc-800/60'"
                                            class="px-2 py-1 rounded-lg text-[10px] font-mono transition-all cursor-pointer">
                                        &times;
                                    </button>
                                </div>

                                <!-- Saving Indicator -->
                                <div class="h-3 flex items-center justify-center mt-1">
                                    <span x-show="isSaving" class="text-[9px] text-amber-400 flex items-center gap-1 font-mono">
                                        <i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>
                                        <span>Menyimpan...</span>
                                    </span>
                                    <span x-show="saveSuccess && !isSaving" class="text-[9px] text-emerald-400 flex items-center gap-1 font-mono">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        <span>Tersimpan!</span>
                                    </span>
                                </div>
                            </td>

                            <!-- Row Actions -->
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Auto Detect Single -->
                                    <button type="button" 
                                            @click="detectSingle({{ $film->id }}, $data)" 
                                            :disabled="isDetecting"
                                            class="px-2.5 py-1.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer disabled:opacity-50"
                                            title="Deteksi Rating Otomatis Berdasarkan AI & Analisis Sinopsis">
                                        <i data-lucide="wand-2" class="w-3.5 h-3.5 text-purple-400" :class="isDetecting ? 'animate-spin' : ''"></i>
                                        <span class="hidden sm:inline" x-text="isDetecting ? 'Analisis...' : 'Auto Detect'"></span>
                                    </button>

                                    <!-- Edit Link -->
                                    <a href="{{ route('admin.films.edit', $film->id) }}" 
                                       target="_blank"
                                       class="p-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition-colors" 
                                       title="Buka Form Edit Film">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i data-lucide="search-x" class="w-8 h-8 text-zinc-600"></i>
                                    <p class="text-xs font-semibold text-zinc-400">Tidak ada film yang cocok dengan filter yang dipilih.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($films->hasPages())
            <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                {{ $films->links() }}
            </div>
        @endif

    </div>

    <!-- ==================== FLOATING BULK ACTION BAR ==================== -->
    <div x-show="selectedFilmIds.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-6"
         class="fixed bottom-6 inset-x-4 sm:inset-x-auto sm:right-8 z-40 max-w-2xl bg-zinc-900/95 backdrop-blur-xl border border-zinc-700/80 p-3.5 rounded-2xl shadow-2xl shadow-black/80 flex flex-wrap items-center justify-between gap-3 ring-1 ring-white/10"
         x-cloak>
        
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-mono font-bold text-xs">
                <span x-text="selectedFilmIds.length"></span>
            </div>
            <span class="text-xs font-bold text-white">Film Dipilih</span>
        </div>

        <!-- Quick Bulk Actions -->
        <div class="flex items-center gap-1.5">
            <span class="text-[11px] text-zinc-400 mr-1 hidden sm:inline">Set ke:</span>
            
            <button type="button" @click="bulkSetRating('SU')" 
                    class="px-2.5 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 text-xs font-bold font-mono transition-all cursor-pointer">
                SU
            </button>

            <button type="button" @click="bulkSetRating('13+')" 
                    class="px-2.5 py-1.5 rounded-xl bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/30 text-xs font-bold font-mono transition-all cursor-pointer">
                13+
            </button>

            <button type="button" @click="bulkSetRating('16+')" 
                    class="px-2.5 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-xs font-bold font-mono transition-all cursor-pointer">
                16+
            </button>

            <button type="button" @click="bulkSetRating('18+')" 
                    class="px-2.5 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 text-xs font-bold font-mono transition-all cursor-pointer">
                18+
            </button>

            <button type="button" @click="bulkSetRating('unrated')" 
                    class="px-2.5 py-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold font-mono transition-all cursor-pointer">
                Reset
            </button>
        </div>

        <button type="button" @click="selectedFilmIds = []" 
                class="p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer" title="Batal Pilih">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- ==================== MODAL KUSTOMISASI STYLE RATING ==================== -->
    <template x-teleport="body">
        <div x-show="showStyleModal" 
             class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak>
            
            <div @click.away="showStyleModal = false" 
                 class="w-full max-w-3xl bg-zinc-900 border border-zinc-700/80 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto admin-scrollbar">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                        <i data-lucide="palette" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white font-chillax">Kustomisasi Desain & Style Badge Rating</h3>
                        <p class="text-xs text-zinc-400">Sesuaikan bentuk, border outline, warna, dan efek visual badge rating usia di seluruh platform.</p>
                    </div>
                </div>
                <button type="button" @click="showStyleModal = false" class="p-2 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- ==================== REALTIME LIVE PREVIEW PANEL ==================== -->
            <div class="p-5 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-zinc-300 uppercase tracking-wider font-mono flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Interactive Preview
                    </span>
                    <span class="text-[11px] text-zinc-500">Bentuk: <span class="text-amber-400 font-mono" x-text="styleDraft.border_radius"></span> &bull; Preset: <span class="text-purple-400 font-mono" x-text="styleDraft.preset"></span></span>
                </div>

                <!-- Preview Badges Row -->
                <div class="p-4 rounded-xl bg-zinc-900/90 border border-zinc-800/80 flex flex-wrap items-center justify-around gap-3">
                    
                    <!-- SU Preview -->
                    <div class="flex flex-col items-center gap-1.5">
                        <span :style="getDraftBadgeStyle('SU')"
                              :class="[
                                  styleDraft.border_radius,
                                  styleDraft.font_weight,
                                  'inline-flex items-center justify-center px-3 py-1.5 text-xs font-mono transition-all duration-200'
                              ]"
                              x-text="styleDraft.badges && styleDraft.badges.SU ? styleDraft.badges.SU.label : 'SU'">
                        </span>
                        <span class="text-[10px] text-zinc-500 font-mono">Semua Umur</span>
                    </div>

                    <!-- 13+ Preview -->
                    <div class="flex flex-col items-center gap-1.5">
                        <span :style="getDraftBadgeStyle('13+')"
                              :class="[
                                  styleDraft.border_radius,
                                  styleDraft.font_weight,
                                  'inline-flex items-center justify-center px-3 py-1.5 text-xs font-mono transition-all duration-200'
                              ]"
                              x-text="styleDraft.badges && styleDraft.badges['13+'] ? styleDraft.badges['13+'].label : '13+'">
                        </span>
                        <span class="text-[10px] text-zinc-500 font-mono">13+ Remaja</span>
                    </div>

                    <!-- 16+ Preview -->
                    <div class="flex flex-col items-center gap-1.5">
                        <span :style="getDraftBadgeStyle('16+')"
                              :class="[
                                  styleDraft.border_radius,
                                  styleDraft.font_weight,
                                  'inline-flex items-center justify-center px-3 py-1.5 text-xs font-mono transition-all duration-200'
                              ]"
                              x-text="styleDraft.badges && styleDraft.badges['16+'] ? styleDraft.badges['16+'].label : '16+'">
                        </span>
                        <span class="text-[10px] text-zinc-500 font-mono">16+ Dewasa Muda</span>
                    </div>

                    <!-- 18+ Preview -->
                    <div class="flex flex-col items-center gap-1.5">
                        <span :style="getDraftBadgeStyle('18+')"
                              :class="[
                                  styleDraft.border_radius,
                                  styleDraft.font_weight,
                                  'inline-flex items-center justify-center px-3 py-1.5 text-xs font-mono transition-all duration-200'
                              ]"
                              x-text="styleDraft.badges && styleDraft.badges['18+'] ? styleDraft.badges['18+'].label : '18+'">
                        </span>
                        <span class="text-[10px] text-zinc-500 font-mono">18+ Dewasa</span>
                    </div>

                    <!-- Unrated Preview -->
                    <div class="flex flex-col items-center gap-1.5">
                        <span :style="getDraftBadgeStyle('unrated')"
                              :class="[
                                  styleDraft.border_radius,
                                  styleDraft.font_weight,
                                  'inline-flex items-center justify-center px-3 py-1.5 text-xs font-mono transition-all duration-200'
                              ]"
                              x-text="styleDraft.badges && styleDraft.badges.unrated ? styleDraft.badges.unrated.label : 'UNRATED'">
                        </span>
                        <span class="text-[10px] text-zinc-500 font-mono">Unrated</span>
                    </div>

                </div>
            </div>

            <!-- ==================== QUICK PRESET PICKER ==================== -->
            <div class="space-y-2.5">
                <label class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-1.5">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Pilihan Preset Cepat</span>
                </label>
                
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                    
                    <!-- Preset 1: Squircle Bordered (Default Reference) -->
                    <button type="button" @click="applyPreset('squircle_bordered')"
                            :class="styleDraft.preset === 'squircle_bordered' ? 'border-amber-500 bg-amber-500/15 text-white ring-1 ring-amber-500/40 font-bold' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-zinc-700'"
                            class="p-3 rounded-2xl border text-center transition-all cursor-pointer space-y-1.5 group">
                        <div class="flex justify-center">
                            <span class="px-2 py-1 rounded-lg text-[11px] font-black border-2 border-[#10b981] bg-[#064e3b] text-white shadow-sm">SU</span>
                        </div>
                        <div class="text-[11px] font-bold">Squircle Border</div>
                        <p class="text-[9px] text-zinc-400 leading-tight">Sesuai Referensi Foto</p>
                    </button>

                    <!-- Preset 2: Pill Capsule -->
                    <button type="button" @click="applyPreset('pill_capsule')"
                            :class="styleDraft.preset === 'pill_capsule' ? 'border-amber-500 bg-amber-500/15 text-white ring-1 ring-amber-500/40 font-bold' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-zinc-700'"
                            class="p-3 rounded-2xl border text-center transition-all cursor-pointer space-y-1.5 group">
                        <div class="flex justify-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border-2 border-[#38bdf8] bg-[#075985] text-white shadow-sm">13+</span>
                        </div>
                        <div class="text-[11px] font-bold">Pill Capsule</div>
                        <p class="text-[9px] text-zinc-400 leading-tight">Kapsul Bulat Penuh</p>
                    </button>

                    <!-- Preset 3: Neon Cyber Glow -->
                    <button type="button" @click="applyPreset('neon_glow')"
                            :class="styleDraft.preset === 'neon_glow' ? 'border-amber-500 bg-amber-500/15 text-white ring-1 ring-amber-500/40 font-bold' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-zinc-700'"
                            class="p-3 rounded-2xl border text-center transition-all cursor-pointer space-y-1.5 group">
                        <div class="flex justify-center">
                            <span class="px-2 py-1 rounded-lg text-[11px] font-black border-2 border-[#ff0055] bg-[#4c0519] text-white shadow-[0_0_10px_rgba(255,0,85,0.6)]">18+</span>
                        </div>
                        <div class="text-[11px] font-bold">Neon Glow</div>
                        <p class="text-[9px] text-zinc-400 leading-tight">Berpendar Cahaya</p>
                    </button>

                    <!-- Preset 4: Solid Vivid -->
                    <button type="button" @click="applyPreset('solid_vivid')"
                            :class="styleDraft.preset === 'solid_vivid' ? 'border-amber-500 bg-amber-500/15 text-white ring-1 ring-amber-500/40 font-bold' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-zinc-700'"
                            class="p-3 rounded-2xl border text-center transition-all cursor-pointer space-y-1.5 group">
                        <div class="flex justify-center">
                            <span class="px-2 py-1 rounded-md text-[11px] font-black bg-[#f59e0b] text-[#451a03] shadow-sm">16+</span>
                        </div>
                        <div class="text-[11px] font-bold">Solid Vivid</div>
                        <p class="text-[9px] text-zinc-400 leading-tight">Warna Blok Penuh</p>
                    </button>

                    <!-- Preset 5: Minimal Glass -->
                    <button type="button" @click="applyPreset('minimal_glass')"
                            :class="styleDraft.preset === 'minimal_glass' ? 'border-amber-500 bg-amber-500/15 text-white ring-1 ring-amber-500/40 font-bold' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-zinc-700'"
                            class="p-3 rounded-2xl border text-center transition-all cursor-pointer space-y-1.5 group">
                        <div class="flex justify-center">
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold border border-[#10b981] bg-[#064e3b] text-[#6ee7b7]">SU</span>
                        </div>
                        <div class="text-[11px] font-bold">Minimal Glass</div>
                        <p class="text-[9px] text-zinc-400 leading-tight">Transparan Halus</p>
                    </button>

                </div>
            </div>

            <!-- ==================== SHAPE & OUTLINE CONTROLS ==================== -->
            <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-4">
                <h4 class="text-xs font-bold text-white uppercase tracking-wider font-mono">Bentuk Sudut, Border & Efek</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    
                    <!-- Corner Radius -->
                    <div class="space-y-1.5">
                        <label class="text-xs text-zinc-400 font-semibold">Bentuk Sudut (Radius)</label>
                        <select x-model="styleDraft.border_radius" 
                                class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                            <option value="rounded-lg">Squircle Sedang (rounded-lg) ★</option>
                            <option value="rounded-md">Squircle Kecil (rounded-md)</option>
                            <option value="rounded-xl">Squircle Besar (rounded-xl)</option>
                            <option value="rounded-full">Kapsul Bulat (rounded-full)</option>
                            <option value="rounded">Kotak Halus (rounded)</option>
                            <option value="rounded-none">Kotak Tegas (rounded-none)</option>
                        </select>
                    </div>

                    <!-- Border Width -->
                    <div class="space-y-1.5">
                        <label class="text-xs text-zinc-400 font-semibold">Ketebalan Border</label>
                        <select x-model="styleDraft.border_width" 
                                class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                            <option value="border-2">Tebal 2px (Sesuai Referensi) ★</option>
                            <option value="border-[1.5px]">Sedang 1.5px</option>
                            <option value="border">Tipis 1px</option>
                            <option value="border-0">Tanpa Border (Solid Only)</option>
                        </select>
                    </div>

                    <!-- Font Weight -->
                    <div class="space-y-1.5">
                        <label class="text-xs text-zinc-400 font-semibold">Ketebalan Huruf</label>
                        <select x-model="styleDraft.font_weight" 
                                class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                            <option value="font-black">Black / Extra Bold (900) ★</option>
                            <option value="font-extrabold">Extra Bold (800)</option>
                            <option value="font-bold">Bold (700)</option>
                            <option value="font-semibold">Semi Bold (600)</option>
                        </select>
                    </div>

                </div>

                <!-- Toggles: Shadow & Glow -->
                <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-zinc-900">
                    <label class="flex items-center gap-2 text-xs font-semibold text-zinc-300 cursor-pointer select-none">
                        <input type="checkbox" x-model="styleDraft.has_shadow" class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-amber-500 focus:ring-0">
                        <span>Aktifkan Drop Shadow</span>
                    </label>

                    <label class="flex items-center gap-2 text-xs font-semibold text-zinc-300 cursor-pointer select-none">
                        <input type="checkbox" x-model="styleDraft.has_glow" class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-amber-500 focus:ring-0">
                        <span>Aktifkan Outer Border Glow (Pendaran Warna)</span>
                    </label>
                </div>
            </div>

            <!-- ==================== COLOR CUSTOMIZATION PER CATEGORY ==================== -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-white uppercase tracking-wider font-mono">Pengaturan Warna & Label per Kategori</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    
                    <!-- SU Card -->
                    <div class="p-3.5 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-400 font-mono">🟢 SU (Semua Umur)</span>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: styleDraft.badges && styleDraft.badges.SU ? styleDraft.badges.SU.border_color : '#10b981' }"></span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2 text-[10px]">
                            <div>
                                <span class="text-zinc-400 block mb-1">Border</span>
                                <input type="color" x-model="styleDraft.badges.SU.border_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Background</span>
                                <input type="color" x-model="styleDraft.badges.SU.bg_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Teks</span>
                                <input type="color" x-model="styleDraft.badges.SU.text_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <!-- 13+ Card -->
                    <div class="p-3.5 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-sky-400 font-mono">🔵 13+ (Remaja)</span>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: styleDraft.badges && styleDraft.badges['13+'] ? styleDraft.badges['13+'].border_color : '#0284c7' }"></span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2 text-[10px]">
                            <div>
                                <span class="text-zinc-400 block mb-1">Border</span>
                                <input type="color" x-model="styleDraft.badges['13+'].border_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Background</span>
                                <input type="color" x-model="styleDraft.badges['13+'].bg_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Teks</span>
                                <input type="color" x-model="styleDraft.badges['13+'].text_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <!-- 16+ Card -->
                    <div class="p-3.5 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-amber-400 font-mono">🟡 16+ (Dewasa Muda)</span>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: styleDraft.badges && styleDraft.badges['16+'] ? styleDraft.badges['16+'].border_color : '#f59e0b' }"></span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2 text-[10px]">
                            <div>
                                <span class="text-zinc-400 block mb-1">Border</span>
                                <input type="color" x-model="styleDraft.badges['16+'].border_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Background</span>
                                <input type="color" x-model="styleDraft.badges['16+'].bg_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Teks</span>
                                <input type="color" x-model="styleDraft.badges['16+'].text_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <!-- 18+ Card -->
                    <div class="p-3.5 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-rose-400 font-mono">🔴 18+ (Dewasa)</span>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: styleDraft.badges && styleDraft.badges['18+'] ? styleDraft.badges['18+'].border_color : '#f43f5e' }"></span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2 text-[10px]">
                            <div>
                                <span class="text-zinc-400 block mb-1">Border</span>
                                <input type="color" x-model="styleDraft.badges['18+'].border_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Background</span>
                                <input type="color" x-model="styleDraft.badges['18+'].bg_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Teks</span>
                                <input type="color" x-model="styleDraft.badges['18+'].text_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <!-- UNRATED Card -->
                    <div class="p-3.5 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-400 font-mono">⚪ UNRATED</span>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: styleDraft.badges && styleDraft.badges.unrated ? styleDraft.badges.unrated.border_color : '#52525b' }"></span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2 text-[10px]">
                            <div>
                                <span class="text-zinc-400 block mb-1">Border</span>
                                <input type="color" x-model="styleDraft.badges.unrated.border_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Background</span>
                                <input type="color" x-model="styleDraft.badges.unrated.bg_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                            <div>
                                <span class="text-zinc-400 block mb-1">Teks</span>
                                <input type="color" x-model="styleDraft.badges.unrated.text_color" class="w-full h-7 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ==================== MODAL FOOTER ACTIONS ==================== -->
            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-zinc-800">
                <button type="button" @click="resetToDefaultStyle()" 
                        class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-semibold text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset ke Style Referensi (Default)</span>
                </button>

                <div class="flex items-center gap-2.5 justify-end">
                    <button type="button" @click="showStyleModal = false" 
                            class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-bold text-xs transition-colors cursor-pointer">
                        Batal
                    </button>

                    <button type="button" @click="saveStyleConfig()" 
                            :disabled="isSavingStyle"
                            class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-black text-xs shadow-lg shadow-amber-500/25 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50">
                        <i data-lucide="check-check" class="w-4 h-4" :class="isSavingStyle ? 'animate-spin' : ''"></i>
                        <span x-text="isSavingStyle ? 'Menyimpan Style...' : 'Simpan & Terapkan Style'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
    </template>

    <!-- ==================== STANDARDS & LEGEND MODAL ==================== -->
    <template x-teleport="body">
        <div x-show="showLegendModal" 
             class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak>
            
            <div @click.away="showLegendModal = false" 
                 class="w-full max-w-xl bg-zinc-900 border border-zinc-800 rounded-3xl p-6 shadow-2xl space-y-5">
                
                <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-chillax">Standar Klasifikasi Rating Usia</h3>
                            <p class="text-xs text-zinc-400">Pedoman sensor & batas usia konten pada aplikasi faiilmov.</p>
                        </div>
                    </div>
                    <button type="button" @click="showLegendModal = false" class="p-1.5 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Categories Grid -->
                <div class="space-y-3 text-xs">
                    
                    <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex gap-3">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500 text-zinc-950 font-bold font-mono h-fit shrink-0">SU / G</span>
                        <div>
                            <h4 class="font-bold text-emerald-300">Semua Umur (General Audience)</h4>
                            <p class="text-zinc-400 mt-0.5">Konten aman untuk ditonton seluruh anggota keluarga dan anak-anak. Ditampilkan pada <em>Kids Profile</em>.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-sky-500/10 border border-sky-500/20 flex gap-3">
                        <span class="px-2.5 py-1 rounded-lg bg-sky-500 text-zinc-950 font-bold font-mono h-fit shrink-0">13+ / PG</span>
                        <div>
                            <h4 class="font-bold text-sky-300">Bimbingan Remaja (Parents Strongly Cautioned)</h4>
                            <p class="text-zinc-400 mt-0.5">Mungkin memuat aksi ringan, tema fantasi, atau ketegangan moderat. Diperuntukkan bagi penonton usia 13 tahun ke atas.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex gap-3">
                        <span class="px-2.5 py-1 rounded-lg bg-amber-500 text-zinc-950 font-bold font-mono h-fit shrink-0">16+ / 17+</span>
                        <div>
                            <h4 class="font-bold text-amber-300">Dewasa Muda (Restricted)</h4>
                            <p class="text-zinc-400 mt-0.5">Memuat unsur kejahatan, thriller, adegan laga intens, atau tema psikologis berat. Dilarang untuk profil anak.</p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex gap-3">
                        <span class="px-2.5 py-1 rounded-lg bg-rose-500 text-zinc-950 font-bold font-mono h-fit shrink-0">18+ / 21+</span>
                        <div>
                            <h4 class="font-bold text-rose-300">Khusus Dewasa (Adults Only)</h4>
                            <p class="text-zinc-400 mt-0.5">Memuat horor ekstrem/sadis, kekerasan grafis, adegan eksplisit, atau bahasa vulgar berat. Memerlukan PIN Parental Control.</p>
                        </div>
                    </div>

                </div>

                <div class="pt-2 text-right">
                    <button type="button" @click="showLegendModal = false" class="px-5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs transition-colors cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
window.__AGE_RATING_STYLE__ = {!! json_encode($ageRatingStyle ?? \App\Models\SiteSetting::defaultAgeRatingStyle(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};

function ageRatingCms() {
    const rawStyle = window.__AGE_RATING_STYLE__ || {};
    const defaultBadges = {
        'SU': { label: 'SU', bg_color: '#064e3b', border_color: '#10b981', text_color: '#ffffff' },
        '13+': { label: '13+', bg_color: '#0c4a6e', border_color: '#0284c7', text_color: '#ffffff' },
        '16+': { label: '16+', bg_color: '#78350f', border_color: '#f59e0b', text_color: '#ffffff' },
        '18+': { label: '18+', bg_color: '#4c0519', border_color: '#f43f5e', text_color: '#ffffff' },
        'unrated': { label: 'UNRATED', bg_color: '#27272a', border_color: '#52525b', text_color: '#d4d4d8' }
    };

    const initialStyle = {
        preset: rawStyle.preset || 'squircle_bordered',
        border_radius: rawStyle.border_radius || 'rounded-lg',
        border_width: rawStyle.border_width || 'border-2',
        font_weight: rawStyle.font_weight || 'font-black',
        has_glow: typeof rawStyle.has_glow !== 'undefined' ? Boolean(rawStyle.has_glow) : true,
        has_shadow: typeof rawStyle.has_shadow !== 'undefined' ? Boolean(rawStyle.has_shadow) : true,
        badges: Object.assign({}, defaultBadges, rawStyle.badges || {})
    };

    return {
        selectedFilmIds: [],
        showLegendModal: false,
        showStyleModal: false,
        isDetectingAll: false,
        isSavingStyle: false,

        styleConfig: JSON.parse(JSON.stringify(initialStyle)),
        styleDraft: JSON.parse(JSON.stringify(initialStyle)),

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        },

        openStyleModal() {
            this.styleDraft = JSON.parse(JSON.stringify(this.styleConfig));
            this.showStyleModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        },

        getBadgeKey(rating) {
            if (!rating) return 'unrated';
            const r = String(rating).toUpperCase().trim();
            if (['SU', 'G', 'PG'].includes(r)) return 'SU';
            if (['13+', 'PG-13'].includes(r)) return '13+';
            if (['16+', '17+'].includes(r)) return '16+';
            if (['18+', '21+', 'R', 'NC-17'].includes(r)) return '18+';
            return 'unrated';
        },

        getBadgeLabel(rating) {
            const key = this.getBadgeKey(rating);
            if (this.styleConfig && this.styleConfig.badges && this.styleConfig.badges[key]) {
                return this.styleConfig.badges[key].label || key;
            }
            return rating || 'UNRATED';
        },

        getBadgeStyle(rating) {
            const key = this.getBadgeKey(rating);
            const cfg = (this.styleConfig && this.styleConfig.badges) ? this.styleConfig.badges[key] : null;
            if (!cfg) return {};

            const bWidth = this.styleConfig.border_width === 'border-0' ? '0px' : (this.styleConfig.border_width === 'border-[1.5px]' ? '1.5px' : (this.styleConfig.border_width === 'border' ? '1px' : '2px'));
            const shadowStr = this.styleConfig.has_glow ? `0 0 8px ${cfg.border_color}40` : (this.styleConfig.has_shadow ? '0 2px 4px rgba(0,0,0,0.4)' : 'none');

            return {
                backgroundColor: cfg.bg_color || '#27272a',
                borderColor: cfg.border_color || '#52525b',
                color: cfg.text_color || '#ffffff',
                borderWidth: bWidth,
                borderStyle: bWidth === '0px' ? 'none' : 'solid',
                boxShadow: shadowStr
            };
        },

        getDraftBadgeStyle(key) {
            const cfg = (this.styleDraft && this.styleDraft.badges) ? this.styleDraft.badges[key] : null;
            if (!cfg) return {};

            const bWidth = this.styleDraft.border_width === 'border-0' ? '0px' : (this.styleDraft.border_width === 'border-[1.5px]' ? '1.5px' : (this.styleDraft.border_width === 'border' ? '1px' : '2px'));
            const shadowStr = this.styleDraft.has_glow ? `0 0 10px ${cfg.border_color}60` : (this.styleDraft.has_shadow ? '0 2px 5px rgba(0,0,0,0.5)' : 'none');

            return {
                backgroundColor: cfg.bg_color || '#27272a',
                borderColor: cfg.border_color || '#52525b',
                color: cfg.text_color || '#ffffff',
                borderWidth: bWidth,
                borderStyle: bWidth === '0px' ? 'none' : 'solid',
                boxShadow: shadowStr
            };
        },

        applyPreset(presetName) {
            this.styleDraft.preset = presetName;

            if (presetName === 'squircle_bordered') {
                this.styleDraft.border_radius = 'rounded-lg';
                this.styleDraft.border_width = 'border-2';
                this.styleDraft.font_weight = 'font-black';
                this.styleDraft.has_glow = true;
                this.styleDraft.has_shadow = true;
                this.styleDraft.badges = {
                    'SU': { label: 'SU', bg_color: '#064e3b', border_color: '#10b981', text_color: '#ffffff' },
                    '13+': { label: '13+', bg_color: '#0c4a6e', border_color: '#0284c7', text_color: '#ffffff' },
                    '16+': { label: '16+', bg_color: '#78350f', border_color: '#f59e0b', text_color: '#ffffff' },
                    '18+': { label: '18+', bg_color: '#4c0519', border_color: '#f43f5e', text_color: '#ffffff' },
                    'unrated': { label: 'UNRATED', bg_color: '#27272a', border_color: '#52525b', text_color: '#d4d4d8' }
                };
            } else if (presetName === 'pill_capsule') {
                this.styleDraft.border_radius = 'rounded-full';
                this.styleDraft.border_width = 'border-2';
                this.styleDraft.font_weight = 'font-black';
                this.styleDraft.has_glow = false;
                this.styleDraft.has_shadow = true;
                this.styleDraft.badges = {
                    'SU': { label: 'SU', bg_color: '#064e3b', border_color: '#34d399', text_color: '#ffffff' },
                    '13+': { label: '13+', bg_color: '#075985', border_color: '#38bdf8', text_color: '#ffffff' },
                    '16+': { label: '16+', bg_color: '#854d0e', border_color: '#fbbf24', text_color: '#ffffff' },
                    '18+': { label: '18+', bg_color: '#881337', border_color: '#fb7185', text_color: '#ffffff' },
                    'unrated': { label: 'UNRATED', bg_color: '#18181b', border_color: '#71717a', text_color: '#a1a1aa' }
                };
            } else if (presetName === 'neon_glow') {
                this.styleDraft.border_radius = 'rounded-lg';
                this.styleDraft.border_width = 'border-2';
                this.styleDraft.font_weight = 'font-black';
                this.styleDraft.has_glow = true;
                this.styleDraft.has_shadow = true;
                this.styleDraft.badges = {
                    'SU': { label: 'SU', bg_color: '#022c22', border_color: '#00f59b', text_color: '#ffffff' },
                    '13+': { label: '13+', bg_color: '#082f49', border_color: '#00c3ff', text_color: '#ffffff' },
                    '16+': { label: '16+', bg_color: '#451a03', border_color: '#ffb703', text_color: '#ffffff' },
                    '18+': { label: '18+', bg_color: '#4c0519', border_color: '#ff0055', text_color: '#ffffff' },
                    'unrated': { label: 'UNRATED', bg_color: '#18181b', border_color: '#a1a1aa', text_color: '#ffffff' }
                };
            } else if (presetName === 'solid_vivid') {
                this.styleDraft.border_radius = 'rounded-md';
                this.styleDraft.border_width = 'border-0';
                this.styleDraft.font_weight = 'font-black';
                this.styleDraft.has_glow = false;
                this.styleDraft.has_shadow = true;
                this.styleDraft.badges = {
                    'SU': { label: 'SU', bg_color: '#10b981', border_color: '#10b981', text_color: '#022c22' },
                    '13+': { label: '13+', bg_color: '#0284c7', border_color: '#0284c7', text_color: '#ffffff' },
                    '16+': { label: '16+', bg_color: '#f59e0b', border_color: '#f59e0b', text_color: '#451a03' },
                    '18+': { label: '18+', bg_color: '#f43f5e', border_color: '#f43f5e', text_color: '#ffffff' },
                    'unrated': { label: 'UNRATED', bg_color: '#3f3f46', border_color: '#3f3f46', text_color: '#ffffff' }
                };
            } else if (presetName === 'minimal_glass') {
                this.styleDraft.border_radius = 'rounded-md';
                this.styleDraft.border_width = 'border';
                this.styleDraft.font_weight = 'font-bold';
                this.styleDraft.has_glow = false;
                this.styleDraft.has_shadow = false;
                this.styleDraft.badges = {
                    'SU': { label: 'SU', bg_color: '#064e3b', border_color: '#10b981', text_color: '#6ee7b7' },
                    '13+': { label: '13+', bg_color: '#082f49', border_color: '#0284c7', text_color: '#7dd3fc' },
                    '16+': { label: '16+', bg_color: '#451a03', border_color: '#f59e0b', text_color: '#fcd34d' },
                    '18+': { label: '18+', bg_color: '#4c0519', border_color: '#f43f5e', text_color: '#fda4af' },
                    'unrated': { label: 'UNRATED', bg_color: '#18181b', border_color: '#71717a', text_color: '#a1a1aa' }
                };
            }
        },

        resetToDefaultStyle() {
            this.applyPreset('squircle_bordered');
        },

        async saveStyleConfig() {
            this.isSavingStyle = true;

            try {
                const res = await fetch('{{ route("admin.films.save_age_rating_style") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.styleDraft)
                });

                if (res.ok) {
                    const data = await res.json();
                    this.styleConfig = JSON.parse(JSON.stringify(this.styleDraft));
                    this.showStyleModal = false;
                    alert('Style rating usia berhasil disimpan dan diterapkan!');
                } else {
                    alert('Gagal menyimpan style. Silakan coba lagi.');
                }
            } catch (e) {
                console.error('Error saving style', e);
                alert('Terjadi kesalahan koneksi server.');
            } finally {
                this.isSavingStyle = false;
            }
        },

        isAllSelected() {
            const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
            return checkboxes.length > 0 && this.selectedFilmIds.length === checkboxes.length;
        },

        toggleSelectAll(checked) {
            const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
            if (checked) {
                this.selectedFilmIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            } else {
                this.selectedFilmIds = [];
            }
        },

        async setRating(filmId, ratingVal, rowData) {
            if (rowData) {
                rowData.isSaving = true;
                rowData.saveSuccess = false;
            }

            try {
                const res = await fetch(`/admin/films/${filmId}/content-rating`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content_rating: ratingVal === 'unrated' ? null : ratingVal
                    })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (rowData) {
                        rowData.currentRating = data.rating || '';
                        rowData.saveSuccess = true;
                        setTimeout(() => {
                            rowData.saveSuccess = false;
                        }, 2000);
                    }
                    window.dispatchEvent(new CustomEvent('age-rate-updated', {
                        detail: { id: filmId, rating: data.rating }
                    }));
                }
            } catch (e) {
                console.error('Gagal memperbarui rating usia untuk film ' + filmId, e);
            } finally {
                if (rowData) {
                    rowData.isSaving = false;
                }
            }
        },

        async detectSingle(filmId, rowData) {
            if (rowData) {
                rowData.isDetecting = true;
            }

            try {
                const res = await fetch(`/admin/films/${filmId}/auto-rate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.rating) {
                        if (rowData) {
                            rowData.currentRating = data.rating;
                            rowData.saveSuccess = true;
                            setTimeout(() => {
                                rowData.saveSuccess = false;
                            }, 2500);
                        }
                        window.dispatchEvent(new CustomEvent('age-rate-updated', {
                            detail: { id: filmId, rating: data.rating }
                        }));
                    }
                }
            } catch (e) {
                console.error('Auto detect error for film ' + filmId, e);
            } finally {
                if (rowData) {
                    rowData.isDetecting = false;
                }
            }
        },

        async autoDetectCurrentPage() {
            this.isDetectingAll = true;
            const rows = document.querySelectorAll('tbody tr[id^="film-row-"]');

            for (const row of rows) {
                const filmId = parseInt(row.id.replace('film-row-', ''));
                try {
                    const res = await fetch(`/admin/films/${filmId}/auto-rate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.rating) {
                            window.dispatchEvent(new CustomEvent('age-rate-updated', {
                                detail: { id: filmId, rating: data.rating }
                            }));
                        }
                    }
                } catch (e) {
                    console.error('Auto detect error for film ' + filmId, e);
                }
            }

            this.isDetectingAll = false;
        },

        async bulkSetRating(ratingVal) {
            if (this.selectedFilmIds.length === 0) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.films.bulk_set_content_rating") }}';
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            form.appendChild(csrf);

            const ratingInput = document.createElement('input');
            ratingInput.type = 'hidden';
            ratingInput.name = 'content_rating';
            ratingInput.value = ratingVal;
            form.appendChild(ratingInput);

            this.selectedFilmIds.forEach(id => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'film_ids[]';
                idInput.value = id;
                form.appendChild(idInput);
            });

            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
@endpush
