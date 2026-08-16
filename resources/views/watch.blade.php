@extends('layouts.app')

@section('title', 'Streaming ' . $film->title . ' ' . ($film->subject_type === 'series' && isset($season) ? "Season {$season} Ep {$episode}" : '') . ' Subtitle Indonesia | faiilmov')
@section('meta_description', 'Putar & nonton streaming film ' . $film->title . ' subtitle Indonesia gratis full HD 1080p tanpa iklan mengganggu di faiilmov.')
@section('meta_keywords', 'streaming ' . $film->title . ', pemutar film ' . $film->title . ', ' . $film->title . ' sub indo full hd')
@section('og_type', 'video.other')
@section('og_image', $film->backdrop_url ?: $film->poster_url)

@section('content')
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
@php
    $selectedSeasonNumber = $season ?? 0;
    $selectedEpisodeNumber = $episode ?? 0;
    $isDracin = ($film->subject_type === 'dracin' || str_starts_with($film->moviebox_subject_id ?? '', 'anichin:'));
    $proxyActiveStream = $activeStream ? (
        $isDracin
            ? $activeStream
            : url('/moviebox/proxy-stream') . '?url=' . urlencode($activeStream) . '&id=' . urlencode($film->moviebox_subject_id) . '&se=' . $selectedSeasonNumber . '&ep=' . $selectedEpisodeNumber
    ) : '';
    
    // Sort resourceList so h264/avc codecs come before hevc
    $sortedList = $resourceList ?? [];
    usort($sortedList, function($a, $b) {
        $codecA = strtolower($a['codecName'] ?? '');
        $codecB = strtolower($b['codecName'] ?? '');
        $isH264A = in_array($codecA, ['h264', 'avc']);
        $isH264B = in_array($codecB, ['h264', 'avc']);
        if ($isH264A && !$isH264B) return -1;
        if (!$isH264A && $isH264B) return 1;
        return 0;
    });

    $processedQualities = [];
    $seenResolutions = [];

    foreach ($sortedList as $res) {
        $rawUrl = $res['resourceLink'] ?? $res['url'] ?? $res['playUrl'] ?? '';
        if (!$rawUrl) continue;

        $resNum = (int)preg_replace('/[^0-9]/', '', (string)($res['resolution'] ?? '1080'));
        if (!$resNum) $resNum = 1080;

        if (isset($seenResolutions[$resNum])) continue;
        $seenResolutions[$resNum] = true;

        $processedQualities[] = [
            'quality' => $resNum . 'p',
            'res_num' => $resNum,
            'codec'   => strtoupper($res['codecName'] ?? 'H264'),
            'size'    => isset($res['size']) ? number_format($res['size'] / 1048576, 1) . ' MB' : '',
            'url'     => url('/moviebox/proxy-stream') . '?url=' . urlencode($rawUrl) . '&id=' . urlencode($film->moviebox_subject_id) . '&se=' . $selectedSeasonNumber . '&ep=' . $selectedEpisodeNumber,
        ];
    }

    usort($processedQualities, fn($a, $b) => $b['res_num'] <=> $a['res_num']);
    $seasonsData = $film->seasons->map(function($s) {
        return [
            'id' => $s->id,
            'season_number' => $s->season_number,
            'title' => $s->title,
            'episodes' => $s->episodes->map(function($e) {
                return [
                    'id' => $e->id,
                    'episode_number' => $e->episode_number,
                    'title' => $e->title,
                    'synopsis' => $e->synopsis,
                    'duration_minutes' => $e->duration_minutes,
                    'thumbnail_url' => $e->thumbnail_url ?: 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=50&w=300',
                ];
            }),
        ];
    });

    $nextEpData = $nextEpisode ? [
        'season_number' => $nextEpisode->season->season_number,
        'episode_number' => $nextEpisode->episode_number,
        'title' => $nextEpisode->title,
        'url' => route('film.watch', $film->slug) . "?season={$nextEpisode->season->season_number}&episode={$nextEpisode->episode_number}",
    ] : null;
@endphp

