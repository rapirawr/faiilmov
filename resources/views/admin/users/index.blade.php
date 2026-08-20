@extends('layouts.admin')

@section('title', 'Kelola Pengguna | faiiladmin')
@section('page_title', 'Kelola Pengguna & Akun')

@section('content')
<div class="space-y-6">
    
    <!-- Interactive Stats Shortcuts Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
        <!-- Total Users Shortcut -->
        <a href="{{ route('admin.users.index') }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ !request()->hasAny(['status', 'search']) ? 'border-amber-500/40 bg-amber-500/5' : 'border-zinc-800 hover:border-zinc-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-white transition-colors">Total Pengguna</span>
                <i data-lucide="users" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
            </div>
            <span class="font-extrabold text-white text-xl mt-2 font-['Outfit']">{{ number_format($stats['total'] ?? $users->total()) }}</span>
        </a>

        <!-- Active Users Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'active']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'active' ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-zinc-800 hover:border-emerald-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-emerald-300 transition-colors">Pengguna Aktif</span>
                <i data-lucide="user-check" class="w-4 h-4 text-emerald-400"></i>
            </div>
            <span class="font-extrabold text-emerald-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['active'] ?? 0) }}</span>
        </a>

        <!-- Administrator (Superadmin) Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'administrator']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'administrator' ? 'border-amber-500/50 bg-amber-500/15' : 'border-zinc-800 hover:border-amber-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-amber-300 transition-colors">Administrator</span>
                <i data-lucide="crown" class="w-4 h-4 text-amber-400"></i>
            </div>
            <span class="font-extrabold text-amber-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['administrator'] ?? 0) }}</span>
        </a>

        <!-- Admin Konten Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'admin']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'admin' ? 'border-sky-500/50 bg-sky-500/15' : 'border-zinc-800 hover:border-sky-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-sky-300 transition-colors">Admin Konten</span>
                <i data-lucide="shield" class="w-4 h-4 text-sky-400"></i>
            </div>
            <span class="font-extrabold text-sky-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['admin'] ?? 0) }}</span>
        </a>

        <!-- Ad-Free Users Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'ad_free']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'ad_free' ? 'border-amber-400/50 bg-amber-400/10' : 'border-zinc-800 hover:border-amber-400/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-amber-300 transition-colors">Bebas Iklan</span>
                <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
            </div>
            <span class="font-extrabold text-amber-300 text-xl mt-2 font-['Outfit']">{{ number_format($stats['ad_free'] ?? 0) }}</span>
        </a>

        <!-- Banned Users Shortcut -->
        <a href="{{ route('admin.users.index', ['status' => 'banned']) }}" 
           class="p-4 rounded-2xl bg-zinc-900/90 border transition-all flex flex-col justify-between hover:scale-[1.01] shadow-md group {{ request('status') === 'banned' ? 'border-rose-500/40 bg-rose-500/10' : 'border-zinc-800 hover:border-rose-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-rose-300 transition-colors">Banned</span>
                <i data-lucide="user-x" class="w-4 h-4 text-rose-400"></i>
            </div>
            <span class="font-extrabold text-rose-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['banned'] ?? 0) }}</span>
        </a>
    </div>

    <!-- Top Toolbar: Search & Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search & Filter Controls Form -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[240px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau ID user..." 
                       autocomplete="off"
                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <!-- Status Filter -->
            <div class="w-64">
                <x-custom-dropdown 
                    name="status" 
                    :value="request('status', '')" 
                    :options="[
                        '' => 'Semua Pengguna Aktif',
                        'active' => 'Aktif (Tidak Dibanned)',
                        'administrator' => '👑 Administrator (Superadmin)',
                        'admin' => '🛡️ Admin Konten',
                        'user' => '👤 Pengguna Biasa',
                        'ad_free' => '✨ Bebas Iklan (No Ads)',
                        'banned' => '🚫 Banned / Suspend',
                        'trashed' => '🗑️ Dihapus (Soft Deleted)',
                        'all_with_trashed' => '🌐 Semua (Termasuk Terhapus)',
                    ]" 
                    placeholder="Semua Pengguna" 
                    :autoSubmit="true"
                />
            </div>

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
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-zinc-950/95 sticky top-0 z-10 border-b border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">ID</th>
                        <th class="px-4 py-3.5">Pengguna</th>
                        <th class="px-4 py-3.5">Role / Akses</th>
                        <th class="px-4 py-3.5">Bebas Iklan</th>
                        <th class="px-4 py-3.5">Ulasan</th>
                        <th class="px-4 py-3.5">Watchlist</th>
                        <th class="px-4 py-3.5">Bergabung</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($users as $user)
                        <tr class="hover:bg-zinc-800/40 transition-colors {{ $user->trashed() ? 'opacity-60 bg-rose-950/10' : '' }}">
                            <td class="px-4 py-3.5 font-mono text-zinc-500 font-bold">
                                #{{ $user->id }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="min-w-0">
                                    <p class="font-bold text-white text-xs truncate flex items-center gap-1.5">
                                        <span>{{ $user->name }}</span>
                                        @if($user->trashed())
                                            <span class="text-[9px] px-1 py-0.2 rounded bg-rose-500/20 text-rose-300 font-mono">Dihapus</span>
                                        @endif
                                    </p>
                                    <p class="text-[11px] text-zinc-400 truncate">{{ $user->email }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($user->id === auth()->id())
                                    <span class="px-2.5 py-1 rounded-xl bg-amber-500/15 text-amber-300 font-extrabold text-[10px] uppercase border border-amber-500/30 flex items-center gap-1.5 w-max" title="Akun Anda Saat Ini">
                                        <i data-lucide="crown" class="w-3 h-3 text-amber-400"></i>
                                        <span>{{ $user->isAdministrator() ? 'Superadmin (Anda)' : 'Admin (Anda)' }}</span>
                                    </span>
                                @elseif($user->trashed() || !auth()->user()->isAdministrator())
                                    @if($user->isAdministrator())
                                        <span class="px-2.5 py-1 rounded-xl bg-amber-500/15 text-amber-300 font-bold text-[10px] border border-amber-500/30 flex items-center gap-1.5 w-max">
                                            <i data-lucide="crown" class="w-3 h-3 text-amber-400"></i>
                                            <span>Administrator</span>
                                        </span>
                                    @elseif($user->role === 'admin')
                                        <span class="px-2.5 py-1 rounded-xl bg-sky-500/15 text-sky-300 font-bold text-[10px] border border-sky-500/30 flex items-center gap-1.5 w-max">
                                            <i data-lucide="shield" class="w-3 h-3 text-sky-400"></i>
                                            <span>Admin Konten</span>
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full bg-zinc-800 text-zinc-400 font-medium text-[10px] border border-zinc-700/50">
                                            Pengguna
                                        </span>
                                    @endif
                                @else
                                    <!-- Interactive Role Switcher Trigger (Powered by React GlobalModal) -->
                                    <button type="button" 
                                            data-modal-role
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-user-email="{{ $user->email }}"
                                            data-current-role="{{ $user->isAdministrator() ? 'administrator' : ($user->role === 'admin' ? 'admin' : 'user') }}"
                                            data-action="{{ route('admin.users.update_role', $user->id) }}"
                                            class="px-2.5 py-1 rounded-xl text-[10px] font-bold border transition-all flex items-center gap-1.5 cursor-pointer {{ $user->isAdministrator() ? 'bg-amber-500/15 text-amber-300 border-amber-500/30 hover:bg-amber-500/25 shadow-sm' : ($user->role === 'admin' ? 'bg-sky-500/15 text-sky-300 border-sky-500/30 hover:bg-sky-500/25 shadow-sm' : 'bg-zinc-800/80 text-zinc-400 border-zinc-700/60 hover:text-white hover:border-zinc-500') }}"
                                            title="Klik untuk mengubah role akun ini">
                                        @if($user->isAdministrator())
                                            <i data-lucide="crown" class="w-3 h-3 text-amber-400"></i>
                                            <span>Administrator</span>
                                        @elseif($user->role === 'admin')
                                            <i data-lucide="shield" class="w-3 h-3 text-sky-400"></i>
                                            <span>Admin Konten</span>
                                        @else
                                            <i data-lucide="user" class="w-3 h-3 text-zinc-500"></i>
                                            <span>Pengguna</span>
                                        @endif
                                        <i data-lucide="chevrons-up-down" class="w-2.5 h-2.5 opacity-60"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <form action="{{ route('admin.users.toggle_ad_free', $user->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="px-2.5 py-1 rounded-xl text-[10px] font-bold border transition-all flex items-center gap-1.5 cursor-pointer {{ $user->is_ad_free ? 'bg-amber-400/15 text-amber-300 border-amber-400/40 hover:bg-amber-400/25 shadow-sm' : 'bg-zinc-800/80 text-zinc-400 border-zinc-700/60 hover:text-white hover:border-zinc-500' }}"
                                            title="{{ $user->is_ad_free ? 'Status: Bebas Iklan. Klik untuk menyalakan iklan kembali.' : 'Status: Kena Iklan. Klik untuk mematikan iklan akun ini.' }}">
                                        <i data-lucide="{{ $user->is_ad_free ? 'sparkles' : 'shield-ban' }}" class="w-3 h-3 {{ $user->is_ad_free ? 'text-amber-400' : 'text-zinc-400' }}"></i>
                                        <span>{{ $user->is_ad_free ? 'Bebas Iklan' : 'Kena Iklan' }}</span>
                                    </button>
                                </form>
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
                                @if($user->trashed())
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 font-extrabold text-[10px] border border-rose-500/30 flex items-center gap-1 w-max" title="Dihapus pada {{ $user->deleted_at->format('d M Y H:i') }}">
                                        <i data-lucide="trash-2" class="w-3 h-3 text-rose-400"></i>
                                        <span>Terhapus</span>
                                    </span>
                                @elseif($user->isBanned())
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 font-extrabold text-[10px] border border-rose-500/30 flex items-center gap-1 w-max">
                                        <i data-lucide="ban" class="w-3 h-3 text-rose-400"></i>
                                        <span>Banned</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-extrabold text-[10px] border border-emerald-500/30 flex items-center gap-1 w-max">
                                        <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-400"></i>
                                        <span>Aktif</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5" x-data="{ banModal: false, deleteModal: false, forceDeleteModal: false }">
                                    
                                    <!-- View Detail Button -->
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors" title="Lihat Profil & Aktivitas">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    @if($user->trashed())
                                        <!-- Restore Button -->
                                        <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" onsubmit="return confirm('Pulihkan akun user {{ addslashes($user->name) }} dari sampah?');">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors cursor-pointer" title="Pulihkan Akun (Restore)">
                                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                            </button>
                                        </form>

                                        <!-- Force Delete Modal Trigger -->
                                        <button type="button" @click="forceDeleteModal = true" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Hapus Permanen">
                                            <i data-lucide="trash" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Force Delete Modal Dialog -->
                                        <div x-show="forceDeleteModal" x-cloak 
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                            <div @click.away="forceDeleteModal = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
                                                <div class="flex items-center gap-3 text-rose-400 border-b border-zinc-800 pb-3">
                                                    <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
                                                        <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-white text-sm font-['Outfit']">Hapus Pengguna Permanen</h4>
                                                        <p class="text-[11px] text-zinc-400">Tindakan ini tidak dapat dibatalkan!</p>
                                                    </div>
                                                </div>
                                                
                                                <p class="text-xs text-zinc-300">Apakah Anda yakin ingin menghapus akun <strong class="text-white">{{ $user->name }}</strong> ({{ $user->email }}) secara <strong>PERMANEN</strong>? Seluruh data riwayat, ulasan, dan profil akan dihapus sepenuhnya dari database.</p>

                                                <form action="{{ route('admin.users.force_delete', $user->id) }}" method="POST" class="pt-2">
                                                    @csrf
                                                    @method('DELETE')

                                                    <div class="flex justify-end gap-2.5 pt-3 border-t border-zinc-800">
                                                        <button type="button" @click="forceDeleteModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">
                                                            Batal
                                                        </button>
                                                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white shadow-lg shadow-rose-600/20 transition-all cursor-pointer">
                                                            Hapus Permanen
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                    @else
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
                                                <button type="button" @click="banModal = true" class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 transition-colors cursor-pointer" title="Ban / Suspen User">
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
                                                        <div class="flex items-center gap-3 text-amber-400 border-b border-zinc-800 pb-3">
                                                            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center shrink-0">
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
                                                                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-xs font-bold text-zinc-950 shadow-lg transition-all cursor-pointer">
                                                                    Konfirmasi Suspen
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($user->id !== auth()->id())
                                                <!-- Soft Delete Modal Trigger -->
                                                <button type="button" @click="deleteModal = true" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Hapus Akun (Soft Delete)">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>

                                                <!-- Soft Delete Modal Dialog -->
                                                <div x-show="deleteModal" x-cloak 
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100"
                                                     x-transition:leave="transition ease-in duration-150"
                                                     x-transition:leave-start="opacity-100 scale-100"
                                                     x-transition:leave-end="opacity-0 scale-95"
                                                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                                    <div @click.away="deleteModal = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
                                                        <div class="flex items-center gap-3 text-rose-400 border-b border-zinc-800 pb-3">
                                                            <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
                                                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                            </div>
                                                            <div>
                                                                <h4 class="font-bold text-white text-sm font-['Outfit']">Pindahkan Akun ke Sampah</h4>
                                                                <p class="text-[11px] text-zinc-400">Soft Delete Pengguna</p>
                                                            </div>
                                                        </div>
                                                        
                                                        <p class="text-xs text-zinc-300">Apakah Anda yakin ingin menghapus akun <strong class="text-white">{{ $user->name }}</strong> ({{ $user->email }})? Akun akan dipindahkan ke folder sampah dan dapat dipulihkan kapan saja.</p>

                                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="pt-2">
                                                            @csrf
                                                            @method('DELETE')

                                                            <div class="flex justify-end gap-2.5 pt-3 border-t border-zinc-800">
                                                                <button type="button" @click="deleteModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">
                                                                    Batal
                                                                </button>
                                                                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition-all cursor-pointer">
                                                                    Pindahkan ke Sampah
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
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
