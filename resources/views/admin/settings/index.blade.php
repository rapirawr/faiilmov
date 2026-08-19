@extends('layouts.admin')

@section('title', 'CMS & Pengaturan Platform Global | faiiladmin')
@section('page_title', 'CMS & Pengaturan Platform Global')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" 
     x-data="{
        activeTab: 'branding',
        primaryColor: '{{ old('primary_color', $siteSetting->primary_color ?: '#ffffff') }}',
        secondaryColor: '{{ old('secondary_color', $siteSetting->secondary_color ?: '#a1a1aa') }}',
        backgroundColor: '{{ old('background_color', $siteSetting->background_color ?: '#09090b') }}',
        siteName: '{{ old('site_name', $siteSetting->site_name) }}',
        siteTagline: '{{ old('site_tagline', $siteSetting->site_tagline) }}',
        logoUrl: '{{ $siteSetting->logo_url }}',
        logoDarkUrl: '{{ $siteSetting->logo_dark_url }}',
        faviconUrl: '{{ $siteSetting->favicon_url }}',
        seoOgImageUrl: '{{ $siteSetting->seo_og_image_url }}',
        pageTransitionEnabled: {{ $siteSetting->page_transition_enabled ? 'true' : 'false' }},
        pageTransitionGifIsloadUrl: '{{ $siteSetting->page_transition_gif_isload_url }}',
        pageTransitionGifLoadedUrl: '{{ $siteSetting->page_transition_gif_loaded_url }}',
        simulatingTransition: false,
        simulatingPhase: 'isLoad',
        uploading: false,
        toast: null,
        
        showToast(message, type = 'success') {
            this.toast = { message, type };
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
            setTimeout(() => { this.toast = null; }, 3500);
        },

        playSimulation() {
            if (!this.pageTransitionGifIsloadUrl && !this.pageTransitionGifLoadedUrl) {
                this.showToast('Silakan unggah minimal salah satu file GIF (isLoad atau load).', 'error');
                return;
            }
            this.simulatingTransition = true;
            this.simulatingPhase = this.pageTransitionGifIsloadUrl ? 'isLoad' : 'load';

            if (this.pageTransitionGifIsloadUrl && this.pageTransitionGifLoadedUrl) {
                setTimeout(() => {
                    this.simulatingPhase = 'load';
                    setTimeout(() => {
                        this.simulatingTransition = false;
                    }, 1200);
                }, 1400);
            } else {
                setTimeout(() => {
                    this.simulatingTransition = false;
                }, 2000);
            }
        },

        async deleteTransitionGif(target = 'all') {
            const label = target === 'isload' ? 'isLoad (saat memuat)' : (target === 'loaded' || target === 'load' ? 'load (saat selesai)' : 'seluruh');
            if (!confirm(`Hapus file GIF animasi transisi ${label}?`)) return;
            this.uploading = true;
            try {
                const res = await fetch(`{{ route('admin.settings.api.transition_gif.delete') }}?target=${target}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (target === 'all' || target === 'isload') this.pageTransitionGifIsloadUrl = '';
                    if (target === 'all' || target === 'loaded' || target === 'load') this.pageTransitionGifLoadedUrl = '';
                    this.showToast(data.message);
                } else {
                    this.showToast(data.message || 'Gagal menghapus file.', 'error');
                }
            } catch (err) {
                this.showToast('Terjadi kesalahan saat menghapus file.', 'error');
            } finally {
                this.uploading = false;
            }
        },

        async uploadFile(event, type) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);
            formData.append('_token', '{{ csrf_token() }}');

            this.uploading = true;
            try {
                const res = await fetch('{{ route('admin.settings.api.logo') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (type === 'logo') this.logoUrl = data.url;
                    if (type === 'logo_dark') this.logoDarkUrl = data.url;
                    if (type === 'favicon') this.faviconUrl = data.url;
                    if (type === 'og_image') this.seoOgImageUrl = data.url;
                    if (type === 'transition_gif' || type === 'page_transition_gif' || type === 'page_transition_gif_isload') this.pageTransitionGifIsloadUrl = data.url;
                    if (type === 'page_transition_gif_loaded' || type === 'page_transition_gif_load') this.pageTransitionGifLoadedUrl = data.url;
                    this.showToast(data.message || 'File berhasil diunggah!');
                } else {
                    this.showToast(data.message || 'Gagal mengunggah file.', 'error');
                }
            } catch (err) {
                this.showToast('Terjadi kesalahan saat mengunggah file.', 'error');
            } finally {
                this.uploading = false;
            }
        }
     }">

    <!-- Toast Notification (Top-Right Floating Toast) -->
    <template x-if="toast">
        <div class="fixed top-6 right-6 z-[99999] flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur-2xl border transition-all duration-300 animate-in fade-in slide-in-from-top-4"
             :class="toast.type === 'error' ? 'bg-red-950/95 border-red-500/40 text-red-200 shadow-red-500/20' : 'bg-emerald-950/95 border-emerald-500/40 text-emerald-200 shadow-emerald-500/20'">
            <div class="w-6 h-6 rounded-xl flex items-center justify-center shrink-0"
                 :class="toast.type === 'error' ? 'bg-red-500/20 text-red-400' : 'bg-emerald-500/20 text-emerald-400'">
                <template x-if="toast.type === 'error'">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </template>
                <template x-if="toast.type !== 'error'">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </template>
            </div>
            <span class="text-xs font-bold tracking-wide" x-text="toast.message"></span>
        </div>
    </template>

    <!-- Page Header & Overview -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/10">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                <i data-lucide="sliders-horizontal" class="w-5 h-5 text-white"></i>
                <span>Pengaturan Umum & CMS Global</span>
            </h1>
            <p class="text-xs text-zinc-400 mt-1">
                Kelola identitas merek, tema warna, SEO meta default, media sosial, dan status pemeliharaan situs dalam satu tempat.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($siteSetting->maintenance_mode)
                <span class="px-3 py-1.5 rounded-xl bg-red-500/20 border border-red-500/30 text-red-400 text-xs font-bold flex items-center gap-1.5 animate-pulse">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                    <span>Maintenance ON</span>
                </span>
            @else
                <span class="px-3 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center gap-1.5">
                    <span>Situs Online (Live)</span>
                </span>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-white/10 pb-3 overflow-x-auto no-scrollbar text-xs font-bold">
        <button type="button" @click="activeTab = 'branding'" 
                :class="activeTab === 'branding' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="tag" class="w-4 h-4"></i>
            <span>Branding & Logo</span>
        </button>

        <button type="button" @click="activeTab = 'colors'" 
                :class="activeTab === 'colors' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="palette" class="w-4 h-4"></i>
            <span>Warna Tema</span>
        </button>

        <button type="button" @click="activeTab = 'seo'" 
                :class="activeTab === 'seo' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="search" class="w-4 h-4"></i>
            <span>SEO & OpenGraph</span>
        </button>

        <button type="button" @click="activeTab = 'display'" 
                :class="activeTab === 'display' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="layout" class="w-4 h-4"></i>
            <span>Tampilan & Medsos</span>
        </button>

        <button type="button" @click="activeTab = 'transition'" 
                :class="activeTab === 'transition' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
            <span>Transisi Halaman</span>
        </button>

        <button type="button" @click="activeTab = 'maintenance'" 
                :class="activeTab === 'maintenance' ? 'bg-red-600 text-white shadow-md shadow-red-600/30' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="shield-alert" class="w-4 h-4"></i>
            <span>Maintenance</span>
        </button>

        <button type="button" @click="activeTab = 'features'" 
                :class="activeTab === 'features' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="toggle-left" class="w-4 h-4"></i>
            <span>Feature Flags</span>
        </button>

        <button type="button" @click="activeTab = 'apikeys'" 
                :class="activeTab === 'apikeys' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="key" class="w-4 h-4"></i>
            <span>API Keys</span>
        </button>

        <button type="button" @click="activeTab = 'featured'" 
                :class="activeTab === 'featured' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900/90 text-zinc-400 hover:text-white border border-white/10'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="film" class="w-4 h-4"></i>
            <span>Hero Slider</span>
        </button>
    </div>

    <!-- Main Settings Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- ==================== TAB 1: BRANDING & IDENTITAS ==================== -->
        <div x-show="activeTab === 'branding'" class="space-y-6">
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Identitas & Logo Platform</h3>
                        <p class="text-xs text-zinc-400">Pengaturan nama brand, slogan tagline, logo utama, dan favicon browser.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Site Name -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Nama Platform / Brand *</label>
                        <input type="text" name="site_name" x-model="siteName" required 
                               class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white focus:outline-none focus:border-white/40 font-medium transition">
                        <p class="text-[11px] text-zinc-500">Nama utama yang tampil pada header, title tab browser, dan meta SEO.</p>
                    </div>

                    <!-- Tagline -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Tagline / Slogan</label>
                        <input type="text" name="site_tagline" x-model="siteTagline" 
                               class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white focus:outline-none focus:border-white/40 font-medium transition">
                        <p class="text-[11px] text-zinc-500">Deskripsi singkat di samping logo atau di bawah brand.</p>
                    </div>
                </div>

                <!-- Logo Upload Section Grid -->
                <div class="pt-6 border-t border-white/10 grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Logo Utama (Light Mode / Standard) -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-3 flex flex-col justify-between">
                        <div>
                            <span class="block text-xs font-bold text-white uppercase tracking-wider">Logo Utama (Header)</span>
                            <p class="text-[11px] text-zinc-400 mt-0.5">Format PNG, SVG, WebP transparan (Maks. 5MB).</p>
                        </div>

                        <div class="w-full h-24 rounded-xl bg-zinc-900 border border-white/10 p-3 flex items-center justify-center overflow-hidden">
                            <img :src="logoUrl" alt="Logo Preview" class="max-h-full max-w-full object-contain">
                        </div>

                        <div>
                            <label class="block w-full py-2 px-3 rounded-xl bg-white/10 hover:bg-white hover:text-zinc-950 text-zinc-200 text-center text-xs font-bold transition cursor-pointer border border-white/10">
                                <span>Ganti Logo Utama</span>
                                <input type="file" name="logo" @change="uploadFile($event, 'logo')" accept="image/*" class="hidden">
                            </label>
                        </div>
                    </div>

                    <!-- Logo Dark Mode (Opsional) -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-3 flex flex-col justify-between">
                        <div>
                            <span class="block text-xs font-bold text-white uppercase tracking-wider">Logo Dark Mode (Opsional)</span>
                            <p class="text-[11px] text-zinc-400 mt-0.5">Versi logo kontras tinggi untuk latar gelap.</p>
                        </div>

                        <div class="w-full h-24 rounded-xl bg-zinc-900 border border-white/10 p-3 flex items-center justify-center overflow-hidden">
                            <img :src="logoDarkUrl" alt="Logo Dark Preview" class="max-h-full max-w-full object-contain">
                        </div>

                        <div>
                            <label class="block w-full py-2 px-3 rounded-xl bg-white/10 hover:bg-white hover:text-zinc-950 text-zinc-200 text-center text-xs font-bold transition cursor-pointer border border-white/10">
                                <span>Ganti Logo Dark</span>
                                <input type="file" name="logo_dark" @change="uploadFile($event, 'logo_dark')" accept="image/*" class="hidden">
                            </label>
                        </div>
                    </div>

                    <!-- Favicon Browser -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-3 flex flex-col justify-between">
                        <div>
                            <span class="block text-xs font-bold text-white uppercase tracking-wider">Favicon Browser</span>
                            <p class="text-[11px] text-zinc-400 mt-0.5">Ikon tab browser (.ico, .png, .svg rasio 1:1).</p>
                        </div>

                        <div class="w-full h-24 rounded-xl bg-zinc-900 border border-white/10 p-3 flex items-center justify-center">
                            <div class="w-12 h-12 rounded-xl bg-zinc-950 border border-white/10 p-2 flex items-center justify-center shadow-inner">
                                <img :src="faviconUrl" alt="Favicon Preview" class="w-8 h-8 object-contain">
                            </div>
                        </div>

                        <div>
                            <label class="block w-full py-2 px-3 rounded-xl bg-white/10 hover:bg-white hover:text-zinc-950 text-zinc-200 text-center text-xs font-bold transition cursor-pointer border border-white/10">
                                <span>Ganti Favicon</span>
                                <input type="file" name="favicon" @change="uploadFile($event, 'favicon')" accept=".ico,image/png,image/svg+xml" class="hidden">
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ==================== TAB 2: WARNA TEMA & CSS PROPERTIES ==================== -->
        <div x-show="activeTab === 'colors'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="palette" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Warna Tema Global (CSS Custom Properties)</h3>
                        <p class="text-xs text-zinc-400">Atur palet warna platform secara dinamis tanpa perlu melakukan rebuild kode.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Color Pickers Form Column -->
                    <div class="space-y-4">
                        
                        <!-- Primary Color -->
                        <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-2">
                            <label class="block text-xs font-bold text-white uppercase tracking-wider">Primary Color (Warna Utama)</label>
                            <p class="text-[11px] text-zinc-400">Digunakan untuk tombol aksi utama, teks tajuk, dan highlight.</p>
                            <div class="flex items-center gap-3 pt-1">
                                <input type="color" x-model="primaryColor" class="w-10 h-10 rounded-xl border border-white/20 bg-transparent cursor-pointer">
                                <input type="text" name="primary_color" x-model="primaryColor" 
                                       class="flex-1 bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none focus:border-white/40">
                            </div>
                        </div>

                        <!-- Secondary Color -->
                        <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-2">
                            <label class="block text-xs font-bold text-white uppercase tracking-wider">Secondary Color (Warna Sekunder)</label>
                            <p class="text-[11px] text-zinc-400">Digunakan untuk teks deskripsi pendukung, metadata, dan garis pemisah.</p>
                            <div class="flex items-center gap-3 pt-1">
                                <input type="color" x-model="secondaryColor" class="w-10 h-10 rounded-xl border border-white/20 bg-transparent cursor-pointer">
                                <input type="text" name="secondary_color" x-model="secondaryColor" 
                                       class="flex-1 bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none focus:border-white/40">
                            </div>
                        </div>

                        <!-- Background Color -->
                        <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 space-y-2">
                            <label class="block text-xs font-bold text-white uppercase tracking-wider">Background Color (Latar Belakang)</label>
                            <p class="text-[11px] text-zinc-400">Warna dasar kanvas seluruh platform web.</p>
                            <div class="flex items-center gap-3 pt-1">
                                <input type="color" x-model="backgroundColor" class="w-10 h-10 rounded-xl border border-white/20 bg-transparent cursor-pointer">
                                <input type="text" name="background_color" x-model="backgroundColor" 
                                       class="flex-1 bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none focus:border-white/40">
                            </div>
                        </div>

                    </div>

                    <!-- Interactive Live Preview Card -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                <span>Live Preview Tampilan Tema</span>
                            </span>
                            <span class="text-[10px] text-zinc-400 font-mono">Real-time simulator</span>
                        </div>

                        <div class="p-6 rounded-3xl border border-white/15 shadow-2xl transition-colors duration-300 space-y-5"
                             :style="`background-color: ${backgroundColor};`">
                            
                            <!-- Mini Mockup Navbar -->
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
                                <div class="flex items-center gap-2">
                                    <img :src="logoUrl" alt="Logo" class="h-6 w-auto object-contain">
                                    <span class="font-bold text-sm tracking-tight" :style="`color: ${primaryColor};`" x-text="siteName || 'Faiilmov'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border"
                                          :style="`border-color: ${secondaryColor}40; color: ${primaryColor}; background-color: ${primaryColor}15;`">
                                        ★ 8.9 HD
                                    </span>
                                </div>
                            </div>

                            <!-- Mini Hero Card Content -->
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10 space-y-3">
                                <span class="text-[9px] font-mono font-bold uppercase tracking-widest px-2 py-0.5 rounded"
                                      :style="`background-color: ${primaryColor}20; color: ${primaryColor};`">
                                    Streaming Premiere
                                </span>
                                <h4 class="text-base font-extrabold" :style="`color: ${primaryColor};`">
                                    Spider-Man: Homecoming (2017)
                                </h4>
                                <p class="text-xs leading-relaxed line-clamp-2" :style="`color: ${secondaryColor};`">
                                    Peter Parker berusaha menyeimbangkan kehidupan sekolah menengahnya dengan tanggung jawab sebagai pahlawan super.
                                </p>
                                <div class="pt-2 flex items-center gap-2">
                                    <button type="button" class="px-4 py-2 rounded-xl text-xs font-bold shadow-md cursor-default transition-all"
                                            :style="`background-color: ${primaryColor}; color: ${backgroundColor};`">
                                        ▶ Mulai Nonton
                                    </button>
                                    <button type="button" class="px-3 py-2 rounded-xl text-xs font-semibold border border-white/10 cursor-default"
                                            :style="`color: ${secondaryColor};`">
                                        + Watchlist
                                    </button>
                                </div>
                            </div>

                            <p class="text-[11px] text-zinc-500 text-center font-mono">
                                CSS Variables: <code class="text-zinc-400">--site-primary-color</code>, <code class="text-zinc-400">--site-secondary-color</code>, <code class="text-zinc-400">--site-bg-color</code>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ==================== TAB 3: SEO & OPENGRAPH DEFAULT ==================== -->
        <div x-show="activeTab === 'seo'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">SEO & Meta Tags Default</h3>
                        <p class="text-xs text-zinc-400">Konfigurasi optimasi mesin pencari (Google) dan kartu pratinjau sosial (WhatsApp, Facebook, Twitter/X).</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <!-- SEO Title -->
                    <div class="space-y-1.5" x-data="{ count: '{{ strlen(old('seo_meta_title', $siteSetting->seo_meta_title)) }}' }">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Default Meta Title *</label>
                            <span class="text-[10px] font-mono text-zinc-500"><span x-text="count"></span> / 70 karakter ideal</span>
                        </div>
                        <input type="text" name="seo_meta_title" value="{{ old('seo_meta_title', $siteSetting->seo_meta_title) }}" required 
                               @input="count = $event.target.value.length"
                               class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white focus:outline-none focus:border-white/40 font-medium transition">
                    </div>

                    <!-- SEO Description -->
                    <div class="space-y-1.5" x-data="{ count: '{{ strlen(old('seo_meta_description', $siteSetting->seo_meta_description)) }}' }">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Default Meta Description</label>
                            <span class="text-[10px] font-mono text-zinc-500"><span x-text="count"></span> / 160 karakter ideal</span>
                        </div>
                        <textarea name="seo_meta_description" rows="3" 
                                  @input="count = $event.target.value.length"
                                  class="w-full bg-zinc-950 border border-white/10 rounded-2xl p-4 text-xs text-white focus:outline-none focus:border-white/40 leading-relaxed transition">{{ old('seo_meta_description', $siteSetting->seo_meta_description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Keywords -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Meta Keywords (Pisahkan Koma)</label>
                            <input type="text" name="seo_meta_keywords" value="{{ old('seo_meta_keywords', $siteSetting->seo_meta_keywords) }}" 
                                   placeholder="nonton film, streaming gratis, film sub indo, moviebox"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white focus:outline-none focus:border-white/40 font-medium transition">
                        </div>

                        <!-- Canonical URL -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Canonical Domain Base</label>
                            <input type="url" name="seo_canonical_url" value="{{ old('seo_canonical_url', $siteSetting->seo_canonical_url) }}" 
                                   placeholder="https://faiilmov.my.id"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white focus:outline-none focus:border-white/40 font-mono transition">
                        </div>
                    </div>

                    <!-- Default Social Share Card (OG Image) -->
                    <div class="pt-4 border-t border-white/10 flex flex-col sm:flex-row sm:items-center gap-6">
                        <div class="w-40 aspect-[1200/630] rounded-2xl bg-zinc-950 border border-white/10 p-2 flex items-center justify-center shrink-0 overflow-hidden shadow-inner">
                            <img :src="seoOgImageUrl" alt="OG Preview" class="w-full h-full object-cover rounded-xl">
                        </div>
                        <div class="flex-1 space-y-2">
                            <label class="block text-xs font-bold text-white uppercase tracking-wider">Default OpenGraph (OG) Share Image</label>
                            <p class="text-[11px] text-zinc-400">Gambar yang otomatis muncul saat link website dibagikan di WhatsApp, Twitter/X, Discord, atau Telegram (Ukuran rekomendasi 1200x630 px).</p>
                            
                            <label class="inline-flex items-center gap-2 py-2 px-4 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition cursor-pointer shadow-md">
                                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                <span>Unggah Gambar Social Share</span>
                                <input type="file" name="seo_og_image" @change="uploadFile($event, 'og_image')" accept="image/*" class="hidden">
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ==================== TAB 4: TAMPILAN UMUM & MEDIA SOSIAL ==================== -->
        <div x-show="activeTab === 'display'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="layout" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Tampilan Footer & Tautan Sosial Media</h3>
                        <p class="text-xs text-zinc-400">Kelola teks hak cipta footer, email kontak bantuan, serta akun media sosial resmi platform.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Footer Text -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Teks Copyright Footer</label>
                        <textarea name="footer_text" rows="3" 
                                  class="w-full bg-zinc-950 border border-white/10 rounded-2xl p-4 text-xs text-white focus:outline-none focus:border-white/40 leading-relaxed transition">{{ old('footer_text', $siteSetting->footer_text) }}</textarea>
                    </div>

                    <!-- Contact Email -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Email Kontak / Bantuan Support</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $siteSetting->contact_email) }}" 
                               class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white focus:outline-none focus:border-white/40 font-medium transition">
                        <p class="text-[11px] text-zinc-500">Email yang ditampilkan di footer & halaman kebijakan privasi.</p>
                    </div>
                </div>

                <!-- Dynamic Social Media Links Section -->
                <div class="pt-6 border-t border-white/10 space-y-4">
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="share-2" class="w-4 h-4 text-white"></i>
                        <span>Tautan Akun Media Sosial Resmi</span>
                    </h4>

                    @php
                        $socials = is_array($siteSetting->social_links) ? $siteSetting->social_links : [];
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Instagram -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
                                <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                <span>Instagram URL</span>
                            </label>
                            <input type="url" name="social_links[instagram]" value="{{ old('social_links.instagram', $socials['instagram'] ?? '') }}" 
                                   placeholder="https://instagram.com/faiilmov"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-white/40 font-mono transition">
                        </div>

                        <!-- Twitter / X -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
                                <i data-lucide="twitter" class="w-3.5 h-3.5"></i>
                                <span>Twitter / X URL</span>
                            </label>
                            <input type="url" name="social_links[twitter]" value="{{ old('social_links.twitter', $socials['twitter'] ?? '') }}" 
                                   placeholder="https://x.com/faiilmov"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-white/40 font-mono transition">
                        </div>

                        <!-- Telegram -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Telegram Channel URL</span>
                            </label>
                            <input type="url" name="social_links[telegram]" value="{{ old('social_links.telegram', $socials['telegram'] ?? '') }}" 
                                   placeholder="https://t.me/faiilmov"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-white/40 font-mono transition">
                        </div>

                        <!-- Discord -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
                                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                <span>Discord Server URL</span>
                            </label>
                            <input type="url" name="social_links[discord]" value="{{ old('social_links.discord', $socials['discord'] ?? '') }}" 
                                   placeholder="https://discord.gg/faiilmov"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-white/40 font-mono transition">
                        </div>

                        <!-- YouTube -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
                                <i data-lucide="youtube" class="w-3.5 h-3.5"></i>
                                <span>YouTube Channel URL</span>
                            </label>
                            <input type="url" name="social_links[youtube]" value="{{ old('social_links.youtube', $socials['youtube'] ?? '') }}" 
                                   placeholder="https://youtube.com/@faiilmov"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-white/40 font-mono transition">
                        </div>

                        <!-- TikTok -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
                                <i data-lucide="video" class="w-3.5 h-3.5"></i>
                                <span>TikTok URL</span>
                            </label>
                            <input type="url" name="social_links[tiktok]" value="{{ old('social_links.tiktok', $socials['tiktok'] ?? '') }}" 
                                   placeholder="https://tiktok.com/@faiilmov"
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-white/40 font-mono transition">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ==================== TAB: TRANSISI HALAMAN (PAGE TRANSITION LOADER) ==================== -->
        <div x-show="activeTab === 'transition'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                <!-- Section Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Animasi Transisi Halaman (2 Kondisi: isLoad & load)</h3>
                            <p class="text-xs text-zinc-400">Atur 2 animasi GIF berbeda untuk fase mulai memuat (<code class="text-amber-400 font-mono">isLoad</code>) dan fase selesai memuat (<code class="text-emerald-400 font-mono">load</code>).</p>
                        </div>
                    </div>

                    <button type="button" @click="playSimulation()" 
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs flex items-center gap-2 transition cursor-pointer shadow-lg shadow-amber-500/20 shrink-0">
                        <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>
                        <span>Simulasi Rangkaian Transisi</span>
                    </button>
                </div>

                <!-- Info Alert -->
                <div class="p-4 rounded-2xl bg-zinc-950 border border-white/10 flex items-start gap-3 text-zinc-300 text-xs">
                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5 text-amber-400"></i>
                    <div class="space-y-1">
                        <strong class="font-bold text-white">Alur Kerja 2 Animasi Transisi:</strong>
                        <ul class="list-disc list-inside text-zinc-400 space-y-0.5 text-[11px] leading-relaxed">
                            <li><span class="text-amber-300 font-bold">1. Fase isLoad:</span> Diputar saat penonton mengklik tautan navigasi dan meninggalkan halaman sebelumnya.</li>
                            <li><span class="text-emerald-300 font-bold">2. Fase load:</span> Diputar sesaat begitu halaman baru berhasil dimuat sebelum overlay menghilang secara mulus.</li>
                        </ul>
                    </div>
                </div>

                <!-- Enable / Disable Switch -->
                <div class="p-5 rounded-2xl bg-zinc-950 border border-white/10 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="font-bold text-sm text-white">Aktifkan Animasi Transisi Halaman</span>
                        <p class="text-xs text-zinc-400">Jika dimatikan, halaman akan berganti secara standar tanpa efek animasi overlay.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="page_transition_enabled" value="1" 
                               x-model="pageTransitionEnabled"
                               {{ $siteSetting->page_transition_enabled ? 'checked' : '' }} 
                               class="sr-only peer">
                        <div class="w-12 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                </div>

                <!-- Dual GIF Upload & Preview Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    
                    <!-- 1. isLoad GIF (Saat Mulai Memuat) -->
                    <div class="p-5 rounded-2xl bg-zinc-950 border border-white/10 space-y-4">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-400 text-[10px] font-mono font-bold uppercase">Kondisi 1</span>
                                <h4 class="text-xs font-bold text-white">Animasi Saat Memuat (isLoad)</h4>
                            </div>
                            <span class="text-[10px] text-zinc-500">Mulai Navigasi</span>
                        </div>

                        <!-- Upload Area -->
                        <div class="p-4 rounded-xl bg-zinc-900/60 border-2 border-dashed border-white/10 hover:border-amber-400/40 transition text-center space-y-2 group">
                            <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-amber-400 mx-auto">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Upload GIF isLoad</p>
                                <p class="text-[10px] text-zinc-500">.gif, .webp (Maks 5 MB)</p>
                            </div>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-bold transition cursor-pointer">
                                <span>Pilih File GIF</span>
                                <input type="file" name="page_transition_gif" accept=".gif,.webp,image/gif,image/webp" 
                                       @change="uploadFile($event, 'page_transition_gif_isload')" class="hidden">
                            </label>
                        </div>

                        <!-- Preview Area -->
                        <div class="min-h-[140px] rounded-xl bg-black border border-white/10 flex flex-col items-center justify-center p-4 text-center">
                            <template x-if="pageTransitionGifIsloadUrl">
                                <div class="space-y-3 flex flex-col items-center">
                                    <img :src="pageTransitionGifIsloadUrl" alt="isLoad GIF Preview" class="max-h-20 max-w-[120px] object-contain mx-auto rounded">
                                    <button type="button" @click="deleteTransitionGif('isload')" 
                                            class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-bold text-[11px] flex items-center gap-1 transition cursor-pointer">
                                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                                        <span>Hapus GIF isLoad</span>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!pageTransitionGifIsloadUrl">
                                <div class="space-y-1 py-2 text-zinc-600">
                                    <i data-lucide="image-off" class="w-5 h-5 mx-auto"></i>
                                    <p class="text-[11px] font-bold text-zinc-500">Belum ada GIF isLoad</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 2. load GIF (Saat Selesai Memuat) -->
                    <div class="p-5 rounded-2xl bg-zinc-950 border border-white/10 space-y-4">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-[10px] font-mono font-bold uppercase">Kondisi 2</span>
                                <h4 class="text-xs font-bold text-white">Animasi Selesai Memuat (load)</h4>
                            </div>
                            <span class="text-[10px] text-zinc-500">Tiba di Halaman Baru</span>
                        </div>

                        <!-- Upload Area -->
                        <div class="p-4 rounded-xl bg-zinc-900/60 border-2 border-dashed border-white/10 hover:border-emerald-400/40 transition text-center space-y-2 group">
                            <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-zinc-400 group-hover:text-emerald-400 mx-auto">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Upload GIF load</p>
                                <p class="text-[10px] text-zinc-500">.gif, .webp (Maks 5 MB)</p>
                            </div>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-bold transition cursor-pointer">
                                <span>Pilih File GIF</span>
                                <input type="file" name="page_transition_gif_loaded" accept=".gif,.webp,image/gif,image/webp" 
                                       @change="uploadFile($event, 'page_transition_gif_loaded')" class="hidden">
                            </label>
                        </div>

                        <!-- Preview Area -->
                        <div class="min-h-[140px] rounded-xl bg-black border border-white/10 flex flex-col items-center justify-center p-4 text-center">
                            <template x-if="pageTransitionGifLoadedUrl">
                                <div class="space-y-3 flex flex-col items-center">
                                    <img :src="pageTransitionGifLoadedUrl" alt="load GIF Preview" class="max-h-20 max-w-[120px] object-contain mx-auto rounded">
                                    <button type="button" @click="deleteTransitionGif('loaded')" 
                                            class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-bold text-[11px] flex items-center gap-1 transition cursor-pointer">
                                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                                        <span>Hapus GIF load</span>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!pageTransitionGifLoadedUrl">
                                <div class="space-y-1 py-2 text-zinc-600">
                                    <i data-lucide="image-off" class="w-5 h-5 mx-auto"></i>
                                    <p class="text-[11px] font-bold text-zinc-500">Belum ada GIF load</p>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ==================== TAB 5: MAINTENANCE MODE ==================== -->
        <div x-show="activeTab === 'maintenance'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-red-500/30 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4 text-red-400">
                    <div class="w-9 h-9 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center text-red-400 shrink-0">
                        <i data-lucide="shield-alert" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Mode Pemeliharaan (Maintenance Mode)</h3>
                        <p class="text-xs text-zinc-400">Alihkan seluruh situs publik ke tampilan Error 503 saat pembaruan server atau perbaikan berlangsung.</p>
                    </div>
                </div>

                <!-- Admin Access Guarantee Notice -->
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-start gap-3 text-emerald-300 text-xs">
                    <i data-lucide="shield-check" class="w-4 h-4 shrink-0 mt-0.5 text-emerald-400"></i>
                    <div>
                        <strong class="font-bold text-white">Jaminan Keamanan Akses Admin:</strong>
                        <p class="mt-0.5 text-emerald-200/90">
                            Saat mode maintenance aktif, akun admin serta seluruh rute panel CMS (`/admin/*`) **TETAP BISA DIAKSES SECARA PENUH** sehingga Anda tidak akan pernah terkunci dari sistem.
                        </p>
                    </div>
                </div>

                <!-- Maintenance Toggle Switch -->
                <div class="p-5 rounded-2xl bg-zinc-950 border border-white/10 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="font-bold text-sm text-white">Aktifkan Status Maintenance Mode</span>
                        <p class="text-xs text-zinc-400">Pengunjung biasa akan diarahkan ke halaman pemeliharaan 503.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" {{ $siteSetting->maintenance_mode ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-12 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                    </label>
                </div>

                <!-- Custom Maintenance Message -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">Pesan Pengumuman untuk Pengunjung</label>
                    <textarea name="maintenance_message" rows="3" 
                              class="w-full bg-zinc-950 border border-white/10 rounded-2xl p-4 text-xs text-white focus:outline-none focus:border-red-500 leading-relaxed transition">{{ old('maintenance_message', $siteSetting->maintenance_message) }}</textarea>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 6: FEATURE FLAGS ==================== -->
        <div x-show="activeTab === 'features'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="toggle-left" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Saklar Fitur Platform (Feature Flags)</h3>
                        <p class="text-xs text-zinc-400">Aktifkan atau matikan fitur modul platform secara instan.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Watch Party Switch -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-white/10 hover:border-white/20 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="tv" class="w-4 h-4 text-emerald-400"></i>
                                <span class="font-bold text-xs text-white">Watch Party & Room Nobar</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Izinkan pengguna membuat ruangan nobar sinkron real-time.</p>
                        </div>
                        <input type="checkbox" name="feature_watch_party" value="1" {{ $legacySettings['feature_watch_party'] ? 'checked' : '' }} class="w-5 h-5 accent-white rounded cursor-pointer">
                    </label>

                    <!-- Dracin Switch -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-white/10 hover:border-white/20 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clapperboard" class="w-4 h-4 text-rose-400"></i>
                                <span class="font-bold text-xs text-white">Saluran Dracin (Drama Pendek)</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Tampilkan feed vertikal dracin & drama mini di beranda.</p>
                        </div>
                        <input type="checkbox" name="feature_dracin" value="1" {{ $legacySettings['feature_dracin'] ? 'checked' : '' }} class="w-5 h-5 accent-white rounded cursor-pointer">
                    </label>

                    <!-- AI Auto Rate -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-white/10 hover:border-white/20 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i>
                                <span class="font-bold text-xs text-white">AI Content Rating Auto-Detection</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Deteksi batas usia dan sensor film otomatis saat impor.</p>
                        </div>
                        <input type="checkbox" name="feature_ai_autorate" value="1" {{ $legacySettings['feature_ai_autorate'] ? 'checked' : '' }} class="w-5 h-5 accent-white rounded cursor-pointer">
                    </label>

                    <!-- User Registration Switch -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-white/10 hover:border-white/20 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="user-plus" class="w-4 h-4 text-blue-400"></i>
                                <span class="font-bold text-xs text-white">Pendaftaran User Baru</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Buka/tutup form registrasi akun baru untuk publik.</p>
                        </div>
                        <input type="checkbox" name="feature_registration" value="1" {{ $legacySettings['feature_registration'] ? 'checked' : '' }} class="w-5 h-5 accent-white rounded cursor-pointer">
                    </label>

                </div>
            </div>
        </div>

        <!-- ==================== TAB 7: API KEYS & CREDENTIALS ==================== -->
        <div x-show="activeTab === 'apikeys'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-white/10 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="key" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Kredensial & Kunci API Integrasi</h3>
                        <p class="text-xs text-zinc-400">Kunci API disamarkan secara otomatis. Klik ikon mata untuk melihat atau menyunting.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- MovieBox API Key -->
                    <div x-data="{ show: false }">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">MovieBox API Gateway Key</label>
                        <div class="relative flex items-center">
                            <input :type="show ? 'text' : 'password'" 
                                   name="moviebox_api_key" 
                                   value="{{ $legacySettings['moviebox_api_key'] }}" 
                                   placeholder="••••••••••••••••••••••••••••" 
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-white/40 font-mono pr-12">
                            <button type="button" @click="show = !show" class="absolute right-3 text-zinc-400 hover:text-white p-1 cursor-pointer">
                                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- NVIDIA API Key -->
                    <div x-data="{ show: false }">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">NVIDIA NIM / AI Inference Key</label>
                        <div class="relative flex items-center">
                            <input :type="show ? 'text' : 'password'" 
                                   name="nvidia_api_key" 
                                   value="{{ $legacySettings['nvidia_api_key'] }}" 
                                   placeholder="nvapi-••••••••••••••••••••••••" 
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-white/40 font-mono pr-12">
                            <button type="button" @click="show = !show" class="absolute right-3 text-zinc-400 hover:text-white p-1 cursor-pointer">
                                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- iTunes / TMDB Key -->
                    <div x-data="{ show: false }">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">iTunes / Apple Search API Key</label>
                        <div class="relative flex items-center">
                            <input :type="show ? 'text' : 'password'" 
                                   name="itunes_api_key" 
                                   value="{{ $legacySettings['itunes_api_key'] }}" 
                                   placeholder="••••••••••••••••••••••••••••" 
                                   class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-white/40 font-mono pr-12">
                            <button type="button" @click="show = !show" class="absolute right-3 text-zinc-400 hover:text-white p-1 cursor-pointer">
                                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 8: HERO BANNER SLIDER ==================== -->
        <div x-show="activeTab === 'featured'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-4" x-data="{ filmSearch: '', typeFilter: 'all' }">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-white/10 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-white">Film Pilihan Hero Banner Carousel</h3>
                        <p class="text-xs text-zinc-400">Pilih film atau serial yang akan dipajang di slider utama halaman depan.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Type Filter Pills -->
                        <div class="flex items-center bg-zinc-950 p-1 rounded-full border border-white/10 text-[10px]">
                            <button type="button" @click="typeFilter = 'all'" :class="typeFilter === 'all' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full uppercase transition-colors cursor-pointer">Semua</button>
                            <button type="button" @click="typeFilter = 'movie'" :class="typeFilter === 'movie' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full uppercase transition-colors cursor-pointer">Movie</button>
                            <button type="button" @click="typeFilter = 'series'" :class="typeFilter === 'series' ? 'bg-white text-zinc-950 font-bold' : 'text-zinc-400 hover:text-white'" class="px-3 py-1 rounded-full uppercase transition-colors cursor-pointer">Series</button>
                        </div>

                        <!-- Search Input Bar -->
                        <div class="relative w-full sm:w-48">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" 
                                   x-model="filmSearch" 
                                   placeholder="Cari judul..." 
                                   class="w-full bg-zinc-950 border border-white/10 rounded-full pl-8 pr-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/40">
                        </div>
                    </div>
                </div>

                <!-- Tile Grid Container -->
                <div class="p-3 bg-zinc-950 border border-white/10 rounded-2xl max-h-[440px] overflow-y-auto admin-scrollbar">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($allFilms as $f)
                            @php $isSelected = in_array($f->id, old('featured_film_ids', $legacySettings['featured_film_ids'])); @endphp
                            <label x-show="(typeFilter === 'all' || typeFilter === '{{ $f->subject_type }}') && (!filmSearch || '{{ strtolower(addslashes($f->title)) }}'.includes(filmSearch.toLowerCase()))" 
                                   class="relative group cursor-pointer select-none">
                                <input type="checkbox" name="featured_film_ids[]" value="{{ $f->id }}" {{ $isSelected ? 'checked' : '' }} class="peer hidden">
                                
                                <div class="h-full border border-white/10 rounded-2xl overflow-hidden bg-zinc-900/80 transition-all duration-200 
                                            peer-checked:border-white peer-checked:ring-2 peer-checked:ring-white/40 peer-checked:bg-zinc-800 
                                            group-hover:border-zinc-700 flex flex-col justify-between">
                                    
                                    <!-- Poster Image -->
                                    <div class="relative w-full aspect-[2/3] overflow-hidden bg-zinc-950">
                                        <img src="{{ $f->poster_url }}" alt="{{ $f->title }}" referrerpolicy="no-referrer" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        
                                        <!-- Selection Badge Overlay -->
                                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full bg-white text-zinc-950 font-extrabold flex items-center justify-center shadow-lg transition-all transform scale-0 peer-checked:scale-100">
                                            <i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>
                                        </div>

                                        <!-- Subject Type Badge -->
                                        <div class="absolute bottom-2 left-2 px-1.5 py-0.5 rounded bg-zinc-950/80 border border-white/10 text-[9px] font-extrabold uppercase text-white tracking-wider">
                                            {{ strtoupper($f->subject_type) }}
                                        </div>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="p-2.5 space-y-1">
                                        <p class="font-bold text-white text-xs line-clamp-1 leading-snug group-hover:text-amber-400 transition-colors">{{ $f->title }}</p>
                                        <p class="text-[10px] text-zinc-400 font-mono">{{ $f->release_year }}</p>
                                    </div>

                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Spacer for Floating Bar -->
        <div class="h-20"></div>

        <!-- Floating Save Action Bar -->
        <div class="fixed bottom-6 right-6 z-40 flex items-center gap-3 bg-zinc-900/95 backdrop-blur-xl border border-white/15 p-2 sm:p-2.5 rounded-2xl shadow-2xl shadow-black/90 ring-1 ring-white/10 transition-all">
            <span class="text-xs text-zinc-400 font-medium px-2 hidden sm:inline">Pengaturan CMS Global</span>
            <button type="submit" 
                    :disabled="uploading"
                    class="px-6 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-extrabold text-xs shadow-lg shadow-white/10 active:scale-95 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Simpan Seluruh Pengaturan</span>
            </button>
        </div>

    </form>

    <!-- Admin Fullscreen Simulation Overlay (Supports isLoad and load Phases) -->
    <div x-show="simulatingTransition" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-black">
        
        <div class="flex flex-col items-center gap-4 animate-in fade-in zoom-in-95 duration-200">
            <!-- Dynamic GIF based on simulatingPhase -->
            <template x-if="simulatingPhase === 'isLoad' && pageTransitionGifIsloadUrl">
                <img :src="pageTransitionGifIsloadUrl" alt="isLoad Simulation" class="max-w-[170px] max-h-[170px] object-contain drop-shadow-2xl">
            </template>
            <template x-if="simulatingPhase === 'load' && pageTransitionGifLoadedUrl">
                <img :src="pageTransitionGifLoadedUrl" alt="load Simulation" class="max-w-[170px] max-h-[170px] object-contain drop-shadow-2xl">
            </template>

            <!-- Fallback if one of the phase GIFs is not uploaded -->
            <template x-if="simulatingPhase === 'isLoad' && !pageTransitionGifIsloadUrl && pageTransitionGifLoadedUrl">
                <img :src="pageTransitionGifLoadedUrl" alt="load Simulation" class="max-w-[170px] max-h-[170px] object-contain drop-shadow-2xl">
            </template>
            <template x-if="simulatingPhase === 'load' && !pageTransitionGifLoadedUrl && pageTransitionGifIsloadUrl">
                <img :src="pageTransitionGifIsloadUrl" alt="isLoad Simulation" class="max-w-[170px] max-h-[170px] object-contain drop-shadow-2xl">
            </template>

            <!-- Phase Badge -->
            <div class="flex items-center gap-2 mt-2">
                <span class="text-[10px] uppercase tracking-widest font-mono font-extrabold px-3 py-1 rounded-full shadow-lg border"
                      :class="simulatingPhase === 'isLoad' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'"
                      x-text="simulatingPhase === 'isLoad' ? '1. Fase isLoad (Mulai Memuat / Meninggalkan Halaman)' : '2. Fase load (Selesai Memuat / Tiba di Halaman Baru)'">
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
