@extends('layouts.app')

@section('title', $collection->name . ' - Koleksi Film Faiilmov')
@section('meta_description', Str::limit(strip_tags($collection->description ?: 'Nonton maraton koleksi film ' . $collection->name . ' kualitas HD subtitle Indonesia gratis di Faiilmov.'), 150))
@section('og_image', $collection->cover_image ?: asset('images/logo.png'))

@section('content')
<div class="min-h-screen pb-16 space-y-8" x-data="{ activeTab: '{{ $hasWatchOrders ? 'watch_order' : 'catalog' }}' }">

    <!-- Hero Backdrop Header -->
    <div class="relative w-full min-h-[320px] sm:min-h-[380px] lg:min-h-[440px] flex items-end bg-zinc-950 overflow-hidden">
        @if($collection->cover_image)
            <img src="{{ $collection->cover_image }}" 
                 alt="{{ $collection->name }}" 
                 class="absolute inset-0 w-full h-full object-cover object-center opacity-40 blur-sm scale-105" />
        @endif

        <!-- Deep Dark Overlay Gradients -->
        <div class="absolute inset-0 bg-gradient-to-t from-dark-950 via-dark-950/80 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-dark-950 via-dark-950/60 to-transparent"></div>

        <!-- Header Content Container -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 w-full space-y-4">
            @if($collection->isTakenDown())
                <div class="p-4 rounded-2xl bg-red-500/15 border border-red-500/40 text-red-200 flex items-start gap-3 text-xs">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-red-400 shrink-0 mt-0.5"></i>
                    <div class="space-y-1">
                        <p class="font-extrabold text-red-300 text-sm">Koleksi Ini Telah Di-takedown oleh Admin Faiilmov</p>
                        <p>Alasan: <strong class="text-white">{{ $collection->takedown_reason ?: 'Melanggar Pedoman Komunitas' }}</strong></p>
                        <p class="text-[11px] text-red-300/80">Koleksi ini disembunyikan dari katalog publik. Hanya Anda sebagai pemilik dan admin yang dapat mengakses halaman ini.</p>
                    </div>
                </div>
            @endif

            <!-- Breadcrumbs & Badges -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('collections.index') }}" class="text-zinc-400 hover:text-white transition-colors flex items-center gap-1">
                    <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                    <span>Semua Koleksi</span>
                </a>
                <span class="text-zinc-600">/</span>
                <span class="px-2.5 py-0.5 rounded-md font-mono font-bold uppercase text-[10px] bg-zinc-800 text-zinc-300 border border-white/10">
                    {{ $collection->type === 'auto' ? 'Franchise Semesta' : ($collection->type === 'prompt' ? 'Kurasi AI' : 'Koleksi Komunitas') }}
                </span>

                @if($collection->isTakenDown())
                    <span class="px-2.5 py-0.5 rounded-md font-bold uppercase text-[10px] bg-red-500/20 text-red-400 border border-red-500/30">
                        🚫 Takedown
                    </span>
                @elseif($collection->status !== 'published')
                    <span class="px-2.5 py-0.5 rounded-md font-bold uppercase text-[10px] {{ $collection->status === 'private' ? 'bg-zinc-800 text-zinc-300 border border-white/10' : 'bg-zinc-700/80 text-zinc-300 border border-zinc-600' }}">
                        {{ $collection->status === 'private' ? '🔒 Private' : '📝 Draft' }}
                    </span>
                @endif

                <span class="px-2.5 py-0.5 rounded-md font-mono font-bold text-[10px] bg-zinc-800/80 text-zinc-300 border border-white/10">
                    {{ $collection->films->count() }} Film
                </span>
            </div>

            <!-- Collection Title -->
            <h1 class="font-serif font-extrabold text-3xl sm:text-4xl lg:text-5xl text-white tracking-tight leading-tight max-w-4xl">
                {{ $collection->name }}
            </h1>

            <!-- Description -->
            @if($collection->description)
                <p class="text-zinc-300 text-sm sm:text-base max-w-3xl leading-relaxed">
                    {{ $collection->description }}
                </p>
            @endif

            <!-- Metadata, Creator & Owner Actions -->
            <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-white/10 text-xs text-zinc-400">
                <div class="flex items-center gap-3">
                    @if($collection->creator)
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-zinc-800 border border-white/10 flex items-center justify-center font-extrabold text-white text-xs">
                                {{ strtoupper(substr($collection->creator->name, 0, 1)) }}
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-zinc-200 font-semibold">{{ $collection->creator->name }}</span>
                                @if($collection->creator->role === 'admin')
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-white/10 text-zinc-300 border border-white/10">
                                        ADMIN
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-zinc-400">
                            <div class="w-7 h-7 rounded-full bg-zinc-800 border border-white/10 flex items-center justify-center font-mono text-[10px] text-zinc-400">
                                SYS
                            </div>
                            <span>Kurasi Otomatis Faiilmov</span>
                        </div>
                    @endif
                </div>

                @if($canEdit)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('collections.edit', $collection->slug) }}" 
                           class="px-4 py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-extrabold shadow-lg shadow-white/10 transition flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Edit di Studio Editor</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Navigation Tabs (Katalog vs Urutan Nonton) -->
        <div class="flex items-center gap-3 border-b border-white/10 pb-4">
            @if($hasWatchOrders)
                <button
                    @click="activeTab = 'watch_order'"
                    :class="activeTab === 'watch_order' ? 'bg-white text-zinc-950 shadow-md font-extrabold' : 'bg-zinc-900/80 text-zinc-400 hover:text-white border border-white/5 font-semibold'"
                    class="px-5 py-2.5 rounded-2xl text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer"
                >
                    <i data-lucide="compass" class="w-4 h-4"></i>
                    <span>Panduan Urutan Nonton</span>
                </button>
            @endif

            <button
                @click="activeTab = 'catalog'"
                :class="activeTab === 'catalog' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'bg-zinc-900/80 text-zinc-400 hover:text-white border border-white/5 font-semibold'"
                class="px-5 py-2.5 rounded-2xl text-xs sm:text-sm transition-all flex items-center gap-2 cursor-pointer"
            >
                <i data-lucide="film" class="w-4 h-4"></i>
                <span>Semua Film ({{ $collection->films->count() }})</span>
            </button>
        </div>

        <!-- TAB 1: WATCH ORDER TIMELINE (Interactive React Component) -->
        @if($hasWatchOrders)
            <div x-show="activeTab === 'watch_order'" class="space-y-6">
                <div id="react-watch-order-timeline"
                     data-release-orders="{{ json_encode($releaseOrders) }}"
                     data-chronological-orders="{{ json_encode($chronologicalOrders) }}"
                     data-franchise-name="{{ $collection->name }}">
                </div>
            </div>
        @endif

        <!-- TAB 2: ALL FILMS GRID (Standard Grid Layout) -->
        <div x-show="activeTab === 'catalog'" class="space-y-6">
            @if($collection->films->isEmpty())
                <div class="py-16 text-center space-y-4 rounded-3xl bg-zinc-900/30 border border-dashed border-white/10">
                    <div class="w-14 h-14 rounded-2xl bg-zinc-900 border border-white/10 flex items-center justify-center mx-auto text-zinc-600">
                        <i data-lucide="film" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-serif font-bold text-base text-white">Belum Ada Film dalam Koleksi Ini</h3>
                    <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                        Koleksi ini masih kosong. Pemilik dapat menambahkan film melalui Studio Editor.
                    </p>
                    @if($canEdit)
                        <a href="{{ route('collections.edit', $collection->slug) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition shadow-md shadow-white/10">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Buka Studio & Tambah Film</span>
                        </a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-6">
                    @foreach($collection->films as $film)
                        @include('components.film-card', ['film' => $film])
                    @endforeach
                </div>
            @endif
        </div>

        <!-- SUGGESTIONS: "Mungkin Cocok dengan Koleksi Ini" -->
        @if($suggestions->isNotEmpty())
            <div class="pt-8 border-t border-white/10 space-y-4">
                <div class="flex items-center gap-2 text-white">
                    <i data-lucide="sparkles" class="w-4 h-4 text-white"></i>
                    <h2 class="font-bold text-base sm:text-lg">Mungkin Cocok dengan Koleksi Ini</h2>
                </div>
                <p class="text-xs text-zinc-400">
                    Rekomendasi film tambahan yang memiliki kemiripan vector semantik dengan tema koleksi
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-6 pt-2">
                    @foreach($suggestions as $film)
                        @include('components.film-card', ['film' => $film])
                    @endforeach
                </div>
            </div>
        @endif

    </div>

</div>
@endsection
