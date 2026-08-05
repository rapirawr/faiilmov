<?php

namespace App\Services;

use App\Models\Film;
use App\Models\SearchLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FilmSearchService
{
    // Minimum query length to avoid returning thousands of irrelevant results
    const MIN_QUERY_LENGTH = 2;

    /**
     * Perform a ranked, relevance-ordered film search with full-text and normalized hyphen/space support.
     * Ranking: exact match > starts with > normalized match (hyphen/space tolerant) > contains / full-text
     *
     * @param string      $query      Search keyword
     * @param array       $filters    ['genre' => slug, 'type' => movie|series, 'min_rating' => float, 'sort' => string]
     * @param int         $perPage
     * @param string|null $ip
     * @return LengthAwarePaginator|null  null if query too short
     */
    public function search(string $query, array $filters = [], int $perPage = 30, ?string $ip = null): ?LengthAwarePaginator
    {
        $query = trim($query);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return null;
        }

        $results = $this->buildQuery($query, $filters, $perPage);

        // Log the search
        $resultCount = $results->total();
        $this->logSearch($query, $resultCount, $ip);

        return $results;
    }

    /**
     * Build the search query with ranked relevance ordering.
     */
    private function buildQuery(string $query, array $filters, int $perPage): LengthAwarePaginator
    {
        $cleanQ = $this->sanitize($query);
        $normalizedQ = str_replace(['and', 'dan', '&', '-', ' ', ':', '.', "'", '"', '!', '?'], '', strtolower($cleanQ));
        $sqlNormalizeTitle = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, 'and', ''), 'dan', ''), '&', ''), '-', ''), ' ', ''), ':', ''), '.', ''), '!', ''), '?', ''))";

        $filmQuery = Film::with('genres')
            ->selectRaw("films.*, 
                CASE 
                    WHEN LOWER(title) = LOWER(?) THEN 100
                    WHEN LOWER(title) LIKE ? THEN 90
                    WHEN {$sqlNormalizeTitle} LIKE ? THEN 80
                    WHEN LOWER(title) LIKE ? THEN 70
                    WHEN {$sqlNormalizeTitle} LIKE ? THEN 60
                    ELSE 40
                END as relevance_score",
                [
                    $cleanQ,
                    strtolower($cleanQ) . '%',
                    $normalizedQ . '%',
                    '%' . strtolower($cleanQ) . '%',
                    '%' . $normalizedQ . '%',
                ]
            )
            ->where(function ($sub) use ($cleanQ, $normalizedQ, $sqlNormalizeTitle) {
                $sub->where('title', 'LIKE', '%' . $cleanQ . '%')
                    ->orWhereRaw("{$sqlNormalizeTitle} LIKE ?", ['%' . $normalizedQ . '%']);
            });

        // Apply optional filters (genre, type, rating)
        if (!empty($filters['genre'])) {
            $filmQuery->whereHas('genres', fn($q) => $q->where('slug', $filters['genre']));
        }

        if (!empty($filters['type'])) {
            $filmQuery->where('subject_type', $filters['type']);
        }

        if (!empty($filters['min_rating'])) {
            $filmQuery->where('rating', '>=', (float)$filters['min_rating']);
        }

        // Sort by relevance first, then user-chosen sort as tiebreaker
        $sort = $filters['sort'] ?? 'relevance';
        if ($sort === 'relevance' || !in_array($sort, ['rating_desc', 'title_asc', 'latest'])) {
            $filmQuery->orderByDesc('relevance_score')->orderByDesc('rating');
        } elseif ($sort === 'rating_desc') {
            $filmQuery->orderByDesc('rating');
        } elseif ($sort === 'title_asc') {
            $filmQuery->orderBy('title');
        } else {
            $filmQuery->orderByDesc('release_year')->orderByDesc('id');
        }

        return $filmQuery->paginate($perPage)->withQueryString();
    }

    /**
     * Return autocomplete suggestions (max 8) — supports ampersand & symbol normalization.
     * Cached per query for 5 minutes.
     */
    public function autocomplete(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        $clean = $this->sanitize($query);
        $normalized = str_replace(['and', 'dan', '&', '-', ' ', ':', '.', "'", '"', '!', '?'], '', strtolower($clean));
        $sqlNormalizeTitle = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, 'and', ''), 'dan', ''), '&', ''), '-', ''), ' ', ''), ':', ''), '.', ''), '!', ''), '?', ''))";
        $cacheKey = 'autocomplete_' . md5($clean);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($clean, $normalized, $sqlNormalizeTitle) {
            $matches = Film::select('id', 'title', 'slug', 'release_year', 'poster_url', 'subject_type', 'rating')
                ->where(function ($sub) use ($clean, $normalized, $sqlNormalizeTitle) {
                    $sub->where('title', 'LIKE', '%' . $clean . '%')
                        ->orWhereRaw("{$sqlNormalizeTitle} LIKE ?", ['%' . $normalized . '%']);
                })
                ->orderByDesc('rating')
                ->limit(8)
                ->get();

            return $matches->map(function ($film) {
                return [
                    'id'           => $film->id,
                    'title'        => $film->title,
                    'slug'         => $film->slug,
                    'year'         => $film->release_year,
                    'poster'       => $film->thumbnail_url,
                    'type'         => $film->subject_type,
                    'rating'       => $film->rating,
                    'url'          => route('film.show', $film->slug),
                ];
            })->values()->toArray();
        });
    }

    /**
     * Sanitize query string to prevent SQL injection and strip special chars.
     */
    public function sanitize(string $query): string
    {
        $query = preg_replace('/[+\-><\(\)~*"@]+/', ' ', $query);
        $query = preg_replace('/\s+/', ' ', $query);
        return trim(strip_tags($query));
    }

    /**
     * Log search query for analytics.
     */
    private function logSearch(string $query, int $resultCount, ?string $ip): void
    {
        try {
            SearchLog::create([
                'query'        => mb_substr($query, 0, 255),
                'result_count' => $resultCount,
                'ip_address'   => $ip,
                'user_id'      => Auth::id(),
            ]);
        } catch (\Exception $e) {
            // Silently fail — search logging must never break the main flow
        }
    }
}
