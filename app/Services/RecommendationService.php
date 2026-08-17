<?php

namespace App\Services;

use App\Models\Film;
use App\Models\WatchHistory;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function __construct(
        private NvidiaAiService $nvidia
    ) {}

    /**
     * Personalized recommendations based on user's watch history for active profile
     */
    public function getPersonalizedForUser(?User $user, $profileId = null, int $limit = 12): Collection
    {
        if (!$user) {
            return collect();
        }

        $history = WatchHistory::where('user_id', $user->id)
            ->where('profile_id', $profileId)
            ->with('film.genres')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        if ($history->isEmpty()) {
            return Film::forActiveProfile()->orderByDesc('rating')->limit($limit)->get();
        }

        $watchedIds = $history->pluck('film_id')->toArray();
        $genreScores = [];
        $recentlyWatched = null;

        foreach ($history as $record) {
            if (!$record->film) continue;

            if (!$recentlyWatched) {
                $recentlyWatched = $record->film;
            }

            foreach ($record->film->genres as $genre) {
                $genreScores[$genre->id] = ($genreScores[$genre->id] ?? 0) + 1;
            }
        }

        arsort($genreScores);
        $topGenreIds = array_slice(array_keys($genreScores), 0, 3);

        if (empty($topGenreIds)) {
            return Film::forActiveProfile()
                ->whereNotIn('id', $watchedIds)
                ->orderByDesc('rating')
                ->limit($limit)
                ->get();
        }

        $recommendations = Film::forActiveProfile()
            ->whereNotIn('id', $watchedIds)
            ->whereHas('genres', fn($q) => $q->whereIn('genres.id', $topGenreIds))
            ->orderByDesc('rating')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();

        if ($recommendations->count() < $limit && $recentlyWatched && !empty($recentlyWatched->ai_embeddings)) {
            $aiBased = $this->getSimilarByEmbedding(
                $recentlyWatched->ai_embeddings,
                array_merge([$recentlyWatched->id], $watchedIds),
                $limit - $recommendations->count()
            );

            $recommendations = $recommendations->merge($aiBased)->unique('id')->values();
        }

        if ($recommendations->count() < $limit) {
            $filler = Film::forActiveProfile()
                ->whereNotIn('id', $watchedIds)
                ->whereNotIn('id', $recommendations->pluck('id')->toArray())
                ->orderByDesc('rating')
                ->limit($limit - $recommendations->count())
                ->get();
            $recommendations = $recommendations->merge($filler);
        }

        return $recommendations;
    }

    /**
     * Get films highly relevant to a source film
     * Uses franchise title matching, shared actors, overlapping genres, and smart fallbacks
     */
    public function getSimilarForFilm(Film $film, array $excludeIds = [], int $limit = 12): Collection
    {
        $excludeIds = array_unique(array_merge([$film->id], $excludeIds));
        $genreIds = $film->genres ? $film->genres->pluck('id')->toArray() : [];
        $actorIds = $film->actors ? $film->actors->pluck('id')->toArray() : [];

        // 1. Extract clean title keywords for franchise/sequel matching
        $cleanTitle = strtolower(preg_replace('/[^\w\s]/u', '', $film->title));
        $stopWords = ['the', 'from', 'with', 'and', 'part', 'movie', 'series', 'brand', 'new', 'day', 'vol', 'ii', 'iii', 'season'];
        $titleKeywords = array_values(array_filter(
            explode(' ', $cleanTitle),
            fn($w) => strlen($w) >= 3 && !in_array($w, $stopWords)
        ));

        $candidates = Film::forActiveProfile()
            ->whereNotIn('id', $excludeIds)
            ->where('subject_type', $film->subject_type)
            ->where(function ($q) use ($genreIds, $actorIds, $titleKeywords) {
                if (!empty($titleKeywords)) {
                    foreach ($titleKeywords as $word) {
                        $q->orWhere('title', 'LIKE', '%' . $word . '%');
                    }
                }
                if (!empty($actorIds)) {
                    $q->orWhereHas('actors', fn($a) => $a->whereIn('actors.id', $actorIds));
                }
                if (!empty($genreIds)) {
                    $q->orWhereHas('genres', fn($g) => $g->whereIn('genres.id', $genreIds));
                }
            })
            ->with(['genres', 'actors'])
            ->limit(150)
            ->get();

        $scored = [];
        foreach ($candidates as $candidate) {
            $candGenreIds = $candidate->genres ? $candidate->genres->pluck('id')->toArray() : [];
            $candActorIds = $candidate->actors ? $candidate->actors->pluck('id')->toArray() : [];
            $candTitleLower = strtolower($candidate->title);

            $genreOverlap = count(array_intersect($genreIds, $candGenreIds));
            $actorOverlap = count(array_intersect($actorIds, $candActorIds));

            $titleMatchScore = 0;
            foreach ($titleKeywords as $kw) {
                if (str_contains($candTitleLower, $kw)) {
                    $titleMatchScore += 50; // Boost for franchise
                }
            }

            $totalScore = $titleMatchScore 
                        + ($actorOverlap * 30) 
                        + ($genreOverlap * 15) 
                        + (float)($candidate->rating * 2)
                        + min(10, ((int)$candidate->view_count) / 50);

            $scored[] = ['film' => $candidate, 'score' => $totalScore];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        $recommendations = collect(array_slice(array_column($scored, 'film'), 0, $limit));

        // If fewer than limit, fill with films of the same subject_type or top rated
        if ($recommendations->count() < $limit) {
            $currentIds = array_merge($excludeIds, $recommendations->pluck('id')->toArray());
            $filler = Film::forActiveProfile()
                ->whereNotIn('id', $currentIds)
                ->where('subject_type', $film->subject_type)
                ->when(!empty($genreIds), function($q) use ($genreIds) {
                    $q->whereHas('genres', fn($g) => $g->whereIn('genres.id', $genreIds));
                })
                ->orderByDesc('rating')
                ->orderByDesc('view_count')
                ->limit($limit - $recommendations->count())
                ->get();

            $recommendations = $recommendations->merge($filler);
        }

        if ($recommendations->count() < $limit) {
            $currentIds = array_merge($excludeIds, $recommendations->pluck('id')->toArray());
            $filler = Film::forActiveProfile()
                ->whereNotIn('id', $currentIds)
                ->where('subject_type', $film->subject_type)
                ->orderByDesc('view_count')
                ->orderByDesc('rating')
                ->limit($limit - $recommendations->count())
                ->get();

            $recommendations = $recommendations->merge($filler);
        }

        return $recommendations;
    }

    /**
     * Get films highly relevant to active profile's last watched film
     */
    public function getBecauseYouWatched(?User $user, $profileId = null, int $limit = 12): Collection
    {
        if (!$user) return collect();

        $lastWatchedQuery = WatchHistory::where('user_id', $user->id)
            ->has('film')
            ->with(['film.genres', 'film.actors'])
            ->orderByDesc('updated_at');

        if ($profileId) {
            $lastWatched = (clone $lastWatchedQuery)->where('profile_id', $profileId)->first() ?: $lastWatchedQuery->first();
        } else {
            $lastWatched = $lastWatchedQuery->first();
        }

        if (!$lastWatched || !$lastWatched->film) {
            return collect();
        }

        $watchedIds = WatchHistory::where('user_id', $user->id)->pluck('film_id')->toArray();

        return $this->getSimilarForFilm($lastWatched->film, $watchedIds, $limit);
    }

    private function getSimilarByEmbedding(array $sourceEmbedding, array $excludeIds, int $limit): Collection
    {
        $candidates = Film::forActiveProfile()
            ->whereNotIn('id', $excludeIds)
            ->whereNotNull('ai_embeddings')
            ->limit(500)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $scored = [];
        foreach ($candidates as $candidate) {
            $embedding = $candidate->ai_embeddings;
            if (!empty($embedding) && is_array($embedding)) {
                $score = $this->nvidia->cosineSimilarity($sourceEmbedding, $embedding);
                $scored[] = ['film' => $candidate, 'score' => $score];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return collect(array_slice(array_column($scored, 'film'), 0, $limit));
    }

    /**
     * "Coming Soon" - films not yet released but with future release dates
     */
    public function getComingSoon(int $limit = 12): Collection
    {
        return Film::forActiveProfile()
            ->where(function ($q) {
                $q->where('available_from', '>', now())
                  ->orWhere('release_year', '>', date('Y'));
            })
            ->orderBy('available_from')
            ->orderByDesc('release_year')
            ->limit($limit)
            ->get();
    }
}