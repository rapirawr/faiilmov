@extends('layouts.admin')

@section('title', 'PHP Script Runner - Admin')

@section('content')
<div x-data="scriptRunner()" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-zinc-900/80 backdrop-blur-xl p-6 rounded-3xl border border-white/10 shadow-xl">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400">
                    <i data-lucide="terminal" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold font-serif text-white tracking-tight flex items-center gap-2">
                        <span>PHP Script Runner & Playground</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-sans font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            PHP {{ PHP_VERSION }}
                        </span>
                    </h1>
                    <p class="text-xs text-zinc-400 mt-0.5">Tulis, jalankan, dan simpan script PHP kustom kapan saja langsung dari admin dashboard.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" @click="resetForm()" class="px-4 py-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white font-semibold text-xs transition-colors border border-white/10 flex items-center gap-2 cursor-pointer">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Script Baru</span>
            </button>
            
            <button type="button" 
                    @click="executeScript()" 
                    :disabled="isExecuting"
                    class="px-5 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-zinc-950 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer shadow-lg border border-amber-400/40">
                <template x-if="!isExecuting">
                    <span class="flex items-center gap-2">
                        <i data-lucide="play" class="w-4 h-4 fill-zinc-950"></i>
                        <span>Jalankan Script (Ctrl+Enter)</span>
                    </span>
                </template>
                <template x-if="isExecuting">
                    <span class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        <span>Mengeksekusi...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- Main Content Area: Split View -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column: Saved Scripts List (4 cols) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-zinc-900/60 backdrop-blur-xl p-5 rounded-3xl border border-white/10 space-y-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400 flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-amber-400"></i>
                        <span>Script Tersimpan ({{ $scripts->count() }})</span>
                    </h3>
                </div>

                <!-- Search Input for Saved Scripts -->
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari script tersimpan..." 
                           class="w-full bg-zinc-950/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500/50">
                </div>

                <!-- List of Saved Scripts -->
                <div class="space-y-2 max-h-[500px] overflow-y-auto pr-1 no-scrollbar">
                    <template x-for="script in filteredScripts" :key="script.id">
                        <div @click="loadScript(script)" 
                             :class="selectedScriptId === script.id ? 'bg-amber-500/10 border-amber-500/40' : 'bg-white/5 hover:bg-white/10 border-white/10'"
                             class="p-3.5 rounded-2xl border transition-all cursor-pointer group relative">
                            
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-bold text-white group-hover:text-amber-300 truncate" x-text="script.title"></h4>
                                    <p class="text-[11px] text-zinc-400 line-clamp-1 mt-0.5" x-text="script.description || 'Tidak ada deskripsi'"></p>
                                </div>
                                <button type="button" 
                                        @click.stop="confirmDelete(script)" 
                                        class="opacity-0 group-hover:opacity-100 p-1 text-zinc-500 hover:text-red-400 transition-all rounded-lg hover:bg-white/5"
                                        title="Hapus script">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>

                            <div class="flex items-center justify-between gap-2 mt-3 pt-2 border-t border-white/5 text-[10px] text-zinc-500">
                                <div class="flex items-center gap-1.5">
                                    <template x-if="script.last_run_status === 'success'">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    </template>
                                    <template x-if="script.last_run_status === 'error'">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    </template>
                                    <template x-if="!script.last_run_status || script.last_run_status === 'pending'">
                                        <span class="w-2 h-2 rounded-full bg-zinc-600"></span>
                                    </template>
                                    <span x-text="script.last_run_at_human || 'Belum dijalankan'"></span>
                                </div>
                                <template x-if="script.execution_time_ms">
                                    <span class="font-mono text-zinc-400" x-text="script.execution_time_ms + 'ms'"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredScripts.length === 0" class="py-8 text-center text-xs text-zinc-500">
                        Tidak ada script ditemukan.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Code Editor & Output Console (8 cols) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Code Editor Card -->
            <div class="bg-zinc-900/60 backdrop-blur-xl p-6 rounded-3xl border border-white/10 space-y-4 shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex-1 space-y-2">
                        <input type="text" 
                               x-model="form.title" 
                               placeholder="Judul Script (contoh: Sync Film Spider-Man)" 
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm font-bold text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500/50">
                        <input type="text" 
                               x-model="form.description" 
                               placeholder="Deskripsi singkat (opsional)" 
                               class="w-full bg-zinc-950/60 border border-white/10 rounded-xl px-4 py-1.5 text-xs text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-amber-500/40">
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" 
                                @click="saveScript()" 
                                class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer shadow">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </div>

                <!-- Code Editor Area -->
                <div class="relative rounded-2xl overflow-hidden border border-white/10 bg-zinc-950">
                    <div class="flex items-center justify-between px-4 py-2 bg-zinc-900/80 border-b border-white/10 text-[11px] text-zinc-400 font-mono">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
                            <span class="ml-2 font-bold text-zinc-300">PHP Editor</span>
                        </div>
                        <span>Gunakan sintaks PHP standar (tanpa tag `&lt;?php`)</span>
                    </div>

                    <textarea x-model="form.code"
                              x-ref="codeTextarea"
                              @keydown.tab.prevent="insertTab($event)"
                              @keydown.ctrl.enter.prevent="executeScript()"
                              @keydown.cmd.enter.prevent="executeScript()"
                              rows="12"
                              spellcheck="false"
                              class="w-full bg-zinc-950 text-emerald-400 font-mono text-xs p-4 leading-relaxed outline-none resize-y border-0 focus:ring-0">
                    </textarea>
                </div>
            </div>

            <!-- Output Terminal Console -->
            <div class="bg-zinc-950 rounded-3xl border border-white/10 overflow-hidden shadow-2xl space-y-0">
                <div class="flex items-center justify-between px-5 py-3 bg-zinc-900/90 border-b border-white/10">
                    <div class="flex items-center gap-2">
                        <i data-lucide="terminal-square" class="w-4 h-4 text-emerald-400"></i>
                        <span class="text-xs font-bold font-serif text-white">Konsol Output Terminal</span>
                        
                        <template x-if="lastResult">
                            <div class="flex items-center gap-2 ml-3">
                                <span :class="lastResult.status === 'success' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-red-500/20 text-red-300 border-red-500/30'" 
                                      class="px-2 py-0.5 rounded-md text-[10px] font-bold font-mono border">
                                    <span x-text="lastResult.status.toUpperCase()"></span>
                                </span>
                                <span class="text-[10px] text-zinc-400 font-mono" x-text="lastResult.duration_ms + ' ms'"></span>
                                <span class="text-[10px] text-zinc-500 font-mono" x-text="'| ' + lastResult.memory_kb + ' KB'"></span>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="clearConsole()" class="text-[11px] text-zinc-400 hover:text-white flex items-center gap-1 transition-colors">
                        <i data-lucide="eraser" class="w-3.5 h-3.5"></i>
                        <span>Bersihkan Konsol</span>
                    </button>
                </div>

                <div class="p-5 font-mono text-xs leading-relaxed max-h-[350px] overflow-y-auto no-scrollbar whitespace-pre-wrap select-text"
                     :class="lastResult && lastResult.status === 'error' ? 'text-red-400' : 'text-zinc-200'"
                     x-text="outputConsole || '> Belum ada eksekusi script. Klik tombol [Jalankan Script] untuk memulai.'">
                </div>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
