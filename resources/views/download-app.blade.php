@extends('layouts.app')

@section('title', 'Download Mobile App - faiilmov')
@section('hide_sidebar', true)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-14 space-y-16">

    <!-- Hero Section: Headline, APK Download & Phone Mockup -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
        
        <!-- Left Column: Copywriting & APK Download Button -->
        <div class="lg:col-span-7 space-y-6 text-center lg:text-left">

            <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight">
                Nikmati Sinema & Watch Party <br class="hidden sm:inline">
                <span class="text-amber-400">Langsung dari HP Android Anda</span>
            </h1>

            <p class="text-sm sm:text-base text-zinc-300 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                Aplikasi mobile resmi <strong class="text-white">faiilmov</strong> kini tersedia untuk perangkat Android! Dapatkan pengalaman streaming yang jauh lebih halus, gesture player kustom, serta notifikasi Nonton Bareng real-time di genggaman Anda.
            </p>

            <!-- Direct APK Download CTA Box -->
            <div class="pt-2 space-y-4">
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    @if($apkFile)
                        <!-- Primary Direct APK Download Button -->
                        <a href="{{ $apkFile['url'] }}" download target="_blank"
                           class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black font-extrabold text-base transition-all duration-300 shadow-xl shadow-amber-500/20 hover:shadow-amber-500/40 hover:-translate-y-0.5 flex items-center justify-center gap-3 border border-amber-300/40">
                            <i data-lucide="download" class="w-6 h-6 stroke-[2.5]"></i>
                            <div class="text-left leading-tight">
                                <div>Unduh APK Android</div>
                                <div class="text-[11px] font-medium opacity-80">v{{ $versionData['latest_version'] }} • {{ $apkFile['size'] }}</div>
                            </div>
                        </a>
                    @else
                        <!-- Coming Soon / Segera Hadir Section -->
                        <div class="w-full max-w-lg space-y-4 text-left">
                            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-xs shadow-lg shadow-amber-500/5">
                                <i data-lucide="clock" class="w-4 h-4 text-amber-400"></i>
                                <span>Aplikasi Mobile - Segera Hadir (Coming Soon)</span>
                            </div>

                            <div class="p-5 rounded-2xl bg-zinc-900/80 border border-white/10 space-y-3">
                                <div class="text-sm font-bold text-white flex items-center gap-2">
                                    <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
                                    <span>Dapatkan Notifikasi Rilis Pertama</span>
                                </div>
                                <p class="text-xs text-zinc-400 leading-relaxed">
                                    Aplikasi Android <strong class="text-white">Faiilmov</strong> sedang disiapkan. Masukkan email Anda untuk mendapatkan notifikasi langsung begitu file APK dirilis.
                                </p>
                                <div x-data="notifyForm()" class="space-y-2">
                                    <template x-if="!success">
                                        <form @submit.prevent="submitEmail()" class="flex flex-col sm:flex-row gap-2">
                                            <input type="email" x-model="email" placeholder="Masukkan alamat email Anda..." required
                                                   class="w-full px-4 py-2.5 rounded-xl bg-zinc-950 border border-white/15 text-white text-xs placeholder:text-zinc-500 focus:outline-none focus:border-amber-500 transition-all">
                                            <button type="submit" :disabled="loading"
                                                    class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs shrink-0 transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                                                <span x-show="!loading">Beri Tahu Saya</span>
                                                <span x-show="loading" class="flex items-center gap-1">
                                                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                                </span>
                                            </button>
                                        </form>
                                    </template>
                                    <template x-if="success">
                                        <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-medium flex items-center gap-2">
                                            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                                            <span x-text="message"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Build Info Badge -->
                    <div class="text-left text-xs text-zinc-400 bg-zinc-900/80 border border-white/10 px-4 py-3 rounded-2xl flex items-center gap-3">
                        <i data-lucide="smartphone" class="w-5 h-5 text-amber-400 shrink-0"></i>
                        <div>
                            <div class="font-bold text-white">Android 5.0+ (ARM64)</div>
                            <div class="text-[11px] text-zinc-400">Build {{ $versionData['latest_build_number'] }} • File APK Resmi</div>
                        </div>
                    </div>
                </div>

                <!-- Release Notes Preview -->
                @if(!empty($versionData['release_notes']))
                    <div class="p-4 rounded-2xl bg-zinc-900/60 border border-white/10 text-left max-w-2xl mx-auto lg:mx-0 space-y-1.5">
                        <div class="flex items-center gap-2 text-xs font-bold text-amber-400">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                            <span>Catatan Pembaruan (Release Notes v{{ $versionData['latest_version'] }}):</span>
                        </div>
                        <p class="text-xs text-zinc-300 whitespace-pre-line leading-relaxed pl-6">{{ $versionData['release_notes'] }}</p>
                    </div>
                @endif
            </div>

            <!-- Store Badges (Segera Hadir) -->
            <div class="pt-4 border-t border-white/10">
                <p class="text-xs text-zinc-400 font-medium mb-3 uppercase tracking-wider">Toko Aplikasi Resmi (Segera Hadir)</p>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 opacity-60">
                    
                    <!-- App Store Disabled -->
                    <div class="cursor-not-allowed pointer-events-none transition-opacity">
                        <img src="{{ asset('images/app-store-badge.png') }}" alt="Download on the App Store" class="h-10 sm:h-11 w-auto object-contain">
                    </div>

                    <!-- Google Play Disabled -->
                    <div class="cursor-not-allowed pointer-events-none transition-opacity">
                        <img src="{{ asset('images/google-play-badge.png') }}" alt="GET IT ON Google Play" class="h-10 sm:h-11 w-auto object-contain">
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


    <!-- Section 3: Cara Install APK Android -->
    <div class="p-8 rounded-3xl bg-zinc-900/80 border border-white/10 space-y-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                <i data-lucide="help-circle" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Panduan Cara Menginstall File APK di Android</h3>
                <p class="text-xs text-zinc-400">Ikuti langkah sederhana ini jika perangkat Anda meminta izin saat pemasangan.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-zinc-300">
            <div class="p-4 rounded-2xl bg-zinc-950/80 border border-white/5 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-400/20 text-amber-400 flex items-center justify-center text-[11px]">1</span>
                    Unduh File APK
                </div>
                <p class="text-zinc-400 leading-relaxed">Klik tombol <strong>"Unduh APK Android"</strong> di atas dan tunggu hingga proses unduhan selesai di browser HP Anda.</p>
            </div>

            <div class="p-4 rounded-2xl bg-zinc-950/80 border border-white/5 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-400/20 text-amber-400 flex items-center justify-center text-[11px]">2</span>
                    Izinkan Sumber Tidak Dikenal
                </div>
                <p class="text-zinc-400 leading-relaxed">Buka file APK yang diunduh. Jika muncul peringatan keamanan, aktifkan izin <em>"Izinkan dari sumber ini"</em> (Allow Unknown Sources).</p>
            </div>

            <div class="p-4 rounded-2xl bg-zinc-950/80 border border-white/5 space-y-2">
                <div class="font-bold text-amber-400 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-400/20 text-amber-400 flex items-center justify-center text-[11px]">3</span>
                    Pasang & Buka Aplikasi
                </div>
                <p class="text-zinc-400 leading-relaxed">Klik <strong>Install</strong>. Setelah selesai, buka aplikasi faiilmov dan nikmati kemudahan streaming di genggaman Anda!</p>
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
