@extends('layouts.admin')

@section('title', 'Manajemen Aktor / Cast | faiiladmin')
@section('page_title', 'Manajemen Aktor & Pemain Film')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Flash Alerts System -->
    @if(session('success'))
        <div class="lg:col-span-3 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-400"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="lg:col-span-3 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 text-rose-400"></i>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Add Actor Form Card -->
    <div class="space-y-4">
        <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4">
            <div class="flex items-center gap-3 border-b border-zinc-800 pb-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white font-['Outfit']">Tambah Aktor Baru</h3>
                    <p class="text-xs text-zinc-400">Tambahkan pemeran baru ke database katalog.</p>
                </div>
            </div>

            <form action="{{ route('admin.actors.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Aktor *</label>
                    <input type="text" name="name" required placeholder="Contoh: Tom Holland, Cillian Murphy" 
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                    @error('name')
                        <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">URL Foto (Link)</label>
                    <input type="url" name="photo_url" placeholder="https://..." 
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                    <p class="text-[11px] text-zinc-500 mt-1">Opsional link gambar avatar foto profil aktor.</p>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Simpan Data Aktor</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Actors Table -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-white font-['Outfit']">Daftar Pemeran & Cast</h3>
                    <p class="text-xs text-zinc-400">Total {{ number_format($actors->total()) }} aktor terdaftar</p>
                </div>

                <div class="flex items-center gap-2.5">
                    <form action="{{ route('admin.actors.sync_api') }}" method="POST" onsubmit="return confirm('Mulai sinkronisasi data Aktor dari MovieBox API di latar belakang?')">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer" title="Sync Otomatis dari API">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            <span>Sync API Aktor</span>
                        </button>
                    </form>

                    <form method="GET" action="{{ route('admin.actors.index') }}" class="w-44">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-zinc-800 bg-zinc-950 focus-within:border-amber-500 transition-all">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-500"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama aktor..." 
                                   class="w-full bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5">Aktor</th>
                            <th class="px-4 py-3.5">Jumlah Film</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60">
                        @forelse($actors as $a)
                            <tr class="hover:bg-zinc-800/40 transition-colors group">
                                <td class="px-4 py-3.5 flex items-center gap-3">
                                    @if($a->photo_url)
                                        <img src="{{ $a->photo_url }}" 
                                             class="w-8 h-8 rounded-full object-cover shrink-0 border border-zinc-700 bg-zinc-950" 
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="w-8 h-8 rounded-full bg-zinc-800 border border-zinc-700 text-zinc-400 flex items-center justify-center shrink-0" style="display: none;">
                                            <i data-lucide="user" class="w-4 h-4 text-zinc-400"></i>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-zinc-800 border border-zinc-700 text-zinc-400 flex items-center justify-center shrink-0">
                                            <i data-lucide="user" class="w-4 h-4 text-zinc-400"></i>
                                        </div>
                                    @endif
                                    <span class="font-bold text-white text-xs">{{ $a->name }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400 font-mono font-bold text-[11px]">
                                        {{ number_format($a->films_count) }} film
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5" x-data="{ editing: false, name: '{{ addslashes($a->name) }}', photo_url: '{{ addslashes($a->photo_url) }}' }">
                                        <button type="button" @click="editing = !editing" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors" title="Edit Aktor">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>

                                        <form action="{{ route('admin.actors.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aktor {{ addslashes($a->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Hapus Aktor">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>

                                        <!-- Quick Edit Modal -->
                                        <div x-show="editing" x-cloak 
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                            <div @click.away="editing = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
                                                <div class="flex items-center gap-3 border-b border-zinc-800 pb-3">
                                                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    </div>
                                                    <h4 class="font-bold text-white text-sm font-['Outfit']">Edit Data Aktor</h4>
                                                </div>

                                                <form action="{{ route('admin.actors.update', $a->id) }}" method="POST" class="space-y-4">
                                                    @csrf
                                                    @method('PUT')
                                                    <div>
                                                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Aktor *</label>
                                                        <input type="text" name="name" x-model="name" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">URL Foto Profil</label>
                                                        <input type="url" name="photo_url" x-model="photo_url" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                                    </div>
                                                    <div class="flex justify-end gap-2.5 pt-2 border-t border-zinc-800">
                                                        <button type="button" @click="editing = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">Batal</button>
                                                        <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-xs font-bold text-zinc-950 transition-all shadow-lg shadow-amber-500/20 cursor-pointer">Perbarui</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center text-zinc-500">Belum ada data aktor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($actors->hasPages())
                <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                    {{ $actors->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
