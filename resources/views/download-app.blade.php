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

        <!-- Right Column: Interactive Phone Screen Mockup Preview -->
        <div class="lg:col-span-5 flex justify-center">
            <div class="relative w-64 sm:w-72 aspect-[9/19] rounded-[3rem] bg-dark-950 p-3 border-4 border-zinc-800 shadow-2xl shadow-amber-500/10 overflow-hidden group">
                
                <!-- Dynamic Glassmorphic Ambient Glow -->
                <div class="absolute -top-16 -left-16 w-32 h-32 rounded-full bg-amber-500/20 blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-16 -right-16 w-32 h-32 rounded-full bg-indigo-500/20 blur-2xl pointer-events-none"></div>

                <!-- Phone Notch / Dynamic Island -->
                <div class="absolute top-5 inset-x-0 mx-auto w-24 h-4 bg-zinc-900 rounded-full z-30 flex items-center justify-center gap-1.5 border border-white/5">
                    <span class="w-2 h-2 rounded-full bg-dark-950"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-700"></span>
                </div>

                <!-- Inner Phone Display Screen -->
                <div class="w-full h-full rounded-[2.2rem] bg-dark-950 overflow-hidden border border-white/10 relative flex flex-col pt-7 px-3.5 pb-4 space-y-3">
                    
                    <!-- App Status Bar Header -->
                    <div class="flex items-center justify-between text-white border-b border-white/10 pb-2">
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded-lg bg-amber-400 text-zinc-950 flex items-center justify-center text-[10px] font-black">f</div>
                            <span class="font-serif font-bold text-xs">faiilmov</span>
                        </div>
                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30">v1.0 Beta</span>
                    </div>

                    <!-- Mini Hero Player Preview Card -->
                    <div class="relative aspect-video rounded-xl overflow-hidden bg-zinc-800 border border-white/10 shadow">
                        <div class="absolute inset-0 bg-dark-950/70 z-10"></div>
                        <div class="absolute inset-0 flex items-center justify-center z-20">
                            <div class="w-8 h-8 rounded-full bg-amber-400/90 text-zinc-950 flex items-center justify-center shadow-lg">
                                <i data-lucide="play" class="w-4 h-4 fill-zinc-950 ml-0.5"></i>
                            </div>
                        </div>
                        <div class="absolute bottom-1.5 left-2 z-20 text-[9px] font-bold text-white truncate max-w-[120px]">
                            Watch Party Active
                        </div>
                    </div>

                    <!-- Feature Cards Inside Mockup -->
                    <div class="space-y-2 flex-1 overflow-hidden">
                        <div class="p-2 rounded-xl bg-white/5 border border-white/5 flex items-center gap-2">
                            <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                            <span class="text-[10px] text-zinc-300 font-medium truncate">Native Flutter 60fps</span>
                        </div>
                        <div class="p-2 rounded-xl bg-white/5 border border-white/5 flex items-center gap-2">
                            <i data-lucide="popcorn" class="w-3.5 h-3.5 text-indigo-400 shrink-0"></i>
                            <span class="text-[10px] text-zinc-300 font-medium truncate">Nobar Sync & Live Chat</span>
                        </div>
                        <div class="p-2 rounded-xl bg-white/5 border border-white/5 flex items-center gap-2">
                            <i data-lucide="sliders" class="w-3.5 h-3.5 text-emerald-400 shrink-0"></i>
                            <span class="text-[10px] text-zinc-300 font-medium truncate">Swipe Gesture Control</span>
                        </div>
                    </div>

                    <!-- Bottom Nav Mockup -->
                    <div class="pt-2 border-t border-white/10 grid grid-cols-4 gap-1 text-center">
                        <div class="text-amber-400 flex flex-col items-center">
                            <i data-lucide="house" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="text-zinc-500 flex flex-col items-center">
                            <i data-lucide="compass" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="text-zinc-500 flex flex-col items-center">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="text-zinc-500 flex flex-col items-center">
                            <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>

    <!-- Section 2: Planned Key Mobile Features -->
    <div class="space-y-8 border-t border-white/10 pt-12">
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
    </div>



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
