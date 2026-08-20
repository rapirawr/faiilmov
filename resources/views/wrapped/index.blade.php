@extends('layouts.app')

@section('title', 'Movie Wrapped: Kilas Balik Sinematik Anda | faiilmov')

@section('content')
<!-- Safe JSON Payload Container -->
<script id="wrapped-initial-data" type="application/json">
{!! json_encode($wrapped) !!}
</script>

<div class="min-h-screen bg-dark-950 text-white flex flex-col justify-between py-6 px-4 sm:px-6 relative overflow-hidden" 
     x-data="movieWrappedApp()">
    
    <!-- Background Dynamic Ambient Gradient Mesh -->
    <div class="fixed inset-0 pointer-events-none z-0 transition-all duration-1000 ease-out" 
         :class="currentSlideGradient"></div>

    <!-- Top Action Bar: Navigation, Sound, Period Switcher & Exit -->
    <div class="max-w-xl mx-auto w-full flex items-center justify-between gap-3 relative z-30 pb-4">
        <a href="{{ route('home') }}" class="p-2.5 rounded-2xl glass-card border border-white/10 hover:border-white/30 text-zinc-300 hover:text-white transition-all flex items-center gap-1.5 text-xs font-bold">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span class="hidden xs:inline">Kembali</span>
        </a>

        <!-- Period Selector Dropdown -->
        <div class="flex items-center p-1 rounded-2xl bg-dark-900/90 border border-white/15 text-xs">
            <a href="{{ route('wrapped', ['period' => 'year']) }}" 
               class="px-3 py-1.5 rounded-xl font-bold transition-all {{ $period === 'year' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                Tahun Ini
            </a>
            <a href="{{ route('wrapped', ['period' => 'month']) }}" 
               class="px-3 py-1.5 rounded-xl font-bold transition-all {{ $period === 'month' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                Bulan Ini
            </a>
            <a href="{{ route('wrapped', ['period' => 'all']) }}" 
               class="px-3 py-1.5 rounded-xl font-bold transition-all {{ $period === 'all' ? 'bg-amber-500 text-zinc-950 shadow' : 'text-zinc-400 hover:text-white' }}">
                All-Time
            </a>
        </div>

        <!-- Sound Synthesizer FX Toggle -->
        <button type="button" 
                @click="toggleSound()" 
                class="p-2.5 rounded-2xl glass-card border border-white/10 hover:border-white/30 transition-all text-zinc-300 hover:text-white cursor-pointer"
                :title="soundEnabled ? 'Matikan Suara' : 'Nyalakan Suara Efek'">
            <span x-show="soundEnabled"><i data-lucide="volume-2" class="w-4 h-4 text-amber-400"></i></span>
            <span x-show="!soundEnabled" style="display:none;"><i data-lucide="volume-x" class="w-4 h-4 text-zinc-500"></i></span>
        </button>
    </div>

    <!-- Main Story Container (Spotify-style 9:16 Aspect Ratio on Desktop) -->
    <div class="max-w-md mx-auto w-full aspect-[9/16] min-h-[580px] max-h-[780px] rounded-3xl glass-panel border border-white/20 relative shadow-2xl overflow-hidden flex flex-col justify-between p-6 sm:p-7 select-none z-20"
         @mousedown="pauseStory()"
         @mouseup="resumeStory()"
         @touchstart="pauseStory()"
         @touchend="resumeStory()">
        
        <!-- Top Story Progress Bar Indicators -->
        <div class="flex items-center gap-1.5 w-full relative z-30 mb-4">
            <template x-for="(slide, index) in totalSlides" :key="index">
                <div class="h-1 flex-1 rounded-full bg-white/20 overflow-hidden relative">
                    <div class="h-full bg-white rounded-full transition-all duration-100 ease-linear"
                         :style="{
                             width: index < currentSlide ? '100%' : (index === currentSlide ? slideProgress + '%' : '0%')
                         }"></div>
                </div>
            </template>
        </div>

        <!-- Touch / Click Story Navigation Overlay (Active only on story slides 0-5, disabled on Finale slide) -->
        <div class="absolute inset-0 z-10 flex" x-show="currentSlide < totalSlides - 1">
            <div class="w-1/3 h-full cursor-pointer" @click.stop="prevSlide()"></div>
            <div class="w-2/3 h-full cursor-pointer" @click.stop="nextSlide()"></div>
        </div>

        <!-- Dynamic Slide Content Screens (z-20 so elements & buttons receive clicks directly) -->
        <div class="relative z-20 flex-1 flex flex-col justify-between py-2 pointer-events-auto">
            
            <!-- SLIDE 0: INTRO & OVERVIEW -->
            <div x-show="currentSlide === 0" 
                 x-transition:enter="transition ease-out duration-400 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-6">
                
                <div class="space-y-2 pt-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-black tracking-widest uppercase">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span x-text="data.period_label"></span>
                    </div>
                    <h2 class="text-3xl font-extrabold text-white tracking-tight font-chillax leading-tight">
                        Kilas Balik Sinematik Anda
                    </h2>
                </div>

                <!-- User Hero Circle -->
                <div class="relative my-auto">
                    <div class="w-32 h-32 rounded-full p-1 bg-gradient-to-tr from-amber-400 via-rose-500 to-indigo-500 shadow-2xl animate-pulse">
                        <img :src="data.user.avatar" :alt="data.user.name" class="w-full h-full rounded-full object-cover border-2 border-dark-950">
                    </div>
                    <div class="absolute -bottom-2.5 inset-x-0 flex justify-center">
                        <span class="px-3 py-1 rounded-xl bg-dark-900 border border-amber-500/50 text-amber-300 text-xs font-bold flex items-center gap-1 shadow-lg">
                            <i data-lucide="{{ $wrapped['user']['tier_icon'] }}" class="w-3.5 h-3.5"></i>
                            <span x-text="data.user.tier_title"></span>
                        </span>
                    </div>
                </div>

                <div class="space-y-3 pb-2">
                    <p class="text-sm font-semibold text-zinc-200">
                        Hai <strong class="text-amber-400" x-text="data.user.name"></strong>, siap melihat jejak tontonan Anda?
                    </p>
                    <div class="text-[11px] text-zinc-400 font-mono flex items-center justify-center gap-1.5">
                        <span>Ketuk layar untuk melanjutkan</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
            </div>

            <!-- SLIDE 1: TOTAL WATCH TIME & MILEAGE -->
            <div x-show="currentSlide === 1" 
                 x-transition:enter="transition ease-out duration-400 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-6"
                 style="display:none;">
                
                <div class="space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        <span>Dedikasi Waktu</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white font-chillax">
                        Waktu di Depan Layar
                    </h2>
                </div>

                <!-- Big Stat Counter Card -->
                <div class="w-full space-y-4 my-auto">
                    <div class="glass-card rounded-3xl border border-white/20 p-6 bg-gradient-to-b from-amber-500/20 via-transparent to-transparent shadow-inner">
                        <div class="text-5xl sm:text-6xl font-black font-mono text-white tracking-tight" x-text="data.stats.total_hours"></div>
                        <div class="text-xs font-extrabold text-amber-300 uppercase tracking-widest mt-1">Total Jam Menonton</div>
                        <div class="text-xs text-zinc-400 mt-2 font-mono" x-text="'(' + data.stats.total_minutes.toLocaleString() + ' Menit)'"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="glass-card p-4 rounded-2xl border border-white/10">
                            <div class="text-2xl font-black text-white font-mono" x-text="data.stats.total_titles"></div>
                            <div class="text-[11px] text-zinc-400 font-bold uppercase mt-0.5">Judul Ditonton</div>
                        </div>
                        <div class="glass-card p-4 rounded-2xl border border-white/10">
                            <div class="text-2xl font-black text-amber-400 font-mono" x-text="data.user.streak_count"></div>
                            <div class="text-[11px] text-zinc-400 font-bold uppercase mt-0.5">Hari Streak</div>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-zinc-300 italic pb-2">
                    "Waktu yang dihabiskan untuk cerita yang luar biasa tidak pernah sia-sia."
                </p>
            </div>

            <!-- SLIDE 2: TOP 5 FILMS / SERIES -->
            <div x-show="currentSlide === 2" 
                 x-transition:enter="transition ease-out duration-400 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="flex-1 flex flex-col justify-between space-y-4"
                 style="display:none;">
                
                <div class="text-center space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <i data-lucide="film" class="w-4 h-4"></i>
                        <span>Koleksi Favorit</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax">
                        Top Film & Serial Anda
                    </h2>
                </div>

                <div class="space-y-2.5 my-auto">
                    <template x-for="(film, idx) in (data.top_films || []).slice(0, 4)" :key="film.id">
                        <div class="glass-card rounded-2xl p-3 border border-white/15 flex items-center gap-3.5 bg-dark-900/60 shadow">
                            <div class="w-7 h-7 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 font-mono font-black text-xs flex items-center justify-center shrink-0" 
                                 x-text="'#' + (idx + 1)"></div>
                            <img :src="film.poster_url" :alt="film.title" class="w-10 h-14 rounded-lg object-cover border border-white/10 shrink-0">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-xs text-white truncate" x-text="film.title"></h4>
                                <div class="flex items-center gap-2 text-[10px] text-zinc-400 mt-0.5">
                                    <span x-text="film.type"></span>
                                    <span>•</span>
                                    <span class="text-amber-400 font-mono" x-text="film.minutes + ' Menit'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <p class="text-[11px] text-center text-zinc-400">
                    Tayangan yang paling menemani hari-hari Anda.
                </p>
            </div>

            <!-- SLIDE 3: GENRE FINGERPRINT -->
            <div x-show="currentSlide === 3" 
                 x-transition:enter="transition ease-out duration-400 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="flex-1 flex flex-col justify-between space-y-4"
                 style="display:none;">
                
                <div class="text-center space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <i data-lucide="pie-chart" class="w-4 h-4"></i>
                        <span>DNA Sinematik</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax">
                        Distribusi Genre Utama
                    </h2>
                </div>

                <div class="space-y-3.5 my-auto">
                    <template x-for="(genre, i) in (data.top_genres || []).slice(0, 4)" :key="genre.name">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-white flex items-center gap-1.5">
                                    <i data-lucide="tag" class="w-3.5 h-3.5 text-amber-400"></i>
                                    <span x-text="genre.name"></span>
                                </span>
                                <span class="font-mono text-amber-300" x-text="genre.percentage + '%'"></span>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-rose-500 transition-all duration-700" 
                                     :style="{ width: genre.percentage + '%' }"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="glass-card p-3 rounded-2xl border border-white/10 text-center text-xs text-zinc-300">
                    Genre teratas Anda: <strong class="text-amber-400" x-text="data.top_genres && data.top_genres[0] ? data.top_genres[0].name : 'Beragam'"></strong>
                </div>
            </div>

            <!-- SLIDE 4: VIEWING HABIT & CLOCK -->
            <div x-show="currentSlide === 4" 
                 x-transition:enter="transition ease-out duration-400 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-6"
                 style="display:none;">
                
                <div class="space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Ritme Menonton</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax">
                        Waktu Favorit Anda
                    </h2>
                </div>

                <div class="my-auto space-y-5">
                    <div class="w-24 h-24 rounded-3xl bg-amber-500/20 border-2 border-amber-500/50 text-amber-400 flex items-center justify-center mx-auto shadow-xl shadow-amber-500/20">
                        <i data-lucide="{{ $wrapped['habit']['icon'] }}" class="w-12 h-12"></i>
                    </div>

                    <div class="space-y-2 max-w-xs mx-auto">
                        <h3 class="text-xl font-black text-white" x-text="data.habit.title"></h3>
                        <p class="text-xs text-zinc-300 leading-relaxed" x-text="data.habit.desc"></p>
                    </div>
                </div>

                <div class="text-[11px] text-zinc-400 font-mono">
                    Setiap momen selalu memiliki film yang tepat.
                </div>
            </div>

            <!-- SLIDE 5: CINEPHILE PERSONA ARCHETYPE -->
            <div x-show="currentSlide === 5" 
                 x-transition:enter="transition ease-out duration-400 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-6"
                 style="display:none;">
                
                <div class="space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>Identitas Sinema</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax">
                        Persona Cinephile Anda
                    </h2>
                </div>

                <!-- Archetype Reveal Card -->
                <div class="my-auto w-full glass-card p-6 rounded-3xl border-2 border-amber-400/60 bg-gradient-to-b from-amber-500/25 via-dark-900 to-dark-950 shadow-2xl space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-amber-400/20 border border-amber-400 text-amber-300 flex items-center justify-center mx-auto shadow">
                        <i data-lucide="{{ $wrapped['archetype']['icon'] }}" class="w-9 h-9"></i>
                    </div>

                    <div class="space-y-1.5">
                        <h3 class="text-xl font-extrabold text-white tracking-tight" x-text="data.archetype.title"></h3>
                        <p class="text-xs text-zinc-300 leading-relaxed italic" x-text="data.archetype.tagline"></p>
                    </div>

                    <div class="pt-2 border-t border-white/10 flex items-center justify-between text-xs">
                        <span class="text-zinc-400">Level Saat Ini:</span>
                        <span class="font-bold text-amber-300" x-text="'Lv. ' + data.user.level + ' (' + data.user.tier_title + ')'"></span>
                    </div>
                </div>

                <p class="text-xs text-zinc-400">
                    Bagikan persona sinematik Anda ke media sosial!
                </p>
            </div>

            <!-- SLIDE 6: GRAND FINALE & HD 9:16 CARD EXPORT -->
            <div x-show="currentSlide === 6" 
                 x-transition:enter="transition ease-out duration-400 transform"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="flex-1 flex flex-col justify-between space-y-4"
                 style="display:none;">
                
                <div class="text-center space-y-1">
                    <h2 class="text-2xl font-black text-white font-chillax">
                        Rangkuman Story Anda
                    </h2>
                    <p class="text-[11px] text-zinc-400">Siap untuk diunduh dan dipajang di Instagram Story / WA / X</p>
                </div>

                <!-- Story Summary Mini Card -->
                <div class="glass-card rounded-2xl p-4 border border-amber-500/40 bg-gradient-to-b from-amber-500/15 via-dark-900 to-dark-950 space-y-3 shadow-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <img :src="data.user.avatar" :alt="data.user.name" class="w-9 h-9 rounded-full border border-amber-400 object-cover">
                            <div>
                                <h4 class="font-bold text-xs text-white" x-text="data.user.name"></h4>
                                <div class="text-[10px] text-amber-300 font-semibold" x-text="data.archetype.title"></div>
                            </div>
                        </div>
                        <span class="font-chillax font-black text-xs text-white">faiilmov</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-center pt-2 border-t border-white/10">
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="font-mono font-black text-sm text-white" x-text="data.stats.total_hours + ' Jam'"></div>
                            <div class="text-[9px] text-zinc-400 uppercase font-bold">Waktu Tonton</div>
                        </div>
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="font-mono font-black text-sm text-amber-400" x-text="data.user.streak_count + ' Hari'"></div>
                            <div class="text-[9px] text-zinc-400 uppercase font-bold">Streak Nonton</div>
                        </div>
                    </div>

                    <div class="text-[10px] text-zinc-300 space-y-1 bg-white/5 p-2.5 rounded-xl border border-white/5">
                        <div class="font-bold text-amber-300 flex items-center gap-1">
                            <i data-lucide="tag" class="w-3 h-3"></i>
                            <span>Top Genre:</span>
                        </div>
                        <div class="text-zinc-200 truncate" x-text="(data.top_genres || []).map(g => g.name).join(', ') || 'Sinema Dunia'"></div>
                    </div>
                </div>

                <!-- Export & Share Buttons (Always Clickable) -->
                <div class="space-y-2 relative z-30 pointer-events-auto pt-1">
                    <button type="button" 
                            @click.stop="downloadStoryCard()" 
                            :disabled="isGeneratingImage"
                            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-400 hover:to-yellow-400 text-zinc-950 font-black text-xs transition-all flex items-center justify-center gap-2 shadow-lg shadow-amber-500/25 cursor-pointer active:scale-98 disabled:opacity-50">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span x-text="isGeneratingImage ? 'Menyiapkan Gambar HD...' : 'Unduh Kartu Story (9:16 HD)'"></span>
                    </button>

                    <button type="button" 
                            @click.stop="currentSlide = 0; startTimer()"
                            class="w-full py-2.5 rounded-2xl glass-card border border-white/15 hover:bg-white/10 text-xs font-bold text-zinc-200 transition-colors flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        <span>Putar Ulang Kilas Balik</span>
                    </button>
                </div>

            </div>

        </div>

        <!-- Story Bottom Navigation Controls -->
        <div class="flex items-center justify-between pt-3 border-t border-white/10 relative z-30 pointer-events-auto text-xs text-zinc-400">
            <button type="button" @click.stop="prevSlide()" :disabled="currentSlide === 0" class="hover:text-white disabled:opacity-30 cursor-pointer flex items-center gap-1">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                <span>Sebelumnya</span>
            </button>
            
            <span class="font-mono text-[11px] text-amber-400 font-bold" x-text="(currentSlide + 1) + ' / ' + totalSlides"></span>

            <button type="button" @click.stop="nextSlide()" :disabled="currentSlide === totalSlides - 1" class="hover:text-white disabled:opacity-30 cursor-pointer flex items-center gap-1">
                <span>Selanjutnya</span>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
        </div>

    </div>

    <!-- Hidden Canvas for High-Resolution 1080x1920 (9:16) Export -->
    <canvas id="storyCanvas" width="1080" height="1920" style="display:none;"></canvas>

    <!-- Bottom Footer Brand -->
    <div class="max-w-md mx-auto w-full text-center text-xs text-zinc-500 pt-4 relative z-10">
        faiilmov Cinephile Wrapped • Rayakan cerita terbaik Anda
    </div>

