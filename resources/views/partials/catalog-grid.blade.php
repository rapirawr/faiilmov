<div id="catalog-content" class="space-y-6">
    <div class="flex items-center justify-between border-b border-white/10 pb-3">
        <h2 class="font-serif font-bold text-xl sm:text-2xl text-white tracking-tight">Full Film Catalog</h2>
        <span class="text-xs text-zinc-400 font-medium">Total: {{ $films->total() }}</span>
    </div>

    <!-- Catalog Grid -->
    @if(isset($films) && is_countable($films) && count($films) > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
            @foreach($films as $film)
                @if($film)
                    <x-film-card :film="$film" />
                @endif
            @endforeach
        </div>

        <div class="mt-8 flex justify-center">
            {{ $films->links() }}
        </div>
    @else
        <div class="glass-panel p-12 rounded-3xl text-center border border-white/10 max-w-2xl mx-auto my-6">
            <i data-lucide="search-x" class="w-12 h-12 text-zinc-500 mx-auto mb-3"></i>
            <h3 class="font-serif font-bold text-lg text-white mb-1">
                @if(!empty($searchQuery))
                    Tidak ada film ditemukan untuk "<span class="text-amber-400">{{ $searchQuery }}</span>"
                @else
                    Tidak ada film ditemukan
                @endif
            </h3>
            <p class="text-xs text-zinc-400 max-w-md mx-auto">Coba gunakan kata kunci lain atau ubah kriteria filter.</p>
        </div>
    @endif
</div>
