@extends('layouts.admin')

@section('title', 'Banner Fitur - Admin Panel')

@section('content')
<div class="space-y-6" x-data="featureBannerCMS()">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2.5">
                <i data-lucide="layout" class="w-5 h-5 text-amber-400"></i>
                <span>Banner Fitur Homepage</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-0.5">Kelola konten, tipe input, dan gaya visual banner utama dengan bantuan AI Copywriter.</p>
        </div>

        <button @click="openCreate()" type="button" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-2 transition-colors cursor-pointer shrink-0 shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Tambah Banner</span>
        </button>
    </div>

    <!-- Stat Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-1">
            <span class="text-[11px] text-zinc-400 font-semibold uppercase tracking-wider">Total Banner</span>
            <div class="text-xl font-bold text-white">{{ $banners->total() }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-zinc-900/80 border border-emerald-500/20 space-y-1">
            <span class="text-[11px] text-emerald-400 font-semibold uppercase tracking-wider">Aktif Tampil</span>
            <div class="text-xl font-bold text-emerald-400">{{ $banners->where('is_active', true)->count() }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-1">
            <span class="text-[11px] text-zinc-400 font-semibold uppercase tracking-wider">Nonaktif</span>
            <div class="text-xl font-bold text-zinc-400">{{ $banners->where('is_active', false)->count() }}</div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-zinc-900/80 rounded-2xl border border-zinc-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-300">
                <thead class="bg-zinc-950/60 text-zinc-400 uppercase font-semibold text-[10px] tracking-wider border-b border-zinc-800">
                    <tr>
                        <th class="px-5 py-3">Badge & Judul</th>
                        <th class="px-5 py-3">Tipe Input</th>
                        <th class="px-5 py-3">Aksi Tombol</th>
                        <th class="px-5 py-3">Tema Gradien</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/50">
                    @forelse($banners as $item)
                        <tr class="hover:bg-zinc-850/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-500/15 text-amber-300 border border-amber-500/20 mb-1">
                                    {{ $item->badge_text }}
                                </span>
                                <div class="font-bold text-xs text-white">{{ $item->title }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-800 text-zinc-300 border border-zinc-700">
                                    {{ $item->input_type ?: 'text' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-1 rounded text-[10px] font-semibold uppercase {{ $item->action_type === 'request_modal' ? 'bg-sky-500/10 text-sky-400 border border-sky-500/20' : 'bg-purple-500/10 text-purple-400 border border-purple-500/20' }}">
                                    {{ $item->action_type === 'request_modal' ? 'Modal Request' : 'URL Link' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    @if($item->bg_gradient === 'custom')
                                        <div class="w-5 h-5 rounded-full border border-white/20 shadow-inner" style="background: linear-gradient(135deg, {{ $item->bg_gradient_from ?: '#1e1b4b' }}, {{ $item->bg_gradient_to ?: '#451a03' }});"></div>
                                        <span class="text-[10px] font-mono text-zinc-300 uppercase">Custom</span>
                                    @else
                                        <span class="text-[10px] font-mono text-zinc-400 uppercase">
                                            {{ str_replace('_', ' ', $item->bg_gradient) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <form action="{{ route('admin.feature-banners.toggle', $item->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase transition-colors cursor-pointer {{ $item->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-zinc-800 text-zinc-400 border border-zinc-700 hover:text-white' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button data-item="{{ json_encode($item) }}"
                                            @click="openEdit(JSON.parse($el.dataset.item), '{{ route('admin.feature-banners.update', $item->id) }}')" 
                                            class="p-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition-colors cursor-pointer" title="Edit">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>

                                    <form action="{{ route('admin.feature-banners.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus banner ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors cursor-pointer" title="Hapus">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-zinc-500 text-xs">
                                Belum ada banner fitur.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($banners->hasPages())
            <div class="p-3 border-t border-zinc-800">
                {{ $banners->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal with AI Copywriter & Live Preview -->
    <template x-teleport="body">
        <div x-show="showModal" 
             x-transition:enter="transition ease-out duration-150 opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150 opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto"
             style="display: none;">
            
            <div @click.outside="if (!iconPickerOpen) showModal = false" class="w-full max-w-3xl bg-zinc-950 border border-zinc-800 p-6 sm:p-7 rounded-2xl shadow-2xl space-y-4 relative text-left my-auto max-h-[90vh] overflow-y-auto admin-scrollbar">
                
                <div class="flex items-center justify-between border-b border-zinc-800/80 pb-3">
                    <h3 class="font-bold text-sm text-white flex items-center gap-2">
                        <i data-lucide="layout" class="w-4 h-4 text-amber-400"></i>
                        <span x-text="isEdit ? 'Edit Banner Fitur' : 'Tambah Banner Fitur'"></span>
                    </h3>
                <button type="button" @click="showModal = false" class="text-zinc-400 hover:text-white p-1 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- AI COPYWRITER ASSISTANT BOX -->
            <div class="p-3.5 rounded-xl bg-zinc-900 border border-amber-500/30 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>AI Copywriter Assistant</span>
                    </span>
                    <span class="text-[10px] text-zinc-400">Buat copywriting otomatis</span>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" x-model="aiTopic" @keydown.enter.prevent="generateWithAi()" placeholder="Ketik topik (misal: Promo Rilis Dracin, Request Film Marvel, Survey)..." 
                           class="flex-1 bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400">
                    <button type="button" @click="generateWithAi()" :disabled="aiLoading || !aiTopic.trim()" 
                            class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs shrink-0 flex items-center gap-1.5 transition-colors disabled:opacity-50 cursor-pointer">
                        <span x-show="!aiLoading" class="flex items-center gap-1">
                            <i data-lucide="wand-2" class="w-3.5 h-3.5"></i>
                            <span>Generate AI</span>
                        </span>
                        <span x-show="aiLoading" class="flex items-center gap-1">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                            <span>Proses...</span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- LIVE PREVIEW BOX -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                    <span>Preview Tampilan Banner</span>
                </div>

                <div class="relative overflow-hidden rounded-2xl border p-5 shadow-lg backdrop-blur-md transition-all duration-200"
                     :class="form.bg_gradient !== 'custom' ? getPreviewClass() : ''"
                     :style="getPreviewStyle()">
                    
                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="space-y-2 text-center md:text-left max-w-lg">
                            <template x-if="form.badge_text">
                                <span class="inline-block px-2.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-bold uppercase tracking-wider" x-text="form.badge_text"></span>
                            </template>

                            <h4 class="font-bold text-lg text-white leading-snug" x-text="form.title || 'Judul Banner'"></h4>

                            <p class="text-zinc-300 text-xs leading-normal" x-text="form.description || 'Deskripsi banner...'"></p>
                        </div>

                        <div class="w-full md:w-auto shrink-0 flex items-center gap-2">
                            <template x-if="form.input_type !== 'none'">
                                <div class="flex items-center gap-2 w-full md:w-auto">
                                    <input :type="form.input_type || 'text'" disabled :placeholder="form.placeholder_text || 'Cari...'" 
                                           class="w-full md:w-52 bg-zinc-950/80 border border-white/10 rounded-xl px-3 py-2 text-xs text-zinc-400 cursor-not-allowed">
                                    <button type="button" disabled class="px-4 py-2 rounded-xl bg-amber-500 text-zinc-950 font-bold text-xs shrink-0 flex items-center gap-1.5 shadow">
                                        <span x-text="form.button_text || 'Request'"></span>
                                        <span class="inline-flex items-center shrink-0" x-html="getIconSvg(form.button_icon || (form.action_type === 'request_modal' ? 'send' : 'arrow-right'))"></span>
                                    </button>
                                </div>
                            </template>

                            <template x-if="form.input_type === 'none'">
                                <button type="button" disabled class="px-5 py-2 rounded-xl bg-amber-500 text-zinc-950 font-bold text-xs shrink-0 flex items-center gap-1.5 shadow">
                                    <span x-text="form.button_text || 'Buka Link'"></span>
                                    <span class="inline-flex items-center shrink-0" x-html="getIconSvg(form.button_icon || (form.action_type === 'request_modal' ? 'send' : 'arrow-right'))"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM INPUTS -->
            <form :action="isEdit ? editUrl : '{{ route('admin.feature-banners.store') }}'" method="POST" class="space-y-4 pt-1">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Badge Header</label>
                        <input type="text" name="badge_text" x-model="form.badge_text" required placeholder="Contoh: REQUEST FILM" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Urutan (Sort)</label>
                        <input type="number" name="sort_order" x-model="form.sort_order" required class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Judul Utama</label>
                    <input type="text" name="title" x-model="form.title" required placeholder="Judul banner..." class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Deskripsi</label>
                    <textarea name="description" x-model="form.description" rows="2" required placeholder="Tuliskan keterangan singkat..." class="w-full bg-zinc-900 border border-zinc-800 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-amber-400"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Tipe Aksi</label>
                        <select name="action_type" x-model="form.action_type" @change="if(form.action_type==='request_modal' && (!form.button_icon || form.button_icon==='arrow-right')) form.button_icon='send'; if(form.action_type==='url_link' && form.button_icon==='send') form.button_icon='arrow-right';" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                            <option value="request_modal">Modal Request Film</option>
                            <option value="url_link">Redirect URL Link</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Tipe Input</label>
                        <select name="input_type" x-model="form.input_type" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                            <option value="text">Teks (Judul Film)</option>
                            <option value="email">Email</option>
                            <option value="number">Angka (Nomor/Tahun)</option>
                            <option value="url">URL Link</option>
                            <option value="none">Tanpa Input (Hanya Tombol)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Teks Tombol</label>
                        <input type="text" name="button_text" x-model="form.button_text" required placeholder="Request Sekarang" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1 flex items-center justify-between">
                            <span>Ikon Tombol</span>
                            <span class="text-[9px] text-amber-400 font-normal">Klik ganti</span>
                        </label>
                        <input type="hidden" name="button_icon" x-model="form.button_icon">
                        <button type="button" @click="openIconPicker()" class="w-full bg-zinc-900 border border-zinc-800 hover:border-amber-400/60 rounded-xl px-3 py-1.5 text-xs text-white flex items-center justify-between gap-2 transition-all cursor-pointer group shadow-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-6 h-6 rounded-lg bg-amber-500/15 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0" x-html="getIconSvg(form.button_icon || (form.action_type === 'request_modal' ? 'send' : 'arrow-right'))"></span>
                                <span class="truncate text-xs font-semibold" x-text="getIconLabel(form.button_icon || (form.action_type === 'request_modal' ? 'send' : 'arrow-right'))"></span>
                            </div>
                            <i data-lucide="palette" class="w-3.5 h-3.5 text-zinc-500 group-hover:text-amber-400 shrink-0 transition-colors"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="form.input_type !== 'none' || form.action_type === 'url_link'">
                    <div x-show="form.input_type !== 'none'">
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Placeholder Input</label>
                        <input type="text" name="placeholder_text" x-model="form.placeholder_text" placeholder="Cari film..." class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>
                    <div x-show="form.action_type === 'url_link'">
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">URL Target</label>
                        <input type="url" name="action_url" x-model="form.action_url" placeholder="https://..." class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>
                </div>

                <!-- GRADIENT PALETTE SELECTOR -->
                <div class="space-y-2 pt-1">
                    <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider">Warna Gradien</label>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <label @click="form.bg_gradient = 'amber_purple'" class="p-2 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors"
                               :class="form.bg_gradient === 'amber_purple' ? 'bg-zinc-800 border-amber-400 text-white font-bold' : 'bg-zinc-900/60 border-zinc-800 text-zinc-400 hover:text-white'">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-white/20" style="background: linear-gradient(135deg, #1e1b4b, #451a03)"></span>
                            <span class="text-xs">Indigo & Amber</span>
                        </label>

                        <label @click="form.bg_gradient = 'emerald_teal'" class="p-2 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors"
                               :class="form.bg_gradient === 'emerald_teal' ? 'bg-zinc-800 border-emerald-400 text-white font-bold' : 'bg-zinc-900/60 border-zinc-800 text-zinc-400 hover:text-white'">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-white/20" style="background: linear-gradient(135deg, #064e3b, #134e4a)"></span>
                            <span class="text-xs">Emerald & Teal</span>
                        </label>

                        <label @click="form.bg_gradient = 'sky_indigo'" class="p-2 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors"
                               :class="form.bg_gradient === 'sky_indigo' ? 'bg-zinc-800 border-sky-400 text-white font-bold' : 'bg-zinc-900/60 border-zinc-800 text-zinc-400 hover:text-white'">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-white/20" style="background: linear-gradient(135deg, #0c4a6e, #312e81)"></span>
                            <span class="text-xs">Sky & Indigo</span>
                        </label>

                        <label @click="form.bg_gradient = 'rose_orange'" class="p-2 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors"
                               :class="form.bg_gradient === 'rose_orange' ? 'bg-zinc-800 border-rose-400 text-white font-bold' : 'bg-zinc-900/60 border-zinc-800 text-zinc-400 hover:text-white'">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-white/20" style="background: linear-gradient(135deg, #4c0519, #7c2d12)"></span>
                            <span class="text-xs">Rose & Orange</span>
                        </label>

                        <label @click="form.bg_gradient = 'cyber_neon'" class="p-2 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors"
                               :class="form.bg_gradient === 'cyber_neon' ? 'bg-zinc-800 border-fuchsia-400 text-white font-bold' : 'bg-zinc-900/60 border-zinc-800 text-zinc-400 hover:text-white'">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-white/20" style="background: linear-gradient(135deg, #701a75, #164e63)"></span>
                            <span class="text-xs">Fuchsia & Cyan</span>
                        </label>

                        <label @click="form.bg_gradient = 'custom'" class="p-2 rounded-xl border flex items-center gap-2 cursor-pointer transition-colors"
                               :class="form.bg_gradient === 'custom' ? 'bg-zinc-800 border-amber-400 text-white font-bold' : 'bg-zinc-900/60 border-zinc-800 text-zinc-400 hover:text-white'">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-white/20" style="background: linear-gradient(135deg, #b91c1c, #1d4ed8)"></span>
                            <span class="text-xs font-semibold">Custom Hex</span>
                        </label>
                    </div>

                    <input type="hidden" name="bg_gradient" x-model="form.bg_gradient">

                    <!-- Custom Hex Picker Fields -->
                    <div x-show="form.bg_gradient === 'custom'" class="pt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold uppercase text-zinc-400 mb-1">Warna Kiri (From Hex)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="form.bg_gradient_from" class="w-8 h-8 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer p-0.5">
                                <input type="text" name="bg_gradient_from" x-model="form.bg_gradient_from" placeholder="#1e1b4b" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-1.5 text-xs text-white font-mono uppercase focus:outline-none focus:border-amber-400">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase text-zinc-400 mb-1">Warna Kanan (To Hex)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="form.bg_gradient_to" class="w-8 h-8 rounded-lg bg-zinc-900 border border-zinc-700 cursor-pointer p-0.5">
                                <input type="text" name="bg_gradient_to" x-model="form.bg_gradient_to" placeholder="#451a03" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-1.5 text-xs text-white font-mono uppercase focus:outline-none focus:border-amber-400">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Checkbox -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="is_active_input" x-model="form.is_active" class="w-4 h-4 rounded text-amber-500 bg-zinc-900 border-zinc-700 cursor-pointer">
                    <label for="is_active_input" class="text-xs font-semibold text-zinc-300 cursor-pointer">Tampilkan Banner Ini di Homepage</label>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-800">
                    <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-semibold transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Simpan Banner</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- ICON PICKER MODAL -->
    <template x-teleport="body">
        <div x-show="iconPickerOpen" 
             x-cloak 
             @click.stop
             class="fixed inset-0 z-[85] flex items-center justify-center bg-black/85 backdrop-blur-md p-4 animate-in fade-in duration-200">
            <div @click.stop @click.outside="iconPickerOpen = false" class="w-full max-w-2xl bg-zinc-950 border border-white/15 rounded-3xl p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-white/10 pb-3 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
                            <i data-lucide="palette" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white">Pilih Ikon Tombol Banner</h3>
                            <p class="text-[11px] text-zinc-400">Pilih salah satu ikon di bawah ini untuk ditampilkan pada tombol aksi banner</p>
                        </div>
                    </div>
                    <button type="button" @click="iconPickerOpen = false" class="p-1 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer">
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
                <div class="flex-1 overflow-y-auto pr-1 admin-scrollbar min-h-[260px]">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5">
                        <template x-for="item in filteredIcons" :key="item.key">
                            <button type="button" @click="selectIcon(item.key)" 
                                    :class="form.button_icon === item.key ? 'bg-amber-500/20 border-amber-500 text-amber-300 ring-1 ring-amber-500' : 'bg-zinc-900/80 border-white/5 text-zinc-300 hover:border-amber-500/40 hover:bg-zinc-800/80 hover:text-white'" 
                                    class="p-3 rounded-2xl border flex flex-col items-center justify-center gap-2 transition-all cursor-pointer group text-center active:scale-95">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110"
                                     :class="form.button_icon === item.key ? 'bg-amber-500 text-zinc-950' : 'bg-zinc-800 text-amber-400 group-hover:bg-amber-500/20'"
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

                <!-- Footer -->
                <div class="flex items-center justify-between border-t border-white/10 pt-3 shrink-0">
                    <div class="text-xs text-zinc-400 flex items-center gap-1.5">
                        <span>Terpilih:</span>
                        <span class="font-bold text-amber-400 font-mono" x-text="form.button_icon || 'send'"></span>
                    </div>
                    <button type="button" @click="iconPickerOpen = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-white transition-colors cursor-pointer">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const ICONS_DICT = {
        'send': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
        'arrow-right': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
        'play': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"><polygon points="6 3 20 12 6 21 6 3"/></svg>',
        'film': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M17 3v18"/><path d="M3 7h4"/><path d="M3 12h18"/><path d="M3 17h4"/><path d="M17 17h4"/><path d="M17 7h4"/></svg>',
        'clapperboard': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.2 6 3 11l-.9-2.4c-.4-1.1.2-2.4 1.3-2.8l13.6-5c1.1-.4 2.4.2 2.8 1.3l.4 1z"/><path d="m6.2 5.3 3.1 3.9"/><path d="m12.4 3 3.1 4"/><path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>',
        'tv': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2"/><polyline points="17 2 12 7 7 2"/></svg>',
        'video': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2"/></svg>',
        'music': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
        'sparkles': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>',
        'flame': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
        'crown': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>',
        'badge-percent': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m15 9-6 6"/><path d="M9 9h.01"/><path d="M15 15h.01"/></svg>',
        'gift': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>',
        'zap': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'star': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'heart': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>',
        'bookmark': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>',
        'search': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
        'ticket': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>',
        'compass': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
        'download': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>',
        'message-square': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'share-2': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>',
        'bell': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
        'eye': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
        'trending-up': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
        'plus': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>',
        'check-circle-2': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
        'shuffle': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18h1.4c1.3 0 2.5-.6 3.3-1.7l6.1-8.6c.7-1.1 2-1.7 3.3-1.7H22"/><path d="m18 2 4 4-4 4"/><path d="M2 6h1.9c1.5 0 2.9.9 3.6 2.2"/><path d="M22 18h-5.9c-1.3 0-2.6-.7-3.3-1.8l-.5-.8"/><path d="m18 14 4 4-4 4"/></svg>',
        'external-link': '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>'
    };

    Alpine.data('featureBannerCMS', () => ({
        showModal: false, 
        iconPickerOpen: false,
        iconSearchQuery: '',
        selectedIconCat: 'all',
        isEdit: false, 
        editUrl: '', 
        aiTopic: '',
        aiLoading: false,
        iconsList: [
            { key: 'send', label: 'Kirim / Pesawat', cat: 'Aksi' },
            { key: 'arrow-right', label: 'Panah Kanan', cat: 'Aksi' },
            { key: 'play', label: 'Play / Tonton', cat: 'Media' },
            { key: 'film', label: 'Film Roll', cat: 'Media' },
            { key: 'clapperboard', label: 'Papan Film', cat: 'Media' },
            { key: 'tv', label: 'TV / Series', cat: 'Media' },
            { key: 'video', label: 'Kamera Video', cat: 'Media' },
            { key: 'music', label: 'Audio / Musik', cat: 'Media' },
            { key: 'sparkles', label: 'Magic / Kilau', cat: 'Spesial' },
            { key: 'flame', label: 'Api / Trending', cat: 'Spesial' },
            { key: 'crown', label: 'VIP / Mahkota', cat: 'Spesial' },
            { key: 'badge-percent', label: 'Promo / Diskon', cat: 'Spesial' },
            { key: 'gift', label: 'Hadiah / Reward', cat: 'Spesial' },
            { key: 'zap', label: 'Kilat / Cepat', cat: 'Spesial' },
            { key: 'star', label: 'Bintang / Favorit', cat: 'Interaksi' },
            { key: 'heart', label: 'Hati / Suka', cat: 'Interaksi' },
            { key: 'bookmark', label: 'Simpan', cat: 'Interaksi' },
            { key: 'search', label: 'Pencarian', cat: 'Aksi' },
            { key: 'ticket', label: 'Tiket Bioskop', cat: 'Media' },
            { key: 'compass', label: 'Jelajah / Explore', cat: 'Aksi' },
            { key: 'download', label: 'Download', cat: 'Aksi' },
            { key: 'message-square', label: 'Komentar / Chat', cat: 'Interaksi' },
            { key: 'share-2', label: 'Bagikan', cat: 'Interaksi' },
            { key: 'bell', label: 'Notifikasi', cat: 'Interaksi' },
            { key: 'eye', label: 'Lihat / Views', cat: 'Interaksi' },
            { key: 'trending-up', label: 'Grafik Naik', cat: 'Spesial' },
            { key: 'plus', label: 'Tambah / Buat', cat: 'Aksi' },
            { key: 'check-circle-2', label: 'Centang Sukses', cat: 'Aksi' },
            { key: 'shuffle', label: 'Acak Film', cat: 'Aksi' },
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
            return ICONS_DICT[key] || ICONS_DICT['send'];
        },
        getIconLabel(key) {
            const item = this.iconsList.find(i => i.key === key);
            return item ? item.label : key;
        },
        openIconPicker() {
            this.iconSearchQuery = '';
            this.iconPickerOpen = true;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },
        selectIcon(key) {
            this.form.button_icon = key;
            this.iconPickerOpen = false;
            this.showModal = true;
        },
        form: {
            badge_text: 'REQUEST FILM',
            title: 'Film Favoritmu Belum Ada di Katalog?',
            description: 'Minta judul film atau series yang kamu cari. Kami akan menambahkannya secara gratis.',
            placeholder_text: 'Cari atau ketik judul film...',
            input_type: 'text',
            button_text: 'Request Sekarang',
            button_icon: 'send',
            action_type: 'request_modal',
            action_url: '',
            bg_gradient: 'amber_purple',
            bg_gradient_from: '#1e1b4b',
            bg_gradient_to: '#451a03',
            is_active: true,
            sort_order: 1
        },
        async generateWithAi() {
            if (!this.aiTopic.trim()) return;
            this.aiLoading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('{{ route("admin.feature-banners.generate_ai") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ topic: this.aiTopic.trim() })
                });
                const json = await res.json();
                if (json.status === 'success' && json.data) {
                    const d = json.data;
                    if (d.badge_text) this.form.badge_text = d.badge_text;
                    if (d.title) this.form.title = d.title;
                    if (d.description) this.form.description = d.description;
                    if (d.placeholder_text) this.form.placeholder_text = d.placeholder_text;
                    if (d.button_text) this.form.button_text = d.button_text;
                    if (d.action_type) {
                        this.form.action_type = d.action_type;
                        this.form.button_icon = d.action_type === 'request_modal' ? 'send' : 'arrow-right';
                    }
                    if (d.input_type) this.form.input_type = d.input_type;
                    if (d.bg_gradient) this.form.bg_gradient = d.bg_gradient;
                }
            } catch (e) {
                console.error('AI Generate Error:', e);
            } finally {
                this.aiLoading = false;
            }
        },
        openCreate() {
            this.isEdit = false;
            this.aiTopic = '';
            this.form = {
                badge_text: 'REQUEST FILM',
                title: 'Film Favoritmu Belum Ada di Katalog?',
                description: 'Minta judul film atau series yang kamu cari. Kami akan menambahkannya secara gratis.',
                placeholder_text: 'Cari atau ketik judul film...',
                input_type: 'text',
                button_text: 'Request Sekarang',
                button_icon: 'send',
                action_type: 'request_modal',
                action_url: '',
                bg_gradient: 'amber_purple',
                bg_gradient_from: '#1e1b4b',
                bg_gradient_to: '#451a03',
                is_active: true,
                sort_order: 1
            };
            this.showModal = true;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },
        openEdit(item, url) {
            this.isEdit = true;
            this.aiTopic = '';
            this.editUrl = url;
            this.form = {
                badge_text: item.badge_text || '',
                title: item.title || '',
                description: item.description || '',
                placeholder_text: item.placeholder_text || '',
                input_type: item.input_type || 'text',
                button_text: item.button_text || 'Request Sekarang',
                button_icon: item.button_icon || (item.action_type === 'request_modal' ? 'send' : 'arrow-right'),
                action_type: item.action_type || 'request_modal',
                action_url: item.action_url || '',
                bg_gradient: item.bg_gradient || 'amber_purple',
                bg_gradient_from: item.bg_gradient_from || '#1e1b4b',
                bg_gradient_to: item.bg_gradient_to || '#451a03',
                is_active: Boolean(item.is_active),
                sort_order: item.sort_order || 1
            };
            this.showModal = true;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },
        getPreviewStyle() {
            if (this.form.bg_gradient === 'custom') {
                return 'background: linear-gradient(135deg, ' + (this.form.bg_gradient_from || '#1e1b4b') + ' 0%, #09090b 50%, ' + (this.form.bg_gradient_to || '#451a03') + ' 100%); border-color: rgba(255, 255, 255, 0.15);';
            }
            return '';
        },
        getPreviewClass() {
            switch(this.form.bg_gradient) {
                case 'emerald_teal': return 'from-emerald-950/60 via-zinc-950 to-teal-950/60 border-emerald-500/30';
                case 'sky_indigo': return 'from-sky-950/60 via-zinc-950 to-indigo-950/60 border-sky-500/30';
                case 'rose_orange': return 'from-rose-950/60 via-zinc-950 to-orange-950/60 border-rose-500/30';
                case 'cyber_neon': return 'from-fuchsia-950/60 via-zinc-950 to-cyan-950/60 border-fuchsia-500/30';
                default: return 'from-indigo-950/60 via-zinc-950 to-amber-950/60 border-amber-500/30';
            }
        }
    }));
});
</script>
@endpush
