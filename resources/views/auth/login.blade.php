@extends('layouts.app')

@section('title', 'Masuk Akun — faiilmov')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 relative z-10">
    <div class="w-full max-w-md glass-panel p-8 sm:p-10 rounded-[2.5rem] border border-white/12 shadow-2xl relative overflow-hidden">
        
        <!-- Decorative Ambient Top Glow Line -->
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>

        <!-- Header / Logo -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-2xl bg-white text-zinc-950 flex items-center justify-center mx-auto mb-4 shadow-xl shadow-white/5 border border-white/20">
                <i data-lucide="film" class="w-7 h-7"></i>
            </div>
            <span class="text-[11px] uppercase tracking-widest text-zinc-400 font-semibold mb-1 block">Selamat Datang Kembali</span>
            <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
                Masuk ke <span class="font-serif text-white">faiil</span><span class="font-sans text-zinc-400">mov</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                Akses daftar tontonan pribadi & beri ulasan film favorit Anda.
            </p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Email Field -->
            <x-input 
                name="email" 
                type="email" 
                label="Alamat Email" 
                icon="mail" 
                :value="old('email')" 
                placeholder="nama@email.com" 
                required 
                autofocus 
            />

            <!-- Password Field -->
            <x-input 
                name="password" 
                type="password" 
                label="Kata Sandi" 
                icon="lock" 
                placeholder="••••••••" 
                required 
            />

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs text-zinc-400 pt-1">
                <label class="flex items-center gap-2.5 cursor-pointer hover:text-zinc-300 transition-colors">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded-md bg-zinc-900 border-white/15 text-zinc-100 focus:ring-0 focus:ring-offset-0 cursor-pointer accent-white">
                    <span class="font-medium">Ingat Saya</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:shadow-white/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer group mt-2">
                <span>Masuk Sekarang</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform"></i>
            </button>
        </form>

        <!-- Switcher to Register -->
        <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs text-zinc-400">
            Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-white hover:underline transition-colors ml-1">Daftar Akun Baru</a>
        </div>

    </div>
</div>
@endsection
