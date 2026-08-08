@extends('layouts.app')

@section('title', 'Daftar Akun Baru — faiilmov')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 relative z-10">
    <div class="w-full max-w-md glass-panel p-8 sm:p-10 rounded-[2.5rem] border border-white/12 shadow-2xl relative overflow-hidden">
        
        <!-- Decorative Ambient Top Glow Line -->
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>

        <!-- Header / Logo -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-[#e4e2dd] p-2 flex items-center justify-center mx-auto mb-4 shadow-xl shadow-white/5 border border-white/20">
                <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="w-full h-full object-contain">
            </div>
            <span class="text-[11px] uppercase tracking-widest text-zinc-400 font-semibold mb-1 block">Bergabung Sekarang</span>
            <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
                Buat Akun <span class="font-serif text-white">faiil</span><span class="font-sans text-zinc-400">mov</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                Gabung gratis untuk menyimpan daftar tontonan & ulasan film.
            </p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Name Field -->
            <x-input 
                name="name" 
                type="text" 
                label="Nama Lengkap" 
                icon="user" 
                :value="old('name')" 
                placeholder="John Doe" 
                required 
                autofocus 
            />

            <!-- Email Field -->
            <x-input 
                name="email" 
                type="email" 
                label="Alamat Email" 
                icon="mail" 
                :value="old('email')" 
                placeholder="nama@email.com" 
                required 
            />

            <!-- Password Field -->
            <x-input 
                name="password" 
                type="password" 
                label="Kata Sandi" 
                icon="lock" 
                placeholder="Minimal 8 karakter" 
                required 
            />

            <!-- Confirm Password Field -->
            <x-input 
                name="password_confirmation" 
                id="password_confirmation"
                type="password" 
                label="Konfirmasi Kata Sandi" 
                icon="key-round" 
                placeholder="Ketik ulang kata sandi" 
                required 
            />

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:shadow-white/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer group mt-3">
                <span>Daftar Sekarang</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform"></i>
            </button>
        </form>

        <!-- Switcher to Login -->
        <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs text-zinc-400">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-white hover:underline transition-colors ml-1">Masuk di sini</a>
        </div>

    </div>
</div>
@endsection
