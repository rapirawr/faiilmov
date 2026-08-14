@props([
    'icon' => 'inbox',
    'title' => 'Belum Ada Data Ditemukan',
    'description' => 'Data saat ini masih kosong atau tidak sesuai dengan filter pencarian.',
    'actionUrl' => null,
    'actionLabel' => null,
    'actionIcon' => 'plus',
])

<div class="p-8 sm:p-14 text-center rounded-3xl bg-zinc-900/40 border border-white/5 flex flex-col items-center justify-center space-y-4 my-4">
    <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-zinc-400 shadow-inner">
        <i data-lucide="{{ $icon }}" class="w-7 h-7"></i>
    </div>
    
    <div class="space-y-1 max-w-sm">
        <h4 class="font-bold text-sm sm:text-base text-white tracking-wide">{{ $title }}</h4>
        <p class="text-xs text-zinc-400 leading-relaxed">{{ $description }}</p>
    </div>

    @if($actionUrl && $actionLabel)
        <div class="pt-2">
            <a href="{{ $actionUrl }}" class="px-4 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer hover:scale-[1.02] active:scale-[0.98]">
                <i data-lucide="{{ $actionIcon }}" class="w-4 h-4"></i>
                <span>{{ $actionLabel }}</span>
            </a>
        </div>
    @endif
</div>
