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
            <div class="w-14 h-14 rounded-2xl bg-white text-zinc-950 flex items-center justify-center mx-auto mb-4 shadow-xl shadow-white/5 border border-white/20">
                <i data-lucide="user-plus" class="w-7 h-7"></i>
            </div>
            <span class="text-[11px] uppercase tracking-widest text-zinc-400 font-semibold mb-1 block">Bergabung Sekarang</span>
            <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
                Buat Akun <span class="font-serif text-white">faiil</span><span class="font-sans text-zinc-400">mov</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                Gabung gratis untuk menyimpan daftar tontonan & ulasan film.
            </p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4" x-data="{ showPass: false, showConfirm: false }">
            @csrf

            <!-- Name Field -->
            <div>
                <label for="name" class="block text-[11px] font-bold text-zinc-300 uppercase tracking-wider mb-2">
                    Nama Lengkap
                </label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 text-zinc-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                           placeholder="John Doe" 
                           class="w-full bg-zinc-900/80 text-xs sm:text-sm text-zinc-100 placeholder-zinc-500 pl-11 pr-4 py-3.5 rounded-2xl border border-white/10 focus:outline-none focus:border-zinc-300 focus:bg-zinc-800/90 transition-all shadow-inner">
                </div>
                @error('name')
                    <p class="text-[11px] text-rose-400 mt-1.5 flex items-center gap-1 font-medium">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-[11px] font-bold text-zinc-300 uppercase tracking-wider mb-2">
                    Alamat Email
                </label>
                <div class="relative">
                    <i data-lucide="mail" class="w-4 h-4 text-zinc-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           placeholder="nama@email.com" 
                           class="w-full bg-zinc-900/80 text-xs sm:text-sm text-zinc-100 placeholder-zinc-500 pl-11 pr-4 py-3.5 rounded-2xl border border-white/10 focus:outline-none focus:border-zinc-300 focus:bg-zinc-800/90 transition-all shadow-inner">
                </div>
                @error('email')
                    <p class="text-[11px] text-rose-400 mt-1.5 flex items-center gap-1 font-medium">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-[11px] font-bold text-zinc-300 uppercase tracking-wider mb-2">
                    Kata Sandi
                </label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 text-zinc-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input id="password" :type="showPass ? 'text' : 'password'" name="password" required
                           placeholder="Minimal 8 karakter" 
                           class="w-full bg-zinc-900/80 text-xs sm:text-sm text-zinc-100 placeholder-zinc-500 pl-11 pr-11 py-3.5 rounded-2xl border border-white/10 focus:outline-none focus:border-zinc-300 focus:bg-zinc-800/90 transition-all shadow-inner">
                    <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white transition-colors focus:outline-none">
                        <i x-show="!showPass" data-lucide="eye" class="w-4 h-4"></i>
                        <i x-show="showPass" data-lucide="eye-off" class="w-4 h-4" style="display: none;"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-[11px] text-rose-400 mt-1.5 flex items-center gap-1 font-medium">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Confirm Password Field -->
            <div>
                <label for="password_confirmation" class="block text-[11px] font-bold text-zinc-300 uppercase tracking-wider mb-2">
                    Konfirmasi Kata Sandi
                </label>
                <div class="relative">
                    <i data-lucide="key-round" class="w-4 h-4 text-zinc-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input id="password_confirmation" :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required
                           placeholder="Ketik ulang kata sandi" 
                           class="w-full bg-zinc-900/80 text-xs sm:text-sm text-zinc-100 placeholder-zinc-500 pl-11 pr-11 py-3.5 rounded-2xl border border-white/10 focus:outline-none focus:border-zinc-300 focus:bg-zinc-800/90 transition-all shadow-inner">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white transition-colors focus:outline-none">
                        <i x-show="!showConfirm" data-lucide="eye" class="w-4 h-4"></i>
                        <i x-show="showConfirm" data-lucide="eye-off" class="w-4 h-4" style="display: none;"></i>
                    </button>
                </div>
            </div>

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
