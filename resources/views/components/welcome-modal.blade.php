@if($shouldShowWelcomeModal)
<div x-data="welcomeModal({
        isGuest: {{ Auth::check() ? 'false' : 'true' }},
        dismissUrl: '{{ route('welcome-modal.dismiss') }}'
     })"
     x-show="show"
     @keydown.window.escape="closeModal()"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
     style="display: none;">

    <!-- Backdrop Blur Overlay -->
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeModal()"
         class="fixed inset-0 bg-dark-950/80 backdrop-blur-md"></div>

    <!-- Glassmorphism Modal Card -->
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="relative w-full max-w-lg glass-panel p-6 sm:p-8 rounded-[2.5rem] border border-white/15 shadow-2xl overflow-hidden z-10 my-8">

        <!-- Close (X) Button -->
        <button type="button" @click="closeModal()" class="absolute top-5 right-5 text-zinc-400 hover:text-white p-2 rounded-xl hover:bg-white/5 transition-colors cursor-pointer focus:outline-none">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <!-- Modal Header / Brand Logo -->
        <div class="text-center mb-5">
            <div class="w-16 h-16 rounded-2xl bg-[#e4e2dd] border border-white/20 p-2 flex items-center justify-center mx-auto mb-3 shadow-xl">
                <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="w-full h-full object-contain">
            </div>
            <span class="text-[10px] uppercase tracking-widest text-amber-400 font-bold block mb-1">Streaming & Watch Party</span>
            <h3 class="font-serif font-extrabold text-2xl text-white tracking-tight">
                Selamat Datang di <span class="font-serif text-white">faiil</span><span class="font-sans text-zinc-400">mov</span>
            </h3>
            <p class="text-xs text-zinc-400 mt-1.5 leading-relaxed max-w-sm mx-auto">
                Akses ribuan film, TV series, dan ruang Nonton Bareng langsung dari browser Anda.
            </p>
        </div>

        <!-- Key Benefit Points (Cards) -->
        <div class="space-y-2.5 mb-6">
            
            <div class="flex items-start gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-white mb-0.5">Accountless Access (Bebas Akses Tanpa Login)</h4>
                    <p class="text-[11px] text-zinc-400 leading-snug">Langsung cari dan tonton film atau series kapan saja tanpa wajib membuat akun.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors">
                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                    <i data-lucide="popcorn" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-white mb-0.5">Watch Party Real-Time</h4>
                    <p class="text-[11px] text-zinc-400 leading-snug">Buat ruang nobar bersama teman dengan sinkronisasi menit tayang & live chat.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors">
                <div class="w-8 h-8 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center shrink-0">
                    <i data-lucide="film" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-white mb-0.5">Katalog Film & Series 4K</h4>
                    <p class="text-[11px] text-zinc-400 leading-snug">Koleksi sinema terbaru dan serial televisi terpopuler dengan kualitas hingga 4K.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 hover:border-white/20 transition-colors">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <i data-lucide="bookmark-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-white mb-0.5">Simpan Watchlist & Progress Tontonan</h4>
                    <p class="text-[11px] text-zinc-400 leading-snug">Login untuk menyimpan daftar tontonan pribadi dan melanjutkan posisi menit tontonan.</p>
                </div>
            </div>

        </div>

        <!-- Actions / CTAs -->
        @auth
            <button type="button" @click="closeModal()" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/5 transition-all cursor-pointer flex items-center justify-center gap-2">
                <span>Mengerti & Mulai Jelajah</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        @else
            <div class="space-y-2.5">
                <a href="{{ route('register') }}" class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm shadow-xl shadow-white/10 transition-all cursor-pointer flex items-center justify-center gap-2 block text-center">
                    <span>Daftar / Login Akun</span>
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                </a>
                <button type="button" @click="closeModal()" class="w-full py-2.5 text-xs text-zinc-400 hover:text-white transition-colors cursor-pointer text-center block font-medium">
                    Lanjutkan Tanpa Login (Tamu)
                </button>
            </div>
        @endauth

    </div>
</div>

<script>
function welcomeModal(config) {
    return {
        show: false,
        isGuest: config.isGuest,
        dismissUrl: config.dismissUrl,

        init() {
            setTimeout(() => {
                this.show = true;
                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            }, 600);
        },

        async closeModal() {
            this.show = false;
            if (!this.isGuest) {
                try {
                    await fetch(this.dismissUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });
                } catch (e) {
                    console.error('Failed to dismiss welcome modal', e);
                }
            }
        }
    };
}
</script>
@endif
