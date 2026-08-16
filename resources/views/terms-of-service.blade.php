@extends('layouts.app')

@section('title', 'Syarat & Ketentuan - faiilmov')
@section('meta_description', 'Syarat dan Ketentuan resmi penggunaan platform streaming faiilmov. Bacalah dengan saksama sebelum menggunakan layanan kami.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 space-y-10"
     x-data="{
         activeSection: 'tos-1',
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
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-sky-500/10 border border-sky-500/30 text-sky-400 text-xs font-bold shadow-lg">
                    <i data-lucide="file-check-2" class="w-4 h-4"></i>
                    <span>LEGAL & TERMS OF SERVICE</span>
                </div>

                <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight">
                    Syarat & Ketentuan Layanan
                </h1>

                <p class="text-sm sm:text-base text-zinc-300 leading-relaxed">
                    Dokumen ini mengatur tata cara penggunaan platform streaming <strong class="text-white">faiilmov</strong>. Dengan mengakses atau menggunakan layanan kami, Anda dianggap telah membaca, memahami, dan menyetujui seluruh ketentuan ini.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row md:flex-col items-start md:items-end gap-3 shrink-0">
                <div class="flex items-center gap-2 text-[11px] text-zinc-400 font-mono bg-zinc-900/80 px-3.5 py-2 rounded-2xl border border-white/10 shadow-inner">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-sky-400"></i>
                    <span>Dokumen ID: <strong class="text-white">FLM-TOS-2026-V1</strong></span>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="copyLink()" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-zinc-200 font-semibold transition-all flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="link" class="w-3.5 h-3.5 text-sky-400"></i>
                        <span x-text="copied ? 'Tautan Tersalin!' : 'Bagikan Tautan'"></span>
                    </button>
                    <button onclick="window.print()" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-zinc-200 font-semibold transition-all items-center gap-1.5 cursor-pointer hidden sm:flex">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <span>Cetak</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Metadata Pills Bar -->
        <div class="pt-4 border-t border-white/10 flex flex-wrap items-center gap-4 text-xs text-zinc-400">
            <div class="flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-sky-400"></i>
                <span>Berlaku Sejak: <strong class="text-zinc-200">9 Agustus 2026</strong></span>
            </div>
            <span class="text-zinc-600">•</span>
            <div class="flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-sky-400"></i>
                <span>Waktu Baca: <strong class="text-zinc-200">~8 Menit</strong></span>
            </div>
            <span class="text-zinc-600">•</span>
            <div class="flex items-center gap-1.5">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>Yurisdiksi: <strong class="text-zinc-200">Hukum Negara Republik Indonesia</strong></span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Sidebar: Sticky Table of Contents -->
        <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
            <div class="glass-panel p-5 rounded-3xl border border-white/10 bg-dark-900/60 backdrop-blur-xl shadow-xl space-y-3">
                <p class="text-xs uppercase font-bold text-zinc-400 tracking-wider flex items-center gap-2 px-2">
                    <i data-lucide="list-tree" class="w-4 h-4 text-sky-400"></i>
                    <span>Daftar Isi Dokumen</span>
                </p>

                <nav class="space-y-1 text-xs">
                    @foreach([
                        ['id' => 'tos-1', 'label' => '1. Penerimaan & Eligibilitas'],
                        ['id' => 'tos-2', 'label' => '2. Pendaftaran & Keamanan Akun'],
                        ['id' => 'tos-3', 'label' => '3. Lisensi Penggunaan Konten'],
                        ['id' => 'tos-4', 'label' => '4. Larangan & Aktivitas Terlarang'],
                        ['id' => 'tos-5', 'label' => '5. Sistem Multi-Profil & Parental Control'],
                        ['id' => 'tos-6', 'label' => '6. Layanan Watch Party'],
                        ['id' => 'tos-7', 'label' => '7. Tanggung Jawab & Penafian'],
                        ['id' => 'tos-8', 'label' => '8. Penangguhan & Penghentian Akun'],
                        ['id' => 'tos-9', 'label' => '9. Perubahan Layanan & Ketentuan'],
                        ['id' => 'tos-10', 'label' => '10. Hukum yang Berlaku & Kontak'],
                    ] as $item)
                    <button @click="scrollTo('{{ $item['id'] }}')"
                            :class="activeSection === '{{ $item['id'] }}' ? 'bg-sky-500/10 text-sky-300 font-bold border-sky-500/30' : 'text-zinc-400 hover:text-white hover:bg-white/5 border-transparent'"
                            class="w-full text-left px-3.5 py-2.5 rounded-xl border transition-all flex items-center justify-between group">
                        <span>{{ $item['label'] }}</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0"></i>
                    </button>
                    @endforeach
                </nav>
            </div>

            <!-- Notice Box -->
            <div class="glass-card p-5 rounded-3xl border border-amber-500/20 bg-amber-500/5 space-y-3">
                <div class="flex items-center gap-2.5 text-amber-400 font-bold text-xs">
                    <i data-lucide="triangle-alert" class="w-4 h-4"></i>
                    <span>Penting Dibaca</span>
                </div>
                <p class="text-[11px] text-zinc-300 leading-relaxed">
                    Dengan menggunakan layanan faiilmov, Anda setuju untuk terikat oleh Syarat & Ketentuan ini. Jika Anda tidak setuju, mohon hentikan penggunaan platform.
                </p>
            </div>

            <!-- Related Links -->
            <div class="glass-card p-5 rounded-3xl border border-white/10 bg-zinc-900/50 space-y-3">
                <p class="text-xs font-bold text-zinc-300 flex items-center gap-2">
                    <i data-lucide="link-2" class="w-4 h-4 text-sky-400"></i>
                    <span>Dokumen Terkait</span>
                </p>
                <a href="{{ route('privacy-policy') }}" class="flex items-center gap-2 text-xs text-zinc-400 hover:text-amber-400 transition-colors group">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Kebijakan Privasi & Data Pribadi</span>
                    <i data-lucide="arrow-up-right" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity ml-auto"></i>
                </a>
            </div>
        </div>

        <!-- Right Content Column -->
        <div class="lg:col-span-8 space-y-8">

            <!-- Section 1: Penerimaan & Eligibilitas -->
            <section id="tos-1" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center shrink-0">
                        <i data-lucide="handshake" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">1. Penerimaan & Eligibilitas Pengguna</h2>
                        <p class="text-xs text-zinc-400">Siapa yang dapat menggunakan platform faiilmov</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>Layanan faiilmov tersedia untuk pengguna yang memenuhi kriteria berikut:</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1 text-center">
                            <span class="text-3xl font-black text-sky-400 block">13+</span>
                            <span class="text-[11px] text-zinc-400 block">Usia minimum pendaftaran akun</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1 text-center">
                            <span class="text-3xl font-black text-amber-400 block">5</span>
                            <span class="text-[11px] text-zinc-400 block">Maksimal sub-profil per akun utama</span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white/5 border border-white/5 space-y-1 text-center">
                            <span class="text-2xl font-black text-emerald-400 block">ID</span>
                            <span class="text-[11px] text-zinc-400 block">Layanan dioperasikan di wilayah Indonesia</span>
                        </div>
                    </div>
                    <p class="text-zinc-400 text-xs pt-1">
                        Pengguna di bawah usia 13 tahun wajib diawasi oleh orang tua/wali yang telah menyetujui Syarat & Ketentuan ini atas nama mereka. Akun yang terbukti milik anak di bawah umur tanpa persetujuan orang tua dapat ditangguhkan sewaktu-waktu.
                    </p>
                </div>
            </section>

            <!-- Section 2: Pendaftaran & Keamanan Akun -->
            <section id="tos-2" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                        <i data-lucide="user-round-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">2. Pendaftaran & Tanggung Jawab Keamanan Akun</h2>
                        <p class="text-xs text-zinc-400">Kewajiban menjaga kredensial dan keamanan akun</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>Saat mendaftar, Anda bertanggung jawab penuh untuk:</p>
                    <ul class="space-y-2.5">
                        <li class="flex items-start gap-2.5 text-zinc-300">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span>Memberikan informasi yang akurat, terkini, dan lengkap pada formulir pendaftaran.</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-zinc-300">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span>Menjaga kerahasiaan kata sandi akun. Setiap aktivitas yang dilakukan melalui akun Anda sepenuhnya menjadi tanggung jawab Anda.</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-zinc-300">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span>Segera melaporkan kepada kami jika terjadi akses tidak sah ke akun Anda melalui kanal resmi.</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-zinc-300">
                            <i data-lucide="check" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                            <span>Tidak berbagi atau mentransfer akses akun kepada pihak lain yang tidak tercantum sebagai sub-profil resmi.</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Section 3: Lisensi Penggunaan Konten -->
            <section id="tos-3" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                        <i data-lucide="film" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">3. Lisensi & Hak Penggunaan Konten</h2>
                        <p class="text-xs text-zinc-400">Hak yang diberikan kepada pengguna atas konten platform</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/20 space-y-2">
                        <div class="flex items-center gap-2 text-purple-300 font-bold text-xs uppercase tracking-wider">
                            <i data-lucide="info" class="w-4 h-4"></i>
                            <span>Lisensi Terbatas & Non-Eksklusif</span>
                        </div>
                        <p class="text-xs text-zinc-300 leading-relaxed">
                            faiilmov memberikan Anda lisensi yang terbatas, non-eksklusif, dan tidak dapat dipindahtangankan untuk mengakses dan menonton konten yang tersedia <strong>hanya untuk keperluan pribadi dan non-komersial</strong>.
                        </p>
                    </div>
                    <p>Konten yang tersedia di platform faiilmov | termasuk film, series, poster, metadata, dan deskripsi sinopsi | <strong class="text-white">dilindungi oleh hak kekayaan intelektual</strong> yang dimiliki atau dilisensikan kepada faiilmov. Anda dilarang keras:</p>
                    <ul class="space-y-1.5 text-xs text-zinc-400 list-disc list-inside">
                        <li>Mengunduh, merekam layar, atau menyimpan konten streaming secara tidak sah.</li>
                        <li>Mendistribusikan, menjual, atau menyiarkan ulang konten kepada pihak ketiga.</li>
                        <li>Menggunakan konten untuk kepentingan komersial tanpa izin tertulis resmi.</li>
                        <li>Merekayasa balik (reverse engineer) teknologi streaming atau sistem enkripsi platform.</li>
                    </ul>
                </div>
            </section>

            <!-- Section 4: Larangan & Aktivitas Terlarang -->
            <section id="tos-4" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <i data-lucide="ban" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">4. Larangan & Aktivitas Terlarang</h2>
                        <p class="text-xs text-zinc-400">Tindakan yang dilarang keras di platform</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>Pengguna dilarang melakukan aktivitas berikut selama menggunakan platform faiilmov:</p>

                    <div class="overflow-x-auto rounded-2xl border border-white/10">
                        <table class="w-full text-left text-xs text-zinc-300">
                            <thead class="bg-white/5 text-zinc-200 uppercase text-[10px] font-bold tracking-wider border-b border-white/10">
                                <tr>
                                    <th class="p-3.5">Kategori</th>
                                    <th class="p-3.5">Deskripsi Larangan</th>
                                    <th class="p-3.5">Konsekuensi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 bg-dark-950/40 text-xs">
                                <tr>
                                    <td class="p-3.5 font-bold text-rose-400">Penyalahgunaan Teknis</td>
                                    <td class="p-3.5 text-zinc-400">Serangan bot, scraping data, injeksi SQL, eksploitasi celah keamanan sistem</td>
                                    <td class="p-3.5 text-rose-300 font-mono">Penangguhan Permanen + Laporan Hukum</td>
                                </tr>
                                <tr>
                                    <td class="p-3.5 font-bold text-orange-400">Manipulasi Akun</td>
                                    <td class="p-3.5 text-zinc-400">Membuat akun palsu, menyamar sebagai pengguna lain, berbagi akun secara massal</td>
                                    <td class="p-3.5 text-orange-300 font-mono">Penangguhan Sementara / Permanen</td>
                                </tr>
                                <tr>
                                    <td class="p-3.5 font-bold text-yellow-400">Pelanggaran Hak Cipta</td>
                                    <td class="p-3.5 text-zinc-400">Merekam, mendistribusikan, atau menjual konten tanpa izin</td>
                                    <td class="p-3.5 text-yellow-300 font-mono">Penangguhan + Tuntutan Hukum Perdata</td>
                                </tr>
                                <tr>
                                    <td class="p-3.5 font-bold text-sky-400">Konten Watch Party</td>
                                    <td class="p-3.5 text-zinc-400">Mengirim konten SARA, ujaran kebencian, atau spam di ruang obrolan Nobar</td>
                                    <td class="p-3.5 text-sky-300 font-mono">Kick dari Ruangan + Peringatan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Section 5: Sistem Multi-Profil & Parental Control -->
            <section id="tos-5" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <i data-lucide="users-round" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">5. Sistem Multi-Profil & Parental Control</h2>
                        <p class="text-xs text-zinc-400">Ketentuan penggunaan fitur profil keluarga</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>Akun utama dapat mengelola hingga <strong class="text-white">5 sub-profil</strong> untuk anggota keluarga. Pengelola akun utama bertanggung jawab penuh atas seluruh aktivitas sub-profil yang dibuat di bawah akunnya, termasuk:</p>
                    <ul class="space-y-2 text-zinc-400 list-disc list-inside text-xs">
                        <li>Memastikan pengaturan <strong class="text-zinc-200">PIN Parental Control</strong> diaktifkan untuk profil yang digunakan oleh anak-anak.</li>
                        <li>Mengawasi bahwa sub-profil anak ditandai sebagai <strong class="text-zinc-200">Profil Anak (Kids Mode)</strong> agar pemfilteran konten otomatis aktif.</li>
                        <li>Bertanggung jawab atas konten yang ditonton melalui sub-profil yang berada dalam akun Anda.</li>
                    </ul>
                    <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-zinc-300 leading-relaxed">
                        <strong class="text-emerald-400 block mb-1">Catatan penting:</strong>
                        Meskipun sistem Kids Mode memblokir konten dewasa secara otomatis, orang tua tetap dianjurkan untuk secara aktif memantau penggunaan platform oleh anak-anak mereka.
                    </div>
                </div>
            </section>

            <!-- Section 6: Layanan Watch Party -->
            <section id="tos-6" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                        <i data-lucide="tv-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">6. Layanan Nonton Bareng (Watch Party)</h2>
                        <p class="text-xs text-zinc-400">Ketentuan khusus fitur komunitas nobar real-time</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>Fitur Nonton Bareng (Watch Party) memungkinkan pengguna menyaksikan film secara sinkron dalam ruang virtual bersama. Dengan menggunakan fitur ini, Anda setuju bahwa:</p>
                    <ul class="space-y-2.5">
                        <li class="flex items-start gap-2.5 text-zinc-300">
                            <i data-lucide="check" class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5"></i>
                            <span>Pesan obrolan dalam ruang Nobar bersifat sementara dan akan dihapus otomatis setelah sesi berakhir (maksimal 24 jam).</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-zinc-300">
                            <i data-lucide="check" class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5"></i>
                            <span>Host ruangan berhak mengeluarkan (kick) peserta yang melanggar aturan komunitas atau mengirimkan konten tidak pantas.</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-zinc-300">
                            <i data-lucide="check" class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5"></i>
                            <span>Aktivitas obrolan yang terindikasi melanggar Ketentuan ini dapat dilaporkan dan dapat mengakibatkan penangguhan fitur Watch Party pada akun Anda.</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Section 7: Tanggung Jawab & Penafian -->
            <section id="tos-7" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 flex items-center justify-center shrink-0">
                        <i data-lucide="scale" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">7. Batasan Tanggung Jawab & Penafian</h2>
                        <p class="text-xs text-zinc-400">Klausul limitation of liability dan disclaimer layanan</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>Platform faiilmov disediakan sebagaimana adanya (<em>as is</em>) dan <em>as available</em>. Sejauh diizinkan oleh hukum yang berlaku:</p>
                    <ul class="space-y-2.5 text-zinc-400 text-xs">
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="minus" class="w-3.5 h-3.5 text-yellow-400 shrink-0 mt-0.5"></i>
                            <span>faiilmov tidak menjamin ketersediaan layanan tanpa gangguan, terutama dalam kondisi force majeure, pemeliharaan terencana, atau gangguan infrastruktur pihak ketiga.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="minus" class="w-3.5 h-3.5 text-yellow-400 shrink-0 mt-0.5"></i>
                            <span>Ketersediaan judul konten dapat berubah sewaktu-waktu sesuai dengan ketersediaan lisensi dari pemegang hak cipta.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="minus" class="w-3.5 h-3.5 text-yellow-400 shrink-0 mt-0.5"></i>
                            <span>faiilmov tidak bertanggung jawab atas kerugian tidak langsung, insidental, atau konsekuensial yang timbul dari penggunaan layanan ini.</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Section 8: Penangguhan & Penghentian Akun -->
            <section id="tos-8" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <i data-lucide="user-round-x" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">8. Penangguhan & Penghentian Akun</h2>
                        <p class="text-xs text-zinc-400">Kondisi yang dapat mengakibatkan penangguhan layanan</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>faiilmov berhak menangguhkan atau mengakhiri akun pengguna | baik sementara maupun permanen | apabila terdapat indikasi:</p>
                    <ul class="space-y-2 text-zinc-400 list-disc list-inside text-xs">
                        <li>Pelanggaran terhadap pasal-pasal dalam Syarat & Ketentuan ini.</li>
                        <li>Aktivitas penipuan, pencurian identitas, atau penyalahgunaan sistem autentikasi.</li>
                        <li>Penggunaan platform untuk tujuan ilegal berdasarkan hukum Negara Republik Indonesia.</li>
                        <li>Permintaan penangguhan yang ditujukan melalui kanal resmi.</li>
                    </ul>
                    <p class="text-xs text-zinc-400 pt-1">
                        Penghentian akun atas permintaan pengguna sendiri dapat dilakukan kapan saja melalui menu Pengaturan Akun. Seluruh data akun akan dihapus permanen dalam waktu 30 hari setelah konfirmasi penghapusan.
                    </p>
                </div>
            </section>

            <!-- Section 9: Perubahan Layanan & Ketentuan -->
            <section id="tos-9" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-zinc-500/10 border border-zinc-500/20 text-zinc-400 flex items-center justify-center shrink-0">
                        <i data-lucide="refresh-ccw" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">9. Perubahan Layanan & Ketentuan</h2>
                        <p class="text-xs text-zinc-400">Prosedur pembaruan syarat & ketentuan</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-3 pt-2">
                    <p>
                        faiilmov berhak memperbarui Syarat & Ketentuan ini sewaktu-waktu. Perubahan substansial akan diberitahukan kepada pengguna melalui notifikasi dalam platform paling lambat <strong class="text-white">14 hari sebelum perubahan berlaku</strong>. Penggunaan layanan yang berlanjut setelah tanggal berlakunya perubahan dianggap sebagai persetujuan terhadap ketentuan baru.
                    </p>
                </div>
            </section>

            <!-- Section 10: Hukum yang Berlaku & Kontak -->
            <section id="tos-10" class="glass-card p-6 sm:p-8 rounded-3xl border border-white/10 space-y-4 bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center shrink-0">
                        <i data-lucide="landmark" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-serif font-bold text-lg sm:text-xl text-white">10. Hukum yang Berlaku & Kontak Resmi</h2>
                        <p class="text-xs text-zinc-400">Yurisdiksi hukum dan kanal komunikasi resmi</p>
                    </div>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed space-y-4 pt-2">
                    <p>
                        Syarat & Ketentuan ini diatur oleh dan ditafsirkan berdasarkan <strong class="text-white">hukum Negara Republik Indonesia</strong>, termasuk namun tidak terbatas pada Undang-Undang Informasi dan Transaksi Elektronik (UU ITE) dan Undang-Undang Hak Cipta yang berlaku.
                    </p>

                    <div class="p-4 rounded-2xl bg-dark-950 border border-white/10 space-y-2 text-xs font-mono">
                        <div class="flex items-center gap-2 text-zinc-300">
                            <i data-lucide="mail" class="w-4 h-4 text-sky-400"></i>
                            <span>Email Legal & TOS: <a href="mailto:support@faiilmov.my.id" class="text-sky-400 underline font-bold">support@faiilmov.my.id</a></span>
                        </div>
                        <div class="flex items-center gap-2 text-zinc-300">
                            <i data-lucide="headphones" class="w-4 h-4 text-amber-400"></i>
                            <span>Layanan Pengguna: <a href="mailto:support@faiilmov.my.id" class="text-amber-400 underline font-bold">support@faiilmov.my.id</a></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom Nav -->
            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/15 transition-all shadow-lg hover:scale-105">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali ke Beranda</span>
                </a>
                <button @click="scrollTo('tos-1')" class="inline-flex items-center gap-1.5 text-xs text-zinc-400 hover:text-white transition-colors cursor-pointer">
                    <span>Ke Atas</span>
                    <i data-lucide="arrow-up" class="w-3.5 h-3.5"></i>
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
