@extends('layouts.app')

@section('title', 'Verifikasi Email — faiilmov')

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
            <span class="text-[11px] uppercase tracking-widest text-zinc-400 font-semibold mb-1 block">Satu langkah lagi</span>
            <h1 class="font-serif font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
                Verifikasi <span class="font-sans text-zinc-400">email</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                Kami telah mengirimkan link verifikasi ke <strong class="text-zinc-300">{{ auth()->user()->email }}</strong>.
                Cek inbox atau folder spam Anda.
            </p>
        </div>

        @if (session('status') === 'verification-link-sent')
            <div class="mb-5 px-4 py-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-start gap-3">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 mt-0.5 shrink-0"></i>
                <p class="text-xs text-emerald-300 leading-relaxed">Link verifikasi baru telah dikirim ke email Anda.</p>
            </div>
        @endif

        <!-- Benefits list -->
        <div class="mb-6 space-y-2">
            <p class="text-[11px] uppercase tracking-widest text-zinc-500 font-semibold mb-3">Setelah verifikasi, Anda bisa:</p>
            <div class="flex items-center gap-3 text-xs text-zinc-400">
                <div class="w-5 h-5 rounded-full bg-emerald-500/15 border border-emerald-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="star" class="w-3 h-3 text-emerald-400"></i>
                </div>
                <span>Menulis ulasan & memberi rating film</span>
            </div>
            <div class="flex items-center gap-3 text-xs text-zinc-400">
                <div class="w-5 h-5 rounded-full bg-blue-500/15 border border-blue-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="w-3 h-3 text-blue-400"></i>
                </div>
                <span>Membuat sesi Nonton Bareng (Watch Party)</span>
            </div>
            <div class="flex items-center gap-3 text-xs text-zinc-400">
                <div class="w-5 h-5 rounded-full bg-purple-500/15 border border-purple-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="smartphone" class="w-3 h-3 text-purple-400"></i>
                </div>
                <span>Sinkronisasi watchlist ke aplikasi mobile</span>
            </div>
        </div>

        <!-- Resend form -->
        <form id="form-resend-verify" action="{{ route('verification.send') }}" method="POST" class="space-y-4">
            @csrf
            <button type="submit" id="btn-resend-verify" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 hover:shadow-white/10 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer group">
                <i data-lucide="mail" class="w-4 h-4"></i>
                <span>Kirim Ulang Email Verifikasi</span>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/10 text-center text-xs text-zinc-400">
            Bukan akun Anda?
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="font-bold text-white hover:underline transition-colors ml-1 cursor-pointer">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Simple cooldown to prevent spam click on resend button
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-resend-verify');
    const btn  = document.getElementById('btn-resend-verify');
    if (!form || !btn) return;

    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Mengirim...</span>';
        if (window.lucide) lucide.createIcons();

        setTimeout(function () {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="mail" class="w-4 h-4"></i><span>Kirim Ulang Email Verifikasi</span>';
            if (window.lucide) lucide.createIcons();
        }, 60000); // 60 second cooldown on client-side
    });
});
</script>
@endsection
