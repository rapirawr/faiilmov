@extends('layouts.admin')

@section('title', 'Manajemen Changelog & Updates | faiiladmin')
@section('page_title', 'Manajemen Changelog & Catatan Rilis')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('admin.changelogs.index') }}" class="flex items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari versi atau judul rilis..." 
                       class="w-full bg-transparent py-2 text-xs text-white placeholder-zinc-500 border-none outline-none focus:ring-0">
            </div>
        </form>

        <div class="flex items-center gap-3">
            <a href="{{ route('changelog') }}" target="_blank" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-2 border border-white/10 transition-all">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Lihat Halaman Publik</span>
            </a>

            <a href="{{ route('admin.changelogs.create') }}" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Catatan Rilis</span>
            </a>
        </div>
    </div>

    <!-- Changelogs Table -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">Versi</th>
                        <th class="px-4 py-3.5">Judul Rilis</th>
                        <th class="px-4 py-3.5">Tipe Update</th>
                        <th class="px-4 py-3.5">Tgl Rilis</th>
                        <th class="px-4 py-3.5">Jumlah Poin</th>
                        <th class="px-4 py-3.5">Status Publikasi</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($changelogs as $log)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 font-bold font-mono text-amber-400">
                                {{ $log->version }}
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-white text-sm line-clamp-1">{{ $log->title }}</p>
                                <p class="text-[11px] text-zinc-400 line-clamp-1">{{ $log->summary }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($log->type === 'major')
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-extrabold text-[10px] uppercase border border-amber-500/30">Major</span>
                                @elseif($log->type === 'minor')
                                    <span class="px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-300 font-extrabold text-[10px] uppercase border border-sky-500/30">Minor</span>
                                @elseif($log->type === 'security')
                                    <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 font-extrabold text-[10px] uppercase border border-purple-500/30">Security</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-extrabold text-[10px] uppercase border border-emerald-500/30">Patch</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-300 font-mono">
                                {{ $log->release_date ? $log->release_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-zinc-400">
                                {{ is_array($log->changes) ? count($log->changes) : 0 }} Poin
                            </td>
                            <td class="px-4 py-3.5">
                                <form action="{{ route('admin.changelogs.toggle_publish', $log->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-colors {{ $log->is_published ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-zinc-800 text-zinc-400 border border-white/10 hover:bg-zinc-700' }}">
                                        {{ $log->is_published ? 'Publik' : 'Draft / Tersembunyi' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.changelogs.edit', $log->id) }}" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>

                                    <form action="{{ route('admin.changelogs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Hapus catatan rilis {{ $log->version }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">Belum ada catatan rilis changelog.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($changelogs->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $changelogs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
