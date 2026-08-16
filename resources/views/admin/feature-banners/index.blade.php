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
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-150 opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150 opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto"
         style="display: none;">
        
        <div @click.outside="showModal = false" class="w-full max-w-3xl bg-zinc-950 border border-zinc-800 p-6 sm:p-7 rounded-2xl shadow-2xl space-y-4 relative text-left my-auto max-h-[90vh] overflow-y-auto admin-scrollbar">
            
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
                                    </button>
                                </div>
                            </template>

                            <template x-if="form.input_type === 'none'">
                                <button type="button" disabled class="px-5 py-2 rounded-xl bg-amber-500 text-zinc-950 font-bold text-xs shrink-0 flex items-center gap-1.5 shadow">
                                    <span x-text="form.button_text || 'Buka Link'"></span>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5" x-show="form.action_type === 'url_link'"></i>
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

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Tipe Aksi</label>
                        <select name="action_type" x-model="form.action_type" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
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
                    <div x-show="form.input_type !== 'none'">
                        <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">Placeholder Input</label>
                        <input type="text" name="placeholder_text" x-model="form.placeholder_text" placeholder="Cari film..." class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                    </div>
                </div>

                <div x-show="form.action_type === 'url_link'">
                    <label class="block text-[11px] font-semibold text-zinc-300 uppercase tracking-wider mb-1">URL Target</label>
                    <input type="url" name="action_url" x-model="form.action_url" placeholder="https://..." class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
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

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('featureBannerCMS', () => ({
        showModal: false, 
        isEdit: false, 
        editUrl: '', 
        aiTopic: '',
        aiLoading: false,
        form: {
            badge_text: 'REQUEST FILM',
            title: 'Film Favoritmu Belum Ada di Katalog?',
            description: 'Minta judul film atau series yang kamu cari. Kami akan menambahkannya secara gratis.',
            placeholder_text: 'Cari atau ketik judul film...',
            input_type: 'text',
            button_text: 'Request Sekarang',
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
                    if (d.action_type) this.form.action_type = d.action_type;
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
                action_type: 'request_modal',
                action_url: '',
                bg_gradient: 'amber_purple',
                bg_gradient_from: '#1e1b4b',
                bg_gradient_to: '#451a03',
                is_active: true,
                sort_order: 1
            };
            this.showModal = true;
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
                action_type: item.action_type || 'request_modal',
                action_url: item.action_url || '',
                bg_gradient: item.bg_gradient || 'amber_purple',
                bg_gradient_from: item.bg_gradient_from || '#1e1b4b',
                bg_gradient_to: item.bg_gradient_to || '#451a03',
                is_active: Boolean(item.is_active),
                sort_order: item.sort_order || 1
            };
            this.showModal = true;
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
