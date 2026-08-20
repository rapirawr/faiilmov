@extends('layouts.admin')

@section('title', 'Manajemen Changelog & Updates | faiiladmin')
@section('page_title', 'Manajemen Changelog & Catatan Rilis')

@section('content')
<div class="space-y-6" x-data="changelogIndexImporter">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('admin.changelogs.index') }}" class="flex items-center gap-3 flex-1">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-zinc-800 bg-zinc-900 focus-within:border-amber-500 transition-all flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari versi atau judul rilis..." 
                       class="w-full bg-transparent text-xs text-white placeholder-zinc-500 border-none outline-none focus:ring-0">
            </div>

            @if(request('search'))
                <a href="{{ route('admin.changelogs.index') }}" class="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white transition-colors" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- AI Import Modal Button -->
            <button type="button" @click="showImportModal = true" class="px-3.5 py-2 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer shadow-lg shadow-purple-500/10">
                <i data-lucide="sparkles" class="w-4 h-4 text-purple-400"></i>
                <span>Import AI (JSON / MD)</span>
            </button>

            <a href="{{ route('changelog') }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs flex items-center gap-1.5 border border-zinc-700 transition-all">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Halaman Publik</span>
            </a>

            <a href="{{ route('admin.changelogs.create') }}" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Catatan Rilis</span>
            </a>
        </div>
    </div>

    <!-- Changelogs Table -->
    <div class="bg-zinc-900/90 border border-zinc-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-950 text-zinc-400 uppercase text-[10px] font-bold border-b border-zinc-800 tracking-wider">
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
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($changelogs as $log)
                        <tr class="hover:bg-zinc-800/40 transition-colors group">
                            <td class="px-4 py-3.5 font-mono font-bold text-amber-400 text-xs">
                                {{ $log->version }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-white max-w-xs truncate text-xs">
                                {{ $log->title }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if($log->type === 'major')
                                    <span class="px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-300 font-extrabold text-[10px] uppercase border border-purple-500/30">Major</span>
                                @elseif($log->type === 'minor')
                                    <span class="px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-300 font-extrabold text-[10px] uppercase border border-sky-500/30">Minor</span>
                                @elseif($log->type === 'security')
                                    <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 font-extrabold text-[10px] uppercase border border-rose-500/30">Security</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-extrabold text-[10px] uppercase border border-emerald-500/30">Patch</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-zinc-400 font-mono text-[11px]">
                                {{ $log->release_date ? $log->release_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-zinc-300 text-xs">
                                {{ is_array($log->changes) ? count($log->changes) : 0 }} Poin
                            </td>
                            <td class="px-4 py-3.5">
                                <form action="{{ route('admin.changelogs.toggle_publish', $log->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition-colors {{ $log->is_published ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-zinc-800 text-zinc-400 border border-zinc-700 hover:bg-zinc-700' }}">
                                        {{ $log->is_published ? 'Publik' : 'Draft / Tersembunyi' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.changelogs.edit', $log->id) }}" class="p-1.5 rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors" title="Edit Rilis">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>

                                    <form action="{{ route('admin.changelogs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan rilis {{ $log->version }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Hapus Rilis">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-zinc-500">Belum ada catatan rilis changelog.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($changelogs->hasPages())
            <div class="p-4 border-t border-zinc-800 bg-zinc-950/40">
                {{ $changelogs->links() }}
            </div>
        @endif
    </div>

    <!-- AI Import Modal -->
    <template x-teleport="body">
        <div x-show="showImportModal" 
             x-cloak 
             class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="w-full max-w-2xl bg-zinc-900 border border-zinc-800 rounded-3xl p-6 shadow-2xl space-y-5" @click.away="showImportModal = false">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white font-['Outfit']">Import Catatan Rilis dari AI</h3>
                            <p class="text-xs text-zinc-400">Salin prompt untuk AI atau tempel langsung data rilis JSON / Markdown.</p>
                        </div>
                    </div>
                    <button type="button" @click="showImportModal = false" class="text-zinc-400 hover:text-white p-1 cursor-pointer">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Format Chooser & Prompt Copy -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-zinc-300">Pilih Format Data:</span>
                            <div class="inline-flex rounded-xl bg-zinc-950 p-1 border border-zinc-800 text-xs">
                                <button type="button" 
                                        @click="importFormat = 'json'" 
                                        :class="importFormat === 'json' ? 'bg-purple-500 text-white font-bold' : 'text-zinc-400 hover:text-white'"
                                        class="px-3 py-1 rounded-lg transition-colors cursor-pointer">
                                    JSON
                                </button>
                                <button type="button" 
                                        @click="importFormat = 'markdown'" 
                                        :class="importFormat === 'markdown' ? 'bg-purple-500 text-white font-bold' : 'text-zinc-400 hover:text-white'"
                                        class="px-3 py-1 rounded-lg transition-colors cursor-pointer">
                                    Markdown
                                </button>
                            </div>
                        </div>

                        <button type="button" @click="copyPrompt()" class="px-3.5 py-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer">
                            <i :data-lucide="copiedPrompt ? 'check' : 'copy'" class="w-3.5 h-3.5"></i>
                            <span x-text="copiedPrompt ? 'Prompt Berhasil Disalin!' : 'Salin Prompt AI (' + importFormat.toUpperCase() + ')'"></span>
                        </button>
                    </div>

                    <!-- Textarea Paste -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tempel Output dari AI di bawah ini:</label>
                        <textarea x-model="rawImportText" 
                                  rows="9" 
                                  placeholder="Tempel teks JSON atau Markdown dari ChatGPT/Gemini/Claude di sini..."
                                  class="w-full bg-zinc-950 border border-zinc-800 rounded-2xl p-4 text-xs font-mono text-white focus:outline-none focus:border-purple-500 admin-scrollbar"></textarea>
                    </div>
                </div>

                <!-- Modal Action Footer -->
                <form action="{{ route('admin.changelogs.import') }}" method="POST" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-zinc-800">
                    @csrf
                    <input type="hidden" name="format" :value="importFormat">
                    <input type="hidden" name="content" :value="rawImportText">
                    
                    <label class="flex items-center gap-2 text-xs text-zinc-300 cursor-pointer select-none">
                        <input type="checkbox" name="auto_publish" value="1" checked class="w-4 h-4 rounded border-zinc-700 bg-zinc-950 text-purple-500">
                        <span>Langsung Publikasikan</span>
                    </label>

                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white text-xs font-bold cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" :disabled="!rawImportText.trim()" class="px-5 py-2 rounded-xl bg-purple-500 hover:bg-purple-400 text-white text-xs font-bold shadow-lg shadow-purple-500/20 transition-all disabled:opacity-40 cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                            <span>Import & Simpan ke DB</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

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
            const targetVersion = @json($nextVersion);
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
        }
    }));
});
</script>
@endsection