</div>

@push('scripts')
<script>
function movieWrappedApp() {
    let initialPayload = {};
    try {
        const rawJson = document.getElementById('wrapped-initial-data')?.textContent || '{}';
        initialPayload = JSON.parse(rawJson);
    } catch (e) {
        console.error('Error parsing wrapped payload:', e);
    }

    return {
        data: initialPayload,
        period: initialPayload.period || 'year',
        year: {{ (int)$year }},
        month: {{ (int)$month }},
        currentSlide: 0,
        totalSlides: 7,
        slideProgress: 0,
        timer: null,
        isPaused: false,
        soundEnabled: true,
        isGeneratingImage: false,
        audioCtx: null,

        gradients: [
            'bg-gradient-to-tr from-purple-950/70 via-dark-950 to-amber-950/50',
            'bg-gradient-to-tr from-amber-950/70 via-dark-950 to-rose-950/50',
            'bg-gradient-to-tr from-indigo-950/70 via-dark-950 to-cyan-950/50',
            'bg-gradient-to-tr from-rose-950/70 via-dark-950 to-purple-950/50',
            'bg-gradient-to-tr from-blue-950/70 via-dark-950 to-indigo-950/50',
            'bg-gradient-to-tr from-amber-950/80 via-dark-950 to-yellow-950/60',
            'bg-gradient-to-tr from-purple-950/80 via-dark-950 to-amber-950/70'
        ],

        get currentSlideGradient() {
            return this.gradients[this.currentSlide] || this.gradients[0];
        },

        init() {
            this.startTimer();
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        startTimer() {
            this.clearInterval();
            this.slideProgress = 0;
            const duration = 6000; // 6 seconds per slide
            const intervalTime = 50;
            const increment = (intervalTime / duration) * 100;

            this.timer = setInterval(() => {
                if (!this.isPaused) {
                    this.slideProgress += increment;
                    if (this.slideProgress >= 100) {
                        if (this.currentSlide < this.totalSlides - 1) {
                            this.nextSlide();
                        } else {
                            this.clearInterval();
                        }
                    }
                }
            }, intervalTime);
        },

        clearInterval() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        pauseStory() {
            this.isPaused = true;
        },

        resumeStory() {
            this.isPaused = false;
        },

        nextSlide() {
            if (this.currentSlide < this.totalSlides - 1) {
                this.currentSlide++;
                this.playSlideTone();
                this.startTimer();
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        prevSlide() {
            if (this.currentSlide > 0) {
                this.currentSlide--;
                this.playSlideTone();
                this.startTimer();
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            if (this.soundEnabled) this.playSlideTone();
        },

        playSlideTone() {
            if (!this.soundEnabled) return;
            try {
                if (!this.audioCtx) {
                    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = 'sine';
                const baseFreq = 440 + (this.currentSlide * 60);
                osc.frequency.setValueAtTime(baseFreq, this.audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(baseFreq * 1.5, this.audioCtx.currentTime + 0.15);

                gain.gain.setValueAtTime(0.08, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 0.15);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.15);
            } catch (e) {}
        },

        async downloadStoryCard() {
            if (this.isGeneratingImage) return;
            this.isGeneratingImage = true;

            try {
                const canvas = document.getElementById('storyCanvas');
                if (!canvas) {
                    console.error('Canvas element not found');
                    return;
                }
                const ctx = canvas.getContext('2d');

                // Draw 9:16 HD Background (1080 x 1920)
                const gradient = ctx.createLinearGradient(0, 0, 1080, 1920);
                gradient.addColorStop(0, '#09090b');
                gradient.addColorStop(0.3, '#1c1917');
                gradient.addColorStop(0.7, '#27272a');
                gradient.addColorStop(1, '#09090b');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, 1080, 1920);

                // Ambient Glow Circles
                ctx.save();
                ctx.fillStyle = 'rgba(245, 158, 11, 0.15)';
                ctx.filter = 'blur(80px)';
                ctx.beginPath();
                ctx.arc(540, 400, 350, 0, Math.PI * 2);
                ctx.fill();
                ctx.fillStyle = 'rgba(225, 29, 72, 0.1)';
                ctx.beginPath();
                ctx.arc(800, 1200, 300, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();

                // Brand Header
                ctx.fillStyle = '#f59e0b';
                ctx.font = 'bold 36px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('faiilmov • MOVIE WRAPPED', 540, 140);

                ctx.fillStyle = '#a1a1aa';
                ctx.font = '500 28px sans-serif';
                ctx.fillText((this.data.period_label || '2026').toUpperCase(), 540, 190);

                // User Box Card
                ctx.fillStyle = 'rgba(255, 255, 255, 0.05)';
                ctx.strokeStyle = 'rgba(245, 158, 11, 0.4)';
                ctx.lineWidth = 4;
                this.roundRect(ctx, 100, 260, 880, 450, 32);
                ctx.fill();
                ctx.stroke();

                // User Name & Archetype
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 54px sans-serif';
                ctx.fillText(this.data.user.name, 540, 390);

                ctx.fillStyle = '#f59e0b';
                ctx.font = 'bold 40px sans-serif';
                ctx.fillText(this.data.archetype.title, 540, 460);

                ctx.fillStyle = '#d4d4d8';
                ctx.font = 'italic 26px sans-serif';
                ctx.fillText('"' + this.data.archetype.tagline + '"', 540, 520);

                ctx.fillStyle = '#a1a1aa';
                ctx.font = 'bold 26px sans-serif';
                ctx.fillText('LEVEL ' + this.data.user.level + ' • ' + (this.data.user.tier_title || '').toUpperCase(), 540, 620);

                // Stat Cards Grid
                // Card 1: Total Hours
                ctx.fillStyle = 'rgba(255, 255, 255, 0.05)';
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
                ctx.lineWidth = 2;
                this.roundRect(ctx, 100, 750, 420, 220, 24);
                ctx.fill();
                ctx.stroke();

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 64px sans-serif';
                ctx.fillText(String(this.data.stats.total_hours), 310, 850);
                ctx.fillStyle = '#f59e0b';
                ctx.font = 'bold 24px sans-serif';
                ctx.fillText('TOTAL JAM NONTON', 310, 910);

                // Card 2: Streak & Titles
                this.roundRect(ctx, 560, 750, 420, 220, 24);
                ctx.fill();
                ctx.stroke();

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 64px sans-serif';
                ctx.fillText(this.data.user.streak_count + ' Hari', 770, 850);
                ctx.fillStyle = '#f59e0b';
                ctx.font = 'bold 24px sans-serif';
                ctx.fillText('STREAK NONTON', 770, 910);

                // Top Genres Box
                this.roundRect(ctx, 100, 1010, 880, 460, 32);
                ctx.fill();
                ctx.stroke();

                ctx.fillStyle = '#f59e0b';
                ctx.font = 'bold 32px sans-serif';
                ctx.textAlign = 'left';
                ctx.fillText('TOP GENRE FAVORIT', 150, 1080);

                let genreY = 1160;
                (this.data.top_genres || []).slice(0, 4).forEach((g, idx) => {
                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 30px sans-serif';
                    ctx.fillText('#' + (idx + 1) + ' ' + g.name, 150, genreY);

                    ctx.fillStyle = '#f59e0b';
                    ctx.textAlign = 'right';
                    ctx.fillText(g.percentage + '%', 930, genreY);
                    ctx.textAlign = 'left';

                    genreY += 75;
                });

                // Habit Tag
                ctx.fillStyle = 'rgba(245, 158, 11, 0.15)';
                ctx.strokeStyle = 'rgba(245, 158, 11, 0.4)';
                this.roundRect(ctx, 100, 1510, 880, 150, 24);
                ctx.fill();
                ctx.stroke();

                ctx.fillStyle = '#f59e0b';
                ctx.font = 'bold 28px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText((this.data.habit.title || '').toUpperCase(), 540, 1575);
                ctx.fillStyle = '#d4d4d8';
                ctx.font = '22px sans-serif';
                ctx.fillText(this.data.habit.desc || '', 540, 1620);

                // Watermark Footer
                ctx.fillStyle = '#71717a';
                ctx.font = '500 24px sans-serif';
                ctx.fillText('Tonton ribuan film & serial favorit di faiilmov', 540, 1800);

                // Trigger Download safely
                const dataUrl = canvas.toDataURL('image/png');
                const link = document.createElement('a');
                link.download = `faiilmov-wrapped-${(this.data.user.name || 'user').toLowerCase().replace(/\s+/g, '-')}-${this.period}.png`;
                link.href = dataUrl;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (err) {
                console.error('Error generating story card:', err);
            } finally {
                this.isGeneratingImage = false;
            }
        },

        roundRect(ctx, x, y, width, height, radius) {
            if (ctx.roundRect) {
                ctx.beginPath();
                ctx.roundRect(x, y, width, height, radius);
                return;
            }
            ctx.beginPath();
            ctx.moveTo(x + radius, y);
            ctx.lineTo(x + width - radius, y);
            ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
            ctx.lineTo(x + width, y + height - radius);
            ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
            ctx.lineTo(x + radius, y + height);
            ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
            ctx.lineTo(x, y + radius);
            ctx.quadraticCurveTo(x, y + radius, x, y);
            ctx.closePath();
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endpush
@endsection
