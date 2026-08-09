@extends('layouts.admin')

@section('title', 'Edit Catatan Rilis: ' . $changelog->version . ' | faiiladmin')
@section('page_title', 'Edit Catatan Rilis: ' . $changelog->version)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    changes: {{ Js::from(empty($changelog->changes) ? [['type' => 'feature', 'text' => '']] : $changelog->changes) }},
    addChange() {
        this.changes.push({ type: 'feature', text: '' });
    },
    removeChange(index) {
        if (this.changes.length > 1) {
            this.changes.splice(index, 1);
        }
    }
}">

    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-white font-['Outfit']">Edit Catatan Rilis: {{ $changelog->version }}</h2>
        <a href="{{ route('admin.changelogs.index') }}" class="text-xs text-zinc-400 hover:text-white flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-white/10 shadow-xl">
        <form action="{{ route('admin.changelogs.update', $changelog->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Version -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nomor Versi *</label>
                    <input type="text" name="version" value="{{ old('version', $changelog->version) }}" required 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:outline-none focus:border-amber-500">
                </div>

                <!-- Update Type -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tipe Rilis *</label>
                    <select name="type" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                        <option value="major" {{ old('type', $changelog->type) === 'major' ? 'selected' : '' }}>Major Release (🚀 Fitur Utama Baru)</option>
                        <option value="minor" {{ old('type', $changelog->type) === 'minor' ? 'selected' : '' }}>Minor Update (✨ Fitur Tambahan)</option>
                        <option value="patch" {{ old('type', $changelog->type) === 'patch' ? 'selected' : '' }}>Patch (🔧 Perbaikan Bug)</option>
                        <option value="security" {{ old('type', $changelog->type) === 'security' ? 'selected' : '' }}>Security Patch (🛡️ Keamanan)</option>
                    </select>
                </div>

                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Judul Rilis *</label>
                    <input type="text" name="title" value="{{ old('title', $changelog->title) }}" required 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Release Date -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Tanggal Rilis *</label>
                    <input type="date" name="release_date" value="{{ old('release_date', $changelog->release_date ? $changelog->release_date->format('Y-m-d') : '') }}" required 
                           class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Publish Toggle -->
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-3 text-xs text-white cursor-pointer select-none">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $changelog->is_published) ? 'checked' : '' }} class="w-4 h-4 rounded border-white/20 bg-zinc-900 text-amber-500 focus:ring-amber-500">
                        <span class="font-bold">Publikasikan di Halaman Changelog</span>
                    </label>
                </div>

                <!-- Summary -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Ringkasan Singkat Rilis</label>
                    <textarea name="summary" rows="3" 
                              class="w-full bg-zinc-950 border border-white/10 rounded-xl p-4 text-sm text-white focus:outline-none focus:border-amber-500">{{ old('summary', $changelog->summary) }}</textarea>
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
                                <input type="text" :name="`changes[${index}][text]`" x-model="item.text" required 
                                       class="flex-1 bg-zinc-900 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none">
                                <button type="button" @click="removeChange(index)" class="p-1.5 rounded-lg text-zinc-400 hover:text-red-400 hover:bg-red-500/10">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                <a href="{{ route('admin.changelogs.index') }}" class="px-5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs transition-colors">Batal</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer">Perbarui Catatan Rilis</button>
            </div>
        </form>
    </div>

</div>
@endsection
