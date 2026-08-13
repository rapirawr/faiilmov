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
            <div class="w-16 h-16 rounded-2xl bg-[#e4e2dd] p-2 flex items-center justify-center mx-auto mb-4 shadow-xl shadow-white/5 border border-white/20">
                <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="w-full h-full object-contain">
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

            <!-- Remember Me + Forgot Password -->
            <div class="flex items-center justify-between text-xs text-zinc-400 pt-1">
                <label class="flex items-center gap-2.5 cursor-pointer hover:text-zinc-300 transition-colors">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded-md bg-zinc-900 border-white/15 text-zinc-100 focus:ring-0 focus:ring-offset-0 cursor-pointer accent-white">
                    <span class="font-medium">Ingat Saya</span>
                </label>
                <a href="{{ route('password.request') }}" class="font-medium hover:text-zinc-200 transition-colors">Lupa password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:shadow-white/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer group mt-2">
                <span>Masuk Sekarang</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform"></i>
            </button>
        </form>

        <!-- Social Login Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-white/10"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-transparent px-3 text-[11px] text-zinc-500 uppercase tracking-widest">atau masuk dengan</span>
            </div>
        </div>

        <!-- Social Login Buttons -->
        <div>
            <a href="{{ route('social.redirect', 'google') }}"
               id="btn-login-google"
               class="w-full flex items-center justify-center gap-2.5 py-3.5 px-4 rounded-2xl border border-white/10 bg-white/5 hover:bg-white/10 text-white text-xs sm:text-sm font-semibold transition-all duration-200 hover:border-white/20 active:scale-[0.98]">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <span>Masuk dengan Google</span>
            </a>
        </div>

        <!-- Switcher to Register -->
        <div class="mt-6 pt-6 border-t border-white/10 text-center text-xs text-zinc-400">
            Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-white hover:underline transition-colors ml-1">Daftar Akun Baru</a>
        </div>

    </div>
</div>
@endsection
