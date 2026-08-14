@extends('layouts.admin')

@section('title', 'Broadcast Push Notifikasi | faiiladmin')
@section('page_title', 'Broadcast Push Notifikasi Pengguna')

@section('content')
<div class="space-y-8" x-data="notificationManager()">

    <!-- Stat Cards Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Registered Users -->
        <div class="p-5 rounded-3xl bg-zinc-900/90 border border-white/10 flex items-center justify-between shadow-xl">
            <div class="space-y-1">
                <p class="text-[10px] font-extrabold text-zinc-400 uppercase tracking-widest">Target Pengguna</p>
                <h3 class="text-3xl font-black text-white font-['Outfit']">{{ number_format($totalUsers) }}</h3>
                <p class="text-[11px] text-zinc-400 font-mono">Total Akun Terdaftar</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 border border-zinc-700 text-white flex items-center justify-center shrink-0 shadow-md">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Total Notifications Sent -->
        <div class="p-5 rounded-3xl bg-zinc-900/90 border border-white/10 flex items-center justify-between shadow-xl">
            <div class="space-y-1">
                <p class="text-[10px] font-extrabold text-zinc-400 uppercase tracking-widest">Pesan Terkirim</p>
                <h3 class="text-3xl font-black text-white font-['Outfit']">{{ number_format($totalNotifications) }}</h3>
                <p class="text-[11px] text-zinc-400 font-mono">Akumulasi Notifikasi</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 border border-zinc-700 text-white flex items-center justify-center shrink-0 shadow-md">
                <i data-lucide="send" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Unread Notifications -->
        <div class="p-5 rounded-3xl bg-zinc-900/90 border border-white/10 flex items-center justify-between shadow-xl">
            <div class="space-y-1">
                <p class="text-[10px] font-extrabold text-zinc-400 uppercase tracking-widest">Belum Dibaca</p>
                <h3 class="text-3xl font-black text-white font-['Outfit']">{{ number_format($unreadNotifications) }}</h3>
                <p class="text-[11px] text-zinc-400 font-mono">Di Kotak Masuk User</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 border border-zinc-700 text-white flex items-center justify-center shrink-0 shadow-md">
                <i data-lucide="mail" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Total Broadcast Sessions -->
        <div class="p-5 rounded-3xl bg-zinc-900/90 border border-white/10 flex items-center justify-between shadow-xl">
            <div class="space-y-1">
                <p class="text-[10px] font-extrabold text-zinc-400 uppercase tracking-widest">Total Siaran</p>
                <h3 class="text-3xl font-black text-white font-['Outfit']">{{ number_format($broadcasts->total()) }}</h3>
                <p class="text-[11px] text-zinc-400 font-mono">Sesi Broadcast Selesai</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-zinc-800 border border-zinc-700 text-white flex items-center justify-center shrink-0 shadow-md">
                <i data-lucide="radio" class="w-5 h-5"></i>
            </div>
        </div>

    </div>

    <!-- Main Workspace Grid (Compose & History) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Column (5 Cols): Broadcast Composer Form & AI Writer -->
        <div class="lg:col-span-5 space-y-6">
            
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-5">
                
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-zinc-800 border border-zinc-700 flex items-center justify-center text-white shrink-0">
                            <i data-lucide="send" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-['Outfit']">Kirim Broadcast Baru</h3>
                            <p class="text-[11px] text-zinc-400">Siarkan pesan instan ke seluruh pengguna aplikasi.</p>
                        </div>
                    </div>
                </div>

                <!-- AI Copywriter Card -->
                <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-3 shadow-inner">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-1 rounded-lg bg-zinc-800 text-zinc-200 border border-zinc-700">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <span class="text-xs font-extrabold text-white font-['Outfit']">AI Notification Writer</span>
                                <p class="text-[10px] text-zinc-400">Suruh AI menyusun judul, teks, dan URL secara otomatis</p>
                            </div>
                        </div>
                        <span class="text-[9px] font-mono font-bold px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-300 border border-zinc-700">AI Powered</span>
                    </div>

                    <!-- AI Prompt Input -->
                    <div class="relative">
                        <textarea x-model="aiPrompt" 
                                  @keydown.ctrl.enter.prevent="generateWithAi()"
                                  rows="2" 
                                  placeholder="Contoh: Buatkan notifikasi rilis film Deadpool & Wolverine dengan gaya antusias..." 
                                  class="w-full bg-zinc-900 border border-zinc-800 rounded-xl p-3 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 resize-none font-medium leading-relaxed"></textarea>
                    </div>

                    <!-- Tone Selector & Generate Button -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                        <div class="flex items-center gap-1 overflow-x-auto py-0.5">
                            <button type="button" @click="aiTone = 'enthusiastic'" 
                                    :class="aiTone === 'enthusiastic' ? 'bg-white text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                                    class="px-2 py-0.5 rounded-lg text-[10px] transition-colors cursor-pointer">
                                Antusias
                            </button>
                            <button type="button" @click="aiTone = 'formal'" 
                                    :class="aiTone === 'formal' ? 'bg-white text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                                    class="px-2 py-0.5 rounded-lg text-[10px] transition-colors cursor-pointer">
                                Formal
                            </button>
                            <button type="button" @click="aiTone = 'urgent'" 
                                    :class="aiTone === 'urgent' ? 'bg-white text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                                    class="px-2 py-0.5 rounded-lg text-[10px] transition-colors cursor-pointer">
                                Mendesak
                            </button>
                            <button type="button" @click="aiTone = 'casual'" 
                                    :class="aiTone === 'casual' ? 'bg-white text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                                    class="px-2 py-0.5 rounded-lg text-[10px] transition-colors cursor-pointer">
                                Santai
                            </button>
                            <button type="button" @click="aiTone = 'promo'" 
                                    :class="aiTone === 'promo' ? 'bg-white text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                                    class="px-2 py-0.5 rounded-lg text-[10px] transition-colors cursor-pointer">
                                Promo
                            </button>
                        </div>

                        <button type="button" 
                                @click="generateWithAi()" 
                                :disabled="isGenerating || !aiPrompt.trim()" 
                                class="px-3.5 py-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 disabled:opacity-50 text-white font-bold text-xs border border-zinc-700 flex items-center gap-1.5 transition-all cursor-pointer shadow-sm">
                            <i data-lucide="loader-2" x-show="isGenerating" class="w-3.5 h-3.5 animate-spin"></i>
                            <i data-lucide="sparkles" x-show="!isGenerating" class="w-3.5 h-3.5"></i>
                            <span x-text="isGenerating ? 'Menulis...' : 'Generate AI'"></span>
                        </button>
                    </div>

                    <!-- AI Status Message -->
                    <div x-show="aiStatusMessage" class="flex items-center gap-1.5 text-[11px] text-zinc-300 font-mono">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <span x-text="aiStatusMessage"></span>
                    </div>
                </div>

                <!-- Template Shortcuts with Lucide Icons (No Emojis) -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-extrabold text-zinc-400 uppercase tracking-wider">Template Cepat</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" @click="applyTemplate('film')" class="px-2.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 text-[11px] font-bold text-zinc-300 hover:text-white flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <i data-lucide="film" class="w-3.5 h-3.5 text-zinc-400"></i>
                            <span>Rilis Film</span>
                        </button>

                        <button type="button" @click="applyTemplate('maintenance')" class="px-2.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 text-[11px] font-bold text-zinc-300 hover:text-white flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <i data-lucide="wrench" class="w-3.5 h-3.5 text-zinc-400"></i>
                            <span>Maintenance</span>
                        </button>

                        <button type="button" @click="applyTemplate('watchparty')" class="px-2.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 text-[11px] font-bold text-zinc-300 hover:text-white flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <i data-lucide="tv" class="w-3.5 h-3.5 text-zinc-400"></i>
                            <span>Watch Party</span>
                        </button>

                        <button type="button" @click="applyTemplate('update')" class="px-2.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 text-[11px] font-bold text-zinc-300 hover:text-white flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-zinc-400"></i>
                            <span>Update Fitur</span>
                        </button>
                    </div>
                </div>

                <form action="{{ route('admin.notifications.send') }}" method="POST" class="space-y-4" onsubmit="return confirm('Kirim broadcast notifikasi ini sekarang ke seluruh target user?')">
                    @csrf

                    <!-- Target Audience -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Target Penerima *</label>
                        <select name="target" x-model="form.target" required 
                                class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-zinc-600 font-medium">
                            <option value="all">Semua Pengguna ({{ $totalUsers }} User Terdaftar)</option>
                            <option value="active_30d">Pengguna Aktif (30 Hari Terakhir)</option>
                            <option value="admin_only">Khusus Administrator (Uji Coba)</option>
                        </select>
                    </div>

                    <!-- Notification Type -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Tipe Notifikasi *</label>
                        <select name="type" x-model="form.type" required 
                                class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-zinc-600 font-medium">
                            <option value="announcement">Pengumuman (Announcement)</option>
                            <option value="new_film">Film / Series Baru (New Release)</option>
                            <option value="watch_party">Watch Party (Undangan Nobar)</option>
                            <option value="system">Sistem (System Notice)</option>
                            <option value="maintenance">Pemeliharaan Server (Maintenance)</option>
                            <option value="promotion">Event & Promo (Promotion)</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Judul Notifikasi (Opsional)</label>
                        <input type="text" name="title" x-model="form.title" placeholder="Contoh: Rilis Film Deadpool & Wolverine" 
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-medium">
                    </div>

                    <!-- Message Body -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">Isi Pesan Notifikasi *</label>
                        <textarea name="message" x-model="form.message" rows="4" required placeholder="Tuliskan pesan notifikasi yang akan dibaca pengguna..." 
                                  class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-3.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 leading-relaxed font-medium"></textarea>
                    </div>

                    <!-- Link Destination URL with Integrated Film Picker -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Tautan URL Tujuan (Klik Notifikasi)</label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <i data-lucide="link" class="w-3.5 h-3.5 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" name="url" x-model="form.url" placeholder="/film/slug-judul atau https://..." 
                                       class="w-full bg-zinc-950 border border-zinc-800 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-mono">
                            </div>
                            <button type="button" @click="filmPickerOpen = true" 
                                    class="px-3.5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-200 hover:text-white border border-zinc-700 flex items-center gap-1.5 shrink-0 transition-colors cursor-pointer"
                                    title="Pilih dari Katalog Film">
                                <i data-lucide="clapperboard" class="w-3.5 h-3.5 text-zinc-400"></i>
                                <span>Pilih Film</span>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full py-3 rounded-full bg-white hover:bg-zinc-200 text-zinc-950 font-extrabold text-xs shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer hover:scale-[1.01] active:scale-[0.99]">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span>Siarkan Notifikasi Sekarang</span>
                        </button>
                    </div>

                </form>

            </div>

        </div>

        <!-- Right Column (7 Cols): Live Preview (TOP) & Past Broadcasts History (BOTTOM) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Live Mockup Card Preview at the TOP of Right Column -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-300 flex items-center gap-2">
                        <i data-lucide="smartphone" class="w-4 h-4 text-zinc-400"></i>
                        <span>Pratinjau Kotak Masuk User (Live)</span>
                    </span>
                    <span class="text-[10px] font-mono text-zinc-500">Live Preview</span>
                </div>

                <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800/90 flex items-start gap-3.5 shadow-inner">
                    <div class="w-10 h-10 rounded-2xl bg-zinc-800 border border-zinc-700 text-white flex items-center justify-center shrink-0">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-300 border border-zinc-700 font-mono" x-text="form.type"></span>
                            <span class="text-[10px] text-zinc-500 font-mono">Baru saja</span>
                        </div>
                        <h4 class="font-bold text-xs text-white" x-text="form.title || 'Pemberitahuan Faiilmov'"></h4>
                        <p class="text-[11px] text-zinc-400 leading-relaxed break-words whitespace-pre-line" x-text="form.message || 'Pesan notifikasi akan muncul di sini...'"></p>
                        <template x-if="form.url">
                            <span class="text-[10px] text-zinc-400 font-mono truncate block underline" x-text="'Tautan: ' + form.url"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Past Broadcasts History Card -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white font-['Outfit'] flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-zinc-400"></i>
                        <span>Riwayat Broadcast Notifikasi ({{ $broadcasts->total() }})</span>
                    </h3>
                </div>

                <div class="bg-zinc-900/90 border border-white/10 rounded-3xl overflow-hidden shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider">
                                <tr>
                                    <th class="px-4 py-3.5">Tipe & Pesan</th>
                                    <th class="px-4 py-3.5 text-center">Penerima</th>
                                    <th class="px-4 py-3.5">Waktu Kirim</th>
                                    <th class="px-4 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/60">
                                @forelse($broadcasts as $item)
                                    <tr class="hover:bg-zinc-800/40 transition-colors group">
                                        <td class="px-4 py-3.5 space-y-1.5 max-w-[280px]">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-mono font-extrabold uppercase bg-zinc-800 text-zinc-300 border border-zinc-700">
                                                    {{ $item->type }}
                                                </span>
                                                @if($item->url)
                                                    <a href="{{ $item->url }}" target="_blank" class="text-[10px] text-zinc-400 hover:text-white flex items-center gap-0.5 underline truncate max-w-[140px]">
                                                    <span>{{ $item->url }}</span>
                                                    <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <p class="text-zinc-200 text-xs line-clamp-2 leading-relaxed whitespace-pre-line">{{ $item->message }}</p>
                                    </td>

                                    <td class="px-4 py-3.5 text-center font-mono">
                                        <span class="px-2.5 py-1 rounded-xl bg-zinc-950 border border-zinc-800 text-white font-bold text-xs inline-block">
                                            {{ number_format($item->recipient_count) }} user
                                        </span>
                                        @if($item->read_count > 0)
                                            <p class="text-[10px] text-zinc-500 mt-0.5 font-mono">{{ $item->read_count }} dibaca</p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3.5 text-zinc-400 text-[11px] font-mono whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y, H:i') }}
                                    </td>

                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        <form action="{{ route('admin.notifications.destroy_broadcast') }}" method="POST" onsubmit="return confirm('Tarik kembali broadcast ini dari kotak masuk seluruh pengguna?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="notification_ids" value="{{ $item->notification_ids }}">
                                            <input type="hidden" name="message" value="{{ $item->message }}">
                                            <input type="hidden" name="type" value="{{ $item->type }}">
                                            <button type="submit" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors cursor-pointer" title="Tarik / Hapus Notifikasi">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-zinc-500">
                                        <i data-lucide="bell-off" class="w-8 h-8 mx-auto text-zinc-600 mb-2"></i>
                                        <p class="text-xs font-semibold text-zinc-400">Belum ada riwayat siaran notifikasi.</p>
                                        <p class="text-[11px] text-zinc-500">Gunakan form di sebelah kiri untuk menyiarkan pesan baru.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($broadcasts->hasPages())
                    <div class="p-4 border-t border-zinc-800">
                        {{ $broadcasts->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Quick Film Picker Modal with Live Filter & Fallback Poster -->
    <div x-show="filmPickerOpen" x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
        <div @click.away="filmPickerOpen = false" class="w-full max-w-xl p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl text-white">
            <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                <div>
                    <h4 class="font-bold text-white text-sm font-['Outfit']">Pilih Film untuk Notifikasi</h4>
                    <p class="text-[11px] text-zinc-400">Pilih film dari katalog untuk mengisi judul & URL otomatis.</p>
                </div>
                <button type="button" @click="filmPickerOpen = false" class="p-1.5 rounded-xl hover:bg-zinc-800 text-zinc-400 hover:text-white cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Instant Search Filter Bar -->
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" 
                       x-model="filmSearch" 
                       placeholder="Cari judul film atau dracin..." 
                       class="w-full bg-zinc-950 border border-zinc-800 rounded-xl pl-9 pr-4 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/30">
            </div>

            <!-- Filtered Film List -->
            <div class="max-h-[380px] overflow-y-auto space-y-2 pr-1 admin-scrollbar">
                @foreach($recentFilms as $f)
                    <div x-show="!filmSearch || '{{ strtolower(addslashes($f->title)) }}'.includes(filmSearch.toLowerCase())"
                         @click="selectFilm('{{ addslashes($f->title) }}', '/film/{{ $f->slug }}')" 
                         class="w-full p-2.5 rounded-2xl bg-zinc-950 hover:bg-zinc-800/80 border border-zinc-800 flex items-center gap-3 transition-colors text-left cursor-pointer group">
                        
                        <!-- Poster with Fallback Image -->
                        <div class="w-9 h-12 rounded-lg overflow-hidden bg-zinc-900 shrink-0 border border-zinc-800 flex items-center justify-center">
                            @if($f->poster_url)
                                <img src="{{ $f->poster_url }}" 
                                     alt="{{ $f->title }}" 
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                                <div class="hidden w-full h-full flex items-center justify-center text-zinc-600">
                                    <i data-lucide="film" class="w-4 h-4"></i>
                                </div>
                            @else
                                <i data-lucide="film" class="w-4 h-4 text-zinc-600"></i>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-xs text-white group-hover:text-zinc-200 truncate">{{ $f->title }}</p>
                            <p class="text-[10px] text-zinc-500 font-mono">{{ strtoupper($f->subject_type) }} &bull; {{ $f->release_year }}</p>
                        </div>

                        <span class="px-3 py-1 rounded-xl bg-zinc-800 group-hover:bg-white group-hover:text-zinc-950 text-zinc-300 text-[10px] font-bold shrink-0 transition-colors">
                            Pilih
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationManager', () => ({
        filmPickerOpen: false,
        filmSearch: '',
        
        // AI State
        aiPrompt: '',
        aiTone: 'enthusiastic',
        isGenerating: false,
        aiStatusMessage: '',

        form: {
            target: 'all',
            type: 'announcement',
            title: '',
            message: '',
            url: '',
        },

        async generateWithAi() {
            if (!this.aiPrompt.trim() || this.isGenerating) return;

            this.isGenerating = true;
            this.aiStatusMessage = '';

            try {
                const res = await fetch('{{ route('admin.notifications.generate_ai') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        prompt: this.aiPrompt,
                        tone: this.aiTone
                    })
                });

                const data = await res.json();

                if (data.success) {
                    this.form.title = data.title || '';
                    this.form.message = data.message || '';
                    if (data.type) this.form.type = data.type;
                    if (data.target) this.form.target = data.target;
                    if (data.url) this.form.url = data.url;

                    this.aiStatusMessage = 'Berhasil digenerate oleh AI!';
                    setTimeout(() => { this.aiStatusMessage = ''; }, 4000);
                } else {
                    this.aiStatusMessage = 'Gagal: ' + (data.error || 'Terjadi kesalahan');
                }
            } catch (err) {
                this.aiStatusMessage = 'Koneksi error: ' + err.message;
            } finally {
                this.isGenerating = false;
            }
        },

        applyTemplate(type) {
            if (type === 'film') {
                this.form.type = 'new_film';
                this.form.title = 'Rilis Film Baru';
                this.form.message = 'Film terbaru kini telah tersedia dan dapat kamu tonton dengan kualitas Full HD & subtitle Indonesia gratis!';
            } else if (type === 'maintenance') {
                this.form.type = 'maintenance';
                this.form.title = 'Pemeliharaan Server Terjadwal';
                this.form.message = 'Akan dilakukan pemeliharaan server rutin demi meningkatkan performa streaming. Mohon maaf atas ketidaknyamanannya.';
            } else if (type === 'watchparty') {
                this.form.type = 'watch_party';
                this.form.title = 'Nobar Watch Party Dimulai!';
                this.form.message = 'Sesi nobar komunitas sedang berlangsung! Gabung sekarang dan nikmati streaming bersama teman-teman.';
                this.form.url = '/watch-party';
            } else if (type === 'update') {
                this.form.type = 'announcement';
                this.form.title = 'Pembaruan Sistem Faiilmov';
                this.form.message = 'Kami baru saja merilis fitur dan optimasi baru untuk pengalaman streaming yang lebih cepat dan lancar.';
            }
        },

        selectFilm(title, url) {
            this.form.type = 'new_film';
            this.form.title = `Rilis Baru: ${title}`;
            this.form.message = `Film "${title}" kini sudah bisa kamu tonton secara gratis dengan subtitle Indonesia!`;
            this.form.url = url;
            this.filmPickerOpen = false;
        }
    }));
});
</script>
@endsection
