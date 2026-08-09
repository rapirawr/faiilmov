@extends('layouts.app')

@section('title', 'Download Mobile App - faiilmov')
@section('hide_sidebar', true)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-14 space-y-16">

    <!-- Hero Section: Headline & Phone Mockup -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
        
        <!-- Left Column: Copywriting & Coming Soon Status -->
        <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold shadow-lg shadow-amber-500/5">
                <span>COMING SOON • FLUTTER MOBILE APP</span>
            </div>

            <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight">
                Nikmati Sinema & Watch Party <br class="hidden sm:inline">
                <span class="text-amber-400">Langsung dari HP Anda</span>
            </h1>

            <p class="text-sm sm:text-base text-zinc-300 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                Aplikasi mobile resmi <strong class="text-white">faiilmov</strong> sedang dalam tahap penyempurnaan akhir menggunakan kerangka <strong class="text-amber-400">Flutter</strong>. Dapatkan pengalaman streaming yang jauh lebih halus, gesture player kustom, serta notifikasi Nonton Bareng real-time di genggaman Anda.
            </p>

            <!-- Store Badges (Disabled / Placeholder State) -->
            <div class="pt-2">
                <p class="text-xs text-zinc-500 font-medium mb-3 uppercase tracking-wider">Segera Hadir di Toko Aplikasi</p>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4">
                    
                    <!-- App Store Disabled -->
                    <div class="cursor-not-allowed pointer-events-none transition-opacity">
                        <img src="{{ asset('images/app-store-badge.png') }}" alt="Download on the App Store" class="h-11 sm:h-12 w-auto object-contain">
                    </div>

                    <!-- Google Play Disabled -->
                    <div class="cursor-not-allowed pointer-events-none transition-opacity">
                        <img src="{{ asset('images/google-play-badge.png') }}" alt="GET IT ON Google Play" class="h-11 sm:h-12 w-auto object-contain">
                    </div>

                </div>
            </div>
        </div>

        <!-- Right Column: Dual Phone Screen Mockup Preview (2 Foto HP) -->
        <div class="lg:col-span-5 flex justify-center items-center">
            <div class="relative w-full max-w-sm sm:max-w-md flex justify-center items-center py-6 group">
                <!-- Back Phone Mockup (Phone 2 - Tilted Left) -->
                <div class="relative w-48 sm:w-60 -mr-16 sm:-mr-24 transform -rotate-12 -translate-y-4 hover:-rotate-6 hover:translate-y-0 transition-all duration-500 z-10 filter brightness-90 contrast-105">
                    <img src="{{ asset('images/mobile-app-mockup-2.webp') }}" 
                         alt="faiilmov Mobile App Screenshot 2" 
                         class="w-full h-auto object-contain drop-shadow-xl opacity-90">
                </div>

                <!-- Front Phone Mockup (Phone 1 - Tilted Right) -->
                <div class="relative w-52 sm:w-64 z-20 transform rotate-6 translate-y-2 hover:rotate-0 hover:translate-y-0 transition-all duration-500">
                    <img src="{{ asset('images/mobile-app-mockup.webp') }}" 
                         alt="faiilmov Mobile App Screenshot 1" 
                         class="w-full h-auto object-contain drop-shadow-2xl">
                </div>

            </div>
        </div>

    </div>

    <!-- Section 2: Planned Key Mobile Features -->
    {{-- <div class="space-y-8 border-t border-white/10 pt-12">
        <div class="text-center max-w-xl mx-auto space-y-2">
            <span class="text-[10px] uppercase tracking-widest text-amber-400 font-bold">Fitur Utama Versi Mobile</span>
            <h2 class="font-serif font-bold text-2xl sm:text-3xl text-white">Kenapa Harus Punya App Mobile faiilmov?</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            
            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-white/20 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                    <i data-lucide="smartphone" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Performa Native 60fps</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Dibangun dengan Flutter untuk navigasi secepat kilat, animasi halus, dan konsumsi memori yang efisien.</p>
            </div>

            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-white/20 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <i data-lucide="popcorn" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Watch Party On-the-Go</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Nonton bareng dari mana saja. Sinkronisasi detik tayangan & obrolan suara/chat langsung dari HP.</p>
            </div>

            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-white/20 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i data-lucide="sliders" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Kustom Gesture Player</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Atur kecerahan & volume suara cukup dengan usapan jari di layar player, plus double-tap skip 10 detik.</p>
            </div>

            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-white/20 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Push Notifications</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Notifikasi langsung ketika teman mengundang Anda ke ruang Nobar atau saat episode serial favorit rilis.</p>
            </div>

        </div>
    </div> --}}



</div>

@push('scripts')
<script>
function notifyForm() {
    return {
        email: '{{ Auth::check() ? Auth::user()->email : "" }}',
        loading: false,
        success: false,
        message: '',

        async submitEmail() {
            if (!this.email.trim()) return;

            this.loading = true;
            this.message = '';

            try {
                const response = await fetch('{{ route("download.notify-me") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ email: this.email })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.success = true;
                    this.message = data.message;
                } else {
                    this.success = false;
                    this.message = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
                }
            } catch (error) {
                this.success = false;
                this.message = 'Gagal mengirimkan permintaan. Periksa koneksi internet Anda.';
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            }
        }
    };
}
</script>
@endpush
@endsection
