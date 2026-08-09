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
     * Get films highly relevant to active profile's last watched film
     */
    public function getBecauseYouWatched(?User $user, $profileId = null, int $limit = 12): Collection
    {
        if (!$user) return collect();

        $lastWatched = WatchHistory::where('user_id', $user->id)
            ->where('profile_id', $profileId)
            ->with(['film.genres', 'film.actors'])
            ->orderByDesc('updated_at')
            ->first();

        if (!$lastWatched || !$lastWatched->film) {
            return collect();
        }

        $film = $lastWatched->film;
        $watchedIds = WatchHistory::where('user_id', $user->id)
            ->where('profile_id', $profileId)
            ->pluck('film_id')
            ->toArray();

        $genreIds = $film->genres->pluck('id')->toArray();
        $actorIds = $film->actors->pluck('id')->toArray();

        // Extract title keywords (e.g. "Spider-Man", "Batman", "Avengers", etc.)
        $titleWords = array_values(array_filter(
            explode(' ', preg_replace('/[^\w\s]/u', '', strtolower($film->title))),
            fn($w) => strlen($w) >= 4 && !in_array($w, ['the', 'from', 'with', 'and', 'part', 'movie', 'series', 'brand', 'new', 'day'])
        ));

        $candidates = Film::forActiveProfile()
            ->whereNotIn('id', $watchedIds)
            ->where('subject_type', $film->subject_type)
            ->where(function ($q) use ($genreIds, $actorIds, $titleWords) {
                if (!empty($genreIds)) {
                    $q->whereHas('genres', fn($g) => $g->whereIn('genres.id', $genreIds));
                }
                if (!empty($actorIds)) {
                    $q->orWhereHas('actors', fn($a) => $a->whereIn('actors.id', $actorIds));
                }
                foreach ($titleWords as $word) {
                    $q->orWhere('title', 'LIKE', '%' . $word . '%');
                }
            })
            ->with(['genres', 'actors'])
            ->limit(100)
            ->get();

        if ($candidates->isEmpty()) {
            return Film::forActiveProfile()
                ->whereNotIn('id', $watchedIds)
                ->where('subject_type', $film->subject_type)
                ->orderByDesc('rating')
                ->orderByDesc('view_count')
                ->limit($limit)
                ->get();
        }

        // Rank candidates by title, genre, and actor overlap score
        $scored = [];
        foreach ($candidates as $candidate) {
            $candGenreIds = $candidate->genres->pluck('id')->toArray();
            $candActorIds = $candidate->actors->pluck('id')->toArray();
            $candTitleLower = strtolower($candidate->title);

            $genreOverlap = count(array_intersect($genreIds, $candGenreIds));
            $actorOverlap = count(array_intersect($actorIds, $candActorIds));

            $titleOverlap = 0;
            foreach ($titleWords as $word) {
                if (str_contains($candTitleLower, $word)) {
                    $titleOverlap += 10;
                }
            }

            $totalScore = ($titleOverlap) + ($genreOverlap * 4) + ($actorOverlap * 6) + ($candidate->rating * 0.5);
            $scored[] = ['film' => $candidate, 'score' => $totalScore];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        $results = collect(array_slice(array_column($scored, 'film'), 0, $limit));

        if ($results->count() < $limit) {
            $existingIds = array_merge($watchedIds, $results->pluck('id')->toArray());
            $filler = Film::forActiveProfile()
                ->whereNotIn('id', $existingIds)
                ->where('subject_type', $film->subject_type)
                ->orderByDesc('rating')
                ->limit($limit - $results->count())
                ->get();
            $results = $results->merge($filler);
        }

        return $results;
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