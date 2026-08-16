@extends('layouts.admin')

@section('title', 'Manajemen Watch Parties | faiiladmin')
@section('page_title', 'Manajemen Watch Parties (Nonton Bareng)')

@section('content')
<div class="space-y-6">

    <!-- Flash Alerts System -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-400"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 text-rose-400"></i>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Top Action Bar & Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('admin.watch_parties.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[240px]">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode Ruangan, Host, atau Judul Film..." 
                       class="w-full bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:ring-0">
            </div>

            <div class="w-56">
                <x-custom-dropdown 
                    name="status" 
                    :value="request('status', '')" 
                    :options="[
                        '' => 'Semua Status Ruangan',
                        'active' => '🟢 Aktif (Sedang Berjalan)',
                        'ended' => '⚪ Selesai / Ditutup',
                    ]" 
                    placeholder="Semua Status" 
                    :autoSubmit="true"
                />
            </div>

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.watch_parties.index') }}" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2.5 text-xs font-mono">
            <span class="px-3.5 py-2 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-300 font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                <span>{{ $activeCount }} Ruangan Aktif</span>
            </span>
            <span class="px-3.5 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-zinc-400 font-semibold">
                {{ $endedCount }} Sesi Selesai
            </span>
        </div>
    </div>

    <!-- Watch Parties Table -->
    <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider">
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
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($watchParties as $party)
                        <tr class="hover:bg-zinc-800/40 transition-colors group">
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-xl bg-zinc-950 border border-zinc-800 text-amber-400 font-mono font-extrabold text-xs">
                                    #{{ $party->room_code }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 flex items-center gap-3">
                                <img src="{{ $party->film->poster_url ?? '' }}" referrerpolicy="no-referrer" class="w-8 h-11 object-cover rounded shrink-0 bg-zinc-950">
                                <div class="min-w-0">
                                    <p class="font-bold text-white text-xs line-clamp-1">{{ $party->film->title ?? 'Film Dihapus' }}</p>
                                    @if($party->season_number)
                                        <p class="text-[10px] text-purple-300 font-mono mt-0.5">S{{ $party->season_number }} E{{ $party->episode_number }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-white text-xs">{{ $party->hostUser->name ?? $party->host_guest_name }}</p>
                                <p class="text-[10px] text-zinc-400 truncate">{{ $party->hostUser->email ?? 'Guest Host' }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-full bg-zinc-950 border border-zinc-800 text-zinc-200 font-mono font-bold text-[11px] flex items-center gap-1 w-fit">
                                    <i data-lucide="users" class="w-3 h-3 text-sky-400"></i>
                                    <span>{{ $party->participants->count() }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($party->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold flex items-center gap-1 w-fit">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span>Aktif</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-zinc-800 text-zinc-400 border border-zinc-700/60 text-[10px] font-semibold">
                                        Selesai
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-400 text-[11px] font-mono">
                                {{ $party->created_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.watch_parties.show', $party->id) }}" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors" title="Detail Ruangan & Chat Log">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    @if($party->status === 'active')
                                        <form action="{{ route('admin.watch_parties.force_close', $party->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENUTUP PAKSA ruang Nobar ini?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Tutup Paksa Ruangan">
                                                <i data-lucide="power" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-zinc-500">Belum ada ruang Watch Party ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($watchParties->hasPages())
            <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                {{ $watchParties->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
