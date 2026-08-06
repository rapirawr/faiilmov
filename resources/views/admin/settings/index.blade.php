@extends('layouts.admin')

@section('title', 'Pengaturan Umum | faiiladmin')
@section('page_title', 'Pengaturan Umum Situs')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl space-y-6">
        <h3 class="text-base font-bold text-white font-['Outfit'] border-b border-white/10 pb-3">Konfigurasi Situs faiilmov</h3>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Site Name -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Situs *</label>
                <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required 
                       class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/50">
            </div>

            <!-- Site Description -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Deskripsi Situs (Meta Description)</label>
                <textarea name="site_description" rows="3" 
                          class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-white/50">{{ old('site_description', $siteDescription) }}</textarea>
            </div>

            <!-- Logo Upload -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Upload Logo Situs Baru</label>
                <input type="file" name="logo" accept="image/*" 
                       class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-white file:text-black hover:file:bg-zinc-200 cursor-pointer">
            </div>

            <!-- Featured Films in Hero Carousel (Tile Grid Cards with Type & Search Filters) -->
            <div x-data="{ filmSearch: '', typeFilter: 'all' }">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Film Featured di Hero Banner</label>
                        <p class="text-[11px] text-zinc-500">Pilih film yang akan ditampilkan pada slider Hero utama halaman Home.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Type Filter Pills -->
                        <div class="flex items-center bg-zinc-950 p-1 rounded-full border border-white/15">
                            <button type="button" @click="typeFilter = 'all'" :class="typeFilter === 'all' ? 'bg-white text-black font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full text-[10px] uppercase transition-colors cursor-pointer">Semua</button>
                            <button type="button" @click="typeFilter = 'movie'" :class="typeFilter === 'movie' ? 'bg-white text-black font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full text-[10px] uppercase transition-colors cursor-pointer">Movie</button>
                            <button type="button" @click="typeFilter = 'series'" :class="typeFilter === 'series' ? 'bg-white text-black font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full text-[10px] uppercase transition-colors cursor-pointer">Series</button>
                        </div>

                        <!-- Search Input Bar for Tile Cards -->
                        <div class="relative w-full sm:w-52">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" 
                                   x-model="filmSearch" 
                                   placeholder="Cari judul..." 
                                   class="w-full bg-zinc-950 border border-white/15 rounded-full pl-8 pr-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/50">
                        </div>
                    </div>
                </div>

                <!-- Tile Grid Container -->
                <div class="p-3 bg-zinc-950/90 border border-white/15 rounded-2xl max-h-[480px] overflow-y-auto scrollbar-thin scrollbar-thumb-zinc-800">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($allFilms as $f)
                            @php $isSelected = in_array($f->id, old('featured_film_ids', $featuredIds)); @endphp
                            <label x-show="(typeFilter === 'all' || typeFilter === '{{ $f->subject_type }}') && (!filmSearch || '{{ strtolower(addslashes($f->title)) }}'.includes(filmSearch.toLowerCase()))" 
                                   class="relative group cursor-pointer select-none">
                                <input type="checkbox" name="featured_film_ids[]" value="{{ $f->id }}" {{ $isSelected ? 'checked' : '' }} class="peer hidden">
                                
                                <div class="h-full border border-white/10 rounded-xl overflow-hidden bg-zinc-900/80 transition-all duration-200 
                                            peer-checked:border-white peer-checked:ring-2 peer-checked:ring-white/40 peer-checked:bg-zinc-800 
                                            group-hover:border-white/30 flex flex-col justify-between">
                                    
                                    <!-- Poster Image -->
                                    <div class="relative w-full aspect-[2/3] overflow-hidden bg-zinc-950">
                                        <img src="{{ $f->poster_url }}" alt="{{ $f->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        
                                        <!-- Selection Badge Overlay -->
                                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full bg-white text-black font-extrabold flex items-center justify-center shadow-lg transition-all transform scale-0 peer-checked:scale-100">
                                            <i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>
                                        </div>

                                        <!-- Subject Type Badge -->
                                        <div class="absolute bottom-2 left-2 px-1.5 py-0.5 rounded bg-black/80 backdrop-blur-md text-[9px] font-extrabold uppercase text-white tracking-wider border border-white/10">
                                            {{ strtoupper($f->subject_type) }}
                                        </div>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="p-2.5 space-y-1">
                                        <p class="font-bold text-white text-xs line-clamp-1 leading-snug group-hover:text-zinc-200 transition-colors">{{ $f->title }}</p>
                                        <p class="text-[10px] text-zinc-400 font-mono">{{ $f->release_year }}</p>
                                    </div>

                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-white/10">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-black font-bold text-xs shadow-lg shadow-white/10 transition-all cursor-pointer">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

</div>
@endsection
