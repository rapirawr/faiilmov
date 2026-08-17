<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\Genre;
use App\Models\Actor;
use App\Models\Season;
use App\Models\Episode;
use App\Models\AdminActivityLog;
use App\Jobs\SyncFilmsJob;
use App\Services\ImdbService;
use App\Services\MovieBoxService;
use App\Services\AnichinService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AdminFilmController extends Controller
{
    public function index(Request $request)
    {
        $query = Film::with(['genres']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('subject_type', $request->type);
        }

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

        if ($request->filled('content_rating')) {
            if ($request->content_rating === 'UNRATED') {
                $query->whereNull('content_rating');
            } else {
                $query->where('content_rating', $request->content_rating);
            }
        }

        if ($request->filled('coming_soon')) {
            if ($request->coming_soon === 'yes') {
                $query->where(function ($q) {
                    $q->where('available_from', '>', now())
                      ->orWhere('release_year', '>', date('Y'));
                });
            } elseif ($request->coming_soon === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('available_from')->orWhere('available_from', '<=', now());
                })->where('release_year', '<=', date('Y'));
            }
        }

        $sort = $request->get('sort', 'latest');
        if ($sort === 'rating') {
            $query->orderBy('rating', 'desc');
        } elseif ($sort === 'views') {
            $query->orderBy('view_count', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $films = $query->paginate(15)->withQueryString();
        $genres = Genre::orderBy('name')->get();
        $trashedFilms = Film::onlyTrashed()->with('genres')->orderByDesc('deleted_at')->get();

        $stats = [
            'total' => Film::count(),
            'movies' => Film::where('subject_type', 'movie')->count(),
            'series' => Film::where('subject_type', 'series')->count(),
            'dracin' => Film::where('subject_type', 'dracin')->count(),
            'unrated' => Film::whereNull('content_rating')->count(),
            'coming_soon' => Film::where('available_from', '>', now())->orWhere('release_year', '>', date('Y'))->count(),
            'trash' => count($trashedFilms),
        ];

        return view('admin.films.index', compact('films', 'genres', 'trashedFilms', 'stats'));
    }

    public function create()
    {
        $genres = Genre::orderBy('name')->get();
        return view('admin.films.create', compact('genres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:2099',
            'duration_minutes' => 'nullable|integer|min:1',
            'rating' => 'nullable|numeric|min:0|max:10',
            'subject_type' => 'required|in:movie,series,dracin',
            'content_rating' => 'nullable|string|in:SU,G,PG,13+,16+,18+',
            'max_resolution' => 'nullable|string|in:480P,720P,1080P,4K',
            'view_count' => 'nullable|integer|min:0',
            'trailer_url' => 'nullable|url',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'poster_url' => 'nullable|string',
            'backdrop_url' => 'nullable|string',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
            'actors' => 'nullable|array',
            'actors.*' => 'exists:actors,id',
            'actor_characters' => 'nullable|array',
            'actor_roles' => 'nullable|array',
        ]);

        $posterUrl = $request->poster_url;
        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $posterUrl = Storage::url($path);
        }

        $availableFrom = $request->filled('available_from') ? $request->available_from : null;
        $isComingSoonReq = $request->boolean('is_coming_soon');
        if ($isComingSoonReq && !$availableFrom) {
            $availableFrom = now()->addMonth();
        } elseif (!$isComingSoonReq && !$request->filled('available_from')) {
            $availableFrom = null;
        }

        $releaseYear = $validated['release_year'] ?? (int)date('Y');

        $film = Film::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
            'synopsis' => $validated['synopsis'] ?? null,
            'release_year' => $releaseYear,
            'duration_minutes' => $validated['duration_minutes'] ?? 120,
            'rating' => $validated['rating'] ?? 0.0,
            'subject_type' => $validated['subject_type'],
            'content_rating' => $validated['content_rating'] ?? null,
            'max_resolution' => $validated['max_resolution'] ?? '1080P',
            'view_count' => $validated['view_count'] ?? 0,
            'trailer_url' => $validated['trailer_url'] ?? null,
            'poster_url' => $posterUrl ?: 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600',
            'backdrop_url' => $validated['backdrop_url'] ?? null,
            'available_from' => $availableFrom,
        ]);

        if (!empty($validated['genres'])) {
            $film->genres()->sync($validated['genres']);
        }

        if (!empty($validated['actors'])) {
            $actorData = [];
            foreach ($validated['actors'] as $actorId) {
                $charName = $request->input("actor_characters.{$actorId}", null);
                $roleType = $request->input("actor_roles.{$actorId}", 'regular');
                $actorData[$actorId] = [
                    'character_name' => $charName,
                    'role_type' => in_array($roleType, ['main', 'regular']) ? $roleType : 'regular',
                ];
            }
            $film->actors()->sync($actorData);
        }

        AdminActivityLog::log('created_film', "Menambahkan film baru: {$film->title}", 'Film', $film->id);

        return redirect()->route('admin.films.index')->with('success', "Film '{$film->title}' berhasil ditambahkan.");
    }

    public function edit(Film $film)
    {
        $film->load(['genres', 'actors', 'seasons.episodes', 'soundtracks']);
        $genres = Genre::orderBy('name')->get();
        return view('admin.films.edit', compact('film', 'genres'));
    }

    public function update(Request $request, Film $film)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:2099',
            'duration_minutes' => 'nullable|integer|min:1',
            'rating' => 'nullable|numeric|min:0|max:10',
            'subject_type' => 'required|in:movie,series,dracin',
            'content_rating' => 'nullable|string|in:SU,G,PG,13+,16+,18+',
            'max_resolution' => 'nullable|string|in:480P,720P,1080P,4K',
            'view_count' => 'nullable|integer|min:0',
            'trailer_url' => 'nullable|url',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'poster_url' => 'nullable|string',
            'backdrop_url' => 'nullable|string',
            'available_from' => 'nullable|date',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
            'actors' => 'nullable|array',
            'actors.*' => 'exists:actors,id',
            'actor_characters' => 'nullable|array',
            'actor_roles' => 'nullable|array',
        ]);

        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $validated['poster_url'] = Storage::url($path);
        }

        $availableFrom = $request->filled('available_from') ? $request->available_from : null;
        $isComingSoonReq = $request->boolean('is_coming_soon');
        if ($isComingSoonReq && !$availableFrom) {
            $availableFrom = ($film->available_from && $film->available_from->isFuture()) ? $film->available_from : now()->addMonth();
        } elseif (!$isComingSoonReq && !$request->filled('available_from')) {
            $availableFrom = null;
        }

        $releaseYear = $validated['release_year'] ?? $film->release_year;

        $film->update([
            'title' => $validated['title'],
            'synopsis' => $validated['synopsis'] ?? $film->synopsis,
            'release_year' => $releaseYear,
            'duration_minutes' => $validated['duration_minutes'] ?? $film->duration_minutes,
            'rating' => $validated['rating'] ?? $film->rating,
            'subject_type' => $validated['subject_type'],
            'content_rating' => $validated['content_rating'] ?? $film->content_rating,
            'max_resolution' => $validated['max_resolution'] ?? $film->max_resolution,
            'view_count' => $validated['view_count'] ?? $film->view_count,
            'trailer_url' => $validated['trailer_url'] ?? $film->trailer_url,
            'poster_url' => $validated['poster_url'] ?? $film->poster_url,
            'backdrop_url' => $validated['backdrop_url'] ?? $film->backdrop_url,
            'available_from' => $availableFrom,
        ]);

        if (isset($validated['genres'])) {
            $film->genres()->sync($validated['genres']);
        }

        if (isset($validated['actors'])) {
            $actorData = [];
            foreach ($validated['actors'] as $actorId) {
                $charName = $request->input("actor_characters.{$actorId}", null);
                $roleType = $request->input("actor_roles.{$actorId}", 'regular');
                $actorData[$actorId] = [
                    'character_name' => $charName,
                    'role_type' => in_array($roleType, ['main', 'regular']) ? $roleType : 'regular',
                ];
            }
            $film->actors()->sync($actorData);
        } else {
            $film->actors()->detach();
        }

        AdminActivityLog::log('updated_film', "Mengubah data film: {$film->title}", 'Film', $film->id);

        return redirect()->route('admin.films.index')->with('success', "Film '{$film->title}' berhasil diperbarui.");
    }

    public function destroy(Film $film)
    {
        $title = $film->title;
        $id = $film->id;

        $film->delete();

        AdminActivityLog::log('deleted_film', "Menghapus (soft-delete) film: {$title}", 'Film', $id);

        return redirect()->route('admin.films.index')->with('success', "Film '{$title}' berhasil dihapus.");
    }

    public function restore(int $id)
    {
        $film = Film::withTrashed()->findOrFail($id);
        $film->restore();

        AdminActivityLog::log('restored_film', "Mengembalikan film dari sampah: {$film->title}", 'Film', $film->id);

        return redirect()->route('admin.films.index')->with('success', "Film '{$film->title}' berhasil dipulihkan.");
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:films,id',
        ]);

        $count = Film::whereIn('id', $validated['ids'])->delete();

        AdminActivityLog::log('bulk_deleted_films', "Menghapus (soft-delete) {$count} film sekaligus.");

        return redirect()->route('admin.films.index')->with('success', "{$count} film berhasil dihapus.");
    }

    public function bulkRestore(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:films,id',
        ]);

        $count = Film::withTrashed()->whereIn('id', $validated['ids'])->restore();

        AdminActivityLog::log('bulk_restored_films', "Mengembalikan {$count} film dari sampah sekaligus.");

        return redirect()->route('admin.films.index')->with('success', "{$count} film berhasil dipulihkan.");
    }

    public function forceDelete(int $id)
    {
        $film = Film::onlyTrashed()->findOrFail($id);
        $title = $film->title;
        $film->forceDelete();

        AdminActivityLog::log('force_deleted_film', "Menghapus permanen film: {$title}", 'Film', $id);

        return redirect()->route('admin.films.index')->with('success', "Film '{$title}' berhasil dihapus secara permanen.");
    }

    public function emptyTrash()
    {
        $count = Film::onlyTrashed()->count();
        Film::onlyTrashed()->forceDelete();

        AdminActivityLog::log('empty_trash', "Mengosongkan tempat sampah ({$count} film dihapus permanen).");

        return redirect()->route('admin.films.index')->with('success', "Tempat sampah berhasil dikosongkan.");
    }

    public function syncApi()
    {
        SyncFilmsJob::dispatch(Auth::id());

        AdminActivityLog::log('sync_api_triggered', "Memulai job sinkronisasi film dari MovieBox API dalam background queue.");

        return redirect()->route('admin.films.index')->with('success', 'Proses sinkronisasi film dari API eksternal telah dimulai di latar belakang.');
    }

    public function syncDracinApi()
    {
        \App\Jobs\SyncDracinJob::dispatch(Auth::id());

        AdminActivityLog::log('sync_dracin_api_triggered', "Memulai job sinkronisasi Dracin dari Anichin API dalam background queue.");

        return redirect()->route('admin.films.index')->with('success', 'Proses sinkronisasi Dracin dari Anichin API telah dimulai di latar belakang.');
    }

    public function contentRatingEditor(Request $request)
    {
        $query = Film::query();

        if ($request->filled('filter')) {
            if ($request->filter === 'unrated') {
                $query->whereNull('content_rating');
            } else {
                $query->where('content_rating', $request->filter);
            }
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $films = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $unratedCount = Film::whereNull('content_rating')->count();

        return view('admin.films.content-rating', compact('films', 'unratedCount'));
    }

    public function updateContentRatings(Request $request)
    {
        $validated = $request->validate([
            'ratings' => 'required|array',
            'ratings.*' => 'nullable|string|in:SU,G,PG,13+,16+,18+',
        ]);

        $updatedCount = 0;
        foreach ($validated['ratings'] as $filmId => $rating) {
            $film = Film::find($filmId);
            if ($film) {
                $film->update(['content_rating' => $rating ?: null]);
                $updatedCount++;
            }
        }

        AdminActivityLog::log('bulk_updated_content_ratings', "Memperbarui content rating untuk {$updatedCount} film.");

        return back()->with('success', "Berhasil memperbarui content rating untuk {$updatedCount} film.");
    }

    public function autoRate(Film $film)
    {
        $rating = $film->autoDetermineContentRating();
        $film->update(['content_rating' => $rating]);

        AdminActivityLog::log('auto_rated_film', "Auto-rate film '{$film->title}' menjadi rating {$rating}.", 'Film', $film->id);

        if (request()->wantsJson() || request()->ajax() || request()->header('Accept') === 'application/json') {
            return response()->json(['status' => 'ok', 'rating' => $rating, 'title' => $film->title]);
        }

        return back()->with('success', "Film '{$film->title}' berhasil di-auto rate menjadi '{$rating}'.");
    }

    public function autoRateAll(Request $request)
    {
        $onlyUnrated = $request->boolean('only_unrated', true);
        $query = Film::query();

        if ($onlyUnrated) {
            $query->whereNull('content_rating');
        }

        $films = $query->get();
        $updatedCount = 0;

        foreach ($films as $film) {
            $rating = $film->autoDetermineContentRating();
            $film->update(['content_rating' => $rating]);
            $updatedCount++;
        }

        $scopeText = $onlyUnrated ? "film tanpa rating" : "seluruh film";
        AdminActivityLog::log('auto_rated_all_films', "Menjalankan auto-rate massal untuk {$updatedCount} {$scopeText}.");

        return back()->with('success', "Auto-rating selesai! {$updatedCount} {$scopeText} berhasil diklasifikasikan.");
    }

    public function toggleComingSoon(Film $film)
    {
        if ($film->isComingSoon()) {
            $film->update([
                'available_from' => null,
                'release_year' => min($film->release_year ?? (int)date('Y'), (int)date('Y')),
            ]);
            $msg = "Status Coming Soon untuk film '{$film->title}' telah dinonaktifkan (film sudah rilis).";
        } else {
            $film->update([
                'available_from' => now()->addMonth(),
            ]);
            $msg = "Film '{$film->title}' berhasil ditandai sebagai Coming Soon (Segera Hadir).";
        }

        AdminActivityLog::log('updated_film_coming_soon', $msg, 'Film', $film->id);

        return back()->with('success', $msg);
    }

    /**
     * Live fetch preview data from IMDb URL / ID
     */
    public function fetchImdb(Request $request, ImdbService $imdbService)
    {
        $request->validate([
            'imdb_url' => 'required|string',
        ]);

        $data = $imdbService->fetchFilmData($request->input('imdb_url'));

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data dari link atau ID IMDb tersebut. Pastikan format link atau ID IMDb valid (contoh: tt1375666 atau https://www.imdb.com/title/tt1375666/).',
            ], 422);
        }

        // Match existing genres in local database
        $genreIds = [];
        if (!empty($data['genres'])) {
            $allGenres = Genre::all()->keyBy(fn($g) => strtolower(trim($g->name)));
            foreach ($data['genres'] as $gName) {
                $cleanName = strtolower(trim($gName));
                if (isset($allGenres[$cleanName])) {
                    $genreIds[] = $allGenres[$cleanName]->id;
                }
            }
        }
        $data['genre_ids'] = $genreIds;

        return response()->json([
            'status' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * Direct import movie with cast, genres, and soundtracks from IMDb link
     */
    public function importImdb(Request $request, ImdbService $imdbService)
    {
        $request->validate([
            'imdb_url' => 'required|string',
        ]);

        $data = $imdbService->fetchFilmData($request->input('imdb_url'));

        if (!$data) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengambil metadata dari link IMDb yang dimasukkan. Pastikan URL atau ID IMDb valid.',
                ], 422);
            }
            return back()->with('error', 'Gagal mengambil metadata dari link IMDb yang dimasukkan. Pastikan URL atau ID IMDb valid.');
        }

        $baseSlug = Str::slug($data['title']);
        $slug = $baseSlug ? $baseSlug . '-' . Str::random(5) : 'film-' . rand(1000, 9999);

        $film = Film::create([
            'title' => $data['title'],
            'slug' => $slug,
            'synopsis' => $data['synopsis'] ?? null,
            'release_year' => $data['release_year'] ?? (int)date('Y'),
            'duration_minutes' => $data['duration_minutes'] ?? 120,
            'rating' => $data['rating'] ?? 0.0,
            'subject_type' => $data['subject_type'] ?? 'movie',
            'content_rating' => $data['content_rating'] ?? '13+',
            'max_resolution' => $data['max_resolution'] ?? '1080P',
            'view_count' => 0,
            'trailer_url' => $data['trailer_url'] ?? null,
            'poster_url' => $data['poster_url'] ?: 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600',
            'backdrop_url' => $data['backdrop_url'] ?? null,
            'moviebox_subject_id' => $data['moviebox_subject_id'] ?? null,
            'available_from' => $data['available_from'] ?? null,
        ]);

        // Sync Genres
        if (!empty($data['genres'])) {
            $genreIds = [];
            foreach ($data['genres'] as $gName) {
                $genre = Genre::firstOrCreate(
                    ['name' => $gName],
                    ['slug' => Str::slug($gName)]
                );
                $genreIds[] = $genre->id;
            }
            $film->genres()->sync($genreIds);
        }

        // Sync Actors & Cast
        if (!empty($data['actors'])) {
            $actorData = [];
            foreach ($data['actors'] as $act) {
                $actor = Actor::firstOrCreate(
                    ['name' => $act['name']],
                    [
                        'photo_url' => $act['photo_url'] ?? null,
                        'slug' => Str::slug($act['name']),
                    ]
                );
                if (!empty($act['photo_url']) && (empty($actor->getRawOriginal('photo_url')) || str_contains($actor->getRawOriginal('photo_url'), 'unsplash.com'))) {
                    $actor->update(['photo_url' => $act['photo_url']]);
                }
                $actorData[$actor->id] = [
                    'character_name' => $act['character_name'] ?? null,
                    'role_type' => $act['role_type'] ?? 'regular',
                ];
            }
            $film->actors()->sync($actorData);
        }

        // Sync Soundtracks (OST)
        $soundtrackCount = 0;
        if (!empty($data['soundtracks'])) {
            foreach ($data['soundtracks'] as $st) {
                $film->soundtracks()->create([
                    'track_name' => $st['track_name'],
                    'artist_name' => $st['artist_name'] ?? 'Various Artists',
                    'collection_name' => $st['collection_name'] ?? ($film->title . ' (Original Soundtrack)'),
                    'preview_audio_url' => $st['preview_audio_url'] ?? null,
                    'artwork_url' => $st['artwork_url'] ?? null,
                    'track_view_url' => $st['track_view_url'] ?? null,
                    'order' => $st['order'] ?? ($soundtrackCount + 1),
                ]);
                $soundtrackCount++;
            }
        }

        AdminActivityLog::log('imported_film_imdb', "Mengimpor film '{$film->title}' dari IMDb (" . count($data['genres'] ?? []) . " genre, " . count($data['actors'] ?? []) . " aktor, {$soundtrackCount} OST)", 'Film', $film->id);

        $msg = "Film '{$film->title}' berhasil diimpor dari IMDb lengkap beserta " . count($data['actors'] ?? []) . " pemeran, " . count($data['genres'] ?? []) . " genre, dan {$soundtrackCount} soundtrack (OST)!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => $msg,
                'film_id' => $film->id,
                'redirect' => route('admin.films.edit', $film->id),
            ]);
        }

        return redirect()->route('admin.films.edit', $film->id)->with('success', $msg);
    }

    /**
     * View Film Importer Page
     */
    public function importer(Request $request)
    {
        return view('admin.films.importer');
    }

    /**
     * Search External Providers (MovieBox & Anichin)
     */
    public function externalSearch(Request $request, MovieBoxService $movieBox, AnichinService $anichin)
    {
        $query = trim($request->input('query', ''));
        $provider = $request->input('provider', 'all');
        $typeFilter = $request->input('type', 'all');
        $page = (int)$request->input('page', 1);

        if (empty($query)) {
            return response()->json(['status' => 'success', 'results' => [], 'total' => 0]);
        }

        $results = [];

        // 1. Search MovieBox
        if (in_array($provider, ['all', 'moviebox'])) {
            try {
                $mbData = $movieBox->search($query, $page);
                if (!empty($mbData)) {
                    $subjects = Film::extractSearchSubjects($mbData);
                    foreach ($subjects as $sub) {
                        $subId = (string)($sub['subjectId'] ?? $sub['id'] ?? '');
                        if (!$subId) continue;

                        $title = $sub['title'] ?? $sub['name'] ?? 'Untitled';
                        $stype = (int)($sub['subjectType'] ?? $sub['stype'] ?? 1);
                        $subType = ($stype === 2) ? 'series' : 'movie';

                        if ($typeFilter !== 'all' && $typeFilter !== $subType) {
                            continue;
                        }

                        $poster = $sub['cover']['url'] ?? $sub['cover'] ?? $sub['poster']['url'] ?? $sub['poster'] ?? $sub['pic']['url'] ?? $sub['pic'] ?? null;
                        $backdrop = $sub['banner']['url'] ?? $sub['banner'] ?? $sub['bgCover']['url'] ?? $sub['bgCover'] ?? $poster;
                        $year = isset($sub['releaseDate']) ? (int)substr($sub['releaseDate'], 0, 4) : 0;
                        if ($year <= 0 && isset($sub['year'])) $year = (int)$sub['year'];

                        $results[] = [
                            'subject_id'     => $subId,
                            'source'         => 'moviebox',
                            'provider_name'  => 'MovieBox',
                            'title'          => $title,
                            'original_title' => $sub['originName'] ?? $title,
                            'subject_type'   => $subType,
                            'poster_url'     => $poster,
                            'backdrop_url'   => $backdrop,
                            'release_year'   => $year > 0 ? $year : null,
                            'rating'         => (float)($sub['imdbRatingValue'] ?? $sub['score'] ?? 0.0),
                            'synopsis'       => $sub['description'] ?? $sub['intro'] ?? $sub['synopsis'] ?? '',
                            'genres'         => array_column($sub['genreList'] ?? $sub['genres'] ?? [], 'name'),
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("External search MovieBox error: " . $e->getMessage());
            }
        }

        // 2. Search Anichin (Dracin)
        if ($provider === 'all' || in_array($provider, ['anichin', 'dramabox', 'reelshort', 'shortmax', 'goodshort', 'dramawave', 'dramanova'])) {
            if ($typeFilter === 'all' || $typeFilter === 'dracin') {
                $dracinSources = ($provider === 'all' || $provider === 'anichin') 
                    ? ['dramabox', 'reelshort', 'shortmax'] 
                    : [$provider];

                foreach ($dracinSources as $src) {
                    try {
                        $anichinItems = $anichin->search($query, $src);
                        if (!empty($anichinItems)) {
                            foreach ($anichinItems as $item) {
                                $rawId = (string)($item['id'] ?? $item['dramaId'] ?? '');
                                if (!$rawId) continue;

                                $subId = "anichin:{$src}:{$rawId}";
                                $title = $item['title'] ?? $item['name'] ?? 'Untitled Dracin';
                                $poster = $item['posterImg'] ?? $item['cover'] ?? $item['poster'] ?? null;
                                if (is_array($poster)) $poster = $poster['url'] ?? null;

                                $results[] = [
                                    'subject_id'     => $subId,
                                    'source'         => $src,
                                    'provider_name'  => ucfirst($src) . ' (Dracin)',
                                    'title'          => $title,
                                    'original_title' => $title,
                                    'subject_type'   => 'dracin',
                                    'poster_url'     => $poster,
                                    'backdrop_url'   => $poster,
                                    'release_year'   => (int)date('Y'),
                                    'rating'         => (float)($item['score'] ?? 4.8),
                                    'synopsis'       => $item['synopsis'] ?? $item['description'] ?? $item['intro'] ?? '',
                                    'genres'         => ['Drama Pendek'],
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning("External search Anichin [{$src}] error: " . $e->getMessage());
                    }
                }
            }
        }

        // 3. Mark which items already exist in local database
        if (!empty($results)) {
            $subjectIds = array_column($results, 'subject_id');
            $existingFilms = Film::whereIn('moviebox_subject_id', $subjectIds)->get()->keyBy('moviebox_subject_id');

            foreach ($results as &$res) {
                $existing = $existingFilms->get($res['subject_id']);
                if ($existing) {
                    $res['is_imported'] = true;
                    $res['local_film_id'] = $existing->id;
                    $res['local_edit_url'] = route('admin.films.edit', $existing->id);
                    if (!empty($existing->synopsis) && empty($res['synopsis'])) {
                        $res['synopsis'] = $existing->synopsis;
                    }
                } else {
                    $res['is_imported'] = false;
                    $res['local_film_id'] = null;
                    $res['local_edit_url'] = null;
                }
            }
        }

        return response()->json([
            'status'  => 'success',
            'query'   => $query,
            'count'   => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Get full details & synopsis for an external film on demand (e.g. for modal preview)
     */
    public function externalDetail(Request $request, MovieBoxService $movieBox, AnichinService $anichin)
    {
        $subjectId = (string)$request->input('subject_id');
        $source = $request->input('source', 'moviebox');

        if (empty($subjectId)) {
            return response()->json(['status' => 'error', 'message' => 'Subject ID wajib diisi.'], 422);
        }

        try {
            // First check if already in local DB
            $localFilm = Film::where('moviebox_subject_id', $subjectId)->with('genres')->first();

            if (str_starts_with($subjectId, 'anichin:')) {
                $parts = explode(':', $subjectId);
                $dracinSource = $parts[1] ?? 'dramabox';
                $rawId = $parts[2] ?? '';

                $detail = $anichin->getDetail($dracinSource, $rawId) ?: [];
                $title = $detail['title'] ?? $detail['name'] ?? ($localFilm->title ?? '');
                $synopsis = $detail['synopsis'] ?? $detail['description'] ?? $detail['intro'] ?? ($localFilm->synopsis ?? '');
                $poster = $detail['posterImg'] ?? $detail['cover'] ?? ($localFilm->poster_url ?? null);
                if (is_array($poster)) $poster = $poster['url'] ?? null;

                return response()->json([
                    'status' => 'success',
                    'detail' => [
                        'subject_id'     => $subjectId,
                        'source'         => $dracinSource,
                        'provider_name'  => ucfirst($dracinSource) . ' (Dracin)',
                        'title'          => $title,
                        'original_title' => $title,
                        'synopsis'       => $synopsis,
                        'poster_url'     => $poster,
                        'backdrop_url'   => $poster,
                        'rating'         => (float)($detail['score'] ?? ($localFilm->rating ?? 4.8)),
                        'release_year'   => $localFilm->release_year ?? (int)date('Y'),
                        'genres'         => ['Drama Pendek'],
                        'duration'       => null,
                        'episodes_count' => count($detail['episodes'] ?? []),
                        'is_imported'    => (bool)$localFilm,
                        'local_edit_url' => $localFilm ? route('admin.films.edit', $localFilm->id) : null,
                    ],
                ]);
            } else {
                $detail = $movieBox->getDetails($subjectId);
                if (empty($detail) && $localFilm) {
                    return response()->json([
                        'status' => 'success',
                        'detail' => [
                            'subject_id'     => $subjectId,
                            'source'         => 'moviebox',
                            'provider_name'  => 'MovieBox',
                            'title'          => $localFilm->title,
                            'original_title' => $localFilm->title,
                            'synopsis'       => $localFilm->synopsis,
                            'poster_url'     => $localFilm->poster_url,
                            'backdrop_url'   => $localFilm->backdrop_url,
                            'rating'         => (float)$localFilm->rating,
                            'release_year'   => $localFilm->release_year,
                            'genres'         => $localFilm->genres->pluck('name')->toArray(),
                            'duration'       => $localFilm->duration_minutes ? "{$localFilm->duration_minutes}m" : null,
                            'is_imported'    => true,
                            'local_edit_url' => route('admin.films.edit', $localFilm->id),
                        ],
                    ]);
                }

                if (empty($detail)) {
                    return response()->json(['status' => 'error', 'message' => 'Detail tidak ditemukan di provider API.'], 404);
                }

                $stype = (int)($detail['subjectType'] ?? $detail['stype'] ?? 1);
                $subType = ($stype === 2) ? 'series' : 'movie';
                $poster = $detail['cover']['url'] ?? $detail['cover'] ?? $detail['poster']['url'] ?? $detail['poster'] ?? ($localFilm->poster_url ?? null);
                $backdrop = $detail['banner']['url'] ?? $detail['banner'] ?? $detail['stills']['url'] ?? $poster ?? ($localFilm->backdrop_url ?? null);
                $year = isset($detail['releaseDate']) ? (int)substr($detail['releaseDate'], 0, 4) : 0;
                if ($year <= 0 && isset($detail['year'])) $year = (int)$detail['year'];
                if ($year <= 0 && $localFilm) $year = $localFilm->release_year;

                $genres = [];
                if (!empty($detail['genreList'])) {
                    $genres = array_column($detail['genreList'], 'name');
                } elseif (!empty($detail['genre'])) {
                    $genres = array_map('trim', explode(',', $detail['genre']));
                } elseif ($localFilm && $localFilm->genres->count() > 0) {
                    $genres = $localFilm->genres->pluck('name')->toArray();
                }

                $synopsis = $detail['description'] ?? $detail['intro'] ?? $detail['synopsis'] ?? ($localFilm->synopsis ?? '');

                return response()->json([
                    'status' => 'success',
                    'detail' => [
                        'subject_id'     => $subjectId,
                        'source'         => 'moviebox',
                        'provider_name'  => 'MovieBox',
                        'title'          => $detail['title'] ?? $detail['postTitle'] ?? ($localFilm->title ?? ''),
                        'original_title' => $detail['originName'] ?? $detail['title'] ?? ($localFilm->title ?? ''),
                        'subject_type'   => $subType,
                        'synopsis'       => $synopsis,
                        'poster_url'     => $poster,
                        'backdrop_url'   => $backdrop,
                        'rating'         => (float)($detail['imdbRatingValue'] ?? $detail['score'] ?? ($localFilm->rating ?? 0.0)),
                        'release_year'   => $year > 0 ? $year : null,
                        'genres'         => $genres,
                        'duration'       => $detail['duration'] ?? ($localFilm->duration_minutes ? "{$localFilm->duration_minutes}m" : null),
                        'content_rating' => $detail['contentRating'] ?? ($localFilm->content_rating ?? null),
                        'is_imported'    => (bool)$localFilm,
                        'local_edit_url' => $localFilm ? route('admin.films.edit', $localFilm->id) : null,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("External detail error for {$subjectId}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal mengambil detail film: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import a single film from external provider
     */
    public function importItem(Request $request, MovieBoxService $movieBox, AnichinService $anichin)
    {
        $subjectId = (string)$request->input('subject_id');
        $source = $request->input('source', 'moviebox');

        if (empty($subjectId)) {
            return response()->json(['status' => 'error', 'message' => 'Subject ID wajib diisi.'], 422);
        }

        try {
            $film = null;

            if (str_starts_with($subjectId, 'anichin:')) {
                $parts = explode(':', $subjectId);
                $dracinSource = $parts[1] ?? 'dramabox';
                $rawId = $parts[2] ?? '';

                $detail = $anichin->getDetail($dracinSource, $rawId);
                if (empty($detail) || !is_array($detail)) {
                    $detail = [
                        'id' => $rawId,
                        'title' => $request->input('title'),
                        'posterImg' => $request->input('poster_url'),
                    ];
                } else {
                    if (empty($detail['id']) && empty($detail['dramaId'])) {
                        $detail['id'] = $rawId;
                    }
                    if (empty($detail['title']) && $request->input('title')) {
                        $detail['title'] = $request->input('title');
                    }
                    if (empty($detail['posterImg']) && empty($detail['cover']) && $request->input('poster_url')) {
                        $detail['posterImg'] = $request->input('poster_url');
                    }
                }
                $film = $anichin->syncItemToFilmModel($dracinSource, $detail, true);
            } else {
                $detail = null;
                try {
                    $detail = $movieBox->getDetails($subjectId);
                } catch (\Throwable $e) {
                    \Log::warning("MovieBox getDetails fallback for {$subjectId}: " . $e->getMessage());
                }

                if (empty($detail) || !is_array($detail)) {
                    $detail = [
                        'subjectId' => $subjectId,
                        'title' => $request->input('title'),
                        'cover' => $request->input('poster_url'),
                        'subjectType' => $request->input('subject_type') === 'series' ? 2 : 1,
                    ];
                } else {
                    if (empty($detail['subjectId'])) {
                        $detail['subjectId'] = $subjectId;
                    }
                    if (empty($detail['title']) && $request->input('title')) {
                        $detail['title'] = $request->input('title');
                    }
                    if (empty($detail['cover']) && $request->input('poster_url')) {
                        $detail['cover'] = $request->input('poster_url');
                    }
                }
                $film = Film::fromApiData($detail, true);
            }

            if (!$film) {
                return response()->json(['status' => 'error', 'message' => 'Gagal memproses data film dari provider API.'], 400);
            }

            AdminActivityLog::log(
                'imported_external_film',
                "Mengimpor film '{$film->title}' (" . strtoupper($film->subject_type) . ") dari {$source} API.",
                'Film',
                $film->id
            );

            return response()->json([
                'status' => 'success',
                'message' => "Film '{$film->title}' berhasil diimpor ke katalog Faiilmov!",
                'film' => [
                    'id' => $film->id,
                    'title' => $film->title,
                    'slug' => $film->slug,
                    'poster_url' => $film->poster_url,
                    'subject_type' => $film->subject_type,
                    'edit_url' => route('admin.films.edit', $film->id),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error("Import external film error for {$subjectId}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal mengimpor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import multiple selected films in batch
     */
    public function importBatch(Request $request, MovieBoxService $movieBox, AnichinService $anichin)
    {
        $items = $request->input('items', []);
        if (!is_array($items) || empty($items)) {
            return response()->json(['status' => 'error', 'message' => 'Daftar film yang dipilih kosong.'], 422);
        }

        $importedCount = 0;
        $failedCount = 0;
        $importedFilms = [];

        foreach ($items as $item) {
            $subjectId = (string)($item['subject_id'] ?? '');
            $source = $item['source'] ?? 'moviebox';
            if (!$subjectId) continue;

            try {
                if (str_starts_with($subjectId, 'anichin:')) {
                    $parts = explode(':', $subjectId);
                    $dracinSource = $parts[1] ?? 'dramabox';
                    $rawId = $parts[2] ?? '';
                    $detail = $anichin->getDetail($dracinSource, $rawId);
                    if (empty($detail) || !is_array($detail)) {
                        $detail = [
                            'id' => $rawId,
                            'title' => $item['title'] ?? '',
                            'posterImg' => $item['poster_url'] ?? null,
                        ];
                    }
                    $film = $anichin->syncItemToFilmModel($dracinSource, $detail, true);
                } else {
                    $detail = null;
                    try {
                        $detail = $movieBox->getDetails($subjectId);
                    } catch (\Throwable $e) {}

                    if (empty($detail) || !is_array($detail)) {
                        $detail = [
                            'subjectId' => $subjectId, 
                            'title' => $item['title'] ?? '',
                            'cover' => $item['poster_url'] ?? null,
                            'subjectType' => ($item['subject_type'] ?? '') === 'series' ? 2 : 1,
                        ];
                    }
                    $film = Film::fromApiData($detail, true);
                }

                if ($film) {
                    $importedCount++;
                    $importedFilms[$subjectId] = [
                        'id' => $film->id,
                        'title' => $film->title,
                        'edit_url' => route('admin.films.edit', $film->id),
                    ];
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        AdminActivityLog::log(
            'imported_batch_external_films',
            "Mengimpor massal {$importedCount} film dari provider API ({$failedCount} gagal)."
        );

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil mengimpor {$importedCount} film ke katalog Faiilmov!" . ($failedCount > 0 ? " ({$failedCount} gagal)" : ""),
            'imported_count' => $importedCount,
            'failed_count' => $failedCount,
            'films' => $importedFilms,
        ]);
    }
}
