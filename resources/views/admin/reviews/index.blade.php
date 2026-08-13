@extends('layouts.admin')

@section('title', 'Moderasi Ulasan | faiiladmin')
@section('page_title', 'Moderasi & Pengawasan Ulasan Pengguna')

@section('content')
<div class="space-y-6">
    
    <!-- Flash Alerts System -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-400"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 text-rose-400"></i>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[220px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari isi ulasan, user, atau film..." 
                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <select name="filter" onchange="this.form.submit()" class="bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer">
                <option value="latest" {{ request('filter') === 'latest' ? 'selected' : '' }}>Terbaru</option>
                <option value="reported" {{ request('filter') === 'reported' ? 'selected' : '' }}>🚨 Di-Report User</option>
                <option value="lowest_rating" {{ request('filter') === 'lowest_rating' ? 'selected' : '' }}>Rating Terendah</option>
            </select>

            @if(request()->hasAny(['search', 'filter']))
                <a href="{{ route('admin.reviews.index') }}" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            @endif
        </form>

        <span class="text-xs font-mono text-zinc-400 bg-zinc-900 px-3 py-1.5 rounded-xl border border-zinc-800 self-start sm:self-auto">
            Total: <strong class="text-white">{{ number_format($reviews->total()) }}</strong> Ulasan
        </span>
    </div>

    <!-- Reviews Table -->
    <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider">
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
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($reviews as $rev)
                        <tr class="hover:bg-zinc-800/40 transition-colors group">
                            <td class="px-4 py-3.5 font-bold text-white text-xs">
                                {{ $rev->user->name ?? 'User Terhapus' }}
                            </td>
                            <td class="px-4 py-3.5 font-bold text-amber-400 text-xs">
                                {{ $rev->film->title ?? 'Film Terhapus' }}
                            </td>
                            <td class="px-4 py-3.5 font-bold">
                                <span class="flex items-center gap-1 text-amber-400 font-mono text-xs">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400"></i>
                                    {{ $rev->rating }}/10
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-zinc-300 max-w-xs">
                                <p class="line-clamp-2 text-xs leading-relaxed">{{ $rev->comment }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($rev->reports_count > 0)
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 text-[10px] font-extrabold flex items-center gap-1 w-max">
                                        <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                        {{ $rev->reports_count }} Report
                                    </span>
                                @else
                                    <span class="text-zinc-500 text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-400 text-[11px] font-mono">
                                {{ $rev->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5" x-data="{ modalOpen: false }">
                                    @if($rev->reports_count > 0)
                                        <form action="{{ route('admin.reviews.dismiss_reports', $rev->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors" title="Abaikan Laporan">
                                                <i data-lucide="check-check" class="w-4 h-4 text-emerald-400"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <button type="button" @click="modalOpen = true" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Hapus Review">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Delete Confirmation Modal -->
                                    <div x-show="modalOpen" x-cloak 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                        <div @click.away="modalOpen = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
                                            <div class="flex items-center gap-3 text-rose-400 border-b border-zinc-800 pb-3">
                                                <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
                                                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                                                </div>
                                                <h4 class="font-bold text-white text-sm font-['Outfit']">Hapus Ulasan Ini?</h4>
                                            </div>

                                            <p class="text-xs text-zinc-300">Ulasan oleh <strong class="text-white">{{ $rev->user->name ?? 'User' }}</strong> akan dihapus permanen dari film <strong class="text-white">{{ $rev->film->title ?? 'Film' }}</strong>.</p>

                                            <form action="{{ route('admin.reviews.destroy', $rev->id) }}" method="POST" class="space-y-4">
                                                @csrf
                                                @method('DELETE')

                                                <div>
                                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Alasan Penghapusan *</label>
                                                    <input type="text" name="reason" required placeholder="Contoh: Berisi kata-kata kasar / spam" 
                                                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                                </div>

                                                <div class="flex justify-end gap-2.5 pt-2 border-t border-zinc-800">
                                                    <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">Batal</button>
                                                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition-all cursor-pointer">Hapus Ulasan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-zinc-500">Tidak ada ulasan ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
