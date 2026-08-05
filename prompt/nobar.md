Buatkan fitur "Nonton Bareng" (Watch Party) untuk web film Laravel saya, 
dengan spesifikasi berikut:

1. KONSEP ROOM
   - Satu user (Host) membuat room nonton bareng untuk film/episode 
     tertentu → sistem generate room_code unik (misal 6 karakter) 
     dan invite_link (misal /watch-party/{room_code})
   - User lain join lewat link/kode tersebut TANPA perlu add friend 
     atau follow dulu — cukup ada link, bisa masuk (boleh required 
     login biar ada nama/identitas di chat, tapi tidak perlu 
     relasi pertemanan)
   - Room punya status: waiting (nunggu semua siap), playing, ended

2. SINKRONISASI VIDEO (HOST-DRIVEN)
   - Host adalah SOURCE OF TRUTH untuk playback state (play, pause, 
     seek position, playback speed)
   - Semua action host (play/pause/seek) di-broadcast real-time ke 
     semua participant, dan video player mereka otomatis ikut 
     menyesuaikan
   - Participant TIDAK bisa kontrol play/pause/seek sendiri (kontrol 
     video di-disable/hidden untuk non-host, atau kalau mereka klik 
     tetap ke-override oleh state host)
   - Handle late joiner — user yang baru join harus langsung sync ke 
     posisi video host saat itu juga (bukan mulai dari 0)
   - Handle host disconnect — apa yang terjadi (pause otomatis? transfer 
     host ke participant lain? kasih rekomendasi)

3. REAL-TIME COMMUNICATION
   - Pakai Laravel Reverb / Pusher / WebSocket untuk broadcast:
     - Playback event (play, pause, seek) dari host ke semua participant
     - Chat message real-time
     - Reaction emoji real-time (muncul floating animation di layar, 
       seperti reaction di Instagram Live/Zoom)
   - Buatkan channel/event structure-nya (private channel per room_code)

4. LIVE CHAT
   - Chat box di samping/bawah video player, muncul nama user + pesan
   - Auto-scroll ke pesan terbaru
   - Tampilkan notifikasi "X bergabung ke room" / "X keluar" di chat 
     sebagai system message

5. REACTION EMOJI
   - Beberapa emoji preset (😂 ❤️ 😮 👏 dll) yang bisa diklik user 
     kapan saja saat nonton
   - Muncul sebagai floating animation yang naik ke atas layar video 
     (mirip TikTok Live), lalu hilang otomatis
   - Tidak perlu disimpan ke database (ephemeral, cukup broadcast event)

6. STRUKTUR DATABASE
   - Tabel `watch_parties`: id, room_code (unique), film_id, episode_id 
     (nullable, kalau series), host_user_id, status, current_timestamp 
     (posisi video terakhir), created_at
   - Tabel `watch_party_participants`: id, watch_party_id, user_id 
     (nullable kalau guest), guest_name (nullable), joined_at, left_at
   - Tabel `watch_party_messages` (opsional, kalau chat mau tersimpan): 
     id, watch_party_id, user_id/guest_name, message, created_at

7. UI/UX
   - Halaman watch party = video player (custom player yang sudah dibuat 
     sebelumnya) + sidebar chat + tombol reaction + list participant 
     (avatar kecil, siapa aja yang lagi nonton)
   - Badge "HOST" di sebelah nama host di list participant
   - Tombol "Copy Invite Link" yang gampang di-share

8. TEKNIS
   - Jelaskan setup Laravel Reverb dari awal (instalasi, konfigurasi 
     broadcasting, event class, listener di frontend pakai Laravel Echo)
   - Buatkan Event classes: PlaybackUpdated, MessageSent, ReactionSent, 
     ParticipantJoined/Left
   - Frontend pakai Alpine.js + Laravel Echo untuk listen event dan 
     update video player secara real-time

Berikan kode lengkap: migration, model, event/broadcasting, controller, 
route, dan Blade/Alpine.js component untuk halaman watch party-nya. 
Sesuaikan dengan video player custom yang sudah ada.