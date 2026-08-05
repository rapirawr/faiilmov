@props(['film'])

<div class="group/card glass-card rounded-2xl overflow-hidden p-2.5 hover:border-white/30 hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between shadow-md">
    <!-- Poster with Glass Rating Chip & Resolution Badge -->
    <a href="{{ route('film.show', $film->slug) }}" class="relative aspect-[2/3] block rounded-xl overflow-hidden bg-dark-900 mb-2">
        <img src="{{ $film->thumbnail_url }}" 
             alt="{{ $film->title }}" 
             loading="lazy" 
             decoding="async"
             width="320"
             height="480"
             class="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500">
        
        <div class="absolute top-2 right-2 bg-dark-950/80 border border-white/10 text-amber-400 font-bold px-2.5 py-1 text-xs rounded-xl flex items-center gap-1 shadow-md">
            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
            <span>{{ number_format($film->rating, 1) }}</span>
        </div>

        @if($film->max_resolution)
            <div class="absolute bottom-2 left-2 bg-dark-950/80 border border-white/10 px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-lg {{ $film->max_resolution === '4K' ? 'text-violet-300' : 'text-sky-300' }} tracking-wider shadow">
                {{ $film->max_resolution }}
            </div>
        @endif
    </a>

    <!-- Card Info -->
    <div class="p-1 flex-1 flex flex-col justify-between">
        <div>
            <a href="{{ route('film.show', $film->slug) }}" class="font-semibold text-xs text-white group-hover/card:text-zinc-300 transition-colors truncate block w-full" title="{{ $film->title }}">
                {{ $film->title }}
            </a>
            <div class="flex items-center justify-between text-[11px] text-zinc-400 mt-1">
                <span>{{ $film->release_year }}</span>
                <span class="text-[10px] text-zinc-400 font-semibold truncate max-w-[80px]">
                    {{ $film->genres->first()?->name ?? strtoupper($film->subject_type) }}
                </span>
            </div>
        </div>
    </div>
</div>
