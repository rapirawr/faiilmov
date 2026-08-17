<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionFilm;
use App\Models\Film;
use App\Models\FilmTag;
use App\Models\FilmEmbedding;
use App\Models\AdminActivityLog;
use App\Services\CollectionClusterService;
use App\Services\CollectionSuggestionService;
use App\Services\WatchOrderService;
use Illuminate\Http\Request;

class AdminCollectionController extends Controller
{
    /**
     * Admin Collections Index
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $type = $request->query('type', 'all');
        $search = $request->query('q');

        $query = Collection::withCount('films')->with(['creator', 'moderator'])->orderByDesc('updated_at');

        if ($status !== 'all') {
            if ($status === 'takedown') {
                $query->takedown();
            } else {
                $query->where('status', $status);
            }
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('source_tag', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                      $creatorQuery->where('name', 'LIKE', "%{$search}%")
                                   ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $collections = $query->paginate(20);

        // Stats
        $stats = [
            'total_collections' => Collection::count(),
            'published_count' => Collection::published()->count(),
            'draft_count' => Collection::draft()->count(),
            'private_count' => Collection::private()->count(),
            'takedown_count' => Collection::takedown()->count(),
            'user_created_count' => Collection::whereNotNull('created_by')->count(),
            'auto_count' => Collection::auto()->count(),
            'prompt_count' => Collection::fromPrompt()->count(),
        ];

        return view('admin.collections.index', compact('collections', 'stats', 'status', 'type', 'search'));
    }

    /**
     * API: Get collection list for Admin UI
     */
    public function apiList(Request $request)
    {
        $collections = Collection::withCount('films')
            ->with([
                'creator:id,name,email,role,created_at',
                'moderator:id,name',
                'watchOrders'
            ])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $collections,
        ]);
    }

    /**
     * Takedown Collection (Admin Moderation)
     */
    public function takedown(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);
        $reason = $request->input('reason', 'Melanggar Pedoman Komunitas Faiilmov');

        $collection->update([
            'status' => 'takedown',
            'takedown_reason' => $reason,
            'taken_down_at' => now(),
            'taken_down_by' => auth()->id(),
        ]);

        AdminActivityLog::log(
            'takedown_collection',
            "Melakukan takedown koleksi '{$collection->name}' (ID: {$collection->id}). Alasan: {$reason}",
            'Collection',
            $collection->id
        );

        return response()->json([
            'success' => true,
            'message' => "Koleksi '{$collection->name}' berhasil di-takedown.",
            'collection' => $collection->fresh(['creator:id,name,email', 'moderator:id,name']),
        ]);
    }

    /**
     * Restore Collection from Takedown
     */
    public function restore(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);
        $targetStatus = $request->input('status', 'draft'); // Restore to draft by default

        $collection->update([
            'status' => $targetStatus,
            'takedown_reason' => null,
            'taken_down_at' => null,
            'taken_down_by' => null,
        ]);

        AdminActivityLog::log(
            'restore_collection',
            "Mengembalikan koleksi yang di-takedown '{$collection->name}' (ID: {$collection->id}) ke status " . strtoupper($targetStatus),
            'Collection',
            $collection->id
        );

        return response()->json([
            'success' => true,
            'message' => "Koleksi '{$collection->name}' berhasil dipulihkan ke status {$targetStatus}.",
            'collection' => $collection->fresh(['creator:id,name,email']),
        ]);
    }

    /**
     * Trigger Auto Collections Rebuild
     */
    public function rebuild(Request $request, CollectionClusterService $clusterService)
    {
        $threshold = (int)$request->input('threshold', 5);

        try {
            $result = $clusterService->generateAutoCollections($threshold);

            AdminActivityLog::log(
                'rebuilt_collections',
                "Menjalankan rebuild auto-collections: Created {$result['created']}, Updated {$result['updated']}, Published {$result['published']}, Draft {$result['draft']}.",
                'Collection'
            );

            return response()->json([
                'success' => true,
                'message' => "Rebuild koleksi cerdas berhasil dijalankan!",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal rebuild: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle Publish Status
     */
    public function togglePublish($id)
    {
        $collection = Collection::findOrFail($id);
        $newStatus = $collection->status === 'published' ? 'draft' : 'published';
        $collection->update(['status' => $newStatus]);

        AdminActivityLog::log(
            'toggle_collection_status',
            "Mengubah status koleksi '{$collection->name}' menjadi " . strtoupper($newStatus),
            'Collection',
            $collection->id
        );

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => "Status koleksi berhasil diubah menjadi {$newStatus}."
        ]);
    }

    /**
     * Get Suggestions for Collection
     */
    public function suggestions($id, CollectionSuggestionService $suggestionService)
    {
        $collection = Collection::findOrFail($id);
        $candidates = $suggestionService->suggestAdditions($collection, 12);

        return response()->json([
            'success' => true,
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
            ],
            'suggestions' => $candidates->map(fn($f) => [
                'id' => $f->id,
                'title' => $f->title,
                'poster_url' => $f->poster_url,
                'release_year' => $f->release_year,
                'rating' => $f->rating,
                'similarity_score' => $f->similarity_score ?? null,
                'genres' => $f->genres->pluck('name')->toArray(),
            ]),
        ]);
    }

    /**
     * Add Film to Collection
     */
    public function addFilm(Request $request, $id)
    {
        $collection = Collection::findOrFail($id);
        $filmId = (int)$request->input('film_id');

        $film = Film::findOrFail($filmId);

        $exists = CollectionFilm::where('collection_id', $collection->id)
            ->where('film_id', $filmId)
            ->exists();

        if (!$exists) {
            CollectionFilm::create([
                'collection_id' => $collection->id,
                'film_id' => $filmId,
                'added_by' => 'admin',
            ]);

            AdminActivityLog::log(
                'added_film_to_collection',
                "Menambahkan film '{$film->title}' ke koleksi '{$collection->name}'",
                'Collection',
                $collection->id
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Film '{$film->title}' berhasil ditambahkan ke koleksi!",
            'films_count' => $collection->films()->count(),
        ]);
    }

    /**
     * Remove Film from Collection
     */
    public function removeFilm($id, $filmId)
    {
        $collection = Collection::findOrFail($id);
        CollectionFilm::where('collection_id', $collection->id)
            ->where('film_id', $filmId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Film berhasil dihapus dari koleksi.",
            'films_count' => $collection->films()->count(),
        ]);
    }

    /**
     * Generate Watch Order for Specific Collection
     */
    public function generateWatchOrder($id, WatchOrderService $watchOrderService)
    {
        $collection = Collection::findOrFail($id);

        try {
            $watchOrderService->generateSuggestedOrder($collection);

            return response()->json([
                'success' => true,
                'message' => "Watch order untuk '{$collection->name}' berhasil di-generate!",
                'orders_count' => $collection->watchOrders()->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal generate watch order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete Collection
     */
    public function destroy($id)
    {
        $collection = Collection::findOrFail($id);
        $name = $collection->name;
        $collection->delete();

        AdminActivityLog::log(
            'deleted_collection',
            "Menghapus koleksi '{$name}'",
            'Collection'
        );

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Koleksi '{$name}' berhasil dihapus."]);
        }

        return back()->with('success', "Koleksi '{$name}' berhasil dihapus.");
    }
}
