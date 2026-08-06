@extends('layouts.admin')

@section('title', 'Detail User: ' . $user->name . ' | faiiladmin')
@section('page_title', 'Detail Pengguna: ' . $user->name)

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="text-xs text-zinc-400 hover:text-white flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke List User</span>
        </a>
    </div>

    <!-- User Profile Header -->
    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-6 shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/20 text-amber-400 font-extrabold text-2xl flex items-center justify-center border border-amber-500/30">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-white font-['Outfit']">{{ $user->name }}</h2>
                <p class="text-xs text-zinc-400">{{ $user->email }}</p>
                <p class="text-[11px] text-zinc-500 mt-1">Terdaftar sejak {{ $user->created_at->format('d M Y H:i') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($user->isBanned())
                <span class="px-3 py-1 rounded-full bg-red-500/20 text-red-400 text-xs font-bold border border-red-500/30">
                    Banned ({{ $user->banned_reason }})
                </span>
            @else
                <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold border border-emerald-500/30">
                    Status: Aktif
                </span>
            @endif
        </div>
    </div>

    <!-- History Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Review History -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-white font-['Outfit'] flex items-center gap-2">
                <i data-lucide="message-square" class="w-5 h-5 text-amber-400"></i>
                <span>Histori Ulasan ({{ $user->reviews->count() }})</span>
            </h3>

            <div class="space-y-3">
                @forelse($user->reviews as $rev)
                    <div class="p-4 rounded-xl bg-zinc-900/60 border border-white/10 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-white text-xs">{{ $rev->film->title ?? 'Film Terhapus' }}</span>
                            <span class="text-amber-400 font-bold text-xs flex items-center gap-1">
                                <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                {{ $rev->rating }}/10
                            </span>
                        </div>
                        <p class="text-xs text-zinc-300">{{ $rev->comment }}</p>
                        <p class="text-[10px] text-zinc-500 text-right">{{ $rev->created_at->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <div class="p-6 rounded-xl bg-zinc-900/40 border border-white/5 text-center text-xs text-zinc-500">
                        Belum ada ulasan yang ditulis.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Watchlist -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-white font-['Outfit'] flex items-center gap-2">
                <i data-lucide="bookmark" class="w-5 h-5 text-blue-400"></i>
                <span>Watchlist ({{ $user->watchlists->count() }})</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse($user->watchlists as $wl)
                    <div class="p-3 rounded-xl bg-zinc-900/60 border border-white/10 flex items-center gap-3">
                        <img src="{{ $wl->film->poster_url ?? '' }}" class="w-10 h-14 object-cover rounded-lg shrink-0">
                        <div>
                            <p class="font-bold text-white text-xs line-clamp-1">{{ $wl->film->title ?? 'Film' }}</p>
                            <p class="text-[10px] text-zinc-400 uppercase mt-0.5">{{ $wl->status }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 p-6 rounded-xl bg-zinc-900/40 border border-white/5 text-center text-xs text-zinc-500">
                        Watchlist kosong.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
