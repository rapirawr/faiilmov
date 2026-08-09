<!-- Global Cross-Page Floating Mini Player Component -->
<div x-data="globalMiniPlayer()" 
     x-show="active && !isOnWatchPage"
     x-transition:enter="transition ease-out duration-300 transform translate-y-4 opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200 transform translate-y-4 opacity-0"
     class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 w-72 sm:w-96 aspect-video z-[9999] rounded-2xl shadow-2xl border border-white/25 glass-panel ring-2 ring-white/20 overflow-hidden shadow-black/80 group select-none"
     style="display: none;">
    
    <div class="relative w-full h-full bg-black flex items-center justify-center"
         @mouseenter="showControls = true"
         @mouseleave="showControls = false">

        <!-- Native HTML5 Mini Video Element -->
        <video x-ref="miniVideo" 
               playsinline
               referrerpolicy="no-referrer"
               @timeupdate="currentTime = $refs.miniVideo.currentTime; duration = $refs.miniVideo.duration || duration"
               @ended="closeMiniPlayer()"
               class="w-full h-full object-cover cursor-pointer"
               @click="togglePlay()"></video>

        <!-- Top Floating Header Bar -->
        <div class="absolute top-0 inset-x-0 z-20 bg-gradient-to-b from-black/90 via-black/60 to-transparent p-2.5 flex items-center justify-between gap-2 pointer-events-auto transition-opacity duration-200"
             :class="showControls || !isPlaying ? 'opacity-100' : 'opacity-0'">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-[11px] font-bold text-white truncate" x-text="filmTitle"></span>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <!-- Maximize / Expand Button -->
                <button @click.stop="expandToWatchPage()" 
                        class="p-1 rounded-lg bg-white/15 hover:bg-white/30 text-white transition-colors cursor-pointer"
                        title="Perbesar ke Halaman Nonton">
                    <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
                </button>
                <!-- Close Button -->
                <button @click.stop="closeMiniPlayer()" 
                        class="p-1 rounded-lg bg-white/15 hover:bg-red-500/80 text-white transition-colors cursor-pointer"
                        title="Tutup Pemutar">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <!-- Center Play/Pause Quick Toggle Overlay -->
        <div class="absolute inset-0 z-10 flex items-center justify-center gap-4 pointer-events-none transition-opacity duration-200"
             :class="showControls || !isPlaying ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">
            <button @click.stop="togglePlay()" 
                    class="w-12 h-12 rounded-full glass-panel flex items-center justify-center border border-white/30 text-white shadow-2xl hover:scale-110 active:scale-95 transition-all cursor-pointer bg-black/40 backdrop-blur-md">
                <svg x-show="!isPlaying" class="w-6 h-6 fill-white ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                <svg x-show="isPlaying" class="w-6 h-6 fill-white" viewBox="0 0 24 24" style="display: none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            </button>
        </div>

        <!-- Bottom Controls Overlay (Time & Scrubber) -->
        <div class="absolute bottom-0 inset-x-0 z-20 bg-gradient-to-t from-black/90 via-black/60 to-transparent p-2 flex flex-col gap-1 transition-opacity duration-200 pointer-events-auto"
             :class="showControls || !isPlaying ? 'opacity-100' : 'opacity-0'">
            <!-- Progress Bar -->
            <div class="w-full h-1 bg-white/20 rounded-full overflow-hidden relative cursor-pointer"
                 @click="const rect = $el.getBoundingClientRect(); const pos = ($event.clientX - rect.left) / rect.width; if ($refs.miniVideo) $refs.miniVideo.currentTime = pos * duration;">
                <div class="absolute top-0 bottom-0 left-0 bg-amber-400 rounded-full" :style="'width: ' + (duration > 0 ? (currentTime / duration) * 100 : 0) + '%'"></div>
            </div>
            
            <div class="flex items-center justify-between text-[10px] text-zinc-300 font-semibold px-0.5">
                <span x-text="formatTime(currentTime) + ' / ' + formatTime(duration)"></span>
                <div class="flex items-center gap-2">
                    <button @click.stop="toggleMute()" class="text-white hover:text-amber-300 transition-colors">
                        <i x-show="!isMuted" data-lucide="volume-2" class="w-3.5 h-3.5"></i>
                        <i x-show="isMuted" data-lucide="volume-x" class="w-3.5 h-3.5 text-amber-400" style="display:none;"></i>
                    </button>
                    <button @click.stop="expandToWatchPage()" class="text-amber-400 font-bold hover:underline text-[9px] uppercase tracking-wider">
                        Buka Halaman
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function globalMiniPlayer() {
        return {
            active: false,
            filmTitle: '',
            streamUrl: '',
            currentTime: 0,
            duration: 0,
            posterUrl: '',
            watchUrl: '',
            isPlaying: false,
            isMuted: false,
            showControls: false,
            isOnWatchPage: false,

            init() {
                this.isOnWatchPage = window.location.pathname.includes('/watch');
                this.checkActivePlayer();

                window.addEventListener('storage', (e) => {
                    if (e.key === 'faiilmov_global_miniplayer') {
                        this.checkActivePlayer();
                    }
                });

                // Periodic sync every 2s
                setInterval(() => {
                    if (!this.isOnWatchPage) {
                        this.checkActivePlayer();
                    }
                }, 2000);
            },

            checkActivePlayer() {
                try {
                    const raw = localStorage.getItem('faiilmov_global_miniplayer');
                    if (!raw) {
                        this.active = false;
                        return;
                    }
                    const data = JSON.parse(raw);
                    // Active if saved within last 2 hours
                    if (data && data.active && (Date.now() - (data.timestamp || 0) < 7200000)) {
                        if (!this.isOnWatchPage) {
                            const isNewSource = (this.streamUrl !== data.streamUrl);
                            this.filmTitle = data.filmTitle || 'Memutar Video';
                            this.streamUrl = data.streamUrl || '';
                            this.duration = data.duration || 0;
                            this.posterUrl = data.posterUrl || '';
                            this.watchUrl = data.watchUrl || '';

                            if (isNewSource || !this.active) {
                                this.active = true;
                                this.currentTime = data.currentTime || 0;
                                this.$nextTick(() => {
                                    const v = this.$refs.miniVideo;
                                    if (v && this.streamUrl) {
                                        v.src = this.streamUrl;
                                        v.currentTime = this.currentTime;
                                        v.play().then(() => {
                                            this.isPlaying = true;
                                        }).catch(() => {
                                            this.isPlaying = false;
                                        });
                                        if (window.lucide) lucide.createIcons();
                                    }
                                });
                            }
                        } else {
                            this.active = false;
                        }
                    } else {
                        this.active = false;
                    }
                } catch (e) {
                    this.active = false;
                }
            },

            togglePlay() {
                const v = this.$refs.miniVideo;
                if (!v) return;
                if (v.paused) {
                    v.play().then(() => { this.isPlaying = true; });
                } else {
                    v.pause();
                    this.isPlaying = false;
                }
            },

            toggleMute() {
                const v = this.$refs.miniVideo;
                if (!v) return;
                v.muted = !v.muted;
                this.isMuted = v.muted;
            },

            expandToWatchPage() {
                const v = this.$refs.miniVideo;
                const time = v ? Math.floor(v.currentTime) : Math.floor(this.currentTime);
                let targetUrl = this.watchUrl || '/';
                if (targetUrl.includes('?')) {
                    targetUrl += `&t=${time}`;
                } else {
                    targetUrl += `?t=${time}`;
                }
                window.location.href = targetUrl;
            },

            closeMiniPlayer() {
                const v = this.$refs.miniVideo;
                if (v) v.pause();
                this.active = false;
                this.isPlaying = false;
                try {
                    localStorage.removeItem('faiilmov_global_miniplayer');
                } catch (e) {}
            },

            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '00:00';
                const m = Math.floor(seconds / 60);
                const s = Math.floor(seconds % 60);
                return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
            }
        }
    }
</script>
