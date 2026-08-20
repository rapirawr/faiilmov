@extends('layouts.app')

@section('content')
<div x-data="detailPage({{ $userWatchlist ? 'true' : 'false' }})">

    <!-- Film Backdrop Header -->
    <div class="relative min-h-[440px] sm:min-h-[500px] flex items-end pb-10 overflow-hidden">
        <div class="absolute inset-0 z-0">
            @if($film->embed_trailer_url)
                @if($film->trailer_provider === 'video')
                    <div class="absolute inset-0 overflow-hidden opacity-60">
                        <video src="{{ $film->embed_trailer_url }}" 
                               autoplay loop muted playsinline 
                               class="w-full h-full object-cover filter brightness-75">
                        </video>
                    </div>
                @elseif($film->trailer_provider === 'vimeo' || $film->trailer_provider === 'dailymotion')
                    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-60">
                        <iframe src="{{ $film->embed_trailer_url }}"
                                class="w-[160%] h-[160%] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 object-cover pointer-events-none scale-125 border-0"
                                allow="autoplay; encrypted-media">
                        </iframe>
                    </div>
                @elseif($film->trailer_provider === 'youtube')
                    @php
                        preg_match('/embed\/([^?&]+)/', $film->embed_trailer_url, $ytMatches);
                        $ytId = $ytMatches[1] ?? '';
                    @endphp
                    @if($ytId)
                        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-60">
                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $ytId }}?autoplay=1&mute=1&controls=0&loop=1&playlist={{ $ytId }}&playsinline=1"
                                    class="w-[160%] h-[160%] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 object-cover pointer-events-none scale-125 border-0">
                            </iframe>
                        </div>
                    @else
                        <img src="{{ $film->backdrop_url ?: $film->poster_url }}" alt="Backdrop film {{ $film->title }} ({{ $film->release_year }})" class="w-full h-full object-cover filter brightness-95">
                    @endif
                @else
                    <img src="{{ $film->backdrop_url ?: $film->poster_url }}" alt="Backdrop film {{ $film->title }} ({{ $film->release_year }})" class="w-full h-full object-cover filter brightness-95">
                @endif
            @else
                <img src="{{ $film->backdrop_url ?: $film->poster_url }}" alt="Backdrop film {{ $film->title }} ({{ $film->release_year }})" class="w-full h-full object-cover filter brightness-95">
            @endif

            <div class="absolute inset-0 bg-black/40"></div>
            <!-- Side Gradient for Text Readability -->
            <div class="absolute inset-0 bg-gradient-to-r from-dark-950/90 via-dark-950/40 to-transparent"></div>
            <!-- Bottom Smooth Gradient Fade -->
            <div class="absolute inset-x-0 -bottom-[25px] h-[15rem] bg-gradient-to-t from-dark-950 via-dark-950/95 to-transparent"></div>
        </div>
        
        <div class="absolute inset-x-0 -bottom-px h-20 bg-dark-950 z-0"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col md:flex-row items-start md:items-end gap-8">
            <!-- Glass Poster Card -->
            <div class="w-48 sm:w-56 shrink-0 aspect-[2/3] rounded-3xl overflow-hidden glass-panel p-1.5 -mb-6 md:mb-0 shadow-2xl">
                <img src="{{ $film->poster_url }}" alt="Poster film {{ $film->title }} ({{ $film->release_year }})" class="w-full h-full object-cover rounded-2xl">
            </div>

            <!-- Details Overview -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-3 flex-wrap">
                    @foreach($film->genres as $genre)
                        <span class="px-3 py-1 rounded-xl glass-chip text-zinc-300 text-xs font-semibold">
                            {{ $genre->name }}
                        </span>
                    @endforeach
                    @if($film->visual_style)
                        <span class="px-3 py-1 rounded-xl glass-chip bg-cyan-500/15 text-cyan-300 border-cyan-500/30 text-xs font-semibold flex items-center gap-1">
                            <i data-lucide="sparkles" class="w-3 h-3 text-cyan-400"></i>
                            <span>{{ $film->visual_style }}</span>
                        </span>
                    @endif
                </div>

                <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight mb-3 drop-shadow-md">
                    {{ $film->title }}
                </h1>

                <div class="flex items-center gap-4 text-sm text-zinc-300 mb-6 flex-wrap">
                    <span class="flex items-center gap-1.5 glass-chip text-amber-400 font-bold px-3 py-1.5 rounded-xl text-sm">
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400"></i>
                        <span>{{ number_format($film->rating, 1) }} / 5.0</span>
                    </span>
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-500"></span>
                    <span>Tahun: <strong>{{ $film->release_year }}</strong></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-500"></span>
                    <span>Durasi: <strong>{{ $film->duration_minutes }} menit</strong></span>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-4 flex-wrap">
                    @if($film->isComingSoon())
                        <div class="px-6 py-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 font-bold text-xs flex items-center gap-2 shadow-lg">
                            <i data-lucide="calendar-clock" class="w-4 h-4 text-amber-400"></i>
                            <span>Segera Hadir (Coming Soon {{ $film->available_from ? $film->available_from->translatedFormat('d M Y') : $film->release_year }})</span>
                        </div>
                    @elseif($film->moviebox_subject_id)
                        @php
                            $watchUrl = route('film.watch', $film->slug);
                            if ($film->subject_type === 'series' && isset($lastWatchedHistory)) {
                                $watchUrl .= "?season={$lastWatchedHistory->season_number}&episode={$lastWatchedHistory->episode_number}";
                            }
                        @endphp
                        <a href="{{ $watchUrl }}" 
                           class="px-7 py-3 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-2 cursor-pointer shadow-lg">
                            <i data-lucide="play" class="w-4 h-4 fill-zinc-950"></i>
                            <span>{{ $film->subject_type === 'series' && isset($lastWatchedHistory) ? "Lanjutkan Nonton (S{$lastWatchedHistory->season_number} E{$lastWatchedHistory->episode_number})" : 'Tonton Streaming (HD)' }}</span>
                        </a>

                        <form action="{{ route('watch-party.create') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="film_id" value="{{ $film->id }}">
                            <input type="hidden" name="season_number" value="{{ isset($lastWatchedHistory) ? $lastWatchedHistory->season_number : 1 }}">
                            <input type="hidden" name="episode_number" value="{{ isset($lastWatchedHistory) ? $lastWatchedHistory->episode_number : 1 }}">
                            <button type="submit" class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-2 cursor-pointer shadow-lg border border-amber-400/40">
                                <i data-lucide="users" class="w-4 h-4 fill-zinc-950"></i>
                                <span>Nonton Bareng</span>
                            </button>
                        </form>
                    @endif

                    @auth
                        <button @click="toggleWatchlist()" 
                                :disabled="isLoadingWatchlist"
                                :class="inWatchlist ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm' : 'glass-card hover:border-white/20 text-white'"
                                class="px-6 py-3 rounded-2xl text-xs font-semibold transition-all duration-300 flex items-center gap-2 cursor-pointer border border-white/10">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 transition-colors" :class="inWatchlist ? 'text-amber-300' : 'text-zinc-400'" x-morph="inWatchlist ? 'BookmarkCheck' : 'Bookmark'"></svg>
                            <span x-text="inWatchlist ? 'Di Watchlist' : '+ Tambah ke Watchlist'"></span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="px-6 py-3 rounded-2xl glass-card hover:border-white/20 text-white text-xs font-semibold transition-colors flex items-center gap-2">
                            <i data-lucide="bookmark" class="w-4 h-4 text-zinc-400"></i>
                            <span>Login untuk Tambah Watchlist</span>
                        </a>
                    @endauth

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
                        
                        <button @click="if(navigator.share) { navigator.share({title: filmTitle + ' - faiilmov', text: 'Nonton ' + filmTitle + ' di faiilmov', url: shareUrl}).catch(()=>{}); } else { shareOpen = !shareOpen; }"
                                @contextmenu.prevent="shareOpen = !shareOpen"
                                class="px-5 py-3 rounded-2xl glass-card hover:border-amber-400/40 text-white hover:text-amber-300 text-xs font-semibold transition-all duration-300 flex items-center gap-2 cursor-pointer border border-white/10 hover:bg-white/10 shadow-sm"
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
                             class="absolute left-0 sm:right-0 sm:left-auto top-full mt-3 w-56 p-3 bg-zinc-900/95 border border-white/15 rounded-2xl shadow-2xl backdrop-blur-2xl z-50 space-y-2"
                             style="display: none;">
                            
                            <p class="text-[10px] uppercase font-bold text-zinc-400 tracking-wider px-2 pt-1">Bagikan ke Sosial Media</p>
                            
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
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4l11.733 16h4.267l-11.733 -16z"></path>
                                        <path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"></path>
                                    </svg>
                                    <span>Twitter / X</span>
                                </button>
                                <button type="button" @click="doShare('fb')" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                                    </svg>
                                    <span>Facebook</span>
                                </button>
                            </div>

                            <div class="pt-1 border-t border-white/10">
                                <button type="button" @click="copyLink()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs transition-colors cursor-pointer">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="link" class="w-3.5 h-3.5 text-amber-400"></i>
                                        <span x-text="copied ? 'Tautan Tersalin!' : 'Salin Tautan'"></span>
                                    </div>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 transition-colors" :class="copied ? 'text-emerald-400' : 'text-zinc-400'" x-morph="copied ? 'Check' : 'Copy'"></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Content Body -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">
        
        <!-- Left 2 Columns: Synopsis, Seasons & Episodes, Cast, Reviews -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Interactive Synopsis & AI Insights Glass Panel -->
            <section class="glass-panel p-6 sm:p-7 rounded-3xl border border-white/10 space-y-4 relative overflow-hidden" 
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
                            <h2 class="font-serif font-bold text-lg sm:text-xl text-white">Sinopsis & Alur Cerita</h2>
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
            </section>

            <!-- SERIES ONLY: Seasons & Episodes Selector Panel -->
            @if($film->subject_type === 'series' && $film->seasons->count() > 0)
                @php
                    $seasonsData = $film->seasons->map(function($s) use ($film) {
                        return [
                            'season_number' => $s->season_number,
                            'episodes' => $s->episodes->map(function($e) use ($film, $s) {
                                return [
                                    'episode_number' => $e->episode_number,
                                    'title' => $e->title,
                                    'synopsis' => $e->synopsis,
                                    'duration_minutes' => $e->duration_minutes,
                                    'thumbnail_url' => $e->thumbnail_url ?: 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=50&w=300',
                                    'watch_url' => route('film.watch', $film->slug) . "?season={$s->season_number}&episode={$e->episode_number}",
                                ];
                            }),
                        ];
                    });
                    $seasonsJson = json_encode($seasonsData);
                @endphp

                <div id="react-episode-selector"
                     data-seasons='@json($seasonsData)'
                     data-initial-season="{{ $lastWatchedHistory ? $lastWatchedHistory->season_number : 1 }}">
                    
                    <!-- Blade Fallback -->
                    <section class="glass-panel p-7 rounded-3xl border border-white/10 space-y-6"
                             x-data="{ 
                                activeSeason: {{ $lastWatchedHistory ? $lastWatchedHistory->season_number : 1 }},
                                seasons: {{ $seasonsJson }}
                             }">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
                            <h3 class="font-serif font-bold text-xl text-white flex items-center gap-2">
                                <i data-lucide="tv" class="w-5 h-5 text-amber-400"></i>
                                <span>Daftar Season & Episode</span>
                                <span class="text-xs font-semibold text-zinc-300 bg-amber-500/10 border border-amber-500/20 px-2.5 py-0.5 rounded-full ml-1">
                                    <span x-text="(seasons.find(item => item.season_number === activeSeason)?.episodes || []).length"></span> Episode
                                </span>
                            </h3>

                            <!-- Season Tabs -->
                            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                                <template x-for="s in seasons" :key="s.season_number">
                                    <button @click="activeSeason = s.season_number"
                                            :class="activeSeason === s.season_number ? 'bg-white text-zinc-950 font-bold shadow' : 'glass-card text-zinc-300 hover:text-white border-white/10'"
                                            class="px-4 py-2 rounded-2xl text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5">
                                        <span x-text="'Season ' + s.season_number"></span>
                                        <span class="text-[10px] opacity-75" x-text="'(' + s.episodes.length + ' Ep)'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Episode Cards Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[28rem] overflow-y-auto pr-1">
                            <template x-for="ep in (seasons.find(item => item.season_number === activeSeason)?.episodes || [])" :key="ep.episode_number">
                                <a :href="ep.watch_url" class="p-3 rounded-2xl glass-card border border-white/10 hover:border-amber-400/50 hover:bg-white/10 transition-all flex items-center gap-3 group">
                                    <div class="relative w-24 aspect-video rounded-xl overflow-hidden bg-dark-900 shrink-0 border border-white/10">
                                        <img :src="ep.thumbnail_url" :alt="ep.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-black/30 group-hover:bg-amber-500/20 transition-colors flex items-center justify-center">
                                            <i data-lucide="play-circle" class="w-5 h-5 text-white group-hover:text-amber-300 transition-colors"></i>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            Eps <span x-text="ep.episode_number"></span>
                                        </span>
                                        <h4 class="text-xs font-semibold text-white mt-1 truncate group-hover:text-amber-300 transition-colors" x-text="ep.title"></h4>
                                        <span class="text-[10px] text-zinc-400 block mt-0.5" x-text="ep.duration_minutes + ' menit'"></span>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </section>
                </div>
            @endif

            <!-- Cast / Actors -->
            @if($film->actors->count() > 0)
                @php
                    $mainActors = $film->actors->filter(fn($a) => $a->pivot->role_type === 'main');
                    $regularActors = $film->actors->filter(fn($a) => $a->pivot->role_type !== 'main');
                @endphp

                <section class="space-y-6">
                    <!-- Pemeran Utama -->
                    @if($mainActors->count() > 0)
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <i data-lucide="star" class="w-4 h-4 text-amber-400 fill-amber-400"></i>
                                <h3 class="font-serif font-bold text-lg text-white">Pemeran Utama</h3>
                                <span class="text-[10px] text-amber-400 font-extrabold uppercase px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20">Lead Cast</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                @foreach($mainActors as $actor)
                                    <div class="glass-card p-3 rounded-2xl border border-amber-500/30 bg-amber-500/5 hover:border-amber-400/60 transition-all flex items-center gap-3 shadow-md relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-6 h-6 bg-amber-500/20 rounded-bl-xl flex items-center justify-center text-amber-400">
                                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                                        </div>
                                        <img src="{{ $actor->photo_url ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=150' }}" 
                                             alt="{{ $actor->name }}" 
                                             class="w-11 h-11 rounded-xl object-cover bg-dark-900 border border-amber-500/20 shrink-0">
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-xs font-bold text-white truncate">{{ $actor->name }}</h4>
                                            <p class="text-[11px] text-amber-300/80 truncate">{{ $actor->pivot->character_name ?: 'Pemeran Utama' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Pemeran Pendukung / Regular Cast -->
                    @if($regularActors->count() > 0 || $mainActors->count() === 0)
                        @php
                            $displayRegulars = $mainActors->count() > 0 ? $regularActors : $film->actors;
                        @endphp
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <i data-lucide="users" class="w-4 h-4 text-zinc-400"></i>
                                <h3 class="font-serif font-bold text-lg text-white">
                                    {{ $mainActors->count() > 0 ? 'Pemeran Pendukung' : 'Pemeran / Aktor' }}
                                </h3>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                @foreach($displayRegulars as $actor)
                                    <div class="glass-card p-3 rounded-2xl border border-white/10 flex items-center gap-3 hover:border-white/20 transition-all">
                                        <img src="{{ $actor->photo_url ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=150' }}" 
                                             alt="{{ $actor->name }}" 
                                             class="w-10 h-10 rounded-xl object-cover bg-dark-900 shrink-0">
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-xs font-semibold text-white truncate">{{ $actor->name }}</h4>
                                            <p class="text-[11px] text-zinc-400 truncate">{{ $actor->pivot->character_name ?: 'Pemeran' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            <!-- Soundtrack & OST Film Section -->
            @if(isset($soundtracks) && count($soundtracks) > 0)
                <section class="glass-panel p-7 rounded-3xl border border-white/10" x-data="soundtrackPlayer()">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <div>
                            <h3 class="font-serif font-bold text-xl text-white flex items-center gap-2">
                                <i data-lucide="music" class="w-5 h-5 text-white"></i>
                                <span>Soundtrack & Lagu Film (OST)</span>
                            </h3>
                            <p class="text-xs text-zinc-400 mt-1">Dengarkan cuplikan audio lagu resmi yang menghiasi film {{ $film->title }}.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="https://open.spotify.com/search/{{ urlencode($film->title . ' Soundtrack') }}" target="_blank" rel="noopener noreferrer" 
                               class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold transition-all flex items-center gap-1.5">
                                <i data-lucide="disc" class="w-3.5 h-3.5 text-white"></i>
                                <span>Cari di Spotify</span>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        @foreach($soundtracks as $idx => $st)
                            <div class="p-3 rounded-2xl glass-card border border-white/10 hover:border-white/30 transition-all flex items-center gap-3 group relative overflow-hidden">
                                <div class="w-12 h-12 rounded-xl overflow-hidden bg-zinc-900 shrink-0 border border-white/10 shadow-md">
                                    <img src="{{ $st['artwork_url'] ?: 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=150' }}" 
                                         alt="{{ $st['track_name'] }}" 
                                         class="w-full h-full object-cover">
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-bold text-white truncate group-hover:text-amber-300 transition-colors">
                                        {{ $st['track_name'] }}
                                    </h4>
                                    <p class="text-[11px] text-zinc-400 truncate mt-0.5">
                                        {{ $st['artist_name'] }}
                                    </p>
                                    @if($st['collection_name'])
                                        <p class="text-[9.5px] text-zinc-500 truncate mt-0.5">
                                            {{ $st['collection_name'] }}
                                        </p>
                                    @endif
                                </div>

                                @if($st['preview_audio_url'])
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <!-- Play / Pause Button -->
                                        <button type="button" 
                                                @click="togglePlay('{{ $st['preview_audio_url'] }}', '{{ addslashes($st['track_name']) }}', '{{ addslashes($st['artist_name']) }}')"
                                                :class="currentAudioUrl === '{{ $st['preview_audio_url'] }}' && isPlaying ? 'bg-white text-zinc-950 shadow-lg shadow-white/20' : 'bg-white/5 hover:bg-white/15 text-white border border-white/10'"
                                                class="w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer"
                                                title="Putar Audio Preview">
                                            <i :data-lucide="currentAudioUrl === '{{ $st['preview_audio_url'] }}' && isPlaying ? 'pause' : 'play'" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Download MP3 Button -->
                                        <a href="{{ route('soundtrack.download') }}?url={{ urlencode($st['preview_audio_url']) }}&title={{ urlencode($st['track_name']) }}&artist={{ urlencode($st['artist_name']) }}" 
                                           target="_blank"
                                           download
                                           class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/15 flex items-center justify-center transition-all cursor-pointer"
                                           title="Download MP3 ({{ $st['track_name'] }})">
                                            <i data-lucide="download" class="w-4 h-4 text-white"></i>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Mini Audio Bar Player when track is playing -->
                    <div x-show="currentAudioUrl" 
                         x-cloak 
                         x-transition
                         class="mt-4 p-3 rounded-2xl bg-zinc-900 border border-white/15 shadow-xl flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-7 h-7 rounded-lg bg-white/10 text-white flex items-center justify-center shrink-0">
                                <i data-lucide="music" class="w-4 h-4 animate-pulse text-white"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-white truncate" x-text="activeTrackName"></p>
                                <p class="text-[10px] text-zinc-400 truncate" x-text="activeArtistName"></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a :href="'{{ route('soundtrack.download') }}?url=' + encodeURIComponent(currentAudioUrl) + '&title=' + encodeURIComponent(activeTrackName) + '&artist=' + encodeURIComponent(activeArtistName)" 
                               target="_blank"
                               download
                               class="px-3 py-1.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-colors shadow">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Download MP3</span>
                            </a>
                            <button type="button" @click="stopPlay()" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-zinc-300 text-xs font-bold cursor-pointer">
                                Stop
                            </button>
                        </div>
                    </div>
                </section>
            @endif

            <!-- Reviews & Community Ratings Section -->
            <section class="glass-panel p-7 rounded-3xl border border-white/10">
                <h3 class="font-serif font-bold text-xl text-white mb-6 flex items-center justify-between">
                    <span>Ulasan & Rating Pengguna ({{ $film->reviews->count() }})</span>
                    <span class="text-xs font-semibold text-zinc-300 flex items-center gap-1">
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <span>Rata-rata: {{ number_format($film->rating, 1) }} / 5.0</span>
                    </span>
                </h3>

                <!-- Submit Review Form -->
                @auth
                    @if(Auth::user()->activeProfile())
                        <div class="glass-card p-4 rounded-2xl mb-8 border border-amber-500/20 bg-amber-500/5 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 text-amber-300 text-xs text-left">
                                <i data-lucide="info" class="w-4 h-4 shrink-0 text-amber-400"></i>
                                <span>Anda sedang menggunakan Sub-Profil (<strong class="text-white">{{ Auth::user()->activeProfile()->name }}</strong>). Fitur ulasan & rating khusus untuk <strong>Akun Utama</strong>.</span>
                            </div>
                            <form action="{{ route('profiles.switch-main') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-extrabold text-xs shrink-0 transition-colors shadow-md cursor-pointer">
                                    Beralih ke Akun Utama
                                </button>
                            </form>
                        </div>
                    @else
                        <form action="{{ route('review.store', $film->id) }}" method="POST" class="glass-card p-5 rounded-2xl mb-8 border border-white/10">
                            @csrf
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 rounded-full overflow-hidden bg-zinc-800 border border-white/10 shrink-0">
                                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-white">Tulis Ulasan Anda</h4>
                                    <p class="text-[11px] text-zinc-400">Sebagai <strong class="text-zinc-200">{{ Auth::user()->name }}</strong></p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-[11px] text-zinc-400 mb-2">Beri Rating (1 - 5 Bintang)</label>
                                <div class="flex items-center gap-2" x-data="{ star: {{ $userReview->rating ?? 5 }} }">
                                    <input type="hidden" name="rating" :value="star">
                                    <template x-for="i in 5">
                                        <button type="button" @click="star = i" class="p-1 text-amber-400">
                                            <i data-lucide="star" :class="i <= star ? 'fill-amber-400 text-amber-400' : 'text-zinc-600'" class="w-5 h-5"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="mb-4">
                                <textarea name="comment" rows="3" placeholder="Bagaimana pendapat Anda tentang film ini?" 
                                          class="w-full bg-dark-950/60 text-xs text-white p-3 rounded-2xl border border-white/10 focus:outline-none focus:border-white/30">{{ $userReview->comment ?? '' }}</textarea>
                            </div>

                            <button type="submit" class="px-5 py-2.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors shadow-md cursor-pointer">
                                {{ $userReview ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}
                            </button>
                        </form>
                    @endif
                @else
                    <div class="glass-card p-4 rounded-2xl text-center mb-8 border border-white/10">
                        <p class="text-xs text-zinc-400 mb-2">Ingin memberi ulasan & bintang untuk film ini?</p>
                        <a href="{{ route('login') }}" class="text-xs font-bold text-white hover:underline">Masuk ke Akun Anda</a>
                    </div>
                @endauth

                <!-- Reviews List -->
                <div class="space-y-4">
                    @forelse($film->reviews as $rev)
                        <div class="glass-card p-4 sm:p-5 rounded-2xl border border-white/10">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-zinc-800 border border-white/10 flex items-center justify-center shrink-0">
                                        <img src="{{ $rev->user?->avatar_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($rev->user?->name ?? 'User') }}" 
                                             alt="{{ $rev->user?->name ?? 'User' }}" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-white block">{{ $rev->user?->name ?? 'Pengguna' }}</span>
                                        <span class="text-[10px] text-zinc-500 block">{{ $rev->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-amber-400 text-xs font-bold bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded-xl">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                                    <span>{{ $rev->rating }} / 5</span>
                                </div>
                            </div>
                            <p class="text-xs text-zinc-300 leading-relaxed">{{ $rev->comment }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-500 text-center py-4">Belum ada ulasan untuk film ini. Jadilah yang pertama memberi penilaian!</p>
                    @endforelse
                </div>
            </section>

        </div>

        <!-- Right Column: Metadata Sidebar & Related Films -->
        <div class="space-y-8">
            <section class="glass-panel p-6 rounded-3xl border border-white/10 space-y-4 text-xs">
                <h3 class="font-serif font-bold text-base text-white mb-2">Informasi Tambahan</h3>
                <div class="flex justify-between border-b border-white/10 pb-2">
                    <span class="text-zinc-400">Tipe Content</span>
                    <span class="text-white font-semibold uppercase">{{ $film->subject_type }}</span>
                </div>
                <div class="flex justify-between border-b border-white/10 pb-2">
                    <span class="text-zinc-400">Tahun Rilis</span>
                    <span class="text-white font-semibold">{{ $film->release_year }}</span>
                </div>
                <div class="flex justify-between border-b border-white/10 pb-2">
                    <span class="text-zinc-400">Durasi Film</span>
                    <span class="text-white font-semibold">{{ $film->duration_minutes }} Menit</span>
                </div>
                <div class="flex justify-between pb-2">
                    <span class="text-zinc-400">Status Server Stream</span>
                    <span class="text-emerald-400 font-semibold">Aktif & Ready</span>
                </div>
            </section>

            <!-- Related Films -->
            @if(isset($relatedFilms) && count($relatedFilms) > 0)
                <section>
                    <h3 class="font-serif font-bold text-base text-white mb-4">Film Serupa</h3>
                    <div class="space-y-3">
                        @foreach($relatedFilms as $rel)
                            <a href="{{ route('film.show', $rel->slug) }}" class="glass-card p-2.5 rounded-2xl border border-white/10 flex items-center gap-3 hover:border-white/30 transition-colors">
                                <div class="relative w-12 h-16 shrink-0">
                                    <img src="{{ $rel->poster_url }}" alt="{{ $rel->title }}" class="w-full h-full object-cover rounded-xl bg-dark-900">
                                    @if($rel->max_resolution)
                                        <span class="absolute bottom-0.5 left-0.5 glass-chip px-1.5 py-0.5 text-[8px] font-extrabold uppercase rounded-md {{ $rel->max_resolution === '4K' ? 'text-violet-300' : 'text-sky-300' }} tracking-wider leading-none">
                                            {{ $rel->max_resolution }}
                                        </span>
                                    @endif
                                </div>
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
                </section>
            @endif
        </div>

    </div>

    <!-- Modal Trailer Video -->
    @if($film->embed_trailer_url)
        <div x-show="showTrailerModal" 
             x-cloak
             x-transition.opacity
             class="fixed inset-0 z-50 bg-black/90 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6">
            
            <div @click.outside="showTrailerModal = false" 
                 class="bg-zinc-950 border border-white/20 rounded-3xl max-w-4xl w-full overflow-hidden shadow-2xl relative">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-zinc-900/80">
                    <div class="flex items-center gap-2">
                        <i data-lucide="video" class="w-5 h-5 text-red-500"></i>
                        <h3 class="font-serif font-bold text-white text-base">Trailer Resmi: {{ $film->title }}</h3>
                    </div>
                    <button type="button" @click="showTrailerModal = false" class="p-1.5 rounded-xl text-zinc-400 hover:text-white bg-white/5 hover:bg-white/10 transition-colors cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Video Player Embed -->
                <div class="aspect-video w-full bg-black relative flex items-center justify-center">
                    <template x-if="showTrailerModal">
                        @if($film->trailer_provider === 'video')
                            <video src="{{ $film->embed_trailer_url }}" 
                                   controls autoplay 
                                   class="w-full h-full object-contain">
                            </video>
                        @else
                            <iframe src="{{ $film->embed_trailer_url }}?autoplay=1&rel=0" 
                                    class="w-full h-full border-0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        @endif
                    </template>
                </div>
            </div>
        </div>
    @endif

</div>

<script>
    function detailPage(initialInWatchlist = false) {
        return {
            showTrailerModal: false,
            playerOpen: false,
            isFetchingStreams: false,
            resourceList: [],
            activeVideoUrl: null,
            inWatchlist: initialInWatchlist,
            isLoadingWatchlist: false,

            async toggleWatchlist() {
                if (this.isLoadingWatchlist) return;

                // Optimistic UI update
                const previousState = this.inWatchlist;
                this.inWatchlist = !previousState;
                this.isLoadingWatchlist = true;

                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });

                try {
                    const res = await fetch('{{ route('watchlist.toggle', $film->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await res.json();
                    if (data.status === 'ok') {
                        this.inWatchlist = data.inWatchlist;
                    } else {
                        this.inWatchlist = previousState;
                    }
                } catch (e) {
                    this.inWatchlist = previousState;
                    console.error('Watchlist toggle error:', e);
                } finally {
                    this.isLoadingWatchlist = false;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                }
            },

            async openPlayer(subjectId) {
                this.playerOpen = true;
                this.isFetchingStreams = true;
                this.resourceList = [];
                this.activeVideoUrl = null;

                try {
                    const res = await fetch(`/moviebox/resources/${subjectId}`);
                    const data = await res.json();
                    const list = data.list || (Array.isArray(data) ? data : []);
                    this.resourceList = list;
                    if (this.resourceList.length > 0) {
                        const first = this.resourceList[0];
                        this.activeVideoUrl = first.resourceLink || first.url || first.playUrl;
                    }
                } catch (e) {
                    console.error('Fetch resources error:', e);
                } finally {
                    this.isFetchingStreams = false;
                    this.$nextTick(() => lucide.createIcons());
                }
            }
        }
    }

    function soundtrackPlayer() {
        return {
            audioObj: null,
            currentAudioUrl: null,
            isPlaying: false,
            activeTrackName: '',
            activeArtistName: '',

            togglePlay(url, title, artist) {
                if (this.currentAudioUrl === url && this.isPlaying) {
                    this.pauseAudio();
                } else {
                    this.playAudio(url, title, artist);
                }
            },

            playAudio(url, title, artist) {
                if (this.audioObj) {
                    this.audioObj.pause();
                }

                this.currentAudioUrl = url;
                this.activeTrackName = title;
                this.activeArtistName = artist;
                this.audioObj = new Audio(url);

                this.audioObj.play().then(() => {
                    this.isPlaying = true;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }).catch(e => {
                    console.error('Audio playback error:', e);
                });

                this.audioObj.onended = () => {
                    this.isPlaying = false;
                    this.currentAudioUrl = null;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                };
            },

            pauseAudio() {
                if (this.audioObj) {
                    this.audioObj.pause();
                }
                this.isPlaying = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            stopPlay() {
                if (this.audioObj) {
                    this.audioObj.pause();
                    this.audioObj = null;
                }
                this.currentAudioUrl = null;
                this.isPlaying = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
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
