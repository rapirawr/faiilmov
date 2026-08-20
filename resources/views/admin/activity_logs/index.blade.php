@extends('layouts.admin')

@section('title', 'Activity Audit Logs | faiiladmin')
@section('page_title', 'Activity Audit Logs')

@section('content')
<div class="space-y-6">
    
    <!-- Filter & Tools Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Search & Dropdown Filters -->
        <form method="GET" action="{{ route('admin.activity_logs.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-white/40 transition-all flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aksi, deskripsi, atau admin..." 
                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <!-- Category Filter -->
            <div class="w-56">
                <x-custom-dropdown 
                    name="category" 
                    :value="request('category', '')" 
                    :options="[
                        '' => 'Semua Kategori Aksi',
                        'film' => 'Film & Dracin',
                        'user' => 'User & Ban',
                        'review' => 'Ulasan & Moderasi',
                        'script' => 'Script Runner',
                        'settings' => 'Pengaturan',
                        'actor' => 'Aktor & Cast',
                        'genre' => 'Genre',
                    ]" 
                    placeholder="Semua Kategori" 
                    :autoSubmit="true"
                />
            </div>

            <!-- Timeframe Filter -->
            <div class="w-44">
                <x-custom-dropdown 
                    name="timeframe" 
                    :value="request('timeframe', '')" 
                    :options="[
                        '' => 'Semua Waktu',
                        'today' => 'Hari Ini',
                        '7d' => '7 Hari Terakhir',
                        '30d' => '30 Hari Terakhir',
                    ]" 
                    placeholder="Semua Waktu" 
                    :autoSubmit="true"
                />
            </div>

            @if(request()->hasAny(['search', 'category', 'timeframe']))
                <a href="{{ route('admin.activity_logs.index') }}" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            @endif
        </form>

        <!-- Right Side: Stats & Clean Old Logs Modal Trigger -->
        <div class="flex items-center gap-3 shrink-0" x-data="{ cleanModal: false }">
            <span class="text-xs font-mono text-zinc-400 bg-zinc-900 px-3.5 py-2 rounded-xl border border-zinc-800">
                Total: <strong class="text-white">{{ number_format($logs->total()) }}</strong> Record
            </span>

            <button type="button" @click="cleanModal = true" class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                <span>Bersihkan Log Lama</span>
            </button>

            <!-- Clean Old Logs Modal -->
            <template x-teleport="body">
                <div x-show="cleanModal" x-cloak 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
                    <div @click.away="cleanModal = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl text-white">
                        <div class="flex items-center gap-3 border-b border-zinc-800 pb-3 text-rose-400">
                            <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
                                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            </div>
                            <h4 class="font-bold text-white text-sm font-['Outfit']">Bersihkan Riwayat Audit Log</h4>
                        </div>

                        <p class="text-xs text-zinc-300">Hapus catatan audit log yang sudah lampau untuk menghemat ruang penyimpanan database.</p>

                        <form action="{{ route('admin.activity_logs.clear_old') }}" method="POST" class="space-y-4">
                            @csrf
                            @method('DELETE')

                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Pilih Retensi Waktu *</label>
                                <select name="days" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-rose-500">
                                    <option value="30">Hapus log yang lebih lama dari 30 Hari</option>
                                    <option value="60">Hapus log yang lebih lama dari 60 Hari</option>
                                    <option value="90">Hapus log yang lebih lama dari 90 Hari</option>
                                </select>
                            </div>

                            <div class="flex justify-end gap-2.5 pt-2 border-t border-zinc-800">
                                <button type="button" @click="cleanModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">Batal</button>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition-all cursor-pointer">Konfirmasi Bersihkan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>

        </div>
    </div>

    <!-- Logs Table Container -->
    <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto max-h-[75vh] admin-scrollbar">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3.5">Admin</th>
                        <th class="px-4 py-3.5">Aksi</th>
                        <th class="px-4 py-3.5">Target ENTITY</th>
                        <th class="px-4 py-3.5">Deskripsi Audit</th>
                        <th class="px-4 py-3.5 text-right">Waktu (WIB)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($logs as $log)
                        <tr class="hover:bg-zinc-800/40 transition-colors group">
                            <td class="px-4 py-3.5 font-bold text-white flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md bg-white/10 text-white text-[10px] flex items-center justify-center font-bold font-mono shrink-0">
                                    {{ strtoupper(substr($log->admin->name ?? 'S', 0, 1)) }}
                                </div>
                                <span class="text-xs">{{ $log->admin->name ?? 'System Queue' }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-md bg-zinc-950 border border-zinc-800 text-rose-400 font-mono text-[10px] font-bold uppercase tracking-wider">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-zinc-400 text-[11px]">
                                @if($log->target_type)
                                    <span class="text-zinc-300 font-semibold">{{ class_basename($log->target_type) }}</span> <span class="text-white font-bold">#{{ $log->target_id }}</span>
                                @else
                                    <span class="text-zinc-600">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-300 max-w-md text-xs leading-relaxed break-words min-w-[200px]">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-zinc-400 text-[11px]">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-zinc-500">
                                <x-admin.empty-state icon="history" title="Belum Ada Log Aktivitas" description="Log aktivitas admin akan tercatat otomatis setiap ada aksi sistem atau perubahan konten." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
