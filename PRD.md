# Product Requirements Document (PRD) — web film "faillmov"

## 1. Overview & Problem Statement

**faillmov** adalah aplikasi web streaming film dan series berbasis Laravel 11 yang mengintegrasikan sumber video dari API eksternal (MovieBox). Dokumen ini menjadi acuan pengembangan spesifikasi fungsional dan teknis untuk 6 fitur inti: Browse & Filter Katalog Film, Custom Video Player, Search dengan Relevance Matching, Watch Party Real-time, Review & Rating, serta Watchlist & Riwayat Tontonan.

### Masalah & Alasan Pentingnya Fitur

| Fitur | Masalah yang Disesuaikan | Alasan Pentingnya Fitur |
| :--- | :--- | :--- |
| **1. Browse & Filter Katalog Film** | Katalog gabungan antara movie dan series sering membingungkan pengguna yang hanya ingin mencari tontonan tipe tertentu atau genre spesifik. | Mempercepat penemuan film sesuai selera pengguna tanpa harus menggeledah halaman katalog satu per satu. |
| **2. Custom Video Player** | Player bawaan HTML5/browser tidak dapat memutar URL stream MovieBox karena pembatasan CORS/header Referer, tidak memiliki navigasi episode series, dan tidak mendukung subtitle Bahasa Indonesia (`.vtt`). | Menjaga video tetap dapat diputar tanpa error 403 Forbidden, serta memfasilitasi penonton series agar tidak perlu keluar dari player untuk berpindah episode. |
| **3. Search dengan Relevance Matching** | Pencarian teks biasa (SQL `LIKE %query%`) menghasilkan list yang acak (misal mencari "Batman" tapi film pendek animasi muncul paling atas dibanding film utama) dan sensitif terhadap posisi kata kunci. | Menampilkan hasil pencarian yang paling relevan di urutan teratas berdasarkan tingkat kecocokan judul, popularitas, dan jenis konten. |
| **4. Watch Party (Nonton Bareng)** | Menonton bersama secara jarak jauh dengan menekan tombol play manual sering terdistorsi oleh perbedaan jeda koneksi internet (out-of-sync hingga puluhan detik). | Menyamakan waktu pemutaran (play, pause, seek) antar penonton di room secara otomatis dengan selisih waktu minimal via WebSockets atau sync state. |
| **5. Review & Rating** | Penonton tidak mengetahui apakah suatu film layak ditonton atau tidak sebelum menghabiskan waktu menontonnya. | Menyediakan acuan objektif dari sesama penonton faillmov berupa skor rating 1–5 dan ulasan teks. |
| **6. Watchlist & Riwayat Tontonan** | Penonton sering lupa episode terakhir yang ditonton atau posisi menit tayangan saat pemutaran terputus di tengah jalan. | Menyimpan posisi tayangan terakhir dan daftar simpanan film agar pengguna dapat melanjutkan tontonan kapan saja. |

---

## 2. Goals & Success Metrics

### Goals Spesifik
1. **Kecepatan Akses Stream**: Video player dapat mulai memuat byte pertama video (*Time to First Frame*) dalam waktu **< 2 detik** via proxy controller backend.
2. **Kesesuaian Pencarian**: Top 3 hasil pencarian utama untuk judul populer (misal "Avatar", "Naruto", "Spider-Man") harus menampilkan film/series utama pada posisi paling atas.
3. **Akurasi Watch Party**: Selisih waktu playback (*drift*) antar penonton di room Watch Party dijaga tidak lebih dari **±1.5 detik** saat sinyal internet stabil.
4. **Resistensi HTTP 403/410 Stream**: Proxy stream backend mampu melakukan auto-retry/refresh token URL secara transparan jika link stream MovieBox kadaluarsa.

### Success Metrics & Metode Pengukuran
* **Stream Failure Rate**: Persentase request ke `/moviebox/proxy-stream` yang mengembalikan response HTTP 4xx/5xx. *(Diukur dari log exception server Laravel & proxy)*.
* **Watch Party Drift Log**: Selisih detik `current_time` penonton terhadap `current_time` Host saat event `syncState` terjadi. *(Diukur via client-side console logging / server event monitor)*.
* **Search Execution Time**: Durasi eksekusi query pencarian dari request diterima hingga JSON response dikembalikan. *(Diukur via Laravel Query Log / Telescope, target < 150ms)*.
* **Watchlist & Resume Trigger Rate**: Rasio pemutaran video yang dipicu dari halaman Watchlist atau tombol "Lanjutkan Nonton". *(Diukur dari hit update record `watch_histories`)*.

