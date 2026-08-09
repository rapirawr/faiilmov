@extends('layouts.admin')

@section('title', 'Manajemen Changelog & Updates | faiiladmin')
@section('page_title', 'Manajemen Changelog & Catatan Rilis')

@section('content')
<div class="space-y-6" x-data="changelogIndexImporter">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('admin.changelogs.index') }}" class="flex items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 rounded-xl border border-white/10 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari versi atau judul rilis..." 
                       class="w-full bg-transparent py-2 text-xs text-white placeholder-zinc-500 border-none outline-none focus:ring-0">
            </div>
        </form>

        <div class="flex items-center gap-2">
            <!-- AI Import Modal Button -->
            <button type="button" @click="showImportModal = true" class="px-3.5 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 text-purple-300 border border-purple-500/30 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer shadow-lg shadow-purple-500/10">
                <i data-lucide="sparkles" class="w-4 h-4 text-purple-400"></i>
                <span>Import AI (JSON / Markdown)</span>
            </button>

            <a href="{{ route('changelog') }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-1.5 border border-white/10 transition-all">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Halaman Publik</span>
            </a>

            <a href="{{ route('admin.changelogs.create') }}" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Catatan Rilis</span>
            </a>
        </div>
    </div>

    <!-- Changelogs Table -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-zinc-400 uppercase text-[10px] font-bold border-b border-white/10">
                    <tr>
                        <th class="px-4 py-3.5">Versi</th>
                        <th class="px-4 py-3.5">Judul Rilis</th>
                        <th class="px-4 py-3.5">Tipe Update</th>
                        <th class="px-4 py-3.5">Tgl Rilis</th>
                        <th class="px-4 py-3.5">Jumlah Poin</th>
                        <th class="px-4 py-3.5">Status Publikasi</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($changelogs as $log)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3.5 font-mono font-bold text-amber-400 text-sm">
                                {{ $log->version }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-white max-w-xs truncate">
                                {{ $log->title }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if($log->type === 'major')
                                    <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 font-extrabold text-[10px] uppercase border border-purple-500/30">Major</span>
                                @elseif($log->type === 'minor')
                                    <span class="px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 font-extrabold text-[10px] uppercase border border-blue-500/30">Minor</span>
                                @elseif($log->type === 'security')
                                    <span class="px-2 py-0.5 rounded-full bg-red-500/20 text-red-300 font-extrabold text-[10px] uppercase border border-red-500/30">Security</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-extrabold text-[10px] uppercase border border-emerald-500/30">Patch</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-300 font-mono">
                                {{ $log->release_date ? $log->release_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-zinc-400">
                                {{ is_array($log->changes) ? count($log->changes) : 0 }} Poin
                            </td>
                            <td class="px-4 py-3.5">
                                <form action="{{ route('admin.changelogs.toggle_publish', $log->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-colors {{ $log->is_published ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-zinc-800 text-zinc-400 border border-white/10 hover:bg-zinc-700' }}">
                                        {{ $log->is_published ? 'Publik' : 'Draft / Tersembunyi' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.changelogs.edit', $log->id) }}" class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>

                                    <form action="{{ route('admin.changelogs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Hapus catatan rilis {{ $log->version }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20" title="Hapus">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">Belum ada catatan rilis changelog.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($changelogs->hasPages())
            <div class="p-4 border-t border-white/10">
                {{ $changelogs->links() }}
            </div>
        @endif
    </div>

    <!-- AI Import Modal -->
    <div x-show="showImportModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
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
                              class="w-full bg-zinc-950 border border-white/10 rounded-2xl p-4 text-xs font-mono text-white focus:outline-none focus:border-purple-500"></textarea>
                </div>
            </div>

            <!-- Modal Action Footer -->
            <form action="{{ route('admin.changelogs.import') }}" method="POST" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-white/10">
                @csrf
                <input type="hidden" name="format" :value="importFormat">
                <input type="hidden" name="content" :value="rawImportText">
                
                <label class="flex items-center gap-2 text-xs text-zinc-300 cursor-pointer">
                    <input type="checkbox" name="auto_publish" value="1" checked class="w-4 h-4 rounded border-white/20 bg-zinc-950 text-purple-500">
                    <span>Langsung Publikasikan</span>
                </label>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" :disabled="!rawImportText.trim()" class="px-5 py-2.5 rounded-xl bg-purple-500 hover:bg-purple-400 text-white text-xs font-bold shadow-lg shadow-purple-500/20 transition-colors disabled:opacity-40 cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        <span>Import & Simpan ke DB</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('changelogIndexImporter', () => ({
        showImportModal: false,
        importFormat: 'json',
        rawImportText: '',
        copiedPrompt: false,

        copyPrompt() {
            const today = new Date().toISOString().split('T')[0];
            const jsonPrompt = 'Buatkan catatan rilis (changelog) terbaru untuk aplikasi faiilmov dalam format JSON persis seperti berikut:\n\n' +
'{\n' +
'  "version": "v2.6.0",\n' +
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
'# v2.6.0 - Judul Pembaruan Singkat\n' +
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
        }
    }));
});
</script>
@endsection
