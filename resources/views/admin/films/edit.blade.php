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

        @if($film->subject_type === 'series')
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

                <!-- Actors / Cast -->
                <div class="md:col-span-2 space-y-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Pemeran Utama (Cast)</label>
                    @php 
                        $actorPivotMap = $film->actors->keyBy('id')->map(fn($a) => $a->pivot->character_name)->toArray();
                        $selectedActorIds = array_keys($actorPivotMap);
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl bg-zinc-950 border border-white/10 max-h-60 overflow-y-auto">
                        @foreach($actors as $actor)
                            @php $isAttached = in_array($actor->id, old('actors', $selectedActorIds)); @endphp
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-zinc-900/60 border border-white/5">
                                <input type="checkbox" name="actors[]" value="{{ $actor->id }}" id="actor-edit-{{ $actor->id }}" {{ $isAttached ? 'checked' : '' }}
                                       class="rounded border-white/20 bg-zinc-900 text-amber-500 focus:ring-amber-500">
                                <label for="actor-edit-{{ $actor->id }}" class="text-xs font-bold text-white flex-1 cursor-pointer truncate">
                                    {{ $actor->name }}
                                </label>
                                <input type="text" name="actor_characters[{{ $actor->id }}]" value="{{ old("actor_characters.{$actor->id}", $actorPivotMap[$actor->id] ?? '') }}" placeholder="Karakter..." 
                                       class="w-28 bg-zinc-950 border border-white/10 rounded-lg px-2 py-1 text-[11px] text-white focus:outline-none focus:border-amber-500">
                            </div>
                        @endforeach
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
    @if($film->subject_type === 'series')
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
                    <div x-show="addEpisodeOpen" x-collapse class="p-4 rounded-xl bg-zinc-950 border border-emerald-500/30 space-y-3">
                        <h5 class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                            <i data-lucide="film" class="w-3.5 h-3.5"></i>
                            <span>Form Episode Baru (Season {{ $season->season_number }})</span>
                        </h5>
                        <form action="{{ route('admin.seasons.episodes.store', $season->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
</div>
@endsection
