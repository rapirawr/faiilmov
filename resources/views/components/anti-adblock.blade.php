@if(\App\Services\AdService::isAntiAdblockEnabled())
    @php
        $title = \App\Models\Setting::get('ads_anti_adblock_title', 'Mohon Nonaktifkan Adblock');
        $message = \App\Models\Setting::get('ads_anti_adblock_message', 'Dukung kami agar tetap bisa menyajikan film & anime berkualitas secara gratis dengan menonaktifkan pemblokir iklan (Adblock) Anda.');
    @endphp

    <div x-data="{
            adblockDetected: false,
            dismissed: false,
            checkAdblock() {
                // Check if dismissed in this session
                if (sessionStorage.getItem('faiilmov_anti_adblock_dismissed')) {
                    return;
                }

                // Bait Element Detection
                setTimeout(() => {
                    const bait = document.createElement('div');
                    bait.className = 'adsbox pub_300x250 pub_300x250m pub_728x90 text-ad textAd text_ad text_ads text-ads text-ad-links ad-banner ad-unit';
                    bait.style.cssText = 'position: absolute !important; left: -9999px !important; top: -9999px !important; width: 1px !important; height: 1px !important;';
                    bait.innerHTML = '&nbsp;';
                    document.body.appendChild(bait);

                    setTimeout(() => {
                        const isBlocked = (
                            bait.offsetParent === null ||
                            bait.offsetHeight === 0 ||
                            bait.offsetLeft === 0 ||
                            bait.offsetTop === 0 ||
                            bait.offsetWidth === 0 ||
                            bait.clientHeight === 0 ||
                            bait.clientWidth === 0 ||
                            window.getComputedStyle(bait).display === 'none' ||
                            window.getComputedStyle(bait).visibility === 'hidden'
                        );

                        if (isBlocked) {
                            this.adblockDetected = true;
                        }
                        bait.remove();
                    }, 250);
                }, 1000);
            },
            dismiss() {
                this.dismissed = true;
                this.adblockDetected = false;
                sessionStorage.setItem('faiilmov_anti_adblock_dismissed', '1');
            },
            reloadPage() {
                window.location.reload();
            }
         }"
         x-init="checkAdblock()"
         x-show="adblockDetected && !dismissed"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/80 backdrop-blur-xl transition-all duration-300"
         style="display: none;">

        <div class="relative w-full max-w-md p-6 sm:p-8 rounded-3xl glass-panel border border-amber-500/30 shadow-2xl bg-zinc-950/95 text-center space-y-5 animate-in fade-in zoom-in-95 duration-300">
            
            <!-- Shield / Warning Icon Glow -->
            <div class="w-16 h-16 mx-auto rounded-3xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shadow-lg ring-4 ring-amber-500/10 animate-pulse">
                <i data-lucide="shield-alert" class="w-8 h-8"></i>
            </div>

            <!-- Title & Description -->
            <div class="space-y-2">
                <h3 class="text-xl font-serif font-bold text-white tracking-tight">
                    {{ $title }}
                </h3>
                <p class="text-xs text-zinc-300 leading-relaxed max-w-sm mx-auto">
                    {{ $message }}
                </p>
            </div>

            <!-- Quick Instructions Box -->
            <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 text-left text-[11px] text-zinc-400 space-y-1.5">
                <p class="font-bold text-zinc-200 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Cara mematikan adblock:</span>
                </p>
                <ol class="list-decimal list-inside space-y-1 pl-1 text-zinc-400">
                    <li>Klik ikon ekstensi Adblock / uBlock di pojok browser Anda.</li>
                    <li>Pilih opsi <strong>"Pause on this site"</strong> atau matikan proteksi untuk domain ini.</li>
                    <li>Muat ulang (refresh) halaman untuk melanjutkan.</li>
                </ol>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <button type="button" 
                        @click="reloadPage()" 
                        class="w-full sm:w-auto px-6 py-2.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span>Sudah Saya Matikan, Refresh</span>
                </button>

                <button type="button" 
                        @click="dismiss()" 
                        class="w-full sm:w-auto px-4 py-2.5 rounded-2xl glass-card text-zinc-400 hover:text-white border border-white/10 text-xs transition-colors cursor-pointer">
                    <span>Lanjutkan Nonton</span>
                </button>
            </div>

        </div>
    </div>
@endif
