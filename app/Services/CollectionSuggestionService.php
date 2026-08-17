<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Film;
use App\Models\FilmEmbedding;
use Illuminate\Support\Collection as SupportCollection;

class CollectionSuggestionService
{
    public function __construct(
        private GeminiEmbeddingService $gemini
    ) {}

    /**
     * Calculate collection centroid embedding and suggest candidate films to add
     * @return SupportCollection<Film>
     */
    public function suggestAdditions(Collection $collection, int $limit = 10): SupportCollection
    {
        $existingFilmIds = $collection->films()->pluck('films.id')->toArray();

        if (empty($existingFilmIds)) {
            return collect();
        }

        // 1. Get embeddings for existing films in collection
        $currentEmbeddings = FilmEmbedding::whereIn('film_id', $existingFilmIds)->get();

        if ($currentEmbeddings->isEmpty()) {
            // Fallback to genre/tag overlap
            $collectionGenres = $collection->films()->with('genres')->get()->pluck('genres')->flatten()->pluck('id')->unique()->toArray();
            $candidates = Film::whereNotIn('id', $existingFilmIds)
                ->whereHas('genres', fn($q) => $q->whereIn('genres.id', $collectionGenres))
                ->orderByDesc('rating')
                ->limit($limit)
                ->get();
            foreach ($candidates as $c) {
                $c->similarity_score = 85.0;
            }
            return $candidates;
        }

        // 2. Compute Centroid Vector (Average of all vectors in collection)
        $centroid = [];
        $vectorDimension = 0;
        $validVectorCount = 0;

        foreach ($currentEmbeddings as $emb) {
            $vec = $emb->embedding;
            if (!is_array($vec) || empty($vec)) continue;

            if ($vectorDimension === 0) {
                $vectorDimension = count($vec);
                $centroid = array_fill(0, $vectorDimension, 0.0);
            }

            if (count($vec) === $vectorDimension) {
                for ($i = 0; $i < $vectorDimension; $i++) {
                    $centroid[$i] += (float)$vec[$i];
                }
                $validVectorCount++;
            }
        }

        if ($validVectorCount > 0) {
            for ($i = 0; $i < $vectorDimension; $i++) {
                $centroid[$i] /= $validVectorCount;
            }
        } else {
            return collect();
        }

        // 3. Find Candidate Films Outside Collection with Highest Cosine Similarity
        $candidateEmbeddings = FilmEmbedding::whereNotIn('film_id', $existingFilmIds)->get();
        $scoredCandidates = [];

        foreach ($candidateEmbeddings as $cand) {
            if (is_array($cand->embedding) && count($cand->embedding) === $vectorDimension) {
                $sim = $this->gemini->cosineSimilarity($centroid, $cand->embedding);
                if ($sim > 0.40) {
                    $scoredCandidates[$cand->film_id] = $sim;
                }
            }
        }

        if (empty($scoredCandidates)) {
            return collect();
        }

        arsort($scoredCandidates);
        $topFilmIds = array_slice(array_keys($scoredCandidates), 0, $limit);

        $films = Film::whereIn('id', $topFilmIds)->with('genres')->get()->keyBy('id');

        // Preserve similarity order and attach similarity score attribute
        $sortedFilms = collect();
        foreach ($topFilmIds as $fId) {
            if (isset($films[$fId])) {
                $film = $films[$fId];
                $film->similarity_score = round($scoredCandidates[$fId] * 100, 1);
                $sortedFilms->push($film);
            }
        }

        return $sortedFilms;
    }
}
