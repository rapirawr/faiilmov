Tambahkan fitur Season & Episode Selector di halaman Watch/Detail untuk 
film bertipe "series" (subject_type = 'series'). Ketentuan:

1. STRUKTUR DATABASE TAMBAHAN
   - Buat tabel `seasons`:
     id, film_id (FK), season_number (integer), title (nullable, 
     misal "Season 1"), poster_url (nullable), release_year (nullable), 
     timestamps
   - Buat tabel `episodes`:
     id, season_id (FK), episode_number (integer), title, synopsis 
     (nullable), duration_minutes, thumbnail_url (nullable), 
     video_source (url/path video episode ini), timestamps
   - Relasi: Film hasMany Seasons, Season hasMany Episodes
   - Tambahkan unique constraint (film_id, season_number) dan 
     (season_id, episode_number) supaya tidak duplikat

2. LOGIKA DI HALAMAN WATCH
   - Kalau film->subject_type == 'movie' → tampilkan video player 
     langsung dengan 1 source
   - Kalau film->subject_type == 'series' → tampilkan:
     a. Tab/dropdown pemilihan Season (misal "Season 1", "Season 2")
     b. List episode di season yang dipilih (dengan thumbnail, nomor 
        episode, judul, durasi)
     c. Episode yang sedang ditonton di-highlight/ditandai aktif
     d. Klik episode lain → ganti video source player tanpa reload 
        halaman penuh (pakai AJAX/Livewire, update URL pakai history.pushState)

3. UI/UX EPISODE LIST
   - Layout mirip Netflix: list vertical di bawah/samping video player, 
     tiap episode card berisi thumbnail, nomor + judul episode, durasi, 
     progress bar kecil kalau sudah pernah ditonton sebagian
   - Season selector berupa dropdown atau horizontal tab kalau season 
     banyak
   - Auto-scroll ke episode yang sedang aktif saat halaman pertama load

4. FITUR TAMBAHAN
   - Tombol "Episode Selanjutnya" di video player (lanjut otomatis ke 
     episode+1 di season yang sama, atau season+1 episode 1 kalau 
     episode terakhir di season itu)
   - Simpan "last watched episode" per user per series (tabel terpisah 
     atau kolom di watchlist) supaya waktu buka series lagi, langsung 
     lanjut dari episode terakhir

5. ROUTING
   - URL harus reflect season & episode yang aktif, misal:
     /watch/{film:slug}?season=2&episode=5
     supaya bisa di-bookmark/share langsung ke episode tertentu
   - Kalau parameter season/episode tidak ada di URL → default ke 
     season 1 episode 1 (atau last watched kalau user login)

6. CONTROLLER & MODEL
   - Update `MovieDetailController` atau buat method baru untuk handle 
     query season+episode
   - Tambahkan relasi & accessor di model Film/Season/Episode yang 
     diperlukan (misal getNextEpisodeAttribute())

Berikan kode lengkap: migration, model + relasi, controller, route, 
dan Blade/Alpine.js component untuk season-episode selector-nya. 
Sesuaikan dengan struktur video player custom yang sudah dibuat 
sebelumnya.