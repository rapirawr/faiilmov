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
                    @elseif(!empty($versionData['download_url']) && filter_var($versionData['download_url'], FILTER_VALIDATE_URL))
                        <a href="{{ $versionData['download_url'] }}" download target="_blank"
                           class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black font-extrabold text-base transition-all duration-300 shadow-xl shadow-amber-500/20 hover:shadow-amber-500/40 hover:-translate-y-0.5 flex items-center justify-center gap-3 border border-amber-300/40">
                            <i data-lucide="download" class="w-6 h-6 stroke-[2.5]"></i>
                            <div class="text-left leading-tight">
                                <div>Unduh APK Android</div>
                                <div class="text-[11px] font-medium opacity-80">v{{ $versionData['latest_version'] }}</div>
                            </div>
                        </a>
                    @else
                        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs space-y-2 max-w-md">
                            <div class="flex items-center gap-2 font-bold">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-400"></i>
                                <span>File APK v{{ $versionData['latest_version'] }} Belum Diunggah</span>
                            </div>
                            <p class="text-zinc-300 leading-relaxed text-[11px]">
                                Silakan upload file <code>.apk</code> melalui <a href="{{ route('admin.app_release.index') }}" class="underline font-bold text-amber-400">Menu Admin Rilis APK</a> atau letakkan file di <code>public/apk-files/</code>.
                            </p>
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

    <!-- Section 2: Key Mobile Features -->
    <div class="space-y-8 border-t border-white/10 pt-12">
        <div class="text-center max-w-xl mx-auto space-y-2">
            <span class="text-[10px] uppercase tracking-widest text-amber-400 font-bold">Fitur Utama Versi Mobile</span>
            <h2 class="font-serif font-bold text-2xl sm:text-3xl text-white">Kenapa Harus Menggunakan App Mobile faiilmov?</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            
            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-amber-500/30 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                    <i data-lucide="smartphone" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Performa Native 60fps</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Dibangun dengan Flutter untuk navigasi secepat kilat, animasi halus, dan konsumsi memori yang efisien.</p>
            </div>

            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-amber-500/30 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <i data-lucide="popcorn" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Watch Party On-the-Go</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Nonton bareng dari mana saja. Sinkronisasi detik tayangan & obrolan obrolan langsung dari HP.</p>
            </div>

            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-amber-500/30 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i data-lucide="sliders" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Kustom Gesture Player</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Atur kecerahan & volume suara cukup dengan usapan jari di layar player, plus double-tap skip 10 detik.</p>
            </div>

            <div class="glass-panel p-6 rounded-3xl border border-white/10 space-y-3 hover:border-amber-500/30 transition-all">
                <div class="w-10 h-10 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Push Notifications</h3>
                <p class="text-xs text-zinc-400 leading-relaxed">Notifikasi langsung ketika teman mengundang Anda ke ruang Nobar atau saat versi aplikasi terbaru rilis.</p>
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
