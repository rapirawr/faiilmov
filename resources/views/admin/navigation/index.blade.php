@extends('layouts.admin')

@section('title', 'Manajemen Menu Sidebar | faiiladmin')
@section('page_title', 'Manajemen Menu Sidebar (Drag & Drop)')

@section('content')
<div class="space-y-8" x-data="navigationManager()">

    <!-- Header Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl">
        <div class="space-y-1">
            <h3 class="text-base font-bold text-white font-['Outfit'] flex items-center gap-2.5">
                <i data-lucide="layout-grid" class="w-5 h-5 text-zinc-400"></i>
                <span>Susunan Menu Navigasi Sidebar Publik</span>
            </h3>
            <p class="text-xs text-zinc-400">Atur urutan dengan drag & drop, aktifkan/nonaktifkan item, ubah icon, atau kelola widget Get App bawah sidebar.</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="addNewItem()" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-white border border-zinc-700 flex items-center gap-2 transition-colors cursor-pointer shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Menu</span>
            </button>

            <button type="button" @click="resetToDefault()" class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-zinc-800 text-xs font-semibold text-zinc-400 hover:text-white border border-zinc-800 flex items-center gap-2 transition-colors cursor-pointer">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                <span>Reset Default</span>
            </button>
        </div>
    </div>

    <!-- Main Drag & Drop Workspace (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Column (7 Cols): Reorder List & Form Editor -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Card 1: Draggable Menu Items -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-6">
                
                <!-- Action Bar Summary -->
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-white uppercase tracking-wider">Daftar Menu Sidebar (<span x-text="sidebarList.length"></span>)</span>
                        <span class="text-[11px] text-zinc-500 font-mono">(Tahan pegangan titik 6 untuk reorder)</span>
                    </div>
                </div>

                <!-- Draggable Menu List Container -->
                <div class="space-y-3">
                    <template x-for="(item, index) in sidebarList" :key="item.id || index">
                        <div draggable="true"
                             @dragstart="onDragStart(index, $event)"
                             @dragover.prevent="onDragOver(index, $event)"
                             @drop.prevent="onDrop(index, $event)"
                             @dragend="onDragEnd()"
                             :class="{
                                 'border-white/40 bg-zinc-850 shadow-2xl scale-[1.01]': draggedIndex === index,
                                 'border-zinc-800 bg-zinc-950/80 hover:border-zinc-700': draggedIndex !== index,
                                 'opacity-50': !item.is_active
                             }"
                             class="p-4 rounded-2xl border transition-all duration-150 group space-y-3">
                            
                            <!-- Card Header Row (Drag Handle, Icon, Title, Actions) -->
                            <div class="flex items-center justify-between gap-3">
                                
                                <div class="flex items-center gap-3 min-w-0">
                                    <!-- Drag Grip Handle (Dedicated Grab Zone) -->
                                    <div class="p-1.5 rounded-lg text-zinc-500 hover:text-white hover:bg-zinc-800 transition-colors shrink-0 cursor-grab active:cursor-grabbing"
                                         title="Tahan dan geser untuk memindahkan urutan">
                                        <span class="flex items-center justify-center pointer-events-none" x-html="getIconSvg('grip-vertical', 'w-4 h-4')"></span>
                                    </div>

                                    <!-- Selected Icon Picker Trigger -->
                                    <button type="button" 
                                            @click="openIconPicker('menu', index)" 
                                            class="w-9 h-9 rounded-xl bg-zinc-900 border border-zinc-700 text-white flex items-center justify-center shrink-0 hover:bg-zinc-800 transition-colors cursor-pointer"
                                            title="Klik untuk mengganti icon">
                                        <span class="flex items-center justify-center pointer-events-none" x-html="getIconSvg(item.icon, 'w-4 h-4 text-zinc-200')"></span>
                                    </button>

                                    <!-- Title & URL Summary -->
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-xs text-white truncate" x-text="item.label || 'Tanpa Nama'"></span>
                                            <template x-if="item.badge">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono" x-text="item.badge"></span>
                                            </template>
                                            <template x-if="!item.is_active">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono bg-zinc-800 text-zinc-400">Nonaktif</span>
                                            </template>
                                        </div>
                                        <span class="text-[10px] text-zinc-500 font-mono truncate block" x-text="item.url || '/'"></span>
                                    </div>
                                </div>

                                <!-- Up, Down, Visibility & Delete Controls -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <!-- Move Up -->
                                    <button type="button" 
                                            @click.stop="moveItem(index, -1)" 
                                            :disabled="index === 0" 
                                            class="p-1.5 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white disabled:opacity-30 transition-colors cursor-pointer"
                                            title="Pindahkan ke atas">
                                        <span class="flex items-center justify-center pointer-events-none" x-html="getIconSvg('chevron-up', 'w-3.5 h-3.5')"></span>
                                    </button>

                                    <!-- Move Down -->
                                    <button type="button" 
                                            @click.stop="moveItem(index, 1)" 
                                            :disabled="index === sidebarList.length - 1" 
                                            class="p-1.5 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white disabled:opacity-30 transition-colors cursor-pointer"
                                            title="Pindahkan ke bawah">
                                        <span class="flex items-center justify-center pointer-events-none" x-html="getIconSvg('chevron-down', 'w-3.5 h-3.5')"></span>
                                    </button>

                                    <!-- Toggle Active Switch -->
                                    <button type="button" 
                                            @click.stop="item.is_active = !item.is_active" 
                                            :class="item.is_active ? 'bg-white text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-500 border border-zinc-800'"
                                            class="px-2.5 py-1 rounded-lg text-[10px] transition-colors cursor-pointer"
                                            title="Aktifkan / Sembunyikan Menu">
                                        <span x-text="item.is_active ? 'Aktif' : 'Off'"></span>
                                    </button>

                                    <!-- Delete Item -->
                                    <button type="button" 
                                            @click.stop="removeItem(index)" 
                                            class="p-1.5 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-500 hover:text-rose-400 hover:border-rose-500/30 transition-colors cursor-pointer"
                                            title="Hapus Menu">
                                        <span class="flex items-center justify-center pointer-events-none" x-html="getIconSvg('trash-2', 'w-3.5 h-3.5')"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Expandable Form Inputs Grid for each Item (Explicitly Non-Draggable) -->
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-2 border-t border-zinc-850/80 text-xs"
                                 @mousedown.stop
                                 @touchstart.stop>
                                
                                <!-- Label Input (4 Cols) -->
                                <div class="sm:col-span-4 space-y-1">
                                    <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Nama Menu</label>
                                    <input type="text" 
                                           x-model="item.label" 
                                           draggable="false"
                                           @dragstart.prevent.stop
                                           placeholder="Label Menu" 
                                           class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-medium cursor-text">
                                </div>

                                <!-- URL Input (4 Cols) -->
                                <div class="sm:col-span-4 space-y-1">
                                    <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Tautan URL</label>
                                    <input type="text" 
                                           x-model="item.url" 
                                           draggable="false"
                                           @dragstart.prevent.stop
                                           placeholder="/browse?type=movie" 
                                           class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-mono cursor-text">
                                </div>

                                <!-- Badge Tag (2 Cols) -->
                                <div class="sm:col-span-2 space-y-1">
                                    <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Badge Tag</label>
                                    <input type="text" 
                                           x-model="item.badge" 
                                           draggable="false"
                                           @dragstart.prevent.stop
                                           placeholder="HOT / NEW" 
                                           class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-2.5 py-1.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 uppercase font-mono cursor-text">
                                </div>

                                <!-- Target Window (2 Cols) -->
                                <div class="sm:col-span-2 space-y-1">
                                    <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Target</label>
                                    <select x-model="item.target" 
                                            draggable="false"
                                            @dragstart.prevent.stop
                                            class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-2 py-1.5 text-xs text-white focus:outline-none focus:border-white/30 font-mono cursor-pointer">
                                        <option value="_self">Tab Sama</option>
                                        <option value="_blank">Tab Baru</option>
                                    </select>
                                </div>

                            </div>

                        </div>
                    </template>
                </div>

            </div>

            <!-- Card 2: Widget Banner Bawah Sidebar (Get faiilmov) -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-5">
                
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                            <i data-lucide="smartphone" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Widget Banner Bawah (Get App)</h4>
                            <p class="text-[11px] text-zinc-400">Banner kartu di bagian bawah sidebar untuk tautan download aplikasi atau platform lain.</p>
                        </div>
                    </div>

                    <!-- Toggle Widget Active -->
                    <button type="button" 
                            @click="sidebarWidget.is_active = !sidebarWidget.is_active" 
                            :class="sidebarWidget.is_active ? 'bg-amber-400 text-zinc-950 font-bold shadow-md' : 'bg-zinc-800 text-zinc-400 border border-zinc-700'"
                            class="px-3 py-1.5 rounded-xl text-xs transition-colors cursor-pointer">
                        <span x-text="sidebarWidget.is_active ? 'Widget Aktif' : 'Nonaktif'"></span>
                    </button>
                </div>

                <div class="space-y-4" x-show="sidebarWidget.is_active" x-collapse>
                    <!-- Judul Banner -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Judul Kartu Widget</label>
                        <input type="text" 
                               x-model="sidebarWidget.title" 
                               placeholder="Get faiilmov" 
                               class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-3.5 py-2 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-semibold">
                    </div>

                    <!-- Tombol Utama (Button 1: Mobile) -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-3">
                        <span class="text-[11px] font-bold text-white flex items-center gap-1.5">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5 text-zinc-300"></i>
                            <span>Tombol Utama (Default: Mobile)</span>
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                            <!-- Icon Trigger (2 Cols) -->
                            <div class="sm:col-span-2 space-y-1">
                                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Icon</label>
                                <button type="button" 
                                        @click="openIconPicker('widget1')" 
                                        class="w-full h-9 rounded-xl bg-zinc-900 border border-zinc-700 text-white flex items-center justify-center hover:bg-zinc-800 transition-colors cursor-pointer"
                                        title="Pilih Icon Tombol">
                                    <i :data-lucide="sidebarWidget.button1_icon || 'smartphone'" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Label (5 Cols) -->
                            <div class="sm:col-span-5 space-y-1">
                                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Teks Tombol</label>
                                <input type="text" 
                                       x-model="sidebarWidget.button1_label" 
                                       placeholder="App Mobile" 
                                       class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-semibold">
                            </div>

                            <!-- URL (5 Cols) -->
                            <div class="sm:col-span-5 space-y-1">
                                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Link URL</label>
                                <input type="text" 
                                       x-model="sidebarWidget.button1_url" 
                                       placeholder="/download-app" 
                                       class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-mono">
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Kedua (Button 2: Opsional) -->
                    <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-zinc-300 flex items-center gap-1.5">
                                <i data-lucide="layers" class="w-3.5 h-3.5 text-zinc-300"></i>
                                <span>Tombol Kedua (Opsional, misal: macOS / PC / Telegram)</span>
                            </span>

                            <button type="button" 
                                    @click="sidebarWidget.button2_active = !sidebarWidget.button2_active" 
                                    :class="sidebarWidget.button2_active ? 'bg-white text-zinc-950 font-bold' : 'bg-zinc-900 text-zinc-500 border border-zinc-800'"
                                    class="px-2.5 py-1 rounded-lg text-[10px] transition-colors cursor-pointer">
                                <span x-text="sidebarWidget.button2_active ? 'Aktif' : 'Off'"></span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs" x-show="sidebarWidget.button2_active" x-collapse>
                            <!-- Icon Trigger (2 Cols) -->
                            <div class="sm:col-span-2 space-y-1">
                                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Icon</label>
                                <button type="button" 
                                        @click="openIconPicker('widget2')" 
                                        class="w-full h-9 rounded-xl bg-zinc-900 border border-zinc-700 text-white flex items-center justify-center hover:bg-zinc-800 transition-colors cursor-pointer"
                                        title="Ganti icon tombol kedua">
                                    <span class="flex items-center justify-center pointer-events-none" x-html="getIconSvg(sidebarWidget.button2_icon || 'laptop', 'w-4 h-4 text-zinc-200')"></span>
                                </button>
                            </div>

                            <!-- Label (4 Cols) -->
                            <div class="sm:col-span-4 space-y-1">
                                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Label Tombol</label>
                                <input type="text" 
                                       x-model="sidebarWidget.button2_text" 
                                       placeholder="macOS" 
                                       class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-medium">
                            </div>

                            <!-- URL (6 Cols) -->
                            <div class="sm:col-span-6 space-y-1">
                                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Tautan URL</label>
                                <input type="text" 
                                       x-model="sidebarWidget.button2_url" 
                                       placeholder="#" 
                                       class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-1.5 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-white/30 font-mono">
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Save Changes Bottom Bar -->
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-zinc-400">
                    <i data-lucide="info" class="w-4 h-4 text-zinc-400"></i>
                    <span>Perubahan susunan menu dan widget akan langsung tersinkronisasi di sidebar publik.</span>
                </div>

                <button type="button" 
                        @click="saveMenuSettings()" 
                        :disabled="isSaving"
                        class="px-6 py-2.5 rounded-full bg-white hover:bg-zinc-200 disabled:opacity-50 text-zinc-950 font-bold text-xs shadow-lg transition-all flex items-center gap-2 cursor-pointer shrink-0">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>

            <!-- Floating Bottom-Right Save Action Bar -->
            <div class="fixed bottom-6 right-6 z-40 flex items-center gap-3 bg-zinc-900/90 backdrop-blur-xl border border-white/15 p-2 sm:p-2.5 rounded-2xl shadow-2xl shadow-black/80 ring-1 ring-white/10 hover:border-amber-500/40 transition-all">
                <button type="button" 
                        @click="saveMenuSettings()" 
                        :disabled="isSaving"
                        class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold text-xs shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Susunan Menu'"></span>
                </button>
            </div>

        </div>

        <!-- Right Column (5 Cols): Live Interactive Visual Mockup -->
        <div class="lg:col-span-5 space-y-6 sticky top-24">
            
            <div class="p-6 rounded-3xl bg-zinc-900/90 border border-white/10 shadow-xl space-y-4">
                
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-300 flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4 text-zinc-400"></i>
                        <span>Pratinjau Sidebar (Live)</span>
                    </span>
                    <span class="text-[10px] font-mono text-zinc-500 uppercase">Live Mockup</span>
                </div>

                <!-- SIDEBAR VISUAL MOCKUP -->
                <div class="p-4 rounded-2xl bg-dark-950 border border-white/10 space-y-4 shadow-inner">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-white/10">
                        <img src="{{ asset('images/logo.png') }}" alt="logo" class="h-6 w-auto object-contain">
                        <span class="font-serif font-extrabold text-base text-white">faiil<span class="text-zinc-400 font-sans font-bold">mov</span></span>
                    </div>

                    <!-- Dynamic Navigation Items -->
                    <div class="space-y-1.5 max-h-[380px] overflow-y-auto no-scrollbar pr-1">
                        <template x-for="(item, idx) in sidebarList.filter(i => i.is_active)" :key="idx">
                            <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                                 :class="idx === 0 ? 'bg-dark-800 text-white border border-zinc-700/70 shadow-sm' : 'text-zinc-400 hover:text-white bg-white/5'">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center shrink-0" x-html="getIconSvg(item.icon, 'w-4 h-4 ' + (idx === 0 ? 'text-white' : 'text-zinc-400'))"></span>
                                    <span x-text="item.label"></span>
                                </div>
                                <template x-if="item.badge">
                                    <span class="px-1.5 py-0.2 rounded text-[8px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono" x-text="item.badge"></span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Widget Banner Live Preview -->
                    <template x-if="sidebarWidget.is_active">
                        <div class="p-3.5 rounded-2xl bg-zinc-900 border border-white/10 space-y-2.5 mt-2">
                            <span class="text-[11px] font-bold text-white block" x-text="sidebarWidget.title || 'Get faiilmov'"></span>
                            <div class="grid gap-2" :class="sidebarWidget.button2_active ? 'grid-cols-2' : 'grid-cols-1'">
                                <div class="px-3 py-2 rounded-xl bg-white text-zinc-950 text-[10px] font-bold flex items-center justify-center gap-1.5 shadow-sm">
                                    <span class="flex items-center justify-center shrink-0" x-html="getIconSvg(sidebarWidget.button_icon || 'smartphone', 'w-3.5 h-3.5 text-zinc-950')"></span>
                                    <span x-text="sidebarWidget.button_text || 'Mobile'"></span>
                                </div>
                                <template x-if="sidebarWidget.button2_active">
                                    <div class="px-3 py-2 rounded-xl bg-dark-900 text-zinc-300 text-[10px] font-semibold flex items-center justify-center gap-1.5 border border-white/10">
                                        <span class="flex items-center justify-center shrink-0" x-html="getIconSvg(sidebarWidget.button2_icon || 'laptop', 'w-3.5 h-3.5 text-zinc-300')"></span>
                                        <span x-text="sidebarWidget.button2_text || 'macOS'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

        </div>

    </div>

    <!-- Lucide Icon Picker Modal -->
    <template x-teleport="body">
        <div x-show="iconPickerOpen" x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div @click.away="iconPickerOpen = false" class="w-full max-w-lg p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl text-white">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div>
                        <h4 class="font-bold text-white text-sm font-['Outfit']">Pilih Icon Lucide</h4>
                        <p class="text-[11px] text-zinc-400">Pilih icon visual untuk elemen yang dipilih.</p>
                    </div>
                    <button type="button" @click="iconPickerOpen = false" class="p-1.5 rounded-xl hover:bg-zinc-800 text-zinc-400 hover:text-white cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Icon Search -->
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" 
                           x-model="iconSearch" 
                           placeholder="Cari nama icon..." 
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-xl pl-9 pr-4 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 font-mono">
                </div>

                <!-- Icon Grid -->
                <div class="grid grid-cols-5 sm:grid-cols-6 gap-2 max-h-[300px] overflow-y-auto admin-scrollbar pr-1">
                    @foreach($availableIcons as $iconName)
                        <button type="button" 
                                x-show="!iconSearch || '{{ $iconName }}'.includes(iconSearch.toLowerCase())"
                                @click="selectIcon('{{ $iconName }}')" 
                                class="p-3 rounded-2xl bg-zinc-950 hover:bg-white hover:text-zinc-950 border border-zinc-800 flex flex-col items-center justify-center gap-1.5 transition-all text-zinc-300 cursor-pointer group">
                            <i data-lucide="{{ $iconName }}" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                            <span class="text-[9px] font-mono truncate w-full text-center">{{ $iconName }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </template>

</div>

<script>
function navigationManager() {
    return {
        sidebarList: @json($sidebarItems ?? []),
        sidebarWidget: @json($sidebarWidget ?? \App\Services\NavigationService::getDefaultSidebarWidget()),

        draggedIndex: null,
        iconPickerOpen: false,
        iconPickerContext: 'menu', // 'menu', 'widget1', 'widget2'
        iconPickerItemIndex: null,
        iconSearch: '',
        isSaving: false,

        init() {
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        getIconSvg(name, cls = 'w-4 h-4') {
            if (!name) name = 'compass';
            
            // Try Lucide Icons Library first if available
            if (window.lucide && window.lucide.icons) {
                const pascal = name.split('-').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('');
                const camel = name.replace(/-([a-z0-9])/g, g => g[1].toUpperCase());
                const iconDef = window.lucide.icons[name] || window.lucide.icons[pascal] || window.lucide.icons[camel] || window.lucide.icons['Compass'];
                if (iconDef) {
                    try {
                        const el = window.lucide.createElement(iconDef, { class: cls });
                        return el.outerHTML;
                    } catch(e) {}
                }
            }

            // Direct SVG definitions
            const iconSvgs = {
                'home': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
                'tv': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>`,
                'tv-2': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 21h10"/><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M12 17v4"/></svg>`,
                'clapperboard': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.2 6 3 11l-.9-2.4 17.2-5z"/><path d="m6.2 5.3 3.1 3.9"/><path d="m12.4 3.4 3.1 4"/><path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>`,
                'sparkles': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>`,
                'flame': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>`,
                'history': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>`,
                'users': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
                'smartphone': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>`,
                'laptop': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45L4 16"/></svg>`,
                'grip-vertical': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>`,
                'chevron-up': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>`,
                'chevron-down': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>`,
                'trash-2': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>`,
                'compass': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>`,
                'film': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg>`,
                'bookmark': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>`,
                'star': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`,
                'heart': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>`,
                'bell': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>`,
                'globe': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>`,
                'play': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>`,
                'zap': `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>`
            };

            return iconSvgs[name] || iconSvgs['compass'];
        },

        onDragStart(index, event) {
            const target = event.target;
            // Never start dragging card when interacting with form inputs or buttons
            if (
                target.tagName === 'INPUT' || 
                target.tagName === 'SELECT' || 
                target.tagName === 'TEXTAREA' || 
                target.tagName === 'BUTTON' ||
                target.closest('input, select, textarea, button')
            ) {
                event.preventDefault();
                return false;
            }

            this.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', index);
        },

        onDragOver(index, event) {
            // Highlight hover slot
        },

        onDrop(targetIndex, event) {
            if (this.draggedIndex === null || this.draggedIndex === targetIndex) return;

            const list = this.sidebarList;
            const movedItem = list.splice(this.draggedIndex, 1)[0];
            list.splice(targetIndex, 0, movedItem);

            this.draggedIndex = null;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        onDragEnd() {
            this.draggedIndex = null;
        },

        moveItem(index, direction) {
            const list = this.sidebarList;
            const target = index + direction;
            if (target < 0 || target >= list.length) return;

            const temp = list[index];
            list[index] = list[target];
            list[target] = temp;

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        addNewItem() {
            const newItem = {
                id: 'custom_' + Date.now(),
                label: 'Menu Baru',
                icon: 'compass',
                url: '/',
                route: '',
                is_active: true,
                badge: '',
                target: '_self',
                visibility: 'all',
            };

            this.sidebarList.push(newItem);
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        removeItem(index) {
            if (confirm('Hapus item menu ini dari susunan?')) {
                this.sidebarList.splice(index, 1);
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        },

        openIconPicker(context = 'menu', index = null) {
            this.iconPickerContext = context;
            this.iconPickerItemIndex = index;
            this.iconSearch = '';
            this.iconPickerOpen = true;
        },

        selectIcon(iconName) {
            if (this.iconPickerContext === 'menu' && this.iconPickerItemIndex !== null && this.sidebarList[this.iconPickerItemIndex]) {
                this.sidebarList[this.iconPickerItemIndex].icon = iconName;
            } else if (this.iconPickerContext === 'widget1') {
                this.sidebarWidget.button_icon = iconName;
            } else if (this.iconPickerContext === 'widget2') {
                this.sidebarWidget.button2_icon = iconName;
            }

            this.iconPickerOpen = false;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        async saveMenuSettings() {
            this.isSaving = true;

            try {
                const res = await fetch('{{ route('admin.navigation.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        items: this.sidebarList,
                        widget: this.sidebarWidget
                    })
                });

                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                } else {
                    alert('Gagal menyimpan menu: ' + (data.message || 'Terjadi kesalahan'));
                }
            } catch (e) {
                alert('Error koneksi: ' + e.message);
            } finally {
                this.isSaving = false;
            }
        },

        async resetToDefault() {
            if (!confirm('Kembalikan susunan menu sidebar dan widget ke pengaturan default?')) return;

            try {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.navigation.reset') }}';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();
            } catch (e) {
                alert(e.message);
            }
        }
    };
}
</script>
@endsection
