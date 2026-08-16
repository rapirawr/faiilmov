@extends('layouts.admin')

@section('title', 'Manajemen Iklan Adsterra | faiiladmin')
@section('page_title', 'Manajemen Iklan Adsterra & Monetisasi')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" 
     x-data="{ activeTab: 'overview' }"
     x-init="$watch('activeTab', () => $nextTick(() => { if(window.lucide) lucide.createIcons(); })); $nextTick(() => { if(window.lucide) lucide.createIcons(); });">
    
    <!-- Top Highlights / Header Hero Banner -->
    <div class="p-6 rounded-3xl bg-gradient-to-br from-zinc-900 via-zinc-900 to-amber-950/30 border border-zinc-800 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2 z-10">
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-mono font-bold uppercase tracking-wider {{ $settings['ads_enabled'] ? 'text-emerald-400' : 'text-zinc-500' }}">
                    {{ $settings['ads_enabled'] ? 'Sistem Iklan Aktif' : 'Sistem Iklan Dinonaktifkan' }}
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">Adsterra Hub</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-white font-['Outfit']">Integrasi Iklan & Unit Monetisasi</h2>
            <p class="text-xs text-zinc-400 max-w-xl leading-relaxed">
                Kelola penempatan Popunder, Social Bar, Banner Pemutar Film, Grid Native, Direct Link, serta Deteksi Anti-Adblock secara dinamis.
            </p>
        </div>

        <!-- Exemption Badge Alert -->
        <div class="z-10 p-4 rounded-2xl bg-zinc-950/80 border border-zinc-800 text-xs space-y-1.5 shrink-0 max-w-xs">
            <div class="flex items-center gap-2 text-zinc-200 font-bold">
                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                <span>Proteksi Bebas Iklan Admin</span>
            </div>
            <p class="text-[11px] text-zinc-400 leading-normal">
                Akun Admin yang sedang login otomatis dibebaskan dari seluruh tampilan iklan dan popup saat browsing di web.
            </p>
        </div>
    </div>

    <!-- Tab Navigation Bar -->
    <div class="flex items-center gap-2 border-b border-zinc-800 pb-3 overflow-x-auto no-scrollbar text-xs font-bold">
        <button @click="activeTab = 'overview'" 
                :class="activeTab === 'overview' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            <span>Ikhtisar & Master Switch</span>
        </button>

        <button @click="activeTab = 'popunder'" 
                :class="activeTab === 'popunder' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="mouse-pointer-click" class="w-4 h-4"></i>
            <span>Popunder (OnClick)</span>
            @if($settings['ads_popunder_enabled'])
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            @endif
        </button>

        <button @click="activeTab = 'socialbar'" 
                :class="activeTab === 'socialbar' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="bell-ring" class="w-4 h-4"></i>
            <span>Social Bar</span>
            @if($settings['ads_socialbar_enabled'])
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            @endif
        </button>

        <button @click="activeTab = 'player'" 
                :class="activeTab === 'player' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="tv" class="w-4 h-4"></i>
            <span>Banner Player (Watch)</span>
            @if($settings['ads_banner_player_top_enabled'] || $settings['ads_banner_player_bottom_enabled'])
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            @endif
        </button>

        <button @click="activeTab = 'grid'" 
                :class="activeTab === 'grid' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="grid" class="w-4 h-4"></i>
            <span>Native / Grid Banner</span>
            @if($settings['ads_banner_grid_enabled'])
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            @endif
        </button>

        <button @click="activeTab = 'directlink'" 
                :class="activeTab === 'directlink' ? 'bg-white text-zinc-950 shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="link-2" class="w-4 h-4"></i>
            <span>Direct Link (Smartlink)</span>
            @if($settings['ads_direct_link_enabled'])
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            @endif
        </button>

        <button @click="activeTab = 'antiadblock'" 
                :class="activeTab === 'antiadblock' ? 'bg-amber-500 text-black shadow-md' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'" 
                class="px-4 py-2.5 rounded-2xl transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap">
            <i data-lucide="shield-alert" class="w-4 h-4"></i>
            <span>Anti-Adblock</span>
            @if($settings['ads_anti_adblock_enabled'])
                <span class="w-2 h-2 rounded-full bg-black"></span>
            @endif
        </button>
    </div>

    <!-- Main Form for All Ads Settings -->
    <form action="{{ route('admin.ads.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- ==================== TAB 1: OVERVIEW & MASTER SWITCH ==================== -->
        <div x-show="activeTab === 'overview'" class="space-y-6">
            
            <!-- Master Toggle Card -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <i data-lucide="power" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Saklar Utama Iklan (Master Switch)</h3>
                            <p class="text-xs text-zinc-400">Matikan atau aktifkan seluruh slot iklan di situs secara instan.</p>
                        </div>
                    </div>

                    <!-- Master Toggle Checkbox Switch -->
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_enabled" value="1" {{ $settings['ads_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <!-- Admin Testing Preview Toggle -->
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <i data-lucide="eye" class="w-4 h-4 text-amber-400"></i>
                            <h4 class="text-xs font-bold text-white">Mode Pengujian Admin (Tampilkan Iklan saat Login)</h4>
                        </div>
                        <p class="text-[11px] text-zinc-300">
                            Aktifkan opsi ini jika Anda ingin melihat dan menguji banner/popunder langsung menggunakan akun Admin ini tanpa harus membuka jendela Incognito.
                        </p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="ads_show_to_admin" value="1" {{ $settings['ads_show_to_admin'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-12 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                </div>

                <!-- Slots Status Matrix Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Popunder -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-300">
                                <i data-lucide="mouse-pointer-click" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Popunder (OnClick)</p>
                                <span class="text-[10px] {{ $settings['ads_popunder_enabled'] ? 'text-emerald-400' : 'text-zinc-500' }}">
                                    {{ $settings['ads_popunder_enabled'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="activeTab = 'popunder'" class="text-xs text-amber-400 hover:underline">Kelola &rarr;</button>
                    </div>

                    <!-- Social Bar -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-300">
                                <i data-lucide="bell-ring" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Social Bar (Push)</p>
                                <span class="text-[10px] {{ $settings['ads_socialbar_enabled'] ? 'text-emerald-400' : 'text-zinc-500' }}">
                                    {{ $settings['ads_socialbar_enabled'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="activeTab = 'socialbar'" class="text-xs text-amber-400 hover:underline">Kelola &rarr;</button>
                    </div>

                    <!-- Banner Player -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-300">
                                <i data-lucide="tv" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Banner Player Watch</p>
                                <span class="text-[10px] {{ ($settings['ads_banner_player_top_enabled'] || $settings['ads_banner_player_bottom_enabled']) ? 'text-emerald-400' : 'text-zinc-500' }}">
                                    {{ ($settings['ads_banner_player_top_enabled'] || $settings['ads_banner_player_bottom_enabled']) ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="activeTab = 'player'" class="text-xs text-amber-400 hover:underline">Kelola &rarr;</button>
                    </div>

                    <!-- Grid Native -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-300">
                                <i data-lucide="grid" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Native Grid Banner</p>
                                <span class="text-[10px] {{ $settings['ads_banner_grid_enabled'] ? 'text-emerald-400' : 'text-zinc-500' }}">
                                    {{ $settings['ads_banner_grid_enabled'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="activeTab = 'grid'" class="text-xs text-amber-400 hover:underline">Kelola &rarr;</button>
                    </div>

                    <!-- Direct Link -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-300">
                                <i data-lucide="link-2" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Direct Link / Smartlink</p>
                                <span class="text-[10px] {{ $settings['ads_direct_link_enabled'] ? 'text-emerald-400' : 'text-zinc-500' }}">
                                    {{ $settings['ads_direct_link_enabled'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="activeTab = 'directlink'" class="text-xs text-amber-400 hover:underline">Kelola &rarr;</button>
                    </div>

                    <!-- Anti-Adblock -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-300">
                                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Anti-Adblock Modal</p>
                                <span class="text-[10px] {{ $settings['ads_anti_adblock_enabled'] ? 'text-amber-400' : 'text-zinc-500' }}">
                                    {{ $settings['ads_anti_adblock_enabled'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="activeTab = 'antiadblock'" class="text-xs text-amber-400 hover:underline">Kelola &rarr;</button>
                    </div>
                </div>
            </div>

            <!-- Adsterra Quick Guide Card -->
            <div class="p-6 rounded-3xl bg-zinc-900/60 border border-zinc-800 shadow-xl space-y-4">
                <div class="flex items-center gap-2.5 text-white font-bold text-sm">
                    <i data-lucide="help-circle" class="w-4 h-4 text-amber-400"></i>
                    <span>Panduan Integrasi Adsterra ke faiilmov</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-zinc-400 leading-relaxed">
                    <div class="p-4 rounded-2xl bg-zinc-950/60 border border-zinc-800/80 space-y-1.5">
                        <span class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-400 font-bold flex items-center justify-center text-xs">1</span>
                        <h4 class="font-bold text-zinc-200">Buat Akun & Tambah Domain</h4>
                        <p>Daftar di dashboard Adsterra Publishers dan tambahkan domain web Anda. Pilih kategori Mainstream/Movies.</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-zinc-950/60 border border-zinc-800/80 space-y-1.5">
                        <span class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-400 font-bold flex items-center justify-center text-xs">2</span>
                        <h4 class="font-bold text-zinc-200">Generate Ad Unit Codes</h4>
                        <p>Buat kode Popunder, Social Bar, Banners (728x90, 300x250), Native Banners, dan Direct Link di Adsterra.</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-zinc-950/60 border border-zinc-800/80 space-y-1.5">
                        <span class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-400 font-bold flex items-center justify-center text-xs">3</span>
                        <h4 class="font-bold text-zinc-200">Tempel & Aktifkan Slot</h4>
                        <p>Buka tab yang sesuai di panel ini, tempelkan kode script yang diberikan Adsterra, centang aktif, lalu klik Simpan.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 2: POPUNDER (ONCLICK) ==================== -->
        <div x-show="activeTab === 'popunder'" class="space-y-6" style="display: none;">
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                            <i data-lucide="mouse-pointer-click" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Popunder / OnClick Script</h3>
                            <p class="text-xs text-zinc-400">Membuka tab/window iklan baru saat pengguna pertama kali mengklik area situs.</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_popunder_enabled" value="1" {{ $settings['ads_popunder_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Kode Skrip Popunder Adsterra (HTML / JavaScript)</label>
                    <textarea name="ads_popunder_code" rows="6" 
                              placeholder="<script type='text/javascript' src='//...adsterra popunder script...'></script>"
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs font-mono text-amber-300 focus:outline-none focus:border-amber-400/40 leading-relaxed">{{ old('ads_popunder_code', $settings['ads_popunder_code']) }}</textarea>
                    <p class="text-[11px] text-zinc-500 mt-1.5 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <span>Kode ini akan dimuat otomatis secara global di seluruh halaman web (kecuali untuk akun Admin).</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 3: SOCIAL BAR ==================== -->
        <div x-show="activeTab === 'socialbar'" class="space-y-6" style="display: none;">
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                            <i data-lucide="bell-ring" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Social Bar / In-Page Push</h3>
                            <p class="text-xs text-zinc-400">Widget notifikasi interaktif yang melayang di pojok layar, ramah desktop & mobile (CPM tinggi).</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_socialbar_enabled" value="1" {{ $settings['ads_socialbar_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Kode Skrip Social Bar Adsterra</label>
                    <textarea name="ads_socialbar_code" rows="6" 
                              placeholder="<script type='text/javascript' src='//...adsterra social bar script...'></script>"
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs font-mono text-purple-300 focus:outline-none focus:border-purple-400/40 leading-relaxed">{{ old('ads_socialbar_code', $settings['ads_socialbar_code']) }}</textarea>
                    <p class="text-[11px] text-zinc-500 mt-1.5 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <span>Social Bar tidak menutupi tombol video dan mematuhi standar UX web modern.</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 4: BANNER PLAYER (WATCH PAGE) ==================== -->
        <div x-show="activeTab === 'player'" class="space-y-6" style="display: none;">
            
            <!-- Player Top Banner -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <i data-lucide="arrow-up-circle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Banner Di Atas Pemutar Video (Player Top)</h3>
                            <p class="text-xs text-zinc-400">Ditampilkan persis di atas pemutar film pada halaman nonton (Ukuran rekomendasi: 728x90, 468x60, atau responsive).</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_banner_player_top_enabled" value="1" {{ $settings['ads_banner_player_top_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Kode Banner Player Atas</label>
                    <textarea name="ads_banner_player_top_code" rows="5" 
                              placeholder="<script type='text/javascript'> ... </script>"
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs font-mono text-amber-300 focus:outline-none focus:border-amber-400/40 leading-relaxed">{{ old('ads_banner_player_top_code', $settings['ads_banner_player_top_code']) }}</textarea>
                </div>
            </div>

            <!-- Player Bottom Banner -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <i data-lucide="arrow-down-circle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Banner Di Bawah Pemutar Video (Player Bottom)</h3>
                            <p class="text-xs text-zinc-400">Ditampilkan di bawah pemutar film sebelum daftar episode / download.</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_banner_player_bottom_enabled" value="1" {{ $settings['ads_banner_player_bottom_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Kode Banner Player Bawah</label>
                    <textarea name="ads_banner_player_bottom_code" rows="5" 
                              placeholder="<script type='text/javascript'> ... </script>"
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs font-mono text-amber-300 focus:outline-none focus:border-amber-400/40 leading-relaxed">{{ old('ads_banner_player_bottom_code', $settings['ads_banner_player_bottom_code']) }}</textarea>
                </div>
            </div>

        </div>

        <!-- ==================== TAB 5: NATIVE / GRID BANNER ==================== -->
        <div x-show="activeTab === 'grid'" class="space-y-6" style="display: none;">
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <i data-lucide="grid" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Native Banner / Grid Banner</h3>
                            <p class="text-xs text-zinc-400">Disisipkan di sela-sela daftar film pada halaman Beranda (Home) dan Jelajah (Browse).</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_banner_grid_enabled" value="1" {{ $settings['ads_banner_grid_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Kode Skrip Native Banner Adsterra (Ukuran 4:1 atau 1:1)</label>
                    <textarea name="ads_banner_grid_code" rows="6" 
                              placeholder="<script type='text/javascript'> ... </script>"
                              class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs font-mono text-emerald-300 focus:outline-none focus:border-emerald-400/40 leading-relaxed">{{ old('ads_banner_grid_code', $settings['ads_banner_grid_code']) }}</textarea>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 6: DIRECT LINK (SMARTLINK) ==================== -->
        <div x-show="activeTab === 'directlink'" class="space-y-6" style="display: none;">
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                            <i data-lucide="link-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Direct Link / Smartlink Monetization</h3>
                            <p class="text-xs text-zinc-400">Tautan langsung dari Adsterra yang diarahkan saat pengguna mengklik tombol khusus di web.</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_direct_link_enabled" value="1" {{ $settings['ads_direct_link_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <!-- URL Input -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">URL Direct Link Adsterra *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-500">
                            <i data-lucide="globe" class="w-4 h-4"></i>
                        </div>
                        <input type="url" name="ads_direct_link_url" value="{{ old('ads_direct_link_url', $settings['ads_direct_link_url']) }}"
                               placeholder="https://www.profitablecpmrate.com/..."
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white focus:outline-none focus:border-rose-400/40 font-mono">
                    </div>
                    <p class="text-[11px] text-zinc-500 mt-1.5">Salin URL Direct Link dari menu "Direct Links" di dashboard Adsterra Anda.</p>
                </div>

                <!-- Placements Toggles for Direct Link -->
                <div class="pt-4 border-t border-zinc-800 space-y-4">
                    <p class="text-xs font-bold text-zinc-200">Pilih Aksi Tombol yang Memicu Direct Link:</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Trigger on Download -->
                        <label class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between cursor-pointer hover:border-zinc-700 transition-colors">
                            <div class="space-y-0.5">
                                <p class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-400"></i>
                                    <span>Tombol Download MP4</span>
                                </p>
                                <p class="text-[11px] text-zinc-400">Membuka Direct Link di tab baru saat user klik Download film.</p>
                            </div>
                            <input type="checkbox" name="ads_direct_link_on_download" value="1" {{ $settings['ads_direct_link_on_download'] ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-500 bg-zinc-900 border-zinc-700 focus:ring-0">
                        </label>

                        <!-- Trigger on Server VIP -->
                        <label class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between cursor-pointer hover:border-zinc-700 transition-colors">
                            <div class="space-y-0.5">
                                <p class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-400"></i>
                                    <span>Tombol "Server VIP Super Fast"</span>
                                </p>
                                <p class="text-[11px] text-zinc-400">Menampilkan tombol VIP di pemutar video yang membuka direct link sponsor.</p>
                            </div>
                            <input type="checkbox" name="ads_direct_link_on_server_vip" value="1" {{ $settings['ads_direct_link_on_server_vip'] ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-500 bg-zinc-900 border-zinc-700 focus:ring-0">
                        </label>
                    </div>
                </div>

            </div>
        </div>

        <!-- ==================== TAB 7: ANTI-ADBLOCK DETECTOR ==================== -->
        <div x-show="activeTab === 'antiadblock'" class="space-y-6" style="display: none;">
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-6">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-['Outfit']">Deteksi & Peringatan Anti-Adblock</h3>
                            <p class="text-xs text-zinc-400">Tampilkan modal notifikasi lembut yang meminta pengunjung menonaktifkan ekstensi AdBlock.</p>
                        </div>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ads_anti_adblock_enabled" value="1" {{ $settings['ads_anti_adblock_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Judul Peringatan Modal</label>
                        <input type="text" name="ads_anti_adblock_title" value="{{ old('ads_anti_adblock_title', $settings['ads_anti_adblock_title']) }}"
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-amber-400/40">
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">Pesan Edukasi / Permintaan Whitelist</label>
                        <textarea name="ads_anti_adblock_message" rows="3"
                                  class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-4 text-xs text-white focus:outline-none focus:border-amber-400/40 leading-relaxed">{{ old('ads_anti_adblock_message', $settings['ads_anti_adblock_message']) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Save Action Bar -->
        <div class="sticky bottom-6 z-30 p-4 rounded-2xl bg-zinc-900/90 border border-zinc-700/80 backdrop-blur-xl shadow-2xl flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs text-zinc-400">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                <span>Perubahan konfigurasi akan langsung berlaku secara global.</span>
            </div>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-2 shadow-lg cursor-pointer">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Simpan Pengaturan Iklan</span>
            </button>
        </div>

    </form>

</div>
@endsection
