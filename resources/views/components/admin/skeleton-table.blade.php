@props([
    'rows' => 5,
    'cols' => 4,
])

<div class="w-full overflow-hidden rounded-2xl border border-white/10 bg-zinc-900/40 divide-y divide-white/5 animate-pulse">
    <!-- Header Skeleton -->
    <div class="p-4 bg-white/5 flex items-center justify-between gap-4">
        <div class="h-4 bg-white/10 rounded w-1/4"></div>
        <div class="h-4 bg-white/10 rounded w-1/6"></div>
        <div class="h-4 bg-white/10 rounded w-1/6 hidden sm:block"></div>
        <div class="h-4 bg-white/10 rounded w-12"></div>
    </div>

    <!-- Rows Skeleton -->
    @for($i = 0; $i < $rows; $i++)
        <div class="p-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 w-1/3">
                <div class="w-10 h-10 rounded-xl bg-white/10 shrink-0"></div>
                <div class="space-y-1.5 flex-1">
                    <div class="h-3.5 bg-white/10 rounded w-4/5"></div>
                    <div class="h-2.5 bg-white/5 rounded w-1/2"></div>
                </div>
            </div>
            <div class="h-3 bg-white/5 rounded w-1/6 hidden sm:block"></div>
            <div class="h-5 bg-white/10 rounded-full w-20"></div>
            <div class="h-8 bg-white/10 rounded-xl w-16"></div>
        </div>
    @endfor
</div>
