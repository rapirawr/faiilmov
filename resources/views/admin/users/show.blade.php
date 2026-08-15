@extends('layouts.admin')

@section('title', 'Detail User: ' . $user->name . ' | faiiladmin')
@section('page_title', 'Detail Pengguna: ' . $user->name)

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'profiles' }">

    @if($user->trashed())
        <!-- Trashed Warning & Action Banner -->
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center shrink-0">
                    <i data-lucide="trash-2" class="w-5 h-5 text-rose-400"></i>
                </div>
                <div>
                    <p class="font-bold text-white text-sm font-['Outfit']">Akun Berada di Tempat Sampah (Soft Deleted)</p>
                    <p class="text-zinc-400 text-[11px]">Akun ini dihapus pada {{ $user->deleted_at->format('d M Y, H:i') }}. Pengguna tidak dapat login ke aplikasi.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" onsubmit="return confirm('Pulihkan akun pengguna ini?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-bold border border-emerald-500/30 transition-all flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span>Pulihkan Akun</span>
                    </button>
                </form>
                <form action="{{ route('admin.users.force_delete', $user->id) }}" method="POST" onsubmit="return confirm('Hapus permanen akun ini? Seluruh data riwayat dan ulasan akan dihapus selamanya!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="trash" class="w-4 h-4"></i>
                        <span>Hapus Permanen</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- User Header Card -->
    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-14 h-14 rounded-2xl object-cover border border-amber-500/30 shrink-0 bg-zinc-800 {{ $user->trashed() ? 'grayscale' : '' }}" onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode($user->name) }}';">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-white font-['Outfit'] {{ $user->trashed() ? 'line-through text-zinc-400' : '' }}">{{ $user->name }}</h2>
                    @if($user->trashed())
                        <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 font-extrabold text-[10px] uppercase border border-rose-500/30">Terhapus</span>
                    @endif
                    @if($user->isAdmin())
                        <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-extrabold text-[10px] uppercase border border-amber-500/30">Admin</span>
                    @endif
                    @if($user->isBanned())
                        <span class="px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 font-extrabold text-[10px] uppercase">Banned</span>
                    @endif
                </div>
                <p class="text-xs text-zinc-400 mt-0.5">{{ $user->email }} • Terdaftar {{ $user->created_at->format('d M Y, H:i') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if(!$user->trashed() && !$user->isAdmin())
                @if($user->isBanned())
                    <form action="{{ route('admin.users.unban', $user->id) }}" method="POST" onsubmit="return confirm('Cabut suspen user ini?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-bold text-xs border border-emerald-500/30 transition-all flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="user-check" class="w-4 h-4"></i>
                            <span>Unban User</span>
                        </button>
                    </form>
                @endif
            @endif

            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-2 border border-white/10 transition-all">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-white/10 text-xs">
        <button @click="activeTab = 'profiles'" 
                :class="activeTab === 'profiles' ? 'border-amber-500 text-amber-400 font-bold bg-amber-500/10' : 'border-transparent text-zinc-400 hover:text-white'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="users" class="w-4 h-4"></i>
            <span>Sub-Profil ({{ $user->profiles->count() }})</span>
        </button>

        <button @click="activeTab = 'history'" 
                :class="activeTab === 'history' ? 'border-sky-500 text-sky-400 font-bold bg-sky-500/10' : 'border-transparent text-zinc-400 hover:text-white'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="history" class="w-4 h-4"></i>
            <span>Riwayat Tontonan ({{ $user->watchHistories->count() }})</span>
        </button>

        <button @click="activeTab = 'watchlist'" 
                :class="activeTab === 'watchlist' ? 'border-purple-500 text-purple-300 font-bold bg-purple-500/10' : 'border-transparent text-zinc-400 hover:text-white'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="bookmark" class="w-4 h-4"></i>
            <span>Daftar Saya ({{ $user->watchlists->count() }})</span>
        </button>

        <button @click="activeTab = 'reviews'" 
                :class="activeTab === 'reviews' ? 'border-emerald-500 text-emerald-400 font-bold bg-emerald-500/10' : 'border-transparent text-zinc-400 hover:text-white'"
                class="px-4 py-2.5 rounded-t-xl border-b-2 transition-all flex items-center gap-2 cursor-pointer">
            <i data-lucide="message-square" class="w-4 h-4"></i>
            <span>Ulasan ({{ $user->reviews->count() }})</span>
        </button>
    </div>

    <!-- Tab 1: Sub-Profiles -->
    <div x-show="activeTab === 'profiles'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        @forelse($user->profiles as $p)
            <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-3 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="{{ $p->avatar_url }}" alt="{{ $p->name }}" class="w-10 h-10 rounded-full object-cover border border-white/10 bg-zinc-800" onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode($p->name) }}';">
                        <div>
                            <h4 class="font-bold text-white text-sm">{{ $p->name }}</h4>
                            <p class="text-[10px] text-zinc-400">{{ $p->is_child ? '🧒 Kids Profile' : '👤 Adult Profile' }}</p>
                        </div>
                    </div>
                    @if($p->is_main)
                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-extrabold text-[9px] uppercase">Utama</span>
                    @endif
                </div>

                <div class="pt-2 border-t border-white/5 space-y-2 text-xs">
                    <div class="flex justify-between text-zinc-400">
                        <span>Parental PIN:</span>
                        <span class="font-mono font-bold {{ $p->pin ? 'text-amber-400' : 'text-zinc-500' }}">
                            {{ $p->pin ? '🔒 Set' : '🔓 Off' }}
                        </span>
                    </div>
                    <div class="flex justify-between text-zinc-400">
                        <span>Max Usia Rating:</span>
                        <span class="font-bold {{ $p->is_child ? 'text-purple-300' : 'text-white' }}">
                            {{ $p->is_child ? 'Ramah Anak (G, PG)' : 'Tanpa Batas (Dewasa)' }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-xs text-zinc-500 col-span-3 py-6 text-center">Belum ada sub-profil tambahan.</p>
        @endforelse
    </div>

    <!-- Tab 2: Watch History -->
    <div x-show="activeTab === 'history'" class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3">Film</th>
                        <th class="px-4 py-3">Progress Tonton</th>
                        <th class="px-4 py-3">Terakhir Ditonton</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($user->watchHistories as $h)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3 flex items-center gap-3">
                                <img src="{{ $h->film->poster_url ?? '' }}" referrerpolicy="no-referrer" class="w-7 h-10 object-cover rounded shrink-0">
                                <span class="font-bold text-white text-xs">{{ $h->film->title ?? 'Film Dihapus' }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-amber-400 font-bold">
                                {{ gmdate("H:i:s", $h->progress_seconds) }}
                            </td>
                            <td class="px-4 py-3 text-zinc-400 text-[11px]">
                                {{ $h->updated_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-zinc-500">Belum ada riwayat tontonan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 3: Watchlist -->
    <div x-show="activeTab === 'watchlist'" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
        @forelse($user->watchlists as $w)
            <div class="p-2 rounded-xl bg-zinc-900/60 border border-white/10 space-y-1.5">
                <img src="{{ $w->film->poster_url ?? '' }}" referrerpolicy="no-referrer" class="w-full h-36 object-cover rounded-lg">
                <p class="font-bold text-white text-xs truncate">{{ $w->film->title ?? 'Film Dihapus' }}</p>
            </div>
        @empty
            <p class="text-xs text-zinc-500 col-span-6 py-6 text-center">Daftar Saya kosong.</p>
        @endforelse
    </div>

    <!-- Tab 4: Reviews -->
    <div x-show="activeTab === 'reviews'" class="space-y-3">
        @forelse($user->reviews as $r)
            <div class="p-4 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-white text-sm">{{ $r->film->title ?? 'Film Dihapus' }}</span>
                    <span class="text-amber-400 font-bold flex items-center gap-1">
                        <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                        {{ $r->rating }}/10
                    </span>
                </div>
                <p class="text-zinc-300 leading-relaxed">{{ $r->comment }}</p>
                <p class="text-[10px] text-zinc-500">{{ $r->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-xs text-zinc-500 py-6 text-center">Belum pernah menulis ulasan.</p>
        @endforelse
    </div>

</div>
@endsection
