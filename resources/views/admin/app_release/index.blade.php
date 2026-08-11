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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-zinc-950/80 border border-white/5 space-y-1">
                <span class="text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">URL Download APK:</span>
                <div class="flex items-center gap-2 truncate">
                    <a href="{{ $versionData['download_url'] }}" target="_blank" class="text-amber-300 hover:underline font-mono truncate">
                        {{ $versionData['download_url'] }}
                    </a>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-zinc-950/80 border border-white/5 space-y-1">
                <span class="text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">Terakhir Diperbarui:</span>
                <p class="text-zinc-300 font-medium">{{ $versionData['updated_at'] ?? 'Belum ada data' }}</p>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-zinc-950/80 border border-white/5 space-y-1">
            <span class="text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">Catatan Pembaruan (Release Notes):</span>
            <p class="text-zinc-300 whitespace-pre-line leading-relaxed">{{ $versionData['release_notes'] }}</p>
        </div>
    </div>

    <!-- Form Upload & Rilis Baru -->
    <div class="p-6 rounded-2xl bg-zinc-900/80 border border-white/10 shadow-xl space-y-6">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <div>
                <h3 class="text-base font-bold text-white font-['Outfit']">Publikasikan Rilis / Update APK Baru</h3>
                <p class="text-xs text-zinc-400">Upload file APK baru dan perbarui informasi versi agar pengguna menerima pop-up update.</p>
            </div>
            <i data-lucide="upload-cloud" class="w-6 h-6 text-zinc-500"></i>
        </div>

        <form action="{{ route('admin.app_release.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Version Name -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Versi Baru *</label>
                    <input type="text" name="version_name" value="{{ old('version_name', $versionData['latest_version']) }}" required
                           placeholder="Contoh: 1.0.1"
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/50">
                    <p class="text-[11px] text-zinc-500 mt-1">Sesuai dengan `version` di pubspec.yaml (misal: 1.0.1).</p>
                </div>

                <!-- Build Number -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nomor Build (versionCode) *</label>
                    <input type="number" name="build_number" value="{{ old('build_number', $versionData['latest_build_number'] + 1) }}" required min="1"
                           placeholder="Contoh: 2"
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/50">
                    <p class="text-[11px] text-zinc-500 mt-1">Wajib **lebih besar** dari build saat ini (Build {{ $versionData['latest_build_number'] }}).</p>
                </div>
            </div>

            <!-- Upload File APK -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Upload File APK Baru (Opsional)</label>
                <div class="p-4 rounded-xl bg-zinc-950 border border-dashed border-white/20 hover:border-white/40 transition-colors">
                    <input type="file" name="apk_file" accept=".apk" 
                           class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-white file:text-black hover:file:bg-zinc-200 cursor-pointer">
                    <p class="text-[11px] text-zinc-500 mt-2">Maksimal ukuran file: 200MB. File akan otomatis disimpan ke `/public/download/faiilmov-v[versi].apk`.</p>
                </div>
            </div>

            <!-- Custom Download URL (Optional override) -->
            <div>
                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">URL Custom Download (Jika Hosting APK di Tempat Lain)</label>
                <input type="url" name="download_url" value="{{ old('download_url', $versionData['download_url']) }}"
                       placeholder="https://faiilmov.my.id/download/faiilmov-v1.0.1.apk"
                       class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/50 font-mono">
                <p class="text-[11px] text-zinc-500 mt-1">Biarkan kosong jika mengunggah file APK langsung melalui form di atas.</p>
            </div>

            <!-- Force Update Checkbox -->
            <div class="flex items-center gap-3 p-4 rounded-xl bg-zinc-950 border border-white/10">
                <input type="checkbox" id="force_update" name="force_update" value="1" {{ old('force_update', $versionData['force_update']) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-white/20 bg-zinc-900 text-amber-500 focus:ring-0 cursor-pointer">
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
                          class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-white/50 leading-relaxed">{{ old('release_notes', $versionData['release_notes']) }}</textarea>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full md:w-auto px-6 py-3 rounded-xl bg-white text-black font-['Outfit'] font-extrabold text-sm hover:bg-zinc-200 transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Publikasikan Update Rilis Baru</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Daftar File APK Terupload -->
    <div class="p-6 rounded-2xl bg-zinc-900/80 border border-white/10 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <div>
                <h3 class="text-base font-bold text-white font-['Outfit']">Daftar File APK Ter-upload (`/public/download`)</h3>
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
                Belum ada file APK yang diupload di folder `/public/download/`.
            </div>
        @endif
    </div>

</div>
@endsection
