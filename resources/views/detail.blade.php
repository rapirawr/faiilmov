@extends('layouts.app')

@section('title', $film->title . ' | faiilmov')

@section('content')
<div x-data="detailPage({{ $userWatchlist ? 'true' : 'false' }})">

    <!-- Film Backdrop Header -->
    <div class="relative min-h-[440px] sm:min-h-[500px] flex items-end pb-10 overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img src="{{ $film->backdrop_url ?: $film->poster_url }}" alt="{{ $film->title }}" class="w-full h-full object-cover filter brightness-95">
            <div class="absolute inset-0 bg-black/15"></div>
            <!-- Side Gradient for Text Readability -->
            <div class="absolute inset-0 bg-gradient-to-r from-dark-950/80 via-dark-950/30 to-transparent"></div>
            <!-- Bottom Smooth Gradient Fade -->
            <div class="absolute inset-x-0 -bottom-[25px] h-[15rem] bg-gradient-to-t from-dark-950 via-dark-950/95 to-transparent"></div>
        </div>
        
        <div class="absolute inset-x-0 -bottom-px h-20 bg-dark-950 z-0"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col md:flex-row items-start md:items-end gap-8">
            <!-- Glass Poster Card -->
            <div class="w-48 sm:w-56 shrink-0 aspect-[2/3] rounded-3xl overflow-hidden glass-panel p-1.5 -mb-6 md:mb-0 shadow-2xl">
                <img src="{{ $film->poster_url }}" alt="{{ $film->title }}" class="w-full h-full object-cover rounded-2xl">
            </div>

            <!-- Details Overview -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-3 flex-wrap">
                    @foreach($film->genres as $genre)
                        <span class="px-3 py-1 rounded-xl glass-chip text-zinc-300 text-xs font-semibold">
                            {{ $genre->name }}
                        </span>
                    @endforeach
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
                    @if($film->moviebox_subject_id)
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
                            <i :data-lucide="inWatchlist ? 'bookmark-check' : 'bookmark'" 
                               :class="inWatchlist ? 'text-amber-300 fill-amber-300' : 'text-zinc-400'"
                               class="w-4 h-4 transition-colors"></i>
                            <span x-text="inWatchlist ? 'Di Watchlist' : '+ Tambah ke Watchlist'"></span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="px-6 py-3 rounded-2xl glass-card hover:border-white/20 text-white text-xs font-semibold transition-colors flex items-center gap-2">
                            <i data-lucide="bookmark" class="w-4 h-4 text-zinc-400"></i>
                            <span>Login untuk Tambah Watchlist</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Details Content Body -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">
        
        <!-- Left 2 Columns: Synopsis, Seasons & Episodes, Cast, Reviews -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Synopsis Glass Panel -->
            <section class="glass-panel p-7 rounded-3xl border border-white/10">
                <h3 class="font-serif font-bold text-xl text-white mb-3">Sinopsis</h3>
                <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed">
                    {{ $film->synopsis ?: 'Belum ada deskripsi sinopsis resmi untuk film ini.' }}
                </p>
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
                <section>
                    <h3 class="font-serif font-bold text-xl text-white mb-4">Pemeran / Aktor</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach($film->actors as $actor)
                            <div class="glass-card p-3 rounded-2xl border border-white/10 flex items-center gap-3">
                                <img src="{{ $actor->photo_url ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=150' }}" 
                                     alt="{{ $actor->name }}" 
                                     class="w-10 h-10 rounded-xl object-cover bg-dark-900">
                                <div class="min-w-0">
                                    <h4 class="text-xs font-semibold text-white truncate">{{ $actor->name }}</h4>
                                    <p class="text-[11px] text-zinc-400 truncate">{{ $actor->pivot->character_name ?: 'Peran' }}</p>
                                </div>
                            </div>
                        @endforeach
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
                    <form action="{{ route('review.store', $film->id) }}" method="POST" class="glass-card p-5 rounded-2xl mb-8 border border-white/10">
                        @csrf
                        <h4 class="text-xs font-bold text-white mb-3">Tulis Ulasan Anda</h4>
                        
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

                        <button type="submit" class="px-5 py-2.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors shadow-md">
                            {{ $userReview ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}
                        </button>
                    </form>
                @else
                    <div class="glass-card p-4 rounded-2xl text-center mb-8 border border-white/10">
                        <p class="text-xs text-zinc-400 mb-2">Ingin memberi ulasan & bintang untuk film ini?</p>
                        <a href="{{ route('login') }}" class="text-xs font-bold text-white hover:underline">Masuk ke Akun Anda</a>
                    </div>
                @endauth

                <!-- Reviews List -->
                <div class="space-y-4">
                    @forelse($film->reviews as $rev)
                        <div class="glass-card p-4 rounded-2xl border border-white/10">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-xl bg-white flex items-center justify-center text-[10px] font-bold text-zinc-950">
                                        {{ strtoupper(substr($rev->user->name, 0, 2)) }}
                                    </div>
                                    <span class="text-xs font-bold text-white">{{ $rev->user->name }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-zinc-300 text-xs font-bold">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                                    <span>{{ $rev->rating }} / 5</span>
                                </div>
                            </div>
                            <p class="text-xs text-zinc-300 leading-relaxed">{{ $rev->comment }}</p>
                            <span class="text-[10px] text-zinc-500 block mt-2">{{ $rev->created_at->diffForHumans() }}</span>
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
            @if($relatedFilms->count() > 0)
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

</div>

<script>
    function detailPage(initialInWatchlist = false) {
        return {
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
</script>
@endsection
