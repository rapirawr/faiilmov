Search film saya sekarang menampilkan hasil yang tidak relevan. Contoh: 
saat user cari "naruto", hasil yang muncul termasuk video yang sama 
sekali tidak berhubungan (video random, wrestling, dll) — padahal 
seharusnya hanya menampilkan film/series yang benar-benar match dengan 
kata kunci "naruto" di title-nya (Naruto, Naruto Shippuden, Boruto, 
The Last: Naruto the Movie, dll).

Kemungkinan penyebab: query search saya menggunakan LIKE '%kata%' yang 
terlalu longgar, atau ikut men-search ke kolom lain yang tidak relevan 
(misal deskripsi/tags) sehingga match parsial yang salah ikut kebawa. 
Tolong bantu:

1. AUDIT QUERY SEARCH SAAT INI
   - Cek kolom apa saja yang di-include dalam WHERE clause search 
     (title only, atau ikut synopsis/tags/genre name?)
   - Kalau search ikut mencocokkan ke kolom deskripsi/tags, itu bisa 
     jadi penyebab hasil melenceng — jelaskan kenapa dan gimana 
     membatasinya

2. PERBAIKI RELEVANCE MATCHING
   - Search harus prioritas match di title film (dan alternative_title 
     kalau ada), bukan full-text ke semua kolom sekaligus
   - Kalau perlu search ke genre/aktor juga, pisahkan logic-nya — 
     jangan digabung jadi satu LIKE besar yang bikin noise
   - Urutkan hasil: exact match title > starts with > contains title, 
     supaya yang paling relevan muncul duluan

3. VALIDASI SUMBER DATA
   - Kalau data film di local database berasal dari sync/scrape API 
     eksternal, cek apakah ada data yang salah kategori/title corrupt 
     saat proses sync (misal title kosong/typo yang bikin false match)
   - Kasih saran validasi data sebelum insert ke database (skip record 
     yang title-nya tidak jelas/kosong)

4. TEST CASE
   - Buatkan saya query search untuk kata kunci "naruto" dan pastikan 
     hasilnya HANYA film/series yang title-nya mengandung "naruto" 
     (Naruto, Naruto Shippuden, Boruto: Naruto Next Generations, 
     The Last: Naruto the Movie), tidak ada hasil di luar itu

5. TAMBAHAN
   - Kalau saya pakai Laravel Scout/Meilisearch, jelaskan juga 
     bagaimana konfigurasi searchable columns dan ranking rules 
     supaya tidak ikut nge-match ke field yang salah

Tunjukkan kode query builder (Eloquent) versi sebelum vs sesudah 
perbaikan, supaya saya paham bedanya.