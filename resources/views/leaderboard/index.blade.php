@extends('layouts.app')

@section('title', 'Leaderboard Cinephile | faiilmov')

@section('content')
<div class="min-h-screen bg-dark-950 text-white pb-24 pt-4 sm:pt-8" x-data="{
    activeTab: '{{ $period }}',
    showBadgeModal: false,
    selectedCategory: 'all',
    togglePrivacy() {
        fetch('{{ route('leaderboard.toggle-privacy') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}">
    <!-- Background Atmospheric Glows -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] sm:w-[900px] h-[400px] bg-gradient-to-b from-amber-500/10 via-purple-500/5 to-transparent blur-3xl rounded-full"></div>
        <div class="absolute top-1/3 -left-48 w-96 h-96 bg-cyan-500/10 blur-3xl rounded-full"></div>
        <div class="absolute bottom-1/4 -right-48 w-96 h-96 bg-rose-500/10 blur-3xl rounded-full"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-8">
        
        <!-- Header Banner & Page Intro -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 glass-panel p-6 sm:p-8 rounded-3xl border border-white/10 relative overflow-hidden">
            <div class="absolute right-0 top-0 bottom-0 w-1/3 bg-gradient-to-l from-amber-500/10 to-transparent pointer-events-none"></div>
            
            <div class="space-y-3 relative z-10 max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-bold uppercase tracking-wider">
                    <i data-lucide="trophy" class="w-3.5 h-3.5"></i>
                    <span>Cinephile Hall of Fame</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white font-chillax">
                    Papan Peringkat Global
                </h1>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    Kumpulkan Cinephile XP dengan menonton film, mempertahankan streak harian, menulis ulasan, serta berpartisipasi dalam Watch Party.
                </p>
            </div>

            <!-- Action buttons: Badges Catalog & Movie Wrapped CTA -->
            <div class="flex flex-wrap items-center gap-3 relative z-10">
                <button @click="showBadgeModal = true" 
                        class="px-4 py-2.5 rounded-2xl glass-card border border-white/15 hover:border-amber-400/40 text-xs font-bold text-zinc-200 hover:text-white transition-all flex items-center gap-2 shadow-sm cursor-pointer">
                    <i data-lucide="award" class="w-4 h-4 text-amber-400"></i>
                    <span>Katalog Badge</span>
                </button>

                <a href="{{ route('wrapped') }}" 
                   class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-zinc-950 font-extrabold text-xs transition-all flex items-center gap-2 shadow-lg shadow-amber-500/20">
                    <i data-lucide="sparkles" class="w-4 h-4 text-zinc-950"></i>
                    <span>Movie Wrapped</span>
                </a>
            </div>
        </div>

        <!-- Period Switcher & Filter Tabs -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center p-1.5 rounded-2xl bg-dark-900/90 border border-white/10 w-full sm:w-auto">
                <a href="{{ route('leaderboard', ['period' => 'weekly']) }}" 
                   class="flex-1 sm:flex-initial px-5 py-2 rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center gap-2 {{ $period === 'weekly' ? 'bg-amber-500 text-zinc-950 shadow-md font-extrabold' : 'text-zinc-400 hover:text-white' }}">
                    <i data-lucide="flame" class="w-3.5 h-3.5"></i>
                    <span>Mingguan</span>
                </a>
                <a href="{{ route('leaderboard', ['period' => 'monthly']) }}" 
                   class="flex-1 sm:flex-initial px-5 py-2 rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center gap-2 {{ $period === 'monthly' ? 'bg-amber-500 text-zinc-950 shadow-md font-extrabold' : 'text-zinc-400 hover:text-white' }}">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    <span>Bulanan</span>
                </a>
                <a href="{{ route('leaderboard', ['period' => 'all']) }}" 
                   class="flex-1 sm:flex-initial px-5 py-2 rounded-xl text-xs font-bold transition-all text-center flex items-center justify-center gap-2 {{ $period === 'all' ? 'bg-amber-500 text-zinc-950 shadow-md font-extrabold' : 'text-zinc-400 hover:text-white' }}">
                    <i data-lucide="infinity" class="w-3.5 h-3.5"></i>
                    <span>Sepanjang Masa</span>
                </a>
            </div>

            <!-- Current User Privacy Mode Quick Toggle -->
            @auth
                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                    <span class="text-xs text-zinc-400">Mode Anonim:</span>
                    <button type="button" 
                            @click="togglePrivacy()"
                            class="px-3.5 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer {{ Auth::user()->is_anonymous_leaderboard ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : 'bg-white/10 text-zinc-300 border-white/15 hover:bg-white/15' }}">
                        <i data-lucide="{{ Auth::user()->is_anonymous_leaderboard ? 'eye-off' : 'eye' }}" class="w-3.5 h-3.5"></i>
                        <span>{{ Auth::user()->is_anonymous_leaderboard ? 'Nama Disamarkan' : 'Nama Publik' }}</span>
                    </button>
                </div>
            @endauth
        </div>

        <!-- Top 3 Podium Cards (Gold, Silver, Bronze) -->
        @if(count($podium) > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6 pt-4 sm:pt-6 items-end">
                
                <!-- Rank 2: Silver (Left on Desktop) -->
                @if(isset($podium[1]))
                    @php $p2 = $podium[1]; @endphp
                    <div class="order-2 md:order-1 glass-panel rounded-3xl border border-slate-300/30 p-6 flex flex-col items-center text-center relative overflow-hidden group hover:border-slate-300/60 transition-all shadow-lg bg-gradient-to-b from-slate-400/10 via-dark-900/60 to-dark-950">
                        <div class="absolute top-3 left-3 w-8 h-8 rounded-full bg-slate-300/20 border border-slate-300/40 flex items-center justify-center font-extrabold text-xs text-slate-200 font-mono">
                            #2
                        </div>
                        <div class="relative mb-4">
                            <img src="{{ $p2['avatar'] }}" alt="{{ $p2['name'] }}" class="w-20 h-20 rounded-full object-cover border-2 border-slate-300 shadow-md">
                            <div class="absolute -bottom-2 -right-1 px-2 py-0.5 rounded-lg bg-slate-400 text-zinc-950 text-[10px] font-black uppercase flex items-center gap-1 shadow">
                                <i data-lucide="medal" class="w-3 h-3"></i>
                                <span>Perak</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-base text-white truncate max-w-full mb-1">{{ $p2['name'] }}</h3>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg {{ $p2['bg_class'] }} text-[11px] font-semibold mb-3">
                            <i data-lucide="{{ $p2['tier_icon'] }}" class="w-3 h-3"></i>
                            <span>{{ $p2['tier_title'] }}</span>
                        </div>
                        <div class="text-xs font-mono font-bold text-slate-300 bg-white/5 px-4 py-2 rounded-xl border border-white/10 w-full flex items-center justify-between">
                            <span class="text-zinc-400 text-[11px]">Cinephile XP</span>
                            <span class="text-white font-extrabold text-sm">{{ number_format($p2['xp']) }}</span>
                        </div>
                    </div>
                @endif

                <!-- Rank 1: Gold (Center on Desktop - Elevated) -->
                @if(isset($podium[0]))
                    @php $p1 = $podium[0]; @endphp
                    <div class="order-1 md:order-2 glass-panel rounded-3xl border border-amber-400/50 p-7 flex flex-col items-center text-center relative overflow-hidden group hover:border-amber-400/80 transition-all shadow-xl bg-gradient-to-b from-amber-500/15 via-dark-900/80 to-dark-950 md:-translate-y-4">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500"></div>
                        <div class="absolute top-3.5 left-3.5 w-9 h-9 rounded-full bg-amber-400/25 border border-amber-400/50 flex items-center justify-center font-black text-sm text-amber-300 font-mono shadow-sm">
                            #1
                        </div>
                        
                        <!-- Crown Icon at top of avatar -->
                        <div class="mb-1 text-amber-400 animate-bounce">
                            <i data-lucide="crown" class="w-7 h-7"></i>
                        </div>

                        <div class="relative mb-4">
                            <img src="{{ $p1['avatar'] }}" alt="{{ $p1['name'] }}" class="w-24 h-24 rounded-full object-cover border-4 border-amber-400 shadow-lg shadow-amber-500/30">
                            <div class="absolute -bottom-2 -right-1 px-2.5 py-0.5 rounded-lg bg-amber-400 text-zinc-950 text-[10px] font-black uppercase flex items-center gap-1 shadow-md">
                                <i data-lucide="award" class="w-3 h-3"></i>
                                <span>Champion</span>
                            </div>
                        </div>
                        <h3 class="font-extrabold text-lg text-white truncate max-w-full mb-1">{{ $p1['name'] }}</h3>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg {{ $p1['bg_class'] }} text-xs font-bold mb-4 shadow-sm">
                            <i data-lucide="{{ $p1['tier_icon'] }}" class="w-3.5 h-3.5"></i>
                            <span>{{ $p1['tier_title'] }}</span>
                        </div>
                        <div class="text-xs font-mono font-bold text-amber-300 bg-amber-500/10 px-4 py-2.5 rounded-xl border border-amber-500/30 w-full flex items-center justify-between">
                            <span class="text-amber-200/70 text-[11px]">Total XP</span>
                            <span class="text-amber-300 font-black text-base">{{ number_format($p1['xp']) }}</span>
                        </div>
                    </div>
                @endif

                <!-- Rank 3: Bronze (Right on Desktop) -->
                @if(isset($podium[2]))
                    @php $p3 = $podium[2]; @endphp
                    <div class="order-3 glass-panel rounded-3xl border border-amber-700/30 p-6 flex flex-col items-center text-center relative overflow-hidden group hover:border-amber-700/60 transition-all shadow-lg bg-gradient-to-b from-amber-700/10 via-dark-900/60 to-dark-950">
                        <div class="absolute top-3 left-3 w-8 h-8 rounded-full bg-amber-700/20 border border-amber-700/40 flex items-center justify-center font-extrabold text-xs text-amber-400 font-mono">
                            #3
                        </div>
                        <div class="relative mb-4">
                            <img src="{{ $p3['avatar'] }}" alt="{{ $p3['name'] }}" class="w-20 h-20 rounded-full object-cover border-2 border-amber-600 shadow-md">
                            <div class="absolute -bottom-2 -right-1 px-2 py-0.5 rounded-lg bg-amber-600 text-white text-[10px] font-black uppercase flex items-center gap-1 shadow">
                                <i data-lucide="medal" class="w-3 h-3"></i>
                                <span>Perunggu</span>
                            </div>
                        </div>
                        <h3 class="font-bold text-base text-white truncate max-w-full mb-1">{{ $p3['name'] }}</h3>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg {{ $p3['bg_class'] }} text-[11px] font-semibold mb-3">
                            <i data-lucide="{{ $p3['tier_icon'] }}" class="w-3 h-3"></i>
                            <span>{{ $p3['tier_title'] }}</span>
                        </div>
                        <div class="text-xs font-mono font-bold text-amber-400 bg-white/5 px-4 py-2 rounded-xl border border-white/10 w-full flex items-center justify-between">
                            <span class="text-zinc-400 text-[11px]">Cinephile XP</span>
                            <span class="text-white font-extrabold text-sm">{{ number_format($p3['xp']) }}</span>
                        </div>
                    </div>
                @endif

            </div>
        @else
            <!-- Empty state when no activity in period -->
            <div class="glass-panel p-12 rounded-3xl border border-white/10 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-400 flex items-center justify-center mx-auto">
                    <i data-lucide="trophy" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-white">Belum Ada Aktivitas di Periode Ini</h3>
                <p class="text-xs text-zinc-400 max-w-md mx-auto">
                    Jadilah yang pertama menonton film dan mencatatkan nama Anda di puncak Leaderboard!
                </p>
            </div>
        @endif

        <!-- Full Ranking List (Rank 4+) -->
        @if(count($rankings) > 0)
            <div class="glass-panel rounded-3xl border border-white/10 overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between bg-dark-900/60">
                    <h2 class="text-sm font-bold text-zinc-200 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="list-ordered" class="w-4 h-4 text-amber-400"></i>
                        <span>Peringkat 4 - {{ count($rankings) + 3 }}</span>
                    </h2>
                    <span class="text-xs text-zinc-400">Total {{ count($rankings) + count($podium) }} Cinephile</span>
                </div>

                <div class="divide-y divide-white/5">
                    @foreach($rankings as $row)
                        <div class="px-5 sm:px-6 py-4 flex items-center justify-between gap-4 hover:bg-white/[0.03] transition-colors {{ $row['is_current'] ? 'bg-amber-500/10 border-l-4 border-amber-400' : '' }}">
                            
                            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                                <!-- Rank number -->
                                <div class="w-8 text-center font-mono font-bold text-sm text-zinc-400">
                                    #{{ $row['rank'] }}
                                </div>

                                <!-- Avatar -->
                                <div class="relative shrink-0">
                                    <img src="{{ $row['avatar'] }}" alt="{{ $row['name'] }}" class="w-11 h-11 rounded-full object-cover border border-white/15">
                                </div>

                                <!-- Name & Tier -->
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-sm text-white truncate">{{ $row['name'] }}</h4>
                                        @if($row['is_current'])
                                            <span class="px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-300 text-[10px] font-extrabold">Anda</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md {{ $row['bg_class'] }} text-[10px] font-semibold">
                                            <i data-lucide="{{ $row['tier_icon'] }}" class="w-3 h-3"></i>
                                            <span>{{ $row['tier_title'] }}</span>
                                        </span>
                                        <span class="text-[11px] text-zinc-500">Lv. {{ $row['level'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- XP Count -->
                            <div class="text-right shrink-0">
                                <div class="font-mono font-black text-sm text-amber-400">{{ number_format($row['xp']) }}</div>
                                <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">XP</div>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Floating / Sticky Current User Rank Bar (if user is logged in) -->
        @auth
            @if($currentUserRank)
                <div class="sticky bottom-6 z-30">
                    <div class="glass-panel p-4 sm:p-5 rounded-2xl border border-amber-500/40 bg-dark-950/95 backdrop-blur-xl shadow-2xl flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                            <div class="px-3 py-1.5 rounded-xl bg-amber-500 text-zinc-950 font-mono font-black text-xs sm:text-sm">
                                #{{ $currentUserRank['rank'] }}
                            </div>
                            <img src="{{ $currentUserRank['avatar'] }}" alt="{{ $currentUserRank['name'] }}" class="w-10 h-10 rounded-full object-cover border border-amber-400">
                            <div class="min-w-0">
                                <div class="font-bold text-xs sm:text-sm text-white truncate flex items-center gap-2">
                                    <span>{{ $currentUserRank['name'] }}</span>
                                    <span class="text-[10px] text-zinc-400">(Peringkat Anda)</span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-300">
                                        <i data-lucide="{{ $currentUserRank['tier_icon'] }}" class="w-3 h-3"></i>
                                        <span>{{ $currentUserRank['tier_title'] }}</span>
                                    </span>
                                    <span class="text-[10px] text-zinc-500">Lv. {{ $currentUserRank['level'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <div class="font-mono font-black text-sm sm:text-base text-amber-300">{{ number_format($currentUserRank['xp']) }}</div>
                            <div class="text-[10px] text-zinc-400 font-bold uppercase">Cinephile XP</div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

    </div>

    <!-- Badge Catalog Modal / Showcase Drawer -->
    <div x-show="showBadgeModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/80 backdrop-blur-md" 
         style="display:none;" 
         @keydown.escape.window="showBadgeModal = false">
        
        <div @click.outside="showBadgeModal = false" 
             class="glass-panel w-full max-w-4xl max-h-[85vh] rounded-3xl border border-white/15 bg-dark-950 flex flex-col shadow-2xl overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between bg-dark-900/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Katalog Badge Pencapaian</h3>
                        <p class="text-xs text-zinc-400">Raih semua lencana sinematik dengan menonton dan berinteraksi</p>
                    </div>
                </div>
                <button @click="showBadgeModal = false" class="text-zinc-400 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Category Filter Tabs -->
            <div class="px-6 py-3 border-b border-white/5 bg-dark-900/40 flex items-center gap-2 overflow-x-auto">
                <button @click="selectedCategory = 'all'" 
                        :class="selectedCategory === 'all' ? 'bg-amber-500 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white bg-white/5'" 
                        class="px-3 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap cursor-pointer">
                    Semua Badge
                </button>
                <button @click="selectedCategory = 'milestone'" 
                        :class="selectedCategory === 'milestone' ? 'bg-amber-500 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white bg-white/5'" 
                        class="px-3 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap cursor-pointer">
                    Pencapaian Utama
                </button>
                <button @click="selectedCategory = 'genre'" 
                        :class="selectedCategory === 'genre' ? 'bg-amber-500 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white bg-white/5'" 
                        class="px-3 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap cursor-pointer">
                    Spesialis Genre
                </button>
                <button @click="selectedCategory = 'habit'" 
                        :class="selectedCategory === 'habit' ? 'bg-amber-500 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white bg-white/5'" 
                        class="px-3 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap cursor-pointer">
                    Kebiasaan Nonton
                </button>
                <button @click="selectedCategory = 'community'" 
                        :class="selectedCategory === 'community' ? 'bg-amber-500 text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white bg-white/5'" 
                        class="px-3 py-1.5 rounded-xl text-xs transition-all whitespace-nowrap cursor-pointer">
                    Sosial & Komunitas
                </button>
            </div>

            <!-- Badges Grid View -->
            <div class="p-6 overflow-y-auto space-y-6 max-h-[60vh]">
                @foreach($badges as $category => $categoryBadges)
                    <div x-show="selectedCategory === 'all' || selectedCategory === '{{ $category }}'" class="space-y-3">
                        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="tag" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span>{{ $categoryBadges->first()->category_label }}</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($categoryBadges as $badge)
                                @php $isUnlocked = in_array($badge->id, $userBadgesIds); @endphp
                                <div class="glass-card rounded-2xl p-4 border transition-all relative overflow-hidden flex items-start gap-3.5 {{ $isUnlocked ? 'border-amber-500/40 bg-amber-500/5' : 'border-white/10 opacity-70 bg-dark-900/50' }}">
                                    
                                    <!-- Badge Icon Box -->
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border {{ $isUnlocked ? 'bg-amber-500/20 border-amber-500/40 text-amber-400' : 'bg-zinc-800 border-zinc-700 text-zinc-500' }}">
                                        <i data-lucide="{{ $badge->lucide_icon }}" class="w-6 h-6"></i>
                                    </div>

                                    <!-- Badge Details -->
                                    <div class="space-y-1 min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <h5 class="font-bold text-xs text-white truncate">{{ $badge->name }}</h5>
                                            @if($isUnlocked)
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-extrabold">Terbuka</span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-400 text-[9px] font-bold">Terkunci</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-zinc-400 leading-relaxed">{{ $badge->description }}</p>
                                        <div class="flex items-center gap-2 pt-1">
                                            <span class="text-[10px] font-mono font-bold text-amber-400">+{{ $badge->xp_reward }} XP</span>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-white/10 bg-dark-900/80 flex items-center justify-between text-xs text-zinc-400">
                <span>Tingkatkan level Cinephile untuk membuka gelar prestisius.</span>
                <button @click="showBadgeModal = false" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold transition-colors cursor-pointer">
                    Tutup
                </button>
            </div>

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
