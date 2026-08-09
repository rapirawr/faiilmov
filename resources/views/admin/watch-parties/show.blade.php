@extends('layouts.admin')

@section('title', 'Detail Nobar #' . $watchParty->room_code . ' | faiiladmin')
@section('page_title', 'Detail Watch Party #' . $watchParty->room_code)

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="px-3.5 py-1.5 rounded-xl bg-white/10 border border-white/10 text-amber-400 font-mono font-extrabold text-base">
                #{{ $watchParty->room_code }}
            </span>
            <div>
                <h2 class="text-lg font-bold text-white font-['Outfit']">{{ $watchParty->film->title ?? 'Film Dihapus' }}</h2>
                <p class="text-xs text-zinc-400">Host: <strong class="text-zinc-200">{{ $watchParty->hostUser->name ?? $watchParty->host_guest_name }}</strong> • Dibuat {{ $watchParty->created_at->diffForHumans() }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($watchParty->status === 'active')
                <form action="{{ route('admin.watch_parties.force_close', $watchParty->id) }}" method="POST" onsubmit="return confirm('TUTUP PAKSA ruang Nobar ini?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 font-bold text-xs border border-rose-500/30 transition-all flex items-center gap-2 cursor-pointer">
                        <i data-lucide="power" class="w-4 h-4"></i>
                        <span>Tutup Paksa Ruangan</span>
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.watch_parties.index') }}" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-2 border border-white/10 transition-all">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- 2 Column Layout: Participants & Broadcast Chat -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column: Room Details & Active Participants -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Room Info Tile -->
            <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-3">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-amber-400"></i>
                    <span>Informasi Pemutaran</span>
                </h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-white/5">
                        <span class="text-zinc-400">Status Pemutaran:</span>
                        <span class="font-bold text-white font-mono">{{ $watchParty->is_playing ? '▶ Playing' : '⏸ Paused' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-white/5">
                        <span class="text-zinc-400">Posisi Detik:</span>
                        <span class="font-bold text-amber-400 font-mono">{{ gmdate("H:i:s", $watchParty->current_position_seconds) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-white/5">
                        <span class="text-zinc-400">Status Room:</span>
                        <span class="font-bold uppercase {{ $watchParty->status === 'active' ? 'text-emerald-400' : 'text-zinc-500' }}">{{ $watchParty->status }}</span>
                    </div>
                </div>
            </div>

            <!-- Active Participants Tile -->
            <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-3">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-sky-400"></i>
                        <span>Daftar Peserta Aktif</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-mono text-[10px]">{{ $watchParty->participants->count() }}</span>
                </h3>

                <div class="space-y-2 max-h-60 overflow-y-auto">
                    @forelse($watchParty->participants as $p)
                        <div class="p-2.5 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/30 flex items-center justify-center font-bold text-[10px]">
                                    {{ strtoupper(substr($p->display_name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-white text-xs">{{ $p->display_name }}</p>
                                    <p class="text-[10px] text-zinc-500">Gabung {{ $p->joined_at ? \Carbon\Carbon::parse($p->joined_at)->diffForHumans() : 'Baru' }}</p>
                                </div>
                            </div>
                            @if($p->is_host)
                                <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-extrabold text-[9px] uppercase">Host</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-zinc-500 text-center py-4">Belum ada peserta dalam ruangan.</p>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right Column: Live Chat Log & Broadcast Announcement -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Chat Log Tile -->
            <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10 space-y-4 flex flex-col h-[500px]">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center justify-between shrink-0">
                    <span class="flex items-center gap-2">
                        <i data-lucide="message-square" class="w-4 h-4 text-emerald-400"></i>
                        <span>Log Obrolan Nobar (Terbaru)</span>
                    </span>
                    <span class="text-[10px] text-zinc-500">{{ count($messages) }} Pesan</span>
                </h3>

                <!-- Message History Box -->
                <div class="flex-1 overflow-y-auto space-y-2.5 p-3 rounded-xl bg-zinc-950 border border-white/10">
                    @forelse($messages as $msg)
                        <div class="p-2.5 rounded-xl border text-xs space-y-1 {{ $msg->is_system ? 'bg-amber-500/10 border-amber-500/20 text-amber-300' : 'bg-white/5 border-white/5 text-zinc-200' }}">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="font-bold {{ $msg->is_system ? 'text-amber-400' : 'text-sky-400' }}">{{ $msg->sender_name }}</span>
                                <span class="text-zinc-500">{{ $msg->created_at->format('H:i:s') }}</span>
                            </div>
                            <p class="text-xs leading-relaxed">{{ $msg->message }}</p>
                        </div>
                    @empty
                        <div class="h-full flex items-center justify-center text-zinc-500 text-xs">
                            Belum ada pesan obrolan di ruangan ini.
                        </div>
                    @endforelse
                </div>

                <!-- Broadcast Announcement Input Form -->
                @if($watchParty->status === 'active')
                    <form action="{{ route('admin.watch_parties.send_message', $watchParty->id) }}" method="POST" class="flex gap-2 shrink-0">
                        @csrf
                        <input type="text" name="message" required placeholder="Kirim pengumuman admin ke obrolan ruangan..." 
                               class="flex-1 bg-zinc-950 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer shadow-lg shadow-amber-500/20">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            <span>Kirim</span>
                        </button>
                    </form>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
