<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Actor;
use App\Models\AdminActivityLog;
use App\Models\AppLaunchNotification;
use App\Models\Changelog;
use App\Models\Episode;
use App\Models\Film;
use App\Models\Genre;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\SearchLog;
use App\Models\Season;
use App\Models\Setting;
use App\Models\User;
use App\Models\WatchHistory;
use App\Models\WatchParty;
use App\Models\WatchPartyMessage;
use App\Models\WatchPartyParticipant;
use App\Models\WatchPartyReaction;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileApiController extends Controller
{
    // ==========================================
    // AUTHENTICATION & USER ACCOUNT ENDPOINTS
    // ==========================================

    public function login(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?? 'Email atau password tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        $token = base64_encode($user->id . '|' . Str::random(40));

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->is_admin ? 'admin' : 'user',
            ],
        ]);
    }

    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?? 'Pendaftaran gagal. Periksa data Anda.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = base64_encode($user->id . '|' . Str::random(40));

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'user',
            ],
        ], 201);
    }

    public function user(Request $request)
    {
        $user = $this->resolveUser($request);

        $watchlistCount = 0;
        $historyCount = 0;
        try {
            $watchlistCount = Watchlist::where('user_id', $user->id)->count();
            $historyCount = WatchHistory::where('user_id', $user->id)->count();
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'bio' => $user->bio,
                'phone' => $user->phone,
                'is_admin' => (bool) ($user->is_admin ?? false),
                'watchlist_count' => $watchlistCount,
                'history_count' => $historyCount,
                'review_count' => Review::where('user_id', $user->id)->count(),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->resolveUser($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar_file' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:25600',
            'avatar' => 'nullable|string|max:1000',
            'bio' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
        ]);

        $avatarUrl = $request->avatar;

        if ($request->hasFile('avatar_file')) {
            $avatarUrl = $this->compressAndSaveAvatar($request->file('avatar_file'));
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'avatar' => $avatarUrl,
            'bio' => $request->bio,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'bio' => $user->bio,
                'phone' => $user->phone,
            ],
        ]);
    }

    private function compressAndSaveAvatar($file): string
    {
        $filename = 'avatars/' . Str::random(40) . '.jpg';
        $fullPath = storage_path('app/public/' . $filename);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        if (extension_loaded('gd')) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo) {
                $mime = $imageInfo['mime'];
                $sourceImage = match ($mime) {
                    'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                    'image/png'  => @imagecreatefrompng($file->getRealPath()),
                    'image/webp' => @imagecreatefromwebp($file->getRealPath()),
                    'image/gif'  => @imagecreatefromgif($file->getRealPath()),
                    default      => null,
                };

                if ($sourceImage) {
                    $origWidth = imagesx($sourceImage);
                    $origHeight = imagesy($sourceImage);
                    $maxSize = 600;

                    if ($origWidth > $maxSize || $origHeight > $maxSize) {
                        $ratio = min($maxSize / $origWidth, $maxSize / $origHeight);
                        $newWidth = (int) round($origWidth * $ratio);
                        $newHeight = (int) round($origHeight * $ratio);

                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                        imagedestroy($sourceImage);
                        $sourceImage = $resized;
                    }

                    imagejpeg($sourceImage, $fullPath, 82);
                    imagedestroy($sourceImage);
                    return asset('storage/' . $filename);
                }
            }
        }

        $path = $file->store('avatars', 'public');
        return asset('storage/' . $path);
    }

    public function changePassword(Request $request)
    {
        $user = $this->resolveUser($request);

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini salah.',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $this->resolveUser($request);

        $request->validate([
            'confirm_password' => 'required',
        ]);

        if (!Hash::check($request->confirm_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi konfirmasi salah.',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dihapus permanen',
        ]);
    }

    public function getUserReviews(Request $request)
    {
        $user = $this->resolveUser($request);
        $reviews = Review::with('film')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $formatted = $reviews->map(function ($r) {
            if (!$r->film) return null;
            return [
                'id' => $r->id,
                'film_id' => $r->film_id,
                'film_title' => $r->film->title,
                'poster_url' => $r->film->poster_url,
                'rating' => (float)$r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->format('d M Y, H:i'),
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    public function deleteUserReview($id, Request $request)
    {
        $user = $this->resolveUser($request);
        Review::where('user_id', $user->id)->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dihapus',
        ]);
    }

    public function clearWatchHistory(Request $request)
    {
        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        $query = WatchHistory::where('user_id', $user->id);
        if ($profileId) {
            $query->where('profile_id', $profileId);
        } else {
            $query->whereNull('profile_id');
        }
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seluruh riwayat tontonan berhasil dibersihkan',
        ]);
    }

    public function clearWatchlist(Request $request)
    {
        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        $query = Watchlist::where('user_id', $user->id);
        if ($profileId) {
            $query->where('profile_id', $profileId);
        } else {
            $query->whereNull('profile_id');
        }
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seluruh watchlist berhasil dibersihkan',
        ]);
    }

    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar',
        ]);
    }

    // ==========================================
    // CATALOG & MEDIA ENDPOINTS
    // ==========================================

    public function movies(Request $request)
    {
        $query = Film::with('genres');

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('subject_type', $request->type);
        }

        if ($request->has('genre') && $request->genre !== 'All') {
            $genreName = $request->genre;
            $query->whereHas('genres', function ($q) use ($genreName) {
                $q->where('name', $genreName);
            });
        }

        $limit = (int)$request->query('limit', 30);
        $films = $query->latest()->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $films->map(fn($film) => $this->formatFilm($film)),
        ]);
    }

    public function featured()
    {
        $featuredIds = json_decode(\App\Models\Setting::get('featured_film_ids', '[]'), true) ?: [];
        if (!empty($featuredIds)) {
            $ids = array_map('intval', $featuredIds);
            $films = Film::with('genres')->whereIn('id', $ids)->get();
            if ($films->isNotEmpty()) {
                $films = $films->sortBy(function ($film) use ($ids) {
                    return array_search($film->id, $ids);
                })->values();
            } else {
                $films = Film::with('genres')->whereNotNull('backdrop_url')->orderBy('rating', 'desc')->limit(6)->get();
            }
        } else {
            $films = Film::with('genres')->whereNotNull('backdrop_url')->orderBy('rating', 'desc')->limit(6)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $films->map(fn($film) => $this->formatFilm($film)),
        ]);
    }

    public function banners()
    {
        $featuredIds = json_decode(\App\Models\Setting::get('featured_film_ids', '[]'), true) ?: [];
        if (!empty($featuredIds)) {
            $ids = array_map('intval', $featuredIds);
            $films = Film::with('genres')->whereIn('id', $ids)->get();
            if ($films->isNotEmpty()) {
                $films = $films->sortBy(function ($film) use ($ids) {
                    return array_search($film->id, $ids);
                })->values();
            } else {
                $films = Film::with('genres')->whereNotNull('backdrop_url')->orderBy('rating', 'desc')->limit(6)->get();
            }
        } else {
            $films = Film::with('genres')->whereNotNull('backdrop_url')->orderBy('rating', 'desc')->limit(6)->get();
        }

        $banners = $films->map(function ($f) {
            return [
                'id' => $f->id,
                'title' => $f->title,
                'slug' => $f->slug ?? Str::slug($f->title),
                'backdrop_url' => $f->backdrop_url ?: $f->poster_url,
                'poster_url' => $f->poster_url,
                'rating' => (float) ($f->rating ?? 0.0),
                'release_year' => (string) ($f->release_year ?? ''),
                'subject_type' => $f->subject_type ?? 'movie',
                'synopsis' => $f->synopsis ?? '',
                'genres' => $f->genres ? $f->genres->pluck('name')->toArray() : [],
                'genres_string' => $f->genres ? $f->genres->pluck('name')->implode(', ') : '',
                'web_url' => url('/film/' . ($f->slug ?? Str::slug($f->title))),
                'video_url' => $f->moviebox_subject_id 
                    ? url('/moviebox/proxy-stream?id=' . urlencode($f->moviebox_subject_id)) 
                    : ($f->trailer_url ?? ''),
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $banners->count(),
            'data' => $banners,
        ]);
    }

    public function trending()
    {
        $films = Film::with('genres')
            ->where('subject_type', 'movie')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $films->map(fn($film) => $this->formatFilm($film)),
        ]);
    }

    public function popularSeries()
    {
        $films = Film::with('genres')
            ->where('subject_type', 'series')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $films->map(fn($film) => $this->formatFilm($film)),
        ]);
    }

    public function becauseYouWatched(Request $request)
    {
        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        $query = WatchHistory::with('film.genres')->where('user_id', $user->id);
        if ($profileId) {
            $query->where('profile_id', $profileId);
        } else {
            $query->whereNull('profile_id');
        }
        $lastWatched = $query->latest('updated_at')->first();

        if (!$lastWatched || !$lastWatched->film) {
            $recommendations = Film::with('genres')->inRandomOrder()->limit(10)->get();
            return response()->json([
                'success' => true,
                'last_watched_title' => null,
                'data' => $recommendations->map(fn($film) => $this->formatFilm($film)),
            ]);
        }

        $lastFilm = $lastWatched->film;
        $genreIds = $lastFilm->genres->pluck('id')->toArray();

        $recommendations = Film::with('genres')
            ->where('id', '!=', $lastFilm->id)
            ->whereHas('genres', function ($q) use ($genreIds) {
                $q->whereIn('genres.id', $genreIds);
            })
            ->latest()
            ->limit(10)
            ->get();

        if ($recommendations->isEmpty()) {
            $recommendations = Film::with('genres')
                ->where('id', '!=', $lastFilm->id)
                ->inRandomOrder()
                ->limit(10)
                ->get();
        }

        return response()->json([
            'success' => true,
            'last_watched_title' => $lastFilm->title,
            'data' => $recommendations->map(fn($film) => $this->formatFilm($film)),
    public function getAvatars()
    {
        $styles = [
            ['id' => 'avataaars-neutral', 'label' => 'Avataaars', 'emoji' => '🧑', 'api_style' => 'avataaars-neutral'],
            ['id' => 'adventurer-neutral', 'label' => 'Adventurer', 'emoji' => '🧙', 'api_style' => 'adventurer-neutral'],
            ['id' => 'bottts-neutral', 'label' => 'Bottts', 'emoji' => '🤖', 'api_style' => 'bottts-neutral'],
            ['id' => 'blobs', 'label' => 'Blobs', 'emoji' => '🫧', 'api_style' => 'blobs'],
            ['id' => 'clay', 'label' => 'Clay', 'emoji' => '🏺', 'api_style' => 'clay'],
            ['id' => 'fun-emoji', 'label' => 'Fun Emoji', 'emoji' => '😄', 'api_style' => 'fun-emoji'],
        ];

        $seeds = ['Felix','Luna','Mochi','Jasper','Zara','Echo','Orion','Nova','Sable','Atlas','Ember','Sage','Flynn','Lyra','Rune','Cleo','Onyx','Iris','Finn','Halo','Mira','Dax','Wren','Skye','Bex','Juno','Loki','Nyx','Cove','Ash','Storm','Vale','Rex','Zoe','Kai','Rue','Vex','Mox','Pax','Sol'];

        return response()->json([
            'success' => true,
            'styles' => $styles,
            'seeds' => $seeds,
            'meta' => [
                'total_seeds' => count($seeds),
                'total_styles' => count($styles),
                'provider' => 'DiceBear',
            ],
        ]);
    }

    public function showMovie($id, Request $request)
    {
        $film = Film::with(['genres', 'actors', 'reviews.user'])->findOrFail($id);
        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        $data = $this->formatFilm($film, true);

        // Check if in watchlist for this specific profile or main account
        $wlQuery = Watchlist::where('user_id', $user->id)->where('film_id', $id);
        if ($profileId) {
            $wlQuery->where('profile_id', $profileId);
        } else {
            $wlQuery->whereNull('profile_id');
        }
        $data['is_in_watchlist'] = $wlQuery->exists();

        // Check watch history for this specific profile or main account
        $whQuery = WatchHistory::where('user_id', $user->id)->where('film_id', $id);
        if ($profileId) {
            $whQuery->where('profile_id', $profileId);
        } else {
            $whQuery->whereNull('profile_id');
        }
        $history = $whQuery->first();
        $data['progress_seconds'] = $history->progress_seconds ?? 0;
        $data['completed'] = (bool)($history->completed ?? false);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function genres()
    {
        $genres = Genre::select('id', 'name', 'slug')->get();

        return response()->json([
            'success' => true,
            'data' => $genres,
        ]);
    }

    public function search(Request $request, \App\Services\FilmSearchService $filmSearch)
    {
        $q = $request->query('q', '');
        if (empty(trim($q))) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $paginator = $filmSearch->search($q, [], 30, $request->ip());
        $films = $paginator ? collect($paginator->items()) : collect();

        return response()->json([
            'success' => true,
            'data' => $films->map(fn($film) => $this->formatFilm($film)),
        ]);
    }

    public function browse(Request $request)
    {
        return $this->movies($request);
    }

    // ==========================================
    // WATCHLIST & HISTORY SYNC ENDPOINTS
    // ==========================================

    private function resolveProfileId(Request $request): ?int
    {
        $profileId = $request->header('X-Profile-ID') 
            ?? $request->input('profile_id') 
            ?? $request->query('profile_id');

        if ($profileId !== null && is_numeric($profileId) && (int)$profileId > 0) {
            return (int)$profileId;
        }

        return null;
    }

    public function getWatchlist(Request $request)
    {
        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        $query = Watchlist::with('film.genres')->where('user_id', $user->id);
        if ($profileId) {
            $query->where('profile_id', $profileId);
        } else {
            $query->whereNull('profile_id');
        }

        $watchlists = $query->latest()->get();

        $films = $watchlists->map(function ($item) {
            return $item->film ? $this->formatFilm($item->film) : null;
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $films,
        ]);
    }

    public function addToWatchlist(Request $request)
    {
        $request->validate([
            'film_id' => 'required|exists:films,id',
        ]);

        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);
        $filmId = $request->film_id;

        Watchlist::firstOrCreate([
            'user_id' => $user->id,
            'profile_id' => $profileId,
            'film_id' => $filmId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ditambahkan ke Watchlist',
        ]);
    }

    public function removeFromWatchlist($filmId, Request $request)
    {
        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        $query = Watchlist::where('user_id', $user->id)->where('film_id', $filmId);
        if ($profileId) {
            $query->where('profile_id', $profileId);
        } else {
            $query->whereNull('profile_id');
        }
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dihapus dari Watchlist',
        ]);
    }

    public function getWatchHistory(Request $request)
    {
        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        $query = WatchHistory::with('film.genres')->where('user_id', $user->id);
        if ($profileId) {
            $query->where('profile_id', $profileId);
        } else {
            $query->whereNull('profile_id');
        }

        $history = $query->latest()->get();

        $data = $history->map(function ($item) {
            if (!$item->film) return null;
            $filmData = $this->formatFilm($item->film);
            $filmData['progress_seconds'] = $item->progress_seconds;
            $filmData['completed'] = (bool)$item->completed;
            $filmData['season_number'] = $item->season_number ?? 1;
            $filmData['episode_number'] = $item->episode_number ?? 1;
            $filmData['updated_at_human'] = $item->updated_at ? $item->updated_at->diffForHumans() : '';
            return $filmData;
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function updateWatchHistory(Request $request)
    {
        $request->validate([
            'film_id' => 'required|exists:films,id',
            'progress_seconds' => 'sometimes|integer',
            'completed' => 'sometimes|boolean',
        ]);

        $user = $this->resolveUser($request);
        $profileId = $this->resolveProfileId($request);

        WatchHistory::updateOrCreate(
            ['user_id' => $user->id, 'profile_id' => $profileId, 'film_id' => $request->film_id],
            [
                'progress_seconds' => $request->progress_seconds ?? 0,
                'completed' => $request->completed ?? false,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat tontonan diperbarui',
        ]);
    }

    // ==========================================
    // REVIEWS ENDPOINTS
    // ==========================================

    public function getMovieReviews($id)
    {
        $reviews = Review::with('user')
            ->where('film_id', $id)
            ->latest()
            ->get();

        $formatted = $reviews->map(function ($r) {
            return [
                'id' => $r->id,
                'user_name' => $r->user->name ?? 'User',
                'rating' => (float)$r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    public function postReview($id, Request $request)
    {
        $profileId = $this->resolveProfileId($request);
        if ($profileId !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Sub-akun tidak memiliki izin untuk memberikan ulasan/rating.',
            ], 403);
        }

        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => $request->user()->id,
            'film_id' => $id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update average rating for film
        $film = Film::find($id);
        if ($film) {
            $film->updateAverageRating();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dipublikasikan',
            'data' => $review,
        ], 201);
    }

    // ==========================================
    // FORMATTER HELPER
    // ==========================================

    private function formatFilm(Film $film, bool $includeDetails = false): array
    {
        $data = [
            'id' => $film->id,
            'title' => $film->title,
            'slug' => $film->slug ?? Str::slug($film->title),
            'poster_url' => $film->poster_url,
            'backdrop_url' => $film->backdrop_url,
            'trailer_url' => $film->trailer_url,
            'youtube_embed_url' => $film->youtube_embed_url,
            'overview' => $film->synopsis ?? '',
            'rating' => (float) ($film->rating ?? 0.0),
            'release_year' => (string) ($film->release_year ?? ''),
            'type' => $film->subject_type ?? 'movie',
            'genres' => $film->genres ? $film->genres->pluck('name')->toArray() : [],
            'video_url' => $film->moviebox_subject_id 
                ? url('/moviebox/proxy-stream?id=' . urlencode($film->moviebox_subject_id)) 
                : ($film->trailer_url ?? ''),
        ];

        if ($includeDetails) {
            $data['cast'] = $film->actors ? $film->actors->pluck('name')->toArray() : [];
            $data['duration_minutes'] = $film->duration_minutes;
            $data['max_resolution'] = $film->max_resolution;
        }

        return $data;
    }

    private function resolveUser(Request $request): User
    {
        $user = $request->user();
        if ($user) return $user;

        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $decoded = base64_decode($token);
            if (str_contains($decoded, '|')) {
                $userId = explode('|', $decoded)[0];
                $found = User::find($userId);
                if ($found) return $found;
            }
        }

        return User::first() ?? User::create([
            'name' => 'Demo User',
            'email' => 'support@faiilmov.my.id',
            'password' => Hash::make('password123'),
        ]);
    }

    // ==========================================
    // SEASONS & EPISODES ENDPOINTS
    // ==========================================

    public function getMovieSeasons($id)
    {
        $film = Film::with(['seasons.episodes'])->findOrFail($id);

        $seasons = $film->seasons->map(function ($season) {
            return [
                'id' => $season->id,
                'film_id' => $season->film_id,
                'season_number' => $season->season_number,
                'title' => $season->title ?? ('Season ' . $season->season_number),
                'poster_url' => $season->poster_url ?? $season->film->poster_url ?? '',
                'release_year' => $season->release_year,
                'episodes_count' => $season->episodes->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $seasons,
        ]);
    }

    public function getSeasonDetail($id)
    {
        $season = Season::with(['film', 'episodes'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $season->id,
                'film_id' => $season->film_id,
                'film_title' => $season->film->title ?? '',
                'season_number' => $season->season_number,
                'title' => $season->title,
                'poster_url' => $season->poster_url,
                'release_year' => $season->release_year,
                'episodes' => $season->episodes->map(function ($ep) {
                    return [
                        'id' => $ep->id,
                        'season_id' => $ep->season_id,
                        'episode_number' => $ep->episode_number,
                        'title' => $ep->title,
                        'synopsis' => $ep->synopsis,
                        'duration_minutes' => $ep->duration_minutes,
                        'thumbnail_url' => $ep->thumbnail_url,
                        'video_source' => $ep->video_source,
                    ];
                }),
            ],
        ]);
    }

    public function getSeasonEpisodes($id)
    {
        $season = Season::with('episodes')->findOrFail($id);

        $episodes = $season->episodes->map(function ($ep) {
            return [
                'id' => $ep->id,
                'season_id' => $ep->season_id,
                'episode_number' => $ep->episode_number,
                'title' => $ep->title,
                'synopsis' => $ep->synopsis,
                'duration_minutes' => $ep->duration_minutes,
                'thumbnail_url' => $ep->thumbnail_url,
                'video_source' => $ep->video_source,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $episodes,
        ]);
    }

    public function getEpisodeDetail($id)
    {
        $episode = Episode::with(['season.film'])->findOrFail($id);
        $next = $episode->getNextEpisode();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $episode->id,
                'season_id' => $episode->season_id,
                'season_number' => $episode->season->season_number ?? 1,
                'film_id' => $episode->season->film_id ?? null,
                'film_title' => $episode->season->film->title ?? '',
                'episode_number' => $episode->episode_number,
                'title' => $episode->title,
                'synopsis' => $episode->synopsis,
                'duration_minutes' => $episode->duration_minutes,
                'thumbnail_url' => $episode->thumbnail_url,
                'video_source' => $episode->video_source,
                'next_episode_id' => $next ? $next->id : null,
            ],
        ]);
    }

    // ==========================================
    // GENRES & ACTORS ENDPOINTS
    // ==========================================

    public function getGenreDetail($id)
    {
        $genre = is_numeric($id) ? Genre::findOrFail($id) : Genre::where('slug', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $genre,
        ]);
    }

    public function getGenreMovies($id, Request $request)
    {
        $genre = is_numeric($id) ? Genre::findOrFail($id) : Genre::where('slug', $id)->firstOrFail();

        $limit = (int)$request->query('limit', 20);
        $films = $genre->films()->with('genres')->latest()->limit($limit)->get();

        return response()->json([
            'success' => true,
            'genre' => $genre->name,
            'data' => $films->map(fn($f) => $this->formatFilm($f)),
        ]);
    }

    public function getActors(Request $request)
    {
        $query = Actor::query();
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $limit = (int)$request->query('limit', 30);
        $actors = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $actors->items(),
            'meta' => [
                'current_page' => $actors->currentPage(),
                'last_page' => $actors->lastPage(),
                'total' => $actors->total(),
            ],
        ]);
    }

    public function getActorDetail($id)
    {
        $actor = Actor::with(['films.genres'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $actor->id,
                'name' => $actor->name,
                'slug' => $actor->slug,
                'photo_url' => $actor->photo_url,
                'films' => $actor->films->map(function ($film) {
                    $formatted = $this->formatFilm($film);
                    $formatted['character_name'] = $film->pivot->character_name ?? null;
                    return $formatted;
                }),
            ],
        ]);
    }

    public function getActorMovies($id, Request $request)
    {
        $actor = Actor::with('films.genres')->findOrFail($id);

        return response()->json([
            'success' => true,
            'actor' => $actor->name,
            'data' => $actor->films->map(fn($f) => $this->formatFilm($f)),
        ]);
    }

    // ==========================================
    // PROFILES ENDPOINTS
    // ==========================================

    public function getProfiles(Request $request)
    {
        $user = $this->resolveUser($request);
        $profiles = Profile::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    public function createProfile(Request $request)
    {
        $user = $this->resolveUser($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:1000',
            'is_child' => 'nullable|boolean',
            'pin' => 'nullable|string|max:10',
        ]);

        $profile = Profile::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'avatar' => $request->avatar ?? 'default',
            'is_child' => $request->is_child ?? false,
            'pin' => $request->pin,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil dibuat',
            'data' => $profile,
        ], 201);
    }

    public function getProfileDetail($id, Request $request)
    {
        $user = $this->resolveUser($request);
        $profile = Profile::where('user_id', $user->id)->where('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function updateProfileDetail($id, Request $request)
    {
        $user = $this->resolveUser($request);
        $profile = Profile::where('user_id', $user->id)->where('id', $id)->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|string|max:1000',
            'is_child' => 'sometimes|boolean',
            'pin' => 'nullable|string|max:10',
        ]);

        $profile->update($request->only(['name', 'avatar', 'is_child', 'pin']));

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $profile,
        ]);
    }

    public function deleteProfileDetail($id, Request $request)
    {
        $user = $this->resolveUser($request);
        Profile::where('user_id', $user->id)->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil dihapus',
        ]);
    }

    // ==========================================
    // NOTIFICATIONS ENDPOINTS
    // ==========================================

    public function getNotifications(Request $request)
    {
        $user = $this->resolveUser($request);
        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate((int)$request->query('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markNotificationAsRead($id, Request $request)
    {
        $user = $this->resolveUser($request);
        $notification = Notification::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai dibaca',
        ]);
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        $user = $this->resolveUser($request);
        Notification::where('user_id', $user->id)->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai dibaca',
        ]);
    }

    public function deleteNotification($id, Request $request)
    {
        $user = $this->resolveUser($request);
        Notification::where('user_id', $user->id)->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi dihapus',
        ]);
    }

    // ==========================================
    // APP LAUNCH, SETTINGS & CHANGELOGS ENDPOINTS
    // ==========================================

    public function getAppLaunchNotifications()
    {
        $emails = AppLaunchNotification::latest()->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $emails,
        ]);
    }

    public function subscribeAppLaunch(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:app_launch_notifications,email',
        ]);

        $sub = AppLaunchNotification::create(['email' => $request->email]);

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diajukan untuk notifikasi peluncuran app',
            'data' => $sub,
        ], 201);
    }

    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function getChangelogs()
    {
        $changelogs = Changelog::published()->get();

        return response()->json([
            'success' => true,
            'data' => $changelogs,
        ]);
    }

    public function getLatestChangelog()
    {
        $latest = Changelog::published()->first();

        return response()->json([
            'success' => true,
            'data' => $latest,
        ]);
    }

    public function getPopularSearches()
    {
        $popular = SearchLog::select('query', DB::raw('count(*) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $popular,
        ]);
    }

    // ==========================================
    // ADMIN & MODERATION ENDPOINTS
    // ==========================================

    public function getUsers(Request $request)
    {
        $limit = (int)$request->query('limit', 20);
        $users = User::select('id', 'name', 'email', 'avatar', 'is_admin', 'created_at')
            ->latest()
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function reportReview($id, Request $request)
    {
        $user = $this->resolveUser($request);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $report = ReviewReport::create([
            'review_id' => $id,
            'user_id' => $user->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan ulasan berhasil dikirim',
            'data' => $report,
        ], 201);
    }

    public function getReviewReports(Request $request)
    {
        $reports = ReviewReport::with(['review', 'user'])->latest()->paginate((int)$request->query('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function getAdminActivityLogs(Request $request)
    {
        $logs = AdminActivityLog::with('admin')->latest()->paginate((int)$request->query('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    // ==========================================
    // WATCH PARTY API ENDPOINTS
    // ==========================================

    public function createWatchPartyApi(Request $request)
    {
        $request->validate([
            'film_id' => 'required|exists:films,id',
            'season_number' => 'nullable|integer',
            'episode_number' => 'nullable|integer',
            'guest_name' => 'nullable|string|max:50',
        ]);

        $user = $this->resolveUser($request);
        $guestName = $request->guest_name ?: ($user->name ?? 'Host');

        $watchParty = WatchParty::create([
            'film_id' => $request->film_id,
            'season_number' => $request->input('season_number', 1),
            'episode_number' => $request->input('episode_number', 1),
            'host_user_id' => $user->id,
            'host_guest_name' => $guestName,
            'status' => 'waiting',
            'current_position_seconds' => 0,
            'is_playing' => false,
            'is_locked' => false,
        ]);

        WatchPartyParticipant::create([
            'watch_party_id' => $watchParty->id,
            'user_id' => $user->id,
            'guest_name' => $guestName,
            'session_id' => Str::random(32),
            'is_host' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room Nonton Bareng berhasil dibuat',
            'data' => [
                'room_code' => $watchParty->room_code,
                'film_id' => $watchParty->film_id,
                'status' => $watchParty->status,
            ],
        ], 201);
    }

    public function getWatchPartyApi($roomCode)
    {
        $watchParty = WatchParty::with(['film', 'participants.user'])
            ->where('room_code', strtoupper($roomCode))
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'room_code' => $watchParty->room_code,
                'film_title' => $watchParty->film->title ?? '',
                'poster_url' => $watchParty->film->poster_url ?? '',
                'status' => $watchParty->status,
                'is_playing' => (bool)$watchParty->is_playing,
                'current_position_seconds' => $watchParty->current_position_seconds,
                'participants' => $watchParty->participants->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->guest_name,
                    'is_host' => (bool)$p->is_host,
                ]),
            ],
        ]);
    }

    public function joinWatchPartyApi($roomCode, Request $request)
    {
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $user = $this->resolveUser($request);

        $guestName = $request->guest_name ?: ($user->name ?? 'Guest');

        $participant = WatchPartyParticipant::firstOrCreate(
            ['watch_party_id' => $watchParty->id, 'user_id' => $user->id],
            [
                'guest_name' => $guestName,
                'session_id' => Str::random(32),
                'is_host' => false,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil bergabung ke Room',
            'data' => $participant,
        ]);
    }

    public function sendWatchPartyMessageApi($roomCode, Request $request)
    {
        $request->validate(['message' => 'required|string|max:500']);

        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $user = $this->resolveUser($request);

        $msg = WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'user_id' => $user->id,
            'sender_name' => $user->name,
            'message' => $request->message,
            'is_system' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $msg,
        ], 201);
    }

    public function getWatchPartyMessagesApi($roomCode)
    {
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();

        $messages = WatchPartyMessage::where('watch_party_id', $watchParty->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function sendWatchPartyReactionApi($roomCode, Request $request)
    {
        $request->validate(['emoji' => 'required|string|max:10']);

        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $user = $this->resolveUser($request);

        $reaction = WatchPartyReaction::create([
            'watch_party_id' => $watchParty->id,
            'user_id' => $user->id,
            'emoji' => $request->emoji,
        ]);

        return response()->json([
            'success' => true,
            'data' => $reaction,
        ], 201);
    }

    public function leaveWatchPartyApi($roomCode, Request $request)
    {
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $user = $this->resolveUser($request);

        WatchPartyParticipant::where('watch_party_id', $watchParty->id)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar dari Room',
        ]);
    }
}
