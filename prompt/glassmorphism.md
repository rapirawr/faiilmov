Redesign UI CineStream ini menggunakan style Glassmorphism yang konsisten 
di seluruh komponen. Ketentuan:

1. GLASS PANEL EFFECT
   - Semua card, navbar, form filter, dan modal pakai:
     background semi-transparent (misal rgba warna gelap, opacity 0.4-0.6)
     backdrop-filter: blur(12-20px)
     border 1px solid rgba(255,255,255,0.1) — tipis dan halus
   - Di Tailwind: bg-white/10 backdrop-blur-md border border-white/20

2. LAYER & DEPTH
   - Background utama (di belakang semua glass panel) harus punya visual 
     menarik — gradient mesh lembut, blob warna blur besar, atau foto/poster 
     film blur sebagai backdrop, supaya efek transparansi glass-nya kelihatan
   - Card film tetap solid/tajam (poster-nya), tapi container di sekitarnya 
     (misal wrapper info, overlay rating) pakai glass

3. NAVBAR
   - Navbar jadi floating/sticky dengan background blur transparan, 
     bukan solid dark seperti sekarang
   - Beri sedikit shadow halus di bawahnya biar ada pemisah dari konten

4. HERO SECTION
   - Card info film (judul, rating, tombol) taruh di atas glass panel 
     yang mengambang di atas hero image, bukan langsung nempel di background

5. FORM FILTER
   - Search bar, dropdown genre/tipe/urutan, dan tombol filter semua 
     pakai glass container dengan border tipis translucent
   - Dropdown saat terbuka juga tetap glass, bukan solid dropdown biasa

6. CARD FILM DI GRID
   - Rating badge di pojok poster pakai glass chip (bukan solid dark/kuning)
   - Kalau ada info title di bawah poster, kasih glass strip semi-transparent 
     di atas poster (overlay bawah), bukan background solid terpisah

7. WARNA & KONTRAS
   - Pastikan teks tetap readable di atas glass — pakai text-white atau 
     text-white/90, hindari teks abu gelap yang kontrasnya hilang
   - Aksen warna (border glow tipis, highlight) boleh dipakai untuk 
     menegaskan elemen interaktif (hover state), tapi tetap subtle

8. HINDARI OVER-EFFECT
   - Jangan terlalu banyak layer blur bertumpuk yang bikin lambat/berat 
     — cukup 2 level depth (background blur + panel blur)
   - Border-radius konsisten (misal rounded-xl / rounded-2xl) di semua 
     glass element

Berikan hasil dalam Tailwind CSS / Blade component, dan pastikan tetap 
performant (backdrop-blur bisa berat di beberapa browser, jadi kasih 
fallback atau batasi elemen yang pakai blur).