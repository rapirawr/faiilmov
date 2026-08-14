@extends('layouts.app')

@section('title', '404 — Adegan Tidak Ditemukan | faiilmov')
@section('hide_navbar', true)
@section('hide_sidebar', true)
@section('hide_footer', true)

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 relative z-10 overflow-hidden">
    
    <!-- Background Ambient Glow & Cinematic Grids -->
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-red-600/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-32 left-1/2 -translate-x-1/2 w-[500px] h-[300px] bg-amber-500/10 rounded-full blur-[140px] pointer-events-none"></div>

    <!-- Brand Logo Top Header -->
    <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 group">
        <div class="w-10 h-10 rounded-xl bg-[#e4e2dd] p-1.5 flex items-center justify-center shadow-lg shadow-white/5 border border-white/20">
            <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="w-full h-full object-contain">
        </div>
        <span class="font-serif font-extrabold text-2xl text-white tracking-tight">faiil<span class="font-sans text-zinc-400">mov</span></span>
    </a>

    <div class="w-full max-w-xl glass-panel p-8 sm:p-14 rounded-[2.5rem] border border-white/12 shadow-2xl relative overflow-hidden text-center backdrop-blur-xl">

        <!-- Top Accent Line -->
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-red-500/50 to-transparent"></div>

        <!-- Glowing Big Number Badge -->
        <div class="relative inline-block mb-6">
            <h1 class="text-7xl sm:text-8xl font-extrabold text-transparent bg-clip-text bg-gradient-to-b from-white via-zinc-200 to-zinc-600 tracking-tighter select-none font-serif drop-shadow-2xl">
                404
            </h1>
            <span class="absolute -top-2 -right-4 px-3 py-1 rounded-full bg-red-500/20 border border-red-500/30 text-red-400 text-[10px] uppercase font-bold tracking-widest backdrop-blur-md">
                Scene Cut
            </span>
        </div>

        <!-- Title & Subtitle -->
        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight mb-3 font-sans">
            Halaman Atau Film Tidak Ditemukan
        </h2>
        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed max-w-md mx-auto mb-8">
            Sepertinya adegan atau film yang Anda cari telah dipindahkan, dihapus, atau jalurnya terputus dari katalog tayangan kami.
        </p>

        <!-- Quick Search Bar -->
        <form action="{{ route('browse') }}" method="GET" class="relative max-w-md mx-auto mb-8">
            <div class="relative flex items-center">
                <i data-lucide="search" class="w-4 h-4 text-zinc-400 absolute left-4 pointer-events-none"></i>
                <input 
                    type="text" 
                    name="q" 
                    placeholder="Cari film, drama, atau tayangan lain..." 
                    class="w-full pl-11 pr-24 py-3.5 rounded-2xl bg-zinc-900/80 border border-white/15 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-red-500/60 focus:ring-1 focus:ring-red-500/60 transition-all shadow-inner"
                    required
                >
                <button type="submit" class="absolute right-2 px-3.5 py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-all cursor-pointer">
                    Cari
                </button>
            </div>
        </form>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer group">
                <i data-lucide="home" class="w-4 h-4 text-zinc-900 group-hover:-translate-x-0.5 transition-transform"></i>
                <span>Kembali ke Beranda</span>
            </a>

            <a href="{{ route('browse') }}" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl glass-chip hover:bg-white/10 text-white font-semibold text-xs sm:text-sm border border-white/10 hover:border-white/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <i data-lucide="clapperboard" class="w-4 h-4 text-zinc-300"></i>
                <span>Jelajahi Katalog Film</span>
            </a>
        </div>

    </div>
</div>
@endsection
