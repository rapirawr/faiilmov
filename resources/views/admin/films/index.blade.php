@extends('layouts.admin')

@section('title', 'Manajemen Film | faiiladmin')
@section('page_title', 'Manajemen Film')

@section('content')
<div x-data="{ trashModalOpen: false }" class="space-y-6 relative">

<script>
(function() {
    const PAGE_IDS = [{{ $films->pluck('id')->implode(',') }}];
    let selectedIds = new Set();

    function updateUI() {
        const count = selectedIds.size;

        // Update floating toolbar
        const toolbar = document.getElementById('bulk-toolbar');
        const countEl = document.getElementById('bulk-count');
        if (toolbar) toolbar.style.display = count > 0 ? 'flex' : 'none';
        if (countEl) countEl.textContent = count;

        // Update header checkbox state
        const headerChk = document.getElementById('chk-all');
        if (headerChk) {
            if (PAGE_IDS.length > 0 && PAGE_IDS.every(id => selectedIds.has(id))) {
                headerChk.indeterminate = false;
                headerChk.checked = true;
            } else if (count > 0) {
                headerChk.indeterminate = true;
                headerChk.checked = false;
            } else {
                headerChk.indeterminate = false;
                headerChk.checked = false;
            }
        }

        // Update visual state of custom checkboxes
        document.querySelectorAll('.film-chk-visual').forEach(el => {
            const id = parseInt(el.dataset.id);
            const isChecked = selectedIds.has(id);
            el.classList.toggle('bg-white', isChecked);
            el.classList.toggle('border-white', isChecked);
            el.classList.toggle('bg-zinc-950', !isChecked);
            el.classList.toggle('border-white/20', !isChecked);
            const svg = el.querySelector('svg');
            if (svg) svg.style.display = isChecked ? 'block' : 'none';
            // Highlight row
            const row = el.closest('tr');
            if (row) row.classList.toggle('bg-white/5', isChecked);
        });

        // Update visual for header checkbox
        const headerVisual = document.getElementById('chk-all-visual');
        const allChecked = PAGE_IDS.length > 0 && PAGE_IDS.every(id => selectedIds.has(id));
        if (headerVisual) {
            headerVisual.classList.toggle('bg-white', allChecked);
            headerVisual.classList.toggle('border-white', allChecked);
            headerVisual.classList.toggle('bg-zinc-950', !allChecked);
            headerVisual.classList.toggle('border-white/20', !allChecked);
            const svg = headerVisual.querySelector('svg');
            if (svg) svg.style.display = allChecked ? 'block' : 'none';
        }

        // Update hidden inputs for bulk form
        const bulkForm = document.getElementById('bulk-delete-form');
        if (bulkForm) {
            bulkForm.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
            selectedIds.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                bulkForm.appendChild(inp);
            });
        }
    }

    function clearAll() {
        selectedIds.clear();
        updateUI();
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Force clean state — ignore any browser-cached checkbox states
        selectedIds.clear();

        // Header "select all" click
        const headerChk = document.getElementById('chk-all');
        if (headerChk) {
            headerChk.checked = false;
            headerChk.addEventListener('change', function () {
                if (this.checked) {
                    PAGE_IDS.forEach(id => selectedIds.add(id));
                } else {
                    PAGE_IDS.forEach(id => selectedIds.delete(id));
                }
                updateUI();
            });
        }

        // Individual film checkboxes
        document.querySelectorAll('.film-chk').forEach(chk => {
            chk.checked = false; // force clear browser cache state
            chk.addEventListener('change', function () {
                const id = parseInt(this.value);
                if (this.checked) selectedIds.add(id);
                else selectedIds.delete(id);
                updateUI();
            });
        });

        // Clear selection button
        const clearBtn = document.getElementById('bulk-clear');
        if (clearBtn) clearBtn.addEventListener('click', clearAll);

        updateUI();
    });
})();
</script>
    
    <!-- Live Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
        <div class="p-3.5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <span class="text-zinc-400 font-semibold">Total Film:</span>
            <span class="font-extrabold text-white text-sm font-mono">{{ number_format($stats['total'] ?? 0) }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <span class="text-zinc-400 font-semibold">Movie:</span>
            <span class="font-extrabold text-blue-400 text-sm font-mono">{{ number_format($stats['movies'] ?? 0) }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <span class="text-zinc-400 font-semibold">Series:</span>
            <span class="font-extrabold text-purple-400 text-sm font-mono">{{ number_format($stats['series'] ?? 0) }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between">
            <span class="text-zinc-400 font-semibold">Unrated:</span>
            <span class="font-extrabold text-amber-400 text-sm font-mono">{{ number_format($stats['unrated'] ?? 0) }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/60 border border-white/10 flex items-center justify-between col-span-2 sm:col-span-1">
            <span class="text-zinc-400 font-semibold">Di Sampah:</span>
            <span class="font-extrabold text-red-400 text-sm font-mono">{{ number_format($stats['trash'] ?? 0) }}</span>
        </div>
    </div>

    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('admin.films.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul film..." 
                       class="w-full min-w-0 bg-transparent py-2 text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <select name="type" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                <option value="">Semua Tipe</option>
                <option value="movie" {{ request('type') === 'movie' ? 'selected' : '' }}>Movie</option>
                <option value="series" {{ request('type') === 'series' ? 'selected' : '' }}>Series</option>
            </select>

            <select name="content_rating" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                <option value="">Semua Usia</option>
                <option value="SU" {{ request('content_rating') === 'SU' ? 'selected' : '' }}>SU (Semua Umur)</option>
                <option value="13+" {{ request('content_rating') === '13+' ? 'selected' : '' }}>13+</option>
                <option value="16+" {{ request('content_rating') === '16+' ? 'selected' : '' }}>16+</option>
                <option value="18+" {{ request('content_rating') === '18+' ? 'selected' : '' }}>18+</option>
                <option value="UNRATED" {{ request('content_rating') === 'UNRATED' ? 'selected' : '' }}>Unrated (Kosong)</option>
            </select>

            <select name="genre" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                <option value="">Semua Genre</option>
                @foreach($genres as $g)
                    <option value="{{ $g->id }}" {{ request('genre') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>

            <select name="sort" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Terbaru</option>
                <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Rating Tertinggi</option>
                <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>View Banyak</option>
            </select>
        </form>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.films.auto_rate_all') }}" method="POST" onsubmit="return confirm('Jalankan Auto-Rate otomatis berbasis genre & sinopsis untuk film yang belum memiliki rating?')">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 font-bold text-xs flex items-center gap-2 border border-amber-500/30 transition-all cursor-pointer" title="Auto Deteksi Usia Berdasarkan Genre & Sinopsis">
                    <i data-lucide="wand-2" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Auto-Rate Massal</span>
                </button>
            </form>

            <a href="{{ route('admin.films.content_rating') }}" class="px-3.5 py-2 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 font-bold text-xs flex items-center gap-2 border border-purple-500/20 transition-all">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                <span>Rating Massal</span>
            </a>

            <!-- Trash Modal Trigger Button -->
            <button type="button" @click="trashModalOpen = true" class="px-3.5 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold text-xs flex items-center gap-2 border border-red-500/20 transition-all cursor-pointer">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>Sampah</span>
                <span class="px-1.5 py-0.5 rounded-full bg-red-500 text-black text-[10px] font-extrabold">{{ count($trashedFilms) }}</span>
            </button>

            <form action="{{ route('admin.films.sync_api') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-2 border border-white/10 transition-all cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span>Sync API</span>
                </button>
            </form>

            <a href="{{ route('admin.films.create') }}" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Film</span>
            </a>
        </div>
    </div>

    <!-- Active Films Table -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5 w-12 text-center">
                            <label class="relative inline-flex items-center justify-center cursor-pointer select-none">
                                <input type="checkbox" id="chk-all" autocomplete="off" class="sr-only">
                                <div id="chk-all-visual" class="w-4 h-4 rounded border transition-all duration-200 flex items-center justify-center shadow-sm bg-zinc-950 border-white/20 hover:border-white/50">
                                    <svg style="display:none" class="w-3 h-3 fill-current text-black" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </label>
                        </th>
                        <th class="px-4 py-3.5">Film</th>
                        <th class="px-4 py-3.5">Tipe & Usia</th>
                        <th class="px-4 py-3.5">Genre</th>
                        <th class="px-4 py-3.5">Rating</th>
                        <th class="px-4 py-3.5">Views</th>
                        <th class="px-4 py-3.5">Kualitas</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($films as $film)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 text-center">
                                <label class="relative inline-flex items-center justify-center cursor-pointer select-none">
                                    <input type="checkbox" value="{{ $film->id }}" autocomplete="off" class="film-chk sr-only">
                                    <div class="film-chk-visual w-4 h-4 rounded border transition-all duration-200 flex items-center justify-center shadow-sm bg-zinc-950 border-white/20 hover:border-white/50" data-id="{{ $film->id }}">
                                        <svg style="display:none" class="w-3 h-3 fill-current text-black" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </label>
                            </td>
                            <td class="px-4 py-3.5 flex items-center gap-3">
                                <img src="{{ $film->poster_url }}" class="w-9 h-13 object-cover rounded-lg shrink-0">
                                <div>
                                    <p class="font-bold text-white text-sm line-clamp-1">{{ $film->title }}</p>
                                    <p class="text-[11px] text-zinc-400">{{ $film->release_year }} • {{ $film->duration_minutes }} mnt</p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $film->subject_type === 'series' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30' }}">
                                        {{ $film->subject_type }}
                                    </span>

                                    @if($film->content_rating)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase {{ in_array($film->content_rating, ['SU','G','PG']) ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' }}">
                                            {{ $film->content_rating }}
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold text-zinc-500 bg-white/5 border border-white/10">
                                            UNRATED
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                    @foreach($film->genres as $g)
                                        <span class="px-1.5 py-0.5 rounded bg-white/10 text-[10px] text-zinc-300">{{ $g->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-amber-400">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                                    {{ number_format($film->rating, 1) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-zinc-300">
                                {{ number_format($film->view_count) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-bold font-mono">
                                    {{ $film->max_resolution ?: '1080P' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.films.edit', $film->id) }}" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>

                                    <form action="{{ route('admin.films.destroy', $film->id) }}" method="POST" onsubmit="return confirm('Pindahkan film ini ke tempat sampah?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus ke Sampah">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-zinc-500">Tidak ada film aktif ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($films->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $films->links() }}
            </div>
        @endif
    </div>

    <!-- Floating Bulk Action Toolbar -->
    <div id="bulk-toolbar" style="display:none"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-zinc-900/95 border border-white/20 rounded-full px-6 py-3 shadow-2xl backdrop-blur-xl flex items-center gap-4">
        <span class="text-xs font-bold text-white"><span id="bulk-count" class="text-amber-400 font-extrabold">0</span> Film Terpilih</span>
        
        <div class="h-4 w-[1px] bg-white/20"></div>

        <form id="bulk-delete-form" action="{{ route('admin.films.bulk_delete') }}" method="POST" onsubmit="return confirm('Yakin ingin memindahkan semua film terpilih ke tempat sampah?')">
            @csrf
            <button type="submit" class="px-3.5 py-1.5 rounded-full bg-red-500/20 hover:bg-red-500/30 text-red-300 font-bold text-xs border border-red-500/30 transition-colors flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>Hapus ke Sampah</span>
            </button>
        </form>

        <button id="bulk-clear" type="button" class="px-3 py-1 rounded-full bg-white/10 hover:bg-white/20 text-zinc-300 hover:text-white font-semibold text-xs transition-colors cursor-pointer">
            Batal
        </button>
    </div>

    <!-- Trash Bin Modal -->
    <div x-show="trashModalOpen" 
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="trashModalOpen = false" 
             class="bg-zinc-900 border border-white/10 rounded-2xl max-w-3xl w-full p-6 space-y-6 shadow-2xl relative max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-500/20 border border-red-500/30 flex items-center justify-center text-red-400">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-base font-['Outfit']">Tempat Sampah Film (Trash Bin)</h3>
                        <p class="text-xs text-zinc-400">Film yang dihapus sementara. Anda dapat memulihkan atau menghapusnya secara permanen.</p>
                    </div>
                </div>

                <button @click="trashModalOpen = false" class="p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-white/5">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Trashed Films Table / List -->
            <div class="flex-1 overflow-y-auto pr-1">
                @if(count($trashedFilms) > 0)
                    <table class="w-full text-left text-xs">
                        <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                            <tr>
                                <th class="px-3 py-2.5">Film</th>
                                <th class="px-3 py-2.5">Tipe</th>
                                <th class="px-3 py-2.5">Tanggal Dihapus</th>
                                <th class="px-3 py-2.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($trashedFilms as $tf)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-3 py-3 flex items-center gap-3">
                                        <img src="{{ $tf->poster_url }}" class="w-8 h-11 object-cover rounded shrink-0">
                                        <div>
                                            <p class="font-bold text-white text-xs line-clamp-1">{{ $tf->title }}</p>
                                            <p class="text-[10px] text-zinc-400">{{ $tf->release_year }}</p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase {{ $tf->subject_type === 'series' ? 'bg-purple-500/20 text-purple-300' : 'bg-blue-500/20 text-blue-300' }}">
                                            {{ $tf->subject_type }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-zinc-400 text-[11px]">
                                        {{ $tf->deleted_at->diffForHumans() }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Restore Button -->
                                            <form action="{{ route('admin.films.restore', $tf->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 text-[11px] font-bold border border-emerald-500/30 transition-colors flex items-center gap-1 cursor-pointer">
                                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                                    <span>Pulihkan</span>
                                                </button>
                                            </form>

                                            <!-- Force Delete Button -->
                                            <form action="{{ route('admin.films.force_delete', $tf->id) }}" method="POST" onsubmit="return confirm('HAPUS PERMANEN film ini? Data yang dihapus permanen tidak dapat dikembalikan!')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-300 text-[11px] font-bold border border-red-500/30 transition-colors flex items-center gap-1 cursor-pointer">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    <span>Hapus Permanen</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 text-center text-zinc-500 space-y-2">
                        <i data-lucide="check-circle-2" class="w-10 h-10 mx-auto text-zinc-600"></i>
                        <p class="text-sm font-semibold text-zinc-400">Tempat sampah kosong</p>
                        <p class="text-xs">Tidak ada film yang dihapus sementara.</p>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between border-t border-white/10 pt-4">
                @if(count($trashedFilms) > 0)
                    <form action="{{ route('admin.films.empty_trash') }}" method="POST" onsubmit="return confirm('Kosongkan SELURUH tempat sampah? Semua film di sampah akan dihapus permanen!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-red-600/30 hover:bg-red-600/50 text-red-200 text-xs font-bold border border-red-500/40 transition-colors flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400"></i>
                            <span>Kosongkan Tempat Sampah</span>
                        </button>
                    </form>
                @else
                    <div></div>
                @endif

                <button @click="trashModalOpen = false" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-colors cursor-pointer">
                    Tutup
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
