<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.8-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black" alt="React">
  <img src="https://img.shields.io/badge/Tailwind-v4-38BDF8?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind">
  <img src="https://img.shields.io/badge/Vite-8.0-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
</p>

<h1 align="center">🎬 Faiilmov</h1>

<p align="center">
  Platform streaming Netflix-style dengan AI-powered search, real-time Watch Party, multi-profile, dan integrasi konten dari MovieBox & Anichin (Dracin).
</p>

---

## 📋 Deskripsi

**Faiilmov** adalah platform streaming full-featured yang dibangun di atas Laravel 13.8 + React 19. Mendukung film, serial TV, dan drama China (Dracin) dengan fitur:

- 🔍 **AI Semantic Search** — powered by NVIDIA NV-Embed-v2 + Llama 3.1
- 🎉 **Watch Party (Nonton Bareng)** — synchronized viewing rooms dengan live chat & reactions
- 👨‍👩‍👧 **Multi-Profile** — Netflix-style sub-profiles dengan parental control & PIN
- 📺 **Proxy Streaming** — auto-refresh stream URLs, multi-subtitle support
- 🛡️ **Content Rating System** — SU / G / PG / 13+ / 16+ / 18+
- 📱 **Mobile API** — REST API lengkap untuk Android/iOS app
- 🤖 **Admin Panel** — full CRUD, script runner, API tester, APK release manager

---

## 🛠️ Tech Stack

### Backend
| Component | Technology |
|-----------|-----------|
| Framework | Laravel 13.8 (PHP 8.3+) |
| Database | MySQL (production) / SQLite (dev) |
| Queue | Database driver |
| Session | Database storage |
| Cache | Database / Redis |
| Broadcasting | Pusher Channels |

### Frontend
| Component | Technology |
|-----------|-----------|
| UI Framework | React 19.2.8 |
| Build Tool | Vite 8.0 |
| Styling | Tailwind CSS v4 |
| Interactivity | Alpine.js 3.15 |
| Animation | Framer Motion |
| Icons | Lucide React |
| Templates | Laravel Blade |

### External APIs & Services
| Service | Purpose |
|---------|---------|
| MovieBox API (aoneroom.com) | Primary streaming content (7 hosts, HMAC-MD5 auth) |
| Anichin API | Chinese drama / Dracin content (16+ sources) |
| NVIDIA AI API | Llama 3.1 8B + NV-Embed-v2 untuk search & recommendations |
| iTunes Music API | Soundtrack retrieval |
| Dicebear Avatars | Default user avatars |
| Pusher Channels | Real-time WebSocket untuk Watch Party |
| Google OAuth | Social login |
| Facebook OAuth | Social login |

---

## 🚀 Setup & Installation

### Prerequisites
- PHP 8.3+
- Composer 2.x
- Node.js LTS
- MySQL 8.0+ (atau SQLite untuk dev)
- Redis (opsional, untuk caching)

### Quick Start

```bash
# 1. Clone repository
git clone <repo-url>
cd faiilmov

# 2. Install semua dependensi + setup otomatis
composer setup

# Composer setup akan:
#   - composer install
#   - cp .env.example .env
#   - php artisan key:generate
#   - php artisan migrate
#   - npm install
#   - npm run build
```

### Konfigurasi .env

Salin `.env.example` ke `.env` lalu isi nilai berikut:

```ini
# === APP ===
APP_NAME=faiilmov
APP_ENV=local
APP_URL=http://localhost

# === DATABASE ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=faiilmov
DB_USERNAME=root
DB_PASSWORD=your_password

# === MAIL (untuk Email Verification & Password Reset) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS=support@faiilmov.my.id
MAIL_FROM_NAME="Faiilmov"

# === NVIDIA AI (Search & Recommendations) ===
# Daftar gratis di: https://build.nvidia.com
NVIDIA_API_KEY=nvapi-xxxxxxxxxxxxxxxxxxxx
NVIDIA_API_URL=https://integrate.api.nvidia.com/v1
NVIDIA_LLM_MODEL=meta/llama-3.1-8b-instruct
NVIDIA_EMBEDDING_MODEL=nvidia/nv-embed-v2

# === MOVIEBOX API ===
MOVIEBOX_SECRET_KEY=your_moviebox_secret_key

# === ANICHIN API ===
ANICHIN_API_URL=https://api.anichin.bio
ANICHIN_PRIV_API_URL=https://priv-api.anichin.bio
ANICHIN_API_KEY=your_anichin_api_key
ANICHIN_PRIV_API_KEY=your_anichin_priv_api_key

# === PUSHER (WebSocket Watch Party) ===
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=ap1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# === SOCIAL LOGIN ===
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback

FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URI=http://localhost/auth/facebook/callback
```

---

## 🖥️ Development

```bash
# Jalankan semua service sekaligus (server + queue + logs + vite)
composer dev

# Atau manual:
php artisan serve          # HTTP server di port 8000
php artisan queue:listen   # Queue worker
npm run dev                # Vite HMR
```

---

## 🧪 Testing

```bash
composer test

# Atau langsung:
php artisan test
php artisan test --filter=PasswordResetTest
php artisan test --filter=SocialAuthTest
```

---

## 🗂️ Struktur Folder Penting

