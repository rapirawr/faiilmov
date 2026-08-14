@extends('layouts.admin')

@section('title', 'Dashboard Overview | faiiladmin')
@section('page_title', 'Dashboard Overview')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Environment Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
            <div>
                <h2 class="text-sm font-bold text-white font-['Outfit']">Ringkasan Sistem & Performa</h2>
                <p class="text-xs text-zinc-400 font-mono mt-0.5">PHP {{ $systemInfo['php_version'] }} &bull; Laravel v{{ $systemInfo['laravel_version'] }} &bull; {{ strtoupper($systemInfo['db_driver']) }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Sync MovieBox -->
            <form action="{{ route('admin.films.sync_api') }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 hover:text-white border border-zinc-700 text-xs font-semibold transition-colors flex items-center gap-2 cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-zinc-400"></i>
                    <span>Sync MovieBox</span>
                </button>
            </form>

            <!-- Sync Dracin -->
            <form action="{{ route('admin.films.sync_dracin_api') }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 hover:text-white border border-zinc-700 text-xs font-semibold transition-colors flex items-center gap-2 cursor-pointer">
                    <i data-lucide="smartphone" class="w-3.5 h-3.5 text-zinc-400"></i>
                    <span>Sync Dracin</span>
                </button>
            </form>

            <!-- Pending Review Reports -->
            <a href="{{ route('admin.reviews.index', ['filter' => 'reported']) }}" 
               class="px-3.5 py-2 rounded-xl {{ $pendingReportsCount > 0 ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border-rose-500/30' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border-zinc-700' }} border text-xs font-semibold transition-colors flex items-center gap-2 cursor-pointer">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5 {{ $pendingReportsCount > 0 ? 'text-rose-400' : 'text-zinc-400' }}"></i>
                <span>Laporan Ulasan @if($pendingReportsCount > 0)({{ $pendingReportsCount }})@endif</span>
            </a>
        </div>
    </div>

    <!-- Core KPI Stat Cards (4 Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Films -->
        <a href="{{ route('admin.films.index') }}" 
           class="p-5 rounded-2xl bg-zinc-900/80 hover:bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors group cursor-pointer block space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Katalog Film</span>
                <i data-lucide="film" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-white font-['Outfit'] tracking-tight">{{ number_format($stats['total_films']) }}</h3>
                <p class="text-[11px] text-zinc-400 font-mono mt-1">
                    {{ number_format($stats['total_movies']) }} Movie &bull; {{ number_format($stats['total_series']) }} Series &bull; {{ number_format($stats['total_dracin'] ?? 0) }} Dracin
                </p>
            </div>
        </a>

        <!-- Total Users -->
        <a href="{{ route('admin.users.index') }}" 
           class="p-5 rounded-2xl bg-zinc-900/80 hover:bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors group cursor-pointer block space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Pengguna Terdaftar</span>
                <i data-lucide="users" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-white font-['Outfit'] tracking-tight">{{ number_format($stats['total_users']) }}</h3>
                <p class="text-[11px] text-emerald-400 font-medium mt-1">
                    +{{ number_format($stats['new_users_7d']) }} user 7 hari terakhir
                </p>
            </div>
        </a>

        <!-- Watch Parties -->
        <a href="{{ route('admin.watch_parties.index') }}" 
           class="p-5 rounded-2xl bg-zinc-900/80 hover:bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors group cursor-pointer block space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Watch Party Aktif</span>
                <i data-lucide="tv" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-white font-['Outfit'] tracking-tight">{{ number_format($stats['active_watch_parties']) }}</h3>
                <p class="text-[11px] text-zinc-400 font-mono mt-1">
                    {{ number_format($stats['total_watch_parties']) }} total sesi terselenggara
                </p>
            </div>
        </a>

        <!-- Reviews & Genres -->
        <a href="{{ route('admin.reviews.index') }}" 
           class="p-5 rounded-2xl bg-zinc-900/80 hover:bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors group cursor-pointer block space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Ulasan Komunitas</span>
                <i data-lucide="message-square" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-white font-['Outfit'] tracking-tight">{{ number_format($stats['total_reviews']) }}</h3>
                <p class="text-[11px] text-zinc-400 font-mono mt-1">
                    {{ number_format($stats['total_genres']) }} Genre Aktif
                </p>
            </div>
        </a>
    </div>

    <!-- Main Grid (8 Cols Left, 4 Cols Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Content Analytics & Most Viewed Table -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Distribution & Rating Matrix Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Format Breakdown -->
                <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-white">Komposisi Konten</span>
                        <span class="font-mono text-zinc-400">{{ number_format($stats['total_films']) }} Judul</span>
                    </div>

                    @php 
                        $total = max(1, $stats['total_films']);
                        $moviePct = round(($stats['total_movies'] / $total) * 100);
                        $seriesPct = round(($stats['total_series'] / $total) * 100);
                        $dracinPct = round((($stats['total_dracin'] ?? 0) / $total) * 100);
                    @endphp

                    <div class="space-y-2.5 pt-1">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-zinc-300">Movie</span>
                                <span class="font-mono text-zinc-400">{{ number_format($stats['total_movies']) }} ({{ $moviePct }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-zinc-950 overflow-hidden">
                                <div class="h-full bg-zinc-400 rounded-full" style="width: {{ $moviePct }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-zinc-300">TV Series</span>
                                <span class="font-mono text-zinc-400">{{ number_format($stats['total_series']) }} ({{ $seriesPct }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-zinc-950 overflow-hidden">
                                <div class="h-full bg-zinc-500 rounded-full" style="width: {{ $seriesPct }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-zinc-300">Drama Pendek (Dracin)</span>
                                <span class="font-mono text-zinc-400">{{ number_format($stats['total_dracin'] ?? 0) }} ({{ $dracinPct }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-zinc-950 overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ $dracinPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parental Rating Matrix -->
                <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-white">Klasifikasi Usia</span>
                        <a href="{{ route('admin.films.content_rating') }}" class="text-amber-400 hover:underline text-[11px]">Edit Massal &rarr;</a>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-xs pt-1">
                        @foreach(['SU', 'G', 'PG', '13+', '16+', '18+'] as $rating)
                            @php $count = $contentRatings[$rating] ?? 0; @endphp
                            <div class="p-2 rounded-xl bg-zinc-950 border border-zinc-800 flex flex-col items-center justify-center">
                                <span class="font-mono text-[11px] font-bold text-zinc-400">{{ $rating }}</span>
                                <span class="font-mono font-bold text-white text-xs mt-0.5">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-1 flex items-center justify-between text-[11px] text-zinc-400 border-t border-zinc-800">
                        <span>Belum terklasifikasi:</span>
                        <span class="font-mono font-bold text-zinc-300">{{ number_format($contentRatings[''] ?? 0) }} film</span>
                    </div>
                </div>

            </div>

            <!-- Top Viewed Films Table -->
            <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white font-['Outfit']">Film Paling Banyak Ditonton</h3>
                    <a href="{{ route('admin.films.index') }}" class="text-xs text-zinc-400 hover:text-white transition-colors">Semua Katalog &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800">
                            <tr>
                                <th class="pb-2.5">Judul Film</th>
                                <th class="pb-2.5">Tipe</th>
                                <th class="pb-2.5">Rating</th>
                                <th class="pb-2.5 text-right">Tayangan</th>
                                <th class="pb-2.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/60">
                            @forelse($mostViewedFilms as $idx => $film)
                                <tr class="hover:bg-zinc-800/30 transition-colors group">
                                    <td class="py-3 flex items-center gap-3">
                                        <span class="font-mono text-zinc-500 font-bold w-4 text-[11px]">{{ $idx + 1 }}</span>
                                        <img src="{{ $film->poster_url }}" class="w-8 h-11 object-cover rounded-lg shrink-0 bg-zinc-950 border border-zinc-800">
                                        <div class="min-w-0">
                                            <p class="font-bold text-white text-xs truncate max-w-[240px]">{{ $film->title }}</p>
                                            <p class="text-[11px] text-zinc-400 font-mono mt-0.5">{{ $film->release_year }} &bull; {{ $film->content_rating ?: 'SU' }}</p>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold uppercase bg-zinc-800 text-zinc-300 border border-zinc-700">
                                            {{ $film->subject_type }}
                                        </span>
                                    </td>
                                    <td class="py-3 font-mono font-semibold text-amber-400">
                                        ★ {{ number_format($film->rating, 1) }}
                                    </td>
                                    <td class="py-3 text-right font-mono font-bold text-zinc-200">
                                        {{ number_format($film->view_count) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.films.edit', $film->id) }}" class="p-1 rounded bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition-colors" title="Edit">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            </a>
                                            <a href="{{ url('/film/' . $film->slug) }}" target="_blank" class="p-1 rounded bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition-colors" title="Lihat">
                                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-zinc-500">Belum ada statistik tayangan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Watch Parties List (If Any) -->
            @if($activeWatchPartiesList->isNotEmpty())
                <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-white font-['Outfit']">Sesi Watch Party Aktif</h3>
                        <a href="{{ route('admin.watch_parties.index') }}" class="text-xs text-zinc-400 hover:text-white transition-colors">Semua Room &rarr;</a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($activeWatchPartiesList as $room)
                            <div class="p-3 rounded-xl bg-zinc-950 border border-zinc-800 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <h5 class="font-bold text-white text-xs truncate">{{ $room->film ? $room->film->title : 'Film Session' }}</h5>
                                    <p class="text-[10px] text-zinc-400 font-mono mt-0.5">Room: <span class="text-zinc-200 font-bold">{{ $room->room_code }}</span></p>
                                </div>
                                <a href="{{ route('admin.watch_parties.show', $room->id) }}" class="px-2.5 py-1 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-[11px] font-semibold text-zinc-200 transition-colors shrink-0">
                                    Pantau
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column: Activity Audit Trail, New Users, Sync Gateway -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Recent Admin Activity -->
            <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-zinc-300 uppercase tracking-wider">Aktivitas Admin</h3>
                    <a href="{{ route('admin.activity_logs.index') }}" class="text-[11px] text-zinc-400 hover:text-white transition-colors">Semua Log &rarr;</a>
                </div>

                <div class="space-y-2.5">
                    @forelse($recentActivityLogs as $log)
                        <div class="p-2.5 rounded-xl bg-zinc-950 border border-zinc-800/80 text-xs space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[10px] font-bold text-amber-400 uppercase truncate">{{ $log->action }}</span>
                                <span class="font-mono text-[10px] text-zinc-500 shrink-0">{{ $log->created_at->diffForHumans(null, true) }}</span>
                            </div>
                            <p class="text-zinc-300 text-[11px] leading-relaxed line-clamp-2">{{ $log->description }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-500 text-center py-4">Belum ada riwayat aktivitas.</p>
                    @endforelse
                </div>
            </div>

            <!-- New Registered Users -->
            <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-zinc-300 uppercase tracking-wider">Pengguna Baru</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-[11px] text-zinc-400 hover:text-white transition-colors">Semua &rarr;</a>
                </div>

                <div class="space-y-2">
                    @forelse($recentUsers as $user)
                        <div class="p-2.5 rounded-xl bg-zinc-950 border border-zinc-800 flex items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img src="{{ $user->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}" class="w-7 h-7 rounded-full object-cover shrink-0 bg-zinc-800">
                                <div class="min-w-0">
                                    <p class="font-bold text-white text-xs truncate">{{ $user->name }}</p>
                                    <p class="text-[10px] text-zinc-500 truncate">{{ $user->email }}</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-mono text-zinc-500 shrink-0">{{ $user->created_at->diffForHumans(null, true) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-500 text-center py-3">Belum ada user baru.</p>
                    @endforelse
                </div>
            </div>

            <!-- Sync Gateway Status -->
            <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-white">Status Sync Gateway</span>
                    <span class="font-mono text-zinc-500 text-[11px]">{{ $lastSyncAt }}</span>
                </div>

                <div class="p-3 rounded-xl bg-zinc-950 border border-zinc-800 text-[11px] text-zinc-400 font-mono leading-relaxed">
                    {{ $lastSyncStatus }}
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
