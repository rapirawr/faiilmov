@props(['film'])

@php
    $rawAge = $film->content_rating ? strtoupper(trim($film->content_rating)) : '13+';
    $titleLower = strtolower($film->title ?? '');

    // Emergency check for horror/watcher titles if rating was unrated or incorrectly SU
    if (str_contains($titleLower, 'horror') || str_contains($titleLower, 'watcher') || str_contains($titleLower, 'nun') || str_contains($titleLower, 'dead') || str_contains($titleLower, 'blood') || str_contains($titleLower, 'slasher') || str_contains($titleLower, 'kill')) {
        $age = '18+';
    } elseif (in_array($rawAge, ['R', 'NC-17', '18+'])) {
        $age = '18+';
    } elseif (in_array($rawAge, ['16+', 'TV-MA'])) {
        $age = '16+';
    } elseif (in_array($rawAge, ['13+', 'PG-13', 'TV-14', 'PG'])) {
        $age = '13+';
    } elseif ($rawAge === 'SU' || $rawAge === 'G' || $rawAge === 'TV-Y') {
        $age = 'SU';
    } else {
        $age = '13+';
    }
    
    $ageBadgeClass = match($age) {
        '18+' => 'bg-rose-500/20 text-rose-300 border-rose-500/40 font-bold',
        '16+' => 'bg-orange-500/20 text-orange-300 border-orange-500/40 font-bold',
        '13+' => 'bg-sky-500/20 text-sky-300 border-sky-500/40 font-bold',
        'SU'  => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 font-bold',
        default => 'bg-sky-500/20 text-sky-300 border-white/20 font-bold',
    };

    $dur = '1h 30m';
    if ($film->duration_minutes > 0 && $film->subject_type === 'movie') {
        $h = floor($film->duration_minutes / 60);
        $m = $film->duration_minutes % 60;
        $dur = ($h > 0 ? "{$h}h " : '') . ($m > 0 ? "{$m}m" : '');
    } elseif ($film->subject_type === 'dracin') {
        $dur = 'Dracin';
    } elseif ($film->subject_type === 'series') {
        $dur = 'TV Series';
    }

    $filmData = [
        'id' => $film->id,
        'title' => $film->title,
        'slug' => $film->slug,
        'rating' => $film->rating,
        'release_year' => $film->release_year,
        'duration_minutes' => $film->duration_minutes,
        'content_rating' => $film->content_rating,
        'subject_type' => $film->subject_type,
        'max_resolution' => $film->max_resolution,
        'thumbnail_url' => $film->thumbnail_url,
        'poster_url' => $film->poster_url,
        'genres' => $film->genres ? $film->genres->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->toArray() : [],
        'available_from' => $film->available_from ? $film->available_from->toIso8601String() : null,
        'is_coming_soon' => $film->isComingSoon(),
    ];
@endphp

<div class="react-film-card w-full h-full"
     data-film='@json($filmData)'
     data-csrf="{{ csrf_token() }}">
    
    <!-- Blade Fallback -->
    <div class="group/card glass-card rounded-2xl overflow-hidden p-2.5 hover:border-white/30 hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between shadow-md h-full">
        <!-- Poster -->
        <a href="{{ route('film.show', $film->slug) }}" class="relative aspect-[2/3] block rounded-xl overflow-hidden bg-dark-900 mb-2">
            <img src="{{ $film->thumbnail_url }}" 
                 alt="{{ $film->title }}" 
                 loading="lazy" 
                 decoding="async"
                 width="320"
                 height="480"
                 class="w-full h-full object-cover group-hover/card:scale-105 group-hover/card:blur-[3px] transition-all duration-500">

            @if($film->max_resolution)
                <div class="absolute bottom-2 left-2 bg-dark-950/80 border border-white/10 px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-lg {{ $film->max_resolution === '4K' ? 'text-violet-300' : 'text-sky-300' }} tracking-wider shadow">
                    {{ $film->max_resolution }}
                </div>
            @endif

            <!-- Hover Action Overlay -->
            @if($filmData['is_coming_soon'])
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/card:opacity-100 transition-all duration-200 pointer-events-none p-2">
                    <span class="px-3.5 py-1.5 rounded-full bg-amber-500 text-zinc-950 font-black text-xs uppercase tracking-wider shadow-2xl border border-amber-300/80 flex items-center gap-1.5 transform group-hover/card:scale-105 transition-transform duration-200">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        <span>Coming Soon</span>
                    </span>
                </div>
            @else
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/card:opacity-100 transition-all duration-200 pointer-events-none">
                    <div class="p-3.5 rounded-full bg-white text-zinc-950 shadow-2xl flex items-center justify-center transform group-hover/card:scale-110 transition-transform duration-200">
                        <i data-lucide="play" class="w-5 h-5 fill-zinc-950 ml-0.5"></i>
                    </div>
                </div>
            @endif
        </a>

        <!-- Card Info -->
        <div class="p-1 flex-1 flex flex-col justify-between">
            <div>
                <a href="{{ route('film.show', $film->slug) }}" class="font-semibold text-xs text-white group-hover/card:text-zinc-300 transition-colors truncate block w-full mb-1" title="{{ $film->title }}">
                    {{ $film->title }}
                </a>
                <div class="flex items-center gap-1.5 text-[10.5px] text-zinc-400 font-medium flex-wrap">
                    <span class="px-1.5 py-0.5 rounded-md border text-[9.5px] font-extrabold uppercase tracking-wider {{ $ageBadgeClass }}">
                        {{ $age }}
                    </span>
                    <span class="text-zinc-500">•</span>
                    <span>{{ $dur }}</span>
                    <span class="text-zinc-500">•</span>
                    <span>{{ $film->release_year }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
