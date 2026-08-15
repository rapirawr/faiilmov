@extends('layouts.admin')

@section('title', 'Edit Film: ' . $film->title . ' | faiiladmin')
@section('page_title', 'Edit Film: ' . $film->title)

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    activeTab: 'details',
    posterUrl: '{{ old('poster_url', $film->poster_url) }}',
    backdropUrl: '{{ old('backdrop_url', $film->backdrop_url) }}'
}">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-white font-['Outfit']">Edit Data Film: {{ $film->title }}</h2>
            <p class="text-xs text-zinc-400">ID Film: <code class="text-amber-400 font-mono">#{{ $film->id }}</code> ({{ $film->subject_type }})</p>
        </div>
        <a href="{{ route('admin.films.index') }}" class="text-xs text-zinc-400 hover:text-white flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-white/10 text-xs">
        <button @click="activeTab = 'details'" 
                :class="activeTab === 'details' ? 'border-amber-500 text-amber-400 font-bold bg-amber-500/10' : 'border-transparent text-zinc-400 hover:text-white'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="info" class="w-4 h-4"></i>
            <span>Informasi Utama</span>
        </button>

        <button @click="activeTab = 'soundtracks'" 
                :class="activeTab === 'soundtracks' ? 'border-amber-500 text-amber-400 font-bold bg-amber-500/10' : 'border-transparent text-zinc-400 hover:text-white'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="music" class="w-4 h-4"></i>
            <span>Soundtrack & Lagu (OST)</span>
            <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-extrabold">{{ $film->soundtracks->count() }} Lagu</span>
        </button>

        @if($film->isEpisodic())
            <button @click="activeTab = 'seasons'" 
                    :class="activeTab === 'seasons' ? 'border-purple-500 text-purple-300 font-bold bg-purple-500/10' : 'border-transparent text-zinc-400 hover:text-white'"
                    class="px-4 py-2.5 rounded-t-xl border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i data-lucide="layers" class="w-4 h-4"></i>
                <span>Kelola Season & Episode</span>
                <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-extrabold">{{ $film->seasons->count() }} Season</span>
            </button>
        @endif
    </div>

    <!-- Tab 1: Film Details Form -->
    <div x-show="activeTab === 'details'" class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl">
        <form action="{{ route('admin.films.update', $film->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Judul Film *</label>
                    <input type="text" name="title" value="{{ old('title', $film->title) }}" required 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tipe *</label>
                    <select name="subject_type" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="movie" {{ old('subject_type', $film->subject_type) === 'movie' ? 'selected' : '' }}>Movie</option>
                        <option value="series" {{ old('subject_type', $film->subject_type) === 'series' ? 'selected' : '' }}>Series</option>
                        <option value="dracin" {{ old('subject_type', $film->subject_type) === 'dracin' ? 'selected' : '' }}>Drama China (Dracin)</option>
                    </select>
                </div>

                <!-- Content Rating -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Rating Usia (Content Rating)</label>
                        <button type="button" 
                                onclick="fetch('{{ route('admin.films.auto_rate', $film->id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{if(d.rating){document.querySelector('select[name=content_rating]').value=d.rating; alert('Auto-rate terdeteksi: ' + d.rating);}})" 
                                class="text-[10px] font-bold text-amber-400 hover:text-amber-300 flex items-center gap-1 cursor-pointer">
                            <i data-lucide="wand-2" class="w-3 h-3"></i>
                            <span>Auto Detect</span>
                        </button>
                    </div>
                    <select name="content_rating" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="" {{ is_null($film->content_rating) ? 'selected' : '' }}>-- Belum Ditentukan (Unrated) --</option>
                        <option value="SU" {{ old('content_rating', $film->content_rating) === 'SU' ? 'selected' : '' }}>SU - Semua Umur</option>
                        <option value="G" {{ old('content_rating', $film->content_rating) === 'G' ? 'selected' : '' }}>G - General Audience</option>
                        <option value="PG" {{ old('content_rating', $film->content_rating) === 'PG' ? 'selected' : '' }}>PG - Parental Guidance</option>
                        <option value="13+" {{ old('content_rating', $film->content_rating) === '13+' ? 'selected' : '' }}>13+ - Remaja</option>
                        <option value="16+" {{ old('content_rating', $film->content_rating) === '16+' ? 'selected' : '' }}>16+ - Dewasa Muda</option>
                        <option value="18+" {{ old('content_rating', $film->content_rating) === '18+' ? 'selected' : '' }}>18+ - Dewasa</option>
                    </select>
                </div>

                <!-- Max Resolution -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Kualitas Maksimum</label>
                    <select name="max_resolution" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="1080P" {{ old('max_resolution', $film->max_resolution) === '1080P' ? 'selected' : '' }}>1080P Full HD</option>
                        <option value="4K" {{ old('max_resolution', $film->max_resolution) === '4K' ? 'selected' : '' }}>4K Ultra HD</option>
                        <option value="720P" {{ old('max_resolution', $film->max_resolution) === '720P' ? 'selected' : '' }}>720P HD</option>
                        <option value="480P" {{ old('max_resolution', $film->max_resolution) === '480P' ? 'selected' : '' }}>480P SD</option>
                    </select>
                </div>

                <!-- Release Year -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tahun Rilis</label>
                    <input type="number" name="release_year" value="{{ old('release_year', $film->release_year) }}" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Durasi (Menit)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $film->duration_minutes) }}" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Rating -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Rating (0 - 10)</label>
                    <input type="number" step="0.1" name="rating" value="{{ old('rating', $film->rating) }}" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- View Count -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Jumlah View Count</label>
                    <input type="number" name="view_count" value="{{ old('view_count', $film->view_count) }}" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Trailer URL -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">URL Trailer YouTube</label>
                    <input type="url" name="trailer_url" value="{{ old('trailer_url', $film->trailer_url) }}" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Poster Image Upload & URL -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Upload Poster Baru (File)</label>
                    <input type="file" name="poster" accept="image/*" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-black hover:file:bg-amber-400 cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Poster URL (Link)</label>
                    <input type="url" name="poster_url" x-model="posterUrl" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Backdrop URL -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Backdrop Image URL (Banner)</label>
                    <input type="url" name="backdrop_url" x-model="backdropUrl" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Live Preview -->
                <div class="md:col-span-2 p-4 rounded-xl bg-zinc-950 border border-white/10 flex flex-col sm:flex-row items-center gap-4">
                    <img :src="posterUrl" class="w-16 h-24 object-cover rounded-lg shrink-0 border border-white/10 shadow" onerror="this.src='https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600'">
                    <div class="space-y-1 text-xs">
                        <span class="text-amber-400 font-bold">Preview Image</span>
                        <p class="text-zinc-400">Pratinjau gambar poster aktif untuk memastikan tautan gambar tidak rusak.</p>
                    </div>
                </div>

                <!-- Synopsis -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Sinopsis</label>
                    <textarea name="synopsis" rows="4" 
                              class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-amber-500">{{ old('synopsis', $film->synopsis) }}</textarea>
                </div>

                <!-- Genres -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Pilih Genre</label>
                    @php $selectedGenreIds = $film->genres->pluck('id')->toArray(); @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-4 rounded-xl bg-zinc-950 border border-white/10 max-h-48 overflow-y-auto">
                        @foreach($genres as $g)
                            <label class="flex items-center gap-2 text-xs text-zinc-300 hover:text-white cursor-pointer">
                                <input type="checkbox" name="genres[]" value="{{ $g->id }}" {{ in_array($g->id, old('genres', $selectedGenreIds)) ? 'checked' : '' }} class="rounded border-white/20 bg-zinc-900 text-amber-500 focus:ring-amber-500">
                                <span>{{ $g->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

<script>
if (typeof window.castPicker !== 'function') {
    window.castPicker = function(initialCast = []) {
        return {
            cast: initialCast,
            searchQuery: '',
            searchResults: [],
            isSearching: false,
            
            async doSearch() {
                const q = this.searchQuery.trim();
                if (q.length < 2) {
                    this.searchResults = [];
                    return;
                }

                this.isSearching = true;
                try {
                    const res = await fetch('/admin/actors/search-api?q=' + encodeURIComponent(q));
                    if (res.ok) {
                        const data = await res.json();
                        this.searchResults = data;
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    this.isSearching = false;
                }
            },

            addActor(actor) {
                if (this.cast.some(a => a.id === actor.id)) {
                    return;
                }
                this.cast.push({
                    id: actor.id,
                    name: actor.name,
                    photo_url: actor.photo_url || '',
                    role_type: 'regular',
                    character_name: ''
                });
                this.searchQuery = '';
                this.searchResults = [];
            },

            removeActor(id) {
                this.cast = this.cast.filter(a => a.id !== id);
            }
        };
    };
}
</script>

                <!-- Actors / Cast Management -->
                @php
                    $initialCastData = $film->actors->map(function($a) {
                        return [
                            'id' => $a->id,
                            'name' => $a->name,
                            'photo_url' => $a->photo_url ?: '',
                            'role_type' => $a->pivot->role_type ?? 'regular',
                            'character_name' => $a->pivot->character_name ?? '',
                        ];
                    })->values();
                @endphp

                <div class="md:col-span-2 space-y-3" x-data="castPicker({{ json_encode($initialCastData) }})">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                            Pemeran Film (<span x-text="cast.length"></span> Aktor)
                        </label>

                        <!-- Live Search Input -->
                        <div class="relative min-w-[280px]">
                            <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all">
                                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-400"></i>
                                <input type="text" 
                                       x-model="searchQuery" 
                                       @input.debounce.300ms="doSearch()"
                                       placeholder="Cari & tambah aktor baru..." 
                                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
                                <div x-show="isSearching" class="w-3.5 h-3.5 border-2 border-amber-500 border-t-transparent rounded-full animate-spin shrink-0"></div>
                            </div>

                            <!-- Search Results Dropdown -->
                            <div x-show="searchResults.length > 0" 
                                 x-cloak
                                 @click.outside="searchResults = []"
                                 class="absolute left-0 right-0 top-full mt-2 bg-zinc-900 border border-white/15 rounded-2xl shadow-2xl z-50 max-h-60 overflow-y-auto p-1 space-y-1">
                                <template x-for="actor in searchResults" :key="actor.id">
                                    <button type="button" 
                                            @click="addActor(actor)"
                                            class="w-full text-left p-2 rounded-xl hover:bg-white/10 flex items-center justify-between transition-colors cursor-pointer group">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <img :src="actor.photo_url || 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 24 24\' fill=\'%239ca3af\'><rect width=\'100%\' height=\'100%\' fill=\'%2327272a\'/><path d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/></svg>'" 
                                                 :alt="actor.name"
                                                 class="w-7 h-7 rounded-full object-cover bg-zinc-800 shrink-0">
                                            <span class="text-xs font-bold text-white group-hover:text-amber-300 transition-colors truncate" x-text="actor.name"></span>
                                        </div>
                                        <span x-text="cast.some(a => a.id === actor.id) ? 'Sudah Ada' : '+ Tambah'" 
                                              :class="cast.some(a => a.id === actor.id) ? 'text-zinc-500 bg-white/5' : 'text-amber-400 bg-amber-500/10 border border-amber-500/20'"
                                              class="text-[10px] font-extrabold px-2 py-0.5 rounded-lg shrink-0"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Cast List Cards -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-3 min-h-[6rem]">
                        <template x-for="(item, idx) in cast" :key="item.id">
                            <div class="flex items-center gap-3 p-2.5 rounded-xl bg-zinc-900/80 border border-white/10 hover:border-white/20 transition-all">
                                <!-- Hidden Input for Form Submission -->
                                <input type="hidden" name="actors[]" :value="item.id">
                                
                                <img :src="item.photo_url || 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 24 24\' fill=\'%239ca3af\'><rect width=\'100%\' height=\'100%\' fill=\'%2327272a\'/><path d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/></svg>'" 
                                     :alt="item.name" 
                                     class="w-9 h-9 rounded-full object-cover bg-zinc-800 shrink-0 border border-white/10 shadow-xs">

                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate" x-text="item.name"></h4>
                                </div>

                                <!-- Role Selector -->
                                <select :name="'actor_roles[' + item.id + ']'" 
                                        x-model="item.role_type"
                                        class="bg-zinc-950 border border-white/15 rounded-xl px-2.5 py-1.5 text-xs text-amber-400 focus:outline-none focus:border-amber-500 font-bold cursor-pointer shrink-0">
                                    <option value="main">⭐ Utama</option>
                                    <option value="regular">Pemeran</option>
                                </select>

                                <!-- Character Name Input -->
                                <input type="text" 
                                       :name="'actor_characters[' + item.id + ']'" 
                                       x-model="item.character_name"
                                       placeholder="Nama Karakter..." 
                                       class="w-36 bg-zinc-950 border border-white/15 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 shrink-0">

                                <!-- Remove Button -->
                                <button type="button" 
                                        @click="removeActor(item.id)" 
                                        title="Hapus Aktor dari Film"
                                        class="p-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 transition-all cursor-pointer shrink-0">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </template>

                        <div x-show="cast.length === 0" class="text-center py-6 text-xs text-zinc-500">
                            Belum ada aktor yang ditambahkan untuk film ini. Gunakan kolom pencarian di atas untuk menambahkan pemeran.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                <a href="{{ route('admin.films.index') }}" class="px-5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs transition-colors">Batal</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer">Perbarui Film</button>
            </div>
        </form>
    </div>

    <!-- Tab 2: Season & Episode Manager (Series Only) -->
    @if($film->isEpisodic())
        <div x-show="activeTab === 'seasons'" class="space-y-6">

            <!-- Add New Season Card -->
            <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-4">
                <h3 class="text-sm font-bold text-white font-['Outfit'] flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-purple-400"></i>
                    <span>Tambah Season Baru</span>
                </h3>
                <form action="{{ route('admin.seasons.store', $film->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @csrf
                    <div>
                        <input type="number" name="season_number" value="{{ old('season_number', $film->seasons->count() + 1) }}" required placeholder="Nomor Season"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <input type="text" name="title" placeholder="Judul Season (Opsional)"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <input type="number" name="release_year" value="{{ $film->release_year }}" placeholder="Tahun Rilis"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <button type="submit" class="w-full py-2 px-4 rounded-xl bg-purple-500 hover:bg-purple-400 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Season</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Seasons List & Episode Management -->
            @forelse($film->seasons as $season)
                <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-4" x-data="{ addEpisodeOpen: false }">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/30 flex items-center justify-center font-bold text-xs font-mono">
                                S{{ $season->season_number }}
                            </span>
                            <div>
                                <h4 class="font-bold text-white text-sm">{{ $season->title ?: 'Season ' . $season->season_number }}</h4>
                                <p class="text-[11px] text-zinc-400">{{ $season->release_year }} • {{ $season->episodes->count() }} Episode</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="addEpisodeOpen = !addEpisodeOpen" class="px-3 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-bold text-xs border border-emerald-500/30 transition-colors flex items-center gap-1 cursor-pointer">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Tambah Episode</span>
                            </button>

                            <form action="{{ route('admin.seasons.destroy', $season->id) }}" method="POST" onsubmit="return confirm('Hapus Season ini beserta seluruh episode di dalamnya?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus Season">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Add Episode Inline Form -->
                    <div x-show="addEpisodeOpen" x-transition class="p-4 rounded-xl bg-zinc-950 border border-emerald-500/30 space-y-3">
                        <h5 class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                            <i data-lucide="film" class="w-3.5 h-3.5"></i>
                            <span>Form Episode Baru (Season {{ $season->season_number }})</span>
                        </h5>
                        <form action="{{ route('admin.episodes.store', $season->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @csrf
                            <div>
                                <label class="block text-[10px] text-zinc-400 mb-1">Nomor Episode *</label>
                                <input type="number" name="episode_number" value="{{ $season->episodes->count() + 1 }}" required
                                       class="w-full bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-zinc-400 mb-1">Judul Episode *</label>
                                <input type="text" name="title" required placeholder="Contoh: Episode 1 - Permulaan"
                                       class="w-full bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-zinc-400 mb-1">Durasi (Menit)</label>
                                <input type="number" name="duration_minutes" value="24"
                                       class="w-full bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-[10px] text-zinc-400 mb-1">URL Video Stream Source (Link MovieBox / MP4 / HLS) *</label>
                                <input type="text" name="video_source" placeholder="https://... atau MovieBox resource ID"
                                       class="w-full bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            </div>
                            <div class="sm:col-span-3 flex justify-end gap-2 pt-2">
                                <button type="button" @click="addEpisodeOpen = false" class="px-3 py-1.5 rounded-lg bg-zinc-800 text-zinc-300 text-xs font-semibold">Batal</button>
                                <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-black font-bold text-xs shadow-md">Simpan Episode</button>
                            </div>
                        </form>
                    </div>

                    <!-- Episode Table List -->
                    <div class="overflow-x-auto rounded-xl border border-white/5">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-white/5 text-zinc-400 uppercase text-[9px] font-bold">
                                <tr>
                                    <th class="px-3 py-2">Eps</th>
                                    <th class="px-3 py-2">Judul Episode</th>
                                    <th class="px-3 py-2">Durasi</th>
                                    <th class="px-3 py-2">Video Stream Link</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @forelse($season->episodes as $episode)
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-3 py-2.5 font-bold font-mono text-purple-400">
                                            E{{ $episode->episode_number }}
                                        </td>
                                        <td class="px-3 py-2.5 text-white font-semibold">
                                            {{ $episode->title }}
                                        </td>
                                        <td class="px-3 py-2.5 text-zinc-400 font-mono">
                                            {{ $episode->duration_minutes }} mnt
                                        </td>
                                        <td class="px-3 py-2.5 text-zinc-400 font-mono text-[11px] truncate max-w-[200px]">
                                            {{ $episode->video_source ?: 'Proxy API' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right">
                                            <form action="{{ route('admin.episodes.destroy', $episode->id) }}" method="POST" onsubmit="return confirm('Hapus episode ini?')" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 rounded bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus Episode">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-zinc-500">Belum ada episode di season ini. Klik "Tambah Episode" di atas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="p-8 rounded-2xl bg-zinc-900/60 border border-white/10 text-center text-zinc-500 space-y-2">
                    <i data-lucide="layers" class="w-10 h-10 mx-auto text-zinc-600"></i>
                    <p class="text-sm font-semibold text-zinc-400">Belum ada season terdaftar</p>
                    <p class="text-xs">Gunakan form di atas untuk menambahkan Season 1.</p>
                </div>
            @endforelse

        </div>
    @endif

    <!-- Tab 3: Soundtrack & OST Manager -->
    <div x-show="activeTab === 'soundtracks'" 
         x-cloak
         x-data="soundtrackManager({{ json_encode($film->soundtracks) }}, {{ $film->id }}, '{{ addslashes($film->title) }}')" 
         class="space-y-6">
        
        <!-- Header & Action Switcher Bar -->
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-white font-['Outfit'] flex items-center gap-2">
                    <i data-lucide="disc" class="w-4 h-4 text-amber-400"></i>
                    <span>Soundtrack & Lagu Film (OST)</span>
                </h3>
                <p class="text-xs text-zinc-400 mt-0.5">Kelola daftar lagu resmi film ini secara manual atau cari cepat dari iTunes Music API.</p>
            </div>

            <div class="flex items-center gap-2 bg-zinc-950 p-1 rounded-xl border border-white/10 text-xs shrink-0">
                <button type="button" 
                        @click="mode = 'manual'" 
                        :class="mode === 'manual' ? 'bg-amber-500 text-zinc-950 font-bold shadow' : 'text-zinc-400 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Tambah Manual</span>
                </button>
                <button type="button" 
                        @click="mode = 'itunes'; if(searchResults.length === 0) searchItunes();" 
                        :class="mode === 'itunes' ? 'bg-amber-500 text-zinc-950 font-bold shadow' : 'text-zinc-400 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Cari di iTunes</span>
                </button>
            </div>
        </div>

        <!-- Mode 1: Manual Add Form Card -->
        <div x-show="mode === 'manual'" x-transition class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-5">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="music" class="w-4 h-4"></i>
                    <span>Form Input Lagu Manual</span>
                </h4>
                <span class="text-[11px] text-zinc-400">Lagu yang ditambahkan akan langsung tampil di halaman detail film & mobile app</span>
            </div>

            <form action="{{ route('admin.films.soundtracks.store', $film->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Track Name -->
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Judul Lagu *</label>
                        <input type="text" name="track_name" x-model="form.track_name" required placeholder="Contoh: Glimpse of Us / Tak Segampang Itu"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                    </div>

                    <!-- Artist Name -->
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Artis / Penyanyi *</label>
                        <input type="text" name="artist_name" x-model="form.artist_name" required placeholder="Contoh: Joji / Anggi Marito"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                    </div>

                    <!-- Collection / Album -->
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Nama Album (Opsional)</label>
                        <input type="text" name="collection_name" x-model="form.collection_name" placeholder="Contoh: SMITHEREENS / Original Soundtrack"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                    </div>

                    <!-- Order -->
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Nomor Urutan Lagu (Track #)</label>
                        <input type="number" name="order" value="{{ $film->soundtracks->count() + 1 }}" min="1"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                    </div>

                    <!-- Audio Source (URL / File Upload) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider">Audio Preview (Stream / MP3)</label>
                            <div class="flex items-center gap-2 text-[10px]">
                                <button type="button" @click="audioSourceType = 'url'" :class="audioSourceType === 'url' ? 'text-amber-400 font-bold underline' : 'text-zinc-500'">Link URL</button>
                                <span class="text-zinc-600">|</span>
                                <button type="button" @click="audioSourceType = 'file'" :class="audioSourceType === 'file' ? 'text-amber-400 font-bold underline' : 'text-zinc-500'">Upload File</button>
                            </div>
                        </div>

                        <div x-show="audioSourceType === 'url'">
                            <input type="url" name="preview_audio_url" x-model="form.preview_audio_url" placeholder="https://.../preview.mp3 (Apple Music / MP3 direct link)"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                        </div>

                        <div x-show="audioSourceType === 'file'" x-cloak>
                            <input type="file" name="audio_file" accept="audio/*"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-black hover:file:bg-amber-400 cursor-pointer">
                        </div>
                    </div>

                    <!-- Artwork Cover (URL / File Upload) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider">Artwork / Cover Album</label>
                            <div class="flex items-center gap-2 text-[10px]">
                                <button type="button" @click="artworkSourceType = 'url'" :class="artworkSourceType === 'url' ? 'text-amber-400 font-bold underline' : 'text-zinc-500'">Link URL</button>
                                <span class="text-zinc-600">|</span>
                                <button type="button" @click="artworkSourceType = 'file'" :class="artworkSourceType === 'file' ? 'text-amber-400 font-bold underline' : 'text-zinc-500'">Upload File</button>
                            </div>
                        </div>

                        <div x-show="artworkSourceType === 'url'">
                            <input type="url" name="artwork_url" x-model="form.artwork_url" placeholder="https://.../cover.jpg"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                        </div>

                        <div x-show="artworkSourceType === 'file'" x-cloak>
                            <input type="file" name="artwork_file" accept="image/*"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-black hover:file:bg-amber-400 cursor-pointer">
                        </div>
                    </div>

                    <!-- Track View URL (Spotify / Apple Music link) -->
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Link Eksternal / Spotify / Apple Music (Opsional)</label>
                        <input type="url" name="track_view_url" x-model="form.track_view_url" placeholder="https://open.spotify.com/track/... atau link streaming lengkap"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <!-- Live Card Preview -->
                <div x-show="form.track_name || form.artist_name" x-transition class="p-3.5 rounded-xl bg-zinc-950 border border-amber-500/30 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <img :src="form.artwork_url || 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=150'" 
                             class="w-10 h-10 rounded-lg object-cover bg-zinc-800 shrink-0 border border-white/10 shadow"
                             onerror="this.src='https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=150'">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-white truncate" x-text="form.track_name || 'Judul Lagu'"></p>
                            <p class="text-[11px] text-zinc-400 truncate" x-text="form.artist_name || 'Nama Artis'"></p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20 shrink-0">Live Preview</span>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="resetForm()" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-semibold">Reset</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs shadow-lg shadow-amber-500/20 flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Tambahkan Lagu</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Mode 2: iTunes Live Search & 1-Click Import -->
        <div x-show="mode === 'itunes'" x-transition class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Cari & Impor Langsung dari iTunes API</span>
                    </h4>
                    <p class="text-xs text-zinc-400 mt-0.5">Cari katalog lagu resmi dan tambahkan ke database film hanya dengan 1 klik.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="searchQuery = '{{ addslashes($film->title) }} soundtrack'; searchItunes()" 
                            class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-white border border-white/10 text-xs font-semibold flex items-center gap-1.5 cursor-pointer shrink-0">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>Cari OST Film Ini</span>
                    </button>
                    <button type="button" 
                            x-show="searchResults.length > 0"
                            @click="importAllSearchResults()" 
                            :disabled="isBatchImporting"
                            class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-zinc-950 font-bold text-xs flex items-center gap-1.5 cursor-pointer shadow shrink-0">
                        <template x-if="isBatchImporting">
                            <div class="w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full animate-spin"></div>
                        </template>
                        <template x-if="!isBatchImporting">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        </template>
                        <span>Impor Semua (<span x-text="searchResults.length"></span>)</span>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           @keydown.enter.prevent="searchItunes()"
                           placeholder="Ketik judul lagu, penyanyi, atau judul film..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                </div>
                <button type="button" 
                        @click="searchItunes()" 
                        :disabled="isSearching"
                        class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-zinc-950 font-bold text-xs flex items-center gap-1.5 cursor-pointer shadow-md">
                    <template x-if="isSearching">
                        <div class="w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full animate-spin"></div>
                    </template>
                    <template x-if="!isSearching">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </template>
                    <span>Cari</span>
                </button>
            </div>

            <!-- Search Results -->
            <div class="space-y-2 pt-2">
                <template x-if="isSearching">
                    <div class="text-center py-8 text-zinc-400 space-y-2">
                        <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                        <p class="text-xs">Mencari lagu di iTunes...</p>
                    </div>
                </template>

                <template x-if="!isSearching && searchResults.length === 0">
                    <div class="text-center py-6 text-zinc-500 text-xs">
                        Tidak ada hasil pencarian. Masukkan kata kunci dan tekan tombol Cari di atas.
                    </div>
                </template>

                <div x-show="!isSearching && searchResults.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto pr-1">
                    <template x-for="(st, idx) in searchResults" :key="idx">
                        <div class="p-3 rounded-xl bg-zinc-950 border border-white/10 hover:border-amber-500/40 transition-all flex items-center justify-between gap-3 group">
                            <div class="flex items-center gap-3 min-w-0">
                                <img :src="st.artwork_url || 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=150'" 
                                     :alt="st.track_name" 
                                     class="w-11 h-11 rounded-lg object-cover bg-zinc-900 shrink-0 border border-white/10 shadow">
                                <div class="min-w-0">
                                    <h5 class="text-xs font-bold text-white truncate group-hover:text-amber-300 transition-colors" x-text="st.track_name"></h5>
                                    <p class="text-[11px] text-zinc-400 truncate" x-text="st.artist_name"></p>
                                    <p class="text-[9.5px] text-zinc-500 truncate" x-text="st.collection_name"></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <!-- Preview Audio Button -->
                                <template x-if="st.preview_audio_url">
                                    <button type="button" 
                                            @click="togglePlay(st.preview_audio_url)" 
                                            :class="currentAudioUrl === st.preview_audio_url && isPlaying ? 'bg-amber-500 text-black' : 'bg-white/10 hover:bg-white/20 text-white'"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all cursor-pointer"
                                            title="Putar Cuplikan Audio">
                                        <i :data-lucide="currentAudioUrl === st.preview_audio_url && isPlaying ? 'pause' : 'play'" class="w-3.5 h-3.5"></i>
                                    </button>
                                </template>

                                <!-- 1-Click Import Button -->
                                <button type="button" 
                                        @click="importTrack(st)" 
                                        :disabled="isTrackImported(st)"
                                        :class="isTrackImported(st) ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30 cursor-default' : 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/30 cursor-pointer'"
                                        class="px-3 py-1.5 rounded-lg font-bold text-xs border flex items-center gap-1 transition-all"
                                        :title="isTrackImported(st) ? 'Lagu sudah ada di database film ini' : 'Impor lagu ini ke film'">
                                    <i :data-lucide="isTrackImported(st) ? 'check' : 'plus'" class="w-3.5 h-3.5"></i>
                                    <span x-text="isTrackImported(st) ? 'Terimpor' : 'Impor'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Section 3: Existing Songs List -->
        <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-4">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">
                        Daftar Lagu Film Terdaftar (<span x-text="soundtracks.length"></span> Lagu)
                    </h4>
                </div>
                <span class="text-[11px] text-zinc-400">Diurutkan berdasarkan prioritas tampilan</span>
            </div>

            <!-- List of Registered Soundtracks -->
            <div class="space-y-2.5">
                <template x-for="(track, idx) in soundtracks" :key="track.id">
                    <div class="p-3.5 rounded-xl bg-zinc-950 border border-white/10 hover:border-white/20 transition-all flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <!-- Track Number Badge -->
                            <span class="w-6 h-6 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center font-bold text-[11px] text-zinc-400 font-mono shrink-0" 
                                  x-text="track.order || (idx + 1)"></span>

                            <!-- Artwork Thumbnail -->
                            <img :src="track.artwork_url || 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=150'" 
                                 :alt="track.track_name" 
                                 class="w-11 h-11 rounded-xl object-cover bg-zinc-900 shrink-0 border border-white/10 shadow"
                                 onerror="this.src='https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=150'">

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h5 class="text-xs font-bold text-white truncate" x-text="track.track_name"></h5>
                                    <template x-if="track.track_view_url">
                                        <a :href="track.track_view_url" target="_blank" class="text-emerald-400 hover:text-emerald-300" title="Buka di Spotify / Web">
                                            <i data-lucide="external-link" class="w-3 h-3"></i>
                                        </a>
                                    </template>
                                </div>
                                <p class="text-[11px] text-zinc-400 truncate mt-0.5" x-text="track.artist_name"></p>
                                <template x-if="track.collection_name">
                                    <p class="text-[9.5px] text-zinc-500 truncate" x-text="track.collection_name"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Actions & Player Controls -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Play Audio Preview -->
                            <template x-if="track.preview_audio_url">
                                <button type="button" 
                                        @click="togglePlay(track.preview_audio_url)" 
                                        :class="currentAudioUrl === track.preview_audio_url && isPlaying ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20' : 'bg-white/5 hover:bg-white/15 text-white border border-white/10'"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer"
                                        title="Putar Audio Preview">
                                    <i :data-lucide="currentAudioUrl === track.preview_audio_url && isPlaying ? 'pause' : 'play'" class="w-3.5 h-3.5"></i>
                                </button>
                            </template>

                            <!-- Edit Track Modal Button -->
                            <button type="button" 
                                    @click="openEditModal(track)"
                                    class="p-2 rounded-xl bg-white/5 hover:bg-white/15 text-zinc-300 hover:text-white border border-white/10 transition-all cursor-pointer"
                                    title="Edit Lagu">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            </button>

                            <!-- Delete Track Form Button -->
                            <form :action="'/admin/soundtracks/' + track.id" method="POST" onsubmit="return confirm('Hapus lagu ini dari daftar film?')" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 transition-all cursor-pointer"
                                        title="Hapus Lagu">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </template>

                <!-- Smart Empty State with 1-Click Auto Import Callout -->
                <div x-show="soundtracks.length === 0" class="p-8 rounded-2xl bg-zinc-950 border border-amber-500/20 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center mx-auto">
                        <i data-lucide="disc" class="w-6 h-6 animate-spin"></i>
                    </div>
                    <div class="space-y-1">
                        <h5 class="text-sm font-bold text-white">Database Lagu Manual Masih Kosong (0 Lagu)</h5>
                        <p class="text-xs text-zinc-400 max-w-lg mx-auto">
                            Di halaman detail film publik saat ini sedang menampilkan lagu otomatis dari iTunes API. 
                            Anda dapat mengimpor lagu-lagu resmi tersebut ke database agar tersimpan permanen dan dapat diedit secara manual.
                        </p>
                    </div>
                    <div class="pt-2 flex flex-wrap justify-center gap-2">
                        <button type="button" 
                                @click="importAllFromItunes()" 
                                :disabled="isBatchImporting"
                                class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-zinc-950 font-bold text-xs shadow-lg shadow-amber-500/20 flex items-center gap-2 cursor-pointer transition-all">
                            <template x-if="isBatchImporting">
                                <div class="w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full animate-spin"></div>
                            </template>
                            <template x-if="!isBatchImporting">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </template>
                            <span>⚡ Impor Semua Lagu iTunes ke Database Sekarang</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Soundtrack Modal -->
        <div x-show="editModalOpen" 
             x-cloak 
             x-transition 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div @click.outside="editModalOpen = false" class="w-full max-w-lg bg-zinc-900 border border-white/15 rounded-2xl shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <i data-lucide="edit" class="w-4 h-4 text-amber-400"></i>
                        <span>Edit Lagu: <span x-text="editForm.track_name" class="text-amber-400"></span></span>
                    </h4>
                    <button type="button" @click="editModalOpen = false" class="p-1 text-zinc-400 hover:text-white rounded-lg">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form :action="'/admin/soundtracks/' + editForm.id" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Judul Lagu *</label>
                            <input type="text" name="track_name" x-model="editForm.track_name" required
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Artis / Penyanyi *</label>
                            <input type="text" name="artist_name" x-model="editForm.artist_name" required
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Nama Album</label>
                            <input type="text" name="collection_name" x-model="editForm.collection_name"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Nomor Urutan (Track #)</label>
                            <input type="number" name="order" x-model="editForm.order" min="1"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Audio Preview URL (atau upload audio baru)</label>
                            <input type="text" name="preview_audio_url" x-model="editForm.preview_audio_url"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Upload Audio Baru (Opsional)</label>
                            <input type="file" name="audio_file" accept="audio/*"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-2 py-1 text-[11px] text-zinc-400 file:py-0.5 file:px-2 file:rounded-md file:border-0 file:bg-amber-500 file:text-black">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Artwork URL</label>
                            <input type="text" name="artwork_url" x-model="editForm.artwork_url"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Upload Cover Baru (Opsional)</label>
                            <input type="file" name="artwork_file" accept="image/*"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-2 py-1 text-[11px] text-zinc-400 file:py-0.5 file:px-2 file:rounded-md file:border-0 file:bg-amber-500 file:text-black">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[10px] uppercase font-bold text-zinc-400 mb-1">Link Spotify / External</label>
                            <input type="url" name="track_view_url" x-model="editForm.track_view_url"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-semibold">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs shadow-md">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
if (typeof window.soundtrackManager !== 'function') {
    window.soundtrackManager = function(initialSoundtracks = [], filmId = 0, defaultFilmTitle = '') {
        return {
            mode: 'manual',
            soundtracks: initialSoundtracks,
            filmId: filmId,
            defaultFilmTitle: defaultFilmTitle,
            audioSourceType: 'url',
            artworkSourceType: 'url',
            form: {
                track_name: '',
                artist_name: '',
                collection_name: '',
                preview_audio_url: '',
                artwork_url: '',
                track_view_url: '',
                order: initialSoundtracks.length + 1
            },
            searchQuery: defaultFilmTitle ? defaultFilmTitle + ' soundtrack' : '',
            searchResults: [],
            isSearching: false,
            isBatchImporting: false,
            currentAudioUrl: null,
            isPlaying: false,
            audioObj: null,
            editModalOpen: false,
            editForm: {
                id: null,
                track_name: '',
                artist_name: '',
                collection_name: '',
                preview_audio_url: '',
                artwork_url: '',
                track_view_url: '',
                order: 1
            },

            resetForm() {
                this.form = {
                    track_name: '',
                    artist_name: '',
                    collection_name: '',
                    preview_audio_url: '',
                    artwork_url: '',
                    track_view_url: '',
                    order: this.soundtracks.length + 1
                };
            },

            isTrackImported(st) {
                const searchName = (st.track_name || '').toLowerCase().trim();
                return this.soundtracks.some(t => (t.track_name || '').toLowerCase().trim() === searchName);
            },

            togglePlay(url) {
                if (!url) return;

                if (this.currentAudioUrl === url && this.isPlaying) {
                    if (this.audioObj) this.audioObj.pause();
                    this.isPlaying = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    return;
                }

                if (this.audioObj) {
                    this.audioObj.pause();
                }

                this.currentAudioUrl = url;
                this.audioObj = new Audio(url);
                this.isPlaying = true;
                this.audioObj.play().catch(e => {
                    console.error('Audio play error:', e);
                    this.isPlaying = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                });

                this.audioObj.onended = () => {
                    this.isPlaying = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                };

                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            async searchItunes() {
                const q = this.searchQuery.trim();
                if (q.length < 2) {
                    this.searchResults = [];
                    return;
                }

                this.isSearching = true;
                try {
                    const res = await fetch('/admin/soundtracks/search-api?q=' + encodeURIComponent(q));
                    if (res.ok) {
                        const data = await res.json();
                        this.searchResults = data;
                    }
                } catch (err) {
                    console.error('Search itunes error:', err);
                } finally {
                    this.isSearching = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            },

            async importTrack(st) {
                if (this.isTrackImported(st)) return;

                const formData = new FormData();
                formData.append('track_name', st.track_name || '');
                formData.append('artist_name', st.artist_name || '');
                formData.append('collection_name', st.collection_name || '');
                formData.append('preview_audio_url', st.preview_audio_url || '');
                formData.append('artwork_url', st.artwork_url || '');
                formData.append('track_view_url', st.track_view_url || '');
                formData.append('order', this.soundtracks.length + 1);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const res = await fetch('/admin/films/' + this.filmId + '/soundtracks', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.soundtrack) {
                            this.soundtracks.push(data.soundtrack);
                        }
                    } else {
                        this.form.track_name = st.track_name || '';
                        this.form.artist_name = st.artist_name || '';
                        this.form.collection_name = st.collection_name || '';
                        this.form.preview_audio_url = st.preview_audio_url || '';
                        this.form.artwork_url = st.artwork_url || '';
                        this.form.track_view_url = st.track_view_url || '';
                        this.mode = 'manual';
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            },

            async importAllSearchResults() {
                if (this.searchResults.length === 0) return;
                this.isBatchImporting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const res = await fetch('/admin/films/' + this.filmId + '/soundtracks/import-batch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ tracks: this.searchResults })
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.soundtracks) {
                            this.soundtracks = data.soundtracks;
                        }
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    this.isBatchImporting = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            },

            async importAllFromItunes() {
                this.isBatchImporting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const res = await fetch('/admin/films/' + this.filmId + '/soundtracks/import-batch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.soundtracks) {
                            this.soundtracks = data.soundtracks;
                        }
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    this.isBatchImporting = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            },

            openEditModal(track) {
                this.editForm = {
                    id: track.id,
                    track_name: track.track_name,
                    artist_name: track.artist_name,
                    collection_name: track.collection_name || '',
                    preview_audio_url: track.preview_audio_url || '',
                    artwork_url: track.artwork_url || '',
                    track_view_url: track.track_view_url || '',
                    order: track.order || 1
                };
                this.editModalOpen = true;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        };
    };
}
</script>
@endsection
