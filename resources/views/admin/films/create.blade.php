@extends('layouts.admin')

@section('title', 'Tambah Film Manual | faiiladmin')
@section('page_title', 'Tambah Film Manual')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-white font-['Outfit']">Form Tambah Film Baru</h2>
        <a href="{{ route('admin.films.index') }}" class="text-xs text-zinc-400 hover:text-white flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl">
        <form action="{{ route('admin.films.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Judul Film *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Contoh: Avatar: The Way of Water" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                    @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tipe *</label>
                    <select name="subject_type" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="movie" {{ old('subject_type') === 'movie' ? 'selected' : '' }}>Movie</option>
                        <option value="series" {{ old('subject_type') === 'series' ? 'selected' : '' }}>Series</option>
                    </select>
                </div>

                <!-- Release Year -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tahun Rilis</label>
                    <input type="number" name="release_year" value="{{ old('release_year', date('Y')) }}" placeholder="2026" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Durasi (Menit)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 120) }}" placeholder="120" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Rating -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Rating (0 - 10)</label>
                    <input type="number" step="0.1" name="rating" value="{{ old('rating', 8.5) }}" placeholder="8.5" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Trailer URL -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">URL Trailer YouTube</label>
                    <input type="url" name="trailer_url" value="{{ old('trailer_url') }}" placeholder="https://www.youtube.com/watch?v=..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Poster Image Upload or URL -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Upload Poster (File)</label>
                    <input type="file" name="poster" accept="image/*" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-black hover:file:bg-amber-400 cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Atau Poster URL (Link)</label>
                    <input type="url" name="poster_url" value="{{ old('poster_url') }}" placeholder="https://..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Backdrop URL -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Backdrop Image URL (Link)</label>
                    <input type="url" name="backdrop_url" value="{{ old('backdrop_url') }}" placeholder="https://..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Synopsis -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Sinopsis</label>
                    <textarea name="synopsis" rows="4" placeholder="Tuliskan ringkasan sinopsis film di sini..." 
                              class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-amber-500">{{ old('synopsis') }}</textarea>
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
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                <a href="{{ route('admin.films.index') }}" class="px-5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs transition-colors">Batal</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer">Simpan Film</button>
            </div>
        </form>
    </div>
</div>
@endsection
