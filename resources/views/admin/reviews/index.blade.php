@extends('layouts.admin')

@section('title', 'Moderasi Ulasan | faiiladmin')
@section('page_title', 'Moderasi Ulasan Pengguna')

@section('content')
<div class="space-y-6">
    
    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari isi ulasan, user, atau film..." 
                       class="w-full min-w-0 bg-transparent py-2 text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <select name="filter" onchange="this.form.submit()" class="bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                <option value="latest" {{ request('filter') === 'latest' ? 'selected' : '' }}>Terbaru</option>
                <option value="reported" {{ request('filter') === 'reported' ? 'selected' : '' }}>Yang Di-Report User</option>
                <option value="lowest_rating" {{ request('filter') === 'lowest_rating' ? 'selected' : '' }}>Rating Terendah</option>
            </select>
        </form>
    </div>

    <!-- Reviews Table -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">User</th>
                        <th class="px-4 py-3.5">Film</th>
                        <th class="px-4 py-3.5">Rating</th>
                        <th class="px-4 py-3.5">Komentar</th>
                        <th class="px-4 py-3.5">Laporan</th>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($reviews as $rev)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 font-semibold text-white">
                                {{ $rev->user->name ?? 'User Terhapus' }}
                            </td>
                            <td class="px-4 py-3.5 font-bold text-amber-400">
                                {{ $rev->film->title ?? 'Film Terhapus' }}
                            </td>
                            <td class="px-4 py-3.5 font-bold">
                                <span class="flex items-center gap-1 text-amber-400">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400"></i>
                                    {{ $rev->rating }}/10
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-zinc-300 max-w-xs">
                                <p class="line-clamp-2">{{ $rev->comment }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($rev->reports_count > 0)
                                    <span class="px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 text-[10px] font-extrabold flex items-center gap-1 w-max">
                                        <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                        {{ $rev->reports_count }} Report
                                    </span>
                                @else
                                    <span class="text-zinc-500 text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-400 text-[11px]">
                                {{ $rev->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2" x-data="{ modalOpen: false }">
                                    @if($rev->reports_count > 0)
                                        <form action="{{ route('admin.reviews.dismiss_reports', $rev->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700" title="Abaikan Laporan">
                                                <i data-lucide="check-check" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <button @click="modalOpen = true" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus Review">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Delete Confirmation Modal with Reason Input -->
                                    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                                        <div @click.away="modalOpen = false" class="w-full max-w-md p-6 rounded-2xl bg-zinc-900 border border-white/10 text-left space-y-4 shadow-2xl">
                                            <div class="flex items-center gap-3 text-red-400">
                                                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                                                <h4 class="font-bold text-white text-base">Hapus Ulasan Ini?</h4>
                                            </div>
                                            <p class="text-xs text-zinc-400">Ulasan oleh <strong class="text-white">{{ $rev->user->name ?? 'User' }}</strong> akan dihapus secara permanen dari film <strong class="text-white">{{ $rev->film->title ?? 'Film' }}</strong>.</p>

                                            <form action="{{ route('admin.reviews.destroy', $rev->id) }}" method="POST" class="space-y-4">
                                                @csrf
                                                @method('DELETE')

                                                <div>
                                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Alasan Penghapusan *</label>
                                                    <input type="text" name="reason" required placeholder="Contoh: Berisi kata-kata kasar / spam" 
                                                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                                                </div>

                                                <div class="flex justify-end gap-2 pt-2">
                                                    <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300">Batal</button>
                                                    <button type="submit" class="px-4 py-2 rounded-xl bg-red-500 text-xs font-bold text-white shadow-lg shadow-red-500/20">Hapus Ulasan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">Tidak ada ulasan ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
