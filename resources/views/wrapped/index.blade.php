@extends('layouts.app')

@section('title', 'Movie Wrapped: Kilas Balik Sinematik Anda | faiilmov')
@section('hide_navbar', 'true')
@section('hide_sidebar', 'true')
@section('hide_footer', 'true')

@section('content')
<!-- Safe JSON Payload Container -->
<script id="wrapped-initial-data" type="application/json">
{!! json_encode($wrapped, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

<!-- Fullscreen Instagram Story Experience Wrapper -->
<div class="fixed inset-0 h-[100dvh] w-full bg-black text-white flex items-center justify-center overflow-hidden select-none z-50 overscroll-none touch-none" 
     x-data="movieWrappedApp()" 
     @keydown.window.arrow-left="prevSlide()"
     @keydown.window.arrow-right="nextSlide()"
     @keydown.window.space.prevent="togglePlayPause()"
     @keydown.window.m="toggleSound()"
     @keydown.window.escape="window.location.href='{{ route('home') }}'">

    <!-- Background Dynamic Ambient Blurred Gradient Mesh (Desktop Visual Depth) -->
    <div class="fixed inset-0 pointer-events-none z-0 transition-all duration-1000 ease-out opacity-40 blur-3xl scale-110" 
         :class="currentSlideGradient"></div>

    <!-- Desktop Top Header: Brand Logo (Left) & Close Button (Right) -->
    <div class="hidden sm:flex fixed top-0 left-0 right-0 z-50 items-center justify-between px-6 py-4 pointer-events-none">
        <!-- Top Left Brand -->
        <a href="{{ route('home') }}" class="pointer-events-auto flex items-center gap-2 group">
            <span class="font-chillax font-extrabold text-2xl tracking-tight text-white group-hover:text-amber-400 transition-colors drop-shadow">
                faiilmov
            </span>
        </a>

        <!-- Top Right Close Button (X) -->
        <a href="{{ route('home') }}" 
           class="pointer-events-auto w-10 h-10 rounded-full flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/10 transition active:scale-95 cursor-pointer"
           title="Kembali ke Beranda (Esc)">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </a>
    </div>

    <!-- Desktop Outside Left Navigation Arrow -->
    <button type="button" 
            @click="prevSlide()" 
            :disabled="currentSlide === 0"
            class="hidden md:flex items-center justify-center w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md text-white border border-white/20 transition-all active:scale-90 cursor-pointer disabled:opacity-20 disabled:pointer-events-none z-40 mr-4 lg:mr-8 shadow-2xl"
            title="Slide Sebelumnya (Panah Kiri)">
        <i data-lucide="chevron-left" class="w-5 h-5"></i>
    </button>

    <!-- Main Instagram Story Canvas (Full-bleed on Mobile 100dvh, exact 9:16 Card on Desktop) -->
    <div class="w-full sm:w-auto h-[100dvh] sm:h-[min(92vh,900px)] aspect-[9/16] rounded-none sm:rounded-2xl overflow-hidden relative shadow-[0_25px_70px_rgba(0,0,0,0.9)] border-0 sm:border sm:border-white/15 bg-dark-950 flex flex-col justify-between select-none z-30 transition-transform duration-150 ease-out"
         :style="isSwipingDown ? `transform: translateY(${swipeTranslateY}px); opacity: ${Math.max(0.3, 1 - (swipeTranslateY / 400))};` : ''"
         :class="currentSlideCardGradient"
         @touchstart="handleTouchStart($event)"
         @touchmove="handleTouchMove($event)"
         @touchend="handleTouchEnd($event)"
         @mousedown="handleMouseDown($event)"
         @mouseup="handleMouseUp($event)"
         @mouseleave="handleMouseUp($event)">
        
        <!-- Story Top Overlays Container (Segmented Progress Bars & Profile Header) -->
        <div class="relative z-30 px-4 sm:px-5 pt-[max(env(safe-area-inset-top,16px),16px)] pb-2 transition-opacity duration-200"
             :class="isHolding ? 'opacity-0' : 'opacity-100'">
            
            <!-- Instagram Story Segmented Progress Bar (High Contrast & Clear) -->
            <div class="flex items-center gap-1.5 w-full mb-3">
                <template x-for="(slide, index) in totalSlides" :key="index">
                    <div class="h-[3.5px] flex-1 rounded-full bg-white/35 overflow-hidden relative shadow-[0_1px_4px_rgba(0,0,0,0.6)]">
                        <div class="h-full bg-white rounded-full transition-all duration-100 ease-linear"
                             :style="{
                                 width: index < currentSlide ? '100%' : (index === currentSlide ? slideProgress + '%' : '0%')
                             }"></div>
                    </div>
                </template>
            </div>

            <!-- Instagram Story Author Bar -->
            <div class="flex items-center justify-between gap-2 text-xs">
                <!-- User Avatar & Identity (Left) -->
                <div class="flex items-center gap-2.5 min-w-0">
                    <!-- IG Gradient Story Ring -->
                    <div class="p-[2px] rounded-full bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 shadow-md shrink-0">
                        <img :src="data.user.avatar" :alt="data.user.name" class="w-8 h-8 rounded-full object-cover border border-dark-950 bg-dark-900">
                    </div>
                    
                    <div class="min-w-0 flex flex-col">
                        <div class="flex items-center gap-1">
                            <span class="font-bold text-white text-xs truncate max-w-[120px] sm:max-w-[140px]" x-text="data.user.name"></span>
                            <i data-lucide="badge-check" class="w-3.5 h-3.5 text-sky-400 shrink-0"></i>
                        </div>
                        
                        <!-- Period Switch Trigger Pill -->
                        <button type="button" 
                                @click.stop="showPeriodModal = true; pauseStory()" 
                                class="inline-flex items-center gap-1 text-[10px] text-zinc-400 hover:text-amber-300 font-medium transition-colors cursor-pointer text-left">
                            <span x-text="data.period_label"></span>
                            <i data-lucide="chevron-down" class="w-2.5 h-2.5"></i>
                        </button>
                    </div>
                </div>

                <!-- Story Header Actions (Right) -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <!-- Sound Toggle -->
                    <button type="button" 
                            @click.stop="toggleSound()" 
                            class="w-7 h-7 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/50 border border-white/10 backdrop-blur-md transition cursor-pointer"
                            :title="soundEnabled ? 'Matikan Suara (M)' : 'Nyalakan Suara (M)'">
                        <!-- Sound On -->
                        <svg x-show="soundEnabled" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
                        </svg>
                        <!-- Sound Muted -->
                        <svg x-show="!soundEnabled" style="display:none;" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <line x1="23" y1="9" x2="17" y2="15"></line>
                            <line x1="17" y1="9" x2="23" y2="15"></line>
                        </svg>
                    </button>

                    <!-- Play / Pause Toggle -->
                    <button type="button" 
                            @click.stop="togglePlayPause()" 
                            class="w-7 h-7 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/50 border border-white/10 backdrop-blur-md transition cursor-pointer"
                            :title="isPaused ? 'Lanjutkan (Spasi)' : 'Jeda (Spasi)'">
                        <!-- Pause Icon (when playing) -->
                        <svg x-show="!isPaused" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                        <!-- Play Icon (when paused) -->
                        <svg x-show="isPaused" style="display:none;" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="5 3 19 12 5 3"></polygon>
                        </svg>
                    </button>

                    <!-- Exit Story (X) -->
                    <a href="{{ route('home') }}" 
                       class="w-7 h-7 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/50 text-white/90 border border-white/10 backdrop-blur-md transition cursor-pointer"
                       title="Tutup Kilas Balik (Esc)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Touch / Click Story Navigation Overlay (Left 30% = Prev, Right 70% = Next) -->
        <div class="absolute inset-0 z-10 flex">
            <div class="w-[30%] h-full cursor-pointer select-none" @click.stop="handlePrevTap($event)"></div>
            <div class="w-[70%] h-full cursor-pointer select-none" @click.stop="handleNextTap($event)"></div>
        </div>

        <!-- Dynamic Slide Content Screens (z-20 so clickable buttons receive clicks directly) -->
        <div class="relative z-20 flex-1 flex flex-col justify-between px-5 sm:px-6 py-3 pointer-events-none">
            
            <!-- SLIDE 0: INTRO & OVERVIEW -->
            <div x-show="currentSlide === 0" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-6">
                
                <div class="space-y-2 pt-2">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-amber-500/20 to-rose-500/20 border border-amber-500/40 text-amber-300 text-[11px] font-bold tracking-wider uppercase backdrop-blur-md">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span x-text="data.period_label"></span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight font-chillax leading-tight drop-shadow-md">
                        Kilas Balik Sinematik
                    </h2>
                </div>

                <!-- User Hero Circle with Story Glow Ring -->
                <div class="relative my-auto flex flex-col items-center">
                    <div class="relative w-36 h-36 rounded-full p-1 bg-gradient-to-tr from-amber-400 via-rose-500 to-purple-600 shadow-[0_0_40px_rgba(245,158,11,0.3)]">
                        <img :src="data.user.avatar" :alt="data.user.name" class="w-full h-full rounded-full object-cover border-4 border-dark-950">
                    </div>
                    
                    <div class="mt-3">
                        <span class="px-3.5 py-1.5 rounded-full bg-dark-900/90 border border-amber-500/40 text-amber-300 text-xs font-bold flex items-center gap-1.5 shadow-xl backdrop-blur-md">
                            <i data-lucide="{{ $wrapped['user']['tier_icon'] }}" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span x-text="data.user.tier_title"></span>
                        </span>
                    </div>
                </div>

                <div class="space-y-3 pb-3">
                    <p class="text-sm font-semibold text-zinc-200 drop-shadow">
                        Hai <strong class="text-amber-400" x-text="data.user.name"></strong>, siap melihat jejak cerita Anda?
                    </p>
                    <div class="text-[11px] text-zinc-400 font-mono flex items-center justify-center gap-1.5 animate-bounce">
                        <span>Ketuk layar untuk melanjutkan</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-amber-400"></i>
                    </div>
                </div>
            </div>

            <!-- SLIDE 1: TOTAL WATCH TIME & MILEAGE -->
            <div x-show="currentSlide === 1" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-5"
                 style="display:none;">
                
                <div class="space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        <span>Dedikasi Waktu</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white font-chillax drop-shadow">
                        Waktu di Depan Layar
                    </h2>
                </div>

                <!-- Big Stat Counter Card -->
                <div class="w-full space-y-3.5 my-auto">
                    <div class="glass-card rounded-3xl border border-white/20 p-6 bg-gradient-to-b from-amber-500/20 via-black/40 to-black/60 shadow-2xl backdrop-blur-xl">
                        <div class="text-5xl sm:text-6xl font-black font-mono text-white tracking-tight drop-shadow-lg" x-text="data.stats.total_hours"></div>
                        <div class="text-xs font-extrabold text-amber-300 uppercase tracking-widest mt-1">Total Jam Menonton</div>
                        <div class="text-xs text-zinc-400 mt-2 font-mono" x-text="'(' + data.stats.total_minutes.toLocaleString() + ' Menit)'"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="glass-card p-4 rounded-2xl border border-white/10 bg-black/40 backdrop-blur-md">
                            <div class="text-2xl font-black text-white font-mono" x-text="data.stats.total_titles"></div>
                            <div class="text-[11px] text-zinc-400 font-bold uppercase mt-0.5">Judul Ditonton</div>
                        </div>
                        <div class="glass-card p-4 rounded-2xl border border-white/10 bg-black/40 backdrop-blur-md">
                            <div class="text-2xl font-black text-amber-400 font-mono" x-text="data.user.streak_count"></div>
                            <div class="text-[11px] text-zinc-400 font-bold uppercase mt-0.5">Hari Streak 🔥</div>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-zinc-300 italic pb-2 px-4 leading-relaxed">
                    "Setiap jam yang Anda luangkan adalah apresiasi bagi para pencerita hebat."
                </p>
            </div>

            <!-- SLIDE 2: TOP 4/5 FILMS & SERIES -->
            <div x-show="currentSlide === 2" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col justify-between space-y-4"
                 style="display:none;">
                
                <div class="text-center space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                        <i data-lucide="film" class="w-4 h-4"></i>
                        <span>Koleksi Favorit</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax drop-shadow">
                        Top Film & Serial Anda
                    </h2>
                </div>

                <div class="space-y-2.5 my-auto">
                    <template x-for="(film, idx) in (data.top_films || []).slice(0, 4)" :key="film.id">
                        <div class="glass-card rounded-2xl p-3 border border-white/15 flex items-center gap-3 bg-black/40 backdrop-blur-md shadow-lg">
                            <div class="w-7 h-7 rounded-full font-mono font-black text-xs flex items-center justify-center shrink-0 border"
                                 :class="idx === 0 ? 'bg-amber-500/30 border-amber-400 text-amber-300' : (idx === 1 ? 'bg-zinc-400/20 border-zinc-400 text-zinc-300' : 'bg-white/10 border-white/20 text-zinc-400')" 
                                 x-text="'#' + (idx + 1)"></div>
                            <img :src="film.poster_url" :alt="film.title" class="w-10 h-14 rounded-lg object-cover border border-white/10 shrink-0">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-xs text-white truncate" x-text="film.title"></h4>
                                <div class="flex items-center gap-2 text-[10px] text-zinc-400 mt-0.5">
                                    <span class="px-1.5 py-0.5 rounded bg-white/10 text-zinc-300" x-text="film.type"></span>
                                    <span>•</span>
                                    <span class="text-amber-400 font-mono" x-text="film.minutes + ' Menit'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <p class="text-[11px] text-center text-zinc-400 pb-2">
                    Karya-karya yang paling setia menemani hari Anda.
                </p>
            </div>

            <!-- SLIDE 3: GENRE FINGERPRINT -->
            <div x-show="currentSlide === 3" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col justify-between space-y-4"
                 style="display:none;">
                
                <div class="text-center space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                        <i data-lucide="pie-chart" class="w-4 h-4"></i>
                        <span>DNA Sinematik</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax drop-shadow">
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
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 via-rose-500 to-purple-500 transition-all duration-700" 
                                     :style="{ width: genre.percentage + '%' }"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="glass-card p-3 rounded-2xl border border-white/15 bg-black/40 text-center text-xs text-zinc-200 backdrop-blur-md pb-2">
                    Genre teratas Anda: <strong class="text-amber-400" x-text="data.top_genres && data.top_genres[0] ? data.top_genres[0].name : 'Beragam'"></strong>
                </div>
            </div>

            <!-- SLIDE 4: VIEWING HABIT & CLOCK -->
            <div x-show="currentSlide === 4" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-6"
                 style="display:none;">
                
                <div class="space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Ritme Menonton</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax drop-shadow">
                        Waktu Favorit Anda
                    </h2>
                </div>

                <div class="my-auto space-y-5">
                    <div class="w-24 h-24 rounded-3xl bg-amber-500/20 border-2 border-amber-500/50 text-amber-400 flex items-center justify-center mx-auto shadow-2xl shadow-amber-500/20 backdrop-blur-md">
                        <i data-lucide="{{ $wrapped['habit']['icon'] }}" class="w-12 h-12"></i>
                    </div>

                    <div class="space-y-2 max-w-xs mx-auto">
                        <h3 class="text-xl font-black text-white" x-text="data.habit.title"></h3>
                        <p class="text-xs text-zinc-300 leading-relaxed px-3" x-text="data.habit.desc"></p>
                    </div>
                </div>

                <div class="text-[11px] text-zinc-400 font-mono pb-2">
                    Setiap momen selalu memiliki film yang tepat.
                </div>
            </div>

            <!-- SLIDE 5: CINEPHILE PERSONA ARCHETYPE -->
            <div x-show="currentSlide === 5" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col justify-between items-center text-center space-y-6"
                 style="display:none;">
                
                <div class="space-y-1">
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>Identitas Sinema</span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-white font-chillax drop-shadow">
                        Persona Cinephile Anda
                    </h2>
                </div>

                <!-- Archetype Reveal Card -->
                <div class="my-auto w-full glass-card p-6 rounded-3xl border-2 border-amber-400/60 bg-gradient-to-b from-amber-500/25 via-dark-900 to-black shadow-2xl space-y-4 backdrop-blur-xl">
                    <div class="w-16 h-16 rounded-2xl bg-amber-400/20 border border-amber-400 text-amber-300 flex items-center justify-center mx-auto shadow-lg">
                        <i data-lucide="{{ $wrapped['archetype']['icon'] }}" class="w-9 h-9"></i>
                    </div>

                    <div class="space-y-1.5">
                        <h3 class="text-xl font-extrabold text-white tracking-tight" x-text="data.archetype.title"></h3>
                        <p class="text-xs text-zinc-300 leading-relaxed italic px-2" x-text="data.archetype.tagline"></p>
                    </div>

                    <div class="pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                        <span class="text-zinc-400">Level Akun:</span>
                        <span class="font-bold text-amber-300" x-text="'Lv. ' + data.user.level + ' (' + data.user.tier_title + ')'"></span>
                    </div>
                </div>

                <p class="text-xs text-zinc-400 pb-2">
                    Karakter unik yang membentuk selera tontonan Anda.
                </p>
            </div>

            <!-- SLIDE 6: GRAND FINALE & HD 9:16 CARD EXPORT -->
            <div x-show="currentSlide === 6" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col justify-between space-y-3.5 pointer-events-auto"
                 style="display:none;">
                
                <div class="text-center space-y-1">
                    <h2 class="text-2xl font-black text-white font-chillax drop-shadow">
                        Rangkuman Story Anda
                    </h2>
                    <p class="text-[11px] text-zinc-400">Siap diunduh dan dipajang di Instagram Story / WhatsApp</p>
                </div>

                <!-- Story Summary Mini Card -->
                <div class="glass-card rounded-2xl p-4 border border-amber-500/40 bg-gradient-to-b from-amber-500/15 via-dark-900 to-black space-y-3 shadow-2xl backdrop-blur-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="p-[1.5px] rounded-full bg-gradient-to-tr from-amber-400 to-rose-500">
                                <img :src="data.user.avatar" :alt="data.user.name" class="w-9 h-9 rounded-full object-cover">
                            </div>
                            <div>
                                <h4 class="font-bold text-xs text-white" x-text="data.user.name"></h4>
                                <div class="text-[10px] text-amber-300 font-semibold" x-text="data.archetype.title"></div>
                            </div>
                        </div>
                        <span class="font-chillax font-black text-xs text-amber-400">faiilmov</span>
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

                    <div class="text-[10px] text-zinc-300 space-y-1 bg-white/5 p-2 rounded-xl border border-white/5">
                        <div class="font-bold text-amber-300 flex items-center gap-1">
                            <i data-lucide="tag" class="w-3 h-3"></i>
                            <span>Top Genre:</span>
                        </div>
                        <div class="text-zinc-200 truncate" x-text="(data.top_genres || []).map(g => g.name).join(', ') || 'Sinema Dunia'"></div>
                    </div>
                </div>

                <!-- Export & Share Buttons -->
                <div class="space-y-2 pt-1">
                    <button type="button" 
                            @click.stop="downloadStoryCard()" 
                            :disabled="isGeneratingImage"
                            class="w-full py-3 rounded-2xl bg-gradient-to-r from-amber-500 via-rose-500 to-purple-600 hover:opacity-95 text-white font-black text-xs transition-all flex items-center justify-center gap-2 shadow-xl shadow-amber-500/20 cursor-pointer active:scale-98 disabled:opacity-50">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span x-text="isGeneratingImage ? 'Menyiapkan Gambar HD...' : 'Unduh Kartu Story (9:16 HD)'"></span>
                    </button>

                    <button type="button" 
                            @click.stop="currentSlide = 0; startTimer()"
                            class="w-full py-2.5 rounded-2xl glass-card border border-white/15 hover:bg-white/10 text-xs font-bold text-zinc-200 transition-colors flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        <span>Putar Ulang Story</span>
                    </button>
                </div>

            </div>

        </div>

    </div>

    <!-- Desktop Outside Right Navigation Arrow -->
    <button type="button" 
            @click="nextSlide()" 
            :disabled="currentSlide === totalSlides - 1"
            class="hidden md:flex items-center justify-center w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md text-white border border-white/20 transition-all active:scale-90 cursor-pointer disabled:opacity-20 disabled:pointer-events-none z-40 ml-4 lg:ml-8 shadow-2xl"
            title="Slide Selanjutnya (Panah Kanan)">
        <i data-lucide="chevron-right" class="w-5 h-5"></i>
    </button>

    <!-- Period Selection Modal -->
    <div x-show="showPeriodModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" 
         style="display:none;"
         @click="showPeriodModal = false; resumeStory()">
        
        <div class="glass-panel w-full max-w-xs rounded-3xl p-5 border border-white/20 bg-dark-900/95 space-y-4 shadow-2xl" 
             @click.stop>
            <div class="flex items-center justify-between pb-2 border-b border-white/10">
                <h3 class="text-sm font-bold text-white">Pilih Periode Kilas Balik</h3>
                <button type="button" @click="showPeriodModal = false; resumeStory()" class="text-zinc-400 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-2">
                <a href="{{ route('wrapped', ['period' => 'year']) }}" 
                   class="w-full flex items-center justify-between p-3 rounded-2xl border transition-all text-xs font-bold {{ $period === 'year' ? 'bg-amber-500 text-zinc-950 border-amber-400 shadow-lg' : 'border-white/10 text-zinc-300 hover:bg-white/10' }}">
                    <span>Tahun Ini ({{ $year }})</span>
                    @if($period === 'year') <i data-lucide="check" class="w-4 h-4"></i> @endif
                </a>
                
                <a href="{{ route('wrapped', ['period' => 'month']) }}" 
                   class="w-full flex items-center justify-between p-3 rounded-2xl border transition-all text-xs font-bold {{ $period === 'month' ? 'bg-amber-500 text-zinc-950 border-amber-400 shadow-lg' : 'border-white/10 text-zinc-300 hover:bg-white/10' }}">
                    <span>Bulan Ini</span>
                    @if($period === 'month') <i data-lucide="check" class="w-4 h-4"></i> @endif
                </a>

                <a href="{{ route('wrapped', ['period' => 'all']) }}" 
                   class="w-full flex items-center justify-between p-3 rounded-2xl border transition-all text-xs font-bold {{ $period === 'all' ? 'bg-amber-500 text-zinc-950 border-amber-400 shadow-lg' : 'border-white/10 text-zinc-300 hover:bg-white/10' }}">
                    <span>Sepanjang Masa (All-Time)</span>
                    @if($period === 'all') <i data-lucide="check" class="w-4 h-4"></i> @endif
                </a>
            </div>
        </div>
    </div>

    <!-- Hidden Canvas for High-Resolution 1080x1920 (9:16) Export -->
    <canvas id="storyCanvas" width="1080" height="1920" style="display:none;"></canvas>

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
        isHolding: false,
        holdTimer: null,
        soundEnabled: true,
        showPeriodModal: false,
        isGeneratingImage: false,
        audioCtx: null,

        // Navigation Lock & Gesture State
        isNavigating: false,
        justFinishedHolding: false,
        justSwiped: false,

        // Touch tracking for mobile
        touchStartX: 0,
        touchStartY: 0,
        touchStartTime: 0,
        isSwipingDown: false,
        swipeTranslateY: 0,

        gradients: [
            'bg-gradient-to-tr from-purple-950 via-dark-950 to-amber-950',
            'bg-gradient-to-tr from-amber-950 via-dark-950 to-rose-950',
            'bg-gradient-to-tr from-indigo-950 via-dark-950 to-cyan-950',
            'bg-gradient-to-tr from-rose-950 via-dark-950 to-purple-950',
            'bg-gradient-to-tr from-blue-950 via-dark-950 to-indigo-950',
            'bg-gradient-to-tr from-amber-950 via-dark-950 to-yellow-950',
            'bg-gradient-to-tr from-purple-950 via-dark-950 to-amber-950'
        ],

        cardGradients: [
            'bg-gradient-to-b from-purple-950/40 via-dark-950 to-amber-950/30',
            'bg-gradient-to-b from-amber-950/40 via-dark-950 to-rose-950/30',
            'bg-gradient-to-b from-indigo-950/40 via-dark-950 to-cyan-950/30',
            'bg-gradient-to-b from-rose-950/40 via-dark-950 to-purple-950/30',
            'bg-gradient-to-b from-blue-950/40 via-dark-950 to-indigo-950/30',
            'bg-gradient-to-b from-amber-950/50 via-dark-950 to-yellow-950/40',
            'bg-gradient-to-b from-purple-950/50 via-dark-950 to-amber-950/40'
        ],

        get currentSlideGradient() {
            return this.gradients[this.currentSlide] || this.gradients[0];
        },

        get currentSlideCardGradient() {
            return this.cardGradients[this.currentSlide] || this.cardGradients[0];
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
                if (!this.isPaused && !this.isHolding) {
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

        /* Touch Gestures for Mobile IG feel */
        handleTouchStart(e) {
            if (!e.touches || e.touches.length !== 1) return;
            const touch = e.touches[0];
            this.touchStartX = touch.clientX;
            this.touchStartY = touch.clientY;
            this.touchStartTime = Date.now();
            this.isSwipingDown = false;
            this.swipeTranslateY = 0;

            // Long-press hold detection (220ms threshold)
            clearTimeout(this.holdTimer);
            this.holdTimer = setTimeout(() => {
                this.isHolding = true;
                this.pauseStory();
                if (navigator.vibrate) navigator.vibrate(15);
            }, 220);
        },

        handleTouchMove(e) {
            if (!e.touches || e.touches.length !== 1) return;
            const touch = e.touches[0];
            const deltaX = touch.clientX - this.touchStartX;
            const deltaY = touch.clientY - this.touchStartY;

            // If user moves finger before hold threshold, cancel hold
            if (Math.abs(deltaX) > 10 || Math.abs(deltaY) > 10) {
                clearTimeout(this.holdTimer);
            }

            // Downward drag to exit
            if (deltaY > 15 && Math.abs(deltaX) < deltaY) {
                this.isSwipingDown = true;
                this.swipeTranslateY = Math.min(deltaY * 0.8, 250);
                this.pauseStory();
            }
        },

        handleTouchEnd(e) {
            clearTimeout(this.holdTimer);
            const touchDuration = Date.now() - this.touchStartTime;

            if (this.isHolding) {
                this.isHolding = false;
                this.resumeStory();
                this.justFinishedHolding = true;
                setTimeout(() => { this.justFinishedHolding = false; }, 300);
                return;
            }

            // Swipe Down to Exit to Home
            if (this.isSwipingDown && this.swipeTranslateY > 70) {
                if (navigator.vibrate) navigator.vibrate(20);
                window.location.href = '{{ route('home') }}';
                return;
            }
            this.isSwipingDown = false;
            this.swipeTranslateY = 0;

            if (!e.changedTouches || e.changedTouches.length !== 1) return;
            const touch = e.changedTouches[0];
            const deltaX = touch.clientX - this.touchStartX;
            const deltaY = touch.clientY - this.touchStartY;

            // Horizontal Swipe Detection
            if (Math.abs(deltaX) > 50 && Math.abs(deltaY) < 60 && touchDuration < 350) {
                this.justSwiped = true;
                setTimeout(() => { this.justSwiped = false; }, 300);
                if (deltaX < 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
                return;
            }
        },

        handlePrevTap(e) {
            if (this.justFinishedHolding || this.justSwiped || this.isHolding || this.isSwipingDown) return;
            this.prevSlide();
        },

        handleNextTap(e) {
            if (this.justFinishedHolding || this.justSwiped || this.isHolding || this.isSwipingDown) return;
            this.nextSlide();
        },

        /* Desktop Mouse Interactions */
        handleMouseDown(e) {
            clearTimeout(this.holdTimer);
            this.holdTimer = setTimeout(() => {
                this.isHolding = true;
                this.pauseStory();
            }, 200);
        },

        handleMouseUp(e) {
            clearTimeout(this.holdTimer);
            if (this.isHolding) {
                this.isHolding = false;
                this.resumeStory();
                this.justFinishedHolding = true;
                setTimeout(() => { this.justFinishedHolding = false; }, 300);
            }
        },

        pauseStory() {
            this.isPaused = true;
        },

        resumeStory() {
            this.isPaused = false;
        },

        togglePlayPause() {
            if (this.isPaused) {
                this.resumeStory();
            } else {
                this.pauseStory();
            }
        },

        nextSlide() {
            if (this.isNavigating) return;
            if (this.currentSlide < this.totalSlides - 1) {
                this.isNavigating = true;
                this.currentSlide++;
                if (navigator.vibrate) navigator.vibrate(10);
                this.playSlideTone();
                this.startTimer();
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
                setTimeout(() => {
                    this.isNavigating = false;
                }, 250);
            }
        },

        prevSlide() {
            if (this.isNavigating) return;
            if (this.currentSlide > 0) {
                this.isNavigating = true;
                this.currentSlide--;
                if (navigator.vibrate) navigator.vibrate(10);
                this.playSlideTone();
                this.startTimer();
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
                setTimeout(() => {
                    this.isNavigating = false;
                }, 250);
            }
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            if (this.soundEnabled) this.playSlideTone();
        },

        sendReaction(emoji) {
            if (navigator.vibrate) navigator.vibrate(20);
            const id = Date.now() + Math.random();
            const x = 25 + Math.random() * 50; // Spread across center
            this.floatingReactions.push({ id, emoji, x });
            setTimeout(() => {
                this.floatingReactions = this.floatingReactions.filter(r => r.id !== id);
            }, 1800);
        },

        toggleLike() {
            this.isLiked = !this.isLiked;
            if (this.isLiked) {
                this.sendReaction('❤️');
                if (navigator.vibrate) navigator.vibrate([20, 50, 20]);
            }
        },

        playSlideTone() {
            if (!this.soundEnabled) return;
            try {
                if (!this.audioCtx) {
                    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = 'sine';
                const baseFreq = 440 + (this.currentSlide * 60);
                osc.frequency.setValueAtTime(baseFreq, this.audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(baseFreq * 1.4, this.audioCtx.currentTime + 0.12);

                gain.gain.setValueAtTime(0.06, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 0.12);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.12);
            } catch (e) {}
        },

        async shareOrDownloadStory() {
            if (this.isGeneratingImage) return;
            this.isGeneratingImage = true;

            try {
                const canvas = document.getElementById('storyCanvas');
                if (!canvas) return;
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
                ctx.fillStyle = 'rgba(245, 158, 11, 0.18)';
                ctx.filter = 'blur(80px)';
                ctx.beginPath();
                ctx.arc(540, 400, 350, 0, Math.PI * 2);
                ctx.fill();
                ctx.fillStyle = 'rgba(225, 29, 72, 0.12)';
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
                ctx.fillText(this.data.archetype ? this.data.archetype.title : 'The Eclectic Cinephile', 540, 460);

                ctx.fillStyle = '#d4d4d8';
                ctx.font = 'italic 26px sans-serif';
                ctx.fillText('"' + (this.data.archetype ? this.data.archetype.tagline : 'Penjelajah sinema sejati') + '"', 540, 520);

                ctx.fillStyle = '#a1a1aa';
                ctx.font = 'bold 26px sans-serif';
                ctx.fillText('LEVEL ' + this.data.user.level + ' • ' + (this.data.user.tier_title || '').toUpperCase(), 540, 620);

                // Stat Cards Grid
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
                ctx.fillText((this.data.habit ? this.data.habit.title : 'PRIME TIME WATCHER').toUpperCase(), 540, 1575);
                ctx.fillStyle = '#d4d4d8';
                ctx.font = '22px sans-serif';
                ctx.fillText(this.data.habit ? this.data.habit.desc : '', 540, 1620);

                // Watermark Footer
                ctx.fillStyle = '#71717a';
                ctx.font = '500 24px sans-serif';
                ctx.fillText('Tonton ribuan film & serial favorit di faiilmov', 540, 1800);

                // Check Web Share API (Mobile Native Share to Instagram / WhatsApp)
                if (navigator.share && canvas.toBlob) {
                    canvas.toBlob(async (blob) => {
                        const file = new File([blob], `faiilmov-wrapped-${this.period}.png`, { type: 'image/png' });
                        if (navigator.canShare && navigator.canShare({ files: [file] })) {
                            try {
                                await navigator.share({
                                    title: 'Movie Wrapped faiilmov',
                                    text: `Cek kilas balik tontonan Movie Wrapped saya di faiilmov! Total jam nonton: ${this.data.stats.total_hours} Jam`,
                                    files: [file],
                                });
                                this.isGeneratingImage = false;
                                return;
                            } catch (e) {
                                // Fallback to direct download
                            }
                        }
                        this.triggerDirectDownload(canvas);
                    }, 'image/png');
                } else {
                    this.triggerDirectDownload(canvas);
                }
            } catch (err) {
                console.error('Error generating story card:', err);
                this.isGeneratingImage = false;
            }
        },

        triggerDirectDownload(canvas) {
            const dataUrl = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = `faiilmov-wrapped-${(this.data.user.name || 'user').toLowerCase().replace(/\s+/g, '-')}-${this.period}.png`;
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            this.isGeneratingImage = false;
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
