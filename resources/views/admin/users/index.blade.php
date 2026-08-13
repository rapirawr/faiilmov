@extends('layouts.admin')

@section('title', 'Kelola Pengguna | faiiladmin')
@section('page_title', 'Kelola Pengguna & Akun')

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

    <!-- Interactive Stats Shortcuts Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <!-- Total Users Shortcut -->
        <a href="{{ route('admin.users.index') }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ !request()->hasAny(['status', 'search']) ? 'border-amber-500/40 bg-amber-500/5' : 'border-zinc-800 hover:border-zinc-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-white transition-colors">Total Pengguna</span>
                <i data-lucide="users" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
            </div>
            <span class="font-extrabold text-white text-xl mt-2 font-['Outfit']">{{ number_format($users->total()) }}</span>
        </a>

        <!-- Active Users Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'active']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'active' ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-zinc-800 hover:border-emerald-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-emerald-300 transition-colors">Pengguna Aktif</span>
                <i data-lucide="user-check" class="w-4 h-4 text-emerald-400"></i>
            </div>
            <span class="font-extrabold text-emerald-400 text-xl mt-2 font-['Outfit']">Active</span>
        </a>

        <!-- Banned Users Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'banned']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'banned' ? 'border-rose-500/40 bg-rose-500/10' : 'border-zinc-800 hover:border-rose-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-rose-300 transition-colors">Pengguna Banned</span>
                <i data-lucide="user-x" class="w-4 h-4 text-rose-400"></i>
            </div>
            <span class="font-extrabold text-rose-400 text-xl mt-2 font-['Outfit']">Banned</span>
        </a>

        <!-- Admin Role Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'admin']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'admin' ? 'border-amber-500/40 bg-amber-500/10' : 'border-zinc-800 hover:border-amber-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-amber-300 transition-colors">Administrator</span>
                <i data-lucide="shield-check" class="w-4 h-4 text-amber-400"></i>
            </div>
            <span class="font-extrabold text-amber-400 text-xl mt-2 font-['Outfit']">Admin</span>
        </a>
    </div>

    <!-- Top Toolbar: Search & Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search & Filter Controls Form -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[240px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau ID user..." 
                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Aktif</option>
                <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>🔴 Banned / Suspend</option>
                <option value="admin" {{ request('status') === 'admin' ? 'selected' : '' }}>👑 Administrator</option>
            </select>

            <!-- Reset Filter Button -->
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.users.index') }}" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2 text-xs font-mono text-zinc-400">
            <span class="px-3 py-1.5 rounded-xl bg-zinc-900 border border-zinc-800">
                Total: <strong class="text-white">{{ number_format($users->total()) }}</strong> User
            </span>
        </div>
    </div>

    <!-- Users Table Container with Sticky Header & Zebra Hover -->
    <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto max-h-[75vh] scrollbar-thin">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 sticky top-0 z-20 backdrop-blur-md tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Pengguna</th>
                        <th class="px-4 py-3.5">Role</th>
                        <th class="px-4 py-3.5">Ulasan</th>
                        <th class="px-4 py-3.5">Watchlist</th>
                        <th class="px-4 py-3.5">Tgl Terdaftar</th>
                        <th class="px-4 py-3.5">Status Akun</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($users as $user)
                        <tr class="hover:bg-zinc-800/40 transition-colors group">
                            <td class="px-4 py-3.5 flex items-center gap-3">
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" 
                                     class="w-9 h-9 rounded-full object-cover border border-zinc-700 shrink-0 bg-zinc-950" 
                                     onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode($user->name) }}';">
                                <div class="min-w-0">
                                    <p class="font-bold text-white text-xs line-clamp-1 flex items-center gap-1.5">
                                        <span>{{ $user->name }}</span>
                                        @if($user->isAdmin())
                                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-400 shrink-0" title="Administrator"></i>
                                        @endif
                                    </p>
                                    <p class="text-[11px] text-zinc-400 truncate">{{ $user->email }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($user->isAdmin())
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-300 font-extrabold text-[10px] uppercase border border-amber-500/20">
                                        Admin
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-zinc-800/80 text-zinc-400 font-medium text-[10px] border border-zinc-700/60">
                                        Pengguna
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-zinc-300">
                                {{ number_format($user->reviews_count) }}
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-zinc-300">
                                {{ number_format($user->watchlists_count) }}
                            </td>
                            <td class="px-4 py-3.5 text-zinc-400 text-[11px] font-mono">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if($user->isBanned())
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 font-extrabold text-[10px] border border-rose-500/30 flex items-center gap-1 w-max">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        <span>Banned</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-extrabold text-[10px] border border-emerald-500/30 flex items-center gap-1 w-max">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        <span>Aktif</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5" x-data="{ banModal: false }">
                                    
                                    <!-- View Detail Button -->
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors" title="Lihat Profil & Aktivitas">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    @if(!$user->isAdmin())
                                        @if($user->isBanned())
                                            <!-- Unban Trigger -->
                                            <form action="{{ route('admin.users.unban', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memulihkan akses login user {{ addslashes($user->name) }}?');">
                                                @csrf
                                                <button type="submit" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors cursor-pointer" title="Cabut Ban (Unban)">
                                                    <i data-lucide="user-check" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <!-- Ban Modal Trigger -->
                                            <button type="button" @click="banModal = true" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Ban / Suspen User">
                                                <i data-lucide="user-x" class="w-4 h-4"></i>
                                            </button>

                                            <!-- Ban Modal Dialog -->
                                            <div x-show="banModal" x-cloak 
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-150"
                                                 x-transition:leave-start="opacity-100 scale-100"
                                                 x-transition:leave-end="opacity-0 scale-95"
                                                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                                <div @click.away="banModal = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
                                                    <div class="flex items-center gap-3 text-rose-400 border-b border-zinc-800 pb-3">
                                                        <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
                                                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                                                        </div>
                                                        <div>
                                                            <h4 class="font-bold text-white text-sm font-['Outfit']">Ban / Pembatasan Pengguna</h4>
                                                            <p class="text-[11px] text-zinc-400">Suspen akses akun pengguna Faiilmov</p>
                                                        </div>
                                                    </div>
                                                    
                                                    <p class="text-xs text-zinc-300">Pengguna <strong class="text-white">{{ $user->name }}</strong> ({{ $user->email }}) tidak akan dapat menggunakan layanan aplikasi selama durasi ban.</p>

                                                    <form action="{{ route('admin.users.ban', $user->id) }}" method="POST" class="space-y-4 pt-1">
                                                        @csrf

                                                        <div>
                                                            <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2">Alasan Pelanggaran *</label>
                                                            <input type="text" name="reason" required placeholder="Contoh: Pelanggaran TOS / Ulasan Spam" 
                                                                   class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2">Durasi Hukuman *</label>
                                                            <select name="duration" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                                                                <option value="1_day">1 Hari</option>
                                                                <option value="7_days">7 Hari (1 Minggu)</option>
                                                                <option value="30_days">30 Hari (1 Bulan)</option>
                                                                <option value="permanent">Permanen</option>
                                                            </select>
                                                        </div>

                                                        <div class="flex justify-end gap-2.5 pt-3 border-t border-zinc-800">
                                                            <button type="button" @click="banModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">
                                                                Batal
                                                            </button>
                                                            <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition-all cursor-pointer">
                                                                Konfirmasi Suspen
                                                            </button>
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
                            <td colspan="7" class="px-4 py-16 text-center text-zinc-500 space-y-3">
                                <i data-lucide="users" class="w-12 h-12 mx-auto text-zinc-700"></i>
                                <p class="text-sm font-semibold text-zinc-400">Tidak ada pengguna ditemukan</p>
                                <p class="text-xs text-zinc-500 max-w-sm mx-auto">Tidak ada akun pengguna yang cocok dengan filter atau kata kunci pencarian Anda.</p>
                                @if(request()->hasAny(['search', 'status']))
                                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-zinc-800 text-white font-bold text-xs hover:bg-zinc-700 transition-colors mt-2">
                                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                        <span>Reset Filter</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($users->hasPages())
            <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
