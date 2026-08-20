@extends('layouts.admin')

@section('title', 'Gamification & Badges CMS | faiiladmin')
@section('page_title', 'Cinephile Gamification, Badges & Wrapped Hub')

@section('content')
<div x-data="{ 
    activeTab: '{{ request('tab', 'badges') }}',
    showAddBadgeModal: false,
    showEditBadgeModal: false,
    editBadgeData: {
        id: null,
        name: '',
        code: '',
        description: '',
        category: 'milestone',
        icon: 'award',
        color: 'amber',
        xp_reward: 50,
        required_count: 1,
        is_active: true
    },
    openEditModal(badge) {
        this.editBadgeData = { ...badge };
        this.showEditBadgeModal = true;
        this.$nextTick(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }
}" class="space-y-6">

    <!-- Header Actions & Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2.5">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                <span class="font-bold">{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-zinc-400 hover:text-white">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2.5">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                <span class="font-bold">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-zinc-400 hover:text-white">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- Top Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-zinc-800/80">
        <div>
            <h2 class="text-lg font-black text-white flex items-center gap-2">
                <i data-lucide="trophy" class="w-5 h-5 text-amber-400"></i>
                <span>Gamification & Cinephile Engine</span>
            </h2>
            <p class="text-xs text-zinc-400 mt-0.5">
                Kelola katalog lencana, tarif reward XP, pengaturan privasi leaderboard, dan penyesuaian level pengguna.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <form action="{{ route('admin.gamification.recompute') }}" method="POST" onsubmit="return confirm('Jalankan sinkronisasi dan perhitungan ulang seluruh XP dan lencana pengguna dari riwayat aktivitas?')">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 hover:text-white text-xs font-bold transition flex items-center gap-2 border border-zinc-700 shadow cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Hitung Ulang & Sync XP</span>
                </button>
            </form>

            <a href="{{ route('leaderboard') }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold transition flex items-center gap-2">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Leaderboard Publik</span>
            </a>

            <a href="{{ route('wrapped') }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-bold transition flex items-center gap-2">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <span>Movie Wrapped</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total XP -->
        <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Total XP Platform</span>
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black font-mono text-white">{{ number_format($totalXpDistributed) }} <span class="text-xs font-sans text-amber-400">XP</span></div>
                <div class="text-[11px] text-zinc-500 mt-1">Rata-rata level: <strong class="text-zinc-300">Lv. {{ $avgUserLevel }}</strong></div>
            </div>
        </div>

        <!-- Card 2: Badges Catalog & Unlocks -->
        <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Koleksi Lencana</span>
                <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
                    <i data-lucide="award" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black font-mono text-white">{{ $totalBadgesCount }} <span class="text-xs font-sans text-purple-400">Badges</span></div>
                <div class="text-[11px] text-zinc-500 mt-1">Total diraih user: <strong class="text-zinc-300">{{ number_format($totalBadgeUnlocks) }}x</strong></div>
            </div>
        </div>

        <!-- Card 3: Active Streaks -->
        <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Streak Nonton Aktif</span>
                <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center border border-rose-500/20">
                    <i data-lucide="flame" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black font-mono text-white">{{ number_format($activeStreakUsers) }} <span class="text-xs font-sans text-rose-400">User</span></div>
                <div class="text-[11px] text-zinc-500 mt-1">Pengguna konsisten nonton harian</div>
            </div>
        </div>

        <!-- Card 4: Top Leader -->
        <div class="p-5 rounded-2xl bg-zinc-900/80 border border-zinc-800 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Pemimpin Klasemen #1</span>
                <div class="w-8 h-8 rounded-xl bg-yellow-500/10 text-yellow-400 flex items-center justify-center border border-yellow-500/20">
                    <i data-lucide="crown" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-base font-black text-white truncate">{{ $topLeader->name ?? 'Belum ada' }}</div>
                <div class="text-[11px] text-amber-400 font-mono font-bold mt-1">{{ number_format($topLeader->xp_total ?? 0) }} XP • Lv. {{ $topLeader->current_level ?? 1 }}</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-zinc-800 overflow-x-auto pb-px">
        <button type="button" 
                @click="activeTab = 'badges'"
                :class="activeTab === 'badges' ? 'border-amber-400 text-white font-bold bg-zinc-850' : 'border-transparent text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900'"
                class="px-4 py-2.5 border-b-2 rounded-t-xl text-xs flex items-center gap-2 transition cursor-pointer whitespace-nowrap">
            <i data-lucide="award" class="w-4 h-4 text-amber-400"></i>
            <span>Katalog Lencana ({{ $totalBadgesCount }})</span>
        </button>

        <button type="button" 
                @click="activeTab = 'settings'"
                :class="activeTab === 'settings' ? 'border-amber-400 text-white font-bold bg-zinc-850' : 'border-transparent text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900'"
                class="px-4 py-2.5 border-b-2 rounded-t-xl text-xs flex items-center gap-2 transition cursor-pointer whitespace-nowrap">
            <i data-lucide="sliders" class="w-4 h-4 text-purple-400"></i>
            <span>Konfigurasi Tarif XP & Fitur</span>
        </button>

        <button type="button" 
                @click="activeTab = 'users'"
                :class="activeTab === 'users' ? 'border-amber-400 text-white font-bold bg-zinc-850' : 'border-transparent text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900'"
                class="px-4 py-2.5 border-b-2 rounded-t-xl text-xs flex items-center gap-2 transition cursor-pointer whitespace-nowrap">
            <i data-lucide="user-check" class="w-4 h-4 text-emerald-400"></i>
            <span>Penyesuaian XP & Lencana User</span>
        </button>

        <button type="button" 
                @click="activeTab = 'leaderboard'"
                :class="activeTab === 'leaderboard' ? 'border-amber-400 text-white font-bold bg-zinc-850' : 'border-transparent text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900'"
                class="px-4 py-2.5 border-b-2 rounded-t-xl text-xs flex items-center gap-2 transition cursor-pointer whitespace-nowrap">
            <i data-lucide="list-ordered" class="w-4 h-4 text-cyan-400"></i>
            <span>Top Leaderboard</span>
        </button>

        <button type="button" 
                @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'border-amber-400 text-white font-bold bg-zinc-850' : 'border-transparent text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900'"
                class="px-4 py-2.5 border-b-2 rounded-t-xl text-xs flex items-center gap-2 transition cursor-pointer whitespace-nowrap">
            <i data-lucide="activity" class="w-4 h-4 text-rose-400"></i>
            <span>Log XP Terbaru</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: KATALOG LENCANA (BADGES)                                            -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'badges'" class="space-y-4">
        
        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Category Pills Filter -->
            <div class="flex flex-wrap items-center gap-1.5 p-1 rounded-2xl bg-zinc-900 border border-zinc-800 text-xs">
                <a href="{{ route('admin.gamification.index', ['category' => 'all', 'q' => $searchQuery, 'tab' => 'badges']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $categoryFilter === 'all' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                    Semua
                </a>
                <a href="{{ route('admin.gamification.index', ['category' => 'milestone', 'q' => $searchQuery, 'tab' => 'badges']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $categoryFilter === 'milestone' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                    Pencapaian
                </a>
                <a href="{{ route('admin.gamification.index', ['category' => 'genre', 'q' => $searchQuery, 'tab' => 'badges']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $categoryFilter === 'genre' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                    Genre
                </a>
                <a href="{{ route('admin.gamification.index', ['category' => 'habit', 'q' => $searchQuery, 'tab' => 'badges']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $categoryFilter === 'habit' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                    Habit / Streak
                </a>
                <a href="{{ route('admin.gamification.index', ['category' => 'community', 'q' => $searchQuery, 'tab' => 'badges']) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition {{ $categoryFilter === 'community' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                    Komunitas
                </a>
            </div>

            <!-- Search and Add Badge Button -->
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('admin.gamification.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="category" value="{{ $categoryFilter }}">
                    <input type="hidden" name="tab" value="badges">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs focus-within:border-amber-500 transition">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-500"></i>
                        <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Cari lencana..." class="bg-transparent text-white outline-none border-none p-0 w-32 sm:w-48 text-xs">
                    </div>
                </form>

                <button type="button" 
                        @click="showAddBadgeModal = true; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })" 
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-400 hover:to-yellow-400 text-zinc-950 font-black text-xs transition flex items-center gap-2 shadow-lg shadow-amber-500/20 cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Badge</span>
                </button>
            </div>
        </div>

        <!-- Badges Table -->
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5">Lencana & Ikon</th>
                            <th class="px-4 py-3.5">Kategori</th>
                            <th class="px-4 py-3.5">Deskripsi & Syarat</th>
                            <th class="px-4 py-3.5">XP Reward</th>
                            <th class="px-4 py-3.5">Diraih Oleh</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60">
                        @forelse($badges as $badge)
                            <tr class="hover:bg-zinc-800/40 transition-colors">
                                <!-- Badge & Icon -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-{{ $badge->color }}-500/20 border border-{{ $badge->color }}-500/40 text-{{ $badge->color }}-400 flex items-center justify-center shrink-0 shadow">
                                            <i data-lucide="{{ $badge->lucide_icon }}" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-white text-xs">{{ $badge->name }}</div>
                                            <div class="font-mono text-[10px] text-zinc-500">{{ $badge->code }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                                        {{ $badge->category === 'milestone' ? 'bg-amber-500/15 text-amber-300 border border-amber-500/30' : '' }}
                                        {{ $badge->category === 'genre' ? 'bg-purple-500/15 text-purple-300 border border-purple-500/30' : '' }}
                                        {{ $badge->category === 'habit' ? 'bg-rose-500/15 text-rose-300 border border-rose-500/30' : '' }}
                                        {{ $badge->category === 'community' ? 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30' : '' }}">
                                        {{ $badge->category_label }}
                                    </span>
                                </td>

                                <!-- Description & Threshold -->
                                <td class="px-4 py-3.5 max-w-xs">
                                    <div class="text-zinc-300 leading-relaxed truncate" title="{{ $badge->description }}">{{ $badge->description }}</div>
                                    <div class="text-[10px] text-zinc-500 mt-0.5">Target: <strong class="text-zinc-300">{{ $badge->required_count }}x</strong> tindakan</div>
                                </td>

                                <!-- XP Reward -->
                                <td class="px-4 py-3.5 font-mono font-bold text-amber-400">
                                    +{{ number_format($badge->xp_reward) }} XP
                                </td>

                                <!-- Users count -->
                                <td class="px-4 py-3.5 font-mono font-bold text-zinc-300">
                                    {{ number_format($badge->users_count) }} User
                                </td>

                                <!-- Status Toggle -->
                                <td class="px-4 py-3.5">
                                    <form action="{{ route('admin.gamification.badges.toggle', $badge) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[10px] font-bold cursor-pointer transition {{ $badge->is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-zinc-800 text-zinc-500 border border-zinc-700 hover:bg-zinc-700' }}">
                                            {{ $badge->is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </button>
                                    </form>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" 
                                                @click="openEditModal({{ json_encode($badge) }})"
                                                class="p-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition cursor-pointer"
                                                title="Edit Badge">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <form action="{{ route('admin.gamification.badges.destroy', $badge) }}" method="POST" onsubmit="return confirm('Hapus badge {{ $badge->name }}? Badge akan dicabut dari seluruh pengguna.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition cursor-pointer" title="Hapus Badge">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                    Tidak ada badge yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($badges->hasPages())
                <div class="p-4 border-t border-zinc-800">
                    {{ $badges->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: KONFIGURASI TARIF XP & FITUR                                      -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'settings'" class="max-w-3xl space-y-6">
        <form action="{{ route('admin.gamification.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- XP Multipliers Card -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-5">
                <div class="border-b border-zinc-800 pb-3">
                    <h3 class="text-sm font-black text-white flex items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-400"></i>
                        <span>Kalkulasi & Bobot Perolehan XP</span>
                    </h3>
                    <p class="text-xs text-zinc-400 mt-0.5">Tentukan seberapa banyak XP yang didapatkan pengguna untuk setiap aktivitas.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- XP per watch minute -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-300">XP per Menit Tonton</label>
                        <div class="relative">
                            <input type="number" name="gamification_xp_watch_minute" value="{{ $settings['gamification_xp_watch_minute'] }}" min="0" max="100" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-zinc-500 font-bold">XP / min</span>
                        </div>
                        <p class="text-[10px] text-zinc-500">Standar: 1 XP per 1 menit durasi pemutaran.</p>
                    </div>

                    <!-- XP per Review -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-300">XP per Ulasan Film (Review)</label>
                        <div class="relative">
                            <input type="number" name="gamification_xp_review" value="{{ $settings['gamification_xp_review'] }}" min="0" max="1000" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-zinc-500 font-bold">XP</span>
                        </div>
                        <p class="text-[10px] text-zinc-500">Standar: 50 XP saat menulis review baru.</p>
                    </div>

                    <!-- XP per Comment -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-300">XP per Komentar Episode</label>
                        <div class="relative">
                            <input type="number" name="gamification_xp_comment" value="{{ $settings['gamification_xp_comment'] }}" min="0" max="500" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-zinc-500 font-bold">XP</span>
                        </div>
                        <p class="text-[10px] text-zinc-500">Standar: 15 XP per komentar diskusi.</p>
                    </div>

                    <!-- XP per Watch Party -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-300">XP per Partisipasi Watch Party</label>
                        <div class="relative">
                            <input type="number" name="gamification_xp_watch_party" value="{{ $settings['gamification_xp_watch_party'] }}" min="0" max="1000" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-zinc-500 font-bold">XP</span>
                        </div>
                        <p class="text-[10px] text-zinc-500">Standar: 30 XP untuk host & participant.</p>
                    </div>

                    <!-- Streak Bonus Base -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-xs font-bold text-zinc-300">Base Bonus Daily Watch Streak</label>
                        <div class="relative">
                            <input type="number" name="gamification_streak_bonus" value="{{ $settings['gamification_streak_bonus'] }}" min="0" max="500" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-zinc-500 font-bold">XP</span>
                        </div>
                        <p class="text-[10px] text-zinc-500">Bonus tambahan per hari saat streak nonton terjaga.</p>
                    </div>
                </div>
            </div>

            <!-- Feature Flags Card -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4">
                <div class="border-b border-zinc-800 pb-3">
                    <h3 class="text-sm font-black text-white flex items-center gap-2">
                        <i data-lucide="toggle-left" class="w-4 h-4 text-cyan-400"></i>
                        <span>Feature Switches</span>
                    </h3>
                </div>

                <div class="space-y-3 text-xs">
                    <label class="flex items-center justify-between p-3 rounded-xl bg-zinc-950 border border-zinc-800 cursor-pointer">
                        <div>
                            <div class="font-bold text-white">Sistem Gamification & Leveling</div>
                            <div class="text-[11px] text-zinc-400">Aktifkan kalkulasi XP, tingkatan level Cinephile, dan unlock badge.</div>
                        </div>
                        <input type="checkbox" name="feature_gamification" value="1" {{ $settings['feature_gamification'] ? 'checked' : '' }} class="w-4 h-4 rounded text-amber-500 focus:ring-0">
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl bg-zinc-950 border border-zinc-800 cursor-pointer">
                        <div>
                            <div class="font-bold text-white">Fitur Interactive Movie Wrapped</div>
                            <div class="text-[11px] text-zinc-400">Izinkan pengguna membuka halaman kilas balik cerita /wrapped.</div>
                        </div>
                        <input type="checkbox" name="feature_movie_wrapped" value="1" {{ $settings['feature_movie_wrapped'] ? 'checked' : '' }} class="w-4 h-4 rounded text-amber-500 focus:ring-0">
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-xl bg-zinc-950 border border-zinc-800 cursor-pointer">
                        <div>
                            <div class="font-bold text-white">Izinkan Mode Anonim di Leaderboard</div>
                            <div class="text-[11px] text-zinc-400">Pengguna dapat menyamarkan nama mereka di papan peringkat publik.</div>
                        </div>
                        <input type="checkbox" name="feature_leaderboard_anonymous_toggle" value="1" {{ $settings['feature_leaderboard_anonymous_toggle'] ? 'checked' : '' }} class="w-4 h-4 rounded text-amber-500 focus:ring-0">
                    </label>
                </div>
            </div>

            <button type="submit" class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-black text-xs transition shadow-lg shadow-amber-500/20 cursor-pointer">
                Simpan Perubahan Pengaturan
            </button>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: PENYESUAIAN XP & LENCANA PENGGUNA                                   -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'users'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Form Manual XP Adjustment -->
        <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4">
            <div class="border-b border-zinc-800 pb-3">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-400"></i>
                    <span>Beri / Sesuaikan XP Pengguna</span>
                </h3>
                <p class="text-xs text-zinc-400 mt-0.5">Tambahkan reward atau kurangi poin XP pengguna dengan catatan audit.</p>
            </div>

            <form action="{{ route('admin.gamification.users.award_xp') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-zinc-300">Pilih Pengguna</label>
                    <select name="user_id" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                        <option value="">-- Pilih Pengguna --</option>
                        @foreach($usersList as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }}) • {{ number_format($u->xp_total) }} XP (Lv. {{ $u->current_level }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-zinc-300">Jumlah XP (+ Tambah / - Kurangi)</label>
                    <input type="number" name="amount" required placeholder="Contoh: 100 atau -50" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-zinc-300">Alasan / Catatan Admin</label>
                    <input type="text" name="reason" required placeholder="Misal: Reward event komunitas / Koreksi bug" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-black text-xs transition shadow cursor-pointer">
                    Eksekusi Penyesuaian XP
                </button>
            </form>
        </div>

        <!-- Form Manual Award Badge -->
        <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4">
            <div class="border-b border-zinc-800 pb-3">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <i data-lucide="award" class="w-4 h-4 text-amber-400"></i>
                    <span>Berikan Lencana Khusus ke User</span>
                </h3>
                <p class="text-xs text-zinc-400 mt-0.5">Buka lencana secara langsung untuk pengguna tertentu.</p>
            </div>

            <form action="{{ route('admin.gamification.users.award_badge') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-zinc-300">Pilih Pengguna</label>
                    <select name="user_id" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                        <option value="">-- Pilih Pengguna --</option>
                        @foreach($usersList as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-zinc-300">Pilih Lencana</label>
                    <select name="badge_id" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                        <option value="">-- Pilih Lencana --</option>
                        @foreach($allBadges as $b)
                            <option value="{{ $b->id }}">[{{ $b->category_label }}] {{ $b->name }} (+{{ $b->xp_reward }} XP)</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-black text-xs transition shadow cursor-pointer">
                    Berikan Lencana ke User
                </button>
            </form>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: TOP LEADERBOARD PREVIEW                                           -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'leaderboard'" class="space-y-4">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-zinc-800 flex items-center justify-between">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Top 10 Global Cinephile Leaderboard</h3>
                <a href="{{ route('leaderboard') }}" target="_blank" class="text-xs text-amber-400 hover:underline flex items-center gap-1">
                    <span>Lihat Halaman Penuh</span>
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800">
                        <tr>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Level & Gelar</th>
                            <th class="px-4 py-3">Streak</th>
                            <th class="px-4 py-3 font-mono">Total XP</th>
                            <th class="px-4 py-3">Anonim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60 font-medium">
                        @foreach($topUsers as $index => $u)
                            @php
                                $tier = $u->level_info;
                            @endphp
                            <tr class="hover:bg-zinc-800/40">
                                <td class="px-4 py-3.5 font-mono font-bold {{ $index === 0 ? 'text-yellow-400' : ($index === 1 ? 'text-zinc-300' : ($index === 2 ? 'text-amber-600' : 'text-zinc-500')) }}">
                                    #{{ $index + 1 }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-white">{{ $u->name }}</div>
                                    <div class="text-[10px] text-zinc-500 font-mono">{{ $u->email }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                        <i data-lucide="{{ $tier['icon'] }}" class="w-3 h-3 text-amber-400"></i>
                                        <span>Lv. {{ $tier['level'] }} • {{ $tier['title'] }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 font-mono text-amber-400 font-bold">
                                    {{ $u->streak_count }} Hari
                                </td>
                                <td class="px-4 py-3.5 font-mono font-black text-white">
                                    {{ number_format($u->xp_total) }} XP
                                </td>
                                <td class="px-4 py-3.5 text-zinc-500 text-[10px]">
                                    {{ $u->is_anonymous_leaderboard ? 'Ya' : 'Tidak' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 5: LOG XP TERBARU                                                     -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'logs'" class="space-y-4">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-zinc-800">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Aktivitas & Log Distribusi XP Terbaru</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Sumber Aktivitas</th>
                            <th class="px-4 py-3 font-mono">XP Diberikan</th>
                            <th class="px-4 py-3">Detail / Metadata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60">
                        @forelse($recentXpLogs as $log)
                            <tr class="hover:bg-zinc-800/40">
                                <td class="px-4 py-3 text-zinc-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ $log->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-4 py-3 font-bold text-white">
                                    {{ $log->user->name ?? 'User Terhapus' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                        {{ $log->source }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono font-black {{ $log->amount >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $log->amount >= 0 ? '+' : '' }}{{ $log->amount }} XP
                                </td>
                                <td class="px-4 py-3 text-zinc-400 font-mono text-[10px] max-w-xs truncate">
                                    {{ json_encode($log->metadata) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-zinc-500">Belum ada log XP tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: TAMBAH BADGE BARU                                                  -->
    <!-- ========================================================================= -->
    <div x-show="showAddBadgeModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4"
             @click.outside="showAddBadgeModal = false">
            
            <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                <h3 class="font-black text-sm text-white flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4 text-amber-400"></i>
                    <span>Tambah Lencana Baru</span>
                </h3>
                <button type="button" @click="showAddBadgeModal = false" class="text-zinc-400 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form action="{{ route('admin.gamification.badges.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1 col-span-2">
                        <label class="text-xs font-bold text-zinc-300">Nama Lencana</label>
                        <input type="text" name="name" required placeholder="Misal: Penjelajah Galaksi" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Kode Unik (Opsional)</label>
                        <input type="text" name="code" placeholder="genre_scifi_master" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Kategori</label>
                        <select name="category" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                            <option value="milestone">Pencapaian Utama (Milestone)</option>
                            <option value="genre">Spesialis Genre</option>
                            <option value="habit">Kebiasaan Nonton (Habit/Streak)</option>
                            <option value="community">Sosial & Komunitas</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Ikon Lucide</label>
                        <input type="text" name="icon" required value="award" placeholder="Misal: rocket, film, flame, sparkles" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                        <span class="text-[9px] text-zinc-500">Nama icon Lucide (tanpa raw emoji)</span>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Warna Aksen</label>
                        <select name="color" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                            <option value="amber">Amber (Emas)</option>
                            <option value="purple">Purple (Ungu)</option>
                            <option value="rose">Rose (Merah Muda)</option>
                            <option value="cyan">Cyan (Biru Langit)</option>
                            <option value="emerald">Emerald (Hijau)</option>
                            <option value="yellow">Yellow (Kuning)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">XP Reward</label>
                        <input type="number" name="xp_reward" value="50" min="0" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Target Hitungan</label>
                        <input type="number" name="required_count" value="1" min="1" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1 col-span-2">
                        <label class="text-xs font-bold text-zinc-300">Deskripsi / Syarat Buka</label>
                        <textarea name="description" required rows="2" placeholder="Tonton minimal 5 film sci-fi untuk membuka lencana ini." class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                    <button type="button" @click="showAddBadgeModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-zinc-300 hover:text-white text-xs font-bold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 text-xs font-black transition shadow">
                        Simpan Lencana
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: EDIT BADGE                                                         -->
    <!-- ========================================================================= -->
    <div x-show="showEditBadgeModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-zinc-900 border border-zinc-800 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4"
             @click.outside="showEditBadgeModal = false">
            
            <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                <h3 class="font-black text-sm text-white flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-4 h-4 text-amber-400"></i>
                    <span>Edit Lencana</span>
                </h3>
                <button type="button" @click="showEditBadgeModal = false" class="text-zinc-400 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form :action="'{{ url('admin/gamification/badges') }}/' + editBadgeData.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1 col-span-2">
                        <label class="text-xs font-bold text-zinc-300">Nama Lencana</label>
                        <input type="text" name="name" x-model="editBadgeData.name" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Kode Unik</label>
                        <input type="text" name="code" x-model="editBadgeData.code" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Kategori</label>
                        <select name="category" x-model="editBadgeData.category" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                            <option value="milestone">Pencapaian Utama (Milestone)</option>
                            <option value="genre">Spesialis Genre</option>
                            <option value="habit">Kebiasaan Nonton (Habit/Streak)</option>
                            <option value="community">Sosial & Komunitas</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Ikon Lucide</label>
                        <input type="text" name="icon" x-model="editBadgeData.icon" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Warna Aksen</label>
                        <select name="color" x-model="editBadgeData.color" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none">
                            <option value="amber">Amber (Emas)</option>
                            <option value="purple">Purple (Ungu)</option>
                            <option value="rose">Rose (Merah Muda)</option>
                            <option value="cyan">Cyan (Biru Langit)</option>
                            <option value="emerald">Emerald (Hijau)</option>
                            <option value="yellow">Yellow (Kuning)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">XP Reward</label>
                        <input type="number" name="xp_reward" x-model="editBadgeData.xp_reward" min="0" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-zinc-300">Target Hitungan</label>
                        <input type="number" name="required_count" x-model="editBadgeData.required_count" min="1" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="space-y-1 col-span-2">
                        <label class="text-xs font-bold text-zinc-300">Deskripsi</label>
                        <textarea name="description" x-model="editBadgeData.description" required rows="2" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:border-amber-400 focus:outline-none"></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="flex items-center gap-2 text-xs text-zinc-300 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" :checked="editBadgeData.is_active" class="w-4 h-4 rounded text-amber-500">
                            <span>Status Lencana Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-800">
                    <button type="button" @click="showEditBadgeModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-zinc-300 hover:text-white text-xs font-bold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 text-xs font-black transition shadow">
                        Perbarui Lencana
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endpush
@endsection
