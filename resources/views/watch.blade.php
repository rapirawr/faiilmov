@extends('layouts.app')

@section('title', 'Memutar: ' . $film->title . ' | faiilmov')

@section('content')
@php
    $proxyActiveStream = $activeStream ? url('/moviebox/proxy-stream') . '?url=' . urlencode($activeStream) : '';
    
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
            'url'     => url('/moviebox/proxy-stream') . '?url=' . urlencode($rawUrl),
        ];
    }

    usort($processedQualities, fn($a, $b) => $b['res_num'] <=> $a['res_num']);
    $qualitiesJson = json_encode($processedQualities);

    $seasonsJson = json_encode($film->seasons->map(function($s) {
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
    }));

    $subtitlesJson = json_encode($subtitles ?? []);
    $nextEpJson = json_encode($nextEpisode ? [
        'season_number' => $nextEpisode->season->season_number,
        'episode_number' => $nextEpisode->episode_number,
        'title' => $nextEpisode->title,
        'url' => route('film.watch', $film->slug) . "?season={$nextEpisode->season->season_number}&episode={$nextEpisode->episode_number}",
    ] : null);
@endphp

<div class="min-h-screen bg-dark-950 pb-16" 
     x-data="customPlayer({
        filmId: {{ $film->id }},
        slug: '{{ $film->slug }}',
        subjectType: '{{ $film->subject_type }}',
        currentSeason: {{ $season }},
        currentEpisode: {{ $episode }},
        activeStream: '{{ $proxyActiveStream }}',
        qualities: {{ $qualitiesJson }},
        subtitles: {{ $subtitlesJson }},
        seasons: {{ $seasonsJson }},
        nextEpisode: {{ $nextEpJson }}
     })">
    
    <!-- Top Bar Navigation & Cinema Mode Header -->
    <div class="glass-panel border-b border-white/10 py-3.5 px-4 sm:px-8 backdrop-blur-md rounded-none relative z-40">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ route('film.show', $film->slug) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-300 hover:text-white transition-colors glass-card px-4 py-2 rounded-2xl border border-white/10">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Detail Film</span>
            </a>

            <div class="flex items-center gap-3">
                <button @click="cinemaLights = !cinemaLights" 
                        :class="cinemaLights ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'glass-card text-zinc-300 border-white/10'"
                        class="px-4 py-2 rounded-2xl text-xs font-semibold border transition-colors flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="sun" class="w-3.5 h-3.5"></i>
                    <span x-text="cinemaLights ? 'Matikan Cinema Mode' : 'Cinema Mode'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Cinema Lights Backdrop Overlay -->
    <div x-show="cinemaLights" 
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-20 bg-black/95 pointer-events-none" style="display: none;"></div>

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
    <div class="relative z-30 max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        
        <!-- Video Player Wrapper (Full Custom Controls Base) -->
        <div x-ref="playerContainer" 
             @mousemove="resetHideTimer()" 
             @mouseleave="startHideTimer()"
             @wheel.prevent="handleWheelVolume($event)"
             class="relative aspect-video w-full rounded-3xl overflow-hidden bg-black border border-white/10 shadow-2xl group select-none">
            
            <template x-if="activeStream">
                <div class="relative w-full h-full flex items-center justify-center bg-black">
                    
                    <!-- Native HTML5 Video Element -->
                    <video x-ref="video" 
                           :src="activeStream" 
                           autoplay 
                           playsinline
                           referrerpolicy="no-referrer"
                           @click="handleSingleClick($event)"
                           @dblclick="handleDoubleClick($event)"
                           @mousedown="startPressAndHold()"
                           @mouseup="stopPressAndHold()"
                           @mouseleave="stopPressAndHold()"
                           class="w-full h-full object-contain cursor-pointer"></video>

                    <!-- Top & Bottom Gradient Shadows for Controls Readability -->
                    <div class="absolute inset-0 pointer-events-none transition-opacity duration-300"
                         :class="showControls || !isPlaying ? 'opacity-100' : 'opacity-0'">
                        <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-black/80 via-black/40 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 h-36 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
                    </div>

                    <!-- Center Loading & Buffering Spinner -->
                    <div x-show="isBuffering" 
                         x-transition:enter="transition ease-out duration-200 opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200 opacity-0"
                         class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center gap-3 bg-black/40 backdrop-blur-[2px] z-20">
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

                    <!-- Double-Click Rewind (-10s) Ripple Indicator -->
                    <div x-show="rippleSide === 'rewind'" 
                         x-transition:enter="transition ease-out duration-200 scale-75 opacity-0"
                         x-transition:enter-end="scale-100 opacity-100"
                         x-transition:leave="transition ease-in duration-300 opacity-0"
                         class="absolute left-12 top-1/2 -translate-y-1/2 z-20 pointer-events-none glass-panel p-5 rounded-full border border-white/20 text-white flex flex-col items-center justify-center gap-1 shadow-2xl">
                        <i data-lucide="rotate-ccw" class="w-8 h-8"></i>
                        <span class="text-xs font-extrabold">-10 Detik</span>
                    </div>

                    <!-- Double-Click Forward (+10s) Ripple Indicator -->
                    <div x-show="rippleSide === 'forward'" 
                         x-transition:enter="transition ease-out duration-200 scale-75 opacity-0"
                         x-transition:enter-end="scale-100 opacity-100"
                         x-transition:leave="transition ease-in duration-300 opacity-0"
                         class="absolute right-12 top-1/2 -translate-y-1/2 z-20 pointer-events-none glass-panel p-5 rounded-full border border-white/20 text-white flex flex-col items-center justify-center gap-1 shadow-2xl">
                        <i data-lucide="rotate-cw" class="w-8 h-8"></i>
                        <span class="text-xs font-extrabold">+10 Detik</span>
                    </div>

                    <!-- Center Play/Pause Indicator -->
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

                    <!-- Video Controls Overlay -->
                    <div class="absolute inset-x-0 bottom-0 z-30 p-4 sm:p-6 transition-opacity duration-300 flex flex-col justify-end gap-3"
                         :class="showControls || !isPlaying ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">
                        
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
                        <div class="flex items-center justify-between gap-4">
                            
                            <!-- Left Controls Group -->
                            <div class="flex items-center gap-2 sm:gap-3">
                                <!-- Play / Pause Toggle Button -->
                                <button @click="togglePlay()" class="p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-white flex items-center justify-center" title="Play/Pause (Space)">
                                    <svg x-show="!isPlaying" class="w-5 h-5 fill-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    <svg x-show="isPlaying" class="w-5 h-5 fill-white" viewBox="0 0 24 24" style="display: none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                </button>

                                <!-- Skip Rewind 10s -->
                                <button @click="seek(currentTime - 10); triggerRipple('rewind')" class="p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white" title="-10 Detik">
                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                </button>

                                <!-- Skip Forward 10s -->
                                <button @click="seek(currentTime + 10); triggerRipple('forward')" class="p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white" title="+10 Detik">
                                    <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                                </button>

                                <!-- Next Episode Button (Series Only) -->
                                <template x-if="nextEpisode">
                                    <button @click="playNextEpisode()" 
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-white transition-colors cursor-pointer border border-white/15 shadow-sm"
                                            title="Episode Selanjutnya">
                                        <i data-lucide="skip-forward" class="w-3.5 h-3.5 fill-white"></i>
                                        <span class="hidden sm:inline">Episode Selanjutnya</span>
                                    </button>
                                </template>

                                <!-- Volume Control Group -->
                                <div class="flex items-center gap-2 group/volume relative" @mouseenter="showVolumeSlider = true" @mouseleave="showVolumeSlider = false">
                                    <button @click="toggleMute()" class="p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white flex items-center justify-center" title="Mute / Unmute (M)">
                                        <svg x-show="isMuted || volume === 0" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
                                        <svg x-show="!isMuted && volume > 0 && volume < 0.5" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" style="display: none;"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                        <svg x-show="!isMuted && volume >= 0.5" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" style="display: none;"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                                    </button>

                                    <!-- Volume Slider -->
                                    <input type="range" 
                                           min="0" 
                                           max="1" 
                                           step="0.05" 
                                           :value="isMuted ? 0 : volume"
                                           @input="setVolume($event.target.value)"
                                           class="w-16 h-1 accent-white bg-white/30 rounded-lg cursor-pointer transition-all duration-200">
                                </div>

                                <!-- Timestamp Display -->
                                <div class="text-[11px] font-semibold text-zinc-300 tracking-wider">
                                    <span x-text="formatTime(currentTime)"></span>
                                    <span class="text-zinc-500 mx-0.5">/</span>
                                    <span x-text="formatTime(duration)"></span>
                                </div>
                            </div>

                            <!-- Right Controls Group: Speed, Quality, PiP, Fullscreen -->
                            <div class="flex items-center gap-2">
                                
                                <!-- Playback Speed Selector Dropdown -->
                                <div class="relative" @click.outside="speedDropdownOpen = false">
                                    <button @click="speedDropdownOpen = !speedDropdownOpen" 
                                            class="px-2.5 py-1 rounded-xl glass-chip text-[11px] font-bold text-zinc-200 hover:text-white hover:border-white/30 transition-all cursor-pointer flex items-center gap-1">
                                        <span x-text="playbackSpeed + 'x'"></span>
                                    </button>

                                    <div x-show="speedDropdownOpen" 
                                         x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                         x-transition:enter-end="scale-100 opacity-100"
                                         class="absolute bottom-full right-0 mb-2 w-28 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-40"
                                         style="display: none;">
                                        <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider">Kecepatan</div>
                                        <template x-for="spd in speeds" :key="spd">
                                            <button @click="setPlaybackSpeed(spd)" 
                                                    :class="playbackSpeed === spd ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                                    class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                                <span x-text="spd + 'x'"></span>
                                                <i x-show="playbackSpeed === spd" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Resolution / Quality Selector Dropdown -->
                                <div class="relative" @click.outside="qualityDropdownOpen = false" x-show="qualities.length > 0">
                                    <button @click="qualityDropdownOpen = !qualityDropdownOpen" 
                                            class="px-2.5 py-1 rounded-xl glass-chip text-[11px] font-bold text-zinc-200 hover:text-white hover:border-white/30 transition-all cursor-pointer flex items-center gap-1">
                                        <span x-text="activeQuality"></span>
                                        <i data-lucide="chevron-up" class="w-3 h-3 text-zinc-400"></i>
                                    </button>

                                    <div x-show="qualityDropdownOpen" 
                                         x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                         x-transition:enter-end="scale-100 opacity-100"
                                         class="absolute bottom-full right-0 mb-2 w-36 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-40"
                                         style="display: none;">
                                        <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider">Kualitas Video</div>
                                        <template x-for="q in qualities" :key="q.url">
                                            <button @click="setQuality(q.url, q.quality)" 
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

                                <!-- Subtitle / Caption Selector Dropdown -->
                                <div class="relative" @click.outside="subtitleDropdownOpen = false">
                                    <button @click="subtitleDropdownOpen = !subtitleDropdownOpen" 
                                            :class="activeSubtitle !== 'off' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'text-zinc-200 hover:text-white hover:border-white/30'"
                                            class="px-2.5 py-1 rounded-xl glass-chip text-[11px] font-bold transition-all cursor-pointer flex items-center gap-1.5"
                                            title="Subtitle / Teks">
                                        <i data-lucide="subtitles" class="w-3.5 h-3.5"></i>
                                        <span>CC</span>
                                    </button>

                                    <div x-show="subtitleDropdownOpen" 
                                         x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                         x-transition:enter-end="scale-100 opacity-100"
                                         class="absolute bottom-full right-0 mb-2 w-48 glass-panel p-1.5 rounded-2xl border border-white/20 shadow-2xl z-40"
                                         style="display: none;">
                                        <div class="text-[10px] font-bold text-zinc-400 px-2 py-1 uppercase tracking-wider">Subtitle</div>
                                        
                                        <!-- Off Option -->
                                        <button @click="setSubtitle('off'); subtitleDropdownOpen = false" 
                                                :class="activeSubtitle === 'off' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                                class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                            <span>Matikan Subtitle</span>
                                            <i x-show="activeSubtitle === 'off'" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                        </button>

                                        <!-- API Subtitles -->
                                        <template x-for="sub in subtitles" :key="sub.id || sub.url">
                                            <button @click="setSubtitle(sub.url); subtitleDropdownOpen = false" 
                                                    :class="activeSubtitle === sub.url ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-300 hover:bg-white/10 hover:text-white'"
                                                    class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer">
                                                <span x-text="sub.label"></span>
                                                <i x-show="activeSubtitle === sub.url" data-lucide="check" class="w-3 h-3 text-zinc-950"></i>
                                            </button>
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

                                <!-- Picture-in-Picture Toggle -->
                                <button @click="togglePiP()" class="p-2 rounded-xl hover:bg-white/20 transition-colors cursor-pointer text-zinc-300 hover:text-white hidden sm:block" title="Picture-in-Picture">
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
            <div class="lg:col-span-2 glass-panel p-6 rounded-3xl border border-white/10">
                <h3 class="font-serif font-bold text-lg text-white mb-3">Ringkasan Cerita</h3>
                <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed">{{ $film->synopsis }}</p>
            </div>

            <!-- Sidebar Related -->
            @if($relatedFilms->count() > 0)
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
            subtitles: config.subtitles || [],
            activeSubtitle: 'off',
            cinemaLights: false,
            clickTimer: null,

            get currentSeasonEpisodes() {
                const s = this.seasons.find(item => item.season_number === this.currentSeason);
                return s ? s.episodes : [];
            },

            init() {
                this.$nextTick(() => {
                    const video = this.$refs.video;
                    if (!video) return;

                    // Load saved position from localStorage
                    const saved = localStorage.getItem('faiilmov_watch_pos_' + this.filmId + '_s' + this.currentSeason + '_e' + this.currentEpisode);
                    if (saved) {
                        const pos = parseFloat(saved);
                        if (pos > 10) {
                            this.savedPos = pos;
                            this.showResumePrompt = true;
                        }
                    }

                    // Attach HTML5 Video Event Listeners
                    video.addEventListener('timeupdate', () => {
                        this.currentTime = video.currentTime;
                        this.duration = video.duration || 0;
                        this.progressPercent = this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
                        
                        // Auto save position every 5s to localStorage & server
                        if (Math.floor(this.currentTime) % 5 === 0 && this.currentTime > 5) {
                            localStorage.setItem('faiilmov_watch_pos_' + this.filmId + '_s' + this.currentSeason + '_e' + this.currentEpisode, this.currentTime);
                            this.saveWatchProgressServer();
                        }
                    });

                    video.addEventListener('progress', () => {
                        if (video.buffered.length > 0 && video.duration > 0) {
                            const end = video.buffered.end(video.buffered.length - 1);
                            this.bufferedPercent = (end / video.duration) * 100;
                        }
                    });

                    video.addEventListener('waiting', () => { this.isBuffering = true; });
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
                        localStorage.removeItem('faiilmov_watch_pos_' + this.filmId + '_s' + this.currentSeason + '_e' + this.currentEpisode);
                        
                        // Autoplay Next Episode if available
                        if (this.nextEpisode) {
                            this.playNextEpisode();
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
                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });

                    lucide.createIcons();
                });
            },

            async switchEpisode(seasonNum, episodeNum) {
                this.isBuffering = true;
                this.currentSeason = seasonNum;
                this.currentEpisode = episodeNum;
                this.selectedSeasonNumber = seasonNum;

                const url = `/film/${this.slug}/watch?season=${seasonNum}&episode=${episodeNum}`;
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
                        this.nextEpisode = data.nextEpisode;

                        this.$nextTick(() => {
                            if (this.$refs.video) {
                                this.$refs.video.src = this.activeStream;
                                this.$refs.video.play();
                                if (this.subtitles.length > 0 && this.activeSubtitle !== 'off') {
                                    this.setSubtitle(this.subtitles[0].url);
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

            setSubtitle(url) {
                this.activeSubtitle = url;
                const video = this.$refs.video;
                if (!video) return;

                const existingTracks = video.querySelectorAll('track');
                existingTracks.forEach(t => t.remove());

                if (url && url !== 'off') {
                    const track = document.createElement('track');
                    track.kind = 'subtitles';
                    track.label = 'Subtitle';
                    track.srclang = 'id';
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

            resumeFromSaved() {
                if (this.$refs.video && this.savedPos > 0) {
                    this.$refs.video.currentTime = this.savedPos;
                    this.$refs.video.play();
                }
                this.showResumePrompt = false;
            },

            togglePlay() {
                const video = this.$refs.video;
                if (!video) return;

                if (video.paused) {
                    video.play();
                    this.isPlaying = true;
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
                        this.togglePlay();
                        this.clickTimer = null;
                    }, 250);
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
                this.currentTime = Math.max(0, Math.min(this.duration, seconds));
                this.$refs.video.currentTime = this.currentTime;
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

            handleWheelVolume(e) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    this.setVolume(Math.min(1, this.volume + 0.05));
                } else {
                    this.setVolume(Math.max(0, this.volume - 0.05));
                }
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

            toggleFullscreen() {
                if (!this.$refs.playerContainer) return;
                if (!document.fullscreenElement) {
                    this.$refs.playerContainer.requestFullscreen().catch(err => {
                        console.error('Error attempting to enable fullscreen:', err);
                    });
                } else {
                    document.exitFullscreen();
                }
            },

            resetHideTimer() {
                this.showControls = true;
                clearTimeout(this.hideTimer);
                this.startHideTimer();
            },

            startHideTimer() {
                if (this.isPlaying) {
                    this.hideTimer = setTimeout(() => {
                        this.showControls = false;
                    }, 3500);
                }
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
</script>
@endsection