<div class="min-h-screen bg-dark-950 pb-16" 
     x-data='customPlayer({
        filmId: {{ $film->id }},
        slug: @json($film->slug),
        subjectType: @json($film->subject_type),
        currentSeason: {{ $season }},
        currentEpisode: {{ $episode }},
        activeStream: @json($proxyActiveStream),
        qualities: @json($processedQualities),
        subtitles: @json($subtitles ?? []),
        audioTracks: @json($audioTracks ?? []),
        activeAudioSubjectId: @json($effectiveSubjectId ?? $film->moviebox_subject_id),
        seasons: @json($seasonsData),
        nextEpisode: @json($nextEpData)
     })'>
    
    <!-- Top Bar Navigation Header -->
    <div class="glass-panel border-b border-white/10 py-3.5 px-4 sm:px-8 backdrop-blur-md rounded-none relative z-40">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ route('film.show', $film->slug) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-300 hover:text-white transition-colors glass-card px-4 py-2 rounded-2xl border border-white/10">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Detail Film</span>
            </a>
        </div>
    </div>

    <!-- Resume Watch Prompt Banner -->
    <div x-show="showResumePrompt" 
         x-transition:enter="transition ease-out duration-300 transform -translate-y-2 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200 opacity-0"
         class="max-w-6xl mx-auto px-4 sm:px-6 pt-4 relative z-40">
        <div class="glass-panel p-4 rounded-2xl border border-zinc-700/60 flex items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-3">
                <i data-lucide="history" class="w-5 h-5 text-white"></i>
                <span>Lanjutkan menonton dari <strong><span x-text="formatTime(savedPos)"></span></strong>?</span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="resumeFromSaved()" class="px-3.5 py-1.5 rounded-xl bg-white text-zinc-950 font-bold hover:bg-zinc-200 transition-colors cursor-pointer">
                    Lanjutkan
                </button>
                <button @click="showResumePrompt = false" class="px-3 py-1.5 rounded-xl glass-card text-zinc-400 hover:text-white transition-colors cursor-pointer">
                    Abaikan
                </button>
            </div>
        </div>
    </div>

    <!-- Main Custom Video Player Theater Area -->
    <div x-ref="theaterPlaceholder" class="relative z-30 max-w-6xl mx-auto px-0 sm:px-6 py-0 sm:py-8">
        
        <!-- Placeholder card in theater area when video is floating in Mini Player -->
        <div x-show="isMiniPlayer" 
             x-transition:enter="transition ease-out duration-300 opacity-0"
             x-transition:enter-end="opacity-100"
             class="w-full aspect-video rounded-3xl glass-panel border border-white/10 flex flex-col items-center justify-center text-center p-6 gap-3 shadow-inner my-4" style="display:none;">
            <div class="w-12 h-12 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center animate-bounce">
                <i data-lucide="tv" class="w-6 h-6"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-white">Video Diputar dalam Mini Player</h4>
                <p class="text-xs text-zinc-400 mt-1">Video sedang melayang di sudut layar (Picture-in-Page)</p>
            </div>
            <button @click="expandMiniPlayer()" class="px-4 py-2 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-2 shadow-md cursor-pointer">
                <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Pemutar Utama</span>
            </button>
        </div>

        <!-- Video Player Wrapper (Full Custom Controls Base & Floating Mini Player) -->
        <div x-ref="playerContainer" 
             @mousemove="resetHideTimer()" 
             @mouseleave="startHideTimer()"
             @contextmenu.prevent
             :class="isMiniPlayer ? 'fixed bottom-4 right-4 sm:bottom-6 sm:right-6 w-72 sm:w-96 aspect-video z-[999] rounded-2xl shadow-2xl border border-white/25 glass-panel ring-2 ring-white/20 transition-all duration-300 overflow-hidden' : 'relative aspect-video w-full rounded-none sm:rounded-3xl overflow-hidden bg-black border-0 sm:border border-white/10 shadow-2xl group select-none'"
             :style="!showControls && isPlaying ? 'cursor: none !important;' : ''">
            
            <!-- Mini Player Top Floating Header Bar -->
            <div x-show="isMiniPlayer" 
                 class="absolute top-0 inset-x-0 z-50 bg-gradient-to-b from-black/90 via-black/60 to-transparent p-2.5 flex items-center justify-between gap-2 pointer-events-auto"
                 style="display:none;">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping shrink-0"></span>
                    <span class="text-[11px] font-bold text-white truncate">{{ $film->title }}</span>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button @click.stop="expandMiniPlayer()" 
                            class="p-1 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
                            title="Perbesar ke Layar Utama">
                        <i data-lucide="maximize" class="w-3.5 h-3.5"></i>
                    </button>
                    <button @click.stop="dismissMiniPlayer()" 
                            class="p-1 rounded-lg bg-white/10 hover:bg-red-500/80 text-white transition-colors cursor-pointer"
                            title="Tutup Mini Player">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
            
            <template x-if="activeStream">
                <div class="relative w-full h-full flex items-center justify-center bg-black">
                    
                    <!-- Native HTML5 Video Element -->
                    <video x-ref="video" 
                           :src="activeStream" 
                           x-on:error="handleVideoError($event)"
                           autoplay 
                           playsinline
                           referrerpolicy="no-referrer"
                           @click="handleSingleClick($event)"
                           @dblclick="handleDoubleClick($event)"
                           @mousedown="startPressAndHold()"
                           @mouseup="stopPressAndHold()"
                           @mouseleave="stopPressAndHold()"
                           :class="{
                               'object-contain transform-none': aspectRatioMode === 'contain',
                               'object-cover transform-none': aspectRatioMode === 'cover',
                               'object-fill transform-none': aspectRatioMode === 'fill',
                               'object-contain scale-125': aspectRatioMode === 'zoom',
                               'cursor-none': !showControls && isPlaying,
                               'cursor-pointer': showControls || !isPlaying
                           }"
                           class="w-full h-full transition-all duration-300"></video>

                    <!-- Top & Bottom Gradient Shadows for Controls Readability -->
                    <div class="absolute inset-0 pointer-events-none transition-opacity duration-300"
                         :class="showControls || !isPlaying ? 'opacity-100' : 'opacity-0'">
                        <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-black/80 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 h-36 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
                    </div>

                    <!-- SKIP INTRO BUTTON (Series Only) -->
                    <template x-if="subjectType === 'series' && introStart > 0 && currentTime >= introStart && !isIntroSkipped">
                        <div x-transition:enter="transition ease-out duration-300" 
                             x-transition:leave="transition ease-in duration-200"
                             class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-40">
                            <button @click="skipIntro()" 
                                    class="px-6 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-sm transition-colors flex items-center gap-2 shadow-lg backdrop-blur-sm border border-amber-500/30">
                                <i data-lucide="skip-forward" class="w-4 h-4 fill-zinc-950"></i>
                                <span>Skip Intro</span>
                            </button>
                        </div>
                    </template>

                    <!-- AUTO-PLAY NEXT EPISODE COUNTDOWN -->
                    <template x-if="nextEpisode && showAutoPlayCountdown">
                        <div x-transition:enter="transition ease-out duration-400"
                             x-transition:leave="transition ease-in duration-200"
                             class="absolute bottom-20 left-1/2 -translate-x-1/2 z-40">
                            <div class="glass-panel p-4 rounded-2xl border border-amber-500/40 shadow-2xl backdrop-blur-md bg-dark-950/80 flex items-center gap-4 min-w-[320px]">
                                <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0 border border-white/10">
                                    <img :src="nextEpisode.thumbnail_url" 
                                         :alt="nextEpisode.title"
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] text-zinc-400 mb-1">Episode Selanjutnya dalam <span x-text="autoPlayCountdown" class="text-amber-400 font-bold"></span>s</p>
                                    <h4 class="text-xs font-bold text-white truncate" x-text="nextEpisode.title"></h4>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button @click="playNextEpisode()" 
                                            class="px-3 py-1.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors cursor-pointer">
                                        Putar
                                    </button>
                                    <button @click="cancelAutoPlay()" 
                                            class="p-1.5 rounded-xl hover:bg-white/20 text-zinc-300 hover:text-white transition-colors cursor-pointer">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Center Loading & Buffering Spinner -->
                    <div x-show="isBuffering" 
                         x-transition:enter="transition ease-out duration-200 opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200 opacity-0"
                         class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center gap-3 bg-black/60 backdrop-blur-md z-35">
                        <div class="w-12 h-12 rounded-full border-3 border-white/20 border-t-white animate-spin"></div>
                        <span class="text-xs font-semibold text-zinc-300">Memuat Video...</span>
                    </div>

                    <!-- 2x Speeding Up Badge Indicator (Press & Hold) -->
                    <div x-show="showSpeedingBadge" 
                         x-transition:enter="transition ease-out duration-200 scale-95 opacity-0"
                         x-transition:enter-end="scale-100 opacity-100"
                         class="absolute top-6 left-1/2 -translate-x-1/2 z-20 glass-chip px-4 py-1.5 rounded-full text-xs font-bold text-white flex items-center gap-2 border border-white/20 shadow-xl">
                        <i data-lucide="zap" class="w-4 h-4 fill-white text-white"></i>
                        <span>2x Speeding Up</span>
                    </div>

                    <!-- Double-Click Rewind (-5s) Ripple Indicator -->
                    <div x-show="rippleSide === 'rewind'" 
                         x-transition:enter="transition ease-out duration-200 scale-75 opacity-0"
                         x-transition:enter-end="scale-100 opacity-100"
                         x-transition:leave="transition ease-in duration-300 opacity-0"
                         class="absolute left-12 top-1/2 -translate-y-1/2 z-20 pointer-events-none glass-panel p-5 rounded-full border border-white/20 text-white flex flex-col items-center justify-center gap-1 shadow-2xl">
                        <i data-lucide="rotate-ccw" class="w-8 h-8"></i>
                        <span class="text-xs font-extrabold">-5 Detik</span>
                    </div>

                    <!-- Double-Click Forward (+5s) Ripple Indicator -->
                    <div x-show="rippleSide === 'forward'" 
                         x-transition:enter="transition ease-out duration-200 scale-75 opacity-0"
                         x-transition:enter-end="scale-100 opacity-100"
                         x-transition:leave="transition ease-in duration-300 opacity-0"
                         class="absolute right-12 top-1/2 -translate-y-1/2 z-20 pointer-events-none glass-panel p-5 rounded-full border border-white/20 text-white flex flex-col items-center justify-center gap-1 shadow-2xl">
                        <i data-lucide="rotate-cw" class="w-8 h-8"></i>
                        <span class="text-xs font-extrabold">+5 Detik</span>
                    </div>

                    <!-- Center Video Quick Controls Overlay (YouTube Mobile Style: -5s, Play/Pause, +5s) -->
                    <div class="absolute inset-0 z-25 pointer-events-none flex items-center justify-center gap-6 sm:gap-14 transition-opacity duration-300"
                         :class="!isMiniPlayer && !isBuffering && (showControls || !isPlaying) ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">

                        <!-- Rewind 5s Center Button -->
                        <button @click.stop="seek(currentTime - 5); triggerRipple('rewind')" 
                                class="w-11 h-11 sm:w-14 sm:h-14 rounded-full glass-panel flex flex-col items-center justify-center border border-white/20 text-white shadow-2xl hover:scale-110 active:scale-95 transition-all cursor-pointer bg-black/40 backdrop-blur-md"
                                title="-5 Detik">
                            <i data-lucide="rotate-ccw" class="w-4 h-4 sm:w-6 sm:h-6"></i>
                            <span class="text-[8px] sm:text-[9px] font-extrabold -mt-0.5">5s</span>
                        </button>

                        <!-- Center Play/Pause Button -->
                        <button @click.stop="togglePlay()" 
                                class="w-14 h-14 sm:w-16 sm:h-16 rounded-full glass-panel flex items-center justify-center border border-white/30 text-white shadow-2xl hover:scale-110 active:scale-95 transition-all cursor-pointer bg-white/15 backdrop-blur-lg"
                                title="Play/Pause">
                            <svg x-show="!isPlaying" class="w-6 h-6 sm:w-8 sm:h-8 fill-white ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <svg x-show="isPlaying" class="w-6 h-6 sm:w-8 sm:h-8 fill-white" viewBox="0 0 24 24" style="display: none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        </button>

                        <!-- Forward 5s Center Button -->
                        <button @click.stop="seek(currentTime + 5); triggerRipple('forward')" 
                                class="w-11 h-11 sm:w-14 sm:h-14 rounded-full glass-panel flex flex-col items-center justify-center border border-white/20 text-white shadow-2xl hover:scale-110 active:scale-95 transition-all cursor-pointer bg-black/40 backdrop-blur-md"
                                title="+5 Detik">
                            <i data-lucide="rotate-cw" class="w-4 h-4 sm:w-6 sm:h-6"></i>
                            <span class="text-[8px] sm:text-[9px] font-extrabold -mt-0.5">5s</span>
                        </button>
                    </div>

                    <!-- Center Pulse Feedback Indicator (Single Click) -->
                    <div x-show="centerPulseIcon" 
                         x-transition:enter="transition ease-out duration-150 scale-50 opacity-0"
                         x-transition:enter-end="scale-100 opacity-100"
                         x-transition:leave="transition ease-in duration-200 opacity-0"
                         class="absolute inset-0 z-20 pointer-events-none flex items-center justify-center">
                        <div class="w-16 h-16 rounded-full glass-panel flex items-center justify-center border border-white/20 shadow-2xl">
                            <i data-lucide="play" x-show="isPlaying" class="w-8 h-8 text-white fill-white ml-1"></i>
                            <i data-lucide="pause" x-show="!isPlaying" class="w-8 h-8 text-white fill-white"></i>
                        </div>
                    </div>

                    <!-- Top Controls Overlay Bar (Title & Quick Settings) -->
                    <div class="absolute inset-x-0 top-0 z-40 p-2.5 sm:p-5 transition-opacity duration-300 flex items-center justify-between gap-2 pointer-events-auto"
                         :class="!isMiniPlayer && (showControls || !isPlaying) ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">
                        
                        <!-- Film Title & Episode Badge -->
                        <div class="flex items-center gap-2 text-white min-w-0 pr-2">
                            <h3 class="text-xs sm:text-sm font-bold truncate drop-shadow-md">
                                {{ $film->title }}
                                <template x-if="subjectType === 'series'">
                                    <span class="text-[10px] sm:text-[11px] font-semibold text-amber-300 ml-1.5 px-2 py-0.5 rounded-lg bg-amber-500/20 border border-amber-500/30 shrink-0">
                                        S<span x-text="currentSeason"></span>:E<span x-text="currentEpisode"></span>
                                    </span>
                                </template>
                            </h3>
                        </div>

                        <!-- Top Right Settings Group: Subtitle, Quality, Speed, Aspect Ratio -->
                        <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                            
                            <!-- Subtitle / Caption Selector Dropdown -->
                            <div class="relative" @click.outside="subtitleDropdownOpen = false">
                                <button @click.stop="subtitleDropdownOpen = !subtitleDropdownOpen; audioDropdownOpen = false; speedDropdownOpen = false; qualityDropdownOpen = false; aspectRatioDropdownOpen = false" 
                                        :class="activeSubtitle !== 'off' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-zinc-200 hover:text-white hover:border-white/30'"
                                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-xl glass-chip text-[10px] sm:text-[11px] font-bold transition-all cursor-pointer flex items-center gap-1 sm:gap-1.5"
                                        title="Subtitle / Teks">
                                    <i data-lucide="subtitles" class="w-3.5 h-3.5"></i>
                                    <span>CC</span>
                                </button>

                                <div x-show="subtitleDropdownOpen" 
                                     x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                     x-transition:enter-end="scale-100 opacity-100"
                                     class="absolute top-full right-0 mt-2 w-56 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-50 max-w-[85vw]"
                                     style="display: none;">
                                    <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="subtitles" class="w-3 h-3"></i>
                                        <span>Subtitle</span>
                                    </div>
                                    
                                    <!-- Off Option -->
                                    <button @click.stop="setSubtitle('off'); subtitleDropdownOpen = false" 
                                            :class="activeSubtitle === 'off' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                            class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="subtitles" class="w-3.5 h-3.5 opacity-60"></i>
                                            <span>Matikan Subtitle</span>
                                        </span>
                                        <i x-show="activeSubtitle === 'off'" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                    </button>

                                    <!-- Empty State (No Subtitles from API) -->
                                    <template x-if="subtitles.length === 0">
                                        <div class="px-2.5 py-2 text-[11px] text-zinc-400 italic flex items-center gap-1.5 border-t border-b border-white/5 my-1">
                                            <i data-lucide="info" class="w-3.5 h-3.5 text-zinc-500 shrink-0"></i>
                                            <span>Tidak ada subtitle otomatis</span>
                                        </div>
                                    </template>

                                    <!-- API Subtitles List -->
                                    <template x-if="subtitles.length > 0">
                                        <div class="border-t border-white/5 mt-1 pt-1">
                                            <div class="text-[9px] font-bold text-zinc-500 px-2 pt-1 pb-1 uppercase tracking-wider">Tersedia</div>
                                            <div class="max-h-48 overflow-y-auto space-y-0.5">
                                                <template x-for="sub in subtitles" :key="sub.id || sub.url">
                                                    <button @click.stop="setSubtitle(sub.url, sub.label, sub.srclang); subtitleDropdownOpen = false" 
                                                            :class="activeSubtitle === sub.url ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                                            class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                                        <span class="flex items-center gap-2 truncate">
                                                            <span class="px-1.5 py-0.5 rounded-md bg-white/10 text-[9px] font-extrabold uppercase tracking-wide shrink-0" 
                                                                  :class="activeSubtitle === sub.url ? 'bg-zinc-950/20 text-zinc-950' : ''"
                                                                  x-text="(sub.srclang || 'id').toUpperCase().substring(0,2)"></span>
                                                            <span class="truncate" x-text="sub.label"></span>
                                                        </span>
                                                        <i x-show="activeSubtitle === sub.url" data-lucide="check" class="w-3 h-3 text-zinc-950 shrink-0 ml-1"></i>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Custom Subtitle File Upload -->
                                    <div class="pt-1.5 mt-1.5 border-t border-white/10">
                                        <label class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs text-amber-300 hover:bg-white/10 transition-colors flex items-center gap-1.5 cursor-pointer">
                                            <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
                                            <span>Upload (.srt / .vtt)</span>
                                            <input type="file" accept=".srt,.vtt" @change="handleCustomSubtitleUpload($event); subtitleDropdownOpen = false" class="hidden">
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Audio Track Selector Dropdown -->
                            <div class="relative" @click.outside="audioDropdownOpen = false">
                                <button @click.stop="audioDropdownOpen = !audioDropdownOpen; subtitleDropdownOpen = false; speedDropdownOpen = false; qualityDropdownOpen = false; aspectRatioDropdownOpen = false" 
                                        :class="audioTracks.length > 1 ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-zinc-200 hover:text-white hover:border-white/30'"
                                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-xl glass-chip text-[10px] sm:text-[11px] font-bold transition-all cursor-pointer flex items-center gap-1 sm:gap-1.5"
                                        title="Pilihan Audio & Bahasa Suara">
                                    <i data-lucide="headphones" class="w-3.5 h-3.5"></i>
                                    <span x-text="audioTrackBadgeText"></span>
                                </button>

                                <div x-show="audioDropdownOpen" 
                                     x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                     x-transition:enter-end="scale-100 opacity-100"
                                     class="absolute top-full right-0 mt-2 w-64 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-50 max-w-[85vw]"
                                     style="display: none;">
                                    <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="headphones" class="w-3 h-3"></i>
                                            <span>Trek Audio / Bahasa</span>
                                        </span>
                                        <template x-if="audioTracks.length > 0">
                                            <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-white/10 text-zinc-300 font-bold" x-text="audioTracks.length + ' Trek'"></span>
                                        </template>
                                    </div>
                                    
                                    <!-- Multiple Audio Tracks List -->
                                    <template x-if="audioTracks.length > 0">
                                        <div class="max-h-48 overflow-y-auto space-y-0.5 mt-0.5">
                                            <template x-for="(track, idx) in audioTracks" :key="track.subjectId || track.id || idx">
                                                <button @click.stop="setAudioTrack(track)" 
                                                        :class="isTrackActive(track, idx) ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                                        class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                                    <span class="flex items-center gap-2 truncate">
                                                        <span class="px-1.5 py-0.5 rounded-md bg-white/10 text-[9px] font-extrabold uppercase tracking-wide shrink-0" 
                                                              :class="isTrackActive(track, idx) ? 'bg-zinc-950/20 text-zinc-950' : ''"
                                                              x-text="getTrackLangBadge(track)"></span>
                                                        <span class="truncate" x-text="track.label || track.lanName || track.name || ('Audio ' + (idx + 1))"></span>
                                                    </span>
                                                    <i x-show="isTrackActive(track, idx)" data-lucide="check" class="w-3 h-3 text-zinc-950 shrink-0 ml-1"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Single / Default Audio Track State -->
                                    <template x-if="audioTracks.length === 0">
                                        <div class="space-y-1 mt-0.5">
                                            <button @click.stop="audioDropdownOpen = false" 
                                                    class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs bg-white text-zinc-950 font-bold flex items-center justify-between cursor-pointer">
                                                <span class="flex items-center gap-2 truncate">
                                                    <span class="px-1.5 py-0.5 rounded-md bg-zinc-950/20 text-zinc-950 text-[9px] font-extrabold uppercase tracking-wide shrink-0">ORI</span>
                                                    <span class="truncate">Audio Utama (Original / Stereo)</span>
                                                </span>
                                                <i data-lucide="check" class="w-3 h-3 text-zinc-950 shrink-0 ml-1"></i>
                                            </button>
                                            <div class="px-2 py-1 text-[10px] text-zinc-400 italic flex items-center gap-1.5">
                                                <i data-lucide="info" class="w-3 h-3 text-zinc-500 shrink-0"></i>
                                                <span>1 Trek audio bawaan video</span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Audio Equalizer & Enhancement Profile Section -->
                                    <div class="pt-1.5 mt-1.5 border-t border-white/10">
                                        <div class="text-[9px] font-bold text-zinc-400 px-2 pt-0.5 pb-1 uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="sliders" class="w-2.5 h-2.5"></i>
                                            <span>Peningkat Suara</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1 p-0.5">
                                            <button @click.stop="setAudioProfile('normal')" 
                                                    :class="audioProfile === 'normal' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold' : 'text-zinc-400 hover:text-white border-white/5 bg-white/5'"
                                                    class="px-2 py-1 rounded-lg text-[10px] border transition-all text-center truncate cursor-pointer">
                                                Standar
                                            </button>
                                            <button @click.stop="setAudioProfile('vocal')" 
                                                    :class="audioProfile === 'vocal' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold' : 'text-zinc-400 hover:text-white border-white/5 bg-white/5'"
                                                    class="px-2 py-1 rounded-lg text-[10px] border transition-all text-center truncate cursor-pointer"
                                                    title="Meningkatkan kejernihan percakapan dialog">
                                                Jernih Dialog
                                            </button>
                                            <button @click.stop="setAudioProfile('bass')" 
                                                    :class="audioProfile === 'bass' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold' : 'text-zinc-400 hover:text-white border-white/5 bg-white/5'"
                                                    class="px-2 py-1 rounded-lg text-[10px] border transition-all text-center truncate cursor-pointer"
                                                    title="Meningkatkan frekuensi bass sinematik">
                                                Bass Sinema
                                            </button>
                                            <button @click.stop="setAudioProfile('night')" 
                                                    :class="audioProfile === 'night' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold' : 'text-zinc-400 hover:text-white border-white/5 bg-white/5'"
                                                    class="px-2 py-1 rounded-lg text-[10px] border transition-all text-center truncate cursor-pointer"
                                                    title="Meredam lonjakan efek suara mendadak di malam hari">
                                                Mode Malam
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resolution / Quality Selector Dropdown -->
                            <div class="relative" @click.outside="qualityDropdownOpen = false" x-show="qualities.length > 0">
                                <button @click.stop="qualityDropdownOpen = !qualityDropdownOpen; audioDropdownOpen = false; speedDropdownOpen = false; subtitleDropdownOpen = false; aspectRatioDropdownOpen = false" 
                                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-xl glass-chip text-[10px] sm:text-[11px] font-bold text-zinc-200 hover:text-white hover:border-white/30 transition-all cursor-pointer flex items-center gap-1">
                                    <span x-text="activeQuality"></span>
                                    <i data-lucide="chevron-down" class="w-3 h-3 text-zinc-400"></i>
                                </button>

                                <div x-show="qualityDropdownOpen" 
                                     x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                     x-transition:enter-end="scale-100 opacity-100"
                                     class="absolute top-full right-0 mt-2 w-36 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-50"
                                     style="display: none;">
                                    <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider">Kualitas Video</div>
                                    <template x-for="q in qualities" :key="q.url">
                                        <button @click.stop="setQuality(q.url, q.quality); qualityDropdownOpen = false" 
                                                :class="activeQuality === q.quality ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                                class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                            <div>
                                                <span x-text="q.quality"></span>
                                                <span class="text-[9px] block text-zinc-400" x-text="q.codec"></span>
                                            </div>
                                            <i x-show="activeQuality === q.quality" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Playback Speed Selector Dropdown -->
                            <div class="relative" @click.outside="speedDropdownOpen = false">
                                <button @click.stop="speedDropdownOpen = !speedDropdownOpen; audioDropdownOpen = false; qualityDropdownOpen = false; subtitleDropdownOpen = false; aspectRatioDropdownOpen = false" 
                                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-xl glass-chip text-[10px] sm:text-[11px] font-bold text-zinc-200 hover:text-white hover:border-white/30 transition-all cursor-pointer flex items-center gap-1">
                                    <span x-text="playbackSpeed + 'x'"></span>
                                </button>

                                <div x-show="speedDropdownOpen" 
                                     x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                     x-transition:enter-end="scale-100 opacity-100"
                                     class="absolute top-full right-0 mt-2 w-28 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-50"
                                     style="display: none;">
                                    <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider">Kecepatan</div>
                                    <template x-for="spd in speeds" :key="spd">
                                        <button @click.stop="setPlaybackSpeed(spd); speedDropdownOpen = false" 
                                                :class="playbackSpeed === spd ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                                class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                            <span x-text="spd + 'x'"></span>
                                            <i x-show="playbackSpeed === spd" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Display Mode / Aspect Ratio Selector Dropdown -->
                            <div class="relative" @click.outside="aspectRatioDropdownOpen = false">
                                <button @click.stop="aspectRatioDropdownOpen = !aspectRatioDropdownOpen; audioDropdownOpen = false; speedDropdownOpen = false; qualityDropdownOpen = false; subtitleDropdownOpen = false" 
                                        :class="aspectRatioMode !== 'contain' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-zinc-200 hover:text-white hover:border-white/30'"
                                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-xl glass-chip text-[10px] sm:text-[11px] font-bold transition-all cursor-pointer flex items-center gap-1 sm:gap-1.5"
                                        title="Gaya Layar (Fit, Cover, Stretch, Zoom)">
                                    <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
                                    <span x-text="aspectRatioMode === 'contain' ? 'Fit' : (aspectRatioMode === 'cover' ? 'Cover' : (aspectRatioMode === 'fill' ? 'Stretch' : 'Zoom'))"></span>
                                </button>

                                <div x-show="aspectRatioDropdownOpen" 
                                     x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                     x-transition:enter-end="scale-100 opacity-100"
                                     class="absolute top-full right-0 mt-2 w-44 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-50 max-w-[85vw]"
                                     style="display: none;">
                                    <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="scan" class="w-3 h-3"></i>
                                        <span>Gaya Layar (Aspect)</span>
                                    </div>

                                    <!-- Fit (Default) -->
                                    <button @click.stop="setAspectRatioMode('contain')" 
                                            :class="aspectRatioMode === 'contain' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                            class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="shrink" class="w-3.5 h-3.5 opacity-70"></i>
                                            <span>Fit (Proporsional)</span>
                                        </span>
                                        <i x-show="aspectRatioMode === 'contain'" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                    </button>

                                    <!-- Cover (Crop) -->
                                    <button @click.stop="setAspectRatioMode('cover')" 
                                            :class="aspectRatioMode === 'cover' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                            class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="maximize-2" class="w-3.5 h-3.5 opacity-70"></i>
                                            <span>Cover (Potong Tepi)</span>
                                        </span>
                                        <i x-show="aspectRatioMode === 'cover'" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                    </button>

                                    <!-- Stretch (Fill) -->
                                    <button @click.stop="setAspectRatioMode('fill')" 
                                            :class="aspectRatioMode === 'fill' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                            class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="scaling" class="w-3.5 h-3.5 opacity-70"></i>
                                            <span>Stretch (Penuhi Layar)</span>
                                        </span>
                                        <i x-show="aspectRatioMode === 'fill'" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                    </button>

                                    <!-- Zoom 125% -->
                                    <button @click.stop="setAspectRatioMode('zoom')" 
                                            :class="aspectRatioMode === 'zoom' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                            class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="zoom-in" class="w-3.5 h-3.5 opacity-70"></i>
                                            <span>Zoom (125%)</span>
                                        </span>
                                        <i x-show="aspectRatioMode === 'zoom'" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Bottom Controls Overlay (Scrubber & Primary Playback Actions) -->
                    <div class="absolute inset-x-0 bottom-0 z-30 p-2 sm:p-5 pb-2.5 sm:pb-6 transition-opacity duration-300 flex flex-col justify-end gap-2 sm:gap-3"
                         :class="!isMiniPlayer && (showControls || !isPlaying) ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">
                        
                        <!-- Timeline Scrubber Bar -->
                        <div class="relative w-full h-3 flex items-center cursor-pointer group/scrubber" 
                             @click="handleScrubberClick($event)"
                             @mousemove="handleScrubberHover($event)"
                             @mouseleave="showHoverTooltip = false">
                            
                            <div class="w-full h-1.5 group-hover/scrubber:h-2.5 bg-white/20 rounded-full overflow-hidden transition-all duration-150 relative">
                                <div class="absolute top-0 bottom-0 left-0 bg-white/30 rounded-full" :style="'width: ' + bufferedPercent + '%'"></div>
                                <div class="absolute top-0 bottom-0 left-0 bg-white rounded-full transition-all duration-75" :style="'width: ' + progressPercent + '%'"></div>
                            </div>
                            
                            <div class="absolute w-3.5 h-3.5 bg-white rounded-full shadow-lg scale-0 group-hover/scrubber:scale-100 transition-transform duration-150 -translate-x-1/2 pointer-events-none"
                                 :style="'left: ' + progressPercent + '%'"></div>

                            <div x-show="showHoverTooltip" 
                                 :style="'left: ' + hoverPos + 'px'" 
                                 class="absolute bottom-5 -translate-x-1/2 px-2 py-1 bg-black/90 text-[10px] font-bold text-white rounded border border-white/20 shadow-md pointer-events-none" style="display: none;">
                                <span x-text="formatTime(hoverTime)"></span>
                            </div>
                        </div>

                        <!-- Control Buttons Row -->
                        <div class="flex items-center justify-between gap-1 sm:gap-4 py-0.5 relative z-40">
                            
                            <!-- Left Controls Group: Play/Pause, Next Ep, Volume, Timestamp -->
                            <div class="flex items-center gap-1 sm:gap-2.5 shrink-0">
                                <!-- Play / Pause Toggle Button -->
                                <button @click="togglePlay()" class="p-1.5 sm:p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-white flex items-center justify-center" title="Play/Pause (Space)">
                                    <svg x-show="!isPlaying" class="w-4 h-4 sm:w-5 sm:h-5 fill-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    <svg x-show="isPlaying" class="w-4 h-4 sm:w-5 sm:h-5 fill-white" viewBox="0 0 24 24" style="display: none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                </button>

                                 <!-- Skip Rewind 5s (Desktop Only) -->
                                <button @click.stop="seek(currentTime - 5); triggerRipple('rewind')" class="p-1.5 sm:p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white hidden sm:flex items-center justify-center" title="-5 Detik">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                </button>

                                <!-- Skip Forward 5s (Desktop Only) -->
                                <button @click.stop="seek(currentTime + 5); triggerRipple('forward')" class="p-1.5 sm:p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white hidden sm:flex items-center justify-center" title="+5 Detik">
                                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                </button>

                                <!-- Next Episode Button (Series Only) -->
                                <template x-if="nextEpisode">
                                    <button @click="playNextEpisode()" 
                                            class="flex items-center gap-1 px-2 sm:px-3 py-1 sm:py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-[10px] sm:text-xs font-bold text-white transition-colors cursor-pointer border border-white/15 shadow-sm"
                                            title="Episode Selanjutnya">
                                        <i data-lucide="skip-forward" class="w-3 h-3 sm:w-3.5 sm:h-3.5 fill-white"></i>
                                        <span class="hidden sm:inline">Next</span>
                                    </button>
                                </template>

                                <!-- Volume Control Group -->
                                <div class="flex items-center gap-1 group/volume relative" @mouseenter="showVolumeSlider = true" @mouseleave="showVolumeSlider = false">
                                    <button @click="toggleMute()" class="p-1.5 sm:p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white flex items-center justify-center" title="Mute / Unmute (M)">
                                        <svg x-show="isMuted || volume === 0" class="w-4 h-4 sm:w-5 sm:h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
                                        <svg x-show="!isMuted && volume > 0 && volume < 0.5" class="w-4 h-4 sm:w-5 sm:h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" style="display: none;"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                        <svg x-show="!isMuted && volume >= 0.5" class="w-4 h-4 sm:w-5 sm:h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" style="display: none;"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                    </button>

                                    <!-- Volume Slider (Desktop Only) -->
                                    <input type="range" 
                                           min="0" 
                                           max="1" 
                                           step="0.05" 
                                           :value="isMuted ? 0 : volume"
                                           @input="setVolume($event.target.value)"
                                           class="w-14 sm:w-16 h-1 accent-white bg-white/30 rounded-lg cursor-pointer transition-all duration-200 hidden md:block">
                                </div>

                                <!-- Timestamp Display -->
                                <div class="text-[10px] sm:text-[11px] font-semibold text-zinc-300 tracking-tight sm:tracking-wider whitespace-nowrap shrink-0 ml-0.5 sm:ml-1">
                                    <span x-text="formatTime(currentTime)"></span>
                                    <span class="text-zinc-500 mx-0.5">/</span>
                                    <span x-text="formatTime(duration)"></span>
                                </div>
                            </div>

                            <!-- Right Controls Group: Mini Player, PiP, Fullscreen -->
                            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                                
                                <!-- In-App Mini Player (Picture-in-Page) Toggle -->
                                <button @click.stop="toggleMiniPlayer()" 
                                        :class="isMiniPlayer ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'text-zinc-300 hover:text-white'"
                                        class="p-1.5 sm:p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer flex items-center justify-center border border-transparent" 
                                        title="Mini Player (Picture-in-Page)">
                                    <i data-lucide="airplay" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                </button>

                                <!-- Picture-in-Picture Toggle -->
                                <button @click="togglePiP()" class="p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white hidden sm:block" title="Picture-in-Picture (OS)">
                                    <i data-lucide="picture-in-picture-2" class="w-4 h-4"></i>
                                </button>

                                <!-- Fullscreen Toggle -->
                                <button @click="toggleFullscreen()" class="p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white flex items-center justify-center" title="Fullscreen (F)">
                                    <svg x-show="!isFullscreen" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                                    <svg x-show="isFullscreen" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" style="display: none;"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path></svg>
                                </button>

                            </div>

                        </div>

                    </div>

                </div>
            </template>

            <!-- Video Stream Fallback -->
            <template x-if="!activeStream">
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center bg-dark-900/90 backdrop-blur-md">
                    <i data-lucide="video-off" class="w-12 h-12 text-zinc-500"></i>
                    <h3 class="font-serif font-bold text-lg text-white">Stream Video Belum Tersedia</h3>
                    <p class="text-xs text-zinc-400 max-w-md">Maaf, link pemutaran video langsung belum dapat dijangkau dari server API saat ini. Coba pilih episode atau resolusi lain di bawah.</p>
                </div>
            </template>

        </div>

        <!-- Lower Content Section (Info, Episode Selector, Downloads, Synopsis) -->
        <div class="px-4 sm:px-0">

        <!-- SERIES ONLY: Netflix-style Season & Episode Selector Section -->
        <template x-if="subjectType === 'series' && seasons.length > 0">
            <div class="mt-8 glass-panel p-6 sm:p-8 rounded-3xl border border-white/10 shadow-xl backdrop-blur-md space-y-6">
                
                <!-- Season Header & Selector Pills -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
                    <div class="flex items-center gap-3">
                        <i data-lucide="tv" class="w-5 h-5 text-amber-400"></i>
                        <h3 class="font-serif font-bold text-xl text-white">Pilih Season & Episode</h3>
                    </div>

                    <!-- Season Horizontal Tabs -->
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                        <template x-for="s in seasons" :key="s.id">
                            <button @click="currentSeason = s.season_number"
                                    :class="currentSeason === s.season_number ? 'bg-white text-zinc-950 font-bold shadow-md' : 'glass-card text-zinc-300 hover:text-white border-white/10'"
                                    class="px-4 py-2 rounded-2xl text-xs transition-all whitespace-nowrap cursor-pointer">
                                <span x-text="'Season ' + s.season_number"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Episodes Vertical List -->
                <div class="space-y-3 max-h-[30rem] overflow-y-auto pr-1" x-ref="epList">
                    <template x-for="ep in currentSeasonEpisodes" :key="ep.id">
                        <div @click="switchEpisode(currentSeason, ep.episode_number)"
                             :data-active="currentSeason === selectedSeasonNumber && currentEpisode === ep.episode_number"
                             :class="currentSeason === selectedSeasonNumber && currentEpisode === ep.episode_number ? 'bg-white/10 border-amber-400/60 ring-1 ring-amber-400/40' : 'bg-white/5 border-white/10 hover:bg-white/10 hover:border-white/20'"
                             class="p-3.5 rounded-2xl border transition-all cursor-pointer flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 group">
                            
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                <!-- Episode Thumbnail -->
                                <div class="relative w-28 sm:w-36 aspect-video rounded-xl overflow-hidden bg-dark-900 shrink-0 border border-white/10">
                                    <img :src="ep.thumbnail_url" :alt="ep.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    
                                    <template x-if="currentSeason === selectedSeasonNumber && currentEpisode === ep.episode_number">
                                        <div class="absolute inset-0 bg-amber-500/30 backdrop-blur-[1px] flex items-center justify-center">
                                            <i data-lucide="play" class="w-6 h-6 fill-white text-white drop-shadow-md"></i>
                                        </div>
                                    </template>
                                </div>

                                <!-- Episode Details -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            Episode <span x-text="ep.episode_number"></span>
                                        </span>
                                        <template x-if="currentSeason === selectedSeasonNumber && currentEpisode === ep.episode_number">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                Sedang Diputar
                                            </span>
                                        </template>
                                    </div>
                                    <h4 class="text-xs sm:text-sm font-semibold text-white mt-1.5 truncate group-hover:text-amber-300 transition-colors" x-text="ep.title"></h4>
                                    <p class="text-[11px] text-zinc-400 line-clamp-1 mt-0.5" x-text="ep.synopsis"></p>
                                </div>
                            </div>

                            <!-- Duration & Action -->
                            <div class="flex items-center gap-3 shrink-0 self-end sm:self-center text-xs text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-zinc-500"></i>
                                    <span x-text="ep.duration_minutes + ' m'"></span>
                                </span>
                                <i data-lucide="play-circle" class="w-5 h-5 text-zinc-500 group-hover:text-amber-400 transition-colors"></i>
                            </div>

                        </div>
                    </template>
                </div>

            </div>
        </template>

        <!-- Video Info & Direct Download Section -->
        <div class="mt-6 glass-panel p-6 sm:p-8 rounded-3xl border border-white/10 shadow-xl backdrop-blur-md">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-6 border-b border-white/10">
                <div>
                    <h1 class="font-serif font-bold text-2xl text-white flex items-center gap-3">
                        <span>{{ $film->title }}</span>
                        <template x-if="subjectType === 'series'">
                            <span class="text-xs font-semibold px-3 py-1 rounded-xl glass-chip text-white border border-white/20">
                                Season <span x-text="currentSeason"></span> • Episode <span x-text="currentEpisode"></span>
                            </span>
                        </template>
                    </h1>
                    <div class="flex items-center gap-3 text-xs text-zinc-400 mt-2">
                        <span class="flex items-center gap-1 glass-chip text-amber-400 font-bold px-2.5 py-1 rounded-xl text-[11px]">
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <span>{{ number_format($film->rating, 1) }}</span>
                        </span>
                        <span>•</span>
                        <span>{{ $film->release_year }}</span>
                        <span>•</span>
                        <span>{{ $film->duration_minutes }} Menit</span>
                        <span>•</span>
                        <span class="text-zinc-300 font-semibold uppercase text-[10px]">{{ $film->subject_type }}</span>
                    </div>
                </div>

                <!-- Share Button Component -->
                <div class="relative" x-data="{ 
                    shareOpen: false, 
                    copied: false,
                    shareUrl: window.location.href,
                    filmTitle: '{{ addslashes($film->title) }}',
                    copyLink() {
                        navigator.clipboard.writeText(this.shareUrl);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2500);
                    },
                    doShare(platform) {
                        let text = encodeURIComponent('Nonton film ' + this.filmTitle + ' gratis di faiilmov!');
                        let url = encodeURIComponent(this.shareUrl);
                        let target = '';
                        if (platform === 'wa') target = 'https://api.whatsapp.com/send?text=' + text + '%20' + url;
                        if (platform === 'tg') target = 'https://t.me/share/url?url=' + url + '&text=' + text;
                        if (platform === 'tw') target = 'https://twitter.com/intent/tweet?text=' + text + '&url=' + url;
                        if (platform === 'fb') target = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
                        if (target) window.open(target, '_blank', 'width=600,height=400');
                    }
                }" @click.outside="shareOpen = false">
                    
                    <button type="button" @click="if(navigator.share) { navigator.share({title: filmTitle + ' - faiilmov', text: 'Nonton ' + filmTitle + ' di faiilmov', url: shareUrl}).catch(()=>{}); } else { shareOpen = !shareOpen; }"
                            @contextmenu.prevent="shareOpen = !shareOpen"
                            class="px-4 py-2 rounded-2xl glass-card hover:border-amber-400/40 text-white hover:text-amber-300 text-xs font-semibold transition-all duration-300 flex items-center gap-2 cursor-pointer border border-white/10 hover:bg-white/10 shadow-sm"
                            title="Bagikan Film Ini">
                        <i data-lucide="share-2" class="w-4 h-4 text-amber-400"></i>
                        <span>Bagikan</span>
                    </button>

                    <!-- Social Share Dropdown Popover -->
                    <div x-show="shareOpen" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                         class="absolute right-0 top-full mt-3 w-56 p-3 bg-zinc-900/95 border border-white/15 rounded-2xl shadow-2xl backdrop-blur-2xl z-50 space-y-2"
                         style="display: none;">
                        
                        <p class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider px-2 pt-1">Bagikan Tayangan Ini</p>
                        
                        <div class="grid grid-cols-2 gap-1.5 text-xs">
                            <button type="button" @click="doShare('wa')" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 transition-colors cursor-pointer">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                <span>WhatsApp</span>
                            </button>
                            <button type="button" @click="doShare('tg')" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/20 transition-colors cursor-pointer">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Telegram</span>
                            </button>
                            <button type="button" @click="doShare('tw')" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white border border-white/10 transition-colors cursor-pointer">
                                <i data-lucide="twitter" class="w-3.5 h-3.5"></i>
                                <span>Twitter / X</span>
                            </button>
                            <button type="button" @click="doShare('fb')" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 transition-colors cursor-pointer">
                                <i data-lucide="facebook" class="w-3.5 h-3.5"></i>
                                <span>Facebook</span>
                            </button>
                        </div>

                        <div class="pt-1 border-t border-white/10">
                            <button type="button" @click="copyLink()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs transition-colors cursor-pointer">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="link" class="w-3.5 h-3.5 text-amber-400"></i>
                                    <span x-text="copied ? 'Tautan Tersalin!' : 'Salin Tautan'"></span>
                                </div>
                                <i data-lucide="copy" class="w-3 h-3 text-zinc-400"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download Options -->
            <div>
                <h4 class="font-serif font-semibold text-sm text-white mb-3 flex items-center gap-2">
                    <i data-lucide="download" class="w-4 h-4 text-zinc-300"></i>
                    <span>Download MP4 Direct Link</span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" x-show="qualities.length > 0">
                    <template x-for="q in qualities" :key="q.url">
                        <div class="glass-card p-3 rounded-2xl flex items-center justify-between border border-white/10 hover:border-white/30 transition-colors">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-white" x-text="q.quality"></span>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30" x-text="q.codec"></span>
                                </div>
                                <span class="text-[11px] text-zinc-400 block mt-1" x-text="q.size ? q.size : 'Direct Stream'"></span>
                            </div>

                            <a :href="q.url" target="_blank" download class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-1.5 shadow-md">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Download</span>
                            </a>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- Synopsis & Info Section Below Player -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Interactive Synopsis & AI Insights Glass Panel -->
            <div class="lg:col-span-2 glass-panel p-6 sm:p-7 rounded-3xl border border-white/10 space-y-4 relative overflow-hidden" 
                 x-data="synopsisWidget()">
                
                <!-- Ambient AI Top Glow (active when AI summary is open) -->
                <div x-show="showAiSummary" x-transition 
                     class="absolute -top-20 -right-20 w-64 h-64 bg-amber-500/15 rounded-full blur-3xl pointer-events-none" style="display: none;"></div>

                <!-- Header Bar with Title & Action Chips -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-white/10 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-serif font-bold text-lg sm:text-xl text-white">Sinopsis & Alur Cerita</h3>
                        </div>
                    </div>

                    <!-- AI & Translation Action Buttons -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Translate Button -->
                        <button type="button" 
                                @click="toggleTranslate()"
                                :disabled="isTranslating"
                                :class="isTranslated ? 'bg-amber-500 text-zinc-950 font-bold border-amber-400 shadow-md' : 'bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white border-white/10 hover:border-amber-500/30'"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer border shadow-sm disabled:opacity-50 group">
                            <template x-if="isTranslating">
                                <svg class="animate-spin w-3.5 h-3.5 text-amber-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <template x-if="!isTranslating">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 shrink-0" :class="isTranslated ? 'text-zinc-950' : 'text-amber-400'">
                                    <path d="m5 8 6 6"/>
                                    <path d="m4 14 6-6 2-3"/>
                                    <path d="M2 5h12"/>
                                    <path d="M7 2h1"/>
                                    <path d="m22 22-5-10-5 10"/>
                                    <path d="M14 18h6"/>
                                </svg>
                            </template>
                            <span x-text="isTranslating ? 'Menerjemahkan...' : (isTranslated ? 'Tampilkan Asli' : 'Terjemahkan (ID)')"></span>
                        </button>

                        <!-- AI Summary Toggle Button -->
                        <button type="button" 
                                @click="toggleAiSummary()"
                                :class="showAiSummary ? 'bg-amber-500 text-zinc-950 font-extrabold shadow-lg shadow-amber-500/25 border-amber-400' : 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border-amber-500/30 hover:border-amber-400/50'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer border shadow-sm group">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none" class="w-3.5 h-3.5 shrink-0 transition-transform group-hover:scale-110" :class="showAiSummary ? 'text-zinc-950 fill-zinc-950' : 'text-amber-400 fill-amber-400'">
                                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                            </svg>
                            <span>Ringkasan AI</span>
                            <span :class="showAiSummary ? 'bg-zinc-950/20 text-zinc-950' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'" 
                                  class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded-md leading-none tracking-wide">TL;DR</span>
                        </button>
                    </div>
                </div>

                <!-- Main Synopsis Body -->
                <div class="relative space-y-3">
                    <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed whitespace-pre-line transition-all duration-300"
                       :class="!isExpanded && currentText.length > 350 ? 'line-clamp-4' : ''"
                       x-text="currentText"></p>

                    <!-- Expand/Collapse Button for Long Synopsis -->
                    <button type="button" 
                            x-show="currentText.length > 350" 
                            @click="isExpanded = !isExpanded"
                            class="text-xs font-bold text-amber-400 hover:text-amber-300 transition-colors inline-flex items-center gap-1 cursor-pointer">
                        <span x-text="isExpanded ? 'Sembunyikan Sebagian' : 'Baca Selengkapnya'"></span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="isExpanded ? 'rotate-180' : ''"></i>
                    </button>
                </div>

                <!-- AI SUMMARY & STORY INSIGHTS PANEL (Expandable) -->
                <div x-show="showAiSummary" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
                     class="mt-4 p-5 rounded-2xl bg-zinc-950/80 border border-amber-500/30 space-y-4 shadow-xl backdrop-blur-md relative"
                     style="display: none;">
                    
                    <!-- AI Panel Badge Header -->
                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="p-1 rounded-lg bg-amber-500/20 text-amber-400">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </span>
                            <span class="text-xs font-extrabold text-white font-['Outfit'] uppercase tracking-wider">AI Story Insights & Poin Kunci</span>
                        </div>
                        <span class="text-[10px] font-mono text-zinc-400 bg-white/5 px-2 py-0.5 rounded-full border border-white/10">Llama 3.1 AI</span>
                    </div>

                    <!-- Loading State -->
                    <div x-show="isLoadingSummary" class="py-8 flex flex-col items-center justify-center space-y-3">
                        <div class="w-8 h-8 rounded-full border-2 border-amber-500 border-t-transparent animate-spin"></div>
                        <p class="text-xs text-zinc-400 animate-pulse">Sedang menganalisis alur dan membuat poin ringkasan AI...</p>
                    </div>

                    <!-- AI Content Result -->
                    <div x-show="!isLoadingSummary && aiData" class="space-y-4">
                        
                        <!-- 1. Quick Recap Summary -->
                        <div class="space-y-1.5">
                            <h4 class="text-[11px] font-extrabold uppercase text-amber-400 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                <span>Ringkasan Kilat</span>
                            </h4>
                            <p class="text-xs sm:text-sm text-zinc-200 leading-relaxed font-medium" x-text="aiData?.summary"></p>
                        </div>

                        <!-- 2. Key Story Hooks / Highlights (3 Bullets) -->
                        <div class="space-y-2">
                            <h4 class="text-[11px] font-extrabold uppercase text-purple-400 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="target" class="w-3.5 h-3.5"></i>
                                <span>Poin Kunci Cerita</span>
                            </h4>
                            <div class="space-y-2">
                                <template x-for="(point, pIdx) in (aiData?.key_points || [])" :key="pIdx">
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-lg bg-amber-500/20 border border-amber-500/30 text-amber-300 text-[10px] font-extrabold flex items-center justify-center shrink-0 mt-0.5" x-text="pIdx + 1"></span>
                                        <p class="text-xs text-zinc-300 leading-relaxed" x-text="point"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- 3. Mood & Vibes + Why to Watch -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <!-- Vibes -->
                            <div class="p-3 rounded-xl bg-white/5 border border-white/10 space-y-1.5">
                                <span class="text-[10px] font-extrabold uppercase text-zinc-400 tracking-wider block">Mood & Atmosfer:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="v in (aiData?.vibes || [])" :key="v">
                                        <span class="px-2 py-0.5 rounded-md bg-amber-500/10 border border-amber-500/20 text-[10px] font-bold text-amber-300" x-text="v"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Why Watch -->
                            <div class="p-3 rounded-xl bg-white/5 border border-white/10 space-y-1.5">
                                <span class="text-[10px] font-extrabold uppercase text-zinc-400 tracking-wider block">Alasan Nonton:</span>
                                <p class="text-xs text-zinc-300 italic" x-text="'“' + (aiData?.why_watch || '') + '”'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Related -->
            @if(isset($relatedFilms) && count($relatedFilms) > 0)
                <div class="space-y-3">
                    <h3 class="font-serif font-bold text-sm text-white">Rekomendasi Lainnya</h3>
                    @foreach($relatedFilms as $rel)
                        <a href="{{ route('film.watch', $rel->slug) }}" class="glass-card p-2.5 rounded-2xl border border-white/10 flex items-center gap-3 hover:border-white/30 transition-colors">
                            <img src="{{ $rel->poster_url }}" alt="{{ $rel->title }}" class="w-12 h-16 object-cover rounded-xl bg-dark-900">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-semibold text-white truncate">{{ $rel->title }}</h4>
                                <div class="flex items-center gap-2 mt-1 text-[11px] text-zinc-400">
                                    <span>{{ $rel->release_year }}</span>
                                    <span class="text-amber-400 font-bold flex items-center gap-0.5">
                                        <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                        <span>{{ number_format($rel->rating, 1) }}</span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        </div>

    </div>

