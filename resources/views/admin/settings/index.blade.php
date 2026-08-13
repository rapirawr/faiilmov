@extends('layouts.admin')

@section('title', 'Pengaturan Umum | faiiladmin')
@section('page_title', 'Pengaturan Umum & Konfigurasi Situs')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <!-- Flash Alerts System -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-400"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 text-rose-400"></i>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
        <div class="flex items-center gap-3 border-b border-zinc-800 pb-4">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                <i data-lucide="settings" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-white font-['Outfit']">Konfigurasi Situs faiilmov</h3>
                <p class="text-xs text-zinc-400">Atur preferensi nama situs, meta deskripsi, logo, dan konten pilihan di Hero Slider.</p>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Site Name -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Situs *</label>
                <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required 
                       class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500 font-medium">
            </div>

            <!-- Site Description -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Deskripsi Situs (Meta Description)</label>
                <textarea name="site_description" rows="3" 
                          class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs text-white focus:outline-none focus:border-amber-500 leading-relaxed">{{ old('site_description', $siteDescription) }}</textarea>
                <p class="text-[11px] text-zinc-500 mt-1">Teks ringkasan SEO yang muncul pada hasil pencarian Google & pembagian link.</p>
            </div>

            <!-- Logo Upload -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Upload Logo Situs Baru</label>
                <input type="file" name="logo" accept="image/*" 
                       class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-white hover:file:bg-zinc-700 cursor-pointer">
            </div>

            <!-- Featured Films in Hero Carousel -->
            <div x-data="{ filmSearch: '', typeFilter: 'all' }" class="space-y-3 pt-2 border-t border-zinc-800">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Film Featured di Hero Banner</label>
                        <p class="text-[11px] text-zinc-500">Pilih film yang akan ditampilkan pada slider Hero utama halaman Home.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Type Filter Pills -->
                        <div class="flex items-center bg-zinc-950 p-1 rounded-full border border-zinc-800 text-[10px]">
                            <button type="button" @click="typeFilter = 'all'" :class="typeFilter === 'all' ? 'bg-amber-400 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full uppercase transition-colors cursor-pointer">Semua</button>
                            <button type="button" @click="typeFilter = 'movie'" :class="typeFilter === 'movie' ? 'bg-amber-400 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full uppercase transition-colors cursor-pointer">Movie</button>
                            <button type="button" @click="typeFilter = 'series'" :class="typeFilter === 'series' ? 'bg-amber-400 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full uppercase transition-colors cursor-pointer">Series</button>
                        </div>

                        <!-- Search Input Bar -->
                        <div class="relative w-full sm:w-48">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" 
                                   x-model="filmSearch" 
                                   placeholder="Cari judul..." 
                                   class="w-full bg-zinc-950 border border-zinc-800 rounded-full pl-8 pr-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                        </div>
                    </div>
                </div>

                <!-- Tile Grid Container -->
                <div class="p-3 bg-zinc-950 border border-zinc-800 rounded-2xl max-h-[440px] overflow-y-auto scrollbar-thin">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($allFilms as $f)
                            @php $isSelected = in_array($f->id, old('featured_film_ids', $featuredIds)); @endphp
                            <label x-show="(typeFilter === 'all' || typeFilter === '{{ $f->subject_type }}') && (!filmSearch || '{{ strtolower(addslashes($f->title)) }}'.includes(filmSearch.toLowerCase()))" 
                                   class="relative group cursor-pointer select-none">
                                <input type="checkbox" name="featured_film_ids[]" value="{{ $f->id }}" {{ $isSelected ? 'checked' : '' }} class="peer hidden">
                                
                                <div class="h-full border border-zinc-800 rounded-xl overflow-hidden bg-zinc-900/80 transition-all duration-200 
                                            peer-checked:border-amber-400 peer-checked:ring-2 peer-checked:ring-amber-400/40 peer-checked:bg-zinc-800 
                                            group-hover:border-zinc-700 flex flex-col justify-between">
                                    
                                    <!-- Poster Image -->
                                    <div class="relative w-full aspect-[2/3] overflow-hidden bg-zinc-950">
                                        <img src="{{ $f->poster_url }}" alt="{{ $f->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        
                                        <!-- Selection Badge Overlay -->
                                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full bg-amber-400 text-zinc-950 font-extrabold flex items-center justify-center shadow-lg transition-all transform scale-0 peer-checked:scale-100">
                                            <i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>
                                        </div>

                                        <!-- Subject Type Badge -->
                                        <div class="absolute bottom-2 left-2 px-1.5 py-0.5 rounded bg-zinc-950/80 border border-zinc-800 text-[9px] font-extrabold uppercase text-white tracking-wider">
                                            {{ strtoupper($f->subject_type) }}
                                        </div>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="p-2.5 space-y-1">
                                        <p class="font-bold text-white text-xs line-clamp-1 leading-snug group-hover:text-amber-400 transition-colors">{{ $f->title }}</p>
                                        <p class="text-[10px] text-zinc-400 font-mono">{{ $f->release_year }}</p>
                                    </div>

                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-zinc-800">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
