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
            'unrated' => Film::whereNull('content_rating')->count(),
            'trash' => count($trashedFilms),
        ];

        return view('admin.films.index', compact('films', 'genres', 'trashedFilms', 'stats'));
    }

    public function create()
    {
        $genres = Genre::orderBy('name')->get();
        $actors = Actor::orderBy('name')->get();
        return view('admin.films.create', compact('genres', 'actors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:2099',
            'duration_minutes' => 'nullable|integer|min:1',
            'rating' => 'nullable|numeric|min:0|max:10',
            'subject_type' => 'required|in:movie,series',
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
        ]);

        $posterUrl = $request->poster_url;
        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $posterUrl = Storage::url($path);
        }

        $film = Film::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
            'synopsis' => $validated['synopsis'] ?? null,
            'release_year' => $validated['release_year'] ?? date('Y'),
            'duration_minutes' => $validated['duration_minutes'] ?? 120,
            'rating' => $validated['rating'] ?? 0.0,
            'subject_type' => $validated['subject_type'],
            'content_rating' => $validated['content_rating'] ?? null,
            'max_resolution' => $validated['max_resolution'] ?? '1080P',
            'view_count' => $validated['view_count'] ?? 0,
            'trailer_url' => $validated['trailer_url'] ?? null,
            'poster_url' => $posterUrl ?: 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600',
            'backdrop_url' => $validated['backdrop_url'] ?? null,
        ]);

        if (!empty($validated['genres'])) {
            $film->genres()->sync($validated['genres']);
        }

        if (!empty($validated['actors'])) {
            $actorData = [];
            foreach ($validated['actors'] as $actorId) {
                $charName = $request->input("actor_characters.{$actorId}", null);
                $actorData[$actorId] = ['character_name' => $charName];
            }
            $film->actors()->sync($actorData);
        }

        AdminActivityLog::log('created_film', "Menambahkan film baru: {$film->title}", 'Film', $film->id);

        return redirect()->route('admin.films.index')->with('success', "Film '{$film->title}' berhasil ditambahkan.");
    }

    public function edit(Film $film)
    {
        $film->load(['genres', 'actors', 'seasons.episodes']);
        $genres = Genre::orderBy('name')->get();
        $actors = Actor::orderBy('name')->get();

        return view('admin.films.edit', compact('film', 'genres', 'actors'));
    }

    public function update(Request $request, Film $film)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'release_year' => 'nullable|integer|min:1900|max:2099',
            'duration_minutes' => 'nullable|integer|min:1',
            'rating' => 'nullable|numeric|min:0|max:10',
            'subject_type' => 'required|in:movie,series',
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
        ]);

        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $validated['poster_url'] = Storage::url($path);
        }

        $film->update([
            'title' => $validated['title'],
            'synopsis' => $validated['synopsis'] ?? $film->synopsis,
            'release_year' => $validated['release_year'] ?? $film->release_year,
            'duration_minutes' => $validated['duration_minutes'] ?? $film->duration_minutes,
            'rating' => $validated['rating'] ?? $film->rating,
            'subject_type' => $validated['subject_type'],
            'content_rating' => $validated['content_rating'] ?? $film->content_rating,
            'max_resolution' => $validated['max_resolution'] ?? $film->max_resolution,
            'view_count' => $validated['view_count'] ?? $film->view_count,
            'trailer_url' => $validated['trailer_url'] ?? $film->trailer_url,
            'poster_url' => $validated['poster_url'] ?? $film->poster_url,
            'backdrop_url' => $validated['backdrop_url'] ?? $film->backdrop_url,
        ]);

        if (isset($validated['genres'])) {
            $film->genres()->sync($validated['genres']);
        }

        if (isset($validated['actors'])) {
            $actorData = [];
            foreach ($validated['actors'] as $actorId) {
                $charName = $request->input("actor_characters.{$actorId}", null);
                $actorData[$actorId] = ['character_name' => $charName];
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

        return back()->with('success', "Auto-rate berhasil! {$updatedCount} {$scopeText} telah dikategorikan secara otomatis.");
    }
}