</div>

<script>
    function customPlayer(config) {
        return {
            filmId: config.filmId,
            slug: config.slug,
            subjectType: config.subjectType,
            currentSeason: config.currentSeason || 1,
            currentEpisode: config.currentEpisode || 1,
            selectedSeasonNumber: config.currentSeason || 1,
            activeStream: config.activeStream,
            qualities: config.qualities || [],
            activeQuality: (config.qualities && config.qualities[0]) ? config.qualities[0].quality : '1080p',
            seasons: config.seasons || [],
            nextEpisode: config.nextEpisode || null,
            
            hlsInstance: null,
            introStart: 0,
            introEnd: 0,
            isIntroSkipped: false,
            showAutoPlayCountdown: false,
            autoPlayCountdown: 10,
            autoPlayTimer: null,
            
            isPlaying: false,
            currentTime: 0,
            duration: 0,
            bufferedPercent: 0,
            progressPercent: 0,
            volume: 1,
            isMuted: false,
            playbackSpeed: 1.0,
            speeds: [0.5, 0.75, 1.0, 1.25, 1.5, 2.0],
            
            showControls: true,
            hideTimer: null,
            isBuffering: false,
            isFullscreen: false,
            isPiP: false,
            
            hoverTime: 0,
            hoverPos: 0,
            showHoverTooltip: false,
            
            showSpeedingBadge: false,
            pressTimer: null,
            rippleSide: null,
            rippleTimer: null,
            centerPulseIcon: false,
            centerPulseTimer: null,
            
            savedPos: 0,
            showResumePrompt: false,
            speedDropdownOpen: false,
            qualityDropdownOpen: false,
            subtitleDropdownOpen: false,
            audioDropdownOpen: false,
            aspectRatioDropdownOpen: false,
            aspectRatioMode: localStorage.getItem('faii_player_aspect_mode') || 'contain',
            subtitles: config.subtitles || [],
            activeSubtitle: 'off',
            audioTracks: config.audioTracks || [],
            activeAudioSubjectId: config.activeAudioSubjectId || '{{ $film->moviebox_subject_id }}',
            activeAudioTrack: config.activeAudioSubjectId || -1,
            audioProfile: localStorage.getItem('faiilmov_player_audio_profile') || 'normal',
            audioCtx: null,
            audioSourceNode: null,
            audioFilterNode: null,
            audioCompressorNode: null,
            audioGainNode: null,
            clickTimer: null,
            isMiniPlayer: false,
            isMiniDismissed: false,

            get audioTrackBadgeText() {
                if (this.audioTracks.length > 1) {
                    const t = this.audioTracks.find(item => 
                        (item.subjectId && (item.subjectId === this.activeAudioSubjectId || item.subjectId === this.activeAudioTrack)) ||
                        ((item.id ?? item.index) === this.activeAudioTrack) ||
                        item.is_current
                    );
                    if (t) {
                        const badge = t.badge || (t.lanCode || t.lang || t.name || '').substring(0, 3).toUpperCase();
                        return badge || 'Audio';
                    }
                }
                return 'Audio';
            },

            getTrackLangBadge(track) {
                if (track.badge) return track.badge;
                const code = (track.lanCode || track.lang || track.language || track.name || track.label || 'ID').trim();
                return code.length <= 3 ? code.toUpperCase() : code.substring(0, 2).toUpperCase();
            },

            isTrackActive(track, idx) {
                if (track.subjectId) {
                    return (track.subjectId === this.activeAudioSubjectId || track.subjectId === this.activeAudioTrack || (!this.activeAudioSubjectId && track.is_current));
                }
                return this.activeAudioTrack === (track.id ?? idx);
            },

            setAspectRatioMode(mode) {
                this.aspectRatioMode = mode;
                this.aspectRatioDropdownOpen = false;
                localStorage.setItem('faii_player_aspect_mode', mode);
            },

            toggleMiniPlayer() {
                if (this.isMiniPlayer) {
                    this.expandMiniPlayer();
                } else {
                    this.isMiniPlayer = true;
                    this.isMiniDismissed = false;
                }
            },

            expandMiniPlayer() {
                this.isMiniPlayer = false;
                this.isMiniDismissed = false;
                if (this.$refs.theaterPlaceholder) {
                    this.$refs.theaterPlaceholder.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            },

            dismissMiniPlayer() {
                this.isMiniPlayer = false;
                this.isMiniDismissed = true;
            },

            get currentSeasonEpisodes() {
                const s = this.seasons.find(item => item.season_number === this.currentSeason);
                return s ? s.episodes : [];
            },

            init() {
                this.$nextTick(() => {
                    const video = this.$refs.video;
                    if (!video) return;

                    this.attachHlsOrSrc(this.activeStream);

                    this.$watch('activeStream', (newStream) => {
                        this.attachHlsOrSrc(newStream);
                    });

                    // IntersectionObserver for Auto Floating Mini Player when scrolling
                    if (this.$refs.theaterPlaceholder) {
                        const observer = new IntersectionObserver((entries) => {
                            const entry = entries[0];
                            if (!entry.isIntersecting && this.isPlaying && !this.isMiniDismissed && this.currentTime > 2) {
                                this.isMiniPlayer = true;
                            } else if (entry.isIntersecting && this.isMiniPlayer) {
                                this.isMiniPlayer = false;
                            }
                        }, { threshold: 0.25 });

                        observer.observe(this.$refs.theaterPlaceholder);
                    }

                    // Load saved position from localStorage
                    const saved = localStorage.getItem('faiilmov_watch_pos_' + this.filmId + '_s' + this.currentSeason + '_e' + this.currentEpisode);
                    if (saved) {
                        const pos = parseFloat(saved);
                        if (pos > 10) {
                            this.savedPos = pos;
                            this.showResumePrompt = true;
                        }
                    }

                    // Auto seek if URL query string contains ?t= (from Mini Player Expand)
                    const urlParams = new URLSearchParams(window.location.search);
                    const timeParam = urlParams.get('t');
                    if (timeParam && !isNaN(parseFloat(timeParam))) {
                        video.currentTime = parseFloat(timeParam);
                        this.showResumePrompt = false;
                    }

                    // Attach HTML5 Video Event Listeners
                    video.addEventListener('timeupdate', () => {
                        this.currentTime = video.currentTime;
                        this.duration = video.duration || 0;
                        this.progressPercent = this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
                        
                        // Check for Skip Intro
                        if (this.subjectType === 'series' && this.introStart > 0 && !this.isIntroSkipped && this.currentTime >= this.introStart) {
                            // Don't auto-skip, just show the button
                        }
                        
                        // Auto-play countdown - STRICT validation
                        // Only trigger when:
                        // 1. There IS a next episode
                        // 2. Countdown not already showing
                        // 3. duration is valid (> 60 seconds to avoid false positives)
                        // 4. video is NOT buffering
                        // 5. video IS actually playing
                        // 6. progressPercent >= 95% (truly near the end, not just loading)
                        if (
                            this.nextEpisode &&
                            !this.showAutoPlayCountdown &&
                            this.duration > 60 &&
                            !this.isBuffering &&
                            this.isPlaying &&
                            this.progressPercent >= 95
                        ) {
                            this.showAutoPlayCountdown = true;
                            this.autoPlayCountdown = 10;
                            if (this.autoPlayTimer) clearInterval(this.autoPlayTimer);
                            this.autoPlayTimer = setInterval(() => {
                                this.autoPlayCountdown--;
                                if (this.autoPlayCountdown <= 0) {
                                    this.playNextEpisode();
                                }
                            }, 1000);
                        }
                        
                        // Auto save position every 5s to localStorage & server
                        if (Math.floor(this.currentTime) % 5 === 0 && this.currentTime > 5) {
                            localStorage.setItem('faiilmov_watch_pos_' + this.filmId + '_s' + this.currentSeason + '_e' + this.currentEpisode, this.currentTime);
                            this.saveWatchProgressServer();
                        }

                        // Sync active stream state for Global Cross-Page Floating Mini Player
                        if (this.isPlaying && this.activeStream && this.currentTime > 2) {
                            try {
                                localStorage.setItem('faiilmov_global_miniplayer', JSON.stringify({
                                    active: true,
                                    filmTitle: '{{ $film->title }}',
                                    streamUrl: this.activeStream,
                                    currentTime: this.currentTime,
                                    duration: this.duration,
                                    posterUrl: '{{ $film->poster_url }}',
                                    watchUrl: window.location.pathname + (window.location.search ? window.location.search.replace(/[\?&]t=\d+/, '') : ''),
                                    timestamp: Date.now()
                                }));
                            } catch(e) {}
                        }
                    });

                    video.addEventListener('progress', () => {
                        if (video.buffered.length > 0 && video.duration > 0) {
                            const end = video.buffered.end(video.buffered.length - 1);
                            this.bufferedPercent = (end / video.duration) * 100;
                        }
                    });

                    video.addEventListener('waiting', () => { this.isBuffering = true; });
                    video.addEventListener('loadedmetadata', () => {
                        if (video.audioTracks && video.audioTracks.length > 0) {
                            this.syncNativeAudioTracks(video.audioTracks);
                        }
                    });
                    video.addEventListener('canplay', () => { 
                        this.isBuffering = false;
                        // Update duration from real video metadata when stream is ready
                        if (video.duration && isFinite(video.duration) && video.duration > 0) {
                            this.duration = video.duration;
                        }
                    });
                    video.addEventListener('playing', () => { 
                        this.isBuffering = false; 
                        this.isPlaying = true;
                        this.resetHideTimer();
                    });
                    video.addEventListener('pause', () => { 
                        this.isPlaying = false; 
                        this.showControls = true;
                    });
                    video.addEventListener('ended', () => {
                        this.isPlaying = false;
                        this.showControls = true;
                        
                        // Only trigger auto-play if video truly ended (progress >= 98%)
                        if (this.progressPercent >= 98 && this.duration > 0) {
                            localStorage.removeItem('faiilmov_watch_pos_' + this.filmId + '_s' + this.currentSeason + '_e' + this.currentEpisode);
                            
                            // Autoplay Next Episode if available
                            if (this.nextEpisode && !this.showAutoPlayCountdown) {
                                setTimeout(() => {
                                    this.playNextEpisode();
                                }, 500);
                            }
                        }
                    });
                    video.addEventListener('volumechange', () => {
                        this.volume = video.volume;
                        this.isMuted = video.muted;
                    });

                    // Keyboard Shortcuts
                    window.addEventListener('keydown', (e) => {
                        if (['input', 'textarea', 'select'].includes(document.activeElement.tagName.toLowerCase())) return;
                        
                        if (e.code === 'Space') {
                            e.preventDefault();
                            this.togglePlay();
                        } else if (e.code === 'KeyF') {
                            e.preventDefault();
                            this.toggleFullscreen();
                        } else if (e.code === 'KeyM') {
                            e.preventDefault();
                            this.toggleMute();
                        } else if (e.code === 'ArrowLeft') {
                            e.preventDefault();
                            this.seek(this.currentTime - 5);
                            this.triggerRipple('rewind');
                        } else if (e.code === 'ArrowRight') {
                            e.preventDefault();
                            this.seek(this.currentTime + 5);
                            this.triggerRipple('forward');
                        } else if (e.code === 'ArrowUp') {
                            e.preventDefault();
                            this.setVolume(Math.min(1, this.volume + 0.1));
                        } else if (e.code === 'ArrowDown') {
                            e.preventDefault();
                            this.setVolume(Math.max(0, this.volume - 0.1));
                        }
                    });

                    // Fullscreen change listener
                    const handleFullscreenChange = () => {
                        this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
                        if (this.isFullscreen) {
                            this.lockLandscape();
                        } else {
                            this.unlockOrientation();
                        }
                    };

                    document.addEventListener('fullscreenchange', handleFullscreenChange);
                    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);

                    // Subtitle Auto-Fetch & Auto-Enable
                    if (this.subtitles.length === 0) {
                        this.fetchSubtitles();
                    } else if (this.subtitles.length > 0) {
                        const defaultSub = this.subtitles.find(s => s.srclang === 'id') || this.subtitles[0];
                        if (defaultSub && (this.activeSubtitle === 'off' || !this.activeSubtitle)) {
                            this.setSubtitle(defaultSub.url, defaultSub.label, defaultSub.srclang);
                        }
                    }

                    // Audio Dubs Auto-Fetch
                    if (this.audioTracks.length === 0) {
                        this.fetchAudioTracks();
                    }

                    lucide.createIcons();
                });
            },

            async fetchSubtitles() {
                const mbSubjectId = '{{ $film->moviebox_subject_id }}';
                if (!mbSubjectId) return;
                try {
                    const res = await fetch(`/moviebox/subtitles/${mbSubjectId}?se=${this.currentSeason}&ep=${this.currentEpisode}`);
                    if (res.ok) {
                        const data = await res.json();
                        if (Array.isArray(data) && data.length > 0) {
                            this.subtitles = data;
                            if (this.activeSubtitle === 'off' || !this.activeSubtitle) {
                                const defaultSub = data.find(s => s.srclang === 'id') || data[0];
                                if (defaultSub) {
                                    this.setSubtitle(defaultSub.url, defaultSub.label, defaultSub.srclang);
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.error('Fetch subtitles error:', e);
                }
            },

            async fetchAudioTracks() {
                const mbSubjectId = '{{ $film->moviebox_subject_id }}';
                if (!mbSubjectId || mbSubjectId.startsWith('anichin:')) return;
                try {
                    const res = await fetch(`/moviebox/audios/${mbSubjectId}`);
                    if (res.ok) {
                        const data = await res.json();
                        if (Array.isArray(data) && data.length > 0) {
                            this.audioTracks = data;
                            const currentTrack = data.find(t => t.original) || data.find(t => t.is_current) || data[0];
                            if (currentTrack && (!this.activeAudioSubjectId || this.activeAudioTrack === -1)) {
                                this.activeAudioSubjectId = currentTrack.subjectId;
                                this.activeAudioTrack = currentTrack.subjectId;
                            }
                            if (window.lucide) this.$nextTick(() => lucide.createIcons());
                        }
                    }
                } catch (e) {
                    console.error('Fetch audio tracks error:', e);
                }
            },

            async switchEpisode(seasonNum, episodeNum) {
                this.isBuffering = true;
                this.failedStreams = new Set();
                this.currentSeason = seasonNum;
                this.currentEpisode = episodeNum;
                this.selectedSeasonNumber = seasonNum;
                
                // CRITICAL: Reset countdown and timer when switching episode
                this.cancelAutoPlay();
                this.isIntroSkipped = false;

                const audioParam = this.activeAudioSubjectId ? `&audio_subject_id=${this.activeAudioSubjectId}` : '';
                const url = `/film/${this.slug}/watch?season=${seasonNum}&episode=${episodeNum}${audioParam}`;
                window.history.pushState(null, '', url);

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        this.activeStream = data.proxyActiveStream;
                        const rawList = (data.resourceList || []).sort((a, b) => {
                            const codecA = (a.codecName || '').toLowerCase();
                            const codecB = (b.codecName || '').toLowerCase();
                            const isH264A = codecA === 'h264' || codecA === 'avc';
                            const isH264B = codecB === 'h264' || codecB === 'avc';
                            if (isH264A && !isH264B) return -1;
                            if (!isH264A && isH264B) return 1;
                            return 0;
                        });

                        const seenRes = new Set();
                        const parsedQualities = [];

                        for (const r of rawList) {
                            const rawUrl = r.resourceLink || r.url || r.playUrl || '';
                            if (!rawUrl) continue;
                            const resNum = parseInt((r.resolution || '1080').toString().replace(/[^0-9]/g, '')) || 1080;
                            if (seenRes.has(resNum)) continue;
                            seenRes.add(resNum);

                            parsedQualities.push({
                                quality: resNum + 'p',
                                res_num: resNum,
                                codec: (r.codecName || 'H264').toUpperCase(),
                                size: r.size ? (r.size / 1048576).toFixed(1) + ' MB' : '',
                                url: '/moviebox/proxy-stream?url=' + encodeURIComponent(rawUrl)
                            });
                        }

                        parsedQualities.sort((a, b) => b.res_num - a.res_num);
                        this.qualities = parsedQualities;

                        this.activeQuality = this.qualities.length ? this.qualities[0].quality : '1080p';
                        this.subtitles = data.subtitles || [];
                        if (this.subtitles.length === 0) {
                            this.fetchSubtitles();
                        }
                        this.nextEpisode = data.nextEpisode;
                        
                        // Reset intro skip and auto-play countdown
                        this.isIntroSkipped = false;
                        this.showAutoPlayCountdown = false;
                        if (this.autoPlayTimer) {
                            clearInterval(this.autoPlayTimer);
                            this.autoPlayTimer = null;
                        }

                        this.$nextTick(() => {
                            if (this.$refs.video) {
                                this.$refs.video.src = this.activeStream;
                                this.safePlay();
                                if (this.subtitles.length > 0 && this.activeSubtitle !== 'off') {
                                    const defaultSub = this.subtitles.find(s => s.srclang === 'id') || this.subtitles[0];
                                    if (defaultSub) {
                                        this.setSubtitle(defaultSub.url, defaultSub.label, defaultSub.srclang);
                                    }
                                }
                            }
                            if (window.lucide) lucide.createIcons();
                        });
                    }
                } catch (e) {
                    console.error('Episode switch error:', e);
                } finally {
                    this.isBuffering = false;
                }
            },

            setSubtitle(url, label = 'Subtitle', srclang = 'id') {
                this.activeSubtitle = url;
                const video = this.$refs.video;
                if (!video) return;

                const existingTracks = video.querySelectorAll('track');
                existingTracks.forEach(t => t.remove());

                if (url && url !== 'off') {
                    const track = document.createElement('track');
                    track.kind = 'subtitles';
                    track.label = label;
                    track.srclang = srclang || 'id';
                    track.src = url;
                    track.default = true;
                    video.appendChild(track);

                    this.$nextTick(() => {
                        if (video.textTracks && video.textTracks.length > 0) {
                            for (let i = 0; i < video.textTracks.length; i++) {
                                video.textTracks[i].mode = 'showing';
                            }
                        }
                    });
                } else {
                    if (video.textTracks) {
                        for (let i = 0; i < video.textTracks.length; i++) {
                            video.textTracks[i].mode = 'disabled';
                        }
                    }
                }
            },

            handleCustomSubtitleUpload(event) {
                const file = event.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    let content = e.target.result;
                    if (!content.startsWith('WEBVTT')) {
                        content = 'WEBVTT\n\n' + content.replace(/(\d{2}:\d{2}:\d{2}),(\d{3})/g, '$1.$2');
                    }
                    const blob = new Blob([content], { type: 'text/vtt' });
                    const blobUrl = URL.createObjectURL(blob);

                    const customSub = {
                        id: 'custom_' + Date.now(),
                        label: 'File: ' + file.name,
                        srclang: 'id',
                        url: blobUrl,
                        isCustom: true
                    };

                    this.subtitles.push(customSub);
                    this.setSubtitle(blobUrl);
                };
                reader.readAsText(file);
            },

            playNextEpisode() {
                if (this.nextEpisode) {
                    this.switchEpisode(this.nextEpisode.season_number, this.nextEpisode.episode_number);
                }
            },

            skipIntro() {
                if (this.$refs.video && this.introEnd > this.introStart) {
                    this.$refs.video.currentTime = this.introEnd;
                    this.isIntroSkipped = true;
                }
            },

            cancelAutoPlay() {
                this.showAutoPlayCountdown = false;
                if (this.autoPlayTimer) {
                    clearInterval(this.autoPlayTimer);
                    this.autoPlayTimer = null;
                }
            },

            async saveWatchProgressServer() {
                try {
                    await fetch('/watch-history/progress', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            film_id: this.filmId,
                            season_number: this.currentSeason,
                            episode_number: this.currentEpisode,
                            progress_seconds: Math.floor(this.currentTime)
                        })
                    });
                } catch (e) {}
            },

            safePlay() {
                const video = this.$refs.video;
                if (!video) return;
                const promise = video.play();
                if (promise !== undefined) {
                    promise.then(() => {
                        this.isPlaying = true;
                    }).catch(() => {
                        video.muted = true;
                        this.isMuted = true;
                        video.play().then(() => {
                            this.isPlaying = true;
                        }).catch(() => {
                            this.isPlaying = false;
                        });
                    });
                }
            },

            resumeFromSaved() {
                if (this.$refs.video && this.savedPos > 0) {
                    this.$refs.video.currentTime = this.savedPos;
                    this.safePlay();
                }
                this.showResumePrompt = false;
            },

            togglePlay() {
                const video = this.$refs.video;
                if (!video) return;

                if (video.paused) {
                    this.safePlay();
                } else {
                    video.pause();
                    this.isPlaying = false;
                }

                this.centerPulseIcon = true;
                clearTimeout(this.centerPulseTimer);
                this.centerPulseTimer = setTimeout(() => { this.centerPulseIcon = false; }, 600);
            },

            handleSingleClick(e) {
                if (this.clickTimer) {
                    clearTimeout(this.clickTimer);
                    this.clickTimer = null;
                } else {
                    this.clickTimer = setTimeout(() => {
                        this.showControls = !this.showControls;
                        if (this.showControls) {
                            this.resetControlsTimeout();
                        }
                        this.clickTimer = null;
                    }, 300);
                }
            },

            handleDoubleClick(e) {
                if (this.clickTimer) {
                    clearTimeout(this.clickTimer);
                    this.clickTimer = null;
                }
                const rect = e.target.getBoundingClientRect();
                const x = e.clientX - rect.left;
                if (x < rect.width / 2) {
                    this.seek(this.currentTime - 10);
                    this.triggerRipple('rewind');
                } else {
                    this.seek(this.currentTime + 10);
                    this.triggerRipple('forward');
                }
            },

            triggerRipple(side) {
                this.rippleSide = side;
                clearTimeout(this.rippleTimer);
                this.rippleTimer = setTimeout(() => { this.rippleSide = null; }, 700);
            },

            startPressAndHold() {
                this.pressTimer = setTimeout(() => {
                    if (this.$refs.video) {
                        this.$refs.video.playbackRate = 2.0;
                        this.showSpeedingBadge = true;
                    }
                }, 400);
            },

            stopPressAndHold() {
                clearTimeout(this.pressTimer);
                if (this.$refs.video) {
                    this.$refs.video.playbackRate = this.playbackSpeed;
                }
                this.showSpeedingBadge = false;
            },

            seek(seconds) {
                if (!this.$refs.video) return;
                const wasPlaying = this.isPlaying || !this.$refs.video.paused;
                this.currentTime = Math.max(0, Math.min(this.duration, seconds));
                this.$refs.video.currentTime = this.currentTime;
                if (wasPlaying) {
                    this.safePlay();
                }
            },

            handleScrubberClick(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                const pct = (e.clientX - rect.left) / rect.width;
                this.seek(pct * this.duration);
            },

            handleScrubberHover(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                this.hoverPos = e.clientX - rect.left;
                const pct = this.hoverPos / rect.width;
                this.hoverTime = pct * this.duration;
                this.showHoverTooltip = true;
            },

            setVolume(val) {
                val = parseFloat(val);
                this.volume = val;
                if (this.$refs.video) {
                    this.$refs.video.volume = val;
                    this.$refs.video.muted = val === 0;
                }
                this.isMuted = val === 0;
            },

            toggleMute() {
                if (!this.$refs.video) return;
                this.isMuted = !this.isMuted;
                this.$refs.video.muted = this.isMuted;
            },

            setPlaybackSpeed(spd) {
                this.playbackSpeed = spd;
                if (this.$refs.video) {
                    this.$refs.video.playbackRate = spd;
                }
                this.speedDropdownOpen = false;
            },

            setQuality(url, qualityLabel) {
                this.activeQuality = qualityLabel;
                const currTime = this.currentTime;
                const playing = this.isPlaying;
                
                // Reset auto-play countdown and interval when changing quality
                this.cancelAutoPlay();
                
                this.activeStream = url;
                
                this.$nextTick(() => {
                    const video = this.$refs.video;
                    if (video) {
                        video.currentTime = currTime;
                        if (playing) video.play();
                    }
                });

                this.qualityDropdownOpen = false;
            },

            handleVideoError(e) {
                console.warn('Video stream error occurred:', e);
                if (!this.failedStreams) this.failedStreams = new Set();
                if (this.activeStream) this.failedStreams.add(this.activeStream);

                if (this.qualities && this.qualities.length > 0) {
                    const altQ = this.qualities.find(q => !this.failedStreams.has(q.url));
                    if (altQ) {
                        this.setQuality(altQ.url, altQ.quality);
                        return;
                    }
                }
                this.activeStream = null;
                this.isBuffering = false;
            },

            togglePiP() {
                if (!this.$refs.video) return;
                if (document.pictureInPictureElement) {
                    document.exitPictureInPicture();
                    this.isPiP = false;
                } else if (document.pictureInPictureEnabled) {
                    this.$refs.video.requestPictureInPicture();
                    this.isPiP = true;
                }
            },

            lockLandscape() {
                if (window.screen && window.screen.orientation && typeof window.screen.orientation.lock === 'function') {
                    window.screen.orientation.lock('landscape').catch(err => {
                        console.warn('Screen orientation lock landscape error:', err);
                    });
                }
            },

            unlockOrientation() {
                if (window.screen && window.screen.orientation && typeof window.screen.orientation.unlock === 'function') {
                    try {
                        window.screen.orientation.unlock();
                    } catch (e) {}
                }
            },

            toggleFullscreen() {
                if (!this.$refs.playerContainer) return;
                const container = this.$refs.playerContainer;
                const video = this.$refs.video;

                if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                    const req = container.requestFullscreen || container.webkitRequestFullscreen || container.mozRequestFullScreen || container.msRequestFullscreen;
                    if (req) {
                        req.call(container).then(() => {
                            this.lockLandscape();
                        }).catch(err => {
                            console.error('Error attempting to enable fullscreen:', err);
                            if (video && video.webkitEnterFullscreen) {
                                try { video.webkitEnterFullscreen(); } catch(e) {}
                            }
                        });
                    } else if (video && video.webkitEnterFullscreen) {
                        try { video.webkitEnterFullscreen(); } catch(e) {}
                    }
                } else {
                    const exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
                    if (exit) {
                        exit.call(document);
                    }
                    this.unlockOrientation();
                }
            },

            resetHideTimer() {
                this.showControls = true;
                clearTimeout(this.hideTimer);
                this.startHideTimer();
            },

            startHideTimer() {
                if (this.isPlaying && !this.subtitleDropdownOpen && !this.audioDropdownOpen && !this.qualityDropdownOpen && !this.speedDropdownOpen && !this.aspectRatioDropdownOpen) {
                    this.hideTimer = setTimeout(() => {
                        this.showControls = false;
                    }, 2500);
                }
            },

            syncHlsAudioTracks(tracks) {
                if (!tracks || !Array.isArray(tracks)) return;
                this.audioTracks = tracks.map((t, i) => ({
                    id: t.id !== undefined ? t.id : i,
                    name: t.name || t.label || `Audio ${i + 1}`,
                    label: t.name || t.label || `Audio ${i + 1}`,
                    lang: t.lang || t.language || '',
                    default: !!t.default,
                }));
                if (this.hlsInstance && this.hlsInstance.audioTrack !== undefined && this.hlsInstance.audioTrack !== -1) {
                    this.activeAudioTrack = this.hlsInstance.audioTrack;
                } else if (this.audioTracks.length > 0 && this.activeAudioTrack === -1) {
                    const def = this.audioTracks.find(t => t.default) || this.audioTracks[0];
                    this.activeAudioTrack = def.id;
                }
                if (window.lucide) this.$nextTick(() => lucide.createIcons());
            },

            syncNativeAudioTracks(tracks) {
                if (!tracks || !tracks.length) return;
                const list = [];
                for (let i = 0; i < tracks.length; i++) {
                    const t = tracks[i];
                    list.push({
                        id: i,
                        name: t.label || `Audio ${i + 1}`,
                        label: t.label || `Audio ${i + 1}`,
                        lang: t.language || '',
                        enabled: t.enabled
                    });
                    if (t.enabled) {
                        this.activeAudioTrack = i;
                    }
                }
                this.audioTracks = list;
                if (window.lucide) this.$nextTick(() => lucide.createIcons());
            },

            async setAudioTrack(track) {
                // If track is a MovieBox audio dub with a subjectId
                if (typeof track === 'object' && track.subjectId) {
                    if (this.activeAudioSubjectId === track.subjectId) {
                        this.audioDropdownOpen = false;
                        return;
                    }
                    this.isBuffering = true;
                    this.activeAudioSubjectId = track.subjectId;
                    this.activeAudioTrack = track.subjectId;
                    this.audioDropdownOpen = false;

                    const currTime = this.$refs.video ? this.$refs.video.currentTime : 0;
                    const wasPlaying = this.isPlaying;

                    const url = `/film/${this.slug}/watch?season=${this.currentSeason}&episode=${this.currentEpisode}&audio_subject_id=${track.subjectId}`;
                    window.history.pushState(null, '', url);

                    try {
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (res.ok) {
                            const data = await res.json();
                            this.activeStream = data.proxyActiveStream;
                            const rawList = (data.resourceList || []).sort((a, b) => {
                                const codecA = (a.codecName || '').toLowerCase();
                                const codecB = (b.codecName || '').toLowerCase();
                                const isH264A = codecA === 'h264' || codecA === 'avc';
                                const isH264B = codecB === 'h264' || codecB === 'avc';
                                if (isH264A && !isH264B) return -1;
                                if (!isH264A && isH264B) return 1;
                                return 0;
                            });

                            const seenRes = new Set();
                            const parsedQualities = [];

                            for (const r of rawList) {
                                const rawUrl = r.resourceLink || r.url || r.playUrl || '';
                                if (!rawUrl) continue;
                                const resNum = parseInt((r.resolution || '1080').toString().replace(/[^0-9]/g, '')) || 1080;
                                if (seenRes.has(resNum)) continue;
                                seenRes.add(resNum);

                                parsedQualities.push({
                                    quality: resNum + 'p',
                                    res_num: resNum,
                                    codec: (r.codecName || 'H264').toUpperCase(),
                                    size: r.size ? (r.size / 1048576).toFixed(1) + ' MB' : '',
                                    url: '/moviebox/proxy-stream?url=' + encodeURIComponent(rawUrl) + '&id=' + track.subjectId + '&title=' + encodeURIComponent('{{ $film->title }}') + '&se=' + this.currentSeason + '&ep=' + this.currentEpisode
                                });
                            }

                            parsedQualities.sort((a, b) => b.res_num - a.res_num);
                            this.qualities = parsedQualities;
                            this.activeQuality = this.qualities.length ? this.qualities[0].quality : '1080p';

                            if (data.audioTracks && data.audioTracks.length > 0) {
                                this.audioTracks = data.audioTracks;
                            }

                            this.$nextTick(() => {
                                if (this.$refs.video) {
                                    this.$refs.video.src = this.activeStream;
                                    this.$refs.video.currentTime = currTime;
                                    if (wasPlaying) this.safePlay();
                                }
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    } catch (e) {
                        console.error('Audio dub switch error:', e);
                    } finally {
                        this.isBuffering = false;
                    }
                    return;
                }

                // If track is an HLS audio track index or native HTML5 audioTrack ID
                const trackId = (typeof track === 'object' ? (track.id ?? track.index) : track);
                this.activeAudioTrack = trackId;
                if (this.hlsInstance && typeof this.hlsInstance.audioTrack !== 'undefined') {
                    this.hlsInstance.audioTrack = trackId;
                } else {
                    const video = this.$refs.video;
                    if (video && video.audioTracks && video.audioTracks.length > 0) {
                        for (let i = 0; i < video.audioTracks.length; i++) {
                            video.audioTracks[i].enabled = (i === trackId);
                        }
                    }
                }
                this.audioDropdownOpen = false;
                if (window.lucide) this.$nextTick(() => lucide.createIcons());
            },

            setAudioProfile(mode) {
                this.audioProfile = mode;
                localStorage.setItem('faiilmov_player_audio_profile', mode);
                this.initWebAudio();
                this.applyAudioProfile();
                if (window.lucide) this.$nextTick(() => lucide.createIcons());
            },

            initWebAudio() {
                const video = this.$refs.video;
                if (!video || this.audioCtx) return;
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;
                    this.audioCtx = new AudioContext();
                    this.audioSourceNode = this.audioCtx.createMediaElementSource(video);
                    this.audioFilterNode = this.audioCtx.createBiquadFilter();
                    this.audioCompressorNode = this.audioCtx.createDynamicsCompressor();
                    this.audioGainNode = this.audioCtx.createGain();

                    // Connect chain: source -> filter -> compressor -> gain -> destination
                    this.audioSourceNode.connect(this.audioFilterNode);
                    this.audioFilterNode.connect(this.audioCompressorNode);
                    this.audioCompressorNode.connect(this.audioGainNode);
                    this.audioGainNode.connect(this.audioCtx.destination);

                    this.applyAudioProfile();
                } catch (err) {
                    console.warn('Web Audio API init bypassed:', err);
                }
            },

            applyAudioProfile() {
                if (!this.audioCtx || !this.audioFilterNode) return;
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume().catch(() => {});
                }

                if (this.audioProfile === 'vocal') {
                    this.audioFilterNode.type = 'peaking';
                    this.audioFilterNode.frequency.value = 2500;
                    this.audioFilterNode.Q.value = 1.2;
                    this.audioFilterNode.gain.value = 6.0;
                    if (this.audioGainNode) this.audioGainNode.gain.value = 1.1;
                } else if (this.audioProfile === 'bass') {
                    this.audioFilterNode.type = 'lowshelf';
                    this.audioFilterNode.frequency.value = 180;
                    this.audioFilterNode.gain.value = 5.5;
                    if (this.audioGainNode) this.audioGainNode.gain.value = 1.0;
                } else if (this.audioProfile === 'night') {
                    this.audioFilterNode.type = 'allpass';
                    if (this.audioCompressorNode) {
                        this.audioCompressorNode.threshold.value = -30;
                        this.audioCompressorNode.knee.value = 30;
                        this.audioCompressorNode.ratio.value = 12;
                        this.audioCompressorNode.attack.value = 0.003;
                        this.audioCompressorNode.release.value = 0.25;
                    }
                    if (this.audioGainNode) this.audioGainNode.gain.value = 1.0;
                } else {
                    this.audioFilterNode.type = 'allpass';
                    this.audioFilterNode.gain.value = 0;
                    if (this.audioGainNode) this.audioGainNode.gain.value = 1.0;
                }
            },

            attachHlsOrSrc(url) {
                if (!url) return;
                this.$nextTick(() => {
                    const video = this.$refs.video;
                    if (!video) return;

                    if (url.includes('.m3u8') || url.includes('/anichin/hls')) {
                        if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                            if (this.hlsInstance) {
                                this.hlsInstance.destroy();
                            }
                            const hls = new Hls({
                                enableWorker: true,
                                lowLatencyMode: true,
                            });
                            this.hlsInstance = hls;
                            hls.loadSource(url);
                            hls.attachMedia(video);
                            hls.on(Hls.Events.AUDIO_TRACKS_UPDATED, (event, data) => {
                                if (data && data.audioTracks) {
                                    this.syncHlsAudioTracks(data.audioTracks);
                                }
                            });
                            hls.on(Hls.Events.AUDIO_TRACK_SWITCHED, (event, data) => {
                                this.activeAudioTrack = data.id;
                                if (window.lucide) this.$nextTick(() => lucide.createIcons());
                            });
                            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                                this.isBuffering = false;
                                if (this.hlsInstance && this.hlsInstance.audioTracks && this.hlsInstance.audioTracks.length > 0) {
                                    this.syncHlsAudioTracks(this.hlsInstance.audioTracks);
                                }
                                this.safePlay();
                            });
                            hls.on(Hls.Events.ERROR, (event, data) => {
                                if (data.fatal) {
                                    console.warn('HLS Fatal Error:', data);
                                    this.handleVideoError(data);
                                }
                            });
                            return;
                        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                            video.src = url;
                            this.safePlay();
                            return;
                        }
                    }

                    if (this.hlsInstance) {
                        this.hlsInstance.destroy();
                        this.hlsInstance = null;
                    }
                    video.src = url;
                });
            },

            formatTime(seconds) {
                if (isNaN(seconds) || seconds < 0) return '00:00';
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = Math.floor(seconds % 60);
                
                if (h > 0) {
                    return `${h}:${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
                }
                return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
            }
        }
    }

    function synopsisWidget() {
        return {
            filmId: {{ $film->id }},
            filmTitle: @json($film->title),
            originalText: @json($film->synopsis ?: 'Belum ada deskripsi sinopsis resmi untuk film ini.'),
            currentText: @json($film->synopsis ?: 'Belum ada deskripsi sinopsis resmi untuk film ini.'),
            translatedText: '',
            isTranslated: false,
            isTranslating: false,
            
            showAiSummary: false,
            isLoadingSummary: false,
            aiData: null,
            isExpanded: false,
            
            async toggleTranslate() {
                if (this.isTranslated) {
                    this.currentText = this.originalText;
                    this.isTranslated = false;
                    return;
                }
                if (this.translatedText) {
                    this.currentText = this.translatedText;
                    this.isTranslated = true;
                    return;
                }
                
                this.isTranslating = true;
                try {
                    const res = await fetch('{{ route('synopsis.translate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            film_id: this.filmId,
                            text: this.originalText,
                            target_lang: 'id'
                        })
                    });
                    const data = await res.json();
                    if (data.success && data.translated_text) {
                        this.translatedText = data.translated_text;
                        this.currentText = data.translated_text;
                        this.isTranslated = true;
                    }
                } catch (e) {
                    console.error('Translation error:', e);
                } finally {
                    this.isTranslating = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            },
            
            async toggleAiSummary() {
                this.showAiSummary = !this.showAiSummary;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

                if (this.showAiSummary && !this.aiData && !this.isLoadingSummary) {
                    this.isLoadingSummary = true;
                    try {
                        const res = await fetch('{{ route('synopsis.summary') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                film_id: this.filmId,
                                title: this.filmTitle,
                                synopsis: this.originalText,
                                genres: @json($film->genres->pluck('name')->toArray())
                            })
                        });
                        const resJson = await res.json();
                        if (resJson.success && resJson.data) {
                            this.aiData = resJson.data;
                        }
                    } catch (e) {
                        console.error('AI Summary error:', e);
                    } finally {
                        this.isLoadingSummary = false;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    }
                }
            }
        };
    }
</script>
@endsection