---

## 3. User Stories

### Skenario 1: Budi Nonton Series Anime Saat Pulang Kerja di HP
* **Konteks**: Budi membuka web faillmov dari HP Android menggunakan koneksi seluler 4G. Dia ingin melanjutkan menonton *Jujutsu Kaisen* Season 2 dari episode 4 yang tadi malam terputus.
* **Aksi & Harapan**: Budi masuk ke halaman profil / watchlist, menekan kartu film yang memiliki indikator progress "S2:E4 - 12:45". Player langsung terbuka, otomatis seek ke menit 12:45, dan ketika episode selesai, player otomatis menyarankan dan memuat Episode 5 tanpa Budi perlu kembali ke halaman katalog detail.

### Skenario 2: Rani & Dimas Nonton Bareng Horor Malam Minggu
* **Konteks**: Rani (di Jakarta) dan Dimas (di Bandung) ingin menonton film *Incantation* bersama malam ini sambil mengobrol di kolom chat.
* **Aksi & Harapan**: Rani membuka halaman detail film, menekan "Buat Watch Party", lalu membagikan kode 6 digit `WP-8923` ke Dimas. Dimas memasukkan kode tersebut di modal Join Room. Ketika Rani menekan tombol Pause untuk mengambil minum, player di HP Dimas ikut ter-pause otomatis dalam waktu seketika (< 1 detik). Saat ada adegan kaget, Dimas bisa mengirimkan reaksi emoji melayang di atas player.

### Skenario 3: Andi Mencari Film dengan Kata Kunci Tidak Lengkap
* **Konteks**: Andi ingin menonton film Marvel tentang Spider-Man tapi lupa judul lengkapnya (*Spider-Man: No Way Home*).
* **Aksi & Harapan**: Andi mengetik "spider no way" di bar pencarian header. Sistem autocomplete menampilkan popup dropdown yang mengurutkan *Spider-Man: No Way Home* di posisi paling atas dibanding film animasi pendek atau video terkait lainnya. Andi mengeklik judul tersebut dan langsung diarahkan ke halaman detail.

---

## 4. Functional Requirements

### 4.1 Browse & Filter Katalog Film
- **Katalog Grid**: Menampilkan daftar poster film (responsive grid 2 kolom di mobile, 4-6 kolom di desktop). Setiap kartu menampilkan poster, judul, rating rata-rata, tahun rilis, dan label tipe (`Movie` / `Series`).
- **Filter Bar**:
  - Filter `Type`: `All`, `Movie`, `Series`.
  - Filter `Genre`: Multi-select / single select (Action, Comedy, Horror, Drama, Animation, dll).
  - Sorting: `Terpopuler` (berdasarkan akumulasi views/watchlist), `Rating Tertinggi`, `Terbaru` (tahun rilis).
- **Pagination**: Menggunakan pagination standar Laravel (`LengthAwarePaginator`) dengan tombol nomor halaman atau AJAX load more untuk performa optimal.

### 4.2 Custom Video Player
- **Standard Controls**: Play/Pause, Scrubber/Seek bar, Time Display (Current / Duration), Volume & Mute control, Playback Speed (0.5x, 1x, 1.25x, 1.5x, 2x), dan Toggle Fullscreen.
- **Season & Episode Selector (Khusus Series)**:
  - Dropdown/Modal di dalam player untuk memilih Season (contoh: Season 1, Season 2) dan daftar Episode (1 s/d N) lengkap dengan judul episode.
  - Mengubah episode akan langsung mengupdate sumber stream player via AJAX/Alpine state tanpa reload halaman.
- **Proxy Stream Delivery (`/moviebox/proxy-stream`)**:
  - Semua URL video dikirim melalui proxy backend Laravel untuk menyembunyikan URL asli MovieBox, menambah header `Referer` yang diperlukan CDN, serta mensupport HTTP Range Requests (Response status `206 Partial Content`) untuk smooth seeking.
