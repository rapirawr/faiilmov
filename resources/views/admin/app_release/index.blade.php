@extends('layouts.admin')

@section('title', 'Manajemen Rilis APK Mobile | faiiladmin')
@section('page_title', 'Manajemen Rilis Aplikasi Mobile (APK)')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <span class="text-sm font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
            <span class="text-sm font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Banner Status Rilis Aktif -->
    <div class="p-6 rounded-2xl bg-zinc-900/80 border border-white/10 shadow-xl space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                    <i data-lucide="smartphone" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white font-['Outfit']">Versi Rilis Aktif Saat Ini</h3>
                    <p class="text-xs text-zinc-400">Data versi ini yang terbaca oleh aplikasi mobile pengguna saat dibuka.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-white text-black font-['Outfit']">
                    v{{ $versionData['latest_version'] }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-zinc-300 border border-white/10">
                    Build {{ $versionData['latest_build_number'] }}
                </span>
                @if($versionData['force_update'])
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                        Wajib Update (Force)
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        Opsional
                    </span>
                @endif
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-zinc-950/80 border border-white/5 space-y-1 text-xs">
            <span class="text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">Terakhir Diperbarui:</span>
            <p class="text-zinc-300 font-medium">{{ $versionData['updated_at'] ?? 'Belum ada data' }}</p>
        </div>

        <div class="p-3.5 rounded-xl bg-zinc-950/80 border border-white/5 space-y-1 text-xs">
            <span class="text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">Catatan Pembaruan (Release Notes):</span>
            <p class="text-zinc-300 whitespace-pre-line leading-relaxed">{{ $versionData['release_notes'] }}</p>
        </div>
    </div>

    <!-- Form Upload & Rilis Baru dengan Real-Time Progress Bar & Byte Counter -->
    <div class="p-6 rounded-2xl bg-zinc-900/80 border border-white/10 shadow-xl space-y-6"
         x-data="apkUploadForm()">

        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <div>
                <h3 class="text-base font-bold text-white font-['Outfit']">Publikasikan Rilis / Update APK Baru</h3>
                <p class="text-xs text-zinc-400">Upload file APK dari komputer Anda ke server. Progres pengunggahan akan ditampilkan secara langsung.</p>
            </div>
            <i data-lucide="upload-cloud" class="w-6 h-6 text-amber-400"></i>
        </div>

        <!-- Success Toast Notification -->
        <div x-show="successMessage" x-transition style="display: none;" 
             class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between text-xs font-semibold">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                <span x-text="successMessage"></span>
            </div>
            <button type="button" @click="successMessage = ''" class="text-emerald-400 hover:text-white text-base leading-none">&times;</button>
        </div>

        <form action="{{ route('admin.app_release.store') }}" method="POST" enctype="multipart/form-data" 
              @submit="submitForm($event)" class="space-y-6">
            @csrf

            <!-- Upload Real-Time Progress Banner (Aktif saat pengunggahan dari komputer ke server) -->
            <div x-show="uploading" x-transition style="display: none;" 
                 class="p-6 rounded-2xl bg-zinc-950 border border-amber-500/40 space-y-4 shadow-2xl shadow-amber-500/10">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 shrink-0">
                            <svg class="animate-spin h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-amber-300" x-text="progress < 100 ? 'Mengunggah File APK ke Server...' : 'Mengekstrak Metadata APK (vName & vCode)...'"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/30" x-text="progress + '%'"></span>
                            </div>
                            <p class="text-xs text-zinc-300 font-mono mt-0.5 truncate" x-text="fileName ? fileName : 'Proses pengiriman...'"></p>
                        </div>
                    </div>

                    <!-- Realtime Byte Counter & Speed -->
                    <div class="text-right text-xs font-mono shrink-0">
                        <div class="text-amber-400 font-bold" x-text="formattedLoaded + ' / ' + formattedTotal"></div>
                        <div class="text-[11px] text-zinc-400" x-text="uploadSpeed ? uploadSpeed + ' • ' + Math.max(0, remainingSeconds) + 's tersisa' : 'Mengirim data...' "></div>
                    </div>
                </div>

                <!-- Animated Progress Bar -->
                <div class="relative w-full bg-zinc-900 rounded-full h-3.5 p-0.5 border border-white/10 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-300 h-full rounded-full transition-all duration-200 shadow-md shadow-amber-500/40 relative overflow-hidden"
                         :style="'width: ' + progress + '%'">
                        <!-- Shimmer animation effect -->
                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-zinc-400">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span>Transfer data dari komputer ke server sedang berlangsung. Mohon tidak menutup tab browser ini.</span>
                    </span>
                </div>
            </div>

            <!-- Error Banner (Jika Upload Gagal) -->
            <div x-show="errorMessage" x-transition style="display: none;" 
                 class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between text-xs font-semibold">
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span x-text="errorMessage"></span>
                </div>
                <button type="button" @click="errorMessage = ''" class="text-rose-400 hover:text-white text-base leading-none">&times;</button>
            </div>

            <fieldset :disabled="uploading" class="space-y-6">
                <!-- Dropzone / Drag & Drop Upload Container -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">File APK Mobile (.apk) *</label>
                    
                    <div class="relative p-6 rounded-2xl bg-zinc-950 border-2 border-dashed transition-all duration-300 text-center space-y-4"
                         :class="isDragging ? 'border-amber-400 bg-amber-500/10' : (fileSelected ? 'border-amber-500/40 bg-zinc-950' : 'border-white/20 hover:border-white/40')"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleFileDrop($event)">
                        
                        <input type="file" name="apk_file" accept=".apk" x-ref="apkFileInput"
                               @change="handleFileSelect($event)"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer disabled:cursor-not-allowed z-10">

                        <div class="space-y-2 pointer-events-none">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mx-auto flex items-center justify-center">
                                <i data-lucide="file-up" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Tarik & Lepas File APK Di Sini</p>
                                <p class="text-xs text-zinc-400 mt-1">atau <span class="text-amber-400 font-semibold underline">klik untuk memilih file</span> dari komputer Anda</p>
                            </div>
                        </div>

                        <!-- Card Detail File APK Terpilih -->
                        <div x-show="fileSelected" x-transition style="display: none;" 
                             class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between text-xs text-left max-w-xl mx-auto">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-400 flex items-center justify-center shrink-0">
                                    <i data-lucide="file-archive" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-amber-300 text-sm truncate" x-text="fileName"></p>
                                    <p class="text-[11px] text-zinc-400 font-mono" x-text="formattedTotal"></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[11px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/30 shrink-0">
                                Siap Di-upload
                            </span>
                        </div>

                        <p class="text-[11px] text-zinc-500">Ukuran file maksimal: 200MB. Versi (`versionName`) dan Build (`versionCode`) akan dibaca otomatis langsung dari file APK.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Version Name -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Versi Baru <span class="text-amber-400/80 font-normal">(Opsional - Otomatis dari APK)</span></label>
                        <input type="text" name="version_name" value="{{ old('version_name', $versionData['latest_version']) }}"
                               placeholder="Otomatis dibaca dari file APK"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/50 disabled:opacity-50 font-mono">
                        <p class="text-[11px] text-zinc-500 mt-1">Biarkan default/kosong saat upload file APK baru. Sistem akan membaca metadata `versionName` dari file APK secara otomatis.</p>
                    </div>

                    <!-- Build Number -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nomor Build / versionCode <span class="text-amber-400/80 font-normal">(Opsional - Otomatis dari APK)</span></label>
                        <input type="number" name="build_number" value="{{ old('build_number', $versionData['latest_build_number'] + 1) }}" min="1"
                               placeholder="Otomatis dibaca dari file APK"
                               class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/50 disabled:opacity-50 font-mono">
                        <p class="text-[11px] text-zinc-500 mt-1">Biarkan default/kosong saat upload file APK baru. Sistem akan mengekstrak `versionCode` secara otomatis.</p>
                    </div>
                </div>

                <!-- Force Update Checkbox -->
                <div class="flex items-center gap-3 p-4 rounded-xl bg-zinc-950 border border-white/10">
                    <input type="checkbox" id="force_update" name="force_update" value="1" {{ old('force_update', $versionData['force_update']) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-white/20 bg-zinc-900 text-amber-500 focus:ring-0 cursor-pointer disabled:opacity-50">
                    <div>
                        <label for="force_update" class="text-sm font-bold text-white cursor-pointer">Paksa Pengguna Update (Wajib Update / Force Update)</label>
                        <p class="text-xs text-zinc-400">Jika dicentang, pengguna tidak akan bisa menutup dialog update sebelum mendownload versi baru ini.</p>
                    </div>
                </div>

                <!-- Release Notes -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Catatan Pembaruan (Release Notes) *</label>
                    <textarea name="release_notes" rows="4" required
                              placeholder="• Perbaikan fitur Watch Party&#10;• Peningkatan kecepatan streaming&#10;• Perbaikan bug pada tampilan"
                              class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-white/50 leading-relaxed disabled:opacity-50">{{ old('release_notes', $versionData['release_notes']) }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="uploading"
                            class="w-full md:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black font-['Outfit'] font-extrabold text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-xl shadow-amber-500/20 flex items-center justify-center gap-2 cursor-pointer border border-amber-300/40">
                        <template x-if="!uploading">
                            <span class="flex items-center gap-2">
                                <i data-lucide="upload" class="w-4 h-4 stroke-[2.5]"></i>
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
            </fieldset>
        </form>
    </div>

    <!-- Daftar File APK Terupload -->
    <div class="p-6 rounded-2xl bg-zinc-900/80 border border-white/10 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <div>
                <h3 class="text-base font-bold text-white font-['Outfit']">Daftar File APK Ter-upload (`/public/apk-files`)</h3>
                <p class="text-xs text-zinc-400">File APK yang saat ini tersimpan di folder publik server Anda.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-white/10 text-zinc-300">
                {{ count($uploadedFiles) }} File
            </span>
        </div>

        @if(count($uploadedFiles) > 0)
            <div class="divide-y divide-white/5">
                @foreach($uploadedFiles as $file)
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-zinc-950 border border-white/10 flex items-center justify-center text-zinc-400">
                                <i data-lucide="file-archive" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $file['name'] }}</p>
                                <p class="text-[11px] text-zinc-500">{{ $file['size'] }} &bull; Diupload {{ $file['modified_at'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ $file['url'] }}" download class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition-colors flex items-center gap-1.5">
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
                Belum ada file APK yang diupload di folder `/public/apk-files/`.
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
        successMessage: '',

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
                    this.errorMessage = 'File yang dipilih harus berformat .apk';
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
                // Submit standard text form directly
                return;
            }

            event.preventDefault();
            this.uploading = true;
            this.progress = 0;
            this.errorMessage = '';
            this.successMessage = '';

            const form = event.target;
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            this.startTime = Date.now();
            this.lastTime = Date.now();
            this.lastLoaded = 0;

            // Track upload progress in real-time
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.loadedBytes = e.loaded;
                    this.totalBytes = e.total;
                    this.formattedLoaded = this.formatBytes(e.loaded);
                    this.formattedTotal = this.formatBytes(e.total);
                    this.progress = Math.min(99, Math.round((e.loaded / e.total) * 100));

                    // Calculate speed & estimated time remaining
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
                    try {
                        const res = JSON.parse(xhr.responseText);
                        this.successMessage = res.message || 'File APK berhasil diunggah & dipublikasikan!';
                    } catch (_) {
                        this.successMessage = 'File APK berhasil diunggah & dipublikasikan!';
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
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
                        // Response is HTML (HTTP 413, 500, etc.)
                    }

                    if (parsedMessage) {
                        this.errorMessage = parsedMessage;
                    } else if (xhr.status === 413) {
                        this.errorMessage = 'Ukuran file melebihi batas request Web Server (HTTP 413 Payload Too Large). Periksa setting LimitRequestBody / post_max_size pada web server / cPanel.';
                    } else if (xhr.status === 500) {
                        this.errorMessage = 'Terjadi kesalahan internal server (HTTP 500). Silakan periksa log Laravel di storage/logs/laravel.log.';
                    } else {
                        this.errorMessage = `Gagal mengunggah file APK (HTTP Status ${xhr.status}). Silakan periksa ukuran file dan log server.`;
                    }
                }
            });

            xhr.addEventListener('error', () => {
                this.uploading = false;
                this.errorMessage = 'Koneksi terputus saat mengunggah file APK ke server.';
            });

            xhr.open('POST', form.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        }
    };
}
</script>
@endpush
@endsection
