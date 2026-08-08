@props([
    'name',
    'id' => null,
    'type' => 'text',
    'label' => null,
    'icon' => null,
    'placeholder' => '',
    'value' => null,
    'required' => false,
    'autofocus' => false,
    'autocomplete' => null,
    'toggleable' => null,
])

@php
    $id = $id ?? $name;
    $isPassword = $type === 'password';
    $canToggle = $toggleable ?? $isPassword;
@endphp

<div>
    @if($label)
        <label for="{{ $id }}" class="block text-[11px] font-bold text-zinc-300 uppercase tracking-wider mb-2">
            {{ $label }}
        </label>
    @endif

    <div 
        @if($canToggle) x-data="{ show: false }" @endif
        class="flex items-center gap-3 px-4 rounded-2xl border border-white/10 bg-zinc-900/80 focus-within:border-zinc-300 focus-within:bg-zinc-800/90 transition-all shadow-inner"
    >
        @if($icon)
            <i data-lucide="{{ $icon }}" class="w-5 h-5 shrink-0 text-zinc-400"></i>
        @endif

        <input 
            id="{{ $id }}"
            name="{{ $name }}"
            @if($canToggle)
                :type="show ? 'text' : '{{ $type }}'"
            @else
                type="{{ $type }}"
            @endif
            @if($value !== null) value="{{ $value }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($autofocus) autofocus @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            {{ $attributes->merge(['class' => 'flex-1 min-w-0 bg-transparent py-3.5 text-xs sm:text-sm text-zinc-100 placeholder-zinc-500 border-none outline-none focus:outline-none focus:ring-0']) }}
        />

        @if($canToggle)
            <button 
                type="button" 
                @click="show = !show" 
                aria-label="Toggle password visibility"
                class="shrink-0 p-2 -mr-2 text-zinc-400 hover:text-white transition-colors focus:outline-none flex items-center justify-center min-w-[40px] min-h-[40px] rounded-xl hover:bg-white/5 active:bg-white/10 cursor-pointer"
            >
                <i x-show="!show" data-lucide="eye" class="w-5 h-5"></i>
                <i x-show="show" data-lucide="eye-off" class="w-5 h-5" style="display: none;"></i>
            </button>
        @endif
    </div>

    @error($name)
        <p class="text-[11px] text-rose-400 mt-1.5 flex items-center gap-1 font-medium">
            <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
