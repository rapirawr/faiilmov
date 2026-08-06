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

        return view('admin.films.index', compact('films', 'genres', 'trashedFilms'));
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
            'trailer_url' => 'nullable|url',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'poster_url' => 'nullable|string',
            'backdrop_url' => 'nullable|string',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
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
            'trailer_url' => $validated['trailer_url'] ?? null,
            'poster_url' => $posterUrl ?: 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600',
            'backdrop_url' => $validated['backdrop_url'] ?? null,
        ]);

        if (!empty($validated['genres'])) {
            $film->genres()->sync($validated['genres']);
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
            'trailer_url' => 'nullable|url',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'poster_url' => 'nullable|string',
            'backdrop_url' => 'nullable|string',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
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
            'trailer_url' => $validated['trailer_url'] ?? $film->trailer_url,
            'poster_url' => $validated['poster_url'] ?? $film->poster_url,
            'backdrop_url' => $validated['backdrop_url'] ?? $film->backdrop_url,
        ]);

        if (isset($validated['genres'])) {
            $film->genres()->sync($validated['genres']);
        }

        AdminActivityLog::log('updated_film', "Mengubah data film: {$film->title}", 'Film', $film->id);

        return redirect()->route('admin.films.index')->with('success', "Film '{$film->title}' berhasil diperbarui.");
    }

    public function destroy(Film $film)
    {
        $title = $film->title;
        $id = $film->id;

        $film->delete(); // Soft delete

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
}
