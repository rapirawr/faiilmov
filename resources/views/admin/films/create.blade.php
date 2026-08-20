@extends('layouts.admin')

@section('title', 'Tambah Film Manual | faiiladmin')
@section('page_title', 'Tambah Film Manual')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="filmCreateForm()">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-white font-['Outfit']">Form Tambah Film Baru</h2>
        <a href="{{ route('admin.films.index') }}" class="text-xs text-zinc-400 hover:text-white flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- Quick IMDb Auto-Fill Banner -->
    <div class="p-4 sm:p-5 rounded-2xl bg-zinc-900/90 border border-amber-500/30 shadow-lg space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-400">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <h3 class="text-xs font-bold uppercase tracking-wider font-['Outfit']">Auto-Fill dari Link IMDb</h3>
            </div>
            <span class="text-[11px] text-zinc-400">Otomatis mengisi form, pemeran & melacak OST</span>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="relative flex-1 w-full">
                <i data-lucide="link" class="w-4 h-4 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" x-model="imdbUrl" @keydown.enter.prevent="fetchFromImdb()"
                       placeholder="Masukkan link/ID IMDb (contoh: https://www.imdb.com/title/tt1375666/ atau tt1375666)..." 
                       class="w-full bg-zinc-950 border border-white/15 focus:border-amber-500 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-zinc-500 outline-none transition-all">
            </div>

            <button type="button" @click="fetchFromImdb()" :disabled="imdbLoading || !imdbUrl.trim()"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-zinc-950 font-bold text-xs flex items-center justify-center gap-2 shadow-md shadow-amber-500/20 transition-all cursor-pointer shrink-0">
                <i data-lucide="search" class="w-3.5 h-3.5" x-show="!imdbLoading"></i>
                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="imdbLoading" style="display:none;"></i>
                <span x-text="imdbLoading ? 'Mengambil Data...' : 'Auto-Fill Form'"></span>
            </button>
        </div>

        <!-- Feedback Alerts -->
        <div x-show="imdbError" x-transition class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-center gap-2" style="display: none;">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-red-400"></i>
            <span x-text="imdbError"></span>
        </div>

        <div x-show="imdbSuccess" x-transition class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2" style="display: none;">
            <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 text-emerald-400"></i>
            <span x-text="imdbSuccess"></span>
        </div>
    </div>

    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl">
        <form action="{{ route('admin.films.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Judul Film *</label>
                    <input type="text" name="title" x-model="title" required placeholder="Contoh: Avatar: The Way of Water" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                    @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tipe *</label>
                    <select name="subject_type" x-model="subjectType" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="movie">Movie</option>
                        <option value="series">Series</option>
                        <option value="dracin">Drama China (Dracin)</option>
                    </select>
                </div>

                <!-- Content Rating (Age Rating) -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Rating Usia (Content Rating)</label>
                    <select name="content_rating" x-model="contentRating" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="">-- Belum Ditentukan (Unrated) --</option>
                        <option value="SU">SU - Semua Umur</option>
                        <option value="G">G - General Audience</option>
                        <option value="PG">PG - Parental Guidance</option>
                        <option value="13+">13+ - Remaja</option>
                        <option value="16+">16+ - Dewasa Muda</option>
                        <option value="18+">18+ - Dewasa</option>
                    </select>
                </div>

                <!-- Max Resolution -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Kualitas Maksimum</label>
                    <select name="max_resolution" x-model="maxResolution" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="1080P">1080P Full HD</option>
                        <option value="4K">4K Ultra HD</option>
                        <option value="720P">720P HD</option>
                        <option value="480P">480P SD</option>
                    </select>
                </div>

                <!-- Release Year -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tahun Rilis</label>
                    <input type="number" name="release_year" x-model="releaseYear" placeholder="2026" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Durasi (Menit)</label>
                    <input type="number" name="duration_minutes" x-model="durationMinutes" placeholder="120" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Rating -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Rating IMDb / Web (0 - 10)</label>
                    <input type="number" step="0.1" name="rating" x-model="rating" placeholder="8.5" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- View Count -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Jumlah View Awal</label>
                    <input type="number" name="view_count" value="{{ old('view_count', 0) }}" placeholder="0" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Trailer URL -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">URL Trailer YouTube</label>
                    <input type="url" name="trailer_url" x-model="trailerUrl" placeholder="https://www.youtube.com/watch?v=..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Poster File Upload & URL -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Upload Poster (File)</label>
                    <input type="file" name="poster" accept="image/*" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-black hover:file:bg-amber-400 cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Atau Poster URL (Link)</label>
                    <input type="url" name="poster_url" x-model="posterUrl" placeholder="https://..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Backdrop Image URL -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Backdrop Image URL (Banner)</label>
                    <input type="url" name="backdrop_url" x-model="backdropUrl" placeholder="https://..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Status Coming Soon & Available From -->
                <div class="md:col-span-2 p-4 rounded-2xl bg-zinc-950/80 border border-amber-500/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-2">
                                <i data-lucide="calendar-clock" class="w-4 h-4 text-amber-400"></i>
                                <span>Status Coming Soon (Segera Hadir)</span>
                            </h4>
                            <p class="text-[11px] text-zinc-400 mt-0.5">Tandai jika film belum dirilis. Tombol play/nonton akan disembunyikan sampai film rilis.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_coming_soon" value="1" {{ old('is_coming_soon') ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 mb-1">Tanggal Rilis Spesifik (Optional / Available From)</label>
                        <input type="datetime-local" name="available_from" value="{{ old('available_from') }}" 
                               class="w-full bg-zinc-900 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="md:col-span-2 p-4 rounded-xl bg-zinc-950 border border-white/10 flex flex-col sm:flex-row items-center gap-4">
                    <img :src="posterUrl" class="w-16 h-24 object-cover rounded-lg shrink-0 border border-white/10 shadow" onerror="this.src='https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600'">
                    <div class="space-y-1 text-xs">
                        <span class="text-amber-400 font-bold">Preview Poster & Backdrop</span>
                        <p class="text-zinc-400">Pastikan URL gambar valid sebelum menyimpan film.</p>
                    </div>
                </div>

                <!-- Synopsis with AI Toolbar -->
                <div class="md:col-span-2 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Sinopsis</label>
                        
                        <!-- AI Tools Buttons -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <!-- Generate AI -->
                            <button type="button" 
                                    @click="runAiSynopsis('generate')"
                                    :disabled="isAiWorking"
                                    class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-bold flex items-center gap-1 transition-all cursor-pointer disabled:opacity-50"
                                    title="Generate sinopsis menarik otomatis dengan AI">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5" :class="isAiWorking ? 'animate-spin' : ''"></i>
                                <span>Generate AI</span>
                            </button>

                            <!-- Translate to Indonesian -->
                            <button type="button" 
                                    @click="runAiSynopsis('translate')"
                                    :disabled="isAiWorking || !synopsis"
                                    class="px-2.5 py-1 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-300 border border-blue-500/30 text-[11px] font-bold flex items-center gap-1 transition-all cursor-pointer disabled:opacity-50"
                                    title="Terjemahkan sinopsis yang ada ke Bahasa Indonesia">
                                <i data-lucide="languages" class="w-3.5 h-3.5"></i>
                                <span>Terjemahkan (ID)</span>
                            </button>

                            <!-- Shorten / Ringkas -->
                            <button type="button" 
                                    @click="runAiSynopsis('shorten')"
                                    :disabled="isAiWorking || !synopsis"
                                    class="px-2.5 py-1 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[11px] font-bold flex items-center gap-1 transition-all cursor-pointer disabled:opacity-50"
                                    title="Ringkas sinopsis menjadi lebih padat">
                                <i data-lucide="scissors" class="w-3.5 h-3.5"></i>
                                <span>Ringkas Padat</span>
                            </button>
                        </div>
                    </div>

                    <textarea name="synopsis" rows="4" x-model="synopsis" placeholder="Tuliskan ringkasan sinopsis film di sini..." 
                              class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-amber-500 leading-relaxed"></textarea>
                </div>

                <!-- Genres -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Pilih Genre</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-4 rounded-xl bg-zinc-950 border border-white/10 max-h-48 overflow-y-auto">
                        @foreach($genres as $g)
                            <label class="flex items-center gap-2 text-xs text-zinc-300 hover:text-white cursor-pointer">
                                <input type="checkbox" name="genres[]" value="{{ $g->id }}" {{ is_array(old('genres')) && in_array($g->id, old('genres')) ? 'checked' : '' }} class="rounded border-white/20 bg-zinc-900 text-amber-500 focus:ring-amber-500">
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
                <div class="md:col-span-2 space-y-3" x-data="castPicker([])">
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

            <!-- Bottom Spacer for Floating Bar -->
            <div class="h-20"></div>

            <!-- Floating Bottom-Right Save Action Bar -->
            <div class="fixed bottom-6 right-6 z-40 flex items-center gap-2 bg-zinc-900/95 backdrop-blur-xl border border-white/15 p-2 sm:p-2.5 rounded-2xl shadow-2xl shadow-black/90 ring-1 ring-white/10 hover:border-amber-500/50 transition-all">
                <a href="{{ route('admin.films.index') }}" class="px-3.5 sm:px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Batal</span>
                </a>
                <button type="submit" class="px-5 sm:px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold text-xs shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan Film</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function filmCreateForm() {
    return {
        imdbUrl: '',
        imdbLoading: false,
        imdbError: '',
        imdbSuccess: '',
        title: @json(old('title', '')),
        subjectType: @json(old('subject_type', 'movie')),
        contentRating: @json(old('content_rating', '')),
        maxResolution: @json(old('max_resolution', '1080P')),
        releaseYear: @json(old('release_year', date('Y'))),
        durationMinutes: @json(old('duration_minutes', 120)),
        rating: @json(old('rating', 8.5)),
        trailerUrl: @json(old('trailer_url', '')),
        posterUrl: @json(old('poster_url', 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600')),
        backdropUrl: @json(old('backdrop_url', '')),
        synopsis: @json(old('synopsis', '')),
        async fetchFromImdb() {
            if (!this.imdbUrl.trim()) {
                this.imdbError = 'Silakan masukkan link atau ID IMDb terlebih dahulu.';
                return;
            }
            this.imdbLoading = true;
            this.imdbError = '';
            this.imdbSuccess = '';
            try {
                const res = await fetch('{{ route('admin.films.fetch_imdb') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ imdb_url: this.imdbUrl.trim() })
                });
                const result = await res.json();
                if (!res.ok || result.status !== 'ok') {
                    this.imdbError = result.message || 'Gagal mengambil data dari link IMDb.';
                } else {
                    const d = result.data;
                    this.title = d.title || this.title;
                    this.synopsis = d.synopsis || this.synopsis;
                    this.releaseYear = d.release_year || this.releaseYear;
                    this.durationMinutes = d.duration_minutes || this.durationMinutes;
                    this.rating = d.rating || this.rating;
                    this.contentRating = d.content_rating || this.contentRating;
                    this.subjectType = d.subject_type || this.subjectType;
                    this.posterUrl = d.poster_url || this.posterUrl;
                    this.backdropUrl = d.backdrop_url || this.backdropUrl;
                    this.trailerUrl = d.trailer_url || this.trailerUrl;
                    
                    // Check matching genres
                    if (Array.isArray(d.genre_ids)) {
                        document.querySelectorAll('input[name=\'genres[]\']').forEach(chk => {
                            chk.checked = d.genre_ids.includes(parseInt(chk.value));
                        });
                    }

                    const ostCount = d.soundtracks ? d.soundtracks.length : 0;
                    const castCount = d.actors ? d.actors.length : 0;
                    this.imdbSuccess = `Berhasil menarik metadata film '${d.title}' (${d.release_year})! ${castCount} pemeran dan ${ostCount} OST lagu siap disinkronkan.`;
                    setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
                }
            } catch (e) {
                this.imdbError = 'Terjadi kesalahan saat memproses: ' + e.message;
            } finally {
                this.imdbLoading = false;
            }
        },
        isAiWorking: false,
        async runAiSynopsis(action) {
            this.isAiWorking = true;
            try {
                const res = await fetch('{{ route('admin.films.ai_synopsis_tools') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        title: this.title || 'Film',
                        synopsis: this.synopsis,
                        tone: 'cinematic'
                    })
                });
                const data = await res.json();
                if (data.success && data.synopsis) {
                    this.synopsis = data.synopsis;
                } else if (data.message) {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isAiWorking = false;
            }
        }
    };
}
</script>
@endsection
