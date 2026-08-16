@extends('layouts.app')

@section('title', 'Lupa Password | faiilmov')

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
            <span class="text-[11px] uppercase tracking-widest text-zinc-400 font-semibold mb-1 block">Reset Kata Sandi</span>
            <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
                Lupa <span class="font-sans text-zinc-400">password?</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                Masukkan email yang terdaftar. Kami akan mengirimkan link untuk mereset kata sandi Anda.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 px-4 py-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-start gap-3">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 mt-0.5 shrink-0"></i>
                <p class="text-xs text-emerald-300 leading-relaxed">{{ session('status') }}</p>
            </div>
        @endif

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

        <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
            @csrf

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

            <button type="submit" id="btn-forgot-submit" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:shadow-white/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer group mt-2">
                <span>Kirim Link Reset</span>
                <i data-lucide="send" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs text-zinc-400">
            Ingat password? <a href="{{ route('login') }}" class="font-bold text-white hover:underline transition-colors ml-1">Masuk Sekarang</a>
        </div>
    </div>
</div>
@endsection
