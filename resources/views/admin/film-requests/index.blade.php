@extends('layouts.admin')

@section('title', 'Manajemen Request Film')

@section('content')
<div x-data="{ 
    selectedIds: [], 
    rejectModalOpen: false, 
    userModalOpen: false,
    statusModalOpen: false,
    activeUserModalData: null,
    activeStatusModalData: null,
    singleRejectId: null, 
    rejectReason: '',
    openRejectModal(id = null, reason = 'Maaf, film yang kamu minta belum dapat tersedia di platform.') {
        this.singleRejectId = id;
        this.rejectReason = reason;
        this.rejectModalOpen = true;
    },
    openStatusModal(data) {
        this.activeStatusModalData = data;
        this.statusModalOpen = true;
    },
    openUserModal(data) {
        this.activeUserModalData = data;
        this.userModalOpen = true;
    },
    toggleAll(checked) {
        if (checked) {
            this.selectedIds = Array.from(document.querySelectorAll('.request-chk')).map(el => parseInt(el.value));
        } else {
            this.selectedIds = [];
        }
    }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white font-['Outfit'] tracking-tight flex items-center gap-2.5">
                <i data-lucide="inbox" class="w-6 h-6 text-amber-500"></i>
                <span>Manajemen Request Film</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Kelola daftar request film dari pengguna, pantau siapa saja pengirimnya, dan perbarui status ketersediaan.</p>
        </div>
    </div>

    <!-- Stat Cards Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
        <a href="{{ route('admin.film-requests.index', ['all' => 1]) }}" class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all hover:scale-[1.02] group {{ request('all') ? 'border-amber-500/40 bg-amber-500/5' : 'border-white/10' }}">
            <span class="text-zinc-400 font-semibold group-hover:text-white">Total Request</span>
            <span class="block font-extrabold text-white text-xl mt-1 font-['Outfit']">{{ number_format($stats['total'] ?? 0) }}</span>
        </a>
        <a href="{{ route('admin.film-requests.index', ['status' => 'pending']) }}" class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all hover:scale-[1.02] group {{ request('status') === 'pending' || (!request()->has('status') && !request()->has('all')) ? 'border-amber-500/40 bg-amber-500/10' : 'border-white/10' }}">
            <span class="text-zinc-400 font-semibold group-hover:text-amber-300">Pending</span>
            <span class="block font-extrabold text-amber-400 text-xl mt-1 font-['Outfit']">{{ number_format($stats['pending'] ?? 0) }}</span>
        </a>
        <a href="{{ route('admin.film-requests.index', ['status' => 'searching']) }}" class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all hover:scale-[1.02] group {{ request('status') === 'searching' ? 'border-sky-500/40 bg-sky-500/10' : 'border-white/10' }}">
            <span class="text-zinc-400 font-semibold group-hover:text-sky-300">Sedang Dicari</span>
            <span class="block font-extrabold text-sky-400 text-xl mt-1 font-['Outfit']">{{ number_format($stats['searching'] ?? 0) }}</span>
        </a>
        <a href="{{ route('admin.film-requests.index', ['status' => 'added']) }}" class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all hover:scale-[1.02] group {{ request('status') === 'added' ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-white/10' }}">
            <span class="text-zinc-400 font-semibold group-hover:text-emerald-300">Ditemukan</span>
            <span class="block font-extrabold text-emerald-400 text-xl mt-1 font-['Outfit']">{{ number_format($stats['added'] ?? 0) }}</span>
        </a>
        <a href="{{ route('admin.film-requests.index', ['status' => 'rejected']) }}" class="p-3.5 rounded-2xl bg-zinc-900/80 border transition-all hover:scale-[1.02] group {{ request('status') === 'rejected' ? 'border-rose-500/40 bg-rose-500/10' : 'border-white/10' }}">
            <span class="text-zinc-400 font-semibold group-hover:text-rose-300">Ditolak</span>
            <span class="block font-extrabold text-rose-400 text-xl mt-1 font-['Outfit']">{{ number_format($stats['rejected'] ?? 0) }}</span>
        </a>
    </div>

    <!-- Toolbar Filters & Bulk Actions -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.film-requests.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1 w-full">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500 shrink-0"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul request..." 
                       class="w-full bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0">
            </div>

            <div class="w-full sm:w-40">
                <x-custom-dropdown 
                    name="type" 
                    :value="request('type', '')" 
                    :options="[
                        '' => 'Semua Tipe',
                        'movie' => '🎬 Movie',
                        'tv' => '📺 TV / Series',
                        'dracin' => '🌸 Dracin',
                    ]" 
                    placeholder="Semua Tipe" 
                    :autoSubmit="true"
                />
            </div>

            <div class="w-full sm:w-56">
                <x-custom-dropdown 
                    name="status" 
                    :value="request('status', '')" 
                    :options="[
                        '' => 'Status: Active (Pending & Searching)',
                        'pending' => '⏳ Pending',
                        'searching' => '🔍 Sedang Dicari',
                        'added' => '✅ Ditemukan',
                        'rejected' => '❌ Ditolak',
                    ]" 
                    placeholder="Semua Status" 
                    :autoSubmit="true"
                />
            </div>

            <div class="w-full sm:w-56">
                <x-custom-dropdown 
                    name="sort" 
                    :value="request('sort', 'popularity')" 
                    :options="[
                        'popularity' => '🔥 Terpopuler (Banyak Permintaan)',
                        'latest' => '🕒 Terbaru',
                    ]" 
                    placeholder="Urutkan" 
                    :autoSubmit="true"
                />
            </div>
        </form>

        <button type="button" x-show="selectedIds.length > 0" @click="openRejectModal(null)" class="px-4 py-2 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold flex items-center gap-2 hover:bg-rose-500/30 transition-all cursor-pointer">
            <i data-lucide="x-circle" class="w-4 h-4"></i>
            <span>Tolak Terpilih (<span x-text="selectedIds.length"></span>)</span>
        </button>
    </div>

    <!-- Requests Table -->
    <div class="bg-zinc-900/80 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-300">
                <thead class="bg-zinc-950/80 text-zinc-400 font-bold uppercase tracking-wider text-[10px] border-b border-white/10">
                    <tr>
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded bg-zinc-900 border-white/20 text-amber-500 focus:ring-0 cursor-pointer">
                        </th>
                        <th class="p-4">Judul & Detail</th>
                        <th class="p-4">Tipe</th>
                        <th class="p-4">Pemohon / Pengirim</th>
                        <th class="p-4 text-center">Jumlah Permintaan</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-medium">
                    @forelse($filmRequests as $req)
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-4 text-center">
                                <input type="checkbox" value="{{ $req->id }}" x-model="selectedIds" class="request-chk rounded bg-zinc-900 border-white/20 text-amber-500 focus:ring-0 cursor-pointer">
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-white text-sm group-hover:text-amber-400 transition-colors">
                                    {{ $req->title }}
                                </div>
                                <div class="text-[11px] text-zinc-400 mt-0.5 flex items-center gap-2">
                                    @if($req->year)
                                        <span>Tahun {{ $req->year }}</span>
                                        <span>•</span>
                                    @endif
                                    <span>Dikirim {{ $req->created_at->diffForHumans() }}</span>
                                </div>
                                @if($req->matchedFilm)
                                    <div class="mt-2 text-[11px] text-emerald-400 flex items-center gap-1.5 font-semibold">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                        <span>Telah dihubungkan:</span>
                                        <a href="{{ route('film.show', $req->matchedFilm->slug) }}" target="_blank" class="underline hover:text-white">
                                            {{ $req->matchedFilm->title }}
                                        </a>
                                    </div>
                                @endif
                                @if($req->rejection_reason && $req->status === 'rejected')
                                    <div class="mt-1.5 text-[11px] text-rose-300/80 bg-rose-500/10 p-2 rounded-lg border border-rose-500/20 max-w-md">
                                        <strong>Alasan Ditolak:</strong> {{ $req->rejection_reason }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $req->type === 'dracin' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : ($req->type === 'tv' || $req->type === 'series' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30') }}">
                                    {{ $req->type }}
                                </span>
                            </td>

                            <!-- Pemohon / Sender Column -->
                            <td class="p-4">
                                @if($req->users->isNotEmpty())
                                    @php $firstUser = $req->users->first(); @endphp
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ $firstUser->avatar_url }}" alt="{{ $firstUser->name }}" 
                                             class="w-7 h-7 rounded-full object-cover border border-white/15 shrink-0 bg-zinc-950" 
                                             onerror="this.onerror=null; this.src='https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode($firstUser->name) }}';">
                                        <div class="min-w-0">
                                            <div class="font-bold text-white text-xs truncate max-w-[140px]" title="{{ $firstUser->name }}">
                                                {{ $firstUser->name }}
                                            </div>
                                            <div class="text-[10px] text-zinc-400 truncate max-w-[140px]" title="{{ $firstUser->email }}">
                                                {{ $firstUser->email }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($req->users->count() > 1)
                                        @php
                                            $usersPayload = [
                                                'title' => $req->title,
                                                'users' => $req->users->map(function($u) use ($req) {
                                                    return [
                                                        'name' => $u->name,
                                                        'email' => $u->email,
                                                        'avatar' => $u->avatar_url,
                                                        'requested_at' => $u->pivot && $u->pivot->created_at ? $u->pivot->created_at->diffForHumans() : $req->created_at->diffForHumans(),
                                                    ];
                                                })->values()
                                            ];
                                        @endphp
                                        <button type="button" @click='openUserModal(@json($usersPayload))' 
                                                class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-bold text-amber-400 hover:text-amber-300 transition-colors cursor-pointer bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                                            <i data-lucide="users" class="w-3 h-3"></i>
                                            <span>+{{ $req->users->count() - 1 }} pemohon lainnya</span>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-zinc-500 text-[11px] italic">Tamu / Anonim</span>
                                @endif
                            </td>

                            <!-- Jumlah Pemohon -->
                            <td class="p-4 text-center">
                                <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 font-extrabold font-mono text-xs border border-amber-500/30">
                                    🔥 {{ $req->request_count }}x
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="p-4">
                                @if($req->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1.5 w-fit">
                                        <i data-lucide="clock" class="w-3 h-3 text-amber-400"></i>
                                        <span>Pending</span>
                                    </span>
                                @elseif($req->status === 'searching')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-sky-500/20 text-sky-300 border border-sky-500/30 flex items-center gap-1.5 w-fit">
                                        <i data-lucide="loader-2" class="w-3 h-3 animate-spin text-sky-400"></i>
                                        <span>Sedang Dicari</span>
                                    </span>
                                @elseif($req->status === 'added')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1.5 w-fit">
                                        <i data-lucide="check-circle" class="w-3 h-3 text-emerald-400"></i>
                                        <span>Ditemukan</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1.5 w-fit">
                                        <i data-lucide="x-circle" class="w-3 h-3 text-rose-400"></i>
                                        <span>Ditolak</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi Column -->
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit Status Button -->
                                    <button type="button" 
                                            @click='openStatusModal({ id: {{ $req->id }}, title: @json($req->title), status: @json($req->status), rejection_reason: @json($req->rejection_reason) })' 
                                            class="px-3 py-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-white/10 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer" 
                                            title="Ubah Status">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        <span>Ubah</span>
                                    </button>

                                    @if($req->status !== 'rejected')
                                        <!-- Reject Button -->
                                        <button type="button" @click="openRejectModal({{ $req->id }})" class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/20 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer" title="Tolak Request Ini">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                            <span>Tolak</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-zinc-500">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-zinc-600"></i>
                                <p class="text-xs">Tidak ada request film yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($filmRequests->hasPages())
            <div class="p-4 border-t border-white/10 bg-zinc-950/50">
                {{ $filmRequests->links() }}
            </div>
        @endif
    </div>

    <!-- Reject Reason Modal -->
    <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div @click.away="rejectModalOpen = false" class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-2xl p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-4 h-4 text-rose-400"></i>
                    <span x-text="singleRejectId ? 'Tolak Request Film' : 'Tolak Request Terpilih Massal'"></span>
                </h3>
                <button type="button" @click="rejectModalOpen = false" class="text-zinc-500 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Single Reject Form -->
            <template x-if="singleRejectId">
                <form :action="'{{ url('/admin/film-requests') }}/' + singleRejectId + '/reject'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1.5">Alasan Penolakan (Akan dikirim ke Notifikasi Pemohon)</label>
                        <textarea name="rejection_reason" x-model="rejectReason" rows="3" required class="w-full bg-zinc-950 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-rose-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300 hover:bg-zinc-700">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-xs font-bold text-white">Konfirmasi Tolak</button>
                    </div>
                </form>
            </template>

            <!-- Bulk Reject Form -->
            <template x-if="!singleRejectId">
                <form action="{{ route('admin.film-requests.bulk_reject') }}" method="POST" class="space-y-4">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1.5">Alasan Penolakan Massal</label>
                        <textarea name="rejection_reason" x-model="rejectReason" rows="3" required class="w-full bg-zinc-950 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-rose-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300 hover:bg-zinc-700">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-xs font-bold text-white">Tolak Massal (<span x-text="selectedIds.length"></span>)</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- All Senders / Users List Modal -->
    <div x-show="userModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div @click.away="userModalOpen = false" class="w-full max-w-lg bg-zinc-900 border border-white/10 rounded-2xl p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-amber-400"></i>
                        <span>Daftar Pemohon Film</span>
                    </h3>
                    <p class="text-xs text-zinc-400 mt-0.5" x-text="activeUserModalData ? activeUserModalData.title : ''"></p>
                </div>
                <button type="button" @click="userModalOpen = false" class="text-zinc-500 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- List of Requesters -->
            <div class="max-h-72 overflow-y-auto space-y-2 pr-1 scrollbar-thin">
                <template x-if="activeUserModalData && activeUserModalData.users">
                    <template x-for="(u, idx) in activeUserModalData.users" :key="idx">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-950/60 border border-white/5">
                            <div class="flex items-center gap-3">
                                <img :src="u.avatar" :alt="u.name" class="w-8 h-8 rounded-full object-cover border border-white/15 bg-zinc-900">
                                <div>
                                    <div class="text-xs font-bold text-white" x-text="u.name"></div>
                                    <div class="text-[11px] text-zinc-400" x-text="u.email"></div>
                                </div>
                            </div>
                            <span class="text-[10px] text-zinc-500 font-medium" x-text="u.requested_at"></span>
                        </div>
                    </template>
                </template>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" @click="userModalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300 hover:bg-zinc-700">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div x-show="statusModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div @click.away="statusModalOpen = false" class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-2xl p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-4 h-4 text-amber-400"></i>
                    <span>Ubah Status Permintaan Film</span>
                </h3>
                <button type="button" @click="statusModalOpen = false" class="text-zinc-500 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <template x-if="activeStatusModalData">
                <form :action="'{{ url('/admin/film-requests') }}/' + activeStatusModalData.id + '/status'" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1.5">Judul Film</label>
                        <div class="px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-white/10 text-xs font-bold text-white" x-text="activeStatusModalData.title"></div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1.5">Pilih Status Baru</label>
                        <select name="status" x-model="activeStatusModalData.status" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                            <option value="pending">⏳ Pending</option>
                            <option value="searching">🔍 Sedang Dicari</option>
                            <option value="added">✅ Ditemukan / Selesai</option>
                            <option value="rejected">❌ Ditolak</option>
                        </select>
                    </div>

                    <div x-show="activeStatusModalData.status === 'rejected'">
                        <label class="block text-xs font-semibold text-zinc-300 mb-1.5">Alasan Penolakan</label>
                        <textarea name="rejection_reason" x-model="activeStatusModalData.rejection_reason" rows="3" placeholder="Tuliskan alasan penolakan..." class="w-full bg-zinc-950 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-rose-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="statusModalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300 hover:bg-zinc-700">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 text-xs font-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
