@extends('layouts.admin')

@section('title', 'Moderasi Ulasan | faiiladmin')
@section('page_title', 'Moderasi & Pengawasan Ulasan Pengguna')

@section('content')
<div class="space-y-6">
    
    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[220px]">
                <i data-lucide="search" class="w-4 h-4 shrink-0 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari isi ulasan, user, atau film..." 
                       class="w-full min-w-0 bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <div class="w-48">
                <x-custom-dropdown 
                    name="filter" 
                    :value="request('filter', 'latest')" 
                    :options="[
                        'latest' => 'Terbaru',
                        'reported' => 'Di-Report User',
                        'lowest_rating' => 'Rating Terendah',
                    ]" 
                    placeholder="Filter Ulasan" 
                    :autoSubmit="true"
                />
            </div>

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
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full overflow-hidden bg-zinc-800 shrink-0 border border-zinc-700">
                                        <img src="{{ $rev->user?->avatar_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($rev->user?->name ?? 'User') }}" alt="{{ $rev->user?->name ?? 'User' }}" class="w-full h-full object-cover">
                                    </div>
                                    <span class="truncate max-w-[120px]">{{ $rev->user->name ?? 'User Terhapus' }}</span>
                                </div>
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
                            <td class="px-4 py-3.5 text-zinc-300 max-w-xs" x-data="{ viewModal: false }">
                                <div class="cursor-pointer group/cmt" @click="viewModal = true">
                                    <p class="line-clamp-2 text-xs leading-relaxed group-hover/cmt:text-white transition-colors">{{ $rev->comment }}</p>
                                    @if(mb_strlen($rev->comment) > 60)
                                        <span class="text-[10px] text-rose-400 font-semibold inline-flex items-center gap-0.5 mt-0.5">
                                            <span>Baca selengkapnya</span>
                                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                        </span>
                                    @endif
                                </div>

                                <!-- Quick View Review Modal -->
                                <div x-show="viewModal" x-cloak 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
                                    <div @click.away="viewModal = false" class="w-full max-w-lg p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl text-white">
                                        <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full overflow-hidden bg-zinc-800 border border-zinc-700 shrink-0">
                                                    <img src="{{ $rev->user?->avatar_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($rev->user?->name ?? 'User') }}" alt="{{ $rev->user?->name ?? 'User' }}" class="w-full h-full object-cover">
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-white text-sm font-['Outfit']">{{ $rev->film->title ?? 'Film' }}</h4>
                                                    <p class="text-xs text-zinc-400">Oleh <strong class="text-zinc-200">{{ $rev->user->name ?? 'User' }}</strong> &bull; Rating: {{ $rev->rating }}/10</p>
                                                </div>
                                            </div>
                                            <button @click="viewModal = false" class="p-1 rounded-lg text-zinc-400 hover:text-white">
                                                <i data-lucide="x" class="w-5 h-5"></i>
                                            </button>
                                        </div>

                                        <div class="p-4 rounded-2xl bg-zinc-950/80 border border-zinc-800 text-xs text-zinc-200 leading-relaxed max-h-60 overflow-y-auto whitespace-pre-wrap">
                                            {{ $rev->comment }}
                                        </div>

                                        <div class="flex items-center justify-between text-[11px] text-zinc-500 pt-2 border-t border-zinc-800">
                                            <span>Ditulis pada {{ $rev->created_at->format('d M Y H:i:s') }}</span>
                                            <button @click="viewModal = false" class="px-4 py-2 rounded-xl bg-white text-black font-bold text-xs">Tutup</button>
                                        </div>
                                    </div>
                                </div>
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
