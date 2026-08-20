@php
    $cmsSetting = \App\Models\SiteSetting::current();
@endphp
@extends('layouts.app')

@section('title', '503 | Sedang Pemeliharaan | ' . $cmsSetting->site_name)
@section('hide_navbar', true)
@section('hide_sidebar', true)
@section('hide_footer', true)
@section('hide_welcome_modal', true)

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 relative z-10 overflow-hidden">
    
    <!-- Brand Logo Top Header -->
    <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2.5 group">
        <div class="w-10 h-10 rounded-xl bg-zinc-900 p-1.5 flex items-center justify-center shadow-lg border border-white/20">
            <img src="{{ $cmsSetting->logo_url }}" alt="{{ $cmsSetting->site_name }}" class="w-full h-full object-contain">
        </div>
        <span class="font-chillax font-bold text-2xl text-white tracking-tight">{{ $cmsSetting->site_name }}</span>
    </a>

    <div class="w-full max-w-xl glass-panel p-8 sm:p-14 rounded-[2.5rem] border border-white/10 shadow-2xl relative overflow-hidden text-center backdrop-blur-xl bg-zinc-900/90">

        <!-- Top Accent Line -->
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>

        <!-- Wrench & Clapper Icon Badge -->
        <div class="w-20 h-20 rounded-3xl bg-white/10 border border-white/20 text-white flex items-center justify-center mx-auto mb-6 shadow-xl backdrop-blur-md">
            <i data-lucide="wrench" class="w-10 h-10 animate-bounce"></i>
        </div>

        <!-- Tag -->
        <div class="mb-3">
            <span class="text-xs font-bold uppercase tracking-widest text-zinc-300 px-3.5 py-1 rounded-full bg-white/10 border border-white/20">
                Mode Pemeliharaan
            </span>
        </div>

        <!-- Title & Subtitle -->
        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight mb-3 font-sans">
            Sistem Sedang Dalam Pemeliharaan
        </h2>
        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed max-w-md mx-auto mb-8">
            {{ $maintenanceMessage ?? ($cmsSetting->maintenance_message ?: 'Kami sedang melakukan peningkatan performa dan pembaruan server untuk memberikan pengalaman streaming terbaik. Kami akan kembali segera!') }}
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.location.reload()" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer group">
                <i data-lucide="rotate-cw" class="w-4 h-4 text-zinc-900 group-hover:rotate-180 transition-transform duration-500"></i>
                <span>Cek Status Kembali</span>
            </button>
        </div>

    </div>
</div>
@endsection
