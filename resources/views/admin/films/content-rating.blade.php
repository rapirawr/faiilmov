@extends('layouts.admin')

@section('title', 'Rating Usia Massal | faiiladmin')
@section('page_title', 'Pengatur Rating Usia Massal')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-white font-['Outfit']">Bulk Content Rating Editor</h2>
            <p class="text-xs text-zinc-400">Atur kualifikasi usia (Parental Rating) untuk banyak film sekaligus secara efisien.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 font-mono font-bold text-xs">
                Belum Dikategorikan: {{ $unratedCount }} Film
            </span>

            <form action="{{ route('admin.films.auto_rate_all') }}" method="POST" onsubmit="return confirm('Deteksi dan tetapkan rating usia secara otomatis berbasis AI/Sinopsis & Genre?')">
                @csrf
                <input type="hidden" name="only_unrated" value="1">
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="wand-2" class="w-4 h-4"></i>
                    <span>Auto-Rate Semua (Unrated)</span>
                </button>
            </form>

            <a href="{{ route('admin.films.index') }}" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-2 border border-white/10 transition-all">
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
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                        <tr>
                            <th class="px-4 py-3.5">Film</th>
                            <th class="px-4 py-3.5">Tipe</th>
                            <th class="px-4 py-3.5">Tahun</th>
                            <th class="px-4 py-3.5">Content Rating Saat Ini</th>
                            <th class="px-4 py-3.5">Ubah Rating Usia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($films as $film)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 flex items-center gap-3">
                                    <img src="{{ $film->poster_url }}" class="w-8 h-11 object-cover rounded shrink-0">
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
                                    <select name="ratings[{{ $film->id }}]" class="bg-zinc-950 border border-white/15 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                        <option value="" {{ is_null($film->content_rating) ? 'selected' : '' }}>-- Pilihh Rating --</option>
                                        <option value="SU" {{ $film->content_rating === 'SU' ? 'selected' : '' }}>SU (Semua Umur)</option>
                                        <option value="G" {{ $film->content_rating === 'G' ? 'selected' : '' }}>G (General)</option>
                                        <option value="PG" {{ $film->content_rating === 'PG' ? 'selected' : '' }}>PG (Parental Guidance)</option>
                                        <option value="13+" {{ $film->content_rating === '13+' ? 'selected' : '' }}>13+ (Remaja)</option>
                                        <option value="16+" {{ $film->content_rating === '16+' ? 'selected' : '' }}>16+ (Dewasa Muda)</option>
                                        <option value="18+" {{ $film->content_rating === '18+' ? 'selected' : '' }}>18+ (Dewasa)</option>
                                    </select>
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

        <div class="flex justify-end pt-2">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer flex items-center gap-2">
                <i data-lucide="check-check" class="w-4 h-4"></i>
                <span>Simpan Perubahan Rating</span>
            </button>
        </div>
    </form>

</div>
@endsection
