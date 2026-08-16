@extends('layouts.admin')

@section('title', 'Pengaturan Sistem & API | faiiladmin')
@section('page_title', 'Pengaturan Umum & Konfigurasi Sistem')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ activeTab: 'general' }">
    
    <!-- Tab Navigation Bar -->
    <div class="flex items-center gap-2 border-b border-zinc-800 pb-3 overflow-x-auto no-scrollbar text-xs font-bold">
        <button @click="activeTab = 'general'" 
                :class="activeTab === 'general' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="sliders" class="w-4 h-4"></i>
            <span>Umum & Branding</span>
        </button>

        <button @click="activeTab = 'features'" 
                :class="activeTab === 'features' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="toggle-left" class="w-4 h-4"></i>
            <span>Feature Flags</span>
        </button>

        <button @click="activeTab = 'apikeys'" 
                :class="activeTab === 'apikeys' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="key" class="w-4 h-4"></i>
            <span>API Keys & Credentials</span>
        </button>

        <button @click="activeTab = 'maintenance'" 
                :class="activeTab === 'maintenance' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="shield-alert" class="w-4 h-4"></i>
            <span>Maintenance Mode</span>
        </button>

        <button @click="activeTab = 'featured'" 
                :class="activeTab === 'featured' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="film" class="w-4 h-4"></i>
            <span>Hero Banner Slider</span>
        </button>
    </div>

    <!-- Main Settings Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- ==================== TAB 1: GENERAL & BRANDING ==================== -->
        <div x-show="activeTab === 'general'" class="space-y-6">
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-zinc-800 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white shrink-0">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white font-['Outfit']">Identitas & Branding Situs</h3>
                        <p class="text-xs text-zinc-400">Pengaturan nama platform, meta deskripsi, dan email support.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Site Name -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Nama Situs *</label>
                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required 
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-white/40 font-medium">
                    </div>

                    <!-- Support Email -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Email Kontak Bantuan / Support</label>
                        <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" 
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-white/40 font-medium">
                    </div>
                </div>

                <!-- Site Description -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Meta Deskripsi (SEO)</label>
                    <textarea name="site_description" rows="3" 
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs text-white focus:outline-none focus:border-white/40 leading-relaxed">{{ old('site_description', $settings['site_description']) }}</textarea>
                    <p class="text-[11px] text-zinc-500 mt-1">Deskripsi ini ditampilkan pada Google search results dan kartu share WhatsApp/Twitter.</p>
                </div>

                <!-- Logo Upload & Preview -->
                <div class="pt-4 border-t border-zinc-800 flex flex-col sm:flex-row sm:items-center gap-6">
                    <div class="w-20 h-20 rounded-2xl bg-zinc-950 border border-zinc-800 p-2 flex items-center justify-center shrink-0 shadow-inner">
                        <img src="{{ $settings['site_logo_url'] }}" alt="Logo Preview" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Unggah Logo Platform Baru</label>
                        <input type="file" name="logo" accept="image/*" 
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3 py-2 text-xs text-zinc-400 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-white hover:file:bg-zinc-700 cursor-pointer">
                        <p class="text-[11px] text-zinc-500 mt-1">Format didukung: PNG, SVG, WebP, JPG (Maks. 2MB).</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 2: FEATURE FLAGS ==================== -->
        <div x-show="activeTab === 'features'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-zinc-800 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 shrink-0">
                        <i data-lucide="toggle-right" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white font-['Outfit']">Saklar Fitur (Feature Flags)</h3>
                        <p class="text-xs text-zinc-400">Aktifkan atau nonaktifkan modul publik secara instan tanpa menyentuh source code.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Watch Party Switch -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="tv" class="w-4 h-4 text-indigo-400"></i>
                                <span class="font-bold text-xs text-white">Watch Party (Nobar)</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Mengizinkan pengguna membuat room nobar publik & privat.</p>
                        </div>
                        <input type="checkbox" name="feature_watch_party" value="1" {{ $settings['feature_watch_party'] ? 'checked' : '' }} class="w-5 h-5 accent-rose-500 rounded cursor-pointer">
                    </label>

                    <!-- Dracin Short Drama Switch -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="smartphone" class="w-4 h-4 text-rose-400"></i>
                                <span class="font-bold text-xs text-white">Modul Dracin (Drama Pendek)</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Menampilkan tab katalog dracin vertikal dan feed TikTok player.</p>
                        </div>
                        <input type="checkbox" name="feature_dracin" value="1" {{ $settings['feature_dracin'] ? 'checked' : '' }} class="w-5 h-5 accent-rose-500 rounded cursor-pointer">
                    </label>

                    <!-- AI Auto Rate Switch -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="bot" class="w-4 h-4 text-amber-400"></i>
                                <span class="font-bold text-xs text-white">AI Content Auto-Rating</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Klasifikasi otomatis rating usia (SU, 13+, 17+, 21+) via AI NVIDIA.</p>
                        </div>
                        <input type="checkbox" name="feature_ai_autorate" value="1" {{ $settings['feature_ai_autorate'] ? 'checked' : '' }} class="w-5 h-5 accent-rose-500 rounded cursor-pointer">
                    </label>

                    <!-- User Registration Switch -->
                    <label class="flex items-center justify-between p-4 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-700 transition-colors cursor-pointer select-none">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="user-plus" class="w-4 h-4 text-emerald-400"></i>
                                <span class="font-bold text-xs text-white">Pendaftaran User Baru</span>
                            </div>
                            <p class="text-[11px] text-zinc-400">Buka/tutup form registrasi akun baru untuk publik.</p>
                        </div>
                        <input type="checkbox" name="feature_registration" value="1" {{ $settings['feature_registration'] ? 'checked' : '' }} class="w-5 h-5 accent-rose-500 rounded cursor-pointer">
                    </label>

                </div>
            </div>
        </div>

        <!-- ==================== TAB 3: API KEYS (MASKED) ==================== -->
        <div x-show="activeTab === 'apikeys'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-zinc-800 pb-4">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                        <i data-lucide="key" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white font-['Outfit']">Kredensial & Kunci API (Tersamar)</h3>
                        <p class="text-xs text-zinc-400">Kunci API disamarkan secara otomatis demi keamanan. Klik tombol mata untuk melihat atau menyunting.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    
                    <!-- MovieBox API Key -->
                    <div x-data="{ show: false }">
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">MovieBox API Gateway Key</label>
                        <div class="relative flex items-center">
                            <input :type="show ? 'text' : 'password'" 
                                   name="moviebox_api_key" 
                                   value="{{ $settings['moviebox_api_key'] }}" 
                                   placeholder="••••••••••••••••••••••••••••" 
                                   class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500 font-mono pr-12">
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
                                   value="{{ $settings['nvidia_api_key'] }}" 
                                   placeholder="nvapi-••••••••••••••••••••••••" 
                                   class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500 font-mono pr-12">
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
                                   value="{{ $settings['itunes_api_key'] }}" 
                                   placeholder="••••••••••••••••••••••••••••" 
                                   class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500 font-mono pr-12">
                            <button type="button" @click="show = !show" class="absolute right-3 text-zinc-400 hover:text-white p-1 cursor-pointer">
                                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ==================== TAB 4: MAINTENANCE MODE ==================== -->
        <div x-show="activeTab === 'maintenance'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-rose-500/20 shadow-xl space-y-6">
                <div class="flex items-center gap-3 border-b border-zinc-800 pb-4 text-rose-400">
                    <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
                        <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white font-['Outfit']">Mode Pemeliharaan (Maintenance Mode)</h3>
                        <p class="text-xs text-zinc-400">Alihkan seluruh situs publik ke tampilan Error 503 saat pembaruan server berlangsung.</p>
                    </div>
                </div>

                <!-- Maintenance Toggle Box -->
                <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="font-bold text-xs text-white">Status Mode Maintenance</span>
                        <p class="text-[11px] text-zinc-400">Admin tetap dapat mengakses seluruh halaman admin dan situs publik.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" {{ $settings['maintenance_mode'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-500"></div>
                    </label>
                </div>

                <!-- Custom Maintenance Notice Message -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Pesan Pemberitahuan untuk Pengunjung</label>
                    <textarea name="maintenance_message" rows="3" 
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs text-white focus:outline-none focus:border-rose-500 leading-relaxed">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 5: HERO BANNER SLIDER ==================== -->
        <div x-show="activeTab === 'featured'" class="space-y-6" x-cloak>
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4" x-data="{ filmSearch: '', typeFilter: 'all' }">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-zinc-800 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-white font-['Outfit']">Film Pilihan Hero Banner Carousel</h3>
                        <p class="text-xs text-zinc-400">Pilih film atau serial yang akan dipajang di slider utama halaman depan.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Type Filter Pills -->
                        <div class="flex items-center bg-zinc-950 p-1 rounded-full border border-zinc-800 text-[10px]">
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
                                   class="w-full bg-zinc-950 border border-zinc-800 rounded-full pl-8 pr-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/40">
                        </div>
                    </div>
                </div>

                <!-- Tile Grid Container -->
                <div class="p-3 bg-zinc-950 border border-zinc-800 rounded-2xl max-h-[440px] overflow-y-auto admin-scrollbar">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($allFilms as $f)
                            @php $isSelected = in_array($f->id, old('featured_film_ids', $settings['featured_film_ids'])); @endphp
                            <label x-show="(typeFilter === 'all' || typeFilter === '{{ $f->subject_type }}') && (!filmSearch || '{{ strtolower(addslashes($f->title)) }}'.includes(filmSearch.toLowerCase()))" 
                                   class="relative group cursor-pointer select-none">
                                <input type="checkbox" name="featured_film_ids[]" value="{{ $f->id }}" {{ $isSelected ? 'checked' : '' }} class="peer hidden">
                                
                                <div class="h-full border border-zinc-800 rounded-2xl overflow-hidden bg-zinc-900/80 transition-all duration-200 
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
                                        <div class="absolute bottom-2 left-2 px-1.5 py-0.5 rounded bg-zinc-950/80 border border-zinc-800 text-[9px] font-extrabold uppercase text-white tracking-wider">
                                            {{ strtoupper($f->subject_type) }}
                                        </div>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="p-2.5 space-y-1">
                                        <p class="font-bold text-white text-xs line-clamp-1 leading-snug group-hover:text-rose-400 transition-colors">{{ $f->title }}</p>
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
        <div class="h-16"></div>

        <!-- Floating Bottom-Right Save Action Bar -->
        <div class="fixed bottom-6 right-6 z-40 flex items-center gap-3 bg-zinc-900/90 backdrop-blur-xl border border-white/15 p-2 sm:p-2.5 rounded-2xl shadow-2xl shadow-black/80 ring-1 ring-white/10 hover:border-amber-500/40 transition-all">
            <span class="text-[11px] text-zinc-400 font-medium px-2 hidden sm:inline">Konfigurasi Sistem</span>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold text-xs shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2 cursor-pointer">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Simpan Seluruh Pengaturan</span>
            </button>
        </div>

    </form>
</div>
@endsection
