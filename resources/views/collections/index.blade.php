@extends('layouts.app')

@section('title', 'Koleksi Film & Urutan Nonton Cerdas - Faiilmov')
@section('meta_description', 'Jelajahi kurasi koleksi film cerdas, franchise semesta terlengkap (MCU, Star Wars, Drakor), dan buat koleksi film impianmu lengkap dengan urutan nonton drag-and-drop di Faiilmov.')

@section('content')
<div class="min-h-screen pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-10">

    <!-- Hero Header Banner -->
    <div class="relative rounded-3xl overflow-hidden bg-zinc-900/80 border border-white/10 p-6 sm:p-10 lg:p-12 shadow-2xl">
        <div class="relative z-10 max-w-3xl space-y-4">
            <h1 class="font-serif font-extrabold text-3xl sm:text-4xl lg:text-5xl text-white tracking-tight leading-tight">
                Mau maraton film tanpa bingung urutannya?
            </h1>

            <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                Nonton franchise populer sesuai kronologi cerita atau bikin daftar tontonan kustommu di sini.
            </p>

            <div class="pt-2 flex flex-wrap items-center gap-3">
                @auth
                <button 
                    onclick="window.dispatchEvent(new CustomEvent('open-create-collection-modal'))"
                    class="px-5 py-3 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs sm:text-sm font-extrabold transition-all shadow-lg shadow-white/10 flex items-center gap-2 cursor-pointer transform hover:-translate-y-0.5 active:scale-95"
                >
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Buat Koleksi Baru</span>
                </button>

                    <a href="{{ route('collections.index', ['type' => 'mine']) }}" class="px-5 py-3 rounded-2xl bg-zinc-900/90 hover:bg-zinc-800 text-zinc-300 hover:text-white text-xs sm:text-sm font-bold border border-white/10 transition-all flex items-center gap-2">
                        <i data-lucide="folder-heart" class="w-4 h-4 text-zinc-400"></i>
                        <span>Koleksi Saya ({{ $myCollectionsCount }})</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Filter Pills Section -->
    <div id="browse-collections" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-white/10">
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            <a href="{{ route('collections.index', ['type' => 'all']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ $type === 'all' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/80 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-white/5' }}">
                Semua Koleksi
            </a>
            <a href="{{ route('collections.index', ['type' => 'franchise']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ $type === 'franchise' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/80 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-white/5' }}">
                Franchise & Semesta Resmi
            </a>
            <a href="{{ route('collections.index', ['type' => 'community']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ $type === 'community' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/80 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-white/5' }}">
                Koleksi Komunitas
            </a>
            @auth
                <a href="{{ route('collections.index', ['type' => 'mine']) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 {{ $type === 'mine' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/80 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-white/5' }}">
                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    <span>Koleksi Saya ({{ $myCollectionsCount }})</span>
                </a>
            @endauth
        </div>

        <span class="text-xs font-mono text-zinc-500">
            Total: {{ $collections->total() }} Koleksi
        </span>
    </div>

    <!-- Collections Grid -->
    @if($collections->isEmpty())
        <div class="py-20 text-center space-y-4 rounded-3xl bg-zinc-900/40 border border-dashed border-white/10 p-8">
            <div class="w-16 h-16 rounded-2xl bg-zinc-900 border border-white/10 flex items-center justify-center mx-auto text-zinc-600">
                <i data-lucide="layers" class="w-8 h-8"></i>
            </div>
            <h3 class="font-serif font-bold text-lg text-white">
                {{ $type === 'mine' ? 'Anda Belum Memiliki Koleksi' : 'Belum Ada Koleksi' }}
            </h3>
            <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                {{ $type === 'mine' 
                    ? 'Buat koleksi film pertama Anda, atur urutan nonton timeline yang rapi, dan simpan atau bagikan ke publik.' 
                    : 'Koleksi sedang diproses oleh AI atau belum ada koleksi publik yang dibuat.' 
                }}
            </p>
            <button 
                onclick="window.dispatchEvent(new CustomEvent('open-create-collection-modal'))"
                class="px-5 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition-all inline-flex items-center gap-2 cursor-pointer shadow-lg shadow-white/10"
            >
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Buat Koleksi Sekarang</span>
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($collections as $col)
                <div class="group relative rounded-3xl overflow-hidden bg-zinc-900/80 hover:bg-zinc-850 border border-white/10 hover:border-white/20 transition-colors duration-200 shadow-xl flex flex-col justify-between">
                    
                    <!-- Cover Image Banner / Backdrop -->
                    <a href="{{ route('collections.show', $col->slug) }}" class="block relative w-full aspect-[16/9] bg-zinc-950 overflow-hidden">
                        @if($col->cover_image)
                            <img src="{{ $col->cover_image }}" 
                                 alt="{{ $col->name }}" 
                                 class="w-full h-full object-cover" 
                                 loading="lazy" />
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-zinc-900 text-zinc-700">
                                <i data-lucide="film" class="w-12 h-12 opacity-30"></i>
                            </div>
                        @endif

                        <!-- Gradient overlays -->
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-zinc-900/20 to-black/30 pointer-events-none"></div>
                    </a>

                    <!-- Card Body Info -->
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div class="space-y-2">
                            <a href="{{ route('collections.show', $col->slug) }}" class="block">
                                <h3 class="font-bold text-base sm:text-lg text-white group-hover:text-zinc-300 transition-colors line-clamp-1">
                                    {{ $col->name }}
                                </h3>
                            </a>

                            <p class="text-xs text-zinc-400 line-clamp-2 leading-relaxed">
                                {{ $col->description ?: 'Koleksi kurasi film sinematik pilihan di Faiilmov.' }}
                            </p>
                        </div>

                        <!-- Card Footer Meta & Owner Actions -->
                        <div class="pt-3 border-t border-white/5 flex items-center justify-between text-[11px] text-zinc-500">
                            <div class="flex items-center gap-1.5 truncate">
                                @if($col->creator)
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-zinc-400"></i>
                                    <span class="truncate">{{ $col->creator->name }}</span>
                                @else
                                    <i data-lucide="bot" class="w-3.5 h-3.5 text-zinc-400"></i>
                                    <span>AI Engine</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if(auth()->check() && $col->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('collections.edit', $col->slug) }}" 
                                       class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white text-zinc-200 hover:text-zinc-950 border border-white/10 font-bold transition flex items-center gap-1"
                                       title="Edit di Studio">
                                        <i data-lucide="edit-3" class="w-3 h-3"></i>
                                        <span>Studio</span>
                                    </a>
                                @endif
                                
                                <a href="{{ route('collections.show', $col->slug) }}" class="text-zinc-400 group-hover:text-white transition flex items-center gap-0.5 font-semibold">
                                    <span>Lihat</span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="pt-6">
            {{ $collections->links() }}
        </div>
    @endif

</div>
@endsection
