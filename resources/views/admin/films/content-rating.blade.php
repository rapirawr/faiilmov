@extends('layouts.admin')

@section('title', 'Rating Usia Massal | faiiladmin')
@section('page_title', 'Pengatur Rating Usia Massal')

@section('content')
<div class="space-y-6" x-data="bulkRatingEditor()">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-white font-['Outfit']">Bulk Content Rating Editor</h2>
            <p class="text-xs text-zinc-400">Atur kualifikasi usia (Parental Rating) untuk banyak film sekaligus secara efisien & otomatis.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <span class="px-3 py-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 font-mono font-bold text-xs">
                Belum Dikategorikan: {{ $unratedCount }} Film
            </span>

            <!-- Auto-Rate Unrated -->
            <form action="{{ route('admin.films.auto_rate_all') }}" method="POST" onsubmit="return confirm('Deteksi dan tetapkan rating usia secara otomatis untuk seluruh film yang belum dikategorikan?')">
                @csrf
                <input type="hidden" name="only_unrated" value="1">
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="wand-2" class="w-3.5 h-3.5"></i>
                    <span>Auto-Rate Unrated</span>
                </button>
            </form>

            <!-- Auto-Rate Overwrite All (Full DB) -->
            <form action="{{ route('admin.films.auto_rate_all') }}" method="POST" onsubmit="return confirm('PERINGATAN: Ini akan mendeteksi ulang dan memperbarui rating usia untuk SELURUH film di basis data. Lanjutkan?')">
                @csrf
                <input type="hidden" name="only_unrated" value="0">
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/30 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Auto-Rate Seluruh DB</span>
                </button>
            </form>

            <a href="{{ route('admin.films.index') }}" class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-1.5 border border-white/10 transition-all">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Search & Quick Filter -->
    <form method="GET" action="{{ route('admin.films.content_rating') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[240px]">
            <i data-lucide="search" class="w-4 h-4 text-zinc-500"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul..." 
                   class="w-full bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:ring-0">
        </div>

        <div class="flex items-center gap-2 text-xs">
            <a href="{{ route('admin.films.content_rating', ['filter' => 'unrated']) }}" 
               class="px-3.5 py-2 rounded-xl border transition-all font-semibold {{ request('filter') === 'unrated' || !request('filter') ? 'bg-amber-500 text-black border-amber-500 font-bold' : 'bg-zinc-900 border-white/10 text-zinc-300 hover:text-white' }}">
                Hanya Unrated ({{ $unratedCount }})
            </a>
            <a href="{{ route('admin.films.content_rating', ['filter' => 'all']) }}" 
               class="px-3.5 py-2 rounded-xl border transition-all font-semibold {{ request('filter') === 'all' ? 'bg-amber-500 text-black border-amber-500 font-bold' : 'bg-zinc-900 border-white/10 text-zinc-300 hover:text-white' }}">
                Semua Film
            </a>
        </div>
    </form>

    <!-- Rating Massal Form -->
    <form action="{{ route('admin.films.update_content_ratings') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 bg-white/5 border-b border-white/10 flex items-center justify-between gap-4">
                <span class="text-xs font-bold text-zinc-300">Daftar Film ({{ $films->count() }} di halaman ini)</span>
                
                <button type="button" 
                        @click="autoDetectCurrentPage()" 
                        :disabled="isDetectingAll"
                        class="px-3.5 py-1.5 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/40 font-bold text-xs transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i data-lucide="wand-2" class="w-3.5 h-3.5" :class="isDetectingAll ? 'animate-spin' : ''"></i>
                    <span x-text="isDetectingAll ? 'Mendeteksi Seluruh Halaman...' : 'Auto-Detect Halaman Ini'"></span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                        <tr>
                            <th class="px-4 py-3.5">Film</th>
                            <th class="px-4 py-3.5">Tipe</th>
                            <th class="px-4 py-3.5">Tahun</th>
                            <th class="px-4 py-3.5">Rating Saat Ini</th>
                            <th class="px-4 py-3.5">Ubah Rating Usia (Auto Detect)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($films as $film)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 flex items-center gap-3">
                                    <img src="{{ $film->poster_url }}" referrerpolicy="no-referrer" class="w-8 h-11 object-cover rounded shrink-0 bg-zinc-900 border border-white/10">
                                    <div>
                                        <p class="font-bold text-white text-xs line-clamp-1">{{ $film->title }}</p>
                                        <p class="text-[10px] text-zinc-400">{{ Str::limit($film->synopsis, 60) }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase {{ $film->subject_type === 'series' ? 'bg-purple-500/20 text-purple-300' : 'bg-blue-500/20 text-blue-300' }}">
                                        {{ $film->subject_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-400 font-mono text-[11px]">
                                    {{ $film->release_year }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($film->content_rating)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ in_array($film->content_rating, ['SU','G','PG']) ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' }}">
                                            {{ $film->content_rating }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold text-zinc-500 bg-white/5 border border-white/10">
                                            UNRATED
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2" x-data="{ loading: false }">
                                        <select name="ratings[{{ $film->id }}]" 
                                                id="rating-select-{{ $film->id }}" 
                                                class="bg-zinc-950 border border-white/15 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 transition-colors">
                                            <option value="" {{ is_null($film->content_rating) ? 'selected' : '' }}>-- Pilih Rating --</option>
                                            <option value="SU" {{ $film->content_rating === 'SU' ? 'selected' : '' }}>SU (Semua Umur)</option>
                                            <option value="G" {{ $film->content_rating === 'G' ? 'selected' : '' }}>G (General)</option>
                                            <option value="PG" {{ $film->content_rating === 'PG' ? 'selected' : '' }}>PG (Parental Guidance)</option>
                                            <option value="13+" {{ $film->content_rating === '13+' ? 'selected' : '' }}>13+ (Remaja)</option>
                                            <option value="16+" {{ $film->content_rating === '16+' ? 'selected' : '' }}>16+ (Dewasa Muda)</option>
                                            <option value="18+" {{ $film->content_rating === '18+' ? 'selected' : '' }}>18+ (Dewasa)</option>
                                        </select>

                                        <!-- Auto Detect Single Button -->
                                        <button type="button" 
                                                @click="detectSingle({{ $film->id }}, $data)" 
                                                :disabled="loading"
                                                class="px-2.5 py-1.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer disabled:opacity-50 shrink-0"
                                                title="Deteksi Rating Otomatis Berdasarkan AI / Genre & Sinopsis">
                                            <i data-lucide="wand-2" class="w-3.5 h-3.5 text-purple-400" :class="loading ? 'animate-spin' : ''"></i>
                                            <span>Auto Detect</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-500">Tidak ada film ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($films->hasPages())
                <div class="p-4 border-t border-white/10">
                    {{ $films->links() }}
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="button" 
                    @click="autoDetectCurrentPage()" 
                    :disabled="isDetectingAll"
                    class="px-4 py-2.5 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/40 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <i data-lucide="wand-2" class="w-4 h-4 text-purple-400" :class="isDetectingAll ? 'animate-spin' : ''"></i>
                <span x-text="isDetectingAll ? 'Mendeteksi Halaman Ini...' : 'Auto Detect Seluruh Halaman Ini'"></span>
            </button>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer flex items-center gap-2">
                <i data-lucide="check-check" class="w-4 h-4"></i>
                <span>Simpan Perubahan Rating</span>
            </button>
        </div>
    </form>

</div>

<script>
    function bulkRatingEditor() {
        return {
            isDetectingAll: false,
            async detectSingle(filmId, rowData) {
                if (rowData) rowData.loading = true;
                const select = document.getElementById('rating-select-' + filmId);
                
                try {
                    const res = await fetch(`/admin/films/${filmId}/auto-rate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.rating && select) {
                            select.value = data.rating;
                            select.classList.add('ring-2', 'ring-purple-500/60', 'border-purple-400', 'bg-purple-950/40');
                            setTimeout(() => {
                                select.classList.remove('ring-2', 'ring-purple-500/60', 'bg-purple-950/40');
                            }, 2500);
                        }
                    }
                } catch (e) {
                    console.error('Auto detect error for film ' + filmId, e);
                } finally {
                    if (rowData) rowData.loading = false;
                }
            },

            async autoDetectCurrentPage() {
                const selects = document.querySelectorAll('select[id^="rating-select-"]');
                if (selects.length === 0) return;

                this.isDetectingAll = true;

                for (const select of selects) {
                    const filmId = select.id.replace('rating-select-', '');
                    try {
                        const res = await fetch(`/admin/films/${filmId}/auto-rate`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Accept': 'application/json'
                            }
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.rating) {
                                select.value = data.rating;
                                select.classList.add('border-purple-400', 'bg-purple-950/40');
                            }
                        }
                    } catch (e) {
                        console.error('Auto detect error for film ' + filmId, e);
                    }
                }

                this.isDetectingAll = false;
            }
        }
    }
</script>
@endsection
