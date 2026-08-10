@extends('layouts.app')

@section('title', 'Kebijakan Privasi - faiilmov')
@section('meta_description', 'Kebijakan Privasi resmi faiilmov. Transparansi lengkap perlindungan data pribadi, isolasi sub-profil, dan keamanan streaming.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-10"
     x-data="{
         activeSection: 'sec-1',
         copied: false,
         copyLink() {
             navigator.clipboard.writeText(window.location.href);
             this.copied = true;
             setTimeout(() => this.copied = false, 2500);
         },
         scrollTo(id) {
             this.activeSection = id;
             const el = document.getElementById(id);
             if (el) {
                 const yOffset = -90;
                 const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
                 window.scrollTo({ top: y, behavior: 'smooth' });
             }
         }
     }">

    <!-- Document Header Hero -->
    <div class="glass-panel p-6 sm:p-10 rounded-3xl border border-white/10 relative overflow-hidden bg-dark-900/80 backdrop-blur-2xl shadow-2xl space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            
            <div class="space-y-3 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold shadow-lg">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    <span>LEGAL & DATA PROTECTION POLICY</span>
                </div>

                <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight">
                    Kebijakan Privasi & Data Pribadi
                </h1>

                <p class="text-sm sm:text-base text-zinc-300 leading-relaxed">
                    Dokumen ini menjabarkan prinsip transparansi, mekanisme perlindungan data pribadi, dan komitmen keamanan sistem di platform streaming <strong class="text-white">faiilmov</strong>.
                </p>
            </div>

            <!-- Action Buttons & Document Metadata -->
            <div class="flex flex-col sm:flex-row md:flex-col items-start md:items-end gap-3 shrink-0">
                <div class="flex items-center gap-2 text-[11px] text-zinc-400 font-mono bg-zinc-900/80 px-3.5 py-2 rounded-2xl border border-white/10 shadow-inner">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Dokumen ID: <strong class="text-white">FLM-PRIV-2026-V2</strong></span>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="copyLink()" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-zinc-200 font-semibold transition-all flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="link" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span x-text="copied ? 'Tautan Tersalin!' : 'Bagikan Tautan'"></span>
                    </button>

                    <button onclick="window.print()" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-zinc-200 font-semibold transition-all flex items-center gap-1.5 cursor-pointer hidden sm:flex">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <span>Cetak</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Metadata Pills Bar -->
        <div class="pt-4 border-t border-white/10 flex flex-wrap items-center gap-4 text-xs text-zinc-400">
            <div class="flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>Terakhir Diperbarui: <strong class="text-zinc-200">9 Agustus 2026</strong></span>
            </div>
            <span class="text-zinc-600">•</span>
            <div class="flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>Waktu Baca: <strong class="text-zinc-200">~6 Menit</strong></span>
            </div>
            <span class="text-zinc-600">•</span>
            <div class="flex items-center gap-1.5">
                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span>Kepatuhan: <strong class="text-zinc-200">UU PDP (Indonesia) & GDPR Compliant</strong></span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid with Sticky Navigation Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Sidebar: Sticky Table of Contents -->
        <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
            <div class="glass-panel p-5 rounded-3xl border border-white/10 bg-dark-900/60 backdrop-blur-xl shadow-xl space-y-3">
                <p class="text-xs uppercase font-bold text-zinc-400 tracking-wider flex items-center gap-2 px-2">
                    <i data-lucide="list-tree" class="w-4 h-4 text-amber-400"></i>
                    <span>Daftar Isi Dokumen</span>
                </p>

                <nav class="space-y-1 text-xs">
                    <button @click="scrollTo('sec-1')"
                            :class="activeSection === 'sec-1' ? 'bg-amber-500/10 text-amber-300 font-bold border-amber-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>1. Pendahuluan & Prinsip Utama</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>

                    <button @click="scrollTo('sec-2')"
                            :class="activeSection === 'sec-2' ? 'bg-amber-500/10 text-amber-300 font-bold border-amber-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>2. Kategori Data yang Dikumpulkan</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>

                    <button @click="scrollTo('sec-3')"
                            :class="activeSection === 'sec-3' ? 'bg-amber-500/10 text-amber-300 font-bold border-amber-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>3. Tujuan & Basis Hukum Pemrosesan</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>

                    <button @click="scrollTo('sec-4')"
                            :class="activeSection === 'sec-4' ? 'bg-amber-500/10 text-amber-300 font-bold border-amber-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>4. Isolasi Sub-Profil & Mode Anak</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>

                    <button @click="scrollTo('sec-5')"
                            :class="activeSection === 'sec-5' ? 'bg-amber-500/10 text-amber-300 font-bold border-amber-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>5. Keamanan & Retensi Data</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>

                    <button @click="scrollTo('sec-6')"
                            :class="activeSection === 'sec-6' ? 'bg-amber-500/10 text-amber-300 font-bold border-amber-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>6. Hak-Hak Pemilik Data</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>

                    <button @click="scrollTo('sec-7')"
                            :class="activeSection === 'sec-7' ? 'bg-amber-500/10 text-amber-300 font-bold border-amber-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>7. Kontak & Tim Perlindungan Data</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>
                </nav>
            </div>

            <!-- Help Contact Box -->
            <div class="glass-card p-5 rounded-3xl border border-white/10 bg-amber-500/5 space-y-3">
                <div class="flex items-center gap-2.5 text-amber-400 font-bold text-xs">
                    <i data-lucide="help-circle" class="w-4 h-4"></i>
                    <span>Ada Pertanyaan Privasi?</span>
                </div>
                <p class="text-[11px] text-zinc-300 leading-relaxed">
                    Tim Data Protection Officer (DPO) kami siap membantu menjawab pertanyaan Anda terkait pengelolaan data akun.
                </p>
                <a href="mailto:support@faiilmov.my.id" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-400 hover:text-amber-300 transition-colors">
                    <span>support@faiilmov.my.id</span>
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>

        <!-- Right Content Column -->
        <div class="lg:col-span-8 space-y-8">

            <!-- Section 1: Pendahuluan -->
            <section id="sec-1" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">1. Pendahuluan & Prinsip Utama</h2>
                        <p class="text-xs text-zinc-400">Landasan hukum dan filosofi perlindungan data kami</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>
                        Selamat datang di <strong class="text-white">faiilmov</strong>. Kebijakan Privasi ini menerangkan tata cara pengumpulan, pengelolaan, penyimpanan, dan perlindungan data pribadi Anda sewaktu mengakses platform web, aplikasi mobile, maupun layanan Nonton Bareng (Watch Party) kami.
                    </p>
                    <p>
                        Kami memegang teguh 3 prinsip utama perlindungan data:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1">
                            <span class="text-amber-400 font-bold text-xs block">1. Minimalisasi Data</span>
                            <span class="text-[11px] text-zinc-400 leading-normal block">Hanya mengumpulkan data yang mutlak diperlukan untuk operasional tayangan.</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1">
                            <span class="text-sky-400 font-bold text-xs block">2. Isolasi Mutlak</span>
                            <span class="text-[11px] text-zinc-400 leading-normal block">Memastikan riwayat dan rekomendasi antar sub-profil keluarga terlindungi terpisah.</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1">
                            <span class="text-emerald-400 font-bold text-xs block">3. Tanpa Penjualan Data</span>
                            <span class="text-[11px] text-zinc-400 leading-normal block">Kami tidak pernah menjual atau menyewakan data pribadi Anda ke pihak ketiga.</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 2: Kategori Data yang Dikumpulkan -->
            <section id="sec-2" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center shrink-0">
                        <i data-lucide="database" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">2. Kategori Data yang Dikumpulkan</h2>
                        <p class="text-xs text-zinc-400">Rincian jenis data dan atribusi pemrosesan</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 leading-relaxed space-y-4 pt-2">
                    <!-- Data Table -->
                    <div class="overflow-x-auto rounded-2xl border border-white/10">
                        <table class="w-full text-left text-xs text-zinc-300">
                            <thead class="bg-white/5 text-zinc-200 uppercase text-[10px] font-bold tracking-wider border-b border-white/10">
                                <tr>
                                    <th class="p-3.5">Kategori Data</th>
                                    <th class="p-3.5">Komponen Spesifik</th>
                                    <th class="p-3.5">Retensi Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 bg-dark-950/40">
                                <tr>
                                    <td class="p-3.5 font-bold text-white">Data Akun Utama</td>
                                    <td class="p-3.5 text-zinc-400">Nama lengkap, alamat email, kata sandi (Bcrypt Hashed)</td>
                                    <td class="p-3.5 text-amber-400 font-mono">Selama Akun Aktif</td>
                                </tr>
                                <tr>
                                    <td class="p-3.5 font-bold text-white">Data Sub-Profil</td>
                                    <td class="p-3.5 text-zinc-400">Nama profil, pilihan avatar, penanda Profil Anak (Is Kids), PIN Hash Parental Control</td>
                                    <td class="p-3.5 text-amber-400 font-mono">Sampai Profil Dihapus</td>
                                </tr>
                                <tr>
                                    <td class="p-3.5 font-bold text-white">Telemetri Streaming</td>
                                    <td class="p-3.5 text-zinc-400">Progres detik tayangan, resolusi video pilihan, riwayat tontonan, daftar simpanan</td>
                                    <td class="p-3.5 text-amber-400 font-mono">Dapat Dihapus Manual</td>
                                </tr>
                                <tr>
                                    <td class="p-3.5 font-bold text-white">Log Watch Party</td>
                                    <td class="p-3.5 text-zinc-400">Pesan obrolan sementara dalam ruang Nobar, respons emotikon, waktu bergabung</td>
                                    <td class="p-3.5 text-zinc-500 font-mono">Otomatis Terhapus (24j)</td>
                                </tr>
                                <tr>
                                    <td class="p-3.5 font-bold text-white">Log Teknis & Keamanan</td>
                                    <td class="p-3.5 text-zinc-400">Alamat IP (Anonimisasi sebagian), jenis peramban, token sesi terenkripsi</td>
                                    <td class="p-3.5 text-zinc-500 font-mono">Maksimal 30 Hari</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Section 3: Tujuan & Basis Hukum Pemrosesan -->
            <section id="sec-3" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                        <i data-lucide="scale" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">3. Tujuan & Basis Hukum Pemrosesan</h2>
                        <p class="text-xs text-zinc-400">Bagaimana dan mengapa kami mengolah data Anda</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <ul class="space-y-3 text-zinc-300">
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span><strong class="text-white">Penyediaan Layanan Streaming:</strong> Memungkinkan pemutaran kontinuitas (Lanjut Nonton) dari titik detik terakhir di seluruh perangkat Anda.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span><strong class="text-white">Rekomendasi Konten Personal:</strong> Menghasilkan rekomendasi sinema berbasis AI (NVIDIA Machine Learning) sesuai riwayat masing-masing sub-profil.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span><strong class="text-white">Fitur Komunitas Watch Party:</strong> Menyinkronkan timestamp pemutaran dan obrolan obrolan langsung secara real-time antar peserta ruang Nobar.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span><strong class="text-white">Perlindungan Keamanan Sistem:</strong> Mencegah penyalahgunaan akun, peretasan, dan banjir permintaan data otomatis melalui mekanisme Rate Limiting.</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Section 4: Isolasi Sub-Profil & Mode Anak -->
            <section id="sec-4" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">4. Isolasi Sub-Profil & Fitur Anak (Kids Mode)</h2>
                        <p class="text-xs text-zinc-400">Perlindungan privasi keluarga dan kontrol usia dinamis</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 leading-relaxed space-y-4 pt-2">
                    <p>
                        Sistem arsitektur database faiilmov mengikat seluruh data tayangan pada tingkatan <code class="bg-white/10 px-2 py-0.5 rounded text-amber-300 font-mono text-xs">profile_id</code> secara ketat.
                    </p>

                    <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/20 space-y-2">
                        <div class="flex items-center gap-2 text-purple-300 font-bold text-xs uppercase tracking-wider">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                            <span>Mode Profil Anak (Kids Profile Protection)</span>
                        </div>
                        <p class="text-xs text-zinc-300 leading-relaxed">
                            Ketika profil ditandai sebagai **Profil Anak**, algoritma filter konten secara otomatis memblokir seluruh judul film/series dengan sertifikasi usia <span class="text-rose-400 font-bold">18+</span>, <span class="text-orange-400 font-bold">16+</span>, dan <span class="text-sky-400 font-bold">13+</span>. Profil Anak hanya dapat melihat judul berkategori Semua Umur (<span class="text-emerald-400 font-bold">SU</span>, <span class="text-emerald-400 font-bold">G</span>, <span class="text-emerald-400 font-bold">PG</span>). Akses langsung melalui URL pun akan diblokir secara otomatis oleh controller sistem.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Section 5: Keamanan & Retensi Data -->
            <section id="sec-5" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">5. Keamanan & Retensi Data</h2>
                        <p class="text-xs text-zinc-400">Enkripsi berstandar industri dan perlindungan data</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>
                        Kami mengimplementasikan tindakan teknis berstandar industri untuk melindungi data dari akses tanpa izin, pengubahan, atau penghancuran:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-zinc-400">
                        <li><strong class="text-zinc-200">Enkripsi Pengiriman (In-Transit):</strong> Seluruh lalu lintas data dienkripsi menggunakan protokol SSL/TLS HTTPS 256-bit.</li>
                        <li><strong class="text-zinc-200">Enkripsi Kata Sandi & PIN:</strong> Seluruh kredensial dan PIN Parental Control di-hash menggunakan algoritma Bcrypt / Argon2 satu arah.</li>
                        <li><strong class="text-zinc-200">Isolasi Sesi:</strong> Sesi pengguna dilindungi token CSRF dan cookie dengan atribut <code class="bg-white/10 px-1.5 py-0.5 rounded text-xs font-mono text-zinc-200">HttpOnly</code> & <code class="bg-white/10 px-1.5 py-0.5 rounded text-xs font-mono text-zinc-200">SameSite=Lax</code>.</li>
                    </ul>
                </div>
            </section>

            <!-- Section 6: Hak-Hak Pemilik Data -->
            <section id="sec-6" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <i data-lucide="user-x" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">6. Hak-Hak Pemilik Data</h2>
                        <p class="text-xs text-zinc-400">Kendali penuh atas data Anda sesuai regulasi UU PDP & GDPR</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>Sesuai dengan regulasi perlindungan data pribadi yang berlaku, Anda memiliki hak-hak berikut:</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1">
                            <span class="text-white font-bold text-xs block flex items-center gap-1.5">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-400"></i>
                                <span>Hak Penghapusan Riwayat</span>
                            </span>
                            <span class="text-[11px] text-zinc-400 leading-normal block">Anda dapat mengosongkan riwayat tontonan atau daftar simpanan pada profil kapan saja melalui pengaturan.</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1">
                            <span class="text-white font-bold text-xs block flex items-center gap-1.5">
                                <i data-lucide="user-minus" class="w-3.5 h-3.5 text-orange-400"></i>
                                <span>Hak Menghapus Sub-Profil</span>
                            </span>
                            <span class="text-[11px] text-zinc-400 leading-normal block">Sub-profil anggota keluarga dapat dihapus beserta seluruh rekaman histori tayangannya secara permanen.</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1">
                            <span class="text-white font-bold text-xs block flex items-center gap-1.5">
                                <i data-lucide="alert-octagon" class="w-3.5 h-3.5 text-red-400"></i>
                                <span>Hak Penghapusan Akun</span>
                            </span>
                            <span class="text-[11px] text-zinc-400 leading-normal block">Fasilitas penghapusan akun utama secara permanen tersedia di halaman Pengaturan Akun.</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1">
                            <span class="text-white font-bold text-xs block flex items-center gap-1.5">
                                <i data-lucide="download" class="w-3.5 h-3.5 text-sky-400"></i>
                                <span>Hak Akses Data</span>
                            </span>
                            <span class="text-[11px] text-zinc-400 leading-normal block">Anda berhak meminta salinan data akun dan riwayat yang tersimpan dengan menghubungi tim DPO kami.</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 7: Kontak & Tim Perlindungan Data -->
            <section id="sec-7" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">7. Kontak Resmi Tim Perlindungan Data</h2>
                        <p class="text-xs text-zinc-400">Kanal komunikasi resmi seputar hak privasi data</p>
                    </div>
                </div>

                <div class="text-sm text-zinc-300 leading-relaxed space-y-4 pt-2">
                    <p>
                        Jika Anda memiliki pertanyaan, keluhan, atau ingin melaksanakan hak perlindungan data Anda, silakan hubungi Tim Data Protection Officer (DPO) faiilmov melalui kanal berikut:
                    </p>

                    <div class="p-4 rounded-2xl bg-dark-950 border border-white/10 space-y-2 text-xs font-mono">
                        <div class="flex items-center gap-2 text-zinc-300">
                            <i data-lucide="mail" class="w-4 h-4 text-amber-400"></i>
                            <span>Email Legal & DPO: <a href="mailto:support@faiilmov.my.id" class="text-amber-400 underline font-bold">support@faiilmov.my.id</a></span>
                        </div>
                        <div class="flex items-center gap-2 text-zinc-300">
                            <i data-lucide="headphones" class="w-4 h-4 text-sky-400"></i>
                            <span>Layanan Pengguna: <a href="mailto:support@faiilmov.my.id" class="text-sky-400 underline font-bold">support@faiilmov.my.id</a></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom Home Nav Button -->
            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/15 transition-all shadow-lg hover:scale-105">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali ke Beranda</span>
                </a>

                <button @click="scrollTo('sec-1')" class="inline-flex items-center gap-1.5 text-xs text-zinc-400 hover:text-white transition-colors cursor-pointer">
                    <span>Ke Atas</span>
                    <i data-lucide="arrow-up" class="w-3.5 h-3.5"></i>
                </button>
            </div>

        </div>

    </div>

</div>
@endsection
