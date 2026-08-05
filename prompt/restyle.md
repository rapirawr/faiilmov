Saya punya web film (faiimov) yang sekarang terlihat generic "AI-generated" — 
didominasi gradient ungu-biru di navbar, hero section, badge, dan tombol. 
Tolong redesign ulang dengan prinsip berikut:

1. HAPUS SEMUA GRADIENT
   - Ganti background gradient dengan warna solid/flat
   - Tombol pakai warna solid, bukan gradient purple-to-blue
   - Badge "FEATURED SPOTLIGHT" ganti jadi style flat, bukan pill ungu glow

2. WARNA & IDENTITAS
   - Gunakan warna soft blue
   - Base warna gelap tapi jangan hitam pekat polos — pakai dark slate/charcoal 
     dengan sedikit warm/cool tint biar ada karakter
   - Batasi 1 warna aksen + 1-2 warna netral, jangan multi-gradient rainbow

3. TIPOGRAFI
   - Judul film (Mirzapur) ganti font — jangan default bold sans generic, 
     coba font display/serif yang lebih ada karakter editorial (misal Fraunces, 
     Playfair Display, atau font condensed bold ala poster bioskop)
   - Perbesar kontras ukuran antara heading dan body text (jangan medium-medium semua)

4. HERO SECTION
   - Hero image sekarang gelap gradasi ke hitam generic — coba pakai overlay 
     solid dengan opacity, atau duotone effect, bukan gradient fade halus
   - Layout hero jangan center-left biasa — coba asymmetric, atau split layout 
     dengan info film di card terpisah

5. CARD FILM / GRID KATALOG
   - Rating badge (bintang kuning pojok) ganti jadi lebih minimal — jangan 
     rounded pill dengan background gelap standar, coba corner-flag style 
     atau overlay bawah poster
   - Hilangkan shadow/glow ungu di sekitar card, ganti border tipis solid 
     atau tanpa border sama sekali dengan spacing yang tegas

6. FORM FILTER
   - Dropdown & input sekarang generic dark UI kit style — kasih border-radius 
     lebih kecil/tegas (bukan full rounded), dan warna border yang lebih terlihat
   - Tombol "Filter" ganti dari gradient ungu jadi solid warna aksen

7. REFERENSI STYLE
   - Ambil inspirasi dari desain editorial film seperti Letterboxd, MUBI, 
     atau poster bioskop klasik — bukan generic SaaS dashboard dark mode

Tolong berikan hasil dalam bentuk kode Tailwind CSS / Blade component, 
dan jelaskan alasan tiap perubahan warna & tipografi yang dipilih.