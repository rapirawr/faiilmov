Buatkan saya custom video player untuk web film, dengan kualitas UX 
setara YouTube, Netflix, dan Apple TV. Gunakan native HTML5 <video> 
sebagai base (bukan iframe embed), dengan custom controls dari nol 
(tanpa default browser controls).

1. CONTROL BAR
   - Play/Pause (toggle icon, animasi smooth)
   - Progress bar / scrubber:
     - Preview thumbnail muncul saat hover (seperti YouTube)
     - Buffer indicator (bagian yang sudah di-load, beda warna dari 
       bagian belum ditonton)
     - Draggable, klik langsung seek ke posisi itu
   - Volume control (icon mute + slider, scroll untuk adjust)
   - Timestamp (current time / total duration)
   - Fullscreen toggle
   - Picture-in-Picture toggle
   - Playback speed (0.5x - 2x, dropdown seperti Netflix/YouTube)
   - Quality selector (kalau ada multiple resolution/source)
   - Skip intro / Next episode button (khusus series, seperti Netflix)

2. INTERAKSI & GESTURE
   - Double click kiri/kanan layar = rewind/forward 10 detik (dengan 
     animasi indicator seperti YouTube)
   - Single click di layar = toggle play/pause
   - Klik & tahan = speed up 2x sementara (seperti TikTok/YouTube Shorts)
   - Keyboard shortcuts: Space (play/pause), Arrow Left/Right (seek), 
     Arrow Up/Down (volume), F (fullscreen), M (mute)
   - Auto-hide controls setelah beberapa detik idle saat playing, 
     muncul lagi saat mouse move

3. LOADING & BUFFERING STATE
   - Skeleton/spinner saat video masih loading
   - Buffering indicator (spinner di tengah) saat network lag, 
     tanpa freeze seluruh UI

4. VISUAL STYLE
   - Overlay gradient tipis di bagian bawah untuk readability control bar 
     di atas video (bukan solid background)
   - Transisi smooth untuk show/hide controls (fade, bukan instant)
   - Progress bar dengan warna aksen brand, thumb/handle yang jelas 
     saat di-drag
   - Responsive — di mobile, tombol lebih besar untuk touch target

5. FITUR TAMBAHAN (OPSIONAL)
   - Subtitle/caption toggle dengan pilihan bahasa
   - "Continue watching" — auto-save posisi terakhir ditonton (localStorage 
     atau ke database via API saat pause/unload)
   - Next episode auto-play countdown (seperti Netflix, muncul 10 detik 
     sebelum video selesai)
   - Mini player saat scroll (video tetap play di pojok layar)

6. TEKNIS
   - Implementasi pakai vanilla JavaScript / Alpine.js (sesuaikan dengan 
     stack Laravel + Blade saya), bukan library player besar seperti 
     Video.js kalau saya ingin full custom control
   - Video source dari <video> tag dengan tag <source> untuk multiple 
     format (mp4, webm) — atau jelaskan cara integrasi HLS.js kalau 
     source saya berupa .m3u8 (untuk adaptive streaming)
   - Struktur kode modular supaya component ini reusable di halaman 
     detail film manapun

Berikan kode lengkap (HTML/Blade + CSS + JS), dengan komentar penjelasan 
di setiap bagian logic penting (event listener, seek calculation, dll).r