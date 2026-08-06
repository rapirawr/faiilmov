@extends('layouts.admin')

@section('title', 'Manajemen Pengguna | faiiladmin')
@section('page_title', 'Manajemen Pengguna')

@section('content')
<div class="space-y-6">
    
    <!-- Search & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email user..." 
                       class="w-full bg-zinc-900 border border-white/10 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
            </div>

            <select name="status" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned / Suspend</option>
                <option value="admin" {{ request('status') === 'admin' ? 'selected' : '' }}>Administrator</option>
            </select>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">Pengguna</th>
                        <th class="px-4 py-3.5">Role</th>
                        <th class="px-4 py-3.5">Ulasan</th>
                        <th class="px-4 py-3.5">Watchlist</th>
                        <th class="px-4 py-3.5">Tgl Daftar</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $user)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-white text-sm line-clamp-1">{{ $user->name }}</p>
                                    <p class="text-[11px] text-zinc-400">{{ $user->email }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($user->isAdmin())
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold text-[10px] uppercase border border-amber-500/30">Admin</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-white/10 text-zinc-400 font-semibold text-[10px]">User</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-zinc-300">
                                {{ number_format($user->reviews_count) }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-zinc-300">
                                {{ number_format($user->watchlists_count) }}
                            </td>
                            <td class="px-4 py-3.5 text-zinc-400 text-[11px]">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if($user->isBanned())
                                    <span class="px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 font-bold text-[10px]">Banned</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-bold text-[10px]">Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2" x-data="{ banModal: false }">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20" title="Detail User">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    @if(!$user->isAdmin())
                                        @if($user->isBanned())
                                            <form action="{{ route('admin.users.unban', $user->id) }}" method="POST" onsubmit="return confirm('Cabut suspen/unban user {{ $user->name }}?')">
                                                @csrf
                                                <button type="submit" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20" title="Unban User">
                                                    <i data-lucide="user-check" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button @click="banModal = true" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Ban User">
                                                <i data-lucide="user-x" class="w-4 h-4"></i>
                                            </button>

                                            <!-- Ban Modal -->
                                            <div x-show="banModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                                                <div @click.away="banModal = false" class="w-full max-w-md p-6 rounded-2xl bg-zinc-900 border border-white/10 text-left space-y-4 shadow-2xl">
                                                    <div class="flex items-center gap-3 text-red-400">
                                                        <i data-lucide="shield-alert" class="w-6 h-6"></i>
                                                        <h4 class="font-bold text-white text-base">Ban / Suspen User</h4>
                                                    </div>
                                                    <p class="text-xs text-zinc-400">Anda akan membatasi akses akun <strong class="text-white">{{ $user->name }}</strong> ({{ $user->email }}).</p>

                                                    <form action="{{ route('admin.users.ban', $user->id) }}" method="POST" class="space-y-4">
                                                        @csrf

                                                        <div>
                                                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Alasan Ban *</label>
                                                            <input type="text" name="reason" required placeholder="Contoh: Pelanggaran ketentuan layanan" 
                                                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Durasi Ban *</label>
                                                            <select name="duration" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                                                                <option value="1_day">1 Hari</option>
                                                                <option value="7_days">7 Hari (1 Minggu)</option>
                                                                <option value="30_days">30 Hari (1 Bulan)</option>
                                                                <option value="permanent">Permanen</option>
                                                            </select>
                                                        </div>

                                                        <div class="flex justify-end gap-2 pt-2">
                                                            <button type="button" @click="banModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300">Batal</button>
                                                            <button type="submit" class="px-4 py-2 rounded-xl bg-red-500 text-xs font-bold text-white shadow-lg shadow-red-500/20">Konfirmasi Ban</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">Tidak ada pengguna ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
