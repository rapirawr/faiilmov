@extends('layouts.admin')

@section('title', 'Manajemen Genre | faiiladmin')
@section('page_title', 'Manajemen Genre')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Add Genre Form -->
    <div class="space-y-4">
        <h3 class="text-base font-bold text-white font-['Outfit']">Tambah Genre Baru</h3>
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl">
            <form action="{{ route('admin.genres.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Genre *</label>
                    <input type="text" name="name" required placeholder="Contoh: Sci-Fi" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                    @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Simpan Genre</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Genres Table -->
    <div class="lg:col-span-2 space-y-4">
        <h3 class="text-base font-bold text-white font-['Outfit']">Daftar Genre</h3>
        <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">Nama Genre</th>
                        <th class="px-4 py-3.5">Slug</th>
                        <th class="px-4 py-3.5">Jumlah Film</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($genres as $g)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 font-bold text-white text-sm">{{ $g->name }}</td>
                            <td class="px-4 py-3.5 font-mono text-zinc-400 text-xs">{{ $g->slug }}</td>
                            <td class="px-4 py-3.5 font-bold text-amber-400">{{ number_format($g->films_count) }} film</td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2" x-data="{ editing: false, name: '{{ $g->name }}' }">
                                    <button @click="editing = !editing" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>

                                    <form action="{{ route('admin.genres.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Hapus genre {{ $g->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>

                                    <!-- Quick Inline Edit Modal / Form -->
                                    <div x-show="editing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                                        <div @click.away="editing = false" class="w-full max-w-md p-6 rounded-2xl bg-zinc-900 border border-white/10 text-left space-y-4">
                                            <h4 class="font-bold text-white text-base">Edit Genre</h4>
                                            <form action="{{ route('admin.genres.update', $g->id) }}" method="POST" class="space-y-4">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="name" x-model="name" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
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
                            <td colspan="4" class="px-4 py-8 text-center text-zinc-500">Belum ada genre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($genres->hasPages())
                <div class="p-4 border-t border-white/10">
                    {{ $genres->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
