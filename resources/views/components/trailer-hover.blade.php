<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $film->title }} - faiilmov</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-dark-950 text-white">
    <div id="app">
        <div class="px-4 sm:px-8 py-6">
            <!-- Film Card with Trailer Preview -->
            <div x-data="{ showTrailer: false, hovered: false }"
                 @mouseenter="hovered = true"
                 @mouseleave="hovered = false"
                 class="relative rounded-3xl overflow-hidden bg-dark-900 border border-white/10 shadow-xl group">
                
                <!-- Poster Image -->
                <img src="{{ $film->poster_url }}" 
                     alt="{{ $film->title }}" 
                     class="w-full h-64 sm:h-80 object-cover transition-transform duration-500 group-hover:scale-105">
                
                <!-- Trailer Hover Overlay -->
                <div class="absolute inset-0 bg-black/80 backdrop-blur-sm flex flex-col items-center justify-center gap-4 transition-opacity duration-300"
                     :class="hovered ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                    
                    <div class="w-20 h-20 rounded-full bg-amber-500/20 border border-amber-500/50 flex items-center justify-center animate-bounce">
                        <i data-lucide="play" class="w-8 h-8 text-amber-400 fill-amber-400"></i>
                    </div>
                    <p class="text-sm font-bold text-white">Hover untuk Preview Trailer</p>
                </div>

                <!-- Trailer Modal (Modal-triggered, auto-plays on open) -->
                <template x-if="showTrailer">
                    <div class="fixed inset-0 z-[9999] bg-black/95 backdrop-blur-md flex items-center justify-center p-4">
                        <button @click="showTrailer = false" class="absolute top-4 right-4 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                        <div class="w-full max-w-4xl aspect-video rounded-2xl overflow-hidden bg-black shadow-2xl">
                            <iframe class="w-full h-full"
                                    :src="`https://www.youtube.com/embed/{{ getYoutubeId('{{ $film->trailer_url }}') }}?autoplay=1&rel=0`"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex gap-4">
                <a href="{{ route('film.watch', $film->slug) }}" 
                   class="flex-1 px-6 py-3 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-sm transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="play" class="w-5 h-5 fill-zinc-950"></i>
                    Tonton Streaming
                </a>
                
                <button @click="showTrailer = true"
                        class="flex-1 px-6 py-3 rounded-2xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 border border-amber-500/30 font-bold text-sm transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="film" class="w-5 h-5 fill-amber-400"></i>
                    Preview Trailer
                </button>
            </div>
        </div>
    </div>

    <script>
        function getYoutubeId(url) {
            const regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[7].length == 11) ? match[7] : false;
        }
    </script>
</body>
</html>