```
app/
├── Console/Commands/
│   ├── SyncActorsCommand.php          # sync:actors
│   ├── GenerateFilmEmbeddings.php     # films:generate-embeddings
│   ├── AssignFilmGenres.php           # films:assign-genres
│   ├── ClassifyFilmActors.php         # films:classify-actors
│   └── VerifySyncCommand.php          # verify:sync
├── Events/
│   ├── PlaybackStateChanged.php       # Broadcast: playback sync
│   ├── WatchPartyMessageSent.php      # Broadcast: chat
│   ├── WatchPartyReactionSent.php     # Broadcast: emoji reactions
│   ├── WatchPartyParticipantJoined.php
│   └── WatchPartyParticipantLeft.php
├── Http/
│   ├── Controllers/                   # Web controllers
│   │   ├── AuthController.php         # Login, register, password reset
│   │   ├── WatchPartyController.php   # Watch party logic
│   │   ├── SocialAuthController.php   # Google/Facebook OAuth
│   │   └── Admin/                     # Admin panel controllers
│   ├── Controllers/Api/
│   │   └── MobileApiController.php    # REST API untuk mobile app
│   └── Middleware/
│       ├── AdminMiddleware.php
│       └── CheckBannedMiddleware.php
├── Models/                            # 22 Eloquent models
│   ├── Film.php                       # Core content entity
│   ├── User.php                       # User account
│   ├── WatchParty.php                 # Watch party rooms
│   └── ...
└── Services/
    ├── MovieBoxService.php            # CDN streaming provider
    ├── AnichinService.php             # Dracin content provider
    ├── NvidiaAiService.php            # AI search & embeddings
    ├── FilmSearchService.php          # Multi-layer search
    └── RecommendationService.php      # Personalized recommendations

resources/
├── js/
│   ├── components/                    # React components
│   │   ├── HeroBannerCarousel.jsx
│   │   ├── FilmCard.jsx
│   │   └── EpisodeSelector.jsx
│   └── echo.js                        # Laravel Echo setup (WebSocket)
└── views/
    ├── auth/                          # Login, register, password reset, verify email
    ├── admin/                         # Admin panel views
    └── *.blade.php                    # Public pages
```

---

## ⚙️ Artisan Commands

| Command | Deskripsi |
|---------|-----------|
| `php artisan sync:actors` | Sync aktor dari TMDB API, auto-fetch photos |
| `php artisan films:generate-embeddings` | Generate NVIDIA AI embeddings untuk semua film |
| `php artisan films:assign-genres` | Auto-assign genre berdasarkan analisis AI |
| `php artisan films:classify-actors` | Klasifikasi aktor sebagai main/regular |
| `php artisan verify:sync` | Verifikasi status MovieBox API sync |
| `php artisan queue:work` | Jalankan queue worker |
| `php artisan reverb:start` | Jalankan WebSocket server (jika pakai Reverb) |

---

## 🚀 Deployment (cPanel)

Project menggunakan `.cpanel.yml` untuk automated deployment via Git.

### Setup Awal di cPanel
```bash
# 1. Clone repo ke folder di luar public_html
# 2. Jalankan setup
composer setup

# 3. Buat storage symlink (via browser atau artisan)
# Buka: https://yourdomain.com/create-storage-link

# 4. Cron job untuk scheduler
* * * * * cd /home/username/faiilmov && php artisan schedule:run >> /dev/null 2>&1
```

### Struktur Deployment
```
/home/faiiller/
├── faiilmov.my.id/
│   ├── public/           # public_html (web root)
│   └── faiilmov-repo/    # Source code (di luar web root)
```

### Environment Production
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://faiilmov.my.id

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

---

## 📡 API Endpoints (Mobile)

Base URL: `/api/v1/`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/login` | Login |
| POST | `/register` | Register |
| GET | `/movies` | List film |
| GET | `/movies/{id}` | Detail film |
| GET | `/search` | Search film |
| GET | `/watchlist` | Watchlist user |
| POST | `/watch-party/create` | Buat Watch Party room |
| GET | `/watch-party/{code}` | Detail room |
| POST | `/watch-party/{code}/join` | Join room |

Lihat [`routes/api.php`](routes/api.php) untuk dokumentasi lengkap.

---

## 🚫 Known Limitations

| Fitur | Status |
|-------|--------|
| Email Verification | ✅ Implemented |
| Password Reset | ✅ Implemented |
| Social Login (Google/Facebook) | ✅ Implemented |
| WebSocket Watch Party | ✅ Pusher-based |
| Video Transcoding | ❌ Bergantung upstream CDN |
| Payment / Subscription | ❌ Belum ada |
| Offline Download | ❌ Belum ada |
| Two-Factor Authentication | ❌ Belum ada |
| Multi-language UI | ❌ Hanya Bahasa Indonesia |

---

## 🗺️ Roadmap

- [ ] Payment integration (subscription tiers)
- [ ] Multi-language UI support
- [ ] Chromecast / AirPlay support
- [ ] Two-factor authentication
- [ ] User-generated playlists
- [ ] Progressive Web App (PWA)
- [ ] Advanced analytics dashboard
- [ ] Redis caching untuk performa
- [ ] CDN untuk static assets

---

## 📞 Kontak

**Maintainer:** CodeArc  
**Domain:** faiilmov.my.id  
**Email:** support@faiilmov.my.id

---

*Built with ❤️ using Laravel + React*
