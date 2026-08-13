@extends('layouts.app')

@section('title', 'Reset Password — faiilmov')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 relative z-10">
    <div class="w-full max-w-md glass-panel p-8 sm:p-10 rounded-[2.5rem] border border-white/12 shadow-2xl relative overflow-hidden">

        <!-- Decorative Ambient -->
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-[#e4e2dd] p-2 flex items-center justify-center mx-auto mb-4 shadow-xl shadow-white/5 border border-white/20">
                <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="w-full h-full object-contain">
            </div>
            <span class="text-[11px] uppercase tracking-widest text-zinc-400 font-semibold mb-1 block">Kata Sandi Baru</span>
            <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
                Buat password <span class="font-sans text-zinc-400">baru</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                Masukkan password baru Anda. Gunakan minimal 8 karakter.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 px-4 py-3.5 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-400 mt-0.5 shrink-0"></i>
                <div class="text-xs text-red-300 leading-relaxed">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <x-input
                name="email"
                type="email"
                label="Alamat Email"
                icon="mail"
                :value="old('email', $email ?? '')"
                placeholder="nama@email.com"
                required
            />

            <x-input
                name="password"
                type="password"
                label="Password Baru"
                icon="lock"
                placeholder="Min. 8 karakter"
                required
            />

            <x-input
                name="password_confirmation"
                type="password"
                label="Konfirmasi Password Baru"
                icon="lock"
                placeholder="Ulangi password baru"
                required
            />

            <button type="submit" id="btn-reset-submit" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:shadow-white/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer group mt-2">
                <span>Simpan Password Baru</span>
                <i data-lucide="shield-check" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs text-zinc-400">
            Kembali ke <a href="{{ route('login') }}" class="font-bold text-white hover:underline transition-colors ml-1">Halaman Masuk</a>
        </div>
    </div>
</div>
@endsection
