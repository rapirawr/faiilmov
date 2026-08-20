@props([
    'name' => '',
    'value' => '',
    'options' => [],
    'placeholder' => 'Pilih Opsi...',
    'searchable' => false,
    'autoSubmit' => false,
    'onChange' => '',
    'size' => 'default', // 'sm', 'default', 'lg'
    'variant' => 'dark', // 'dark', 'glass', 'table'
    'class' => '',
    'buttonClass' => '',
    'menuClass' => '',
    'id' => null,
])

@php
    $id = $id ?? 'dropdown_' . ($name ?: uniqid()) . '_' . rand(100, 999);
    
    // Normalize options into standard array: [['value' => ..., 'label' => ...]]
    $normalizedOptions = [];
    foreach ($options as $key => $opt) {
        if (is_array($opt)) {
            $val = (string) ($opt['value'] ?? $key);
            $lbl = (string) ($opt['label'] ?? $val);
            $cls = $opt['class'] ?? '';
        } else {
            $val = is_numeric($key) && !array_key_exists((string)$opt, $options) ? (string)$opt : (string)$key;
            $lbl = (string)$opt;
            $cls = '';
        }
        $normalizedOptions[] = [
            'value' => $val,
            'label' => $lbl,
            'class' => $cls,
        ];
    }

    $currentVal = (string) old($name, $value ?? '');
    $selectedOption = collect($normalizedOptions)->firstWhere('value', $currentVal) ?? [
        'value' => $currentVal,
        'label' => $placeholder,
    ];
@endphp

<div x-data="{
    open: false,
    selectedValue: '{{ addslashes($currentVal) }}',
    selectedLabel: '{{ addslashes($selectedOption['label'] ?? $placeholder) }}',
    searchQuery: '',
    options: @js($normalizedOptions),
    autoSubmit: {{ $autoSubmit ? 'true' : 'false' }},
    get filteredOptions() {
        if (!this.searchQuery.trim()) return this.options;
        const q = this.searchQuery.toLowerCase();
        return this.options.filter(o => o.label.toLowerCase().includes(q));
    },
    selectOption(opt) {
        this.selectedValue = opt.value;
        this.selectedLabel = opt.label;
        this.open = false;
        this.searchQuery = '';
        
        $nextTick(() => {
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.value = opt.value;
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            @if($onChange)
                {!! $onChange !!}
            @endif
            if (this.autoSubmit && this.$refs.hiddenInput && this.$refs.hiddenInput.form) {
                this.$refs.hiddenInput.form.submit();
            }
        });
    },
    init() {
        const found = this.options.find(o => String(o.value) === String(this.selectedValue));
        if (found) {
            this.selectedLabel = found.label;
        }
    }
}" 
class="relative block text-left w-full {{ $class }}"
:class="{ 'z-50': open, 'z-10': !open }"
@click.outside="open = false"
@keydown.escape.window="open = false">

    <!-- Hidden Native Form Input for Standard Submissions -->
    @if($name)
        <input type="hidden" name="{{ $name }}" x-ref="hiddenInput" :value="selectedValue">
    @endif

    <!-- Trigger Button -->
    <button type="button" 
            @click="open = !open" 
            :aria-expanded="open"
            class="group w-full min-w-0 flex items-center justify-between gap-2 transition-all duration-200 cursor-pointer focus:outline-none 
            @if($variant === 'glass')
                bg-dark-950/70 hover:bg-dark-900/80 text-white border border-white/10 hover:border-white/20 backdrop-blur-xl shadow-inner
            @elseif($variant === 'table')
                bg-zinc-950/80 hover:bg-zinc-900 text-zinc-200 border border-white/10 hover:border-amber-500/40 shadow-sm
            @else
                bg-zinc-900 hover:bg-zinc-800/90 text-white border border-white/10 hover:border-zinc-700 shadow-md
            @endif
            @if($size === 'sm')
                px-2.5 py-1.5 rounded-xl text-[11px] font-bold
            @elseif($size === 'lg')
                px-4 py-3 rounded-2xl text-sm font-semibold
            @else
                px-3 py-2 rounded-2xl text-xs font-semibold
            @endif
            {{ $buttonClass }}"
            :class="{ 'ring-2 ring-amber-500/30 border-amber-500/50': open }">
        
        <span class="truncate min-w-0 flex items-center gap-1.5" x-text="selectedLabel || '{{ addslashes($placeholder) }}'">
            {{ $selectedOption['label'] ?? $placeholder }}
        </span>

        <!-- Chevron SVG Icon with 180deg flip -->
        <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-300 text-zinc-400 group-hover:text-white"
             :class="open ? 'rotate-180 text-amber-400' : ''"
             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <!-- Floating Dropdown Menu Panel -->
    <div x-show="open" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
         class="absolute left-0 mt-2 z-[60] w-full min-w-[180px] origin-top-left rounded-2xl border border-white/15 bg-zinc-950 p-1.5 shadow-2xl backdrop-blur-2xl ring-1 ring-black/50 {{ $menuClass }}">
        
        <!-- Search Filter Input (Optional) -->
        @if($searchable)
            <div class="p-1.5 border-b border-white/10 mb-1">
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-zinc-900 border border-white/10 focus-within:border-amber-500">
                    <svg class="w-3.5 h-3.5 text-zinc-500 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari..." 
                           class="w-full bg-transparent text-[11px] text-white placeholder-zinc-500 border-none outline-none focus:ring-0 p-0">
                </div>
            </div>
        @endif

        <!-- Options List -->
        <div class="max-h-60 overflow-y-auto space-y-0.5 scrollbar-thin scrollbar-thumb-zinc-700 scrollbar-track-transparent pr-0.5">
            <template x-for="(opt, index) in filteredOptions" :key="index">
                <button type="button" 
                        @click="selectOption(opt)"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-left text-xs transition-all duration-150 cursor-pointer"
                        :class="String(selectedValue) === String(opt.value) 
                            ? 'bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30' 
                            : 'text-zinc-300 hover:bg-white/10 hover:text-white font-medium'">
                    
                    <span class="truncate" x-text="opt.label"></span>

                    <template x-if="String(selectedValue) === String(opt.value)">
                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </template>
                </button>
            </template>

            <!-- Empty Search State -->
            <template x-if="filteredOptions.length === 0">
                <div class="py-3 px-2 text-center text-xs text-zinc-500 italic">
                    Tidak ditemukan
                </div>
            </template>
        </div>

    </div>

</div>
