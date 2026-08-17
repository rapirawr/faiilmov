@props(['section' => 'all'])

@php
    $currentPage = 'all';
    if (request()->routeIs('home') || request()->is('/')) {
        $currentPage = 'home';
    } elseif (request()->routeIs('film.watch') || request()->routeIs('watch_party.room')) {
        $currentPage = 'watch';
    } elseif (request()->routeIs('film.show')) {
        $currentPage = 'detail';
    }

    $elementsData = app(\App\Services\PageElementService::class)->getActiveElementsForPage($currentPage, request()->path());
    $topBars = array_map(fn($i) => is_array($i) ? (object)$i : $i, $elementsData['top_bars'] ?? []);
    $bottomBars = array_map(fn($i) => is_array($i) ? (object)$i : $i, $elementsData['bottom_bars'] ?? []);
    $floatingWidgets = array_map(fn($i) => is_array($i) ? (object)$i : $i, $elementsData['floating_widgets'] ?? []);
    $popupModals = array_map(fn($i) => is_array($i) ? (object)$i : $i, $elementsData['popup_modals'] ?? []);
    $customBlocks = array_map(fn($i) => is_array($i) ? (object)$i : $i, $elementsData['custom_blocks'] ?? []);
@endphp

<div x-data="pageElementsRenderer()">

    @if(($section === 'top_bars' || $section === 'all') && count($topBars) > 0)
        <!-- ==================== TOP BROADCAST BARS ==================== -->
        <div class="w-full">
            @foreach($topBars as $bar)
                <div x-show="!isDismissed({{ $bar->id }})" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="-translate-y-full opacity-0"
                     x-transition:enter-end="translate-y-0 opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0 opacity-100"
                     x-transition:leave-end="-translate-y-full opacity-0"
                     class="w-full relative px-4 py-2 text-xs flex items-center justify-between gap-3 shadow-md border-b
                        {{ $bar->theme_color === 'amber' ? 'bg-amber-500/15 border-amber-500/30 text-amber-300' : '' }}
                        {{ $bar->theme_color === 'blue' ? 'bg-blue-600/20 border-blue-500/30 text-blue-300' : '' }}
                        {{ $bar->theme_color === 'emerald' ? 'bg-emerald-500/20 border-emerald-500/30 text-emerald-300' : '' }}
                        {{ $bar->theme_color === 'rose' ? 'bg-rose-500/20 border-rose-500/30 text-rose-300' : '' }}
                        {{ $bar->theme_color === 'purple' ? 'bg-purple-600/25 border-purple-500/30 text-purple-200' : '' }}
                        {{ $bar->theme_color === 'zinc' ? 'bg-zinc-900 border-zinc-750 text-zinc-200' : '' }}
                        {{ $bar->target_device === 'desktop' ? 'hidden md:flex' : '' }}
                        {{ $bar->target_device === 'mobile' ? 'flex md:hidden' : '' }}"
                     style="display: none;">
                    
                    <div class="max-w-7xl mx-auto w-full flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <i data-lucide="{{ $bar->icon ?: 'bell' }}" class="w-4 h-4 shrink-0 text-amber-400"></i>
                            <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                @if($bar->title)
                                    <strong class="font-bold text-white truncate font-['Outfit']">{{ $bar->title }}</strong>
                                @endif
                                @if($bar->content)
                                    <span class="opacity-90 truncate max-w-md sm:max-w-xl text-zinc-200">{{ $bar->content }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 shrink-0">
                            @if($bar->button_text && $bar->button_url)
                                <a href="{{ $bar->button_url }}" 
                                   target="{{ $bar->button_target ?: '_self' }}" 
                                   class="px-3 py-1 rounded-lg text-xs font-bold transition-transform hover:scale-105 active:scale-95 shadow-sm
                                        {{ $bar->theme_color === 'amber' ? 'bg-amber-500 hover:bg-amber-400 text-zinc-950' : 'bg-white hover:bg-zinc-200 text-zinc-950' }}">
                                    {{ $bar->button_text }}
                                </a>
                            @endif

                            @if($bar->is_dismissible)
                                <button type="button" 
                                        @click="dismissElement({{ $bar->id }}, {{ $bar->dismiss_duration_hours }})" 
                                        class="p-1 rounded-md opacity-70 hover:opacity-100 hover:bg-white/10 transition-all cursor-pointer text-zinc-300"
                                        title="Tutup">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($section === 'body' || $section === 'all')
        <!-- ==================== FLOATING ACTION WIDGETS ==================== -->
        @foreach($floatingWidgets as $widget)
            @php
                $posClass = match($widget->position ?? 'bottom_right') {
                    'bottom_left'  => 'left-5 bottom-6 items-start lg:left-68',
                    'top_right'    => 'right-5 top-24 items-end',
                    'top_left'     => 'left-5 top-24 items-start lg:left-68',
                    'center_right' => 'right-0 top-1/2 -translate-y-1/2 items-end pr-2',
                    'center_left'  => 'left-0 top-1/2 -translate-y-1/2 items-start pl-2 lg:left-68',
                    default        => 'right-5 bottom-6 items-end',
                };
            @endphp
            <div x-show="!isDismissed({{ $widget->id }})"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="scale-75 opacity-0 translate-y-4"
                 x-transition:enter-end="scale-100 opacity-100 translate-y-0"
                 x-data="{ showTooltip: false }"
                 class="fixed z-50 flex flex-col gap-2 {{ $posClass }}
                    {{ $widget->target_device === 'desktop' ? 'hidden md:flex' : '' }}
                    {{ $widget->target_device === 'mobile' ? 'flex md:hidden' : '' }}"
                 style="display: none;">
                
                <!-- Tooltip Bubble on Hover / Idle -->
                @if($widget->content)
                    <div x-show="showTooltip" 
                         x-transition
                         class="px-3.5 py-2 rounded-2xl bg-zinc-950/95 border border-white/15 text-xs text-[#E4E2DD] shadow-2xl backdrop-blur-md max-w-xs space-y-0.5"
                         style="display: none;">
                        @if($widget->title)
                            <strong class="block font-bold text-amber-400 font-['Outfit']">{{ $widget->title }}</strong>
                        @endif
                        <p class="text-[11px] text-zinc-300 leading-snug">{{ $widget->content }}</p>
                    </div>
                @endif

                <!-- Floating Button Body -->
                <div class="flex items-center gap-1.5">
                    <a href="{{ $widget->button_url ?: '#' }}" 
                       target="{{ $widget->button_target ?: '_self' }}"
                       @mouseenter="showTooltip = true"
                       @mouseleave="showTooltip = false"
                       class="px-4 py-2.5 rounded-full font-bold text-xs flex items-center gap-2.5 shadow-2xl transition-all hover:scale-105 active:scale-95 border cursor-pointer group
                            {{ $widget->theme_color === 'amber' ? 'bg-amber-500 hover:bg-amber-400 text-zinc-950 border-amber-400/50 shadow-amber-500/25' : '' }}
                            {{ $widget->theme_color === 'blue' ? 'bg-blue-600 hover:bg-blue-500 text-white border-blue-400/50 shadow-blue-500/25' : '' }}
                            {{ $widget->theme_color === 'emerald' ? 'bg-emerald-500 hover:bg-emerald-400 text-zinc-950 border-emerald-400/50 shadow-emerald-500/25' : '' }}
                            {{ $widget->theme_color === 'rose' ? 'bg-rose-600 hover:bg-rose-500 text-white border-rose-400/50 shadow-rose-500/25' : '' }}
                            {{ $widget->theme_color === 'purple' ? 'bg-purple-600 hover:bg-purple-500 text-white border-purple-400/50 shadow-purple-500/25' : '' }}
                            {{ $widget->theme_color === 'zinc' ? 'bg-zinc-800 hover:bg-zinc-700 text-[#E4E2DD] border-zinc-700 shadow-black/40' : '' }}">
                        
                        <i data-lucide="{{ $widget->icon ?: 'send' }}" class="w-4 h-4 group-hover:rotate-12 transition-transform"></i>
                        <span>{{ $widget->button_text ?: ($widget->title ?: 'Action') }}</span>
                    </a>

                    @if($widget->is_dismissible)
                        <button type="button" 
                                @click="dismissElement({{ $widget->id }}, {{ $widget->dismiss_duration_hours }})"
                                class="w-6 h-6 rounded-full bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white border border-white/10 flex items-center justify-center transition-colors cursor-pointer shadow-md"
                                title="Tutup Widget">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- ==================== STICKY BOTTOM BROADCAST BARS ==================== -->
        @foreach($bottomBars as $bar)
            <div x-show="!isDismissed({{ $bar->id }})" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0 opacity-100"
                 x-transition:leave-end="translate-y-full opacity-0"
                 class="fixed bottom-0 left-0 right-0 z-40 px-4 py-2.5 text-xs flex items-center justify-between gap-3 shadow-2xl border-t backdrop-blur-xl
                    {{ $bar->theme_color === 'amber' ? 'bg-zinc-950/95 border-amber-500/30 text-amber-300' : '' }}
                    {{ $bar->theme_color === 'blue' ? 'bg-zinc-950/95 border-blue-500/30 text-blue-300' : '' }}
                    {{ $bar->theme_color === 'emerald' ? 'bg-zinc-950/95 border-emerald-500/30 text-emerald-300' : '' }}
                    {{ $bar->theme_color === 'rose' ? 'bg-zinc-950/95 border-rose-500/30 text-rose-300' : '' }}
                    {{ $bar->theme_color === 'purple' ? 'bg-zinc-950/95 border-purple-500/30 text-purple-200' : '' }}
                    {{ $bar->theme_color === 'zinc' ? 'bg-zinc-950/95 border-zinc-750 text-zinc-200' : '' }}
                    {{ $bar->target_device === 'desktop' ? 'hidden md:flex' : '' }}
                    {{ $bar->target_device === 'mobile' ? 'flex md:hidden' : '' }}"
                 style="display: none;">
                
                <div class="max-w-7xl mx-auto w-full flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <i data-lucide="{{ $bar->icon ?: 'bell' }}" class="w-4 h-4 shrink-0 text-amber-400"></i>
                        <div class="flex items-center gap-2 min-w-0 flex-wrap">
                            @if($bar->title)
                                <strong class="font-bold text-white truncate font-['Outfit']">{{ $bar->title }}</strong>
                            @endif
                            @if($bar->content)
                                <span class="opacity-90 truncate max-w-md sm:max-w-xl text-zinc-200">{{ $bar->content }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        @if($bar->button_text && $bar->button_url)
                            <a href="{{ $bar->button_url }}" 
                               target="{{ $bar->button_target ?: '_self' }}" 
                               class="px-3 py-1 rounded-lg text-xs font-bold transition-transform hover:scale-105 active:scale-95 shadow-sm
                                    {{ $bar->theme_color === 'amber' ? 'bg-amber-500 hover:bg-amber-400 text-zinc-950' : 'bg-white hover:bg-zinc-200 text-zinc-950' }}">
                                {{ $bar->button_text }}
                            </a>
                        @endif

                        @if($bar->is_dismissible)
                            <button type="button" 
                                    @click="dismissElement({{ $bar->id }}, {{ $bar->dismiss_duration_hours }})" 
                                    class="p-1 rounded-md opacity-70 hover:opacity-100 hover:bg-white/10 transition-all cursor-pointer text-zinc-300"
                                    title="Tutup">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <!-- ==================== POPUP / MODAL DIALOGS ==================== -->
        @foreach($popupModals as $modal)
            @php
                $modalPosWrapper = match($modal->position ?? 'center') {
                    'bottom_right' => 'fixed bottom-6 right-6 z-50 p-4 max-w-sm w-full',
                    'bottom_left'  => 'fixed bottom-6 left-6 z-50 p-4 max-w-sm w-full lg:left-70',
                    default        => 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md',
                };
            @endphp
            <div x-show="!isDismissed({{ $modal->id }}) && showModalMap[{{ $modal->id }}]"
                 x-transition.opacity
                 class="{{ $modalPosWrapper }}"
                 style="display: none;">
                
                <div @click.outside="dismissElement({{ $modal->id }}, {{ $modal->dismiss_duration_hours }})"
                     class="w-full max-w-md rounded-3xl bg-zinc-900 border border-white/15 shadow-2xl overflow-hidden animate-scale-up">
                    
                    @if($modal->image_url)
                        <div class="w-full h-44 overflow-hidden relative border-b border-white/10">
                            <img src="{{ $modal->image_url }}" alt="{{ $modal->title }}" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-transparent to-black/30"></div>
                            <button type="button" 
                                    @click="dismissElement({{ $modal->id }}, {{ $modal->dismiss_duration_hours }})"
                                    class="absolute top-3 right-3 p-1.5 rounded-full bg-black/60 hover:bg-black text-white/80 hover:text-white transition-colors cursor-pointer">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    @endif

                    <div class="p-6 space-y-4">
                        @unless($modal->image_url)
                            <div class="flex items-center justify-between">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                                    <i data-lucide="{{ $modal->icon ?: 'sparkles' }}" class="w-5 h-5"></i>
                                </div>
                                <button type="button" 
                                        @click="dismissElement({{ $modal->id }}, {{ $modal->dismiss_duration_hours }})"
                                        class="p-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors cursor-pointer">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @endunless

                        <div class="space-y-1.5">
                            <h3 class="text-base font-bold text-[#E4E2DD] font-['Outfit']">{{ $modal->title }}</h3>
                            @if($modal->content)
                                <p class="text-xs text-zinc-400 leading-relaxed">{{ $modal->content }}</p>
                            @endif
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2.5">
                            @if($modal->is_dismissible)
                                <button type="button" 
                                        @click="dismissElement({{ $modal->id }}, {{ $modal->dismiss_duration_hours }})"
                                        class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-750 text-zinc-300 font-semibold text-xs transition-colors cursor-pointer">
                                    Tutup
                                </button>
                            @endif

                            @if($modal->button_text && $modal->button_url)
                                <a href="{{ $modal->button_url }}" 
                                   target="{{ $modal->button_target ?: '_self' }}"
                                   @click="dismissElement({{ $modal->id }}, {{ $modal->dismiss_duration_hours }})"
                                   class="px-5 py-2 rounded-xl font-bold text-xs shadow-lg transition-transform hover:scale-105 active:scale-95
                                        {{ $modal->theme_color === 'amber' ? 'bg-amber-500 hover:bg-amber-400 text-zinc-950 shadow-amber-500/20' : 'bg-white hover:bg-zinc-200 text-zinc-950' }}">
                                    {{ $modal->button_text }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    @if(($section === 'content_blocks' || $section === 'all' || $section === 'custom_blocks') && count($customBlocks) > 0)
        <!-- ==================== CUSTOM HTML / IFRAME / EMBED BLOCKS ==================== -->
        @foreach($customBlocks as $block)
            @php
                $blockWrapperClass = match($block->position ?? 'content_top') {
                    'floating_bottom_right' => 'fixed bottom-6 right-6 z-40 max-w-md w-full p-2',
                    'floating_bottom_left'  => 'fixed bottom-6 left-6 z-40 max-w-md w-full p-2 lg:left-70',
                    default                 => 'w-full relative my-4 max-w-5xl mx-auto px-4',
                };
            @endphp
            <div x-show="!isDismissed({{ $block->id }})"
                 class="{{ $blockWrapperClass }}
                    {{ $block->target_device === 'desktop' ? 'hidden md:block' : '' }}
                    {{ $block->target_device === 'mobile' ? 'block md:hidden' : '' }}">
                @if($block->is_dismissible)
                    <div class="flex justify-end mb-1">
                        <button type="button" 
                                @click="dismissElement({{ $block->id }}, {{ $block->dismiss_duration_hours }})" 
                                class="px-2.5 py-1 rounded-lg text-zinc-400 hover:text-white bg-zinc-900/80 hover:bg-zinc-800 border border-white/10 transition-all text-[11px] flex items-center gap-1 cursor-pointer shadow-sm">
                            <i data-lucide="x" class="w-3 h-3"></i>
                            <span>Tutup</span>
                        </button>
                    </div>
                @endif
                <div class="w-full overflow-hidden rounded-2xl border border-white/10 bg-zinc-950/60 shadow-xl">
                    {!! $block->custom_html !!}
                </div>
            </div>
        @endforeach
    @endif

</div>

<script>
    if (typeof window.pageElementsRenderer !== 'function') {
        window.pageElementsRenderer = function() {
            return {
                showModalMap: {},
                dismissedInPageMap: {},

                init() {
                    @foreach($popupModals as $modal)
                        if (!this.isDismissed({{ $modal->id }})) {
                            setTimeout(() => {
                                this.showModalMap[{{ $modal->id }}] = true;
                            }, 1200);
                        }
                    @endforeach
                },

                isDismissed(id) {
                    if (this.dismissedInPageMap && this.dismissedInPageMap[id]) {
                        return true;
                    }
                    try {
                        const expiry = localStorage.getItem('fe_dismiss_' + id);
                        if (!expiry) return false;
                        if (expiry === 'forever') return true;
                        return Number(expiry) > Date.now();
                    } catch (e) {
                        return false;
                    }
                },

                dismissElement(id, durationHours) {
                    // Reactive in-page dismiss (immediately hides in the current view)
                    this.dismissedInPageMap[id] = true;
                    this.showModalMap[id] = false;

                    try {
                        const hours = Number(durationHours);
                        if (hours === -1) {
                            localStorage.setItem('fe_dismiss_' + id, 'forever');
                        } else if (hours > 0) {
                            const expiry = Date.now() + (hours * 3600 * 1000);
                            localStorage.setItem('fe_dismiss_' + id, expiry.toString());
                        } else {
                            // durationHours === 0: Temporary dismiss for current view only.
                            // Will reappear automatically when page is reloaded.
                            localStorage.removeItem('fe_dismiss_' + id);
                            sessionStorage.removeItem('fe_dismiss_' + id);
                        }
                    } catch (e) {}
                }
            };
        };
    }
</script>
