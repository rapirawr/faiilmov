@extends('layouts.admin')

@section('title', 'Manajemen Watch Parties | faiiladmin')
@section('page_title', 'Manajemen Watch Parties (Nonton Bareng)')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar & Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('admin.watch_parties.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[220px]">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode Ruangan, Host, atau Judul Film..." 
                       class="w-full bg-transparent py-2 text-xs text-white placeholder-zinc-500 border-none outline-none focus:ring-0">
            </div>

            <select name="status" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif (Sedang Berjalan)</option>
                <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Selesai / Ditutup</option>
            </select>
        </form>

        <div class="flex items-center gap-3 text-xs">
            <span class="px-3.5 py-2 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-300 font-bold">
                {{ $activeCount }} Ruangan Aktif
            </span>
            <span class="px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 text-zinc-400 font-semibold">
                {{ $endedCount }} Sesi Selesai
            </span>
        </div>
    </div>

    <!-- Watch Parties Table -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">Kode Room</th>
                        <th class="px-4 py-3.5">Film</th>
                        <th class="px-4 py-3.5">Host Sesi</th>
                        <th class="px-4 py-3.5">Peserta</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Dibuat</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($watchParties as $party)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-xl bg-white/10 border border-white/10 text-amber-400 font-mono font-extrabold text-xs">
                                    #{{ $party->room_code }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 flex items-center gap-3">
                                <img src="{{ $party->film->poster_url ?? '' }}" class="w-8 h-11 object-cover rounded shrink-0">
                                <div>
                                    <p class="font-bold text-white text-xs line-clamp-1">{{ $party->film->title ?? 'Film Dihapus' }}</p>
                                    @if($party->season_number)
                                        <p class="text-[10px] text-purple-300">S{{ $party->season_number }} E{{ $party->episode_number }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-white text-xs">{{ $party->hostUser->name ?? $party->host_guest_name }}</p>
                                <p class="text-[10px] text-zinc-400">{{ $party->hostUser->email ?? 'Guest Host' }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10 text-zinc-200 font-bold text-[11px] flex items-center gap-1 w-fit">
                                    <i data-lucide="users" class="w-3 h-3 text-sky-400"></i>
                                    <span>{{ $party->participants->count() }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($party->status === 'active')
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-extrabold flex items-center gap-1 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span>Aktif</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-400 text-[10px] font-bold">
                                        Selesai
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-400 text-[11px]">
                                {{ $party->created_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.watch_parties.show', $party->id) }}" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20" title="Detail Ruangan & Chat Log">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    @if($party->status === 'active')
                                        <form action="{{ route('admin.watch_parties.force_close', $party->id) }}" method="POST" onsubmit="return confirm('TUTUP PAKSA ruang Nobar ini?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20" title="Tutup Paksa Ruangan">
                                                <i data-lucide="power" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">Belum ada ruang Watch Party ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($watchParties->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $watchParties->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
