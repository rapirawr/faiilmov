@extends('layouts.admin')

@section('title', 'Manajemen Film | faiiladmin')
@section('page_title', 'Manajemen Film & Katalog')

@section('content')
<div x-data="{ 
    trashModalOpen: false, 
    syncDropdownOpen: false,
    ratingDropdownOpen: false,
    isSubmitting: false
}" class="space-y-6 relative">

<script>
(function() {
    const STORAGE_KEY = 'admin_selected_film_ids';
    const PAGE_IDS = [{{ $films->pluck('id')->implode(',') }}];

    function getStoredIds() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            return raw ? new Set(JSON.parse(raw).map(Number)) : new Set();
        } catch (e) {
            return new Set();
        }
    }

    function saveStoredIds(set) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(set)));
        } catch (e) {}
    }

    let selectedIds = getStoredIds();

    function updateUI() {
        const count = selectedIds.size;

        // Update floating toolbar
        const toolbar = document.getElementById('bulk-toolbar');
        const countEl = document.getElementById('bulk-count');
        if (toolbar) {
            if (count > 0) {
                toolbar.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
                toolbar.classList.add('translate-y-0', 'opacity-100');
            } else {
                toolbar.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                toolbar.classList.remove('translate-y-0', 'opacity-100');
            }
        }
        if (countEl) countEl.textContent = count;

        // Update header checkbox state based on current page items
        const headerChk = document.getElementById('chk-all');
        const pageCount = PAGE_IDS.length;
        const pageSelectedCount = PAGE_IDS.filter(id => selectedIds.has(id)).length;
        if (headerChk) {
            if (pageCount > 0 && pageSelectedCount === pageCount) {
                headerChk.indeterminate = false;
                headerChk.checked = true;
            } else if (pageSelectedCount > 0) {
                headerChk.indeterminate = true;
                headerChk.checked = false;
            } else {
                headerChk.indeterminate = false;
                headerChk.checked = false;
            }
        }

        // Update checkboxes & visual state on current page
        document.querySelectorAll('.film-chk').forEach(chk => {
            const id = parseInt(chk.value);
            chk.checked = selectedIds.has(id);
        });

        document.querySelectorAll('.film-chk-visual').forEach(el => {
            const id = parseInt(el.dataset.id);
            const isChecked = selectedIds.has(id);
            el.classList.toggle('bg-amber-400', isChecked);
            el.classList.toggle('border-amber-400', isChecked);
            el.classList.toggle('bg-zinc-950', !isChecked);
            el.classList.toggle('border-white/20', !isChecked);
            const svg = el.querySelector('svg');
            if (svg) svg.style.display = isChecked ? 'block' : 'none';
            
            // Highlight row
            const row = el.closest('tr');
            if (row) row.classList.toggle('bg-amber-500/5', isChecked);
        });

        // Update visual for header checkbox
        const headerVisual = document.getElementById('chk-all-visual');
        const allChecked = pageCount > 0 && pageSelectedCount === pageCount;
        const someChecked = pageSelectedCount > 0 && !allChecked;
        if (headerVisual) {
            headerVisual.classList.toggle('bg-amber-400', allChecked || someChecked);
            headerVisual.classList.toggle('border-amber-400', allChecked || someChecked);
            headerVisual.classList.toggle('bg-zinc-950', !allChecked && !someChecked);
            headerVisual.classList.toggle('border-white/20', !allChecked && !someChecked);
            const svg = headerVisual.querySelector('svg');
            if (svg) svg.style.display = (allChecked || someChecked) ? 'block' : 'none';
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
        saveStoredIds(selectedIds);
        updateUI();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const headerChk = document.getElementById('chk-all');
        if (headerChk) {
            headerChk.addEventListener('change', function () {
                if (this.checked) {
                    PAGE_IDS.forEach(id => selectedIds.add(id));
                } else {
                    PAGE_IDS.forEach(id => selectedIds.delete(id));
                }
                saveStoredIds(selectedIds);
                updateUI();
            });
        }

        document.querySelectorAll('.film-chk').forEach(chk => {
            const id = parseInt(chk.value);
            chk.checked = selectedIds.has(id);
            chk.addEventListener('change', function () {
                const val = parseInt(this.value);
                if (this.checked) selectedIds.add(val);
                else selectedIds.delete(val);
                saveStoredIds(selectedIds);
                updateUI();
            });
        });

        const clearBtn = document.getElementById('bulk-clear');
        if (clearBtn) clearBtn.addEventListener('click', clearAll);

        const bulkForm = document.getElementById('bulk-delete-form');
        if (bulkForm) {
            bulkForm.addEventListener('submit', function() {
                sessionStorage.removeItem(STORAGE_KEY);
            });
        }

        updateUI();
    });
})();
</script>
    
    <!-- Interactive Stats Shortcuts Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
        <!-- Total Films -->
        <a href="{{ route('admin.films.index') }}" 
           class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all flex flex-col justify-between hover:scale-[1.02] shadow-md group {{ !request()->hasAny(['type', 'content_rating', 'search', 'genre']) ? 'border-amber-500/40 bg-amber-500/5' : 'border-white/10 hover:border-white/20' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-white transition-colors">Total Film</span>
                <i data-lucide="layers" class="w-4 h-4 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
            </div>
            <span class="font-extrabold text-white text-xl mt-2 font-['Outfit']">{{ number_format($stats['total'] ?? 0) }}</span>
        </a>

        <!-- Movie Filter Shortcut -->
        <a href="{{ route('admin.films.index', ['type' => 'movie']) }}" 
           class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all flex flex-col justify-between hover:scale-[1.02] shadow-md group {{ request('type') === 'movie' ? 'border-sky-500/40 bg-sky-500/10' : 'border-white/10 hover:border-sky-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-sky-300 transition-colors">Movie</span>
                <i data-lucide="clapperboard" class="w-4 h-4 text-sky-400"></i>
            </div>
            <span class="font-extrabold text-sky-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['movies'] ?? 0) }}</span>
        </a>

        <!-- Series Filter Shortcut -->
        <a href="{{ route('admin.films.index', ['type' => 'series']) }}" 
           class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all flex flex-col justify-between hover:scale-[1.02] shadow-md group {{ request('type') === 'series' ? 'border-purple-500/40 bg-purple-500/10' : 'border-white/10 hover:border-purple-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-purple-300 transition-colors">Series</span>
                <i data-lucide="tv" class="w-4 h-4 text-purple-400"></i>
            </div>
            <span class="font-extrabold text-purple-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['series'] ?? 0) }}</span>
        </a>

        <!-- Dracin Filter Shortcut -->
        <a href="{{ route('admin.films.index', ['type' => 'dracin']) }}" 
           class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all flex flex-col justify-between hover:scale-[1.02] shadow-md group {{ request('type') === 'dracin' ? 'border-rose-500/40 bg-rose-500/10' : 'border-white/10 hover:border-rose-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-rose-300 transition-colors">Dracin</span>
                <i data-lucide="sparkles" class="w-4 h-4 text-rose-400"></i>
            </div>
            <span class="font-extrabold text-rose-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['dracin'] ?? 0) }}</span>
        </a>

        <!-- Unrated Filter Shortcut -->
        <a href="{{ route('admin.films.index', ['content_rating' => 'UNRATED']) }}" 
           class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all flex flex-col justify-between hover:scale-[1.02] shadow-md group {{ request('content_rating') === 'UNRATED' ? 'border-amber-500/40 bg-amber-500/10' : 'border-white/10 hover:border-amber-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-amber-300 transition-colors">Unrated</span>
                <i data-lucide="shield-alert" class="w-4 h-4 text-amber-400"></i>
            </div>
            <span class="font-extrabold text-amber-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['unrated'] ?? 0) }}</span>
        </a>

        <!-- Coming Soon Filter Shortcut -->
        <a href="{{ route('admin.films.index', ['coming_soon' => 'yes']) }}" 
           class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all flex flex-col justify-between hover:scale-[1.02] shadow-md group {{ request('coming_soon') === 'yes' ? 'border-amber-500/40 bg-amber-500/10' : 'border-white/10 hover:border-amber-500/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-amber-300 transition-colors">Coming Soon</span>
                <i data-lucide="calendar-clock" class="w-4 h-4 text-amber-400"></i>
            </div>
            <span class="font-extrabold text-amber-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['coming_soon'] ?? 0) }}</span>
        </a>

        <!-- Trash Bin Shortcut Trigger -->
        <button type="button" @click="trashModalOpen = true" 
                class="p-3.5 rounded-2xl bg-zinc-900/80 border border-white/10 hover:border-red-500/40 hover:bg-red-500/5 transition-all flex flex-col justify-between hover:scale-[1.02] shadow-md group text-left cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-zinc-400 font-semibold group-hover:text-red-400 transition-colors">Di Sampah</span>
                <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
            </div>
            <span class="font-extrabold text-red-400 text-xl mt-2 font-['Outfit']">{{ number_format($stats['trash'] ?? 0) }}</span>
        </button>
    </div>

    <!-- Toolbar: Filter Bar & Primary Actions -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        
        <!-- Search & Filter Controls -->
        <form method="GET" action="{{ route('admin.films.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[220px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul film, cast, atau slug..." 
                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <!-- Type Filter Dropdown -->
            <select name="type" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                <option value="">Semua Tipe</option>
                <option value="movie" {{ request('type') === 'movie' ? 'selected' : '' }}>🎬 Movie</option>
                <option value="series" {{ request('type') === 'series' ? 'selected' : '' }}>📺 Series</option>
                <option value="dracin" {{ request('type') === 'dracin' ? 'selected' : '' }}>🌸 Dracin</option>
            </select>

            <!-- Coming Soon Status Filter Dropdown -->
            <select name="coming_soon" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                <option value="">Semua Status Rilis</option>
                <option value="yes" {{ request('coming_soon') === 'yes' ? 'selected' : '' }}>⏳ Coming Soon</option>
                <option value="no" {{ request('coming_soon') === 'no' ? 'selected' : '' }}>✅ Sudah Rilis</option>
            </select>

            <!-- Content Rating Filter Dropdown -->
            <select name="content_rating" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                <option value="">Semua Usia</option>
                <option value="SU" {{ request('content_rating') === 'SU' ? 'selected' : '' }}>🟢 SU (Semua Umur)</option>
                <option value="13+" {{ request('content_rating') === '13+' ? 'selected' : '' }}>🔵 13+</option>
                <option value="16+" {{ request('content_rating') === '16+' ? 'selected' : '' }}>🟠 16+</option>
                <option value="18+" {{ request('content_rating') === '18+' ? 'selected' : '' }}>🔴 18+</option>
                <option value="UNRATED" {{ request('content_rating') === 'UNRATED' ? 'selected' : '' }}>⚪ Unrated (Kosong)</option>
            </select>

            <!-- Genre Filter Dropdown -->
            <select name="genre" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer max-w-[140px]">
                <option value="">Semua Genre</option>
                @foreach($genres as $g)
                    <option value="{{ $g->id }}" {{ request('genre') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>

            <!-- Sort Dropdown -->
            <select name="sort" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Terbaru</option>
                <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Rating Tertinggi</option>
                <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>View Banyak</option>
            </select>

            <!-- Reset Filter Button -->
            @if(request()->hasAny(['search', 'type', 'content_rating', 'genre', 'sort']))
                <a href="{{ route('admin.films.index') }}" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors" title="Reset Semua Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            @endif
        </form>

        <!-- Grouped Action Buttons Bar -->
        <div class="flex items-center gap-2.5 flex-wrap">
            
            <!-- Rating Tools Dropdown -->
            <div class="relative" @click.outside="ratingDropdownOpen = false">
                <button type="button" @click="ratingDropdownOpen = !ratingDropdownOpen" 
                        class="px-3.5 py-2 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 font-bold text-xs flex items-center gap-2 border border-purple-500/30 transition-all cursor-pointer">
                    <i data-lucide="wand-2" class="w-3.5 h-3.5 text-purple-400"></i>
                    <span>Opsi Rating</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-purple-400 transition-transform duration-200" :class="ratingDropdownOpen ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="ratingDropdownOpen" 
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-56 bg-zinc-900 border border-white/15 rounded-2xl shadow-2xl p-1.5 z-40 space-y-1"
                     style="display: none;">
                    
                    <form action="{{ route('admin.films.auto_rate_all') }}" method="POST" @submit="isSubmitting = true">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-xl hover:bg-white/10 text-xs text-amber-300 font-semibold flex items-center gap-2.5 transition-colors cursor-pointer">
                            <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
                            <span>Auto-Rate Massal (AI)</span>
                        </button>
                    </form>

                    <a href="{{ route('admin.films.content_rating') }}" class="w-full text-left px-3 py-2 rounded-xl hover:bg-white/10 text-xs text-purple-300 font-semibold flex items-center gap-2.5 transition-colors block">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-purple-400"></i>
                        <span>Editor Rating Masif</span>
                    </a>
                </div>
            </div>

            <!-- Sync API Dropdown -->
            <div class="relative" @click.outside="syncDropdownOpen = false">
                <button type="button" @click="syncDropdownOpen = !syncDropdownOpen" 
                        class="px-3.5 py-2 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-300 font-bold text-xs flex items-center gap-2 border border-blue-500/30 transition-all cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-400"></i>
                    <span>Sync Content</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-blue-400 transition-transform duration-200" :class="syncDropdownOpen ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="syncDropdownOpen" 
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-60 bg-zinc-900 border border-white/15 rounded-2xl shadow-2xl p-1.5 z-40 space-y-1"
                     style="display: none;">
                    
                    <form action="{{ route('admin.films.sync_api') }}" method="POST" @submit="isSubmitting = true">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-xl hover:bg-white/10 text-xs text-blue-300 font-semibold flex items-center gap-2.5 transition-colors cursor-pointer">
                            <i data-lucide="film" class="w-4 h-4 text-blue-400"></i>
                            <span>Sync MovieBox API</span>
                        </button>
                    </form>

                    <form action="{{ route('admin.films.sync_dracin_api') }}" method="POST" @submit="isSubmitting = true">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-xl hover:bg-white/10 text-xs text-rose-300 font-semibold flex items-center gap-2.5 transition-colors cursor-pointer">
                            <i data-lucide="sparkles" class="w-4 h-4 text-rose-400"></i>
                            <span>Sync Dracin (Anichin API)</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Add New Film (Primary CTA) -->
            <a href="{{ route('admin.films.create') }}" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 hover:scale-[1.02] transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Film</span>
            </a>
        </div>
    </div>

    <!-- Active Films Table Container -->
    <div class="bg-zinc-900/80 border border-white/10 rounded-2xl overflow-hidden shadow-xl backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10 tracking-wider">
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
                        <th class="px-4 py-3.5">Judul & Info</th>
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
                        <tr class="hover:bg-white/5 transition-colors group">
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
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-9 h-13 rounded-lg overflow-hidden shrink-0 bg-zinc-950 border border-white/10 group-hover:border-amber-400/40 transition-colors">
                                        <img src="{{ $film->poster_url }}" alt="{{ $film->title }}" loading="lazy" referrerpolicy="no-referrer" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('film.show', $film->slug) }}" target="_blank" class="font-bold text-white text-xs hover:text-amber-400 transition-colors line-clamp-1 flex items-center gap-1.5" title="Buka Detail di Website">
                                            <span>{{ $film->title }}</span>
                                            <i data-lucide="external-link" class="w-3 h-3 text-zinc-500 group-hover:text-amber-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                        </a>
                                        <p class="text-[11px] text-zinc-400 mt-0.5">{{ $film->release_year }} • {{ $film->duration_minutes }} mnt</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 space-y-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $film->subject_type === 'dracin' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : ($film->subject_type === 'series' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30') }}">
                                        {{ $film->subject_type }}
                                    </span>

                                    @if($film->isComingSoon())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1 inline-flex">
                                            <i data-lucide="clock" class="w-3 h-3 text-amber-400"></i>
                                            <span>Coming Soon</span>
                                        </span>
                                    @endif

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
                                    @foreach($film->genres->take(3) as $g)
                                        <span class="px-1.5 py-0.5 rounded bg-white/5 border border-white/10 text-[9.5px] text-zinc-300 font-medium">{{ $g->name }}</span>
                                    @endforeach
                                    @if($film->genres->count() > 3)
                                        <span class="px-1.5 py-0.5 rounded bg-white/5 text-[9.5px] text-zinc-500">+{{ $film->genres->count() - 3 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-amber-400">
                                <span class="flex items-center gap-1 font-mono text-xs">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                                    {{ number_format($film->rating, 1) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-zinc-300 text-xs">
                                {{ number_format($film->view_count) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-bold font-mono">
                                    {{ $film->max_resolution ?: '1080P' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Quick Toggle Coming Soon -->
                                    <form action="{{ route('admin.films.toggle_coming_soon', $film->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $film->isComingSoon() ? 'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/30' : 'bg-white/5 text-zinc-400 hover:bg-white/10 hover:text-white' }}" title="{{ $film->isComingSoon() ? 'Batalkan Coming Soon (Film Sudah Rilis)' : 'Tandai sebagai Coming Soon (Segera Hadir)' }}">
                                            <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                                        </button>
                                    </form>

                                    <a href="{{ route('film.show', $film->slug) }}" target="_blank" class="p-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white transition-colors" title="Lihat di Web">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    <a href="{{ route('admin.films.edit', $film->id) }}" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition-colors" title="Edit Film">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>

                                    <form action="{{ route('admin.films.destroy', $film->id) }}" method="POST" onsubmit="return confirm('Pindahkan film \'{{ addslashes($film->title) }}\' ke tempat sampah?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors cursor-pointer" title="Hapus ke Sampah">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center text-zinc-500 space-y-3">
                                <i data-lucide="film" class="w-12 h-12 mx-auto text-zinc-700"></i>
                                <p class="text-sm font-semibold text-zinc-400">Tidak ada film ditemukan</p>
                                <p class="text-xs text-zinc-500 max-w-sm mx-auto">Tidak ada film yang cocok dengan filter atau kata kunci pencarian Anda.</p>
                                @if(request()->hasAny(['search', 'type', 'content_rating', 'genre']))
                                    <a href="{{ route('admin.films.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 text-white font-bold text-xs hover:bg-white/20 transition-colors mt-2">
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
        @if($films->hasPages())
            <div class="p-4 border-t border-white/10 bg-zinc-950/40">
                {{ $films->links() }}
            </div>
        @endif
    </div>

    <!-- Floating Bulk Action Bar (Animated Slide-up) -->
    <div id="bulk-toolbar" 
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-zinc-900/95 border border-amber-500/30 rounded-full px-6 py-3 shadow-2xl backdrop-blur-xl flex items-center gap-4 transition-all duration-300 transform translate-y-24 opacity-0 pointer-events-none">
        <span class="text-xs font-bold text-white"><span id="bulk-count" class="text-amber-400 font-extrabold text-sm font-mono">0</span> Film Terpilih</span>
        
        <div class="h-4 w-[1px] bg-white/20"></div>

        <form id="bulk-delete-form" action="{{ route('admin.films.bulk_delete') }}" method="POST" onsubmit="return confirm('Yakin ingin memindahkan semua film terpilih ke tempat sampah?')">
            @csrf
            <button type="submit" class="px-4 py-1.5 rounded-full bg-red-500/20 hover:bg-red-500/30 text-red-300 font-bold text-xs border border-red-500/30 transition-colors flex items-center gap-1.5 cursor-pointer">
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
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="trashModalOpen = false" 
             class="bg-zinc-900 border border-white/15 rounded-3xl max-w-3xl w-full p-6 space-y-6 shadow-2xl relative max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-red-500/20 border border-red-500/30 flex items-center justify-center text-red-400">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-base font-['Outfit']">Tempat Sampah Film (Trash Bin)</h3>
                        <p class="text-xs text-zinc-400">Film yang dihapus sementara. Anda dapat memulihkan atau menghapusnya secara permanen.</p>
                    </div>
                </div>

                <button @click="trashModalOpen = false" class="p-2 rounded-xl text-zinc-400 hover:text-white hover:bg-white/5 transition-colors cursor-pointer">
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
                                        <img src="{{ $tf->poster_url }}" referrerpolicy="no-referrer" class="w-8 h-11 object-cover rounded shrink-0">
                                        <div>
                                            <p class="font-bold text-white text-xs line-clamp-1">{{ $tf->title }}</p>
                                            <p class="text-[10px] text-zinc-400">{{ $tf->release_year }}</p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase {{ $tf->subject_type === 'dracin' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : ($tf->subject_type === 'series' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30') }}">
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
                                                <button type="submit" class="px-2.5 py-1 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 text-[11px] font-bold border border-emerald-500/30 transition-colors flex items-center gap-1 cursor-pointer">
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