- **Subtitle System**:
  - Dropdown pemilih track Subtitle (Bahasa Indonesia, English, Disable).
  - Subtitle ditarik via endpoint proxy backend (`/moviebox/proxy-subtitle`) dari API MovieBox dan di-cache di backend (Redis/Storage TTL 24 jam) untuk efisiensi transfer data.
  - Mendukung upload / parser file subtitle `.vtt` / `.srt` lokal.

### 4.3 Search dengan Relevance Matching
- **Autocomplete Instant**: Panggilan API pencarian dipicu saat pengguna mengetik minimal 2 karakter (dengan debounce 300ms).
- **Relevance Scoring Engine**:
  - Menggunakan urutan prioritas skor match di database SQL / Query Builder:
    1. Match persis judul utama (`exact title match`) -> Skor tertinggi.
    2. Judul yang diawali kata kunci (`starts_with`) -> Skor tinggi.
    3. Kata kunci ada di dalam judul (`contains`) -> Skor sedang.
    4. Kata kunci ada di deskripsi / genre -> Skor dasar.
- **Mobile & Desktop UI**:
  - Mobile: Modal pencarian penuh layar (*full width overlay*) agar nyaman diketik di layar sentuh.
  - Desktop: Dropdown terikat di bawah search bar header.

### 4.4 Watch Party (Nonton Bareng Real-Time)
- **Room Creation & Code**:
  - Pengguna membuat room dari halaman detail film/series. Sistem menghasilkan kode acak 6 karakter alfanumerik unik (misal: `FA-781A`).
- **Real-Time Playback Synchronization**:
  - **Host Actions**: Play, Pause, Seek (pindah menit), Change Episode (untuk series).
  - Setiap aksi Host memicu event real-time (Laravel Reverb / Soketi WebSockets atau fallback polling state `/watch-party/{code}/state`).
  - Penonton (*Guest*) yang tertinggal > 2 detik dari Host akan otomatis disesuaikan (`video.currentTime = hostTime`).
- **Chat & Reaksi**:
  - Panel chat teks real-time di samping/bawah player.
  - Tombol emoji cepat (😱, 😂, 🔥, ❤️) yang menampilkan animasi emoji melayang di atas layar video player penonton lain.
- **Guest Access & Custom Nickname**:
  - Pengguna yang belum login (Guest) diizinkan bergabung ke room Watch Party tanpa harus registrasi akun.
  - Guest diberikan modal awal untuk menentukan dan mengganti *nickname* tampilan mereka di room chat dan daftar participant.
- **Edge Cases & Failure Recovery**:
  - Jika koneksi WebSockets terputus atau server Reverb/Soketi down, sistem akan langsung menampilkan **Notifikasi/Pesan Terputus** ("Koneksi Ke Server Terputus") pada UI room beserta indikator retry reconnect.
  - Maksimal 10 participant per room (untuk mencegah kelebihan beban socket/server).
  - Jika Host disconnect/keluar, room otomatis mentransfer hak Host ke participant berikutnya yang aktif, atau membekukan kontrol sampai Host reconnect dalam batas waktu 2 menit.

### 4.5 Review & Rating
- **Input Ulasan**: Form review hanya dapat diakses oleh user yang sudah terautentikasi (login).
- **Aturan Rating**: Rating berupa angka 1.0 s/d 5.0 (bisa bintang penuh atau setengah).
- **Unique Constraint**: 1 user hanya dapat memberikan 1 review per film/series. Jika user mengirim review ulang, sistem akan memperbarui review lama (*upsert*).
- **Agregasi Skor**: Mengkalkulasi ulang rerata skor film secara otomatis saat review ditambahkan/dihapus dan menyimpannya di kolom `rating` tabel `films`.

### 4.6 Watchlist & Riwayat Tontonan
- **Watchlist**:
  - Tombol toggle "Tambah ke Watchlist" / "Hapus dari Watchlist" via AJAX tanpa reload halaman.
  - Daftar watchlist ditampilkan di halaman Profile pengguna.
- **Riwayat Tontonan (`watch_histories`)**:
  - Player mengirim heartbeat interval (tiap 10 detik) berisi `current_position_seconds` ke endpoint `/watch-history/progress`.
  - Jika tayangan sudah mencapai > 90% durasi total, status ditandai sebagai `completed`.

---

## 5. Technical Considerations

