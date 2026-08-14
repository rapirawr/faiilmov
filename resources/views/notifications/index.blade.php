@extends('layouts.app')

@section('title', 'Notifikasi Saya | faiilmov')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-6"
     x-data="{ 
         filter: 'all',
         async markRead(id, url) {
             try {
                 await fetch('/notifications/' + id + '/read', {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json'
                     }
                 });
             } catch(e) {}
             if (url) window.location.href = url;
         },
         async markAllRead() {
             try {
                 let res = await fetch('{{ route('notifications.read-all') }}', {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json'
                     }
                 });
                 if (res.ok) {
                     window.location.reload();
                 }
             } catch(e) {}
         }
     }">

    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-6">
        <div>
            <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white flex items-center gap-3">
                <i data-lucide="bell" class="w-7 h-7 text-amber-400"></i>
                <span>Notifikasi</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-1">Pembaruan film terbaru, balasan ulasan, dan info akun Anda</p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="markAllRead()" 
                    class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-zinc-200 hover:text-white transition-all flex items-center gap-2 cursor-pointer shadow-sm">
                <i data-lucide="check-check" class="w-4 h-4 text-emerald-400"></i>
                <span>Tandai Semua Dibaca</span>
            </button>
        </div>
    </div>

    <!-- Notification Filter Tabs -->
    <div class="flex items-center gap-2">
        <button @click="filter = 'all'" 
                :class="filter === 'all' ? 'bg-amber-500 text-black font-bold' : 'bg-white/5 text-zinc-400 hover:text-white'"
                class="px-4 py-1.5 rounded-full text-xs transition-all cursor-pointer">
            Semua ({{ $notifications->total() }})
        </button>
        <button @click="filter = 'unread'" 
                :class="filter === 'unread' ? 'bg-amber-500 text-black font-bold' : 'bg-white/5 text-zinc-400 hover:text-white'"
                class="px-4 py-1.5 rounded-full text-xs transition-all cursor-pointer">
            Belum Dibaca
        </button>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
        @forelse($notifications as $notif)
            <div x-show="filter === 'all' || (filter === 'unread' && !{{ $notif->is_read ? 'true' : 'false' }})"
                 @click="markRead({{ $notif->id }}, '{{ $notif->url ?? '' }}')"
                 class="glass-panel p-4 rounded-2xl border transition-all cursor-pointer group flex items-start gap-4 {{ !$notif->is_read ? 'border-amber-500/30 bg-amber-500/5 hover:bg-amber-500/10' : 'border-white/10 hover:border-white/20 bg-zinc-900/60' }}">
                
                <!-- Type Icon -->
                <div class="w-10 h-10 rounded-2xl shrink-0 flex items-center justify-center shadow-md {{ $notif->type === 'new_film' ? 'bg-amber-500/20 border border-amber-500/30 text-amber-400' : ($notif->type === 'review_reply' ? 'bg-sky-500/20 border border-sky-500/30 text-sky-400' : ($notif->type === 'watch_party' ? 'bg-emerald-500/20 border border-emerald-500/30 text-emerald-400' : ($notif->type === 'maintenance' ? 'bg-rose-500/20 border border-rose-500/30 text-rose-400' : 'bg-purple-500/20 border border-purple-500/30 text-purple-400'))) }}">
                    @if($notif->type === 'new_film')
                        <i data-lucide="film" class="w-5 h-5"></i>
                    @elseif($notif->type === 'review_reply')
                        <i data-lucide="message-square" class="w-5 h-5"></i>
                    @elseif($notif->type === 'watch_party')
                        <i data-lucide="tv" class="w-5 h-5"></i>
                    @elseif($notif->type === 'maintenance')
                        <i data-lucide="wrench" class="w-5 h-5"></i>
                    @elseif($notif->type === 'promotion')
                        <i data-lucide="gift" class="w-5 h-5"></i>
                    @else
                        <i data-lucide="bell" class="w-5 h-5"></i>
                    @endif
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        @if($notif->type === 'new_film')
                            <span class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 text-[10px] font-extrabold uppercase border border-amber-500/30">Film Baru</span>
                        @elseif($notif->type === 'review_reply')
                            <span class="px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-300 text-[10px] font-extrabold uppercase border border-sky-500/30">Balasan Ulasan</span>
                        @elseif($notif->type === 'watch_party')
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold uppercase border border-emerald-500/30">Watch Party</span>
                        @elseif($notif->type === 'maintenance')
                            <span class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-300 text-[10px] font-extrabold uppercase border border-rose-500/30">Maintenance</span>
                        @elseif($notif->type === 'promotion')
                            <span class="px-2 py-0.5 rounded-md bg-pink-500/20 text-pink-300 text-[10px] font-extrabold uppercase border border-pink-500/30">Promo & Event</span>
                        @elseif($notif->type === 'announcement')
                            <span class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-300 text-[10px] font-extrabold uppercase border border-purple-500/30">Pengumuman</span>
                        @else
                            <span class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-300 text-[10px] font-extrabold uppercase border border-purple-500/30">Informasi</span>
                        @endif

                        @if(!$notif->is_read)
                            <span class="px-2 py-0.5 rounded-md bg-red-500/20 text-red-400 text-[10px] font-bold border border-red-500/30 animate-pulse">Baru</span>
                        @endif

                        <span class="text-[11px] text-zinc-500 ml-auto">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>

                    <p class="text-xs sm:text-sm text-zinc-200 group-hover:text-white transition-colors {{ !$notif->is_read ? 'font-semibold' : '' }}">
                        {{ $notif->message }}
                    </p>
                </div>

                @if($notif->url)
                    <div class="self-center p-2 rounded-xl text-zinc-500 group-hover:text-amber-400 group-hover:bg-white/5 transition-all shrink-0">
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </div>
                @endif
            </div>
        @empty
            <div class="glass-panel p-12 rounded-3xl border border-white/10 text-center space-y-3">
                <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 flex items-center justify-center mx-auto text-zinc-500">
                    <i data-lucide="bell-off" class="w-8 h-8"></i>
                </div>
                <h3 class="font-bold text-white text-base">Belum Ada Notifikasi</h3>
                <p class="text-xs text-zinc-400 max-w-sm mx-auto">Anda belum menerima notifikasi apapun saat ini. Setiap rilis film baru atau ulasan balasan akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="pt-4">
            {{ $notifications->links() }}
        </div>
    @endif

</div>
@endsection
