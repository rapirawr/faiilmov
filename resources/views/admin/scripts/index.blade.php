@extends('layouts.admin')

@section('title', 'PHP Script Runner & Playground | faiiladmin')
@section('page_title', 'PHP Script Runner & AI Playground')

@section('content')
<div x-data="scriptRunner()" class="space-y-6 relative" @keydown.window.ctrl.s.prevent="saveScript()" @keydown.window.cmd.s.prevent="saveScript()">

    <!-- Floating In-App Toast System (Replaces Native alert) -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                 class="pointer-events-auto p-4 rounded-2xl shadow-2xl border flex items-center justify-between gap-3 text-xs font-semibold backdrop-blur-xl"
                 :class="{
                     'bg-zinc-900/95 text-emerald-300 border-emerald-500/30': toast.type === 'success',
                     'bg-zinc-900/95 text-rose-300 border-rose-500/30': toast.type === 'error',
                     'bg-zinc-900/95 text-amber-300 border-amber-500/30': toast.type === 'warning',
                     'bg-zinc-900/95 text-zinc-200 border-zinc-700': toast.type === 'info'
                 }">
                <div class="flex items-center gap-2.5 min-w-0">
                    <template x-if="toast.type === 'success'">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-400 shrink-0"></i>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400 shrink-0"></i>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <i data-lucide="info" class="w-4 h-4 text-zinc-400 shrink-0"></i>
                    </template>
                    <span class="truncate" x-text="toast.message"></span>
                </div>
                <button type="button" @click="toast.visible = false" class="text-zinc-500 hover:text-white p-1 transition-colors cursor-pointer shrink-0">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </template>
    </div>

    <!-- Header Section Card -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-zinc-900/90 p-6 rounded-3xl border border-white/10 shadow-xl">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-zinc-800 border border-zinc-700 text-white flex items-center justify-center shrink-0">
                    <i data-lucide="terminal" class="w-5 h-5 text-amber-400"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold font-['Outfit'] text-white flex items-center gap-2">
                        <span>PHP Script Runner & Playground</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30">
                            PHP {{ PHP_VERSION }}
                        </span>
                    </h1>
                    <p class="text-xs text-zinc-400 mt-0.5">Tulis, jalankan, dan simpan script PHP kustom langsung di runtime server dengan aman.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" 
                    @click="resetForm()" 
                    class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white font-bold text-xs transition-colors border border-zinc-700 flex items-center gap-2 cursor-pointer">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Script Baru</span>
            </button>
            
            <button type="button" 
                    @click="promptExecution()" 
                    :disabled="isExecuting"
                    class="px-5 py-2.5 rounded-xl bg-white hover:bg-zinc-200 disabled:opacity-50 text-zinc-950 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer shadow-lg shadow-white/10">
                <template x-if="!isExecuting">
                    <span class="flex items-center gap-2">
                        <i data-lucide="play" class="w-4 h-4 fill-zinc-950"></i>
                        <span>Jalankan Script (Ctrl+Enter)</span>
                    </span>
                </template>
                <template x-if="isExecuting">
                    <span class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-zinc-950"></i>
                        <span>Mengeksekusi...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- 2-Step Confirmation Security Modal -->
    <div x-show="confirmModal" x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
        <div @click.away="confirmModal = false" class="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-amber-500/30 text-left space-y-4 shadow-2xl text-white">
            <div class="flex items-center gap-3 text-amber-400 border-b border-zinc-800 pb-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="font-bold text-white text-sm font-['Outfit']">Konfirmasi Eksekusi Kode PHP</h4>
                    <p class="text-[11px] text-zinc-400">Peringatan Keamanan Runtime Server</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-zinc-950/80 border border-zinc-800 space-y-2 text-xs text-zinc-300">
                <p>Anda akan mengeksekusi kode PHP kustom langsung pada runtime live:</p>
                <div class="p-2 rounded-xl bg-zinc-900 font-mono text-[11px] text-amber-300 border border-zinc-800 truncate" x-text="form.title || 'Custom PHP Snippet'"></div>
                <p class="text-[11px] text-zinc-500">Aksi eksekusi ini akan otomatis tercatat ke <strong>AdminActivityLog</strong>.</p>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-zinc-800">
                <label class="flex items-center gap-2 text-[11px] text-zinc-400 cursor-pointer select-none">
                    <input type="checkbox" x-model="bypassConfirm" @change="saveBypassSetting()" class="rounded border-zinc-700 bg-zinc-950 text-amber-500 focus:ring-0">
                    <span>Jangan tanya lagi</span>
                </label>

                <div class="flex items-center gap-2">
                    <button type="button" @click="confirmModal = false" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer">Batal</button>
                    <button type="button" @click="confirmAndRun()" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-xs font-bold text-zinc-950 shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2 cursor-pointer">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                        <span>Eksekusi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Generate Panel Card -->
    <div class="bg-zinc-900/90 rounded-3xl border border-white/10 shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-800">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-xl bg-zinc-800 border border-zinc-700 text-white shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        AI Script Generator
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-zinc-800 text-zinc-300 border border-zinc-700 font-mono">AI Powered</span>
                    </h2>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Deskripsikan instruksi dalam bahasa natural, AI akan menyusun kode PHP siap jalan.</p>
                </div>
            </div>
            <button type="button" @click="aiPanelOpen = !aiPanelOpen"
                    class="p-2 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition-all cursor-pointer">
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="aiPanelOpen ? '' : '-rotate-90'"></i>
            </button>
        </div>

        <div x-show="aiPanelOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="p-6 space-y-4">

            <!-- Prompt Examples -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] text-zinc-400 self-center shrink-0">Contoh prompt:</span>
                <template x-for="example in aiPromptExamples" :key="example">
                    <button type="button" @click="aiPrompt = example"
                            class="px-3 py-1 rounded-full text-[11px] bg-zinc-950 hover:bg-zinc-800 text-zinc-300 hover:text-white border border-zinc-800 transition-colors cursor-pointer"
                            x-text="example"></button>
                </template>
            </div>

            <!-- Prompt Input Row -->
            <div class="flex gap-3 items-end">
                <div class="flex-1">
                    <textarea x-model="aiPrompt"
                              @keydown.ctrl.enter.prevent="generateWithAI()"
                              placeholder="Contoh: Buatkan script untuk sinkronisasi film Spider-Man dari API MovieBox dan tampilkan hasilnya..."
                              rows="2"
                              class="w-full bg-zinc-950 border border-zinc-800 focus:border-white/30 rounded-2xl px-4 py-3 text-xs text-white placeholder-zinc-500 focus:outline-none resize-none transition-colors font-medium"></textarea>
                </div>

                <button type="button"
                        @click="generateWithAI()"
                        :disabled="isGenerating || !aiPrompt.trim()"
                        class="px-5 py-3 rounded-2xl bg-white hover:bg-zinc-200 disabled:opacity-40 disabled:cursor-not-allowed text-zinc-950 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer shadow-lg shrink-0 h-fit">
                    <template x-if="!isGenerating">
                        <span class="flex items-center gap-2">
                            <i data-lucide="wand-2" class="w-4 h-4"></i>
                            <span>Generate (Ctrl+Enter)</span>
                        </span>
                    </template>
                    <template x-if="isGenerating">
                        <span class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-zinc-950"></i>
                            <span>Menyusun Kode...</span>
                        </span>
                    </template>
                </button>
            </div>

            <!-- AI Status / Error / Engine Info -->
            <template x-if="aiError">
                <div class="flex items-start gap-3 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <span x-text="aiError"></span>
                </div>
            </template>

            <template x-if="aiLastModel">
                <div class="flex items-center gap-4 text-[11px] text-zinc-400 font-mono">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span class="text-emerald-400 font-medium font-sans">Kode PHP siap digunakan di editor!</span>
                    </span>
                    <span x-text="'Engine: ' + aiLastModel"></span>
                    <template x-if="aiLastTokens > 0">
                        <span x-text="'Tokens: ' + aiLastTokens"></span>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Main Content Area: Split View -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column: Saved Scripts List (4 cols) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-zinc-900/90 p-5 rounded-3xl border border-white/10 space-y-4 shadow-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-300 flex items-center gap-2">
                        <i data-lucide="bookmark" class="w-4 h-4 text-amber-400"></i>
                        <span>Script Tersimpan (<span x-text="scripts.length"></span>)</span>
                    </h3>
                </div>

                <!-- Search Input for Saved Scripts -->
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari script tersimpan..." 
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/30">
                </div>

                <!-- List of Saved Scripts -->
                <div class="space-y-2 max-h-[560px] overflow-y-auto pr-1 no-scrollbar">
                    <template x-for="script in filteredScripts" :key="script.id">
                        <div @click="loadScript(script)" 
                             :class="selectedScriptId === script.id ? 'bg-amber-500/10 border-amber-500/40 shadow-md' : 'bg-zinc-950 hover:bg-zinc-800/50 border-zinc-800'"
                             class="p-3.5 rounded-2xl border transition-all cursor-pointer group relative">
                            
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-bold text-white group-hover:text-amber-300 truncate" x-text="script.title"></h4>
                                    <p class="text-[11px] text-zinc-400 line-clamp-1 mt-0.5" x-text="script.description || 'Tidak ada deskripsi'"></p>
                                </div>
                                <button type="button" 
                                        @click.stop="confirmDelete(script)" 
                                        class="opacity-0 group-hover:opacity-100 p-1 text-zinc-500 hover:text-rose-400 transition-all rounded-lg hover:bg-zinc-800 cursor-pointer shrink-0"
                                        title="Hapus script">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>

                            <div class="flex items-center justify-between gap-2 mt-3 pt-2 border-t border-zinc-800/80 text-[10px] text-zinc-500 font-mono">
                                <div class="flex items-center gap-1.5">
                                    <template x-if="script.last_run_status === 'success'">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                    </template>
                                    <template x-if="script.last_run_status === 'error'">
                                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
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
                        Tidak ada script yang cocok.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Code Editor & Output Console (8 cols) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Code Editor Card -->
            <div class="bg-zinc-900/90 p-6 rounded-3xl border border-white/10 space-y-4 shadow-xl">
                
                <!-- Title & Action Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex-1 space-y-2">
                        <input type="text" 
                               x-model="form.title" 
                               placeholder="Judul Script (contoh: Sync Film Spider-Man)" 
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2 text-xs font-bold text-white placeholder-zinc-500 focus:outline-none focus:border-white/30">
                        <input type="text" 
                               x-model="form.description" 
                               placeholder="Deskripsi singkat (opsional)" 
                               class="w-full bg-zinc-950/60 border border-zinc-800 rounded-xl px-4 py-1.5 text-xs text-zinc-300 placeholder-zinc-600 focus:outline-none focus:border-white/30">
                    </div>
                    
                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Save Button -->
                        <button type="button" 
                                @click="saveScript()" 
                                class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs transition-colors flex items-center gap-2 cursor-pointer border border-zinc-700 shadow-sm"
                                title="Simpan ke daftar script (Ctrl+S)">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Snippets Bar -->
                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                    <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-wider mr-1">Snippets:</span>
                    <button type="button" @click="insertSnippet('stats')" class="px-2.5 py-1 rounded-lg bg-zinc-950 hover:bg-zinc-800 border border-zinc-800 text-[10px] font-mono text-zinc-400 hover:text-white transition-colors cursor-pointer">
                        DB Stats
                    </button>
                    <button type="button" @click="insertSnippet('sync')" class="px-2.5 py-1 rounded-lg bg-zinc-950 hover:bg-zinc-800 border border-zinc-800 text-[10px] font-mono text-zinc-400 hover:text-white transition-colors cursor-pointer">
                        Sync Film Live
                    </button>
                    <button type="button" @click="insertSnippet('top_rated')" class="px-2.5 py-1 rounded-lg bg-zinc-950 hover:bg-zinc-800 border border-zinc-800 text-[10px] font-mono text-zinc-400 hover:text-white transition-colors cursor-pointer">
                        Top Rated
                    </button>
                    <button type="button" @click="insertSnippet('users')" class="px-2.5 py-1 rounded-lg bg-zinc-950 hover:bg-zinc-800 border border-zinc-800 text-[10px] font-mono text-zinc-400 hover:text-white transition-colors cursor-pointer">
                        Latest Users
                    </button>
                    <button type="button" @click="insertSnippet('clear_cache')" class="px-2.5 py-1 rounded-lg bg-zinc-950 hover:bg-zinc-800 border border-zinc-800 text-[10px] font-mono text-zinc-400 hover:text-white transition-colors cursor-pointer">
                        Clear Cache
                    </button>
                </div>

                <!-- Code Editor Area -->
                <div class="relative rounded-2xl overflow-hidden border border-zinc-800 bg-zinc-950">
                    <div class="flex items-center justify-between px-4 py-2 bg-zinc-900 border-b border-zinc-800 text-[11px] text-zinc-400 font-mono">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
                            <span class="ml-2 font-bold text-zinc-300">PHP Editor Runtime</span>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <span class="text-zinc-500 text-[10px]" x-text="'Baris: ' + (form.code.split('\n').length)"></span>
                            <button type="button" @click="copyCode()" class="hover:text-white flex items-center gap-1 transition-colors cursor-pointer">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span x-text="codeCopied ? 'Tersalin!' : 'Salin'"></span>
                            </button>
                        </div>
                    </div>

                    <textarea x-model="form.code"
                              x-ref="codeTextarea"
                              @keydown.tab.prevent="insertTab($event)"
                              @keydown.ctrl.enter.prevent="promptExecution()"
                              @keydown.cmd.enter.prevent="promptExecution()"
                              rows="13"
                              spellcheck="false"
                              placeholder="// Tulis kode PHP kustom di sini..."
                              class="w-full bg-zinc-950 text-emerald-400 font-mono text-xs p-4 leading-relaxed outline-none resize-y border-0 focus:ring-0">
                    </textarea>
                </div>

                <div class="flex items-center justify-between text-[11px] text-zinc-500">
                    <span>💡 Shortcut: <kbd class="px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-300 font-mono text-[10px]">Ctrl+Enter</kbd> Eksekusi, <kbd class="px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-300 font-mono text-[10px]">Ctrl+S</kbd> Simpan, <kbd class="px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-300 font-mono text-[10px]">Tab</kbd> Indentasi 4 spasi.</span>
                </div>
            </div>

            <!-- Output Terminal Console -->
            <div class="bg-zinc-950 rounded-3xl border border-zinc-800 overflow-hidden shadow-2xl space-y-0">
                <div class="flex items-center justify-between px-5 py-3 bg-zinc-900/90 border-b border-zinc-800">
                    <div class="flex items-center gap-2">
                        <i data-lucide="terminal" class="w-4 h-4 text-emerald-400"></i>
                        <span class="text-xs font-bold font-['Outfit'] text-white">Konsol Output Terminal</span>
                        
                        <template x-if="lastResult">
                            <div class="flex items-center gap-2 ml-3">
                                <span :class="lastResult.status === 'success' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30'" 
                                      class="px-2 py-0.5 rounded-md text-[10px] font-bold font-mono border">
                                    <span x-text="lastResult.status.toUpperCase()"></span>
                                </span>
                                <span class="text-[10px] text-zinc-400 font-mono" x-text="lastResult.duration_ms + ' ms'"></span>
                                <template x-if="lastResult.memory_kb > 0">
                                    <span class="text-[10px] text-zinc-500 font-mono" x-text="lastResult.memory_kb + ' KB'"></span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="copyOutput()" class="text-[11px] text-zinc-400 hover:text-white flex items-center gap-1 transition-colors cursor-pointer">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span x-text="outputCopied ? 'Tersalin!' : 'Salin Output'"></span>
                        </button>

                        <button type="button" @click="clearConsole()" class="text-[11px] text-zinc-400 hover:text-white flex items-center gap-1 transition-colors cursor-pointer">
                            <i data-lucide="eraser" class="w-3.5 h-3.5"></i>
                            <span>Bersihkan</span>
                        </button>
                    </div>
                </div>

                <div class="p-5 font-mono text-xs leading-relaxed max-h-[380px] overflow-y-auto scrollbar-thin whitespace-pre-wrap select-text"
                     :class="lastResult && lastResult.status === 'error' ? 'text-rose-400' : 'text-zinc-200'"
                     x-text="outputConsole || '> Belum ada eksekusi script. Tulis kode lalu tekan [Jalankan Script] atau Ctrl+Enter.'">
                </div>
            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('scriptRunner', () => ({
        scripts: @json($scripts),
        searchQuery: '',
        selectedScriptId: null,
        isExecuting: false,
        outputConsole: '',
        lastResult: null,

        aiPanelOpen: true,
        aiPrompt: '',
        isGenerating: false,
        aiError: null,
        aiLastTokens: 0,
        aiLastModel: '',
        aiPromptExamples: [
            'Sync film Spider-Man dari API MovieBox',
            'Tampilkan statistik lengkap database film & user',
            'Tampilkan 10 film rating tertinggi',
            'Daftar 10 pengguna terbaru terdaftar',
            'Bersihkan seluruh cache aplikasi',
        ],

        confirmModal: false,
        bypassConfirm: localStorage.getItem('scriptRunner_bypassConfirm') === 'true',
        codeCopied: false,
        outputCopied: false,
        toasts: [],

        form: {
            script_id: null,
            title: 'Script PHP Kustom Baru',
            description: '',
            code: `// Tulis kode PHP kustom di sini...\n$totalFilm = \\App\\Models\\Film::count();\necho "Total film di database: " . $totalFilm . "\\n";`,
        },

        init() {
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        showToast(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type, visible: true });
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
            setTimeout(() => {
                const found = this.toasts.find(t => t.id === id);
                if (found) found.visible = false;
            }, 4000);
        },

        saveBypassSetting() {
            localStorage.setItem('scriptRunner_bypassConfirm', this.bypassConfirm ? 'true' : 'false');
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
            this.showToast('Form script baru siap ditulis', 'info');
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
            this.showToast(`Memuat: ${script.title}`, 'info');
        },

        insertSnippet(type) {
            const snippets = {
                'stats': `echo "=== STATISTIK DATABASE FAIILMOV ===\\n\\n";\necho "• Total Film: " . \\App\\Models\\Film::count() . "\\n";\necho "• Total Series: " . \\App\\Models\\Film::where('subject_type', 'series')->count() . "\\n";\necho "• Total User: " . \\App\\Models\\User::count() . "\\n";\necho "• Total Review: " . \\App\\Models\\Review::count() . "\\n";`,
                'sync': `$keyword = 'Spider-Man';\necho "Memulai sinkronisasi API untuk: '{$keyword}'...\\n";\napp(\\App\\Services\\FilmSearchService::class)->fetchAndSyncFromMovieBox($keyword);\n$matches = \\App\\Models\\Film::where('title', 'LIKE', "%{$keyword}%")->get();\necho "Total film tersinkron: " . $matches->count() . "\\n";\nforeach ($matches as $f) {\n    echo "- [{$f->subject_type}] {$f->title} ({$f->release_year}) ★ {$f->rating}\\n";\n}`,
                'top_rated': `echo "=== TOP 10 FILM RATING TERTINGGI ===\\n\\n";\n$films = \\App\\Models\\Film::where('rating', '>', 0)->orderByDesc('rating')->limit(10)->get();\nforeach ($films as $idx => $f) {\n    $num = $idx + 1;\n    echo sprintf("  %2d. ★ %-4.1f | %-35s (%d)\\n", $num, $f->rating, $f->title, $f->release_year);\n}`,
                'users': `echo "=== 10 PENGGUNA TERBARU TERDAFTAR ===\\n\\n";\n$users = \\App\\Models\\User::latest()->limit(10)->get();\nforeach ($users as $idx => $u) {\n    $num = $idx + 1;\n    echo "  {$num}. {$u->name} ({$u->email}) - {$u->created_at->format('d M Y H:i')}\\n";\n}`,
                'clear_cache': `\\Illuminate\\Support\\Facades\\Cache::flush();\necho "SUCCESS: Seluruh application cache berhasil dibersihkan!\\n";`
            };

            if (snippets[type]) {
                this.form.code = snippets[type];
                this.showToast(`Snippet [${type.toUpperCase()}] disisipkan`, 'success');
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

        copyCode() {
            if (!this.form.code) return;
            navigator.clipboard.writeText(this.form.code);
            this.codeCopied = true;
            this.showToast('Kode berhasil disalin ke clipboard!', 'success');
            setTimeout(() => this.codeCopied = false, 2000);
        },

        copyOutput() {
            if (!this.outputConsole) return;
            navigator.clipboard.writeText(this.outputConsole);
            this.outputCopied = true;
            this.showToast('Output terminal berhasil disalin!', 'success');
            setTimeout(() => this.outputCopied = false, 2000);
        },

        promptExecution() {
            if (!this.form.code.trim()) {
                this.showToast('Kode PHP tidak boleh kosong!', 'warning');
                return;
            }

            if (this.bypassConfirm) {
                this.executeScript();
            } else {
                this.confirmModal = true;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        confirmAndRun() {
            this.confirmModal = false;
            this.executeScript();
        },

        async executeScript() {
            if (this.isExecuting) return;
            this.isExecuting = true;
            this.outputConsole = '> Mengeksekusi script PHP pada runtime server...\n';

            try {
                const res = await fetch('{{ route('admin.scripts.execute') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
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

                if (this.selectedScriptId) {
                    const found = this.scripts.find(s => s.id === this.selectedScriptId);
                    if (found) {
                        found.last_run_at_human = 'Baru Saja';
                        found.last_run_status = data.status;
                        found.execution_time_ms = data.duration_ms;
                        found.last_run_output = data.output;
                    }
                }

                if (data.status === 'success') {
                    this.showToast(`Eksekusi selesai (${data.duration_ms}ms)`, 'success');
                } else {
                    this.showToast('Script menghasilkan exception/error', 'error');
                }
            } catch (e) {
                this.lastResult = { status: 'error', duration_ms: 0, memory_kb: 0 };
                this.outputConsole = `ERROR EXECUTION: ${e.message}`;
                this.showToast('Gagal menghubungi server: ' + e.message, 'error');
            } finally {
                this.isExecuting = false;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        async saveScript() {
            if (!this.form.title.trim() || !this.form.code.trim()) {
                this.showToast('Judul dan Kode PHP tidak boleh kosong!', 'warning');
                return;
            }

            try {
                const res = await fetch('{{ route('admin.scripts.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();
                if (data.success && data.script) {
                    const saved = data.script;
                    const existingIndex = this.scripts.findIndex(s => s.id === saved.id);
                    if (existingIndex >= 0) {
                        this.scripts[existingIndex] = saved;
                    } else {
                        this.scripts.unshift(saved);
                    }
                    this.selectedScriptId = saved.id;
                    this.form.script_id = saved.id;
                    this.showToast(data.message || 'Script berhasil disimpan!', 'success');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan script', 'error');
                }
            } catch (e) {
                this.showToast('Error koneksi: ' + e.message, 'error');
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
                        'Accept': 'application/json',
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
                    this.showToast(data.message || 'Script berhasil dihapus', 'success');
                }
            } catch (e) {
                this.showToast('Gagal menghapus: ' + e.message, 'error');
            }
        },

        clearConsole() {
            this.outputConsole = '';
            this.lastResult = null;
            this.showToast('Konsol terminal dibersihkan', 'info');
        },

        async generateWithAI() {
            if (this.isGenerating || !this.aiPrompt.trim()) return;

            this.isGenerating = true;
            this.aiError = null;
            this.aiLastTokens = 0;
            this.aiLastModel = '';

            try {
                const res = await fetch('{{ route('admin.scripts.generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ prompt: this.aiPrompt }),
                });

                const data = await res.json();

                if (!data.success) {
                    this.aiError = data.error || 'Terjadi kesalahan saat generate script.';
                    this.showToast(this.aiError, 'error');
                    return;
                }

                this.form.code = data.code;

                if (!this.form.title || this.form.title === 'Script PHP Kustom Baru') {
                    const shortPrompt = this.aiPrompt.length > 50
                        ? this.aiPrompt.substring(0, 47) + '...'
                        : this.aiPrompt;
                    this.form.title = 'AI: ' + shortPrompt;
                }

                this.aiLastTokens = data.tokens || 0;
                this.aiLastModel  = data.model || 'Llama 3.1 8B';

                this.showToast('Kode berhasil disusun oleh AI!', 'success');

                this.$nextTick(() => {
                    if (this.$refs.codeTextarea) {
                        this.$refs.codeTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    if (window.lucide) lucide.createIcons();
                });

            } catch (e) {
                this.aiError = 'Network error: ' + e.message;
                this.showToast(this.aiError, 'error');
            } finally {
                this.isGenerating = false;
            }
        },
    }));
});
</script>
@endsection
