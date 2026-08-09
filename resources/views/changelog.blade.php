@extends('layouts.app')

@section('title', 'Changelog & Pembaruan Sistem - faiilmov')
@section('meta_description', 'Catatan rilis resmi, histori pembaruan fitur, perbaikan bug, dan pengumuman sistem terbaru dari platform streaming faiilmov.')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-12"
     x-data="{
         copied: false,
         copyLink() {
             navigator.clipboard.writeText(window.location.href);
             this.copied = true;
             setTimeout(() => this.copied = false, 2500);
         }
     }">

    <!-- Hero Section -->
    <div class="glass-panel p-6 sm:p-12 rounded-3xl border border-white/10 relative overflow-hidden bg-dark-900/80 backdrop-blur-2xl shadow-2xl space-y-6 text-center md:text-left">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">

            <div class="space-y-3 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold shadow-lg">
                    <i data-lucide="history" class="w-4 h-4"></i>
                    <span>SYSTEM CHANGELOG & RELEASE NOTES</span>
                </div>

                <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight">
                    Catatan Rilis & Pembaruan
                </h1>

                <p class="text-sm sm:text-base text-zinc-300 leading-relaxed">
                    Pelajari fitur-fitur baru, peningkatan performa, perbaikan keamanan, dan evolusi platform streaming <strong class="text-white">faiilmov</strong> dari waktu ke waktu.
                </p>
            </div>

            <!-- Version Highlight Card -->
            @if($latestRelease)
                <div class="p-5 rounded-2xl bg-white/5 border border-white/10 space-y-2 text-center md:text-right shrink-0">
                    <span class="text-[10px] uppercase tracking-wider font-extrabold text-zinc-400 block">Versi Terbaru</span>
                    <span class="text-3xl font-extrabold text-amber-400 font-mono block">{{ $latestRelease->version }}</span>
                    <span class="text-xs text-zinc-400 block">{{ $latestRelease->release_date ? $latestRelease->release_date->format('d M Y') : 'Terbaru' }}</span>
                </div>
            @endif

        </div>

        <!-- Filter Tags Bar -->
        <div class="pt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <a href="{{ route('changelog') }}" 
                   class="px-3.5 py-2 rounded-xl border transition-all font-semibold {{ !request('type') ? 'bg-amber-500 text-black border-amber-500 font-bold shadow-lg shadow-amber-500/20' : 'bg-white/5 border-white/10 text-zinc-400 hover:text-white' }}">
                    Semua Rilis
                </a>
                <a href="{{ route('changelog', ['type' => 'major']) }}" 
                   class="px-3.5 py-2 rounded-xl border transition-all font-semibold {{ request('type') === 'major' ? 'bg-amber-500 text-black border-amber-500 font-bold shadow-lg shadow-amber-500/20' : 'bg-white/5 border-white/10 text-zinc-400 hover:text-white' }}">
                    Major Release
                </a>
                <a href="{{ route('changelog', ['type' => 'minor']) }}" 
                   class="px-3.5 py-2 rounded-xl border transition-all font-semibold {{ request('type') === 'minor' ? 'bg-amber-500 text-black border-amber-500 font-bold shadow-lg shadow-amber-500/20' : 'bg-white/5 border-white/10 text-zinc-400 hover:text-white' }}">
                    Minor Updates
                </a>
                <a href="{{ route('changelog', ['type' => 'security']) }}" 
                   class="px-3.5 py-2 rounded-xl border transition-all font-semibold {{ in_array(request('type'), ['security', 'patch']) ? 'bg-amber-500 text-black border-amber-500 font-bold shadow-lg shadow-amber-500/20' : 'bg-white/5 border-white/10 text-zinc-400 hover:text-white' }}">
                    Security & Patches
                </a>
            </div>

            <button @click="copyLink()" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-zinc-300 font-semibold transition-all flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="link" class="w-3.5 h-3.5 text-amber-400"></i>
                <span x-text="copied ? 'Tautan Tersalin!' : 'Bagikan Halaman'"></span>
            </button>
        </div>
    </div>

    <!-- Changelog Timeline Section -->
    <div class="relative space-y-8 {{ $changelogs->count() > 0 ? 'before:absolute before:inset-0 before:left-3 sm:before:left-[143px] before:w-0.5 before:bg-gradient-to-b before:from-amber-500/40 before:via-white/10 before:to-transparent' : '' }}">

        @forelse($changelogs as $log)
            <div class="relative flex flex-col sm:flex-row items-start gap-4 sm:gap-8 group" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                
                <!-- Left Date / Version Badge Column (Desktop) -->
                <div class="sm:w-28 shrink-0 sm:text-right space-y-1 pl-8 sm:pl-0">
                    <span class="px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-mono font-extrabold text-xs inline-block shadow-md">
                        {{ $log->version }}
                    </span>
                    <p class="text-[11px] text-zinc-400 font-mono">
                        {{ $log->release_date ? $log->release_date->format('d M Y') : '' }}
                    </p>
                </div>

                <!-- Timeline Node Indicator Circle -->
                <div class="absolute left-1 sm:left-[135px] top-2.5 w-4 h-4 rounded-full bg-dark-950 border-2 border-amber-500 flex items-center justify-center shadow-lg group-hover:scale-125 transition-transform duration-300 z-10">
                    <span class="w-1 h-1 rounded-full bg-amber-400"></span>
                </div>

                <!-- Right Card Container (Collapsible FAQ Accordion) -->
                <div class="flex-1 min-w-0 glass-card p-6 sm:p-8 rounded-3xl border border-white/10 bg-zinc-900/60 backdrop-blur-xl shadow-xl transition-all">
                    
                    <!-- Header Title & Type Badge (Clickable Accordion Header) -->
                    <div @click="open = !open" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer select-none group/header">
                        <h2 class="font-serif font-bold text-lg sm:text-2xl text-white group-hover/header:text-amber-300 transition-colors flex-1 min-w-0 leading-tight">
                            {{ $log->title }}
                        </h2>

                        <div class="flex items-center gap-3 shrink-0 whitespace-nowrap">
                            @if($log->type === 'major')
                                <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 font-extrabold text-[11px] uppercase border border-amber-500/30 whitespace-nowrap inline-block shadow-sm">
                                    🚀 Major Release
                                </span>
                            @elseif($log->type === 'minor')
                                <span class="px-3 py-1 rounded-full bg-sky-500/20 text-sky-300 font-extrabold text-[11px] uppercase border border-sky-500/30 whitespace-nowrap inline-block shadow-sm">
                                    ✨ Feature Update
                                </span>
                            @elseif($log->type === 'security')
                                <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 font-extrabold text-[11px] uppercase border border-purple-500/30 whitespace-nowrap inline-block shadow-sm">
                                    🛡️ Security Patch
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-extrabold text-[11px] uppercase border border-emerald-500/30 whitespace-nowrap inline-block shadow-sm">
                                    🔧 Patch & Fixes
                                </span>
                            @endif

                            <!-- Chevron Collapse / Expand Toggle Button -->
                            <div class="w-8 h-8 rounded-xl bg-white/5 group-hover/header:bg-white/15 border border-white/10 flex items-center justify-center text-zinc-400 group-hover/header:text-white transition-all shadow-sm">
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180 text-amber-400' : ''"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Content Body (Summary & Change List) -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-5 pt-5 border-t border-white/10 mt-5">
                        
                        <!-- Summary -->
                        @if($log->summary)
                            <p class="text-sm text-zinc-300 leading-relaxed font-normal">
                                {{ $log->summary }}
                            </p>
                        @endif

                        <!-- Detailed Change Items List -->
                        @if(!empty($log->changes) && is_array($log->changes))
                            <div class="space-y-2.5 pt-1">
                                <p class="text-xs uppercase font-bold text-zinc-400 tracking-wider">Rincian Perubahan:</p>

                                <div class="space-y-2">
                                    @foreach($log->changes as $item)
                                        @php 
                                            $itemType = strtolower($item['type'] ?? 'feature');
                                            $itemText = $item['text'] ?? '';
                                        @endphp
                                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-white/5 border border-white/5 text-xs text-zinc-300 leading-relaxed">
                                            @if($itemType === 'feature')
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-400 font-extrabold text-[9px] uppercase shrink-0 mt-0.5">FITUR BARU</span>
                                            @elseif($itemType === 'improvement')
                                                <span class="px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-400 font-extrabold text-[9px] uppercase shrink-0 mt-0.5">PENINGKATAN</span>
                                            @elseif($itemType === 'fix')
                                                <span class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-400 font-extrabold text-[9px] uppercase shrink-0 mt-0.5">PERBAIKAN</span>
                                            @elseif($itemType === 'security')
                                                <span class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-400 font-extrabold text-[9px] uppercase shrink-0 mt-0.5">KEAMANAN</span>
                                            @endif
                                            <span>{{ $itemText }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @empty
            <div class="text-center py-16 space-y-3 glass-panel rounded-3xl p-8 border border-white/10">
                <i data-lucide="file-text" class="w-12 h-12 mx-auto text-zinc-600"></i>
                <p class="text-base font-bold text-white">Belum Ada Catatan Rilis</p>
                <p class="text-xs text-zinc-400">Tidak ada catatan changelog yang cocok dengan filter yang dipilih.</p>
            </div>
        @endforelse

    </div>

    <!-- Pagination -->
    @if($changelogs->hasPages())
        <div class="pt-6">
            {{ $changelogs->links() }}
        </div>
    @endif

    <!-- Back to Home Link -->
    <div class="text-center pt-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/15 transition-all shadow-lg hover:scale-105">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Beranda</span>
        </a>
    </div>

</div>
@endsection
