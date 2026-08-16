@extends('layouts.app')

@section('title', 'Profil & Pengaturan Akun | faiilmov')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ tab: 'history' }">
    

    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm font-semibold space-y-1 shadow-lg">
            <div class="flex items-center gap-2 text-rose-400 font-bold mb-1">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <span>Terjadi Kesalahan Validasi:</span>
            </div>
            <ul class="list-disc list-inside text-xs pl-2 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- User Profile Header Glass Card -->
    <div class="glass-panel p-6 sm:p-8 rounded-3xl mb-8 border border-white/10 flex flex-col md:flex-row items-center justify-between gap-6 shadow-2xl relative overflow-hidden">
        <div class="absolute rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row items-center gap-5 text-center sm:text-left z-10">
            <div class="w-20 h-20 shrink-0 rounded-3xl bg-gradient-to-tr from-amber-400 to-amber-200 text-zinc-950 flex items-center justify-center text-2xl font-serif font-black shadow-xl ring-4 ring-white/10 overflow-hidden">
                @if($activeProfile && $activeProfile->avatar)
                    <img src="{{ $activeProfile->avatar }}" alt="{{ $activeProfile->name }}" class="w-full h-full object-cover">
                @elseif(!$activeProfile && $user->avatar)
                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($activeProfile ? $activeProfile->name : $user->name, 0, 2)) }}
                @endif
            </div>
            <div>
                <div class="flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                    <h1 class="font-serif font-bold text-2xl sm:text-3xl text-white tracking-tight">
                        {{ $activeProfile ? $activeProfile->name : $user->name }}
                    </h1>
                    @if($activeProfile && $activeProfile->is_child)
                        <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 text-[10px] font-extrabold uppercase">Profil Anak</span>
                    @elseif($activeProfile)
                        <span class="px-2.5 py-0.5 rounded-full bg-zinc-800 text-zinc-300 border border-white/20 text-[10px] font-extrabold uppercase">Sub Profil</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] font-extrabold uppercase">Akun Utama</span>
                    @endif
                </div>
                <p class="text-zinc-400 text-xs sm:text-sm mt-1 flex items-center justify-center sm:justify-start gap-1.5">
                    <i data-lucide="mail" class="w-3.5 h-3.5 text-zinc-500"></i>
                    <span>{{ $user->email }}</span>
                    @if(!$activeProfile && $user->phone)
                        <span class="text-zinc-600">•</span>
                        <i data-lucide="phone" class="w-3.5 h-3.5 text-zinc-500"></i>
                        <span>{{ $user->phone }}</span>
                    @endif
                </p>
                @if(!$activeProfile && $user->bio)
                    <p class="text-amber-300/80 text-xs italic mt-1.5">"{{ $user->bio }}"</p>
                @endif
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mt-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl glass-chip text-[10px] font-bold text-zinc-300 uppercase tracking-wider">
                        <i data-lucide="flame" class="w-3 h-3 text-rose-400"></i>
                        <span>Genre Favorit: {{ $topGenre }}</span>
                    </span>
                    <a href="{{ route('profiles.index') }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-[10px] font-bold transition-all">
                        <i data-lucide="users" class="w-3 h-3"></i>
                        <span>Kelola / Ganti Profil</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-3 z-10 w-full sm:w-auto">
            <div class="text-center px-4 py-3.5 rounded-2xl glass-card border border-white/10 min-w-[85px] shadow-lg">
                <span class="block font-serif font-bold text-2xl text-white">{{ $watchHistories->count() }}</span>
                <span class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold">Riwayat</span>
            </div>
            <div class="text-center px-4 py-3.5 rounded-2xl glass-card border border-white/10 min-w-[85px] shadow-lg">
                <span class="block font-serif font-bold text-2xl text-white">{{ $watchlists->count() }}</span>
                <span class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold">Watchlist</span>
            </div>
            <div class="text-center px-4 py-3.5 rounded-2xl glass-card border border-white/10 min-w-[85px] shadow-lg">
                <span class="block font-serif font-bold text-2xl text-white">{{ $reviews->count() }}</span>
                <span class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold">Ulasan</span>
            </div>
            <div class="text-center px-4 py-3.5 rounded-2xl glass-card border border-white/10 min-w-[85px] shadow-lg">
                <span class="block font-serif font-bold text-2xl text-amber-400">{{ $filmRequests->count() }}</span>
                <span class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold">Request</span>
            </div>
        </div>
    </div>

    <!-- Responsive Horizontal Scroll Tab Navigation Pills -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar p-1.5 rounded-2xl bg-dark-900/90 border border-white/10 mb-8 shadow-xl">
        <button @click="tab = 'history'" 
                :class="tab === 'history' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-5 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap">
            <i data-lucide="history" class="w-4 h-4"></i>
            <span>Riwayat Tontonan</span>
        </button>
        <button @click="tab = 'watchlist'" 
                :class="tab === 'watchlist' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-5 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap">
            <i data-lucide="bookmark" class="w-4 h-4"></i>
            <span>Watchlist (Daftar Tontonan)</span>
        </button>
        <button @click="tab = 'reviews'" 
                :class="tab === 'reviews' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-5 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap">
            <i data-lucide="message-square" class="w-4 h-4"></i>
            <span>Riwayat Ulasan</span>
        </button>
        <button @click="tab = 'analytics'" 
                :class="tab === 'analytics' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-5 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap">
            <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
            <span>Statistik Nonton</span>
        </button>
        <button @click="tab = 'requests'" 
                :class="tab === 'requests' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-5 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap">
            <i data-lucide="inbox" class="w-4 h-4"></i>
            <span>Request Saya ({{ $filmRequests->count() }})</span>
        </button>
        <button @click="tab = 'settings'" 
                :class="tab === 'settings' ? 'bg-white text-zinc-950 font-bold shadow-md' : 'text-zinc-400 hover:text-white'"
                class="px-5 py-2.5 rounded-full text-xs transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer whitespace-nowrap">
            <i data-lucide="settings" class="w-4 h-4"></i>
            <span>Pengaturan Akun</span>
        </button>
    </div>

    <!-- Tab 1: Watch History -->
    <div x-show="tab === 'history'" x-data="{ historyType: 'all' }">
        @if($watchHistories->count() > 0)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4 flex-wrap">
                    <h2 class="font-serif font-bold text-lg text-white flex items-center gap-2">
                        <i data-lucide="play-circle" class="w-5 h-5 text-amber-400"></i>
                        <span>Tontonan Terakhir</span>
                    </h2>

                    <!-- Filter Pills: All / Movies & Series / Dracin -->
                    <div class="flex items-center gap-1 p-1 rounded-xl bg-dark-900/90 border border-white/10 text-xs">
                        <button @click="historyType = 'all'" :class="historyType === 'all' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-lg transition-all cursor-pointer">Semua</button>
                        <button @click="historyType = 'movies'" :class="historyType === 'movies' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-lg transition-all cursor-pointer">Film & Series</button>
                        <button @click="historyType = 'dracin'" :class="historyType === 'dracin' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-lg transition-all cursor-pointer">Dracin</button>
                    </div>
                </div>

                <form action="{{ route('watch-history.clear-all') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh riwayat tontonan?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl glass-chip text-rose-400 hover:bg-rose-500 hover:text-white text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5 border border-rose-500/20 shadow-md">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span>Bersihkan Riwayat</span>
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($watchHistories as $item)
                    @if(!$item->film)
                        @continue
                    @endif
                    @php
                        $isDracin = ($item->film->subject_type === 'dracin' || str_starts_with($item->film->moviebox_subject_id ?? '', 'anichin:'));
                        $durMin = 15;
                        $anichinSource = 'dramabox';
                        $anichinId = $item->film->id;

                        if ($isDracin) {
                            $durMin = 15;
                            $parts = explode(':', $item->film->moviebox_subject_id ?? '');
                            $anichinSource = $parts[1] ?? 'dramabox';
                            $anichinId = $parts[2] ?? (string)$item->film->id;
                            $watchUrl = route('dracin.show', ['source' => $anichinSource, 'id' => $anichinId]) . "?ep={$item->episode_number}";
                        } elseif ($item->film->subject_type === 'series') {
                            $seasonObj = $item->film->seasons->firstWhere('season_number', $item->season_number);
                            $epObj = $seasonObj ? $seasonObj->episodes->firstWhere('episode_number', $item->episode_number) : null;
                            $durMin = $epObj ? $epObj->duration_minutes : 45;
                            $watchUrl = route('film.watch', $item->film->slug) . "?season={$item->season_number}&episode={$item->episode_number}";
                        } else {
                            $durMin = $item->film->duration_minutes ?: 120;
                            $watchUrl = route('film.watch', $item->film->slug);
                        }

                        $totalSec = max(1, $durMin * 60);
                        $progPercent = min(100, max(0, round(($item->progress_seconds / $totalSec) * 100)));
                        $formattedMin = floor($item->progress_seconds / 60);
                    @endphp

                    <div x-show="historyType === 'all' || (historyType === 'dracin' && {{ $isDracin ? 'true' : 'false' }}) || (historyType === 'movies' && {{ !$isDracin ? 'true' : 'false' }})"
                         class="glass-panel rounded-3xl p-4 border border-white/10 hover:border-white/20 transition-all duration-300 flex flex-col justify-between group shadow-xl relative overflow-hidden">
                        <div>
                            <!-- Poster & Overlay Container -->
                            <div class="relative aspect-[16/9] block rounded-2xl overflow-hidden mb-3 bg-dark-900 shadow-md group/poster">
                                <a href="{{ $watchUrl }}" class="absolute inset-0 z-0">
                                    <img src="{{ $item->film->backdrop_url ?: $item->film->poster_url }}" alt="{{ $item->film->title }}" class="w-full h-full object-cover group-hover/poster:scale-105 transition-transform duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-dark-950 via-dark-950/30 to-transparent"></div>
                                </a>
                                
                                <!-- Category & Ep & Rating Badge -->
                                <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 z-10 flex-wrap pointer-events-none">
                                    @if($isDracin)
                                        <span class="px-2.5 py-1 rounded-xl bg-white text-zinc-950 text-[10px] font-extrabold uppercase tracking-wider shadow-sm">
                                            DRACIN
                                        </span>
                                        <span class="px-2.5 py-1 rounded-xl bg-zinc-900/90 text-white text-[10px] font-extrabold uppercase shadow-sm border border-zinc-700">
                                            EP {{ $item->episode_number }}
                                        </span>
                                    @elseif($item->film->subject_type === 'series')
                                        <span class="px-2.5 py-1 rounded-xl glass-chip text-[10px] font-bold text-white uppercase tracking-wider shadow-sm">
                                            Series
                                        </span>
                                        <span class="px-2.5 py-1 rounded-xl bg-amber-500/80 text-zinc-950 text-[10px] font-extrabold uppercase shadow-sm">
                                            S{{ $item->season_number }} E{{ $item->episode_number }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-xl glass-chip text-[10px] font-bold text-white uppercase tracking-wider shadow-sm">
                                            Film
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-xl bg-dark-950/80 border border-amber-500/30 text-amber-400 text-[10px] font-extrabold flex items-center gap-1 shadow-sm">
                                        <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                        <span>{{ number_format($item->film->rating, 1) }}</span>
                                    </span>
                                </div>

                                <!-- Quick Delete Single History -->
                                <form action="{{ route('watch-history.destroy', $item->id) }}" method="POST" class="absolute top-2.5 right-2.5 z-20">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-xl glass-chip text-zinc-400 hover:text-rose-400 hover:bg-white/10 transition-colors cursor-pointer" title="Hapus dari riwayat">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
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
                                    <div class="h-full bg-white rounded-full transition-all duration-300" style="width: {{ max(5, $progPercent) }}%"></div>
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
    @php
        $watchlistJson = json_encode($watchlists->filter(fn($w) => $w->film)->map(function($w) {
            return [
                'id' => $w->id,
                'film_id' => $w->film_id,
                'title' => $w->film ? $w->film->title : '',
                'rating' => $w->film ? number_format($w->film->rating, 1) : '0.0',
                'poster_url' => $w->film ? $w->film->poster_url : '',
                'slug' => $w->film ? $w->film->slug : '',
                'toggle_url' => route('watchlist.toggle', $w->film_id)
            ];
        })->values());
    @endphp
    <div x-show="tab === 'watchlist'" style="display: none;" x-data="{ items: {{ $watchlistJson }} }">
        <template x-if="items.length > 0">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-serif font-bold text-lg text-white flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-5 h-5 text-amber-400"></i>
                        <span>Daftar Simpanan Film</span>
                    </h2>
                    <form action="{{ route('profile.clear-watchlist') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh daftar tontonan?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3.5 py-1.5 rounded-xl glass-chip text-rose-400 hover:bg-rose-500 hover:text-white text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5 border border-rose-500/20 shadow-md">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Kosongkan Watchlist</span>
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="glass-card p-3 rounded-2xl border border-white/10 hover:border-white/30 transition-all duration-300 flex flex-col justify-between group shadow-md">
                            <div>
                                <div class="relative aspect-[2/3] rounded-xl overflow-hidden mb-3 bg-dark-900">
                                    <img :src="item.poster_url" :alt="item.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <div class="absolute top-2 left-2 px-2 py-0.5 rounded-lg bg-dark-950/80 border border-amber-500/30 text-amber-400 text-[10px] font-bold flex items-center gap-1 shadow">
                                        <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                        <span x-text="item.rating"></span>
                                    </div>
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
        @if($activeProfile)
            <div class="glass-panel p-8 rounded-3xl text-center space-y-4 max-w-lg mx-auto border border-amber-500/20 bg-amber-500/5 my-6">
                <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 mx-auto flex items-center justify-center">
                    <i data-lucide="info" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-white text-base">Ulasan Dikelola Oleh Akun Utama</h3>
                <p class="text-xs text-zinc-400">Sub-Profil (<strong class="text-white">{{ $activeProfile->name }}</strong>) tidak memiliki daftar ulasan terpisah. Semua ulasan dan rating tersimpan secara terpusat pada Akun Utama.</p>
                <form action="{{ route('profiles.switch-main') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-400 text-black font-extrabold text-xs transition-colors shadow-lg cursor-pointer">
                        Beralih ke Akun Utama
                    </button>
                </form>
            </div>
        @else
            @if($reviews->count() > 0)
                <div class="space-y-4 max-w-3xl">
                    @foreach($reviews as $rev)
                        @if(!$rev->film)
                            @continue
                        @endif
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
                <div class="text-center py-16 space-y-4">
                    <div class="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto text-zinc-500">
                        <i data-lucide="message-square-off" class="w-8 h-8"></i>
                    </div>
                    <p class="text-xs text-zinc-400 font-medium">Anda belum pernah memberikan ulasan untuk film apa pun.</p>
                </div>
            @endif
        @endif
    </div>

    <!-- Tab 4: Analytics Stats -->
    <div x-show="tab === 'analytics'" style="display: none;">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-panel p-6 rounded-3xl border border-white/10 shadow-xl flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-2xl font-serif font-bold text-white">{{ $totalHoursWatched }} Jam</span>
                    <span class="text-xs text-zinc-400">Total Waktu Menonton</span>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-3xl border border-white/10 shadow-xl flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <i data-lucide="heart" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-2xl font-serif font-bold text-white">{{ $topGenre }}</span>
                    <span class="text-xs text-zinc-400">Genre Terfavorit</span>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-3xl border border-white/10 shadow-xl flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i data-lucide="film" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-2xl font-serif font-bold text-white">{{ $watchHistories->count() }} Title</span>
                    <span class="text-xs text-zinc-400">Judul Pernah Ditonton</span>
                </div>
            </div>
        </div>

        <div class="glass-panel p-8 rounded-3xl border border-white/10 shadow-xl">
            <h3 class="font-serif font-bold text-lg text-white mb-2">Ringkasan Aktivitas Akun</h3>
            <p class="text-xs text-zinc-400 mb-6">Analisis tontonan Anda tersinkronisasi secara otomatis di seluruh perangkat Web & Mobile.</p>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 rounded-2xl glass-card border border-white/5">
                    <div class="flex items-center gap-3">
                        <i data-lucide="bookmark" class="w-5 h-5 text-amber-400"></i>
                        <span class="text-xs text-zinc-300 font-semibold">Total Item Watchlist</span>
                    </div>
                    <span class="text-sm font-bold text-white">{{ $watchlists->count() }} Item</span>
                </div>
                <div class="flex items-center justify-between p-4 rounded-2xl glass-card border border-white/5">
                    <div class="flex items-center gap-3">
                        <i data-lucide="message-square" class="w-5 h-5 text-indigo-400"></i>
                        <span class="text-xs text-zinc-300 font-semibold">Total Ulasan Diberikan</span>
                    </div>
                    <span class="text-sm font-bold text-white">{{ $reviews->count() }} Ulasan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 5: Account & Security Settings -->
    <div x-show="tab === 'settings'" style="display: none;">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Edit Profile Form -->
            <div class="glass-panel p-8 rounded-3xl border border-white/10 shadow-xl"
                 x-data="{
                    avatarUrl: '{{ old('avatar', $user->avatar) }}',
                    previewSrc: null,
                    avatarModalOpen: false,
                    avatarStyle: 'avataaars-neutral',
                    avatarStyles: [
                        { id: 'avataaars-neutral', label: 'Avataaars', emoji: '🧑' },
                        { id: 'adventurer-neutral', label: 'Adventurer', emoji: '🧙' },
                        { id: 'bottts-neutral', label: 'Bottts', emoji: '🤖' },
                        { id: 'blobs', label: 'Blobs', emoji: '🫧' },
                        { id: 'clay', label: 'Clay', emoji: '🏺' },
                        { id: 'fun-emoji', label: 'Fun Emoji', emoji: '😄' }
                    ],
                    dicebearSeeds: ['Felix','Luna','Mochi','Jasper','Zara','Echo','Orion','Nova','Sable','Atlas','Ember','Sage','Flynn','Lyra','Rune','Cleo','Onyx','Iris','Finn','Halo','Mira','Dax','Wren','Skye','Bex','Juno','Loki','Nyx','Cove','Ash','Storm','Vale','Rex','Zoe','Kai','Rue','Vex','Mox','Pax','Sol'],
                    selectedSeed: null,
                    selectedStyle: null,
                    styleUrl(style, seed, size) { return 'https://api.dicebear.com/10.x/' + style + '/svg?seed=' + seed + '&size=' + size; },
                    pickAvatar(seed) {
                        this.selectedSeed = seed;
                        this.selectedStyle = this.avatarStyle;
                        this.avatarUrl = this.styleUrl(this.avatarStyle, seed, 200);
                        this.previewSrc = null;
                        this.avatarModalOpen = false;
                    }
                 }">
                <h3 class="font-serif font-bold text-lg text-white mb-1 flex items-center gap-2">
                    <i data-lucide="user-check" class="w-5 h-5 text-amber-400"></i>
                    <span>Informasi Profil Pengguna</span>
                </h3>
                <p class="text-xs text-zinc-400 mb-6">Unggah foto profil dari komputer/HP, perbarui nama, email, bio, dan nomor telepon akun Anda.</p>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- File Upload Input -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Unggah Foto Profil dari Perangkat</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-dark-900 border border-white/10 overflow-hidden shrink-0 flex items-center justify-center shadow-md">
                                <template x-if="previewSrc">
                                    <img :src="previewSrc" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!previewSrc && avatarUrl">
                                    <img :src="avatarUrl" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!previewSrc && !avatarUrl">
                                    <span class="text-xs font-bold text-zinc-400">No Image</span>
                                </template>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="avatar_file" accept="image/*" 
                                       @change="
                                           const file = $event.target.files[0];
                                           if (file) {
                                               const reader = new FileReader();
                                               reader.onload = (e) => { previewSrc = e.target.result; avatarUrl = ''; selectedSeed = null; };
                                               reader.readAsDataURL(file);
                                           }
                                       "
                                       class="w-full text-xs text-zinc-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-white file:text-zinc-950 hover:file:bg-zinc-200 cursor-pointer">
                                <p class="text-[10px] text-zinc-500 mt-1">Format: JPG, PNG, WEBP, GIF (Maks. 5MB)</p>
                            </div>
                        </div>
                    </div>

                    <!-- DiceBear Avatar Picker Trigger -->
                    <div class="pt-2 border-t border-white/5">
                        <label class="block text-xs font-bold text-zinc-300 mb-2">Atau Pilih Avatar Karakter</label>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="avatarModalOpen = true"
                                    class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl glass-card border border-white/15 hover:border-amber-400/50 hover:bg-white/5 text-xs font-bold text-zinc-300 hover:text-white transition-all cursor-pointer shadow-md group">
                                <div class="w-7 h-7 rounded-xl overflow-hidden bg-dark-900 border border-white/10 shrink-0 flex items-center justify-center">
                                    <template x-if="selectedSeed">
                                        <img :src="styleUrl(selectedStyle, selectedSeed, 60)" class="w-full h-full">
                                    </template>
                                    <template x-if="!selectedSeed">
                                        <i data-lucide="smile" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
                                    </template>
                                </div>
                                <span x-text="selectedSeed ? 'Avatar: ' + selectedSeed : 'Pilih Avatar Karakter'"></span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-zinc-500 ml-auto"></i>
                            </button>
                            <template x-if="selectedSeed">
                                <button type="button" @click="avatarUrl = ''; selectedSeed = null; previewSrc = null"
                                        class="px-3 py-2.5 rounded-2xl glass-card border border-white/10 text-zinc-400 hover:text-rose-400 text-[10px] font-bold cursor-pointer transition-colors">
                                    Reset
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="avatar" x-model="avatarUrl">
                    </div>

                    <!-- DiceBear Avatar Modal -->
                    <div x-show="avatarModalOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click.self="avatarModalOpen = false"
                         class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                         style="display: none;">
                        <div x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="glass-panel w-full max-w-2xl rounded-3xl border border-white/15 shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">

                            <!-- Modal Header -->
                            <div class="flex items-center justify-between p-5 border-b border-white/10 shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center">
                                        <i data-lucide="smile" class="w-5 h-5 text-amber-400"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-serif font-bold text-white text-base">Pilih Avatar Karakter</h4>
                                        <p class="text-[10px] text-zinc-400">40 seed × 4 gaya · DiceBear</p>
                                    </div>
                                </div>
                                <button type="button" @click="avatarModalOpen = false"
                                        class="p-2 rounded-xl glass-card border border-white/10 text-zinc-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Style Tabs -->
                            <div class="flex items-center gap-2 px-5 py-3 border-b border-white/10 overflow-x-auto no-scrollbar shrink-0 bg-dark-950/30">
                                <template x-for="s in avatarStyles" :key="s.id">
                                    <button type="button"
                                            @click="avatarStyle = s.id"
                                            :class="avatarStyle === s.id
                                                ? 'bg-white text-zinc-950 font-bold shadow-md'
                                                : 'glass-card text-zinc-400 hover:text-white border-white/10'"
                                            class="px-3.5 py-1.5 rounded-2xl text-xs transition-all whitespace-nowrap cursor-pointer border flex items-center gap-1.5 shrink-0">
                                        <span x-text="s.emoji"></span>
                                        <span x-text="s.label"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Avatar Grid -->
                            <div class="p-5 overflow-y-auto grid grid-cols-5 sm:grid-cols-8 gap-3">
                                <template x-for="seed in dicebearSeeds" :key="seed">
                                    <button type="button"
                                            @click="pickAvatar(seed)"
                                            :class="selectedSeed === seed && selectedStyle === avatarStyle
                                                ? 'ring-2 ring-amber-400 border-amber-400/60 bg-amber-500/10'
                                                : 'border-white/10 hover:border-amber-400/40 hover:bg-white/5'"
                                            class="relative aspect-square rounded-2xl overflow-hidden border-2 transition-all cursor-pointer group p-1.5 bg-dark-900/60">
                                        <img :src="styleUrl(avatarStyle, seed, 100)"
                                             :alt="seed"
                                             class="w-full h-full object-contain rounded-xl">
                                        <template x-if="selectedSeed === seed && selectedStyle === avatarStyle">
                                            <div class="absolute top-1 right-1 w-4 h-4 rounded-full bg-amber-400 flex items-center justify-center shadow-md">
                                                <i data-lucide="check" class="w-2.5 h-2.5 text-zinc-950"></i>
                                            </div>
                                        </template>
                                    </button>
                                </template>
                            </div>

                            <!-- Modal Footer -->
                            <div class="p-4 border-t border-white/10 flex items-center justify-between gap-3 shrink-0 bg-dark-950/50">
                                <span class="text-[11px] text-zinc-400" x-text="selectedSeed ? (avatarStyles.find(s=>s.id===selectedStyle)?.label ?? '') + ' · ' + selectedSeed : 'Belum ada yang dipilih'"></span>
                                <button type="button" @click="avatarModalOpen = false"
                                        class="px-5 py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors cursor-pointer shadow-md">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                               class="w-full px-4 py-3 rounded-2xl glass-card border border-white/10 text-white text-xs focus:outline-none focus:border-amber-400/50 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Alamat Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                               class="w-full px-4 py-3 rounded-2xl glass-card border border-white/10 text-white text-xs focus:outline-none focus:border-amber-400/50 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Bio / Slogan Sinematik</label>
                        <textarea name="bio" rows="2" placeholder="Tuliskan bio atau kutipan film favorit Anda..." 
                                  class="w-full px-4 py-2.5 rounded-2xl glass-card border border-white/10 text-white text-xs focus:outline-none focus:border-amber-400/50 transition-all resize-none">{{ old('bio', $user->bio) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 081234567890" 
                               class="w-full px-4 py-3 rounded-2xl glass-card border border-white/10 text-white text-xs focus:outline-none focus:border-amber-400/50 transition-all">
                    </div>

                    <button type="submit" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-all shadow-md cursor-pointer">
                        Simpan Perubahan Profil
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="glass-panel p-8 rounded-3xl border border-white/10 shadow-xl">
                <h3 class="font-serif font-bold text-lg text-white mb-1 flex items-center gap-2">
                    <i data-lucide="key" class="w-5 h-5 text-indigo-400"></i>
                    <span>Ubah Kata Sandi (Password)</span>
                </h3>
                <p class="text-xs text-zinc-400 mb-6">Keamanan akun Anda sangat penting bagi kami.</p>

                <form action="{{ route('profile.update-password') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Kata Sandi saat ini</label>
                        <input type="password" name="current_password" required 
                               class="w-full px-4 py-3 rounded-2xl glass-card border border-white/10 text-white text-xs focus:outline-none focus:border-indigo-400/50 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Kata Sandi Baru</label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-3 rounded-2xl glass-card border border-white/10 text-white text-xs focus:outline-none focus:border-indigo-400/50 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="password_confirmation" required 
                               class="w-full px-4 py-3 rounded-2xl glass-card border border-white/10 text-white text-xs focus:outline-none focus:border-indigo-400/50 transition-all">
                    </div>
                    <button type="submit" class="w-full py-3 rounded-2xl bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-xs transition-all shadow-md cursor-pointer">
                        Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>

        <!-- Logout Session Section -->
        @php
            $activeProfile = Auth::user()->activeProfile();
        @endphp
        <div class="mt-8 p-6 rounded-3xl glass-panel border border-white/10 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h4 class="font-serif font-bold text-base text-white flex items-center gap-2">
                    <i data-lucide="log-out" class="w-5 h-5 text-rose-400"></i>
                    <span>Keluar dari Akun (Logout)</span>
                </h4>
                @if($activeProfile)
                    <p class="text-xs text-amber-400 font-semibold mt-1">
                        Anda sedang menggunakan profil sub-akun ({{ $activeProfile->name }}). Silakan beralih ke Akun Utama terlebih dahulu untuk dapat Keluar.
                    </p>
                @else
                    <p class="text-xs text-zinc-400 mt-1">Akhiri sesi login Anda di perangkat ini secara aman.</p>
                @endif
            </div>

            @if($activeProfile)
                <form action="{{ route('profiles.switch-main') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-extrabold transition-all shadow-md cursor-pointer flex items-center gap-2">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span>Beralih ke Akun Utama</span>
                    </button>
                </form>
            @else
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 text-xs font-bold transition-all shadow-md cursor-pointer flex items-center gap-2">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Keluar Sekarang</span>
                    </button>
                </form>
            @endif
        </div>

        <!-- Danger Zone -->
        <div class="mt-10 p-8 rounded-3xl glass-panel border border-rose-500/20 shadow-xl">
            <h3 class="font-serif font-bold text-lg text-rose-400 mb-1 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500"></i>
                <span>Zona Bahaya (Danger Zone)</span>
            </h3>
            <p class="text-xs text-zinc-400 mb-6">Tindakan berikut bersifat permanen dan tidak dapat dibatalkan.</p>

            <div class="flex flex-wrap items-center gap-4">
                <form action="{{ route('profile.delete-account') }}" method="POST" onsubmit="return confirm('APAKAH ANDA YAKIN INGIN MENGHAPUS AKUN PERMANEN? Seluruh data tontonan dan ulasan akan hilang!')">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-center gap-3">
                        <input type="password" name="confirm_password" placeholder="Masukkan password untuk hapus" required 
                               class="px-4 py-2.5 rounded-xl glass-card border border-rose-500/30 text-white text-xs focus:outline-none">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-md cursor-pointer">
                            Hapus Akun Permanen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab: Request Saya -->
    <div x-show="tab === 'requests'">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="font-serif font-bold text-lg text-white flex items-center gap-2">
                    <i data-lucide="inbox" class="w-5 h-5 text-amber-400"></i>
                    <span>Daftar Request Film Saya</span>
                </h2>
                <p class="text-xs text-zinc-400 mt-1">Status dan riwayat permintaan film/series yang kamu kirimkan.</p>
            </div>
            <button type="button" onclick="window.openFilmRequestModal()" class="px-4 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center gap-2 transition-all shadow-md cursor-pointer shrink-0">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat Request Baru</span>
            </button>
        </div>

        @if($filmRequests->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($filmRequests as $req)
                    <div class="glass-card p-5 rounded-3xl border border-white/10 space-y-3 relative flex flex-col justify-between shadow-xl">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-serif font-bold text-sm text-white line-clamp-2">{{ $req->title }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase shrink-0 {{ $req->type === 'dracin' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : ($req->type === 'tv' || $req->type === 'series' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30') }}">
                                    {{ $req->type }}
                                </span>
                            </div>

                            @if($req->year)
                                <p class="text-[11px] text-zinc-400 mt-1">Tahun {{ $req->year }}</p>
                            @endif

                            <div class="mt-3">
                                @if($req->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 inline-flex items-center gap-1.5">
                                        <i data-lucide="clock" class="w-3 h-3 text-amber-400"></i>
                                        <span>Pending (Menunggu Antrean)</span>
                                    </span>
                                @elseif($req->status === 'searching')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-sky-500/20 text-sky-300 border border-sky-500/30 inline-flex items-center gap-1.5">
                                        <i data-lucide="loader-2" class="w-3 h-3 animate-spin text-sky-400"></i>
                                        <span>Sedang Dicari Sistem</span>
                                    </span>
                                @elseif($req->status === 'added')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 inline-flex items-center gap-1.5">
                                        <i data-lucide="check-circle" class="w-3 h-3 text-emerald-400"></i>
                                        <span>Ditemukan & Tersedia</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30 inline-flex items-center gap-1.5">
                                        <i data-lucide="x-circle" class="w-3 h-3 text-rose-400"></i>
                                        <span>Ditolak</span>
                                    </span>
                                @endif
                            </div>

                            @if($req->matchedFilm)
                                <div class="mt-3 p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Sudah Siap Ditonton</p>
                                        <p class="text-xs font-bold text-white truncate mt-0.5">{{ $req->matchedFilm->title }}</p>
                                    </div>
                                    <a href="{{ route('film.show', $req->matchedFilm->slug) }}" class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black text-xs font-bold shrink-0 transition-colors">
                                        Tonton
                                    </a>
                                </div>
                            @endif

                            @if($req->rejection_reason && $req->status === 'rejected')
                                <div class="mt-3 p-3 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-[11px] text-rose-300">
                                    <strong>Alasan:</strong> {{ $req->rejection_reason }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px] text-zinc-500">
                            <span>Dikirim {{ $req->created_at->diffForHumans() }}</span>
                            <span>🔥 {{ $req->request_count }} Pemohon</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="glass-panel p-12 rounded-3xl text-center border border-white/10 max-w-xl mx-auto my-6">
                <i data-lucide="inbox" class="w-12 h-12 text-zinc-500 mx-auto mb-3"></i>
                <h3 class="font-serif font-bold text-lg text-white mb-1">Belum Ada Request Film</h3>

            </div>
        @endif
    </div>

</div>
@endsection
