@if(\App\Services\AdService::isAntiAdblockEnabled())
    @php
        $title = \App\Models\Setting::get('ads_anti_adblock_title', 'Mohon Nonaktifkan Adblock');
        $message = \App\Models\Setting::get('ads_anti_adblock_message', 'Dukung kami agar tetap bisa menyajikan film & anime berkualitas secara gratis dengan menonaktifkan pemblokir iklan (Adblock) Anda.');
    @endphp

    <div id="faiilmov-anti-adblock-wrapper" 
         x-data="antiAdblockSystem()" 
         x-init="initDetector()"
         x-show="isBlocked" 
         x-cloak
         class="fixed inset-0 z-[2147483647] flex items-center justify-center p-4 bg-black/90 backdrop-blur-2xl transition-all duration-300 select-none"
         style="display: none;">

        <div id="faiilmov-anti-adblock-card"
             class="relative w-full max-w-md p-6 sm:p-8 rounded-3xl glass-panel border border-amber-500/40 shadow-2xl bg-zinc-950/95 text-center space-y-5 animate-in fade-in zoom-in-95 duration-300">
            
            <!-- Shield Alert Animated Icon Glow -->
            <div class="w-16 h-16 mx-auto rounded-3xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-lg ring-4 ring-amber-500/10 animate-pulse">
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
            <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 text-left text-[11px] text-zinc-300 space-y-1.5">
                <p class="font-bold text-amber-300 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Cara mematikan adblock:</span>
                </p>
                <ol class="list-decimal list-inside space-y-1 pl-1 text-zinc-400 text-[11px]">
                    <li>Klik ikon ekstensi <strong>Adblock / uBlock / Shields</strong> di browser Anda.</li>
                    <li>Pilih opsi <strong>"Pause on this site"</strong> atau nonaktifkan pemblokir untuk situs ini.</li>
                    <li>Klik tombol <strong>Periksa Kembali</strong> di bawah atau cukup kembali ke tab ini.</li>
                </ol>
            </div>

            <!-- Feedback Notice if still blocked -->
            <div x-show="showStillBlockedWarning" 
                 x-transition
                 class="p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-[11px] font-bold flex items-center justify-center gap-1.5"
                 style="display: none;">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-400 shrink-0"></i>
                <span>Adblock masih terdeteksi aktif. Mohon matikan dan coba lagi.</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <button type="button" 
                        @click="checkAndVerify(true)" 
                        :disabled="isChecking"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-zinc-950 font-bold text-xs transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer shadow-amber-500/20">
                    <span x-show="isChecking" class="w-3.5 h-3.5 border-2 border-zinc-950 border-t-transparent rounded-full animate-spin"></span>
                    <i x-show="!isChecking" data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span x-text="isChecking ? 'Memeriksa...' : 'Periksa Kembali'"></span>
                </button>

                <button type="button" 
                        @click="window.location.reload()" 
                        class="w-full sm:w-auto px-4 py-2.5 rounded-2xl glass-card text-zinc-400 hover:text-white border border-white/10 text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Muat Ulang Halaman</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function antiAdblockSystem() {
            return {
                isBlocked: false,
                isChecking: false,
                showStillBlockedWarning: false,
                observer: null,
                heartbeatInterval: null,

                initDetector() {
                    this.runProbe();

                    // 1. Auto-check on Tab/Window Focus
                    window.addEventListener('focus', () => {
                        if (this.isBlocked) {
                            this.runProbe(false);
                        }
                    });

                    // 2. Periodic Heartbeat Probe every 25 seconds
                    this.heartbeatInterval = setInterval(() => {
                        this.runProbe(false);
                    }, 25000);

                    // 3. Setup Anti-Tamper MutationObserver
                    this.setupAntiTamper();
                },

                async probeNetwork() {
                    return false;
                },

                probeDOMBait() {
                    return new Promise((resolve) => {
                        try {
                            const bait = document.createElement('div');
                            bait.id = 'ad-banner-detector-' + Math.random().toString(36).substring(7);
                            bait.className = 'adsbox pub_300x250 pub_300x250m pub_728x90 text-ad textAd text_ad text_ads text-ads text-ad-links ad-banner ad-unit ad-zone ad_wrapper';
                            bait.style.cssText = 'position: absolute !important; left: -9999px !important; top: -9999px !important; width: 10px !important; height: 10px !important; z-index: -100 !important;';
                            bait.innerHTML = '&nbsp;';
                            document.body.appendChild(bait);

                            setTimeout(() => {
                                let blocked = false;
                                try {
                                    const style = window.getComputedStyle(bait);
                                    blocked = (
                                        bait.offsetParent === null ||
                                        bait.offsetHeight === 0 ||
                                        bait.offsetLeft === 0 ||
                                        bait.offsetTop === 0 ||
                                        bait.offsetWidth === 0 ||
                                        bait.clientHeight === 0 ||
                                        bait.clientWidth === 0 ||
                                        style.display === 'none' ||
                                        style.visibility === 'hidden' ||
                                        style.opacity === '0'
                                    );
                                } catch (err) {
                                    blocked = true;
                                }
                                bait.remove();
                                resolve(blocked);
                            }, 150);
                        } catch (e) {
                            resolve(true);
                        }
                    });
                },

                async runProbe(showWarningIfBlocked = false) {
                    // Method 1: DOM Bait Check
                    const domBlocked = await this.probeDOMBait();
                    
                    // Method 2: Network Fetch Check
                    let netBlocked = false;
                    if (!domBlocked) {
                        netBlocked = await this.probeNetwork();
                    }

                    const detected = domBlocked || netBlocked;

                    if (detected) {
                        this.setBlockedState(true);
                        if (showWarningIfBlocked) {
                            this.showStillBlockedWarning = true;
                            setTimeout(() => this.showStillBlockedWarning = false, 4000);
                        }
                    } else {
                        this.setBlockedState(false);
                    }

                    return !detected;
                },

                async checkAndVerify(manualClick = false) {
                    this.isChecking = true;
                    this.showStillBlockedWarning = false;

                    // Small delay for UX feel
                    await new Promise(r => setTimeout(r, 600));

                    const clean = await this.runProbe(manualClick);
                    this.isChecking = false;

                    if (clean) {
                        this.showStillBlockedWarning = false;
                    }
                },

                setBlockedState(blocked) {
                    this.isBlocked = blocked;

                    if (blocked) {
                        document.body.style.setProperty('overflow', 'hidden', 'important');
                        window.dispatchEvent(new CustomEvent('faiilmov:adblock-status', { detail: { blocked: true } }));
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                        });
                    } else {
                        document.body.style.removeProperty('overflow');
                        window.dispatchEvent(new CustomEvent('faiilmov:adblock-status', { detail: { blocked: false } }));
                    }
                },

                setupAntiTamper() {
                    const enforceModal = () => {
                        if (!this.isBlocked) return;

                        const wrapper = document.getElementById('faiilmov-anti-adblock-wrapper');
                        if (!wrapper) {
                            // If removed from DOM in DevTools -> Re-trigger reload/enforce
                            window.location.reload();
                            return;
                        }

                        // Check if styles were altered in DevTools
                        const style = window.getComputedStyle(wrapper);
                        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0' || parseInt(style.zIndex, 10) < 1000) {
                            wrapper.style.setProperty('display', 'flex', 'important');
                            wrapper.style.setProperty('visibility', 'visible', 'important');
                            wrapper.style.setProperty('opacity', '1', 'important');
                            wrapper.style.setProperty('z-index', '2147483647', 'important');
                            document.body.style.setProperty('overflow', 'hidden', 'important');
                        }
                    };

                    this.observer = new MutationObserver(() => {
                        enforceModal();
                    });

                    this.observer.observe(document.body, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['style', 'class', 'hidden']
                    });
                }
            };
        }
    </script>
@endif
