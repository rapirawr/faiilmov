Optimasi fitur search film saya di Laravel supaya lebih relevan, cepat, 
dan user-friendly — bukan cuma LIKE '%query%' biasa. Tolong bantu:

1. RELEVANSI HASIL PENCARIAN
   - Search harus match title, tapi juga alternative title/original 
     title (kalau ada), nama aktor, dan nama sutradara/director
   - Ranking hasil berdasarkan relevansi: exact match > starts with 
     > contains, bukan urutan random/id
   - Typo tolerance — kalau user salah ketik dikit (misal "spidermen" 
     tetap ketemu "spiderman"), pakai fuzzy matching / Levenshtein 
     atau full-text search
   - Pertimbangkan pakai Laravel Scout + driver (Meilisearch/Typesense) 
     kalau data film cukup banyak (500+), atau cukup optimasi query 
     manual kalau data masih kecil — jelaskan kapan harus upgrade

2. AUTOCOMPLETE / SEARCH SUGGESTION
   - Saat user ketik di search bar, muncul dropdown suggestion real-time 
     (debounce request, jangan fetch tiap keystroke)
   - Suggestion tampilkan thumbnail kecil + judul + tahun, biar user 
     bisa langsung klik tanpa submit form
   - Highlight bagian teks yang match dengan query (bold/underline)

3. FILTER KOMBINASI DENGAN SEARCH
   - Search query harus tetap bisa dikombinasi dengan filter genre, 
     tahun, rating yang sudah ada (bukan search yang reset semua filter)
   - Query string di URL harus reflect semua parameter (search + filter) 
     supaya bisa di-share/bookmark

4. HANDLING EDGE CASES
   - Search kosong / hasil tidak ditemukan → tampilkan pesan yang 
     membantu (misal "Tidak ditemukan, coba kata kunci lain" + 
     saran film populer)
   - Search dengan kata umum (misal "the", "a") → jangan return 
     ribuan hasil tidak relevan, kasih minimum character length
   - Sanitize input untuk mencegah SQL injection (meski pakai Eloquent, 
     tetap validasi)

5. PERFORMA
   - Index database yang tepat untuk kolom yang di-search (title, 
     dan full-text index kalau pakai MySQL FULLTEXT)
   - Cache hasil search yang sering dicari (misal pakai Redis) untuk 
     query populer
   - Pagination tetap jalan normal saat kombinasi search + filter

6. TRACKING (OPSIONAL)
   - Simpan log search query user (tabel search_logs) untuk analisa 
     kata kunci yang sering dicari tapi hasilnya kosong — berguna 
     untuk tahu film apa yang harus ditambah ke database

Kasih saya:
- Kode controller/query builder untuk search yang relevan
- Kode untuk autocomplete endpoint (API + JS/Alpine.js untuk frontend)
- Rekomendasi index database yang perlu ditambahkan
- Kalau perlu Laravel Scout, kasih langkah setup-nya juga

Data saya sekarang masih di database MySQL lokal (bukan API eksternal), 
jadi optimasi murni di sisi search lokal.