<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionFilm;
use App\Models\CollectionWatchOrder;
use App\Models\Film;
use App\Services\CollectionPromptService;
use App\Services\CollectionSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class CollectionController extends Controller
{
    /**
     * Browse Collections Unified Hub
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');
        $user = Auth::user();

        if ($type === 'mine') {
            if (!$user) {
                return redirect()->route('login')->with('error', 'Silakan masuk untuk melihat koleksi Anda.');
            }
            $query = Collection::where('created_by', $user->id)
                ->withCount('films')
                ->orderByDesc('updated_at');
        } elseif ($type === 'franchise') {
            $query = Collection::published()
                ->where('type', 'auto')
                ->withCount('films')
                ->orderByDesc('films_count');
        } elseif ($type === 'community') {
            $query = Collection::published()
                ->whereIn('type', ['prompt', 'manual'])
                ->withCount('films')
                ->orderByDesc('updated_at');
        } else {
            // 'all' public
            $query = Collection::published()
                ->withCount('films')
                ->orderByDesc('films_count');
        }

        $collections = $query->paginate(24)->withQueryString();

        // Featured Hero Collection for 'all' / 'franchise' tab
        $featuredCollection = null;
        if (in_array($type, ['all', 'franchise'])) {
            $featuredCollection = Collection::published()
                ->withCount('films')
                ->whereNotNull('cover_image')
                ->orderByDesc('films_count')
                ->first();
        }

        $myCollectionsCount = $user ? Collection::where('created_by', $user->id)->count() : 0;

        return view('collections.index', compact(
            'collections', 
            'featuredCollection', 
            'type', 
            'myCollectionsCount'
        ));
    }

    /**
     * Collection Detail Page
     */
    public function show(string $slug, CollectionSuggestionService $suggestionService)
    {
        $user = Auth::user();

        $collection = Collection::where('slug', $slug)
            ->with(['creator', 'films.genres'])
            ->firstOrFail();

        // Privacy & Takedown Authorization
        if ($collection->isTakenDown()) {
            if (!$user || (!$user->is_admin && $collection->created_by !== $user->id)) {
                abort(404, 'Koleksi ini telah di-takedown oleh administrator karena tidak memenuhi pedoman komunitas.');
            }
        } elseif (!$collection->isAccessibleBy($user)) {
            abort(403, 'Koleksi ini bersifat pribadi atau masih dalam draf oleh pemiliknya.');
        }

        $isOwner = $collection->isOwner($user);
        $canEdit = $collection->canBeEditedBy($user);

        // Fetch watch orders
        $releaseOrders = $collection->releaseWatchOrders()->with('film')->get();
        $chronologicalOrders = $collection->chronologicalWatchOrders()->with('film')->get();

        // If no official franchise watch order exists, derive custom watch order from collection_films sequence
        if ($chronologicalOrders->isEmpty() && $collection->films->isNotEmpty()) {
            $chronologicalOrders = $collection->films->map(function ($film, $idx) {
                return (object)[
                    'sequence' => $film->pivot->sequence ?: ($idx + 1),
                    'note' => $film->pivot->note,
                    'film' => $film,
                ];
            });
        }

        $hasWatchOrders = $releaseOrders->isNotEmpty() || $chronologicalOrders->isNotEmpty();

        // Suggestions for adding more films
        $suggestions = collect();
        if ($collection->films->count() >= 2) {
            $suggestions = $suggestionService->suggestAdditions($collection, 6);
        }

        return view('collections.show', compact(
            'collection', 
            'releaseOrders', 
            'chronologicalOrders', 
            'hasWatchOrders', 
            'suggestions',
            'isOwner',
            'canEdit'
        ));
    }

    /**
     * Store new manual user collection
     * POST /collections
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:published,private,draft',
            'initial_film_id' => 'nullable|exists:films,id',
        ]);

        $name = trim($request->input('name'));
        $slug = Str::slug($name) . '-' . Str::random(5);

        $initialFilm = $request->input('initial_film_id') ? Film::find($request->input('initial_film_id')) : null;
        $coverImage = $initialFilm ? ($initialFilm->backdrop_url ?: $initialFilm->poster_url) : null;

        $collection = Collection::create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'manual',
            'description' => $request->input('description'),
            'cover_image' => $coverImage,
            'status' => $request->input('status', 'published'),
            'created_by' => $user->id,
            'custom_watch_order_enabled' => true,
        ]);

        if ($initialFilm) {
            CollectionFilm::create([
                'collection_id' => $collection->id,
                'film_id' => $initialFilm->id,
                'sequence' => 1,
                'added_by' => 'user',
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'collection' => $collection,
                'redirect_url' => route('collections.edit', $collection->slug),
            ]);
        }

        return redirect()->route('collections.edit', $collection->slug)
            ->with('success', "Koleksi '{$collection->name}' berhasil dibuat! Tambahkan film dan atur urutan nonton di Studio.");
    }

    /**
     * Dedicated Collection Studio Editor
     * GET /collections/{slug}/edit
     */
    public function edit(string $slug)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $collection = Collection::where('slug', $slug)
            ->with(['creator', 'films.genres'])
            ->firstOrFail();

        if (!$collection->canBeEditedBy($user)) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit koleksi pengguna ini.');
        }

        $initialFilms = $collection->films->map(function ($f, $idx) {
            return [
                'id' => $f->id,
                'title' => $f->title,
                'poster_url' => $f->poster_url,
                'release_year' => $f->release_year,
                'rating' => $f->rating,
                'duration_minutes' => $f->duration_minutes,
                'genres' => $f->genres->pluck('name')->toArray(),
                'sequence' => (int)($f->pivot->sequence ?: ($idx + 1)),
                'note' => (string)($f->pivot->note ?? ''),
            ];
        })->values()->toArray();

        return view('collections.edit', compact('collection', 'initialFilms'));
    }

    /**
     * Update collection metadata & privacy status
     * PUT /collections/{id}
     */
    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        $collection = Collection::findOrFail($id);

        if (!$collection->canBeEditedBy($user)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengedit koleksi pengguna ini.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1500',
            'status' => 'required|in:published,private,draft',
            'cover_image' => 'nullable|string|max:500',
        ]);

        $collection->update([
            'name' => trim($request->input('name')),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
            'cover_image' => $request->input('cover_image') ?: $collection->cover_image,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perubahan koleksi berhasil disimpan!',
            'collection' => $collection,
        ]);
    }

    /**
     * Search catalog films for instant addition in Studio
     * GET /collections/api/search-films
     */
    public function searchCatalogFilms(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $films = Film::where('title', 'LIKE', "%{$q}%")
            ->orWhereHas('genres', fn($g) => $g->where('name', 'LIKE', "%{$q}%"))
            ->with('genres')
            ->orderByDesc('rating')
            ->limit(12)
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'title' => $f->title,
                'poster_url' => $f->poster_url,
                'release_year' => $f->release_year,
                'rating' => $f->rating,
                'genres' => $f->genres->pluck('name')->toArray(),
            ]);

        return response()->json($films);
    }

    /**
     * Add film to collection
     * POST /collections/{id}/films
     */
    public function addFilm(Request $request, int $id)
    {
        $user = Auth::user();
        $collection = Collection::findOrFail($id);

        if (!$collection->canBeEditedBy($user)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengubah isi koleksi ini.'], 403);
        }

        $request->validate([
            'film_id' => 'required|exists:films,id',
            'note' => 'nullable|string|max:255',
        ]);

        $filmId = (int)$request->input('film_id');
        $film = Film::findOrFail($filmId);

        // Check if already in collection
        $exists = CollectionFilm::where('collection_id', $id)->where('film_id', $filmId)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Film sudah ada di dalam koleksi ini.'], 422);
        }

        $maxSeq = CollectionFilm::where('collection_id', $id)->max('sequence') ?: 0;
        $nextSeq = $maxSeq + 1;

        $cf = CollectionFilm::create([
            'collection_id' => $id,
            'film_id' => $filmId,
            'sequence' => $nextSeq,
            'note' => $request->input('note'),
            'added_by' => 'user',
        ]);

        // Auto update cover image if collection has none
        if (empty($collection->cover_image)) {
            $collection->update(['cover_image' => $film->backdrop_url ?: $film->poster_url]);
        }

        return response()->json([
            'success' => true,
            'message' => "Film '{$film->title}' berhasil ditambahkan ke koleksi!",
            'film' => [
                'id' => $film->id,
                'title' => $film->title,
                'poster_url' => $film->poster_url,
                'release_year' => $film->release_year,
                'rating' => $film->rating,
                'duration_minutes' => $film->duration_minutes,
                'genres' => $film->genres->pluck('name')->toArray(),
                'sequence' => $nextSeq,
                'note' => (string)$cf->note,
            ],
        ]);
    }

    /**
     * Remove film from collection
     * DELETE /collections/{id}/films/{filmId}
     */
    public function removeFilm(Request $request, int $id, int $filmId)
    {
        $user = Auth::user();
        $collection = Collection::findOrFail($id);

        if (!$collection->canBeEditedBy($user)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengubah isi koleksi ini.'], 403);
        }

        CollectionFilm::where('collection_id', $id)->where('film_id', $filmId)->delete();
        CollectionWatchOrder::where('collection_id', $id)->where('film_id', $filmId)->delete();

        // Re-index sequences
        $remaining = CollectionFilm::where('collection_id', $id)->orderBy('sequence')->get();
        foreach ($remaining as $idx => $item) {
            $item->update(['sequence' => $idx + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Film berhasil dihapus dari koleksi.',
        ]);
    }

    /**
     * Save Drag & Drop Reordered Watch Order List
     * POST /collections/{id}/reorder
     */
    public function reorder(Request $request, int $id)
    {
        $user = Auth::user();
        $collection = Collection::findOrFail($id);

        if (!$collection->canBeEditedBy($user)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengubah urutan koleksi ini.'], 403);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.film_id' => 'required|integer|exists:films,id',
            'items.*.sequence' => 'required|integer',
            'items.*.note' => 'nullable|string|max:255',
        ]);

        $items = $request->input('items');

        foreach ($items as $item) {
            $fId = (int)$item['film_id'];
            $seq = (int)$item['sequence'];
            $note = !empty($item['note']) ? trim((string)$item['note']) : null;

            // 1. Update collection_films sequence & note
            CollectionFilm::where('collection_id', $id)
                ->where('film_id', $fId)
                ->update([
                    'sequence' => $seq,
                    'note' => $note,
                ]);

            // 2. Sync to collection_watch_orders (chronological / custom order)
            CollectionWatchOrder::updateOrCreate(
                [
                    'collection_id' => $id,
                    'film_id' => $fId,
                    'order_type' => 'chronological',
                ],
                [
                    'sequence' => $seq,
                    'note' => $note,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan nonton berhasil diperbarui dan disimpan!',
        ]);
    }

    /**
     * Delete user collection
     * DELETE /collections/{id}
     */
    public function destroy(int $id)
    {
        $user = Auth::user();
        $collection = Collection::findOrFail($id);

        if (!$collection->canBeEditedBy($user) && !($user && $user->is_admin)) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus koleksi ini.');
        }

        $name = $collection->name;
        $collection->films()->detach();
        $collection->watchOrders()->delete();
        $collection->delete();

        return redirect()->route('collections.index', ['type' => 'mine'])
            ->with('success', "Koleksi '{$name}' telah dihapus.");
    }

    /**
     * Create prompt collection from natural language prompt
     */
    public function fromPrompt(Request $request, CollectionPromptService $promptService)
    {
        $request->validate([
            'prompt' => 'required|string|min:3|max:500',
        ]);

        $prompt = trim($request->input('prompt'));

        try {
            $collection = $promptService->createFromPrompt($prompt, Auth::user());

            return response()->json([
                'success' => true,
                'collection' => $collection,
                'redirect_url' => route('collections.edit', $collection->slug),
            ]);
        } catch (Exception $e) {
            Log::error('Collection prompt generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal meracik koleksi dari AI: ' . $e->getMessage(),
            ], 500);
        }
    }
}
