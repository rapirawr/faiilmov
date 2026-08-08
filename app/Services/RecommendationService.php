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
     * Personalized recommendations based on user's watch history
     * Uses collaborative + content-based filtering
     */
    public function getPersonalizedForUser(?User $user, int $limit = 12): Collection
    {
        if (!$user) {
            return collect();
        }

        $history = WatchHistory::where('user_id', $user->id)
            ->with('film.genres')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        if ($history->isEmpty()) {
            return Film::orderByDesc('rating')->limit($limit)->get();
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
            return Film::whereNotIn('id', $watchedIds)
                ->orderByDesc('rating')
                ->limit($limit)
                ->get();
        }

        $recommendations = Film::whereNotIn('id', $watchedIds)
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
            $filler = Film::whereNotIn('id', $watchedIds)
                ->whereNotIn('id', $recommendations->pluck('id')->toArray())
                ->orderByDesc('rating')
                ->limit($limit - $recommendations->count())
                ->get();
            $recommendations = $recommendations->merge($filler);
        }

        return $recommendations;
    }

    /**
     * Get films similar to user's last watched film
     */
    public function getBecauseYouWatched(?User $user, int $limit = 12): Collection
    {
        if (!$user) return collect();

        $lastWatched = WatchHistory::where('user_id', $user->id)
            ->with('film')
            ->orderByDesc('updated_at')
            ->first();

        if (!$lastWatched || !$lastWatched->film) {
            return collect();
        }

        $film = $lastWatched->film;
        $watchedIds = WatchHistory::where('user_id', $user->id)
            ->pluck('film_id')
            ->toArray();

        $similar = collect();

        if (!empty($film->ai_embeddings)) {
            $similar = $this->getSimilarByEmbedding($film->ai_embeddings, $watchedIds, $limit);
        }

        if ($similar->isEmpty()) {
            $genreIds = $film->genres->pluck('id')->toArray();
            if (!empty($genreIds)) {
                $similar = Film::whereNotIn('id', $watchedIds)
                    ->whereHas('genres', fn($q) => $q->whereIn('genres.id', $genreIds))
                    ->orderByDesc('rating')
                    ->limit($limit)
                    ->get();
            }
        }

        return $similar;
    }

    private function getSimilarByEmbedding(array $sourceEmbedding, array $excludeIds, int $limit): Collection
    {
        $candidates = Film::whereNotIn('id', $excludeIds)
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
        return Film::where(function ($q) {
                $q->where('available_from', '>', now())
                  ->orWhere('release_year', '>', date('Y'));
            })
            ->orderBy('available_from')
            ->orderByDesc('release_year')
            ->limit($limit)
            ->get();
    }
}