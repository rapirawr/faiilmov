<?php

namespace App\Services;

use App\Models\Film;
use App\Models\SearchLog;
use App\Models\Genre;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FilmSearchService
{
    const MIN_QUERY_LENGTH = 2;

    public function __construct(
        private NvidiaAiService $nvidia,
        private MovieBoxService $movieBox
    ) {}

    public function search(string $query, array $filters = [], int $perPage = 30, ?string $ip = null): ?LengthAwarePaginator
    {
        $query = trim($query);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return null;
        }

        // 1. Direct Local DB SQL Match (Title, Actor, Synopsis)
        $results = $this->buildQuery($query, $filters, $perPage);

        // 2. If results are sparse (< 5), attempt Fuzzy & Token Local Match
        if ($results->total() < 5) {
            $fuzzyResults = $this->fuzzyLocalSearch($query, $filters, $perPage);
            if ($fuzzyResults && $fuzzyResults->total() > $results->total()) {
                $results = $fuzzyResults;
            }
        }

        // 3. If results are still sparse (< 3), fetch Live data from MovieBox API & Sync to DB
        if ($results->total() < 3) {
            $this->fetchAndSyncFromMovieBox($query);
            // Re-query local DB after sync
            $results = $this->buildQuery($query, $filters, $perPage);
            if ($results->total() < 3) {
                $fuzzyResults = $this->fuzzyLocalSearch($query, $filters, $perPage);
                if ($fuzzyResults && $fuzzyResults->total() > $results->total()) {
                    $results = $fuzzyResults;
                }
            }
        }

        // NOTE: AI Search interpretation is NEVER used to replace main title matching results.
        // It is exposed separately via getAiRecommendations() for a dedicated recommendation section.

        $this->logSearch($query, $results->total(), $ip);

        return $results;
    }

    /**
     * Live Upstream Search from MovieBox API and Batch Sync to Local DB
     */
    public function fetchAndSyncFromMovieBox(string $query): void
    {
        $cleanQ = $this->sanitize($query);
        $cacheKey = 'mb_live_sync_search_' . md5($cleanQ);

        // Avoid spamming upstream API repeatedly, but do NOT block retry if 0 results synced
        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            $searchTerms = $this->expandSearchTerms($cleanQ);
            $syncedAny = false;

            foreach ($searchTerms as $term) {
                $apiData = $this->movieBox->search($term, 1);
                if (!empty($apiData)) {
                    $subjects = Film::extractSearchSubjects($apiData);
                    if (!empty($subjects)) {
                        Film::syncFromApiBatch($subjects);
                        $syncedAny = true;
                    }
                }
            }

            if ($syncedAny) {
                Cache::put($cacheKey, true, 600);
                return;
            }

            // Short 15s throttle if no subjects found to allow quick retries
            Cache::put($cacheKey, true, 15);
        } catch (\Exception $e) {
            Log::debug("Live MovieBox sync search failed for '{$cleanQ}': " . $e->getMessage());
            Cache::put($cacheKey, true, 15);
        }
    }

    /**
     * Expand query with synonyms and localized title mappings (e.g. "402 Rumah Sakit Angker Korea" -> "Gonjiam")
     */
    public function expandSearchTerms(string $query): array
    {
        $lower = strtolower(trim($query));
        $terms = [$query];

        $mappings = [
            '402 rumah sakit angker korea' => ['Gonjiam: Haunted Asylum', 'Gonjiam', '402', 'Haunted Asylum'],
            '402 rumah sakit'               => ['Gonjiam: Haunted Asylum', '402', 'Asylum'],
            'rumah sakit angker'           => ['Gonjiam: Haunted Asylum', 'Asylum', 'Haunted Hospital'],
            'rumah sakit korea'            => ['Gonjiam: Haunted Asylum', 'Doctor on the Edge', 'Hospital Playlist', 'Hospital'],
            'rumah sakit'                  => ['Hospital', 'Asylum', 'Medical'],
            'angker'                       => ['Haunted', 'Horror', 'Asylum'],
        ];

        foreach ($mappings as $key => $expansions) {
            if (str_contains($lower, $key) || str_contains($key, $lower)) {
                $terms = array_merge($terms, $expansions);
            }
        }

        return array_values(array_unique($terms));
    }

    public function getAiInterpretation(string $query): ?array
    {
        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return null;
        }

        return $this->nvidia->interpretQuery($query);
    }

    /**
     * Get AI Semantic Recommendations in a SEPARATE dataset (never replacing exact search)
     */
    public function getAiRecommendations(string $originalQuery, array $interpretation, array $excludeIds = [], int $limit = 6): Collection
    {
        $filmQuery = Film::forActiveProfile()->with('genres');

        if (!empty($excludeIds)) {
            $filmQuery->whereNotIn('id', $excludeIds);
        }

        $this->applyAiFilters($filmQuery, $interpretation);
        $this->applyAiMoodKeywords($filmQuery, $interpretation);

        return $filmQuery->orderByDesc('rating')->orderByDesc('release_year')->limit($limit)->get();
    }

    private function applyAiFilters($filmQuery, array $interpretation): void
    {
        $genres = $interpretation['genres'] ?? [];
        if (!empty($genres)) {
            $genreSlugs = array_map(fn($g) => Str::slug($g), $genres);
            $filmQuery->whereHas('genres', function ($q) use ($genreSlugs) {
                $q->whereIn('slug', $genreSlugs);
            });
        }

        $type = $interpretation['type'] ?? null;
        if ($type && in_array($type, ['movie', 'series', 'dracin'])) {
            $filmQuery->where('subject_type', $type);
        }

        $minRating = $interpretation['min_rating'] ?? null;
        if ($minRating !== null) {
            $filmQuery->where('rating', '>=', $minRating);
        }

        $yearRange = $interpretation['year_range'] ?? null;
        if ($yearRange) {
            if (isset($yearRange['min'])) {
                $filmQuery->where('release_year', '>=', $yearRange['min']);
            }
            if (isset($yearRange['max'])) {
                $filmQuery->where('release_year', '<=', $yearRange['max']);
            }
        }

        $similarToTitle = $interpretation['similar_to_title'] ?? null;
        if ($similarToTitle) {
            $similarFilm = Film::where('title', 'LIKE', '%' . $similarToTitle . '%')->first();
            if ($similarFilm) {
                $similarGenres = $similarFilm->genres->pluck('genre_id')->toArray();
                if (!empty($similarGenres)) {
                    $filmQuery->whereHas('genres', fn($q) => $q->whereIn('genres.id', $similarGenres));
                }
                $filmQuery->where('id', '!=', $similarFilm->id);
            }
        }
    }

    private function applyAiMoodKeywords($filmQuery, array $interpretation): void
    {
        $moodKeywords = $interpretation['mood_keywords'] ?? [];
        
        if (empty($moodKeywords)) {
            return;
        }

        $filmQuery->where(function ($q) use ($moodKeywords) {
            foreach ($moodKeywords as $keyword) {
                $q->orWhere('title', 'LIKE', '%' . $keyword . '%')
                  ->orWhere('synopsis', 'LIKE', '%' . $keyword . '%');
            }
        });
    }

    private function buildQuery(string $query, array $filters, int $perPage): LengthAwarePaginator
    {
        $cleanQ = $this->sanitize($query);
        $normalizedQ = str_replace(['and', 'dan', '&', '-', ' ', ':', '.', "'", '"', '!', '?'], '', strtolower($cleanQ));
        $sqlNormalizeTitle = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, 'and', ''), 'dan', ''), '&', ''), '-', ''), ' ', ''), ':', ''), '.', ''), '!', ''), '?', ''))";
        $expandedTerms = $this->expandSearchTerms($cleanQ);

        $filmQuery = Film::forActiveProfile()->with('genres')
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
            ->where(function ($sub) use ($cleanQ, $normalizedQ, $sqlNormalizeTitle, $expandedTerms) {
                $sub->where('title', 'LIKE', '%' . $cleanQ . '%')
                    ->orWhereRaw("{$sqlNormalizeTitle} LIKE ?", ['%' . $normalizedQ . '%']);

                foreach ($expandedTerms as $term) {
                    $sub->orWhere('title', 'LIKE', '%' . $term . '%')
                        ->orWhere('synopsis', 'LIKE', '%' . $term . '%');
                }

                // Tokenize words for multi-word queries (e.g. "spider man")
                $words = array_values(array_filter(explode(' ', strtolower($cleanQ)), fn($w) => strlen($w) >= 2));
                if (count($words) > 1) {
                    $sub->orWhere(function ($qWords) use ($words) {
                        foreach ($words as $word) {
                            $qWords->where('title', 'LIKE', '%' . $word . '%');
                        }
                    });
                }
            });

        if (!empty($filters['genre'])) {
            $filmQuery->whereHas('genres', fn($q) => $q->where('slug', $filters['genre']));
        }

        if (!empty($filters['type'])) {
            $filmQuery->where('subject_type', $filters['type']);
        }

        if (!empty($filters['min_rating'])) {
            $filmQuery->where('rating', '>=', (float)$filters['min_rating']);
        }

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
     * PHP-based Fuzzy Search for Typo Tolerance (Levenshtein, Similar Text, Soundex, & Word Tokens)
     */
    public function fuzzyLocalSearch(string $query, array $filters = [], int $perPage = 30): ?LengthAwarePaginator
    {
        $cleanQ = strtolower($this->sanitize($query));
        $queryWords = array_values(array_filter(explode(' ', $cleanQ), fn($w) => strlen($w) >= 2));

        if (empty($cleanQ)) {
            return null;
        }

        $candidatesQuery = Film::forActiveProfile()->with('genres', 'actors');

        if (!empty($filters['genre'])) {
            $candidatesQuery->whereHas('genres', fn($q) => $q->where('slug', $filters['genre']));
        }
        if (!empty($filters['type'])) {
            $candidatesQuery->where('subject_type', $filters['type']);
        }
        if (!empty($filters['min_rating'])) {
            $candidatesQuery->where('rating', '>=', (float)$filters['min_rating']);
        }

        $allFilms = $candidatesQuery->get();
        if ($allFilms->isEmpty()) {
            return null;
        }

        $matchedScores = [];
        $querySoundex = soundex($cleanQ);

        foreach ($allFilms as $film) {
            $titleLower = strtolower($film->title);
            $score = 0;

            // 1. Check exact word token matching or partial match
            $titleWords = array_values(array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/', '', $titleLower))));
            
            // 2. Levenshtein edit distance & similar_text on full title
            similar_text($cleanQ, $titleLower, $percent);
            $lev = levenshtein($cleanQ, substr($titleLower, 0, 255));
            $maxLen = max(strlen($cleanQ), strlen($titleLower));
            $levScore = $maxLen > 0 ? (1 - ($lev / $maxLen)) * 100 : 0;

            $maxSimilarity = max($percent, $levScore);

            // 3. Word token level fuzzy matching (e.g., "spidrman" vs "spider")
            $tokenMatchScore = 0;
            foreach ($queryWords as $qWord) {
                $bestWordScore = 0;
                foreach ($titleWords as $tWord) {
                    if (empty($tWord)) continue;
                    
                    if (str_contains($tWord, $qWord) || str_contains($qWord, $tWord)) {
                        $bestWordScore = max($bestWordScore, 85);
                    } else {
                        $wLev = levenshtein($qWord, substr($tWord, 0, 255));
                        $wMaxLen = max(strlen($qWord), strlen($tWord));
                        if ($wLev <= 2 && $wMaxLen > 3) {
                            $wScore = (1 - ($wLev / $wMaxLen)) * 100;
                            $bestWordScore = max($bestWordScore, $wScore);
                        }
                    }
                }
                $tokenMatchScore += $bestWordScore;
            }

            if (count($queryWords) > 0) {
                $avgTokenScore = $tokenMatchScore / count($queryWords);
                $maxSimilarity = max($maxSimilarity, $avgTokenScore);
            }

            // 4. Soundex matching
            if (soundex($titleLower) === $querySoundex) {
                $maxSimilarity = max($maxSimilarity, 70);
            }

            // 5. Multi-field match in Synopsis or Actor names
            if ($maxSimilarity < 45) {
                if (str_contains(strtolower($film->synopsis ?? ''), $cleanQ)) {
                    $maxSimilarity = 50;
                }
                foreach ($film->actors as $actor) {
                    if (str_contains(strtolower($actor->name ?? ''), $cleanQ)) {
                        $maxSimilarity = 65;
                        break;
                    }
                }
            }

            // Threshold: Similarity >= 45% or Edit Distance score >= 45
            if ($maxSimilarity >= 45) {
                $matchedScores[$film->id] = $maxSimilarity;
            }
        }

        if (empty($matchedScores)) {
            return null;
        }

        // Sort film IDs by score descending
        arsort($matchedScores);
        $filmIds = array_keys($matchedScores);

        // Retrieve films maintaining fuzzy score order
        $orderedFilms = Film::forActiveProfile()
            ->with('genres')
            ->whereIn('id', $filmIds)
            ->get()
            ->sortByDesc(fn($f) => $matchedScores[$f->id] ?? 0)
            ->values();

        // Manual Pagination
        $page = Paginator::resolveCurrentPage();
        $slice = $orderedFilms->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $orderedFilms->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    public function autocomplete(string $query, bool $isPopular = false): array
    {
        $query = trim($query);

        if ($isPopular || mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            if ($isPopular || mb_strlen($query) === 0) {
                return Cache::remember('autocomplete_popular_films_v2', now()->addMinutes(15), function () {
                    return Film::select('id', 'title', 'slug', 'release_year', 'poster_url', 'subject_type', 'rating')
                        ->orderByDesc('rating')
                        ->orderByDesc('id')
                        ->limit(4)
                        ->get()
                        ->map(function ($film) {
                            return [
                                'id'     => $film->id,
                                'title'  => $film->title,
                                'slug'   => $film->slug,
                                'year'   => $film->release_year,
                                'poster' => $film->thumbnail_url,
                                'type'   => $film->subject_type,
                                'rating' => $film->rating,
                                'url'    => route('film.show', $film->slug),
                            ];
                        })->values()->toArray();
                });
            }
            return [];
        }

        $clean = $this->sanitize($query);
        $normalized = str_replace(['and', 'dan', '&', '-', ' ', ':', '.', "'", '"', '!', '?'], '', strtolower($clean));
        $sqlNormalizeTitle = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, 'and', ''), 'dan', ''), '&', ''), '-', ''), ' ', ''), ':', ''), '.', ''), '!', ''), '?', ''))";
        $cacheKey = 'autocomplete_' . md5($clean);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($clean, $normalized, $sqlNormalizeTitle, $query) {
            $matches = Film::select('id', 'title', 'slug', 'release_year', 'poster_url', 'subject_type', 'rating')
                ->where(function ($sub) use ($clean, $normalized, $sqlNormalizeTitle) {
                    $sub->where('title', 'LIKE', '%' . $clean . '%')
                        ->orWhereRaw("{$sqlNormalizeTitle} LIKE ?", ['%' . $normalized . '%']);
                })
                ->orderByDesc('rating')
                ->limit(8)
                ->get();

            if ($matches->count() < 4) {
                $fuzzyPaginator = $this->fuzzyLocalSearch($query, [], 8);
                if ($fuzzyPaginator && $fuzzyPaginator->total() > 0) {
                    $fuzzyItems = collect($fuzzyPaginator->items());
                    $matches = $matches->concat($fuzzyItems)->unique('id')->take(8);
                }
            }

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

    public function getSimilarFilms(Film $film, int $limit = 6): Collection
    {
        $embedding = $film->ai_embeddings;
        
        if (!empty($embedding) && is_array($embedding)) {
            return $this->getSimilarByEmbedding($embedding, $film->id, $limit);
        }

        return $this->getSimilarByGenre($film, $limit);
    }

    private function getSimilarByEmbedding(array $sourceEmbedding, int $excludeId, int $limit): Collection
    {
        $allFilms = Film::where('id', '!=', $excludeId)
            ->whereNotNull('ai_embeddings')
            ->limit(500)
            ->get();

        if ($allFilms->isEmpty()) {
            return collect();
        }

        $similarities = [];
        foreach ($allFilms as $candidate) {
            $candidateEmbedding = $candidate->ai_embeddings;
            if (!empty($candidateEmbedding) && is_array($candidateEmbedding)) {
                $score = $this->nvidia->cosineSimilarity($sourceEmbedding, $candidateEmbedding);
                $similarities[] = [
                    'film' => $candidate,
                    'score' => $score,
                ];
            }
        }

        usort($similarities, fn($a, $b) => $b['score'] <=> $a['score']);

        return collect(array_slice(array_column($similarities, 'film'), 0, $limit));
    }

    private function getSimilarByGenre(Film $film, int $limit): Collection
    {
        $genreIds = $film->genres->pluck('id')->toArray();
        
        if (empty($genreIds)) {
            return Film::where('id', '!=', $film->id)
                ->orderByDesc('rating')
                ->limit($limit)
                ->get();
        }

        return Film::where('id', '!=', $film->id)
            ->whereHas('genres', fn($q) => $q->whereIn('genres.id', $genreIds))
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();
    }

    public function sanitize(string $query): string
    {
        $query = preg_replace('/[+\-><\(\)~*"@\'";\\\\#]+/', ' ', $query);
        $query = preg_replace('/\s+/', ' ', $query);
        return trim(strip_tags($query));
    }

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
        }
    }
}
