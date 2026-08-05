@extends('layouts.app')

@section('title', 'Nonton Bareng: ' . $film->title . ' | faiilmov')

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
    $subtitlesJson = json_encode($subtitles ?? []);

    $initialParticipantsJson = json_encode($activeParticipants ?? []);
    $initialMessagesJson = json_encode($initialMessages ?? []);
@endphp

<div class="min-h-screen bg-dark-950 pb-16 overflow-x-hidden" 
     x-data="watchPartyRoom({
        roomCode: '{{ $watchParty->room_code }}',
        filmId: {{ $film->id }},
        subjectType: '{{ $film->subject_type }}',
        currentSeason: {{ $watchParty->season_number ?? 1 }},
        currentEpisode: {{ $watchParty->episode_number ?? 1 }},
        seasons: @json($film->seasons ?? []),
        isHost: {{ $isHost ? 'true' : 'false' }},
        isLocked: {{ $watchParty->is_locked ? 'true' : 'false' }},
        myDisplayName: '{{ $participant->display_name }}',
        activeStream: '{{ $proxyActiveStream }}',
        qualities: {{ $qualitiesJson }},
        subtitles: {{ $subtitlesJson }},
        initialPosition: {{ $watchParty->current_position_seconds }},
        initialIsPlaying: {{ $watchParty->is_playing ? 'true' : 'false' }},
        initialParticipants: {{ $initialParticipantsJson }},
        initialMessages: {{ $initialMessagesJson }}
     })">
    
    <!-- Top Bar Navigation & Watch Party Header -->
    <div class="glass-panel border-b border-white/10 py-3.5 px-4 sm:px-8 backdrop-blur-md relative z-40">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <a href="{{ route('film.show', $film->slug) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-300 hover:text-white transition-colors glass-card px-4 py-2 rounded-2xl border border-white/10">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali</span>
                </a>

                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-3 py-1.5 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center gap-1.5">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>Nonton Bareng</span>
                    </span>

                    <template x-if="isHost">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="crown" class="w-3 h-3 text-amber-400"></i>
                            <span>HOST</span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- Host Actions & Invite Code -->
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end flex-wrap">
                
                <!-- HOST EXCLUSIVE ACTIONS TOOLBAR -->
                <template x-if="isHost">
                    <div class="flex items-center gap-2">
                        <!-- Toggle Lock Room -->
                        <button @click="toggleLock()" 
                                :class="isLocked ? 'bg-red-500/20 text-red-300 border-red-500/40' : 'bg-white/10 text-zinc-200 border-white/15 hover:bg-white/20'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-colors flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="lock" x-show="isLocked" class="w-3.5 h-3.5 text-red-400"></i>
                            <i data-lucide="unlock" x-show="!isLocked" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span x-text="isLocked ? 'Room Dikunci' : 'Kunci Room'"></span>
                        </button>

                        <!-- End Room Button -->
                        <button @click="endRoom()" 
                                class="px-3 py-1.5 rounded-xl bg-red-600/80 hover:bg-red-600 text-white text-xs font-bold border border-red-500/40 transition-colors flex items-center gap-1 cursor-pointer">
                            <i data-lucide="power" class="w-3.5 h-3.5"></i>
                            <span>Akhiri Room</span>
                        </button>
                    </div>
                </template>

                <div class="glass-card px-3.5 py-1.5 rounded-2xl border border-white/15 text-xs text-zinc-300 flex items-center gap-2">
                    <span class="text-[10px] text-zinc-400 uppercase font-bold">Kode Room:</span>
                    <strong class="font-mono text-amber-400 font-extrabold tracking-widest text-sm" x-text="roomCode"></strong>
                </div>

                <button @click="copyInviteLink()" 
                        class="px-4 py-2 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-1.5 shadow-md cursor-pointer">
                    <i data-lucide="copy" x-show="!copied" class="w-3.5 h-3.5"></i>
                    <i data-lucide="check" x-show="copied" class="w-3.5 h-3.5 text-emerald-600" style="display:none;"></i>
                    <span x-text="copied ? 'Tersalin!' : 'Salin Invite Link'"></span>
                </button>
            </div>

        </div>
    </div>

    <!-- Main Layout Container (Theater & Sidebar split-view) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        
        <!-- Left 2 Columns: Video Player & Floating Reaction Overlay -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Video Player Theater Wrapper -->
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
                               playsinline
                               referrerpolicy="no-referrer"
                               @click="handleSingleClick($event)"
                               @dblclick="handleDoubleClick($event)"
                               @mousedown="startPressAndHold()"
                               @mouseup="stopPressAndHold()"
                               @mouseleave="stopPressAndHold()"
                               class="w-full h-full object-contain cursor-pointer"></video>

                        <!-- Top & Bottom Gradient Shadows -->
                        <div class="absolute inset-0 pointer-events-none transition-opacity duration-300"
                             :class="showControls || !isPlaying ? 'opacity-100' : 'opacity-0'">
                            <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-black/80 via-black/40 to-transparent"></div>
                            <div class="absolute bottom-0 left-0 right-0 h-36 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
                        </div>

                        <!-- FLOATING EMOJI ANIMATION OVERLAY CONTAINER -->
                        <div class="absolute inset-0 pointer-events-none z-30 overflow-hidden">
                            <template x-for="r in floatingReactions" :key="r.id">
                                <div class="absolute bottom-6 font-sans flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-black/70 backdrop-blur-md border border-white/20 text-white shadow-2xl animate-float-up"
                                     :style="'left: ' + r.left + '%;'">
                                    <span class="text-xl" x-text="r.emoji"></span>
                                    <span class="text-[10px] font-bold text-zinc-300" x-text="r.sender"></span>
                                </div>
                            </template>
                        </div>

                        <!-- NON-HOST OVERLAY BADGE -->
                        <template x-if="!isHost">
                            <div class="absolute top-4 left-4 z-20 glass-chip px-3 py-1.5 rounded-full text-[11px] font-bold text-zinc-200 flex items-center gap-2 border border-white/20 shadow-lg pointer-events-none">
                                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                                <span>Pemutaran Dikontrol oleh Host</span>
                            </div>
                        </template>

                        <!-- HOST CONTROL OVERLAY BADGE -->
                        <template x-if="isHost">
                            <div class="absolute top-4 left-4 z-20 glass-chip px-3 py-1.5 rounded-full text-[11px] font-bold text-emerald-400 flex items-center gap-2 border border-emerald-500/30 shadow-lg pointer-events-none">
                                <i data-lucide="crown" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>Anda adalah Host Room</span>
                            </div>
                        </template>

                        <!-- Loading & Sync Overlay -->
                        <div x-show="isBuffering" 
                             class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center gap-3 bg-black/60 backdrop-blur-[2px] z-20">
                            <div class="w-12 h-12 rounded-full border-3 border-white/20 border-t-white animate-spin"></div>
                            <span class="text-xs font-semibold text-zinc-300">Menyinkronkan Video...</span>
                        </div>

                        <!-- 2x Speeding Up Badge Indicator -->
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
                            <div class="relative w-full h-3 flex items-center group/scrubber" 
                                 :class="isHost ? 'cursor-pointer' : 'cursor-not-allowed opacity-75'"
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
                                    <button @click="togglePlay()" 
                                            :disabled="!isHost"
                                            :class="isHost ? 'cursor-pointer hover:bg-white/20' : 'cursor-not-allowed opacity-60'"
                                            class="p-2 rounded-xl transition-colors text-white flex items-center justify-center" 
                                            title="Play/Pause (Host Only)">
                                        <svg x-show="!isPlaying" class="w-5 h-5 fill-white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        <svg x-show="isPlaying" class="w-5 h-5 fill-white" viewBox="0 0 24 24" style="display: none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                    </button>

                                    <!-- Skip Rewind 10s -->
                                    <button @click="if(isHost) { seek(currentTime - 10); triggerRipple('rewind'); }" 
                                            :disabled="!isHost"
                                            :class="isHost ? 'cursor-pointer hover:bg-white/20 text-zinc-300 hover:text-white' : 'cursor-not-allowed opacity-50 text-zinc-500'"
                                            class="p-2 rounded-xl transition-colors" title="-10 Detik (Host Only)">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Skip Forward 10s -->
                                    <button @click="if(isHost) { seek(currentTime + 10); triggerRipple('forward'); }" 
                                            :disabled="!isHost"
                                            :class="isHost ? 'cursor-pointer hover:bg-white/20 text-zinc-300 hover:text-white' : 'cursor-not-allowed opacity-50 text-zinc-500'"
                                            class="p-2 rounded-xl transition-colors" title="+10 Detik (Host Only)">
                                        <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                                    </button>

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

                                <!-- Right Controls Group: Speed, Quality, Fullscreen -->
                                <div class="flex items-center gap-2">
                                    
                                    <!-- Playback Speed Selector Dropdown -->
                                    <div class="relative" @click.outside="speedDropdownOpen = false">
                                        <button @click="if(isHost) speedDropdownOpen = !speedDropdownOpen" 
                                                :class="isHost ? 'cursor-pointer hover:border-white/30' : 'cursor-not-allowed opacity-60'"
                                                class="px-2.5 py-1 rounded-xl glass-chip text-[11px] font-bold text-zinc-200 transition-all flex items-center gap-1">
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

                <!-- Stream Fallback -->
                <template x-if="!activeStream">
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center bg-dark-900/90 backdrop-blur-md">
                        <i data-lucide="video-off" class="w-12 h-12 text-zinc-500"></i>
                        <h3 class="font-serif font-bold text-lg text-white">Stream Video Belum Tersedia</h3>
                        <p class="text-xs text-zinc-400 max-w-md">Maaf, stream video langsung belum dapat dijangkau.</p>
                    </div>
                </template>

            </div>

            <!-- Video Information Card -->
            <div class="glass-panel p-6 rounded-3xl border border-white/10 shadow-xl backdrop-blur-md">
                <h1 class="font-serif font-bold text-xl sm:text-2xl text-white mb-2 flex items-center gap-3">
                    <span>{{ $film->title }}</span>
                    <template x-if="subjectType === 'series'">
                        <span class="text-xs font-semibold px-3 py-1 rounded-xl glass-chip text-amber-300 border border-amber-400/20">
                            Season <span x-text="currentSeason"></span> • Episode <span x-text="currentEpisode"></span>
                        </span>
                    </template>
                </h1>
                <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed line-clamp-3">{{ $film->synopsis }}</p>
            </div>

            <!-- Season & Episode Selector Card (Series Only) -->
            <template x-if="subjectType === 'series' && seasons.length > 0">
                <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/10 shadow-xl backdrop-blur-md space-y-6">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/10">
                        <div>
                            <h2 class="font-serif font-bold text-lg text-white flex items-center gap-2">
                                <i data-lucide="layers" class="w-5 h-5 text-amber-400"></i>
                                <span>Pilih Season & Episode</span>
                            </h2>
                            <p class="text-xs text-zinc-400 mt-1">
                                <span x-show="isHost" class="text-amber-400 font-semibold">Anda adalah Host: Klik episode untuk mengganti tayangan seluruh anggota.</span>
                                <span x-show="!isHost">Tayangan dikendalikan oleh Host room.</span>
                            </p>
                        </div>

                        <!-- Season Selector Pills -->
                        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                            <template x-for="s in seasons" :key="s.id">
                                <button type="button" 
                                        @click="selectedSeasonNumber = s.season_number"
                                        :class="selectedSeasonNumber === s.season_number ? 'bg-white text-zinc-950 font-bold shadow-md' : 'glass-chip text-zinc-300 hover:text-white hover:bg-white/10'"
                                        class="px-4 py-2 rounded-xl text-xs transition-all shrink-0 cursor-pointer flex items-center gap-1.5">
                                    <span x-text="s.title || ('Season ' + s.season_number)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Episodes List Grid -->
                    <div class="space-y-3 max-h-[26rem] overflow-y-auto pr-1" x-ref="epList">
                        <template x-for="ep in currentSeasonEpisodes" :key="ep.id">
                            <div @click="switchEpisode(selectedSeasonNumber, ep.episode_number)"
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

        </div>

        <!-- Right 1 Column: Active Members, Host Management, Live Chat, Floating Emoji Bar -->
        <div class="space-y-6 flex flex-col h-[38rem] lg:h-auto">
            
            <!-- Active Participants & Host Management Card -->
            <div class="glass-panel p-4 rounded-3xl border border-white/10 shadow-xl relative z-30">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-serif font-bold text-sm text-white flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-amber-400"></i>
                        <span>Anggota Room (<span x-text="participants.length"></span>)</span>
                    </h3>
                    <template x-if="isHost">
                        <span class="text-[10px] text-zinc-400 font-semibold">Klik anggota untuk opsi Host</span>
                    </template>
                </div>

                <div class="flex flex-wrap items-center gap-2 py-1 relative z-20 overflow-visible">
                    <template x-for="p in participants" :key="p.id || p.name">
                        <div class="relative" @click.outside="if (selectedMemberId == p.id) selectedMemberId = null">
                            <button type="button" 
                                    @click="if (isHost && !p.is_host) { selectedMemberId = (selectedMemberId == p.id ? null : p.id) }"
                                    :class="p.is_host ? 'border-amber-400/50 bg-amber-500/10' : (isHost ? 'border-white/10 bg-white/5 hover:bg-white/15 cursor-pointer' : 'border-white/10 bg-white/5')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border shrink-0 text-xs text-zinc-200 transition-colors">
                                <span class="w-2 h-2 rounded-full" :class="p.is_muted ? 'bg-red-400' : 'bg-emerald-400'"></span>
                                <span class="font-semibold text-[11px]" x-text="p.name"></span>
                                <template x-if="p.is_host">
                                    <span class="text-[9px] font-extrabold px-1 rounded bg-amber-500/20 text-amber-300">HOST</span>
                                </template>
                                <template x-if="p.is_muted">
                                    <i data-lucide="mic-off" class="w-3 h-3 text-red-400 ml-0.5"></i>
                                </template>
                            </button>

                            <!-- HOST ACTION POPOVER FOR MEMBER -->
                            <div x-show="selectedMemberId == p.id" 
                                 x-transition:enter="transition ease-out duration-150 scale-95 opacity-0"
                                 x-transition:enter-end="scale-100 opacity-100"
                                 class="absolute top-full left-0 mt-2 w-48 bg-zinc-900/95 backdrop-blur-xl p-2 rounded-2xl border border-white/20 shadow-2xl z-[100] space-y-1 text-left"
                                 style="display: none;">
                                <div class="text-[10px] font-bold text-zinc-400 px-2.5 py-1 border-b border-white/10 truncate" x-text="p.name"></div>
                                
                                <button type="button" @click="transferHost(p.id)" class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs text-amber-300 hover:bg-white/10 transition-colors flex items-center gap-2 cursor-pointer">
                                    <i data-lucide="crown" class="w-3.5 h-3.5 text-amber-400"></i>
                                    <span>Jadikan Host</span>
                                </button>
                                
                                <button type="button" @click="toggleMuteParticipant(p.id)" class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs text-zinc-200 hover:bg-white/10 transition-colors flex items-center gap-2 cursor-pointer">
                                    <i data-lucide="mic-off" class="w-3.5 h-3.5 text-zinc-400"></i>
                                    <span x-text="p.is_muted ? 'Unmute Chat' : 'Mute Chat'"></span>
                                </button>
                                
                                <button type="button" @click="kickParticipant(p.id)" class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs text-red-400 hover:bg-red-500/20 transition-colors flex items-center gap-2 cursor-pointer">
                                    <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                    <span>Keluarkan (Kick)</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Live Chat Box Container -->
            <div class="glass-panel p-4 rounded-3xl border border-white/10 shadow-xl flex-1 flex flex-col justify-between overflow-hidden min-h-[18rem]">
                
                <div class="border-b border-white/10 pb-2 mb-3 flex items-center justify-between">
                    <span class="text-xs font-bold text-white flex items-center gap-1.5">
                        <i data-lucide="message-square" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <span>Live Chat</span>
                    </span>
                    <template x-if="amIMuted">
                        <span class="text-[10px] text-red-400 font-bold flex items-center gap-1">
                            <i data-lucide="mic-off" class="w-3 h-3"></i>
                            Anda di-Mute oleh Host
                        </span>
                    </template>
                </div>

                <!-- Chat History Messages Scroll Box -->
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 text-xs" x-ref="chatScroll">
                    <template x-for="(msg, i) in chatMessages" :key="msg.id || i">
                        <div>
                            <!-- System Message Notification -->
                            <template x-if="msg.isSystem">
                                <div class="text-center py-1">
                                    <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10 text-[10px] font-semibold text-zinc-400" x-text="msg.message"></span>
                                </div>
                            </template>

                            <!-- User Chat Bubble -->
                            <template x-if="!msg.isSystem">
                                <div class="glass-card p-2.5 rounded-2xl border border-white/10 space-y-1">
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="font-bold text-amber-400" x-text="msg.senderName"></span>
                                        <span class="text-zinc-500" x-text="msg.time"></span>
                                    </div>
                                    <p class="text-zinc-200 text-xs break-words" x-text="msg.message"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Floating Emoji Bar Preset Buttons -->
                <div class="pt-3 border-t border-white/10 mt-3 space-y-2">
                    <div class="flex items-center justify-between gap-1 overflow-x-auto no-scrollbar py-1">
                        <template x-for="emo in ['😂', '❤️', '😮', '👏', '🔥', '🎉', '🍿']" :key="emo">
                            <button type="button" @click="sendReaction(emo)" 
                                    class="p-2 rounded-xl bg-white/5 hover:bg-white/15 border border-white/10 text-lg transition-transform hover:scale-110 cursor-pointer">
                                <span x-text="emo"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Chat Input Form -->
                    <form @submit.prevent="sendChatMessage()" class="flex items-center gap-2">
                        <input type="text" 
                               x-model="chatInput"
                               :disabled="amIMuted"
                               :placeholder="amIMuted ? 'Anda sedang di-mute oleh Host...' : 'Ketik pesan live chat...'" 
                               class="flex-1 bg-zinc-900/80 text-xs text-white placeholder-zinc-500 px-3.5 py-2.5 rounded-xl border border-white/10 focus:outline-none focus:border-amber-400 disabled:opacity-50 disabled:cursor-not-allowed">
                        
                        <button type="submit" 
                                :disabled="amIMuted"
                                class="px-3 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors cursor-pointer shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>

            </div>

        </div>

    <!-- Reusable Custom Glass Confirmation Modal -->
    <div x-show="confirmModal.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/80 backdrop-blur-md"
         style="display: none;"
         @keydown.escape.window="confirmModal.open = false">
        
        <div class="glass-panel p-6 sm:p-8 rounded-[2rem] border border-white/15 shadow-2xl max-w-sm w-full text-center relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-500/40 to-transparent"></div>

            <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i :data-lucide="confirmModal.icon || 'alert-triangle'" class="w-7 h-7"></i>
            </div>

            <h3 class="font-serif font-extrabold text-xl text-white mb-2" x-text="confirmModal.title"></h3>
            <p class="text-xs text-zinc-300 leading-relaxed mb-6" x-text="confirmModal.message"></p>

            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="confirmModal.open = false" 
                        class="flex-1 py-3 rounded-xl glass-chip hover:bg-white/10 text-zinc-300 font-semibold text-xs transition-colors cursor-pointer">
                    Batal
                </button>
                
                <button type="button" 
                        @click="confirmModal.open = false; if (typeof confirmModal.onConfirm === 'function') confirmModal.onConfirm()" 
                        :class="confirmModal.confirmClass || 'bg-white hover:bg-zinc-200 text-zinc-950'"
                        class="flex-1 py-3 rounded-xl font-bold text-xs shadow-lg transition-all active:scale-95 cursor-pointer"
                        x-text="confirmModal.confirmText || 'Lanjutkan'">
                </button>
            </div>
        </div>
    </div>

    <!-- Reusable Custom Glass Alert Modal -->
    <div x-show="alertModal.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/85 backdrop-blur-md"
         style="display: none;">
        
        <div class="glass-panel p-6 sm:p-8 rounded-[2rem] border border-white/15 shadow-2xl max-w-sm w-full text-center relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-rose-500/40 to-transparent"></div>

            <div class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i :data-lucide="alertModal.icon || 'info'" class="w-7 h-7"></i>
            </div>

            <h3 class="font-serif font-extrabold text-xl text-white mb-2" x-text="alertModal.title"></h3>
            <p class="text-xs text-zinc-300 leading-relaxed mb-6" x-text="alertModal.message"></p>

            <button type="button" 
                    @click="alertModal.open = false; if (alertModal.actionUrl) window.location.href = alertModal.actionUrl" 
                    class="w-full py-3 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs shadow-lg transition-all active:scale-95 cursor-pointer">
                Mengerti
            </button>
        </div>
    </div>

