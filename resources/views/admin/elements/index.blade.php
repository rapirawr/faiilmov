@extends('layouts.admin')

@section('title', 'Visual Element & Widget Studio | faiiladmin')
@section('page_title', 'Visual Element & Widget Studio')

@section('content')
<div x-data="elementStudio({{ json_encode($elements) }}, {{ json_encode($presets) }}, '{{ csrf_token() }}')" class="space-y-6">

    <!-- Header & Hero Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-zinc-900 border border-white/10 shadow-xl backdrop-blur-md">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0 shadow-inner">
                <i data-lucide="layout-template" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-lg font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">
                        Visual Element & Widget Studio
                    </h1>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30">
                        CMS v2.0
                    </span>
                </div>
                <p class="text-xs text-zinc-400 mt-0.5">
                    Buat, pasang, dan atur penargetan banner broadcast, floating widget, popup, dan blok kustom di seluruh halaman.
                </p>
            </div>
        </div>

        <!-- Action CTAs -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Preset Templates Dropdown -->
            <div class="relative" @click.outside="presetDropdownOpen = false">
                <button type="button" 
                        @click="presetDropdownOpen = !presetDropdownOpen"
                        class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-750 text-zinc-200 border border-white/10 text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer shadow-sm">
                    <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
                    <span>Template Bawaan</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-zinc-400 transition-transform" :class="presetDropdownOpen ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="presetDropdownOpen" 
                     x-transition
                     class="absolute right-0 mt-2 w-72 bg-zinc-900 border border-white/15 rounded-2xl shadow-2xl p-1.5 z-40 space-y-1"
                     style="display: none;">
                    <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">Pilih Template Siap Pakai</div>
                    <template x-for="preset in presets" :key="preset.id">
                        <button type="button" 
                                @click="applyPreset(preset)" 
                                class="w-full text-left p-2.5 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3 cursor-pointer group">
                            <div class="w-7 h-7 rounded-lg bg-zinc-800 border border-zinc-700 flex items-center justify-center text-amber-400 group-hover:scale-105 transition-transform shrink-0">
                                <i :data-lucide="preset.icon || 'star'" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-xs font-bold text-[#E4E2DD] truncate" x-text="preset.name"></span>
                                <span class="block text-[10px] text-zinc-400 truncate" x-text="getTypeBadge(preset.type).label"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Primary Add Button -->
            <button type="button" 
                    @click="openCreateModal()"
                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Elemen Baru</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-white/10 space-y-1">
            <span class="text-[11px] text-zinc-400 block font-medium">Total Elemen</span>
            <span class="text-xl font-bold font-mono text-[#E4E2DD]">{{ $stats['total'] }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-emerald-500/20 space-y-1">
            <span class="text-[11px] text-emerald-400 block font-medium">Elemen Aktif</span>
            <span class="text-xl font-bold font-mono text-emerald-300">{{ $stats['active'] }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-white/10 space-y-1">
            <span class="text-[11px] text-zinc-400 block font-medium">Broadcast Bar</span>
            <span class="text-xl font-bold font-mono text-[#E4E2DD]">{{ $stats['broadcast_bars'] }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-white/10 space-y-1">
            <span class="text-[11px] text-zinc-400 block font-medium">Floating Widget</span>
            <span class="text-xl font-bold font-mono text-[#E4E2DD]">{{ $stats['floating_widgets'] }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-white/10 space-y-1">
            <span class="text-[11px] text-zinc-400 block font-medium">Popup Modal</span>
            <span class="text-xl font-bold font-mono text-[#E4E2DD]">{{ $stats['popup_modals'] }}</span>
        </div>
        <div class="p-3.5 rounded-2xl bg-zinc-900/80 border border-white/10 space-y-1">
            <span class="text-[11px] text-zinc-400 block font-medium">Promo & Custom</span>
            <span class="text-xl font-bold font-mono text-[#E4E2DD]">{{ $stats['promo_banners'] + $stats['custom_blocks'] }}</span>
        </div>
    </div>

    <!-- Filter Bar & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-2xl bg-zinc-900/60 border border-white/10">
        <!-- Type Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs">
            <a href="{{ route('admin.page_elements.index', ['type' => 'all', 'status' => $statusFilter]) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $typeFilter === 'all' ? 'bg-amber-500 text-zinc-950 shadow-md' : 'bg-zinc-800/80 text-zinc-400 hover:text-white' }}">
                Semua ({{ $stats['total'] }})
            </a>
            <a href="{{ route('admin.page_elements.index', ['type' => 'broadcast_bar', 'status' => $statusFilter]) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $typeFilter === 'broadcast_bar' ? 'bg-amber-500 text-zinc-950 shadow-md' : 'bg-zinc-800/80 text-zinc-400 hover:text-white' }}">
                Broadcast Bar ({{ $stats['broadcast_bars'] }})
            </a>
            <a href="{{ route('admin.page_elements.index', ['type' => 'floating_widget', 'status' => $statusFilter]) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $typeFilter === 'floating_widget' ? 'bg-amber-500 text-zinc-950 shadow-md' : 'bg-zinc-800/80 text-zinc-400 hover:text-white' }}">
                Floating Widget ({{ $stats['floating_widgets'] }})
            </a>
            <a href="{{ route('admin.page_elements.index', ['type' => 'popup_modal', 'status' => $statusFilter]) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $typeFilter === 'popup_modal' ? 'bg-amber-500 text-zinc-950 shadow-md' : 'bg-zinc-800/80 text-zinc-400 hover:text-white' }}">
                Popup Modal ({{ $stats['popup_modals'] }})
            </a>
            <a href="{{ route('admin.page_elements.index', ['type' => 'promo_banner', 'status' => $statusFilter]) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $typeFilter === 'promo_banner' ? 'bg-amber-500 text-zinc-950 shadow-md' : 'bg-zinc-800/80 text-zinc-400 hover:text-white' }}">
                Promo Card ({{ $stats['promo_banners'] }})
            </a>
            <a href="{{ route('admin.page_elements.index', ['type' => 'custom_block', 'status' => $statusFilter]) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ $typeFilter === 'custom_block' ? 'bg-amber-500 text-zinc-950 shadow-md' : 'bg-zinc-800/80 text-zinc-400 hover:text-white' }}">
                Custom HTML ({{ $stats['custom_blocks'] }})
            </a>
        </div>

        <!-- Search input -->
        <form method="GET" action="{{ route('admin.page_elements.index') }}" class="relative shrink-0">
            <input type="hidden" name="type" value="{{ $typeFilter }}">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" 
                   name="search" 
                   value="{{ $search }}" 
                   placeholder="Cari elemen..." 
                   class="w-full sm:w-52 pl-8 pr-3 py-1.5 text-xs rounded-xl bg-zinc-950 border border-white/10 text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-amber-500/50">
        </form>
    </div>

    <!-- Element Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($elements as $element)
            <div class="rounded-2xl bg-zinc-900/90 border border-white/10 p-5 shadow-lg space-y-4 hover:border-white/20 transition-all flex flex-col justify-between"
                 :class="{ 'opacity-60 border-zinc-800': !elementsState[{{ $element->id }}]?.is_active }">
                
                <div class="space-y-3">
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border
                                {{ $element->theme_color === 'amber' ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : '' }}
                                {{ $element->theme_color === 'blue' ? 'bg-blue-500/10 border-blue-500/30 text-blue-400' : '' }}
                                {{ $element->theme_color === 'emerald' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : '' }}
                                {{ $element->theme_color === 'rose' ? 'bg-rose-500/10 border-rose-500/30 text-rose-400' : '' }}
                                {{ $element->theme_color === 'purple' ? 'bg-purple-500/10 border-purple-500/30 text-purple-400' : '' }}
                                {{ $element->theme_color === 'zinc' ? 'bg-zinc-800 border-zinc-700 text-zinc-300' : '' }}">
                                <i data-lucide="{{ $element->icon ?: 'layout' }}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-[#E4E2DD] truncate font-['Outfit']">
                                    {{ $element->name }}
                                </h3>
                                <span class="text-[10px] font-mono text-zinc-400 block">
                                    {{ $element->type_label }} &bull; Posisi: {{ ucfirst(str_replace('_', ' ', $element->position)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Active Toggle Switch -->
                        <button type="button" 
                                @click="toggleStatus({{ $element->id }})"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="elementsState[{{ $element->id }}]?.is_active ? 'bg-emerald-500' : 'bg-zinc-800'">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                  :class="elementsState[{{ $element->id }}]?.is_active ? 'translate-x-4' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <!-- Visual Preview Thumbnail/Card -->
                    <div class="p-3 rounded-xl bg-zinc-950 border border-white/5 space-y-2 text-xs">
                        @if($element->title)
                            <div class="font-bold text-[#E4E2DD] text-xs flex items-center gap-1.5 truncate">
                                <span>{{ $element->title }}</span>
                            </div>
                        @endif

                        @if($element->content)
                            <p class="text-zinc-400 text-[11px] line-clamp-2 leading-relaxed">
                                {{ $element->content }}
                            </p>
                        @endif

                        @if($element->button_text)
                            <div class="pt-1 flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border flex items-center gap-1
                                    {{ $element->theme_color === 'amber' ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-zinc-800 border-zinc-700 text-zinc-200' }}">
                                    <span>{{ $element->button_text }}</span>
                                    <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                                </span>
                                @if($element->button_url)
                                    <span class="text-[10px] text-zinc-500 font-mono truncate max-w-[140px]">{{ $element->button_url }}</span>
                                @endif
                            </div>
                        @endif

                        @if($element->image_url)
                            <div class="w-full h-20 rounded-lg overflow-hidden border border-white/10 relative">
                                <img src="{{ $element->image_url }}" alt="Preview" class="w-full h-full object-cover">
                            </div>
                        @endif
                    </div>

                    <!-- Targeting Badges -->
                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-mono text-zinc-400">
                        <span class="px-2 py-0.5 rounded-md bg-zinc-800 border border-white/5 flex items-center gap-1">
                            <i data-lucide="globe" class="w-3 h-3 text-zinc-400"></i>
                            <span>{{ $element->target_page === 'all' ? 'Seluruh Web' : ucfirst($element->target_page) }}</span>
                        </span>

                        <span class="px-2 py-0.5 rounded-md bg-zinc-800 border border-white/5 flex items-center gap-1">
                            <i data-lucide="{{ $element->target_device === 'mobile' ? 'smartphone' : ($element->target_device === 'desktop' ? 'monitor' : 'layers') }}" class="w-3 h-3 text-zinc-400"></i>
                            <span>{{ ucfirst($element->target_device) }}</span>
                        </span>

                        <span class="px-2 py-0.5 rounded-md bg-zinc-800 border border-white/5 flex items-center gap-1">
                            <i data-lucide="users" class="w-3 h-3 text-zinc-400"></i>
                            <span>{{ $element->target_audience === 'guest' ? 'Guest Only' : ($element->target_audience === 'user' ? 'Member Only' : 'Semua User') }}</span>
                        </span>

                        @if($element->is_dismissible)
                            <span class="px-2 py-0.5 rounded-md bg-zinc-800/60 border border-white/5 text-zinc-500">
                                Dismiss {{ $element->dismiss_duration_hours === -1 ? 'Forever' : ($element->dismiss_duration_hours === 0 ? 'Muncul saat reload' : $element->dismiss_duration_hours.'h') }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-3 border-t border-white/5 flex items-center justify-between gap-2">
                    <span class="text-[10px] font-mono text-zinc-500">
                        Diperbarui {{ $element->updated_at->diffForHumans() }}
                    </span>

                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="openEditModal({{ json_encode($element) }})"
                                class="px-2.5 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-white/10 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span>Edit</span>
                        </button>

                        <form action="{{ route('admin.page_elements.destroy', $element->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus elemen ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="p-1.5 rounded-lg bg-zinc-800 hover:bg-rose-500/20 text-zinc-400 hover:text-rose-400 border border-white/10 transition-colors cursor-pointer"
                                    title="Hapus Elemen">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full py-16 text-center rounded-2xl bg-zinc-900/40 border border-white/5 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-500 mx-auto">
                    <i data-lucide="layout-template" class="w-6 h-6"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-[#E4E2DD]">Belum ada elemen yang dibuat</h3>
                    <p class="text-xs text-zinc-400 max-w-sm mx-auto">
                        Mulai buat banner pengumuman, tombol melayang Telegram/Saweria, atau modal pop-up dengan template bawaan.
                    </p>
                </div>
                <button type="button" 
                        @click="openCreateModal()" 
                        class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs inline-flex items-center gap-2 shadow-lg shadow-amber-500/20 cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Buat Elemen Pertama</span>
                </button>
            </div>
        @endforelse
    </div>

    <!-- ==================== VISUAL STUDIO FORM & LIVE PREVIEW MODAL ==================== -->
    <div x-show="modalOpen" 
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/80 backdrop-blur-md overflow-y-auto"
         style="display: none;">
        
        <div @click.outside="modalOpen = false" 
             class="w-full max-w-5xl rounded-3xl bg-zinc-900 border border-white/15 shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-white/10 flex items-center justify-between bg-zinc-950/60">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#E4E2DD] font-['Outfit']" x-text="isEdit ? 'Edit Konfigurasi Elemen' : 'Buat Elemen Baru (Visual Studio)'"></h2>
                        <p class="text-xs text-zinc-400">Atur konten, penargetan halaman, serta tinjau live preview sebelum disimpan.</p>
                    </div>
                </div>

                <button type="button" @click="modalOpen = false" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body (2-Column Studio: Form & Live Preview) -->
            <form :action="formAction" method="POST" class="flex-1 overflow-y-auto p-5 grid grid-cols-1 lg:grid-cols-12 gap-6">
                <input type="hidden" name="_token" :value="csrfToken">
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Left Column (Form Inputs - 7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <!-- Tipe Elemen & Nama -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1">Tipe Elemen <span class="text-amber-400">*</span></label>
                            <select name="type" x-model="formData.type" class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-white/15 text-xs text-[#E4E2DD] focus:border-amber-500 focus:outline-none">
                                <option value="broadcast_bar">Announcement / Broadcast Bar</option>
                                <option value="floating_widget">Floating Action Widget</option>
                                <option value="popup_modal">Popup / Modal Pengumuman</option>
                                <option value="promo_banner">Promo / Highlight Card</option>
                                <option value="custom_block">Custom HTML / Embed Block</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1">Nama Internal <span class="text-amber-400">*</span></label>
                            <input type="text" name="name" x-model="formData.name" placeholder="Misal: Banner Telegram 2026" required
                                   class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-white/15 text-xs text-[#E4E2DD] placeholder-zinc-500 focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Judul & Ikon -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-zinc-300 mb-1">Judul Teks / Headline</label>
                            <input type="text" name="title" x-model="formData.title" placeholder="Misal: Gabung Komunitas Telegram"
                                   class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-white/15 text-xs text-[#E4E2DD] placeholder-zinc-500 focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1 flex items-center justify-between">
                                <span>Ikon Lucide</span>
                                <span class="text-[10px] text-amber-400 font-normal">Klik ganti</span>
                            </label>
                            <input type="hidden" name="icon" x-model="formData.icon">
                            <button type="button" @click="openIconPicker()" class="w-full bg-zinc-950 border border-white/15 hover:border-amber-400/60 rounded-xl px-3 py-2 text-xs text-[#E4E2DD] flex items-center justify-between gap-2 transition-all cursor-pointer group shadow-sm">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-6 h-6 rounded-lg bg-amber-500/15 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0" x-html="getIconSvg(formData.icon || 'sparkles')"></span>
                                    <span class="truncate text-xs font-semibold" x-text="getIconLabel(formData.icon || 'sparkles')"></span>
                                </div>
                                <i data-lucide="palette" class="w-3.5 h-3.5 text-zinc-500 group-hover:text-amber-400 shrink-0 transition-colors"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Deskripsi / Pesan -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1">Isi Konten / Deskripsi Pesan</label>
                        <textarea name="content" x-model="formData.content" rows="2" placeholder="Tuliskan isi informasi pengumuman..."
                                  class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-white/15 text-xs text-[#E4E2DD] placeholder-zinc-500 focus:border-amber-500 focus:outline-none"></textarea>
                    </div>

                    <!-- Tombol Aksi (CTA) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 rounded-2xl bg-zinc-950 border border-white/5">
                        <div>
                            <label class="block text-[11px] font-bold text-zinc-400 mb-1">Teks Tombol Aksi</label>
                            <input type="text" name="button_text" x-model="formData.button_text" placeholder="Misal: Gabung"
                                   class="w-full px-2.5 py-1.5 rounded-lg bg-zinc-900 border border-white/10 text-xs text-[#E4E2DD] placeholder-zinc-500 focus:border-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-zinc-400 mb-1">URL Link Tujuan</label>
                            <input type="text" name="button_url" x-model="formData.button_url" placeholder="https://..."
                                   class="w-full px-2.5 py-1.5 rounded-lg bg-zinc-900 border border-white/10 text-xs text-[#E4E2DD] placeholder-zinc-500 focus:border-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-zinc-400 mb-1">Target Link</label>
                            <select name="button_target" x-model="formData.button_target" class="w-full px-2.5 py-1.5 rounded-lg bg-zinc-900 border border-white/10 text-xs text-[#E4E2DD] focus:border-amber-500 focus:outline-none">
                                <option value="_self">Tab Saat Ini (_self)</option>
                                <option value="_blank">Tab Baru (_blank)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Gambar / Banner & Tema Warna -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-zinc-300 mb-1">Gambar URL (Opsional)</label>
                            <input type="text" name="image_url" x-model="formData.image_url" placeholder="https://images.unsplash.com/..."
                                   class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-white/15 text-xs text-[#E4E2DD] placeholder-zinc-500 focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1">Tema Warna</label>
                            <select name="theme_color" x-model="formData.theme_color" class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-white/15 text-xs text-[#E4E2DD] focus:border-amber-500 focus:outline-none">
                                <option value="amber">Amber Gold</option>
                                <option value="blue">Blue Tech</option>
                                <option value="emerald">Emerald Green</option>
                                <option value="rose">Rose Red</option>
                                <option value="purple">Purple Modern</option>
                                <option value="zinc">Sleek Zinc / Dark</option>
                            </select>
                        </div>
                    </div>

                    <!-- Penargetan Halaman, Perangkat, dan Audiens -->
                    <div class="p-3.5 rounded-2xl bg-zinc-950 border border-white/5 space-y-3">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                            <i data-lucide="target" class="w-3.5 h-3.5"></i>
                            <span>Aturan Penargetan & Tampil</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-zinc-400 mb-1">Halaman Tayang</label>
                                <select name="target_page" x-model="formData.target_page" class="w-full px-2.5 py-1.5 rounded-lg bg-zinc-900 border border-white/10 text-xs text-[#E4E2DD] focus:border-amber-500 focus:outline-none">
                                    <option value="all">Seluruh Website (Global)</option>
                                    <option value="home">Halaman Utama (Homepage)</option>
                                    <option value="watch">Halaman Player (Watch)</option>
                                    <option value="detail">Halaman Detail Film</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-medium text-zinc-400 mb-1">Target Perangkat</label>
                                <select name="target_device" x-model="formData.target_device" class="w-full px-2.5 py-1.5 rounded-lg bg-zinc-900 border border-white/10 text-xs text-[#E4E2DD] focus:border-amber-500 focus:outline-none">
                                    <option value="all">Semua Perangkat</option>
                                    <option value="desktop">Hanya Desktop</option>
                                    <option value="mobile">Hanya Mobile</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-medium text-zinc-400 mb-1">Target Pengunjung</label>
                                <select name="target_audience" x-model="formData.target_audience" class="w-full px-2.5 py-1.5 rounded-lg bg-zinc-900 border border-white/10 text-xs text-[#E4E2DD] focus:border-amber-500 focus:outline-none">
                                    <option value="all">Semua Pengunjung</option>
                                    <option value="guest">Tamu (Belum Login)</option>
                                    <option value="user">Member (Sudah Login)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dismissible controls -->
                        <div class="pt-2 border-t border-white/5 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="is_dismissible" value="1" x-model="formData.is_dismissible" class="rounded bg-zinc-900 border-zinc-700 text-amber-500 focus:ring-0">
                                <span class="text-zinc-300 font-medium">Bisa ditutup pengunjung (Tombol X)</span>
                            </label>

                            <div class="flex items-center gap-2" x-show="formData.is_dismissible">
                                <span class="text-[11px] text-zinc-400">Ingatan Dismiss:</span>
                                <select name="dismiss_duration_hours" x-model="formData.dismiss_duration_hours" class="px-2 py-1 rounded bg-zinc-900 border border-white/10 text-[11px] text-zinc-200">
                                    <option value="0">Tutup Sementara (Muncul lagi saat reload)</option>
                                    <option value="1">1 Jam</option>
                                    <option value="24">1 Hari (24 Jam)</option>
                                    <option value="48">2 Hari (48 Jam)</option>
                                    <option value="-1">Selamanya (Permanen)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Custom HTML input (if type is custom_block) -->
                    <div x-show="formData.type === 'custom_block'" class="space-y-1">
                        <label class="block text-xs font-bold text-zinc-300">Snippet Custom HTML / Iframe</label>
                        <textarea name="custom_html" x-model="formData.custom_html" rows="4" placeholder="<div>...</div>"
                                  class="w-full font-mono text-xs px-3 py-2 rounded-xl bg-zinc-950 border border-white/15 text-amber-300 focus:border-amber-500 focus:outline-none"></textarea>
                    </div>

                    <!-- Toggle Status Aktif -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-950 border border-white/10">
                        <div>
                            <span class="block text-xs font-bold text-zinc-200">Status Aktifkan Elemen</span>
                            <span class="block text-[10px] text-zinc-400">Langsung tayang di website setelah disimpan</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="formData.is_active" class="sr-only peer">
                            <div class="w-9 h-5 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>
                </div>

                <!-- Right Column (Live Interactive Preview Box - 5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="sticky top-0 p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-3">
                        <div class="flex items-center justify-between border-b border-white/10 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-xs font-bold text-[#E4E2DD] font-['Outfit']">Live Real-time Preview</span>
                            </div>
                            <span class="text-[10px] font-mono text-zinc-400" x-text="getTypeBadge(formData.type).label"></span>
                        </div>

                        <!-- Interactive Preview Container -->
                        <div class="min-h-[260px] p-4 rounded-xl bg-zinc-900/90 border border-white/5 flex flex-col justify-center items-center relative overflow-hidden">
                            
                            <!-- 1. Broadcast Bar Preview -->
                            <template x-if="formData.type === 'broadcast_bar'">
                                <div class="w-full p-3 rounded-xl border flex items-center justify-between gap-3 shadow-lg transition-all"
                                     :class="getThemeClasses(formData.theme_color).bar">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <i :data-lucide="formData.icon || 'bell'" class="w-4 h-4 shrink-0"></i>
                                        <div class="min-w-0">
                                            <span class="block text-xs font-bold truncate" x-text="formData.title || 'Pengumuman Penting'"></span>
                                            <span class="block text-[11px] opacity-90 truncate" x-text="formData.content || 'Isi pesan broadcast bar...'"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <template x-if="formData.button_text">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold shadow-sm"
                                                  :class="getThemeClasses(formData.theme_color).btn"
                                                  x-text="formData.button_text"></span>
                                        </template>
                                        <template x-if="formData.is_dismissible">
                                            <i data-lucide="x" class="w-3.5 h-3.5 opacity-60 hover:opacity-100 cursor-pointer"></i>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- 2. Floating Widget Preview -->
                            <template x-if="formData.type === 'floating_widget'">
                                <div class="w-full flex flex-col items-end justify-end space-y-2 py-4">
                                    <div class="p-2.5 rounded-xl bg-zinc-950 border border-white/15 text-xs text-[#E4E2DD] shadow-xl max-w-xs space-y-1">
                                        <span class="font-bold block text-amber-400" x-text="formData.title || 'Gabung Bersama Kami'"></span>
                                        <span class="text-[11px] text-zinc-300 block" x-text="formData.content || 'Klik tombol di bawah untuk membuka tautan.'"></span>
                                    </div>
                                    <div class="px-4 py-2.5 rounded-2xl shadow-xl flex items-center gap-2.5 font-bold text-xs cursor-pointer border"
                                         :class="getThemeClasses(formData.theme_color).widget">
                                        <i :data-lucide="formData.icon || 'send'" class="w-4 h-4"></i>
                                        <span x-text="formData.button_text || formData.title || 'Floating Action'"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- 3. Popup Modal Preview -->
                            <template x-if="formData.type === 'popup_modal'">
                                <div class="w-full max-w-xs rounded-2xl bg-zinc-950 border border-white/15 shadow-2xl overflow-hidden text-xs">
                                    <template x-if="formData.image_url">
                                        <div class="w-full h-24 overflow-hidden">
                                            <img :src="formData.image_url" alt="Preview" class="w-full h-full object-cover">
                                        </div>
                                    </template>
                                    <div class="p-4 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-bold text-sm text-[#E4E2DD]" x-text="formData.title || 'Pengumuman Penting'"></h4>
                                            <i data-lucide="x" class="w-3.5 h-3.5 text-zinc-500"></i>
                                        </div>
                                        <p class="text-zinc-400 text-[11px] leading-relaxed" x-text="formData.content || 'Deskripsi isi pesan modal popup...'"></p>
                                        <div class="pt-2 flex justify-end gap-2">
                                            <span class="px-3 py-1.5 rounded-xl font-bold text-xs"
                                                  :class="getThemeClasses(formData.theme_color).btn"
                                                  x-text="formData.button_text || 'Mengerti'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- 4. Promo Banner Preview -->
                            <template x-if="formData.type === 'promo_banner'">
                                <div class="w-full rounded-2xl border overflow-hidden p-4 space-y-2 relative"
                                     :class="getThemeClasses(formData.theme_color).bar">
                                    <div class="flex items-center gap-2">
                                        <i :data-lucide="formData.icon || 'sparkles'" class="w-4 h-4"></i>
                                        <h4 class="font-bold text-xs" x-text="formData.title || 'Promo Menarik'"></h4>
                                    </div>
                                    <p class="text-[11px] opacity-90" x-text="formData.content || 'Informasi promosi atau highlight...'"></p>
                                    <template x-if="formData.button_text">
                                        <span class="inline-block px-3 py-1 rounded-lg text-[10px] font-bold mt-1"
                                              :class="getThemeClasses(formData.theme_color).btn"
                                              x-text="formData.button_text"></span>
                                    </template>
                                </div>
                            </template>

                            <!-- 5. Custom Block Preview -->
                            <template x-if="formData.type === 'custom_block'">
                                <div class="w-full p-3 rounded-xl bg-zinc-950 border border-amber-500/30 text-amber-300 font-mono text-[10px] overflow-x-auto">
                                    <div class="text-zinc-500 mb-1">// Custom HTML / Embed Container</div>
                                    <div x-text="formData.custom_html || '<div class=\'my-widget\'>Konten custom...</div>'"></div>
                                </div>
                            </template>

                        </div>

                        <!-- Footer Modal Submit Actions -->
                        <div class="pt-3 border-t border-white/10 flex items-center justify-end gap-2.5">
                            <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-750 text-zinc-300 font-semibold text-xs transition-colors cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 cursor-pointer">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span x-text="isEdit ? 'Simpan Perubahan' : 'Terbitkan Elemen'"></span>
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- ==================== VISUAL ICON PICKER MODAL ==================== -->
    <div x-show="iconPickerOpen" 
         x-cloak 
         @click.stop
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/85 backdrop-blur-md p-4 animate-in fade-in duration-200"
         style="display: none;">
        <div @click.stop @click.outside="iconPickerOpen = false" class="w-full max-w-2xl bg-zinc-950 border border-white/15 rounded-3xl p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-3 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
                        <i data-lucide="palette" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#E4E2DD]">Pilih Ikon Elemen</h3>
                        <p class="text-[11px] text-zinc-400">Pilih salah satu ikon di bawah ini untuk ditampilkan pada elemen widget/banner</p>
                    </div>
                </div>
                <button type="button" @click="iconPickerOpen = false" class="p-1.5 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative shrink-0">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" x-model="iconSearchQuery" placeholder="Cari nama ikon (misal: send, film, play, star, flame, heart)..." 
                       class="w-full bg-zinc-900 border border-white/10 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400">
            </div>

            <!-- Icon Categories Filter Tabs -->
            <div class="flex items-center gap-1.5 shrink-0 overflow-x-auto pb-1 no-scrollbar">
                <button type="button" @click="selectedIconCat = 'all'" 
                        :class="selectedIconCat === 'all' ? 'bg-amber-500 text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-white/5'" 
                        class="px-3 py-1 rounded-lg text-[11px] transition-all cursor-pointer shrink-0">
                    Semua (<span x-text="iconsList.length"></span>)
                </button>
                <template x-for="cat in ['Aksi', 'Media', 'Spesial', 'Interaksi']" :key="cat">
                    <button type="button" @click="selectedIconCat = cat" 
                            :class="selectedIconCat === cat ? 'bg-amber-500 text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-white/5'" 
                            class="px-3 py-1 rounded-lg text-[11px] transition-all cursor-pointer shrink-0" 
                            x-text="cat">
                    </button>
                </template>
            </div>

            <!-- Icons Grid -->
            <div class="flex-1 overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-zinc-700 min-h-[260px]">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5">
                    <template x-for="item in filteredIcons" :key="item.key">
                        <button type="button" @click="selectIcon(item.key)" 
                                :class="formData.icon === item.key ? 'bg-amber-500/20 border-amber-500 text-amber-300 ring-1 ring-amber-500' : 'bg-zinc-900/80 border-white/5 text-zinc-300 hover:border-amber-500/40 hover:bg-zinc-800/80 hover:text-white'" 
                                class="p-3 rounded-2xl border flex flex-col items-center justify-center gap-2 transition-all cursor-pointer group text-center active:scale-95">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110"
                                 :class="formData.icon === item.key ? 'bg-amber-500 text-zinc-950' : 'bg-zinc-800 text-amber-400 group-hover:bg-amber-500/20'"
                                 x-html="getIconSvg(item.key)">
                            </div>
                            <div class="w-full">
                                <div class="text-[11px] font-bold truncate leading-tight" x-text="item.label"></div>
                                <div class="text-[9px] text-zinc-500 font-mono mt-0.5 truncate" x-text="item.key"></div>
                            </div>
                        </button>
                    </template>
                </div>

                <div x-show="filteredIcons.length === 0" class="py-12 text-center text-zinc-500 text-xs">
                    Ikon dengan kata kunci "<span x-text="iconSearchQuery"></span>" tidak ditemukan.
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const ELEMENT_ICONS_DICT = {
        'sparkles': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>',
        'send': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
        'arrow-right': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
        'play': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>',
        'film': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="2.18" ry="2.18"/><line x1="7" x2="7" y1="2" y2="22"/><line x1="17" x2="17" y1="2" y2="22"/><line x1="2" x2="22" y1="12" y2="12"/><line x1="2" x2="7" y1="7" y2="7"/><line x1="2" x2="7" y1="17" y2="17"/><line x1="17" x2="22" y1="17" y2="17"/><line x1="17" x2="22" y1="7" y2="7"/></svg>',
        'clapperboard': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="20.2 6 3 11l-.9-2.4c-.3-1.1.3-2.2 1.3-2.5l13.5-4c1.1-.3 2.2.3 2.5 1.3Z"/><path d="m6.2 5.3 3.1 3.9"/><path d="m12.4 3.4 3.1 4"/><path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>',
        'tv': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>',
        'video': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>',
        'music': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
        'flame': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
        'crown': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>',
        'badge-percent': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m15 9-6 6"/><path d="M9 9h.01"/><path d="M15 15h.01"/></svg>',
        'gift': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>',
        'zap': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'star': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'heart': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>',
        'bookmark': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>',
        'search': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
        'message-circle': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>',
        'message-square': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'bell': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
        'info': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
        'alert-triangle': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>',
        'wrench': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'download': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>',
        'share-2': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>',
        'eye': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
        'trending-up': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
        'plus': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>',
        'check-circle-2': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>',
        'external-link': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>'
    };

    function elementStudio(initialElements, presets, csrfToken) {
        const stateMap = {};
        initialElements.forEach(el => {
            stateMap[el.id] = { is_active: Boolean(el.is_active) };
        });

        return {
            elements: initialElements,
            presets: presets,
            csrfToken: csrfToken,
            elementsState: stateMap,
            modalOpen: false,
            isEdit: false,
            presetDropdownOpen: false,
            iconPickerOpen: false,
            iconSearchQuery: '',
            selectedIconCat: 'all',
            formAction: '{{ route("admin.page_elements.store") }}',
            iconsList: [
                { key: 'sparkles', label: 'Magic / Kilau', cat: 'Spesial' },
                { key: 'send', label: 'Kirim (Telegram)', cat: 'Aksi' },
                { key: 'arrow-right', label: 'Panah Kanan', cat: 'Aksi' },
                { key: 'play', label: 'Play / Tonton', cat: 'Media' },
                { key: 'film', label: 'Film Roll', cat: 'Media' },
                { key: 'clapperboard', label: 'Papan Film', cat: 'Media' },
                { key: 'tv', label: 'TV / Watch Party', cat: 'Media' },
                { key: 'video', label: 'Kamera Video', cat: 'Media' },
                { key: 'music', label: 'Audio / Musik', cat: 'Media' },
                { key: 'flame', label: 'Api / Trending', cat: 'Spesial' },
                { key: 'crown', label: 'VIP / Mahkota', cat: 'Spesial' },
                { key: 'badge-percent', label: 'Promo / Diskon', cat: 'Spesial' },
                { key: 'gift', label: 'Hadiah / Reward', cat: 'Spesial' },
                { key: 'zap', label: 'Kilat / Cepat', cat: 'Spesial' },
                { key: 'star', label: 'Bintang / Favorit', cat: 'Interaksi' },
                { key: 'heart', label: 'Hati / Donasi', cat: 'Interaksi' },
                { key: 'bookmark', label: 'Simpan', cat: 'Interaksi' },
                { key: 'search', label: 'Pencarian', cat: 'Aksi' },
                { key: 'message-circle', label: 'Chat / Diskusi', cat: 'Interaksi' },
                { key: 'message-square', label: 'Komentar', cat: 'Interaksi' },
                { key: 'bell', label: 'Notifikasi', cat: 'Interaksi' },
                { key: 'info', label: 'Informasi', cat: 'Aksi' },
                { key: 'alert-triangle', label: 'Peringatan / Alert', cat: 'Spesial' },
                { key: 'wrench', label: 'Pemeliharaan', cat: 'Aksi' },
                { key: 'download', label: 'Download App', cat: 'Aksi' },
                { key: 'share-2', label: 'Bagikan', cat: 'Interaksi' },
                { key: 'eye', label: 'Lihat / Views', cat: 'Interaksi' },
                { key: 'trending-up', label: 'Grafik Naik', cat: 'Spesial' },
                { key: 'plus', label: 'Tambah Baru', cat: 'Aksi' },
                { key: 'check-circle-2', label: 'Centang Sukses', cat: 'Aksi' },
                { key: 'external-link', label: 'Link Eksternal', cat: 'Aksi' }
            ],

            get filteredIcons() {
                const q = this.iconSearchQuery.toLowerCase().trim();
                return this.iconsList.filter(item => {
                    const matchCat = this.selectedIconCat === 'all' || item.cat === this.selectedIconCat;
                    const matchSearch = !q || item.key.toLowerCase().includes(q) || item.label.toLowerCase().includes(q) || item.cat.toLowerCase().includes(q);
                    return matchCat && matchSearch;
                });
            },

            getIconSvg(key) {
                return ELEMENT_ICONS_DICT[key] || ELEMENT_ICONS_DICT['sparkles'];
            },

            getIconLabel(key) {
                const item = this.iconsList.find(i => i.key === key);
                return item ? item.label : key;
            },

            openIconPicker() {
                this.iconSearchQuery = '';
                this.selectedIconCat = 'all';
                this.iconPickerOpen = true;
                this.$nextTick(() => {
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        window.lucide.createIcons();
                    }
                });
            },

            selectIcon(key) {
                this.formData.icon = key;
                this.iconPickerOpen = false;
                this.$nextTick(() => {
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        window.lucide.createIcons();
                    }
                });
            },

            formData: {
                name: '',
                type: 'broadcast_bar',
                title: '',
                content: '',
                image_url: '',
                button_text: '',
                button_url: '',
                button_target: '_self',
                icon: 'sparkles',
                position: 'top',
                theme_color: 'amber',
                target_page: 'all',
                target_device: 'all',
                target_audience: 'all',
                is_dismissible: true,
                dismiss_duration_hours: 24,
                is_active: true,
                custom_html: '',
            },

            openCreateModal() {
                this.isEdit = false;
                this.formAction = '{{ route("admin.page_elements.store") }}';
                this.formData = {
                    name: '',
                    type: 'broadcast_bar',
                    title: '',
                    content: '',
                    image_url: '',
                    button_text: '',
                    button_url: '',
                    button_target: '_self',
                    icon: 'sparkles',
                    position: 'top',
                    theme_color: 'amber',
                    target_page: 'all',
                    target_device: 'all',
                    target_audience: 'all',
                    is_dismissible: true,
                    dismiss_duration_hours: 24,
                    is_active: true,
                    custom_html: '',
                };
                this.modalOpen = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            openEditModal(el) {
                this.isEdit = true;
                this.formAction = `/admin/page-elements/${el.id}`;
                this.formData = {
                    name: el.name || '',
                    type: el.type || 'broadcast_bar',
                    title: el.title || '',
                    content: el.content || '',
                    image_url: el.image_url || '',
                    button_text: el.button_text || '',
                    button_url: el.button_url || '',
                    button_target: el.button_target || '_self',
                    icon: el.icon || 'sparkles',
                    position: el.position || 'top',
                    theme_color: el.theme_color || 'amber',
                    target_page: el.target_page || 'all',
                    target_device: el.target_device || 'all',
                    target_audience: el.target_audience || 'all',
                    is_dismissible: Boolean(el.is_dismissible),
                    dismiss_duration_hours: el.dismiss_duration_hours ?? 24,
                    is_active: Boolean(el.is_active),
                    custom_html: el.custom_html || '',
                };
                this.modalOpen = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            applyPreset(preset) {
                this.presetDropdownOpen = false;
                this.isEdit = false;
                this.formAction = '{{ route("admin.page_elements.store") }}';
                this.formData = { ...preset, is_active: true };
                this.modalOpen = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            async toggleStatus(id) {
                const current = this.elementsState[id]?.is_active;
                this.elementsState[id].is_active = !current;

                try {
                    const res = await fetch(`/admin/page-elements/${id}/toggle`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        }
                    });
                    if (!res.ok) {
                        this.elementsState[id].is_active = current;
                    }
                } catch (e) {
                    this.elementsState[id].is_active = current;
                }
            },

            getTypeBadge(type) {
                switch(type) {
                    case 'broadcast_bar': return { label: 'Announcement Bar' };
                    case 'floating_widget': return { label: 'Floating Widget' };
                    case 'popup_modal': return { label: 'Popup Modal' };
                    case 'promo_banner': return { label: 'Promo Card' };
                    case 'custom_block': return { label: 'Custom HTML' };
                    default: return { label: 'Elemen' };
                }
            },

            getThemeClasses(color) {
                switch(color) {
                    case 'amber':
                        return {
                            bar: 'bg-amber-500/10 border-amber-500/30 text-amber-300',
                            btn: 'bg-amber-500 hover:bg-amber-400 text-zinc-950',
                            widget: 'bg-amber-500 hover:bg-amber-400 text-zinc-950 border-amber-400/50 shadow-amber-500/20',
                        };
                    case 'blue':
                        return {
                            bar: 'bg-blue-500/10 border-blue-500/30 text-blue-300',
                            btn: 'bg-blue-500 hover:bg-blue-400 text-white',
                            widget: 'bg-blue-600 hover:bg-blue-500 text-white border-blue-400/50 shadow-blue-500/20',
                        };
                    case 'emerald':
                        return {
                            bar: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300',
                            btn: 'bg-emerald-500 hover:bg-emerald-400 text-zinc-950',
                            widget: 'bg-emerald-500 hover:bg-emerald-400 text-zinc-950 border-emerald-400/50 shadow-emerald-500/20',
                        };
                    case 'rose':
                        return {
                            bar: 'bg-rose-500/10 border-rose-500/30 text-rose-300',
                            btn: 'bg-rose-500 hover:bg-rose-400 text-white',
                            widget: 'bg-rose-600 hover:bg-rose-500 text-white border-rose-400/50 shadow-rose-500/20',
                        };
                    case 'purple':
                        return {
                            bar: 'bg-purple-500/10 border-purple-500/30 text-purple-300',
                            btn: 'bg-purple-500 hover:bg-purple-400 text-white',
                            widget: 'bg-purple-600 hover:bg-purple-500 text-white border-purple-400/50 shadow-purple-500/20',
                        };
                    default:
                        return {
                            bar: 'bg-zinc-900 border-zinc-750 text-zinc-200',
                            btn: 'bg-zinc-800 hover:bg-zinc-700 text-white',
                            widget: 'bg-zinc-800 hover:bg-zinc-700 text-[#E4E2DD] border-zinc-700 shadow-black/40',
                        };
                }
            }
        };
    }
</script>
@endpush
