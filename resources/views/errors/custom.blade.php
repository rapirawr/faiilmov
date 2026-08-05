@extends('layouts.app')

@section('title', ($title ?? 'Akses Ditolak') . ' — faiilmov')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center px-4 py-12 relative z-10">
    <div class="w-full max-w-lg glass-panel p-8 sm:p-12 rounded-[2.5rem] border border-white/12 shadow-2xl relative overflow-hidden text-center">
        
        <!-- Ambient Decorative Top Glow Line -->
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-500/50 to-transparent"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

        <!-- Glowing Icon Badge -->
        <div class="w-20 h-20 rounded-3xl bg-amber-500/10 border border-amber-500/25 text-amber-400 flex items-center justify-center mx-auto mb-6 shadow-xl shadow-amber-500/10 backdrop-blur-md">
            <i data-lucide="{{ $icon ?? 'lock' }}" class="w-10 h-10"></i>
        </div>

        <!-- Category Tag -->
        <div class="mb-4">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full glass-chip text-[10px] font-bold text-amber-400 uppercase tracking-widest border border-amber-500/20">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                <span>{{ $tag ?? 'Room Dikunci' }}</span>
            </span>
        </div>

        <!-- Error Heading -->
        <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white tracking-tight mb-3">
            {{ $title ?? 'Akses Room Dibatasi' }}
        </h1>

        <!-- Error Description Message -->
        <p class="text-xs sm:text-sm text-zinc-300 leading-relaxed max-w-md mx-auto mb-8">
            {{ $message ?? 'Room ini sedang dikunci oleh Host. Anda tidak dapat bergabung saat ini.' }}
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
                <i data-lucide="home" class="w-4 h-4"></i>
                <span>Kembali ke Beranda</span>
            </a>

            <a href="{{ route('browse') }}" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl glass-chip hover:bg-white/10 text-white font-semibold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                <i data-lucide="film" class="w-4 h-4"></i>
                <span>Jelajahi Film</span>
            </a>
        </div>

    </div>
</div>
@endsection
