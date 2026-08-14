<?php

namespace App\Http\Controllers;

use App\Services\AnichinService;
use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DracinController extends Controller
{
    public function __construct(protected AnichinService $anichin)
    {
    }

    /**
     * Display Dracin pages:
     * - Catalog Grid Browser (/dracin)
     * - Vertical Player Feed (/dracin/{source}/{id})
     */
    public function index(Request $request, ?string $source = null, ?string $id = null)
    {
        $source = $source ?: $request->query('source', 'dramabox');
        $id = $id ?: $request->query('id');
        $initialEp = (int)$request->query('ep', 1);

        // If specific drama ID is provided, render the vertical feed player
        if ($id) {
            $feedItems = $this->getFeedWithFallback($source, 'foryou', 1);
            $activeDramaDetail = $this->getDetailWithFallback($source, $id);

            return view('dracin.player', [
                'initialSource' => $source,
                'initialId' => $id,
                'initialEp' => $initialEp,
                'initialFeed' => $feedItems,
                'initialActiveDetail' => $activeDramaDetail,
                'sourcesList' => AnichinService::SOURCES,
            ]);
        }

        // Otherwise render Dracin Catalog Grid Page (/dracin)
        $feedItems = $this->getFeedWithFallback($source, 'all', 1);

        return view('dracin.index', [
            'currentSource' => $source,
            'feedItems' => $feedItems,
            'sourcesList' => AnichinService::SOURCES,
        ]);
    }

    /**
     * API endpoint to search Dracin by query with source & local database fallback.
     */
    public function searchApi(Request $request): JsonResponse
    {
        $query = trim((string)$request->query('query', $request->query('q', '')));
        $source = (string)$request->query('source', 'dramabox');

        if (mb_strlen($query) < 1) {
            return response()->json(['items' => []]);
        }

        $items = [];

        // 1. Try external Anichin API
        try {
            $apiItems = $this->anichin->search($query, $source);
            if (!empty($apiItems) && is_array($apiItems)) {
                $items = $apiItems;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Dracin search API error for {$source}: " . $e->getMessage());
        }

        // 2. Search local DB Dracins and merge
        $localFilms = Film::where(function ($q) {
                $q->where('subject_type', 'dracin')
                  ->orWhere('moviebox_subject_id', 'like', 'anichin%');
            })
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('synopsis', 'LIKE', "%{$query}%");
            })
            ->take(25)
            ->get()
            ->map(function ($film) {
                $parts = explode(':', $film->moviebox_subject_id ?? '');
                $source = $parts[1] ?? 'dramabox';
                $rawId = $parts[2] ?? (string)$film->id;
                $epCount = $film->seasons->first()?->episodes->count() ?? 50;

                return [
                    'id'            => $rawId,
                    'dramaId'       => $rawId,
                    'title'         => $film->title,
                    'cover'         => $film->poster_url ?: $film->backdrop_url,
                    'posterImg'     => $film->poster_url,
                    'episodes'      => $epCount,
                    'totalEpisodes' => $epCount,
                    'source'        => $source,
                    'synopsis'      => $film->synopsis,
                ];
            })
            ->toArray();

        // Merge & normalize items with deduplication
        $seenIds = [];
        $merged = [];
        foreach (array_merge($items, $localFilms) as $it) {
            $idKey = (string)($it['id'] ?? $it['dramaId'] ?? $it['bookId'] ?? '');
            if (!$idKey || isset($seenIds[$idKey])) continue;
            $seenIds[$idKey] = true;

            $title = $it['title'] ?? $it['name'] ?? $it['bookName'] ?? 'Untitled Dracin';
            $cover = $it['posterImg'] ?? $it['cover'] ?? $it['poster'] ?? $it['horizontalCover'] ?? '';
            $epCount = (int)($it['episodes'] ?? $it['totalEpisodes'] ?? $it['chapterCount'] ?? 0);
            if ($epCount < 1) {
                $epCount = 50;
            }

            $merged[] = [
                'id'            => $idKey,
                'dramaId'       => $idKey,
                'title'         => $title,
                'cover'         => $cover,
                'posterImg'     => $cover,
                'episodes'      => $epCount,
                'totalEpisodes' => $epCount,
                'source'        => $it['source'] ?? $source,
                'synopsis'      => $it['synopsis'] ?? $it['description'] ?? $it['intro'] ?? '',
            ];
        }

        return response()->json([
            'query' => $query,
            'source' => $source,
            'items' => $merged,
        ]);
    }

    /**
     * API endpoint to get Dracin feed for a source and page.
     */
    public function feedApi(Request $request): JsonResponse
    {
        $source = $request->query('source', 'dramabox');
        $page = (int)$request->query('page', 1);
        $tab = $request->query('tab', 'all');

        $items = $this->getFeedWithFallback($source, $tab, $page);

        return response()->json([
            'source' => $source,
            'page' => $page,
            'items' => $items,
        ]);
    }

    /**
     * API endpoint to get detail & total episodes of a specific drama.
     */
    public function detailApi(string $source, string $id): JsonResponse
    {
        $detail = $this->getDetailWithFallback($source, $id);
        if (!$detail) {
            return response()->json([
                'id' => $id,
                'title' => 'Drama Short',
                'episodes' => 50,
                'cover' => '',
            ]);
        }

        return response()->json($detail);
    }

    /**
     * Helper: Fetch feed with local DB fallback if external API fails/times out.
     */
    protected function getFeedWithFallback(string $source, string $tab, int $page): array
    {
        try {
            if ($tab === 'trending') {
                $items = $this->anichin->getTrending($source);
            } elseif ($tab === 'latest') {
                $items = $this->anichin->getLatest($source, $page);
            } elseif ($tab === 'hotrank') {
                $items = $this->anichin->getHotRank($source);
            } else {
                $items = $this->anichin->getForYou($source, $page);
            }

            if (!empty($items)) {
                return $items;
            }
        } catch (\Exception $e) {}

        // Fallback to local DB Dracins
        return Film::where('subject_type', 'dracin')
            ->orWhere('moviebox_subject_id', 'like', 'anichin%')
            ->latest()
            ->take(30)
            ->get()
            ->map(function ($film) {
                $parts = explode(':', $film->moviebox_subject_id ?? '');
                $source = $parts[1] ?? 'dramabox';
                $rawId = $parts[2] ?? (string)$film->id;
                $epCount = $film->seasons->first()?->episodes->count() ?? 50;

                return [
                    'id' => $rawId,
                    'dramaId' => $rawId,
                    'title' => $film->title,
                    'cover' => $film->poster_url ?: $film->backdrop_url,
                    'posterImg' => $film->poster_url,
                    'episodes' => $epCount,
                    'totalEpisodes' => $epCount,
                    'source' => $source,
                    'synopsis' => $film->synopsis,
                ];
            })
            ->toArray();
    }

    /**
     * Helper: Fetch drama detail with local DB fallback.
     */
    protected function getDetailWithFallback(string $source, string $id): ?array
    {
        try {
            $detail = $this->anichin->getDetail($source, $id);
            if (!empty($detail) && !isset($detail['error'])) {
                if (empty($detail['episodes']) && empty($detail['totalEpisodes']) && empty($detail['chapterCount'])) {
                    $epCount = null;
                    $film = Film::where('moviebox_subject_id', "anichin:{$source}:{$id}")
                        ->orWhere('moviebox_subject_id', 'like', "%:{$id}")
                        ->first();
                    if ($film) {
                        $epCount = $film->seasons->first()?->episodes->count();
                    }
                    if (!$epCount) {
                        $feedItems = $this->getFeedWithFallback($source, 'foryou', 1);
                        foreach ($feedItems as $item) {
                            $itemId = (string)($item['id'] ?? $item['dramaId'] ?? '');
                            if ($itemId === (string)$id && !empty($item['episodes'])) {
                                $epCount = (int)$item['episodes'];
                                break;
                            }
                        }
                    }
                    $detail['episodes'] = $epCount ?: 50;
                    $detail['totalEpisodes'] = $detail['episodes'];
                }
                return $detail;
            }
        } catch (\Exception $e) {}

        // Look up local DB film
        $film = Film::with('seasons.episodes')
            ->where('moviebox_subject_id', "anichin:{$source}:{$id}")
            ->orWhere('moviebox_subject_id', 'like', "%:{$id}")
            ->orWhere('id', $id)
            ->orWhere('slug', $id)
            ->first();

        if ($film) {
            $epCount = $film->seasons->first()?->episodes->count() ?? 50;
            return [
                'id' => $id,
                'dramaId' => $id,
                'title' => $film->title,
                'cover' => $film->poster_url ?: $film->backdrop_url,
                'posterImg' => $film->poster_url,
                'episodes' => $epCount,
                'totalEpisodes' => $epCount,
                'synopsis' => $film->synopsis,
            ];
        }

        return [
            'id' => $id,
            'dramaId' => $id,
            'title' => 'Drama Short',
            'cover' => '',
            'episodes' => 50,
            'totalEpisodes' => 50,
            'synopsis' => '',
        ];
    }

    /**
     * API endpoint to record Dracin watch progress for logged-in user.
     */
    public function watchProgressApi(Request $request): JsonResponse
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return response()->json(['status' => 'guest']);
        }

        $source = $request->input('source', 'dramabox');
        $dramaId = (string)($request->input('dramaId') ?? $request->input('id') ?? '');
        $epNumber = (int)$request->input('episode', 1);
        $progressSeconds = (int)$request->input('progress_seconds', 0);
        $title = $request->input('title', 'Dracin');
        $posterUrl = $request->input('posterUrl');

        if (!$dramaId) {
            return response()->json(['error' => 'Missing dramaId'], 400);
        }

        $film = $this->anichin->syncItemToFilmModel($source, [
            'id' => $dramaId,
            'dramaId' => $dramaId,
            'title' => $title,
            'posterImg' => $posterUrl,
            'episodes' => $request->input('totalEpisodes', 50),
        ]);

        if (!$film) {
            return response()->json(['error' => 'Failed to sync film model'], 500);
        }

        $userId = \Illuminate\Support\Facades\Auth::id();
        $profileId = session('active_profile_id');

        \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $profileId, $film, $epNumber, $progressSeconds) {
            $history = \App\Models\WatchHistory::where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->where('film_id', $film->id)
                ->first();

            if ($history) {
                $isNewer = ($epNumber > $history->episode_number) ||
                           ($epNumber == $history->episode_number && $progressSeconds >= $history->progress_seconds);

                if ($isNewer) {
                    $history->update([
                        'season_number'    => 1,
                        'episode_number'   => $epNumber,
                        'progress_seconds' => $progressSeconds,
                    ]);
                }
                $history->touch();
            } else {
                \App\Models\WatchHistory::create([
                    'user_id'          => $userId,
                    'profile_id'       => $profileId,
                    'film_id'          => $film->id,
                    'season_number'    => 1,
                    'episode_number'   => $epNumber,
                    'progress_seconds' => $progressSeconds,
                ]);
            }
        });

        return response()->json(['status' => 'ok', 'film_id' => $film->id]);
    }
}