</div>

<!-- Floating Reaction Upward CSS Animation -->
<style>
@keyframes floatUp {
    0% { transform: translateY(0) scale(0.8); opacity: 1; }
    80% { opacity: 0.9; }
    100% { transform: translateY(-180px) scale(1.1); opacity: 0; }
}
.animate-float-up {
    animation: floatUp 2.8s cubic-bezier(0.25, 1, 0.5, 1) forwards;
}
</style>

<script>
    function watchPartyRoom(config) {
        return {
            roomCode: config.roomCode,
            filmId: config.filmId,
            subjectType: config.subjectType || 'movie',
            currentSeason: config.currentSeason || 1,
            currentEpisode: config.currentEpisode || 1,
            selectedSeasonNumber: config.currentSeason || 1,
            seasons: config.seasons || [],
            isHost: config.isHost,
            isLocked: config.isLocked || false,
            myDisplayName: config.myDisplayName || 'Guest',
            activeStream: config.activeStream,
            qualities: config.qualities || [],
            activeQuality: (config.qualities && config.qualities[0]) ? config.qualities[0].quality : '1080p',
            initialPosition: config.initialPosition || 0,
            initialIsPlaying: config.initialIsPlaying || false,

            get currentSeasonEpisodes() {
                const s = this.seasons.find(item => item.season_number === this.selectedSeasonNumber);
                return s ? s.episodes : [];
            },

            get nextEpisode() {
                if (this.subjectType !== 'series' || !this.seasons.length) return null;
                const currentEps = this.currentSeasonEpisodes;
                const currentEpIndex = currentEps.findIndex(e => e.episode_number === this.currentEpisode);
                
                if (currentEpIndex !== -1 && currentEpIndex < currentEps.length - 1) {
                    return { season: this.currentSeason, episode: currentEps[currentEpIndex + 1].episode_number };
                }
                
                const nextSeason = this.seasons.find(s => s.season_number === this.currentSeason + 1);
                if (nextSeason && nextSeason.episodes.length > 0) {
                    return { season: nextSeason.season_number, episode: nextSeason.episodes[0].episode_number };
                }
                
                return null;
            },

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
            
            hoverTime: 0,
            hoverPos: 0,
            showHoverTooltip: false,
            showSpeedingBadge: false,
            pressTimer: null,
            rippleSide: null,
            rippleTimer: null,
            centerPulseIcon: false,
            centerPulseTimer: null,
            speedDropdownOpen: false,
            qualityDropdownOpen: false,
            subtitleDropdownOpen: false,
            subtitles: config.subtitles || [],
            activeSubtitle: 'off',
            clickTimer: null,

            participants: config.initialParticipants || [],
            chatMessages: config.initialMessages || [],
            chatInput: '',
            floatingReactions: [],
            copied: false,
            selectedMemberId: null,
            amIMuted: false,

            confirmModal: {
                open: false,
                title: '',
                message: '',
                icon: 'alert-triangle',
                confirmText: 'Lanjutkan',
                confirmClass: '',
                onConfirm: null
            },

            alertModal: {
                open: false,
                title: '',
                message: '',
                icon: 'info',
                actionUrl: null
            },

            lastMsgId: 0,
            lastRxId: 0,

            init() {
                if (this.chatMessages.length > 0) {
                    const ids = this.chatMessages.map(m => m.id || 0);
                    this.lastMsgId = Math.max(...ids);
                }

                this.$nextTick(() => {
                    const video = this.$refs.video;
                    if (!video) return;

                    video.currentTime = this.initialPosition;

                    video.addEventListener('timeupdate', () => {
                        this.currentTime = video.currentTime;
                        this.duration = video.duration || 0;
                        this.progressPercent = this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
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
                    });

                    window.addEventListener('keydown', (e) => {
                        if (['input', 'textarea', 'select'].includes(document.activeElement.tagName.toLowerCase())) return;
                        
                        if (e.code === 'Space' && this.isHost) {
                            e.preventDefault();
                            this.togglePlay();
                        } else if (e.code === 'KeyF') {
                            e.preventDefault();
                            this.toggleFullscreen();
                        } else if (e.code === 'KeyM') {
                            e.preventDefault();
                            this.toggleMute();
                        } else if (e.code === 'ArrowLeft' && this.isHost) {
                            e.preventDefault();
                            this.seek(this.currentTime - 5);
                            this.triggerRipple('rewind');
                        } else if (e.code === 'ArrowRight' && this.isHost) {
                            e.preventDefault();
                            this.seek(this.currentTime + 5);
                            this.triggerRipple('forward');
                        }
                    });

                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });

                    this.initBroadcastListeners();
                    this.startPollingSync();
                    this.scrollChatToBottom();

                    if (window.lucide) lucide.createIcons();
                });
            },

            initBroadcastListeners() {
                if (window.Echo) {
                    window.Echo.channel('watch-party.' + this.roomCode)
                        .listen('.PlaybackUpdated', (e) => {
                            if (!this.isHost && this.$refs.video) {
                                const video = this.$refs.video;
                                if (Math.abs(video.currentTime - e.position) > 1.5) {
                                    video.currentTime = e.position;
                                }
                                if (e.isPlaying && video.paused) {
                                    video.play();
                                } else if (!e.isPlaying && !video.paused) {
                                    video.pause();
                                }
                            }
                        })
                        .listen('.MessageSent', (e) => {
                            if (!this.chatMessages.some(m => m.id && m.id === e.id)) {
                                this.chatMessages.push({
                                    id: e.id,
                                    isSystem: e.isSystem,
                                    senderName: e.senderName,
                                    message: e.message,
                                    time: e.time
                                });
                                this.scrollChatToBottom();
                            }
                        })
                        .listen('.ReactionSent', (e) => {
                            this.spawnFloatingEmoji(e.emoji, e.senderName);
                        })
                        .listen('.ParticipantJoined', (e) => {
                            this.participants = e.participants || this.participants;
                        })
                        .listen('.ParticipantLeft', (e) => {
                            this.participants = e.participants || this.participants;
                        });
                }
            },

            startPollingSync() {
                const pollInterval = window.Echo ? 6000 : 3000;
                setInterval(async () => {
                    if (this.isPollingBusy) return;
                    this.isPollingBusy = true;

                    try {
                        const url = `/watch-party/${this.roomCode}/state?last_msg_id=${this.lastMsgId}&last_rx_id=${this.lastRxId}`;
                        const res = await fetch(url);
                        if (res.ok) {
                            const data = await res.json();
                            
                            // Check if room ended
                            if (data.status === 'ended') {
                                this.showAlert({
                                    title: 'Sesi Room Diakhiri',
                                    message: 'Room Nonton Bareng ini telah diakhiri oleh Host.',
                                    icon: 'video-off',
                                    actionUrl: '/'
                                });
                                return;
                            }

                            // Check if kicked
                            if (data.is_kicked) {
                                this.showAlert({
                                    title: 'Dikeluarkan dari Room',
                                    message: 'Anda telah dikeluarkan (kick) dari room oleh Host.',
                                    icon: 'user-x',
                                    actionUrl: '/'
                                });
                                return;
                            }

                            this.isLocked = data.is_locked;
                            this.isHost = data.am_i_host;
                            this.amIMuted = data.am_i_muted;
                            this.participants = data.participants || [];

                            if (!this.isHost && this.$refs.video) {
                                const video = this.$refs.video;
                                if (Math.abs(video.currentTime - data.position) > 2) {
                                    video.currentTime = data.position;
                                }
                                if (data.is_playing && video.paused) {
                                    video.play();
                                } else if (!data.is_playing && !video.paused) {
                                    video.pause();
                                }
                            }

                            if (data.season_number && data.episode_number) {
                                if (this.currentSeason !== data.season_number || this.currentEpisode !== data.episode_number) {
                                    this.currentSeason = data.season_number;
                                    this.currentEpisode = data.episode_number;
                                    this.selectedSeasonNumber = data.season_number;
                                    this.subtitles = data.subtitles || [];
                                    if (data.proxy_active_stream && this.$refs.video) {
                                        this.$refs.video.src = data.proxy_active_stream;
                                        this.$refs.video.currentTime = 0;
                                        if (data.is_playing) this.$refs.video.play();
                                        if (this.subtitles.length > 0 && this.activeSubtitle !== 'off') {
                                            this.setSubtitle(this.subtitles[0].url);
                                        }
                                    }
                                }
                            }

                            if (data.new_messages && data.new_messages.length > 0) {
                                data.new_messages.forEach(msg => {
                                    if (!this.chatMessages.some(m => m.id === msg.id)) {
                                        this.chatMessages.push(msg);
                                    }
                                });
                                this.lastMsgId = data.latest_msg_id;
                                this.scrollChatToBottom();
                            }

                            if (data.new_reactions && data.new_reactions.length > 0) {
                                data.new_reactions.forEach(rx => {
                                    this.spawnFloatingEmoji(rx.emoji, rx.senderName);
                                });
                                this.lastRxId = data.latest_rx_id;
                            }
                        }
                    } catch (e) {
                    } finally {
                        this.isPollingBusy = false;
                    }
                }, pollInterval);
            },

            // --- HOST ACTIONS ---
            async toggleLock() {
                try {
                    const res = await fetch(`/watch-party/${this.roomCode}/toggle-lock`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.isLocked = data.is_locked;
                    }
                } catch (e) {}
            },

            showConfirm(opts) {
                this.confirmModal = {
                    open: true,
                    title: opts.title || 'Konfirmasi',
                    message: opts.message || '',
                    icon: opts.icon || 'alert-triangle',
                    confirmText: opts.confirmText || 'Lanjutkan',
                    confirmClass: opts.confirmClass || 'bg-white hover:bg-zinc-200 text-zinc-950',
                    onConfirm: opts.onConfirm || null
                };
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            showAlert(opts) {
                this.alertModal = {
                    open: true,
                    title: opts.title || 'Pemberitahuan',
                    message: opts.message || '',
                    icon: opts.icon || 'info',
                    actionUrl: opts.actionUrl || null
                };
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            kickParticipant(participantId) {
                this.selectedMemberId = null;
                this.showConfirm({
                    title: 'Keluarkan Anggota?',
                    message: 'Apakah Anda yakin ingin mengeluarkan anggota ini dari room?',
                    icon: 'user-x',
                    confirmText: 'Keluarkan (Kick)',
                    confirmClass: 'bg-rose-600 hover:bg-rose-700 text-white',
                    onConfirm: async () => {
                        try {
                            await fetch(`/watch-party/${this.roomCode}/kick`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                },
                                body: JSON.stringify({ participant_id: participantId })
                            });
                        } catch (e) {}
                    }
                });
            },

            async toggleMuteParticipant(participantId) {
                this.selectedMemberId = null;
                try {
                    await fetch(`/watch-party/${this.roomCode}/mute`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({ participant_id: participantId })
                    });
                } catch (e) {}
            },

            transferHost(participantId) {
                this.selectedMemberId = null;
                this.showConfirm({
                    title: 'Transfer Peran Host?',
                    message: 'Apakah Anda yakin ingin mentransfer peran Host kepada anggota ini?',
                    icon: 'crown',
                    confirmText: 'Transfer Host',
                    confirmClass: 'bg-amber-500 hover:bg-amber-600 text-zinc-950 font-bold',
                    onConfirm: async () => {
                        try {
                            const res = await fetch(`/watch-party/${this.roomCode}/transfer-host`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                },
                                body: JSON.stringify({ participant_id: participantId })
                            });
                            if (res.ok) {
                                this.isHost = false;
                            }
                        } catch (e) {}
                    }
                });
            },

            async switchEpisode(seasonNum, epNum) {
                if (!this.isHost) {
                    this.showAlert({
                        title: 'Opsi Khusus Host',
                        message: 'Hanya Host yang dapat mengganti Season & Episode tayangan.',
                        icon: 'lock'
                    });
                    return;
                }

                try {
                    const res = await fetch(`/watch-party/${this.roomCode}/switch-episode`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            season_number: seasonNum,
                            episode_number: epNum
                        })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.currentSeason = data.season_number;
                        this.currentEpisode = data.episode_number;
                        this.selectedSeasonNumber = data.season_number;
                        if (data.proxy_active_stream && this.$refs.video) {
                            this.$refs.video.src = data.proxy_active_stream;
                            this.$refs.video.currentTime = 0;
                            this.$refs.video.play();
                        }
                        this.subtitles = data.subtitles || [];
                        if (this.subtitles.length > 0 && this.activeSubtitle !== 'off') {
                            this.setSubtitle(this.subtitles[0].url);
                        }
                    }
                } catch (e) {}
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
                    this.switchEpisode(this.nextEpisode.season, this.nextEpisode.episode);
                }
            },

            endRoom() {
                this.showConfirm({
                    title: 'Akhiri Room Nonton Bareng?',
                    message: 'Apakah Anda yakin ingin mengakhiri Room Nonton Bareng ini untuk semua anggota?',
                    icon: 'power',
                    confirmText: 'Ya, Akhiri Room',
                    confirmClass: 'bg-rose-600 hover:bg-rose-700 text-white',
                    onConfirm: async () => {
                        try {
                            const res = await fetch(`/watch-party/${this.roomCode}/end`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                }
                            });
                            if (res.ok) {
                                window.location.href = '/';
                            }
                        } catch (e) {}
                    }
                });
            },

            togglePlay() {
                const video = this.$refs.video;
                if (!video) return;

                if (this.isHost) {
                    if (video.paused) {
                        video.play();
                        this.isPlaying = true;
                        this.broadcastPlaybackState('play');
                    } else {
                        video.pause();
                        this.isPlaying = false;
                        this.broadcastPlaybackState('pause');
                    }
                }

                this.centerPulseIcon = true;
                clearTimeout(this.centerPulseTimer);
                this.centerPulseTimer = setTimeout(() => { this.centerPulseIcon = false; }, 600);
            },

            handleSingleClick(e) {
                if (!this.isHost) return;
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
                if (!this.isHost) return;
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
                if (!this.isHost) return;
                this.pressTimer = setTimeout(() => {
                    if (this.$refs.video) {
                        this.$refs.video.playbackRate = 2.0;
                        this.showSpeedingBadge = true;
                    }
                }, 400);
            },

            stopPressAndHold() {
                if (!this.isHost) return;
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
                if (this.isHost) {
                    this.broadcastPlaybackState('seek');
                }
            },

            handleScrubberClick(e) {
                if (!this.isHost) return;
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
                if (!this.isHost) return;
                this.playbackSpeed = spd;
                if (this.$refs.video) {
                    this.$refs.video.playbackRate = spd;
                }
                this.speedDropdownOpen = false;
                this.broadcastPlaybackState('speed');
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

            async broadcastPlaybackState(action) {
                if (!this.$refs.video) return;
                try {
                    await fetch(`/watch-party/${this.roomCode}/playback`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            action: action,
                            position: this.$refs.video.currentTime,
                            is_playing: !this.$refs.video.paused,
                            speed: this.$refs.video.playbackRate
                        })
                    });
                } catch (e) {}
            },

            async sendChatMessage() {
                if (this.amIMuted) return;
                const text = this.chatInput.trim();
                if (!text) return;

                this.chatInput = '';

                try {
                    const res = await fetch(`/watch-party/${this.roomCode}/message`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            message: text,
                            sender_name: this.myDisplayName
                        })
                    });

                    if (res.ok) {
                        const resData = await res.json();
                        if (resData.data && !this.chatMessages.some(m => m.id === resData.data.id)) {
                            this.chatMessages.push(resData.data);
                            this.lastMsgId = resData.data.id;
                            this.scrollChatToBottom();
                        }
                    }
                } catch (e) {}
            },

            async sendReaction(emoji) {
                try {
                    const res = await fetch(`/watch-party/${this.roomCode}/reaction`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            emoji: emoji,
                            sender_name: this.myDisplayName
                        })
                    });

                    if (res.ok) {
                        const resData = await res.json();
                        if (resData.data) {
                            this.spawnFloatingEmoji(resData.data.emoji, resData.data.senderName);
                            if (resData.data.id) this.lastRxId = resData.data.id;
                        }
                    }
                } catch (e) {}
            },

            spawnFloatingEmoji(emoji, sender) {
                const rxId = 'rx_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
                const randomLeft = Math.floor(Math.random() * 70) + 15;

                this.floatingReactions.push({
                    id: rxId,
                    emoji: emoji,
                    sender: sender,
                    left: randomLeft
                });

                setTimeout(() => {
                    this.floatingReactions = this.floatingReactions.filter(r => r.id !== rxId);
                }, 3000);
            },

            copyInviteLink() {
                const link = window.location.href;
                navigator.clipboard.writeText(link);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
            },

            scrollChatToBottom() {
                this.$nextTick(() => {
                    const scroll = this.$refs.chatScroll;
                    if (scroll) scroll.scrollTop = scroll.scrollHeight;
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
</script>
@endsection
