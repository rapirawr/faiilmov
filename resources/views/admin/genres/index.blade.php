@extends('layouts.admin')

@section('title', 'Manajemen Genre | faiiladmin')
@section('page_title', 'Manajemen Genre & Kategori')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Add Genre Form Card -->
    <div class="space-y-4">
        <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4">
            <div class="flex items-center gap-3 border-b border-zinc-800 pb-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                    <i data-lucide="tags" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white font-['Outfit']">Tambah Genre Baru</h3>
                    <p class="text-xs text-zinc-400">Buat kategori genre baru untuk pengelompokan film.</p>
                </div>
            </div>

            <form action="{{ route('admin.genres.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Genre *</label>
                    <input type="text" name="name" required placeholder="Contoh: Sci-Fi, Thriller, Wuxia" 
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                    @error('name')
                        <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Simpan Genre</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Genres Table -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white font-['Outfit']">Daftar Genre Katalog</h3>
                    <p class="text-xs text-zinc-400">Total {{ number_format($genres->total()) }} genre terdaftar</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-zinc-800 text-zinc-300 border border-zinc-700">
                    {{ $genres->total() }} Genre
                </span>
            </div>

            <div class="overflow-x-auto max-h-[75vh] admin-scrollbar">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3.5">Nama Genre</th>
                            <th class="px-4 py-3.5">Slug</th>
                            <th class="px-4 py-3.5">Jumlah Film</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60">
                        @forelse($genres as $g)
                            <tr class="hover:bg-zinc-800/40 transition-colors group">
                                <td class="px-4 py-3.5 font-bold text-white text-xs">{{ $g->name }}</td>
                                <td class="px-4 py-3.5 font-mono text-zinc-400 text-xs">{{ $g->slug }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400 font-mono font-bold text-[11px]">
                                        {{ number_format($g->films_count) }} film
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5" x-data="{ editing: false, name: '{{ addslashes($g->name) }}' }">
                                        <button type="button" @click="editing = !editing" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors cursor-pointer" title="Edit Genre">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>

                                        <form action="{{ route('admin.genres.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus genre {{ addslashes($g->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Hapus Genre">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>

                                        <!-- Quick Edit Modal -->
                                        <template x-teleport="body">
                                            <div x-show="editing" x-cloak 
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-150"
                                                 x-transition:leave-start="opacity-100 scale-100"
                                                 x-transition:leave-end="opacity-0 scale-95"
                                                 class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                                <div @click.away="editing = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
                                                    <div class="flex items-center gap-3 border-b border-zinc-800 pb-3">
                                                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                        </div>
                                                        <h4 class="font-bold text-white text-sm font-['Outfit']">Edit Genre</h4>
                                                    </div>

                                                    <form action="{{ route('admin.genres.update', $g->id) }}" method="POST" class="space-y-4">
                                                        @csrf
                                                        @method('PUT')
                                                        <div>
                                                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Genre *</label>
                                                            <input type="text" name="name" x-model="name" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                                        </div>
                                                        <div class="flex justify-end gap-2.5 pt-2">
                                                            <button type="button" @click="editing = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">Batal</button>
                                                            <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-xs font-bold text-zinc-950 transition-all shadow-lg shadow-amber-500/20 cursor-pointer">Perbarui</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-zinc-500">Belum ada data genre.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($genres->hasPages())
                <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                    {{ $genres->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
