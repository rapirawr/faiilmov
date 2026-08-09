@extends('layouts.admin')

@section('title', 'Manajemen Aktor / Cast | faiiladmin')
@section('page_title', 'Manajemen Aktor / Cast')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Add Actor Form -->
    <div class="space-y-4">
        <h3 class="text-base font-bold text-white font-['Outfit']">Tambah Aktor Baru</h3>
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl">
            <form action="{{ route('admin.actors.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Aktor *</label>
                    <input type="text" name="name" required placeholder="Contoh: Tom Holland" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                    @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Photo URL (Link)</label>
                    <input type="url" name="photo_url" placeholder="https://..." 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Simpan Aktor</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Actors Table -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="text-base font-bold text-white font-['Outfit']">Daftar Aktor</h3>
            
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.actors.sync_api') }}" method="POST" onsubmit="return confirm('Mulai sinkronisasi data Aktor dari MovieBox API di latar belakang?')">
                    @csrf
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Sync API Aktor</span>
                    </button>
                </form>

                <form method="GET" action="{{ route('admin.actors.index') }}" class="w-48">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama aktor..." 
                           class="w-full bg-zinc-900 border border-white/10 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                </form>
            </div>
        </div>

        <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">Aktor</th>
                        <th class="px-4 py-3.5">Jumlah Film</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($actors as $a)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 flex items-center gap-3">
                                @if($a->photo_url)
                                    <img src="{{ $a->photo_url }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($a->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-bold text-white text-sm">{{ $a->name }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-amber-400">{{ number_format($a->films_count) }} film</td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2" x-data="{ editing: false, name: '{{ $a->name }}', photo_url: '{{ $a->photo_url }}' }">
                                    <button @click="editing = !editing" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>

                                    <form action="{{ route('admin.actors.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Hapus aktor {{ $a->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>

                                    <!-- Quick Inline Edit Modal -->
                                    <div x-show="editing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                                        <div @click.away="editing = false" class="w-full max-w-md p-6 rounded-2xl bg-zinc-900 border border-white/10 text-left space-y-4">
                                            <h4 class="font-bold text-white text-base">Edit Aktor</h4>
                                            <form action="{{ route('admin.actors.update', $a->id) }}" method="POST" class="space-y-4">
                                                @csrf
                                                @method('PUT')
                                                <div>
                                                    <label class="block text-xs font-semibold text-zinc-400 mb-1">Nama</label>
                                                    <input type="text" name="name" x-model="name" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-amber-500">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-zinc-400 mb-1">Photo URL</label>
                                                    <input type="url" name="photo_url" x-model="photo_url" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-amber-500">
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="editing = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300">Batal</button>
                                                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 text-xs font-bold text-black">Perbarui</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-zinc-500">Belum ada aktor.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($actors->hasPages())
                <div class="p-4 border-t border-white/10">
                    {{ $actors->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
