@extends('layouts.admin')

@section('title', 'Manajemen Rilis APK Mobile | faiiladmin')
@section('page_title', 'Manajemen Rilis Aplikasi Mobile (APK)')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Flash Alerts System -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-400"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 text-rose-400"></i>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Status Banner: Versi Rilis Aktif (Standardized Semantic Badges) -->
    <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-800 pb-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                    <i data-lucide="smartphone" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white font-['Outfit']">Versi Rilis Aktif Saat Ini</h3>
                    <p class="text-xs text-zinc-400">Metadata versi ini yang dibaca secara otomatis oleh aplikasi mobile pengguna saat dibuka.</p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-zinc-800 text-white border border-zinc-700 font-mono">
                    v{{ $versionData['latest_version'] }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-zinc-800/80 text-zinc-300 border border-zinc-700/60 font-mono">
                    Build {{ $versionData['latest_build_number'] }}
                </span>
                @if($versionData['force_update'])
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-rose-500/10 text-rose-400 border border-rose-500/30 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                        <span>Wajib Update (Force)</span>
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>Opsional</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-zinc-950/80 border border-zinc-800 space-y-1">
                <span class="text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">Terakhir Diperbarui:</span>
                <p class="text-zinc-200 font-mono font-medium">{{ $versionData['updated_at'] ?? 'Belum ada data' }}</p>
            </div>

            <div class="p-3.5 rounded-xl bg-zinc-950/80 border border-zinc-800 space-y-1 md:col-span-2">
                <span class="text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">Catatan Pembaruan (Release Notes):</span>
                <p class="text-zinc-300 whitespace-pre-line leading-relaxed">{{ $versionData['release_notes'] ?: 'Tidak ada catatan rilis.' }}</p>
            </div>
        </div>
    </div>

    <!-- Form Upload & Rilis APK Baru (SaaS Grouped Card Layout) -->
    <div x-data="apkUploadForm()" class="space-y-6">
        
        <form action="{{ route('admin.app_release.store') }}" method="POST" enctype="multipart/form-data" 
              @submit="submitForm($event)" class="space-y-6">
            @csrf

            <!-- Section 1: File Upload (5-State Interactive Dropzone) -->
            <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 shrink-0">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">1. Upload File APK (.apk)</h3>
                            <p class="text-xs text-zinc-400">Pilih atau tarik file APK dari komputer. Sistem akan membaca versi & versionCode secara otomatis.</p>
                        </div>
                    </div>
                    <span class="text-[11px] font-mono text-zinc-500 bg-zinc-950 px-2.5 py-1 rounded-lg border border-zinc-800">Maks. 200 MB</span>
                </div>

                <!-- Live Upload Progress Bar (State: Uploading) -->
                <div x-show="uploading" x-transition style="display: none;" 
                     class="p-5 rounded-2xl bg-zinc-950 border border-amber-500/40 space-y-4 shadow-xl">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shrink-0">
                                <svg class="animate-spin h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-amber-300" x-text="progress < 100 ? 'Mengunggah File APK ke Server...' : 'Mengekstrak Metadata APK (vName & vCode)...'"></span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/30" x-text="progress + '%'"></span>
                                </div>
                                <p class="text-xs text-zinc-400 font-mono mt-0.5 truncate" x-text="fileName ? fileName : 'Proses pengiriman...'"></p>
                            </div>
                        </div>

                        <div class="text-right text-xs font-mono shrink-0">
                            <div class="text-amber-400 font-bold" x-text="formattedLoaded + ' / ' + formattedTotal"></div>
                            <div class="text-[11px] text-zinc-500" x-text="uploadSpeed ? uploadSpeed + ' • ' + Math.max(0, remainingSeconds) + 's tersisa' : 'Mengirim data...' "></div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative w-full bg-zinc-900 rounded-full h-3 p-0.5 border border-zinc-800 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-amber-400 h-full rounded-full transition-all duration-200 shadow-md relative overflow-hidden"
                             :style="'width: ' + progress + '%'">
                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Actionable Error Message Banner (State: Error) -->
                <div x-show="errorMessage" x-transition style="display: none;" 
                     class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-start justify-between text-xs gap-3">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-400 mt-0.5"></i>
                        <div class="space-y-1">
                            <p class="font-bold text-rose-300">Gagal Mengunggah File APK</p>
                            <p class="text-zinc-300 whitespace-pre-line leading-relaxed" x-text="errorMessage"></p>
                        </div>
                    </div>
                    <button type="button" @click="errorMessage = ''" class="text-zinc-400 hover:text-white text-base leading-none cursor-pointer">&times;</button>
                </div>

                <!-- Dropzone Box (States: Idle, Drag Hovering, Selected Success) -->
                <div class="relative p-6 rounded-2xl bg-zinc-950 border-2 border-dashed transition-all duration-200 text-center space-y-4"
                     :class="isDragging ? 'border-amber-400 bg-amber-500/5' : (fileSelected ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-zinc-800 hover:border-zinc-700')"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleFileDrop($event)">
                    
                    <input type="file" name="apk_file" accept=".apk" x-ref="apkFileInput"
                           @change="handleFileSelect($event)"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer disabled:cursor-not-allowed z-10">

                    <div class="space-y-2 pointer-events-none" x-show="!fileSelected">
                        <div class="w-12 h-12 rounded-2xl bg-zinc-900 border border-zinc-800 text-zinc-400 mx-auto flex items-center justify-center">
                            <i data-lucide="file-up" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Tarik & Lepas File APK Di Sini</p>
                            <p class="text-xs text-zinc-400 mt-1">atau <span class="text-amber-400 font-semibold underline">klik untuk memilih file</span> dari komputer</p>
                        </div>
                    </div>

                    <!-- Selected File Preview Badge (State: Success Selected) -->
                    <div x-show="fileSelected" x-transition style="display: none;" 
                         class="p-4 rounded-xl bg-zinc-900 border border-emerald-500/30 flex items-center justify-between text-xs text-left max-w-xl mx-auto">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center shrink-0">
                                <i data-lucide="file-archive" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-emerald-300 text-sm truncate" x-text="fileName"></p>
                                <p class="text-[11px] text-zinc-400 font-mono" x-text="formattedTotal"></p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 shrink-0 flex items-center gap-1">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            <span>Siap Di-upload</span>
                        </span>
                    </div>

                    <p class="text-[11px] text-zinc-500">Format file yang didukung: <code class="text-zinc-400">.apk</code>. Nama & versi build akan diparsing otomatis oleh server.</p>
                </div>
            </div>

            <!-- Section 2: Metadata Versi & Release Notes -->
            <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-5">
                <div class="flex items-center gap-3 border-b border-zinc-800 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white font-['Outfit']">2. Informasi Versi & Catatan Pembaruan</h3>
                        <p class="text-xs text-zinc-400">Tentukan rincian versi rilis baru atau biarkan otomatis dibaca dari file APK.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Version Name -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                            Nama Versi Baru <span class="text-zinc-500 font-normal lowercase">(opsional - otomatis dari APK)</span>
                        </label>
                        <input type="text" name="version_name" value="{{ old('version_name') }}"
                               placeholder="Otomatis dari file APK (misal: 1.0.2)"
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500 disabled:opacity-50 font-mono">
                        <p class="text-[11px] text-zinc-500 mt-1.5">Biarkan kosong jika mengunggah file APK baru. Server akan membaca <code class="text-zinc-400">versionName</code> dari file APK.</p>
                        @error('version_name')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Build Number -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                            Nomor Build / versionCode <span class="text-zinc-500 font-normal lowercase">(opsional - otomatis dari APK)</span>
                        </label>
                        <input type="number" name="build_number" value="{{ old('build_number') }}" min="1"
                               placeholder="Otomatis dari file APK (misal: 3)"
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500 disabled:opacity-50 font-mono">
                        <p class="text-[11px] text-zinc-500 mt-1.5">Nomor urut build aplikasi untuk mendeteksi update di HP pengguna.</p>
                        @error('build_number')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Release Notes -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                        Catatan Pembaruan (Release Notes) *
                    </label>
                    <textarea name="release_notes" rows="4" required
                              placeholder="• Perbaikan fitur Watch Party&#10;• Peningkatan kecepatan streaming&#10;• Perbaikan bug pada tampilan"
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs text-white focus:outline-none focus:border-amber-500 leading-relaxed disabled:opacity-50">{{ old('release_notes') }}</textarea>
                    <p class="text-[11px] text-zinc-500 mt-1.5">Tuliskan ringkasan fitur baru atau perbaikan bug yang ditampilkan ke pengguna saat dialog update muncul.</p>
                    @error('release_notes')
                        <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Section 3: Pengaturan Force Update -->
            <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-5">
                <div class="flex items-center gap-3 border-b border-zinc-800 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
                        <i data-lucide="shield-alert" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white font-['Outfit']">3. Pengaturan Kebijakan Update</h3>
                        <p class="text-xs text-zinc-400">Atur apakah pengguna wajib melakukan update sebelum melanjutkan penggunaan aplikasi.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3.5 p-4 rounded-xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 transition-colors">
                    <input type="checkbox" id="force_update" name="force_update" value="1" {{ old('force_update') ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus:ring-0 cursor-pointer disabled:opacity-50 mt-0.5">
                    <div>
                        <label for="force_update" class="text-xs font-bold text-white cursor-pointer select-none">Paksa Pengguna Update (Wajib Update / Force Update)</label>
                        <p class="text-[11px] text-zinc-400 mt-0.5">Jika dicentang, aplikasi mobile tidak bisa digunakan sampai pengguna mengunduh dan memasang versi rilis baru ini.</p>
                    </div>
                </div>

                <div class="pt-3 border-t border-zinc-800 flex justify-end">
                    <button type="submit" :disabled="uploading"
                            class="w-full sm:w-auto px-8 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2 cursor-pointer">
                        <template x-if="!uploading">
                            <span class="flex items-center gap-2">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                                <span>Publikasikan & Unggah Update Rilis Baru</span>
                            </span>
                        </template>
                        <template x-if="uploading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="'Mengunggah... ' + progress + '%'"></span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>

            <!-- Floating Bottom-Right Save Action Bar -->
            <div class="fixed bottom-6 right-6 z-40 flex items-center gap-3 bg-zinc-900/90 backdrop-blur-xl border border-white/15 p-2 sm:p-2.5 rounded-2xl shadow-2xl shadow-black/80 ring-1 ring-white/10 hover:border-amber-500/40 transition-all">
                <button type="submit" :disabled="uploading"
                        class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold text-xs shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                    <template x-if="!uploading">
                        <span class="flex items-center gap-2">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span>Publikasikan Rilis APK</span>
                        </span>
                    </template>
                    <template x-if="uploading">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="progress + '%'"></span>
                        </span>
                    </template>
                </button>
            </div>
        </form>
    </div>

    <!-- Daftar File APK Terupload di Server -->
    <div class="p-6 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
            <div>
                <h3 class="text-base font-bold text-white font-['Outfit']">Daftar File APK Ter-upload (`/public/apk-files`)</h3>
                <p class="text-xs text-zinc-400">File installer APK yang tersimpan di direktori server publik.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-zinc-800 text-zinc-300 border border-zinc-700">
                {{ count($uploadedFiles) }} File
            </span>
        </div>

        @if(count($uploadedFiles) > 0)
            <div class="divide-y divide-zinc-800/60">
                @foreach($uploadedFiles as $file)
                    <div class="py-3 flex items-center justify-between gap-4 group hover:bg-zinc-800/20 px-2 rounded-xl transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-zinc-950 border border-zinc-800 flex items-center justify-center text-zinc-400 shrink-0">
                                <i data-lucide="file-archive" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-white truncate font-mono">{{ $file['name'] }}</p>
                                <p class="text-[11px] text-zinc-500">{{ $file['size'] }} &bull; Diupload {{ $file['modified_at'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ $file['url'] }}" download class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-white transition-colors flex items-center gap-1.5">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">Download</span>
                            </a>

                            <form action="{{ route('admin.app_release.destroy_file', $file['name']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file {{ $file['name'] }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors cursor-pointer" title="Hapus File">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center text-zinc-500 text-xs">
                Belum ada file APK yang tersimpan di direktori server `/public/apk-files/`.
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function apkUploadForm() {
    return {
        uploading: false,
        progress: 0,
        loadedBytes: 0,
        totalBytes: 0,
        formattedLoaded: '0 MB',
        formattedTotal: '0 MB',
        uploadSpeed: '',
        remainingSeconds: 0,
        startTime: null,
        lastLoaded: 0,
        lastTime: null,
        fileSelected: false,
        fileName: '',
        isDragging: false,
        errorMessage: '',

        handleFileSelect(event) {
            const file = event.target.files[0];
            this.setFileInfo(file);
        },

        handleFileDrop(event) {
            this.isDragging = false;
            if (event.dataTransfer && event.dataTransfer.files.length > 0) {
                const file = event.dataTransfer.files[0];
                if (file.name.endsWith('.apk')) {
                    this.$refs.apkFileInput.files = event.dataTransfer.files;
                    this.setFileInfo(file);
                } else {
                    this.errorMessage = 'Format file tidak valid. File harus memiliki ekstensi .apk';
                }
            }
        },

        setFileInfo(file) {
            if (file) {
                this.fileSelected = true;
                this.fileName = file.name;
                this.totalBytes = file.size;
                this.formattedTotal = this.formatBytes(file.size);
                this.formattedLoaded = '0 MB';
            } else {
                this.fileSelected = false;
                this.fileName = '';
                this.totalBytes = 0;
                this.formattedTotal = '0 MB';
            }
        },

        formatBytes(bytes) {
            if (!bytes || bytes <= 0) return '0 MB';
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            }
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        submitForm(event) {
            const fileInput = this.$refs.apkFileInput;
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            if (!hasFile) {
                return;
            }

            event.preventDefault();
            this.uploading = true;
            this.progress = 0;
            this.errorMessage = '';

            const form = event.target;
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            this.startTime = Date.now();
            this.lastTime = Date.now();
            this.lastLoaded = 0;

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.loadedBytes = e.loaded;
                    this.totalBytes = e.total;
                    this.formattedLoaded = this.formatBytes(e.loaded);
                    this.formattedTotal = this.formatBytes(e.total);
                    this.progress = Math.min(99, Math.round((e.loaded / e.total) * 100));

                    const now = Date.now();
                    const timeDiff = (now - this.lastTime) / 1000;
                    if (timeDiff >= 0.3) {
                        const loadedDiff = e.loaded - this.lastLoaded;
                        const bytesPerSec = loadedDiff / timeDiff;
                        if (bytesPerSec > 0) {
                            this.uploadSpeed = (bytesPerSec / 1048576).toFixed(1) + ' MB/s';
                            const remainingBytes = e.total - e.loaded;
                            this.remainingSeconds = Math.ceil(remainingBytes / bytesPerSec);
                        }
                        this.lastTime = now;
                        this.lastLoaded = e.loaded;
                    }
                }
            });

            xhr.addEventListener('load', () => {
                this.progress = 100;
                if (xhr.status >= 200 && xhr.status < 300) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    this.uploading = false;
                    let parsedMessage = '';

                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.message) {
                            parsedMessage = res.message;
                        } else if (res.errors && typeof res.errors === 'object') {
                            const errKeys = Object.keys(res.errors);
                            if (errKeys.length > 0 && Array.isArray(res.errors[errKeys[0]])) {
                                parsedMessage = res.errors[errKeys[0]][0];
                            }
                        }
                    } catch (e) {
                    }

                    if (parsedMessage) {
                        this.errorMessage = parsedMessage;
                    } else if (xhr.status === 413) {
                        this.errorMessage = 'Ukuran file terlalu besar melebihi batas batas request web server (HTTP 413 Payload Too Large). Periksa konfigurasi post_max_size / upload_max_filesize di cPanel.';
                    } else if (xhr.status === 500) {
                        this.errorMessage = 'Terjadi kesalahan server internal (HTTP 500). Periksa log Laravel untuk detail exception.';
                    } else {
                        this.errorMessage = `Gagal mengunggah file APK (HTTP Status ${xhr.status}). Silakan coba beberapa saat lagi.`;
                    }
                }
            });

            xhr.addEventListener('error', () => {
                this.uploading = false;
                this.errorMessage = 'Koneksi jaringan terputus saat proses upload file ke server.';
            });

            xhr.open('POST', form.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        }
    };
}
</script>
@endsection
