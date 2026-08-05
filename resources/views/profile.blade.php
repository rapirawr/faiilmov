@extends('layouts.app')

@section('title', 'Profil Pengguna | faiilmov')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ tab: 'history' }">
    
    <!-- User Profile Header Glass Card -->
    <div class="glass-panel p-8 rounded-3xl mb-10 border border-white/10 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-2xl">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-white text-zinc-950 flex items-center justify-center text-xl font-extrabold shadow-xl">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <h1 class="font-serif font-bold text-2xl text-white">{{ $user->name }}</h1>
                <p class="text-zinc-400 text-xs sm:text-sm mt-0.5">{{ $user->email }}</p>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl glass-chip text-[10px] font-bold text-zinc-300 uppercase tracking-wider mt-2">
                    <span>Anggota Member</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-center px-4 py-3 rounded-2xl glass-card border border-white/10 min-w-[90px]">
                <span class="block font-serif font-bold text-2xl text-white">{{ $watchHistories->count() }}</span>
                <span class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold">Riwayat</span>
            </div>
            <div class="text-center px-4 py-3 rounded-2xl glass-card border border-white/10 min-w-[90px]">
                <span class="block font-serif font-bold text-2xl text-white">{{ $watchlists->count() }}</span>
                <span class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold">Watchlist</span>
            </div>
            <div class="text-center px-4 py-3 rounded-2xl glass-card border border-white/10 min-w-[90px]">
                <span class="block font-serif font-bold text-2xl text-white">{{ $reviews->count() }}</span>
                <span class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold">Ulasan</span>
            </div>
        </div>
    </div>

    <!-- Curved Bridge Tab Navigation Pills -->
    <div class="bridge-container p-1.5 rounded-full inline-flex gap-1 mb-8 shadow-xl">
        <button @click="tab = 'history'" 
                :class="tab === 'history' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-6 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 cursor-pointer">
            <i data-lucide="history" class="w-4 h-4"></i>
            <span>Riwayat Tontonan</span>
        </button>
        <button @click="tab = 'watchlist'" 
                :class="tab === 'watchlist' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-6 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 cursor-pointer">
            <i data-lucide="bookmark" class="w-4 h-4"></i>
            <span>Daftar Tontonan (Watchlist)</span>
        </button>
        <button @click="tab = 'reviews'" 
                :class="tab === 'reviews' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-6 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 cursor-pointer">
            <i data-lucide="message-square" class="w-4 h-4"></i>
            <span>Riwayat Ulasan</span>
        </button>
    </div>

    <!-- Tab 1: Watch History -->
    <div x-show="tab === 'history'">
        @if($watchHistories->count() > 0)
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-serif font-bold text-lg text-white flex items-center gap-2">
                    <i data-lucide="play-circle" class="w-5 h-5 text-amber-400"></i>
                    <span>Tontonan Terakhir</span>
                </h2>
                <form action="{{ route('watch-history.clear-all') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh riwayat tontonan?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl glass-chip text-rose-400 hover:bg-rose-500 hover:text-white text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5 border border-rose-500/20">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span>Bersihkan Riwayat</span>
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($watchHistories as $item)
                    @php
                        $durMin = 120;
                        if ($item->film->subject_type === 'series') {
                            $seasonObj = $item->film->seasons->firstWhere('season_number', $item->season_number);
                            $epObj = $seasonObj ? $seasonObj->episodes->firstWhere('episode_number', $item->episode_number) : null;
                            $durMin = $epObj ? $epObj->duration_minutes : 45;
                        } else {
                            $durMin = $item->film->duration_minutes ?: 120;
                        }
                        $totalSec = max(1, $durMin * 60);
                        $progPercent = min(100, max(0, round(($item->progress_seconds / $totalSec) * 100)));
                        $formattedMin = floor($item->progress_seconds / 60);
                        $watchUrl = route('film.watch', $item->film->slug) . ($item->film->subject_type === 'series' ? "?season={$item->season_number}&episode={$item->episode_number}" : '');
                    @endphp

                    <div class="glass-panel rounded-3xl p-4 border border-white/10 hover:border-white/20 transition-all duration-300 flex flex-col justify-between group shadow-xl relative overflow-hidden">
                        <div>
                            <!-- Poster & Overlay -->
                            <div class="relative aspect-[16/9] rounded-2xl overflow-hidden mb-3 bg-dark-900 shadow-md">
                                <img src="{{ $item->film->backdrop_url ?: $item->film->poster_url }}" alt="{{ $item->film->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-dark-950 via-dark-950/30 to-transparent"></div>
                                
                                <!-- Category & Ep Badge -->
                                <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                                    <span class="px-2.5 py-1 rounded-xl glass-chip text-[10px] font-bold text-white uppercase tracking-wider shadow-sm">
                                        {{ $item->film->subject_type === 'series' ? 'Series' : 'Film' }}
                                    </span>
                                    @if($item->film->subject_type === 'series')
                                        <span class="px-2.5 py-1 rounded-xl bg-amber-500/80 text-zinc-950 text-[10px] font-extrabold uppercase shadow-sm">
                                            S{{ $item->season_number }} E{{ $item->episode_number }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Quick Delete Single History -->
                                <form action="{{ route('watch-history.destroy', $item->id) }}" method="POST" class="absolute top-2.5 right-2.5">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-xl glass-chip text-zinc-400 hover:text-rose-400 hover:bg-white/10 transition-colors cursor-pointer" title="Hapus dari riwayat">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>

                                <!-- Play Overlay Button -->
                                <a href="{{ $watchUrl }}" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-dark-950/40">
                                    <div class="w-11 h-11 rounded-full bg-white text-zinc-950 flex items-center justify-center shadow-xl transform group-hover:scale-110 transition-transform">
                                        <i data-lucide="play" class="w-5 h-5 fill-current ml-0.5"></i>
                                    </div>
                                </a>
                            </div>

                            <!-- Title & Metadata -->
                            <h3 class="font-serif font-bold text-sm text-white line-clamp-1 group-hover:text-amber-300 transition-colors mb-1">
                                {{ $item->film->title }}
                            </h3>
                            <p class="text-[11px] text-zinc-400 flex items-center gap-2 mb-3">
                                <span>Terakhir ditonton {{ $item->updated_at->diffForHumans() }}</span>
                            </p>

                            <!-- Progress Bar -->
                            <div class="space-y-1 mb-4">
                                <div class="w-full h-1.5 rounded-full bg-white/10 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-amber-500 to-amber-300 rounded-full transition-all duration-300" style="width: {{ max(5, $progPercent) }}%"></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-zinc-400 font-semibold">
                                    <span>{{ $formattedMin }}m / {{ $durMin }}m</span>
                                    <span>{{ $progPercent }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Resume Button -->
                        <a href="{{ $watchUrl }}" class="w-full py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5 shadow-md">
                            <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>
                            <span>Lanjut Nonton</span>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 glass-panel rounded-3xl border border-white/10">
                <i data-lucide="history" class="w-12 h-12 text-zinc-600 mx-auto mb-3"></i>
                <h3 class="font-serif font-bold text-lg text-white mb-1">Belum Ada Riwayat Tontonan</h3>
                <p class="text-xs text-zinc-400 mb-5">Film atau series yang Anda tonton akan tersimpan di sini secara otomatis.</p>
                <a href="{{ route('browse') }}" class="px-6 py-2.5 rounded-full bg-white text-zinc-950 text-xs font-bold hover:bg-zinc-200 transition-colors shadow-md">Jelajahi Film</a>
            </div>
        @endif
    </div>

    <!-- Tab 2: Watchlist -->
    <div x-show="tab === 'watchlist'" style="display: none;" x-data="{ items: @json($watchlists->map(fn($w) => ['id' => $w->id, 'film_id' => $w->film_id, 'title' => $w->film->title, 'poster_url' => $w->film->poster_url, 'slug' => $w->film->slug, 'toggle_url' => route('watchlist.toggle', $w->film_id)])) }">
        <template x-if="items.length > 0">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <template x-for="(item, index) in items" :key="item.id">
                    <div class="glass-card p-3 rounded-2xl border border-white/10 hover:border-white/30 transition-all duration-300 flex flex-col justify-between group shadow-md">
                        <div>
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden mb-3 bg-dark-900">
                                <img :src="item.poster_url" :alt="item.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <button @click="
                                            fetch(item.toggle_url, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                                            });
                                            items.splice(index, 1);
                                        " 
                                        class="absolute top-2 right-2 p-2 rounded-xl glass-chip text-rose-400 hover:bg-rose-500 hover:text-white transition-colors cursor-pointer shadow-md" title="Hapus dari Watchlist">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <h3 class="font-semibold text-xs text-white line-clamp-1 mb-1" x-text="item.title"></h3>
                        </div>
                        <a :href="'/film/' + item.slug" class="mt-3 block w-full py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold text-center transition-colors shadow-md">
                            Lihat Detail
                        </a>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="items.length === 0">
            <div class="text-center py-16 glass-panel rounded-3xl border border-white/10">
                <i data-lucide="bookmark" class="w-12 h-12 text-zinc-600 mx-auto mb-3"></i>
                <h3 class="font-serif font-bold text-lg text-white mb-1">Watchlist Masih Kosong</h3>
                <p class="text-xs text-zinc-400 mb-5">Tambahkan film favorit Anda ke daftar tontonan saat menjelajah katalog.</p>
                <a href="{{ route('browse') }}" class="px-6 py-2.5 rounded-full bg-white text-zinc-950 text-xs font-bold hover:bg-zinc-200 transition-colors shadow-md">Jelajahi Katalog</a>
            </div>
        </template>
    </div>

    <!-- Tab 3: Reviews History -->
    <div x-show="tab === 'reviews'" style="display: none;">
        @if($reviews->count() > 0)
            <div class="space-y-4 max-w-3xl">
                @foreach($reviews as $rev)
                    <div class="glass-panel p-6 rounded-3xl border border-white/10 flex items-start gap-4 hover:border-white/20 transition-all shadow-md">
                        <img src="{{ $rev->film->poster_url }}" alt="{{ $rev->film->title }}" class="w-14 h-20 object-cover rounded-2xl shrink-0 bg-dark-900">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-2">
                                <a href="{{ route('film.show', $rev->film->slug) }}" class="font-serif font-bold text-base text-white hover:text-zinc-300 transition-colors truncate">{{ $rev->film->title }}</a>
                                <div class="flex items-center gap-1 text-amber-400 font-bold text-xs glass-chip px-2.5 py-1 rounded-xl">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400"></i>
                                    <span>{{ $rev->rating }} / 5</span>
                                </div>
                            </div>
                            <p class="text-xs text-zinc-300 leading-relaxed mb-3">{{ $rev->comment }}</p>
                            <div class="flex items-center justify-between text-[11px] text-zinc-500">
                                <span>{{ $rev->created_at->format('d M Y, H:i') }}</span>
                                <form action="{{ route('review.destroy', $rev->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-400 hover:text-rose-300 font-semibold cursor-pointer transition-colors">Hapus Ulasan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 glass-panel rounded-3xl border border-white/10">
                <i data-lucide="message-square" class="w-12 h-12 text-zinc-600 mx-auto mb-3"></i>
                <h3 class="font-serif font-bold text-lg text-white mb-1">Belum Ada Ulasan</h3>
                <p class="text-xs text-zinc-400">Ulasan yang Anda tulis pada halaman detail film akan muncul di sini.</p>
            </div>
        @endif
    </div>

</div>
@endsection