function scriptRunner() {
    return {
        scripts: @json($scripts),
        searchQuery: '',
        selectedScriptId: null,
        isExecuting: false,
        outputConsole: '',
        lastResult: null,

        form: {
            script_id: null,
            title: 'Script PHP Kustom Baru',
            description: '',
            code: `// Tulis kode PHP kustom di sini...\n$totalFilm = \\App\\Models\\Film::count();\necho "Total film di database: " . $totalFilm . "\\n";`,
        },

        get filteredScripts() {
            if (!this.searchQuery.trim()) return this.scripts;
            const q = this.searchQuery.toLowerCase();
            return this.scripts.filter(s => 
                s.title.toLowerCase().includes(q) || 
                (s.description && s.description.toLowerCase().includes(q))
            );
        },

        resetForm() {
            this.selectedScriptId = null;
            this.form = {
                script_id: null,
                title: 'Script PHP Kustom Baru',
                description: '',
                code: `// Tulis kode PHP kustom di sini...\n$totalFilm = \\App\\Models\\Film::count();\necho "Total film di database: " . $totalFilm . "\\n";`,
            };
            this.outputConsole = '';
            this.lastResult = null;
        },

        loadScript(script) {
            this.selectedScriptId = script.id;
            this.form = {
                script_id: script.id,
                title: script.title,
                description: script.description || '',
                code: script.code,
            };
            if (script.last_run_output) {
                this.outputConsole = script.last_run_output;
                this.lastResult = {
                    status: script.last_run_status || 'success',
                    duration_ms: script.execution_time_ms || 0,
                    memory_kb: 0,
                };
            }
        },

        insertTab(event) {
            const textarea = this.$refs.codeTextarea;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;

            this.form.code = this.form.code.substring(0, start) + "    " + this.form.code.substring(end);
            this.$nextTick(() => {
                textarea.selectionStart = textarea.selectionEnd = start + 4;
            });
        },

        async executeScript() {
            if (this.isExecuting) return;
            this.isExecuting = true;
            this.outputConsole = '> Mengeksekusi script PHP... Mohon tunggu...\n';

            try {
                const res = await fetch('{{ route('admin.scripts.execute') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        code: this.form.code,
                        script_id: this.form.script_id,
                    }),
                });

                const data = await res.json();
                this.lastResult = data;
                this.outputConsole = data.output;

                // Update last run stats in local script state if selected
                if (this.selectedScriptId) {
                    const found = this.scripts.find(s => s.id === this.selectedScriptId);
                    if (found) {
                        found.last_run_at_human = 'Baru Saja';
                        found.last_run_status = data.status;
                        found.execution_time_ms = data.duration_ms;
                        found.last_run_output = data.output;
                    }
                }
            } catch (e) {
                this.lastResult = { status: 'error', duration_ms: 0, memory_kb: 0 };
                this.outputConsole = `ERROR EXECUTION: ${e.message}`;
            } finally {
                this.isExecuting = false;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        async saveScript() {
            if (!this.form.title.trim() || !this.form.code.trim()) {
                alert('Judul dan Kode PHP tidak boleh kosong!');
                return;
            }

            try {
                const res = await fetch('{{ route('admin.scripts.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();
                if (data.success) {
                    alert('Script berhasil disimpan!');
                    // Refresh script list
                    location.reload();
                }
            } catch (e) {
                alert('Gagal menyimpan script: ' + e.message);
            }
        },

        async confirmDelete(script) {
            if (!confirm(`Apakah Anda yakin ingin menghapus script "${script.title}"?`)) {
                return;
            }

            try {
                const res = await fetch(`/admin/scripts/${script.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await res.json();
                if (data.success) {
                    this.scripts = this.scripts.filter(s => s.id !== script.id);
                    if (this.selectedScriptId === script.id) {
                        this.resetForm();
                    }
                }
            } catch (e) {
                alert('Gagal menghapus script: ' + e.message);
            }
        },

        clearConsole() {
            this.outputConsole = '';
            this.lastResult = null;
        }
    };
}
</script>
@endpush
@endsection
