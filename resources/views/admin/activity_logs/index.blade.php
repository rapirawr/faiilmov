@extends('layouts.admin')

@section('title', 'Activity Log | faiiladmin')
@section('page_title', 'Admin Activity Audit Log')

@section('content')
<div class="space-y-6">
    
    <!-- Search & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.activity_logs.index') }}" class="flex items-center gap-3 flex-1 max-w-md">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aksi, deskripsi, atau nama admin..." 
                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            @if(request('search'))
                <a href="{{ route('admin.activity_logs.index') }}" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            @endif
        </form>

        <span class="text-xs font-mono text-zinc-400 bg-zinc-900 px-3.5 py-2 rounded-xl border border-zinc-800 self-start sm:self-auto">
            Total: <strong class="text-white">{{ number_format($logs->total()) }}</strong> Record Log
        </span>
    </div>

    <!-- Logs Table Container -->
    <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider">
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
                                <div class="w-6 h-6 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] flex items-center justify-center font-bold font-mono shrink-0">
                                    {{ strtoupper(substr($log->admin->name ?? 'S', 0, 1)) }}
                                </div>
                                <span class="text-xs">{{ $log->admin->name ?? 'System Queue' }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-md bg-zinc-950 border border-zinc-800 text-amber-400 font-mono text-[10px] font-bold uppercase tracking-wider">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-zinc-400 text-[11px]">
                                @if($log->target_type)
                                    <span class="text-zinc-300 font-semibold">{{ class_basename($log->target_type) }}</span> <span class="text-amber-400">#{{ $log->target_id }}</span>
                                @else
                                    <span class="text-zinc-600">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-300 max-w-md text-xs leading-relaxed">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-zinc-400 text-[11px]">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-zinc-500">Belum ada activity log.</td>
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
