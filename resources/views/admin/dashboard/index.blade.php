@extends('layouts.admin')

@section('title', 'Dashboard Overview | faiiladmin')
@section('page_title', 'Dashboard Overview')

@section('content')
<div class="space-y-8">
    
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total Films -->
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Film</p>
                <h3 class="text-2xl font-extrabold text-white mt-1.5 font-['Outfit']">{{ number_format($stats['total_films']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                <i data-lucide="film" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Users -->
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Pengguna Terdaftar</p>
                <h3 class="text-2xl font-extrabold text-white mt-1.5 font-['Outfit']">{{ number_format($stats['total_users']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Reviews -->
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Ulasan</p>
                <h3 class="text-2xl font-extrabold text-white mt-1.5 font-['Outfit']">{{ number_format($stats['total_reviews']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                <i data-lucide="message-square" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Active Watch Parties -->
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Nobar Aktif Hari Ini</p>
                <h3 class="text-2xl font-extrabold text-white mt-1.5 font-['Outfit']">{{ number_format($stats['active_watch_parties']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                <i data-lucide="tv" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Content Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Most Viewed Films -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white font-['Outfit'] flex items-center gap-2">
                    <i data-lucide="flame" class="w-5 h-5 text-amber-400"></i>
                    <span>Film Paling Banyak Ditonton Minggu Ini</span>
                </h3>
                <a href="{{ route('admin.films.index') }}" class="text-xs text-amber-400 hover:underline">Lihat Semua</a>
            </div>

            <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                        <tr>
                            <th class="px-4 py-3">Film</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Rating</th>
                            <th class="px-4 py-3 text-right">View Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($mostViewedFilms as $film)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 flex items-center gap-3">
                                    <img src="{{ $film->poster_url }}" class="w-8 h-12 object-cover rounded-lg shrink-0">
                                    <div>
                                        <p class="font-bold text-white text-sm line-clamp-1">{{ $film->title }}</p>
                                        <p class="text-[11px] text-zinc-400">{{ $film->release_year }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $film->subject_type === 'series' ? 'bg-purple-500/20 text-purple-300' : 'bg-blue-500/20 text-blue-300' }}">
                                        {{ $film->subject_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-amber-400">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                                        {{ number_format($film->rating, 1) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-zinc-200">
                                    {{ number_format($film->view_count) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-zinc-500">Belum ada data ditonton.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sync API Log Status -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-white font-['Outfit'] flex items-center gap-2">
                <i data-lucide="refresh-cw" class="w-5 h-5 text-blue-400"></i>
                <span>Status Sync API Terakhir</span>
            </h3>

            <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-4">
                <div>
                    <p class="text-xs text-zinc-400">Waktu Sync Terakhir:</p>
                    <p class="text-sm font-semibold text-white mt-0.5">{{ $lastSyncAt ?: 'Belum Pernah' }}</p>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 text-xs text-zinc-300 font-mono">
                    {{ $lastSyncStatus }}
                </div>

                <form action="{{ route('admin.films.sync_api') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        <span>Sync Film dari API Sekarang</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
