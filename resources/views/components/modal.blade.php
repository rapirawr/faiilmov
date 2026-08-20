@props([
    'id' => 'modal-' . uniqid(),
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'size' => 'md', // xs, sm, md, lg, xl, 2xl, full
    'variant' => 'default', // default, amber, sky, rose, emerald, purple
    'footer' => null,
])

{{-- Template wrapper for Blade-defined modals that can be opened via window.openModal({ ... }) or data-modal-target="{{ $id }}" --}}
<template id="{{ $id }}-template" style="display: none;">
    <div class="space-y-4">
        {{ $slot }}
    </div>
</template>

<script>
    (function() {
        // Register helper to open this modal from JS
        window['openModal_{{ Str::slug($id, "_") }}'] = function() {
            const template = document.getElementById('{{ $id }}-template');
            if (!template) return;
            
            window.openModal({
                title: @json($title),
                subtitle: @json($subtitle),
                icon: @json($icon),
                size: @json($size),
                variant: @json($variant),
                htmlContent: template.innerHTML,
            });
        };

        // Attach to click triggers matching data-modal-target="{{ $id }}"
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('[data-modal-target="{{ $id }}"]');
            if (trigger) {
                e.preventDefault();
                window['openModal_{{ Str::slug($id, "_") }}']();
            }
        });
    })();
</script>
