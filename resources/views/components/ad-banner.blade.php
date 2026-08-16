@props([
    'placement' => 'player_top',
    'class' => '',
])

@php
    $targetSlot = (string) ($placement ?? $attributes->get('placement') ?? 'player_top');
    if ($targetSlot === 'player_top' && $attributes->has('slot')) {
        $rawSlot = (string) $attributes->get('slot');
        if (!empty($rawSlot)) $targetSlot = $rawSlot;
    }
    $finalSlotName = str_starts_with($targetSlot, 'banner_') ? $targetSlot : "banner_{$targetSlot}";
    $code = \App\Services\AdService::renderSlot($finalSlotName);
@endphp

@if($code)
    <div class="w-full flex flex-col items-center justify-center my-4 overflow-hidden {{ $class }}">
        <div class="flex items-center text-[9px] font-mono tracking-widest text-zinc-500 uppercase mb-1">
            <span>Advertisement</span>
        </div>
        <div class="w-full flex justify-center items-center overflow-x-auto no-scrollbar rounded-2xl glass-card border border-white/5 p-2 sm:p-3 shadow-lg max-w-full">
            {!! $code !!}
        </div>
    </div>
@endif