### Tech Stack & Dependencies
- **Framework**: Laravel 11.x (PHP 8.2+)
- **Database**: MySQL 8.0 / MariaDB (Menggunakan Fulltext Index / `CASE WHEN` scoring pada `title` & `genre`)
- **Frontend Layer**: Blade Templating, Alpine.js v3 (untuk reactive player UI & socket listener), Tailwind CSS v3
- **Real-Time Infrastructure**: Laravel Reverb / Soketi (WebSockets) dengan fallback AJAX Long-Polling jika koneksi soket terputus.
- **Caching**: Redis (Cache URL proxy stream MovieBox selama TTL 2 jam untuk mengurangi panggilan API eksternal).

### Database Schema Requirements (Ringkasan Core Model)
1. `films` (`id`, `title`, `slug`, `type` [movie/series], `rating`, `release_year`, `poster_url`, `moviebox_id`, `created_at`, `updated_at`)
2. `episodes` (`id`, `film_id`, `season_number`, `episode_number`, `title`, `stream_url`, `moviebox_episode_id`)
3. `reviews` (`id`, `user_id`, `film_id`, `rating`, `comment`, `created_at`, `updated_at`)
4. `watchlists` (`id`, `user_id`, `film_id`, `created_at`)
5. `watch_histories` (`id`, `user_id`, `film_id`, `episode_id`, `last_position_seconds`, `is_completed`, `updated_at`)
6. `watch_parties` (`id`, `room_code`, `film_id`, `host_id`, `episode_id`, `is_playing`, `current_position_seconds`, `is_locked`, `created_at`)
7. `watch_party_participants` (`id`, `watch_party_id`, `user_id`, `socket_id`, `is_muted`, `last_active_at`)

### Performance & Security Concerns
- **Stream Proxy Bottleneck**: Streaming video via Laravel controller (`php artisan serve` atau single-process PHP) dapat menghabiskan I/O thread. Harus dipastikan proxy menggunakan `fpassthru` / `StreamResponse` dengan buffer chunk kecil (8KB-64KB) serta dukungan `HTTP 206 Partial Content`.
- **CORS & Referer Spoofing**: Backend proxy wajib menyuntikkan header `Referer` asli MovieBox ke API eksternal dan melarang hotlinking direct URL stream dari domain luar.

---

## 6. Out of Scope

Fasilitas berikut **secara sengaja tidak dikerjakan** pada fase pengembangan ini:
1. **WebRTC Voice / Video Call**: Tidak ada fitur obrolan suara atau video langsung di dalam Watch Party (hanya obrolan teks dan emoji reaksi).
2. **Payment Gateway / Monetisasi Subskripsi**: Seluruh katalog film dan pemutaran bersifat gratis tanpa fitur paywall atau billing.
3. **Offline Video Download**: Tidak mendukung pengunduhan file video ke penyimpanan lokal pengguna untuk diputar secara offline.
4. **Custom Subtitle Sync Offset Adjuster**: Tidak ada slider manual untuk menggeser waktu subtitle (misal +1.5s delay subtitle) di dalam player.

---

## 7. Decided Architecture & Closed Questions

### 1. Watch Party Access untuk Guest (User Belum Login)
- **Keputusan**: **Diizinkan (Boleh Guest)**. Pengguna tanpa akun dapat bergabung ke room Watch Party dan secara bebas mengganti *nickname* tampilannya.
- **Implementasi**: Nickname disimpan pada session / `localStorage` guest dan dicatat di tabel `watch_party_participants` (dengan `user_id` null).

### 2. Penanganan Disconnection Server WebSockets
- **Keputusan**: **Tampilkan Pesan Terputus**. Jika service Laravel Reverb / Soketi mengalami gangguan atau koneksi soket terputus:
- **Implementasi**: UI Watch Party akan menampilkan banner/modal peringatan "Koneksi Terputus dari Server Nobar" dengan tombol *Coba Hubungkan Ulang* (Manual Reconnect) alih-alih polling tersembunyi.

### 3. Strategi Subtitle (Proxy & Caching)
- **Keputusan**: **Via Proxy API + Backend Cache**.
- **Implementasi**: Subtitle ditarik dari API MovieBox melalui endpoint `/moviebox/proxy-subtitle` dan hasil konversinya di-cache di Redis/file storage backend dengan TTL (misal 24 jam) agar response subtitle instan dan meminimalkan beban request eksternal.
