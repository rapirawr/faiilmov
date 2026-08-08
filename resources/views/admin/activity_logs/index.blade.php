@extends('layouts.admin')

@section('title', 'Activity Log | faiiladmin')
@section('page_title', 'Admin Activity Audit Log')

@section('content')
<div class="space-y-6">
    
    <!-- Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.activity_logs.index') }}" class="flex items-center gap-3 flex-1 max-w-md">
            <div class="flex items-center gap-2.5 px-3 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aksi, deskripsi, atau admin..." 
                       class="w-full min-w-0 bg-transparent py-2 text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">Admin</th>
                        <th class="px-4 py-3.5">Aksi</th>
                        <th class="px-4 py-3.5">Target</th>
                        <th class="px-4 py-3.5">Deskripsi</th>
                        <th class="px-4 py-3.5 text-right">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($logs as $log)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 font-bold text-white flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md bg-amber-500/20 text-amber-400 text-[10px] flex items-center justify-center font-bold">
                                    {{ strtoupper(substr($log->admin->name ?? 'A', 0, 1)) }}
                                </div>
                                <span>{{ $log->admin->name ?? 'System' }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded-md bg-white/10 text-amber-400 font-mono text-[10px] font-bold uppercase">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-zinc-400 text-[11px]">
                                @if($log->target_type)
                                    {{ $log->target_type }} #{{ $log->target_id }}
                                @else
                                    <span class="text-zinc-600">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-300 max-w-md">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-zinc-400 text-[11px]">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">Belum ada activity log.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
