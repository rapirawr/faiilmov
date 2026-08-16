@extends('layouts.app')

@section('title', '419 — Sesi Halaman Berakhir | faiilmov')
@section('hide_navbar', true)
@section('hide_sidebar', true)
@section('hide_footer', true)
@section('hide_welcome_modal', true)

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 relative z-10 overflow-hidden">
    
    <!-- Background Ambient Glow -->
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-blue-500/15 rounded-full blur-[140px] pointer-events-none"></div>

    <!-- Brand Logo Top Header -->
    <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 group">
        <div class="w-10 h-10 rounded-xl bg-[#e4e2dd] p-1.5 flex items-center justify-center shadow-lg shadow-white/5 border border-white/20">
            <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="w-full h-full object-contain">
        </div>
        <span class="font-serif font-extrabold text-2xl text-white tracking-tight">faiil<span class="font-sans text-zinc-400">mov</span></span>
    </a>

    <div class="w-full max-w-xl glass-panel p-8 sm:p-14 rounded-[2.5rem] border border-white/12 shadow-2xl relative overflow-hidden text-center backdrop-blur-xl">

        <!-- Top Accent Line -->
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

        <!-- Clock Refresh Icon Badge -->
        <div class="w-20 h-20 rounded-3xl bg-blue-500/10 border border-blue-500/25 text-blue-400 flex items-center justify-center mx-auto mb-6 shadow-xl shadow-blue-500/10 backdrop-blur-md">
            <i data-lucide="timer-reset" class="w-10 h-10"></i>
        </div>

        <!-- Tag -->
        <div class="mb-3">
            <span class="text-xs font-bold uppercase tracking-widest text-blue-400 px-3.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20">
                Error 419 — Sesi Berakhir
            </span>
        </div>

        <!-- Title & Subtitle -->
        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight mb-3 font-sans">
            Sesi Tayangan Kadaluarsa
        </h2>
        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed max-w-md mx-auto mb-8">
            Halaman ini telah berada dalam mode diam terlalu lama sehingga token keamanan berakhir. Silakan muat ulang halaman untuk melanjutkan.
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.location.reload()" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer group">
                <i data-lucide="rotate-cw" class="w-4 h-4 text-zinc-900 group-hover:rotate-180 transition-transform duration-500"></i>
                <span>Muat Ulang Halaman</span>
            </button>

            <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl glass-chip hover:bg-white/10 text-white font-semibold text-xs sm:text-sm border border-white/10 hover:border-white/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <i data-lucide="home" class="w-4 h-4 text-zinc-300"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>

    </div>
</div>
@endsection
