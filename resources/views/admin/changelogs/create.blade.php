@extends('layouts.admin')

@section('title', 'Tambah Catatan Rilis | faiiladmin')
@section('page_title', 'Tambah Catatan Rilis Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="changelogCreateForm">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-white font-['Outfit']">Form Catatan Rilis Baru</h2>
            <p class="text-xs text-zinc-400">Isi manual atau gunakan fitur AI Import untuk mengisi form secara otomatis.</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- AI Import Modal Trigger Button -->
            <button type="button" @click="showImportModal = true" class="px-4 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 text-purple-300 border border-purple-500/30 text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow-lg shadow-purple-500/10">
                <i data-lucide="sparkles" class="w-4 h-4 text-purple-400 animate-pulse"></i>
                <span>Import data dari AI (JSON / Markdown)</span>
            </button>

            <a href="{{ route('admin.changelogs.index') }}" class="px-3.5 py-2 rounded-xl bg-zinc-900 border border-white/10 text-xs text-zinc-400 hover:text-white flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl">
        <form action="{{ route('admin.changelogs.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Version -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nomor Versi *</label>
                    <input type="text" name="version" x-model="version" required placeholder="Contoh: v2.5.0" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:outline-none focus:border-amber-500">
                </div>

                <!-- Update Type -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tipe Rilis *</label>
                    <select name="type" x-model="type" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="major">Major Release (Fitur Utama Baru)</option>
                        <option value="minor">Minor Update (Fitur Tambahan)</option>
                        <option value="patch">Patch (Perbaikan Bug)</option>
                        <option value="security">Security Patch (Keamanan)</option>
                    </select>
                </div>

                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Judul Rilis *</label>
                    <input type="text" name="title" x-model="title" required placeholder="Contoh: Pembaruan Antarmuka & Peningkatan Kecepatan Video" 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Release Date -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tanggal Rilis *</label>
                    <input type="date" name="release_date" x-model="release_date" required 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Publish Toggle -->
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-3 text-xs text-white cursor-pointer select-none">
                        <input type="checkbox" name="is_published" value="1" checked class="w-4 h-4 rounded border-white/20 bg-zinc-900 text-amber-500 focus:ring-amber-500">
                        <span class="font-bold">Langsung Publikasikan di Halaman Changelog</span>
                    </label>
                </div>

                <!-- Summary -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Ringkasan Singkat Rilis</label>
                    <textarea name="summary" x-model="summary" rows="3" placeholder="Jelaskan secara singkat latar belakang pembaruan ini..." 
                              class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <!-- Dynamic Change Items Builder -->
                <div class="md:col-span-2 space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Rincian Poin Perubahan (Changes List)</label>
                        <button type="button" @click="addChange()" class="px-3 py-1 rounded-lg bg-amber-500/20 text-amber-300 font-bold text-xs hover:bg-amber-500/30 transition-colors flex items-center gap-1 cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Tambah Poin</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in changes" :key="index">
                            <div class="flex items-center gap-2 p-3 rounded-xl bg-zinc-950 border border-white/10">
                                <select :name="`changes[${index}][type]`" x-model="item.type" class="bg-zinc-900 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none shrink-0">
                                    <option value="feature">Fitur Baru</option>
                                    <option value="improvement">Peningkatan</option>
                                    <option value="fix">Perbaikan Bug</option>
                                    <option value="security">Keamanan</option>
                                </select>
                                <input type="text" :name="`changes[${index}][text]`" x-model="item.text" required placeholder="Deskripsi perubahan yang dilakukan..." 
                                       class="flex-1 bg-zinc-900 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none">
                                <button type="button" @click="removeChange(index)" class="p-1.5 rounded-lg text-zinc-400 hover:text-red-400 hover:bg-red-500/10 cursor-pointer">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Bottom Spacer for Floating Bar -->
            <div class="h-20"></div>

            <!-- Floating Bottom-Right Save Action Bar -->
            <div class="fixed bottom-6 right-6 z-40 flex items-center gap-2 bg-zinc-900/95 backdrop-blur-xl border border-white/15 p-2 sm:p-2.5 rounded-2xl shadow-2xl shadow-black/90 ring-1 ring-white/10 hover:border-amber-500/50 transition-all">
                <a href="{{ route('admin.changelogs.index') }}" class="px-3.5 sm:px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Batal</span>
                </a>
                <button type="submit" class="px-5 sm:px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold text-xs shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan Catatan Rilis</span>
                </button>
            </div>
        </form>
    </div>

    <!-- AI Import Modal -->
    <template x-teleport="body">
        <div x-show="showImportModal" 
             x-cloak 
             class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
             x-transition>
            <div class="w-full max-w-2xl bg-zinc-900 border border-white/15 rounded-3xl p-6 shadow-2xl space-y-5" @click.away="showImportModal = false">
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-300 flex items-center justify-center border border-purple-500/30">
                            <i data-lucide="sparkles" class="w-5 h-5 text-purple-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-white">Import Catatan Rilis dari AI</h3>
                            <p class="text-xs text-zinc-400">Salin prompt untuk AI atau tempel langsung data rilis JSON / Markdown.</p>
                        </div>
                    </div>
                    <button type="button" @click="showImportModal = false" class="text-zinc-400 hover:text-white p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Format Chooser & Prompt Copy -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-zinc-300">Pilih Format Data:</span>
                            <div class="inline-flex rounded-xl bg-zinc-950 p-1 border border-white/10">
                                <button type="button" 
                                        @click="importFormat = 'json'" 
                                        :class="importFormat === 'json' ? 'bg-purple-500 text-white font-bold' : 'text-zinc-400 hover:text-white'"
                                        class="px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer">
                                    JSON
                                </button>
                                <button type="button" 
                                        @click="importFormat = 'markdown'" 
                                        :class="importFormat === 'markdown' ? 'bg-purple-500 text-white font-bold' : 'text-zinc-400 hover:text-white'"
                                        class="px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer">
                                    Markdown
                                </button>
                            </div>
                        </div>

                        <button type="button" @click="copyPrompt()" class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer">
                            <i :data-lucide="copiedPrompt ? 'check' : 'copy'" class="w-3.5 h-3.5"></i>
                            <span x-text="copiedPrompt ? 'Prompt Berhasil Disalin!' : 'Salin Prompt AI (' + importFormat.toUpperCase() + ')'"></span>
                        </button>
                    </div>

                    <!-- Textarea Paste -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1.5">Tempel Output dari AI di bawah ini:</label>
                        <textarea x-model="rawImportText" 
                                  rows="9" 
                                  placeholder="Tempel teks JSON atau Markdown dari ChatGPT/Gemini/Claude di sini..."
                                  class="w-full bg-zinc-950 border border-white/10 rounded-2xl p-4 text-xs font-mono text-white focus:outline-none focus:border-purple-500 admin-scrollbar"></textarea>
                    </div>
                </div>

                <!-- Modal Action Footer -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-white/10">
                    <form action="{{ route('admin.changelogs.import') }}" method="POST" class="w-full sm:w-auto flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="format" :value="importFormat">
                        <input type="hidden" name="content" :value="rawImportText">
                        <input type="hidden" name="auto_publish" value="1">
                        <button type="submit" :disabled="!rawImportText.trim()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-200 border border-purple-500/30 text-xs font-bold disabled:opacity-40 transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                            <i data-lucide="database" class="w-4 h-4 text-purple-400"></i>
                            <span>Langsung Simpan ke DB</span>
                        </button>
                    </form>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-bold cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="parseAndPopulate()" :disabled="!rawImportText.trim()" class="px-5 py-2.5 rounded-xl bg-purple-500 hover:bg-purple-400 text-white text-xs font-bold shadow-lg shadow-purple-500/20 transition-colors disabled:opacity-40 cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                            <span>Isi Form Otomatis</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('changelogCreateForm', () => ({
        version: @json(old('version', $nextVersion)),
        title: @json(old('title', '')),
        type: @json(old('type', 'minor')),
        release_date: @json(old('release_date', date('Y-m-d'))),
        summary: @json(old('summary', '')),
        changes: [
            { type: 'feature', text: '' }
        ],
        showImportModal: false,
        importFormat: 'json',
        rawImportText: '',
        copiedPrompt: false,

        copyPrompt() {
            const today = new Date().toISOString().split('T')[0];
            const targetVersion = this.version || @json($nextVersion);
            const jsonPrompt = 'Buatkan catatan rilis (changelog) terbaru untuk aplikasi faiilmov dalam format JSON persis seperti berikut:\n\n' +
'{\n' +
'  "version": "' + targetVersion + '",\n' +
'  "title": "Judul Pembaruan Singkat",\n' +
'  "type": "minor",\n' +
'  "release_date": "' + today + '",\n' +
'  "summary": "Ringkasan singkat pembaruan...",\n' +
'  "changes": [\n' +
'    { "type": "feature", "text": "Deskripsi fitur baru" },\n' +
'    { "type": "improvement", "text": "Deskripsi peningkatan" },\n' +
'    { "type": "fix", "text": "Deskripsi perbaikan bug" }\n' +
'  ]\n' +
'}';

            const mdPrompt = 'Buatkan catatan rilis (changelog) terbaru untuk aplikasi faiilmov dalam format Markdown persis seperti berikut:\n\n' +
'# ' + targetVersion + ' - Judul Pembaruan Singkat\n' +
'**Tanggal**: ' + today + '\n' +
'**Tipe**: minor\n' +
'**Ringkasan**: Ringkasan singkat pembaruan...\n\n' +
'### Perubahan:\n' +
'- [feature] Deskripsi fitur baru\n' +
'- [improvement] Deskripsi peningkatan\n' +
'- [fix] Deskripsi perbaikan bug';

            const promptText = this.importFormat === 'json' ? jsonPrompt : mdPrompt;
            navigator.clipboard.writeText(promptText);
            this.copiedPrompt = true;
            setTimeout(() => this.copiedPrompt = false, 2500);
        },

        parseAndPopulate() {
            if (!this.rawImportText.trim()) return;

            try {
                if (this.importFormat === 'json') {
                    const cleanJson = this.rawImportText.replace(/^```(?:json)?\s*|\s*```$/gi, '').trim();
                    const parsed = JSON.parse(cleanJson);
                    const data = Array.isArray(parsed) ? parsed[0] : parsed;

                    if (data.version) this.version = data.version;
                    if (data.title) this.title = data.title;
                    if (data.type) this.type = data.type;
                    if (data.release_date) this.release_date = data.release_date;
                    if (data.summary) this.summary = data.summary;

                    if (Array.isArray(data.changes) && data.changes.length > 0) {
                        this.changes = data.changes.map(c => {
                            if (typeof c === 'string') return { type: 'feature', text: c };
                            return { type: c.type || 'feature', text: c.text || '' };
                        });
                    }
                } else {
                    const text = this.rawImportText.trim();
                    const lines = text.split('\n');
                    let firstLine = lines.shift() || '';

                    const vMatch = firstLine.match(/^#+\s*(v?\d+\.\d+(?:\.\d+)?)(?:\s*[:-]\s*(.+))?/i);
                    if (vMatch) {
                        this.version = vMatch[1];
                        if (vMatch[2]) this.title = vMatch[2].trim();
                    }

                    let newChanges = [];
                    let currentType = 'feature';
                    let summaryArr = [];

                    lines.forEach(line => {
                        line = line.trim();
                        if (!line) return;

                        const dateMatch = line.match(/(?:\*\*|\*)?(?:tanggal|date)(?:\*\*|\*)?\s*:\s*(\d{4}-\d{2}-\d{2})/i);
                        if (dateMatch) { this.release_date = dateMatch[1]; return; }

                        const typeMatch = line.match(/(?:\*\*|\*)?(?:tipe|type)(?:\*\*|\*)?\s*:\s*(major|minor|patch|security)/i);
                        if (typeMatch) { this.type = typeMatch[1].toLowerCase(); return; }

                        const sumMatch = line.match(/(?:\*\*|\*)?(?:ringkasan|summary)(?:\*\*|\*)?\s*:\s*(.+)/i);
                        if (sumMatch) { summaryArr.push(sumMatch[1].trim()); return; }

                        const subMatch = line.match(/^#+\s*(fitur|feature|peningkatan|improvement|perbaikan|fix|bug|keamanan|security)/i);
                        if (subMatch) {
                            const sub = subMatch[1].toLowerCase();
                            if (sub.includes('fix') || sub.includes('perbaikan') || sub.includes('bug')) currentType = 'fix';
                            else if (sub.includes('improve') || sub.includes('peningkatan')) currentType = 'improvement';
                            else if (sub.includes('sec') || sub.includes('keamanan')) currentType = 'security';
                            else currentType = 'feature';
                            return;
                        }

                        const bulletMatch = line.match(/^[-*+]\s+(?:\[(feature|improvement|fix|security)\]\s*)?(.+)/i);
                        if (bulletMatch) {
                            const itemType = bulletMatch[1] ? bulletMatch[1].toLowerCase() : currentType;
                            const itemText = bulletMatch[2].trim();
                            if (itemText) newChanges.push({ type: itemType, text: itemText });
                        } else if (!line.startsWith('#')) {
                            if (newChanges.length === 0) summaryArr.push(line);
                        }
                    });

                    if (summaryArr.length > 0) this.summary = summaryArr.join('\n');
                    if (newChanges.length > 0) this.changes = newChanges;
                }

                this.showImportModal = false;
                this.rawImportText = '';
            } catch (e) {
                alert('Gagal memproses data rilis: ' + e.message);
            }
        },

        addChange() {
            this.changes.push({ type: 'feature', text: '' });
        },
        removeChange(index) {
            if (this.changes.length > 1) {
                this.changes.splice(index, 1);
            }
        }
    }));
});
</script>
@endsection
