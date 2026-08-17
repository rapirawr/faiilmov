<?php

namespace App\Services;

use App\Models\Film;
use App\Models\FilmTag;
use App\Models\Collection;
use App\Models\CollectionFilm;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CollectionClusterService
{
    private string $nvidiaApiKey;
    private string $nvidiaBaseUrl;
    private string $llmModel;

    public function __construct()
    {
        $this->nvidiaApiKey = config('services.nvidia.api_key', '');
        $this->nvidiaBaseUrl = rtrim(config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->llmModel = config('services.nvidia.llm_model', 'meta/llama-3.1-8b-instruct');
    }

    /**
     * Group film_tags by tag_value and generate/sync auto collections
     */
    public function generateAutoCollections(int $minThreshold = 5): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'published' => 0,
            'draft' => 0,
        ];

        // 1. Find all distinct tags for franchise and universe
        $tagGroups = FilmTag::whereIn('tag_type', ['franchise', 'universe', 'genre_mood'])
            ->select('tag_value', 'tag_type', DB::raw('count(DISTINCT film_id) as total_films'))
            ->groupBy('tag_value', 'tag_type')
            ->having('total_films', '>=', 2) // We create collections for >= 2, but only publish when >= minThreshold
            ->get();

        foreach ($tagGroups as $group) {
            $tagValue = trim($group->tag_value);
            $tagType = $group->tag_type;
            $filmCount = (int)$group->total_films;

            // Get all film IDs matching this tag
            $filmIds = FilmTag::where('tag_value', $tagValue)
                ->where('tag_type', $tagType)
                ->pluck('film_id')
                ->unique()
                ->toArray();

            if (empty($filmIds)) {
                continue;
            }

            // Find top rated / featured film in group for cover image
            $featuredFilm = Film::whereIn('id', $filmIds)
                ->orderByDesc('rating')
                ->orderByDesc('view_count')
                ->first();

            $coverImage = $featuredFilm ? ($featuredFilm->backdrop_url ?: $featuredFilm->poster_url) : null;

            // Check if collection already exists for this source tag
            $collection = Collection::where('source_tag', $tagValue)
                ->where('type', 'auto')
                ->first();

            $isNew = false;
            $shouldPublish = $filmCount >= $minThreshold;

            if (!$collection) {
                $isNew = true;
                $slug = Str::slug($tagValue);
                $collection = Collection::create([
                    'name' => $tagValue,
                    'slug' => $slug,
                    'type' => 'auto',
                    'source_tag' => $tagValue,
                    'cover_image' => $coverImage,
                    'status' => $shouldPublish ? 'published' : 'draft',
                ]);
                $summary['created']++;
            } else {
                $collection->update([
                    'cover_image' => $collection->cover_image ?: $coverImage,
                    'status' => $shouldPublish ? 'published' : ($collection->status === 'published' ? 'published' : 'draft'),
                ]);
                $summary['updated']++;
            }

            if ($collection->status === 'published') {
                $summary['published']++;
            } else {
                $summary['draft']++;
            }

            // Sync collection_films
            $existingFilmIds = CollectionFilm::where('collection_id', $collection->id)
                ->pluck('film_id')
                ->toArray();

            $toInsert = array_diff($filmIds, $existingFilmIds);
            foreach ($toInsert as $fId) {
                CollectionFilm::create([
                    'collection_id' => $collection->id,
                    'film_id' => $fId,
                    'added_by' => 'system',
                ]);
            }

            // Generate AI description if empty
            if (empty($collection->description)) {
                $sampleTitles = Film::whereIn('id', array_slice($filmIds, 0, 5))->pluck('title')->toArray();
                $desc = $this->generateDescriptionWithLlm($tagValue, $tagType, $sampleTitles);
                if ($desc) {
                    $collection->update(['description' => $desc]);
                }
            }
        }

        return $summary;
    }

    /**
     * Generate engaging cinematic collection description via LLM
     */
    public function generateDescriptionWithLlm(string $collectionName, string $tagType, array $sampleTitles): ?string
    {
        if (empty($this->nvidiaApiKey)) {
            return "Koleksi pilihan film {$collectionName} terbaik dengan kualitas HD dan subtitle Indonesia terlengkap di Faiilmov.";
        }

        $sampleStr = implode(', ', $sampleTitles);
        $systemPrompt = <<<PROMPT
You are a professional film curator and editor for "Faiilmov".
Write a concise, captivating, and cinematic description (in natural Bahasa Indonesia, 2-3 sentences) for a movie collection named "{$collectionName}".
Mention the thematic vibe and notable films like {$sampleStr}.
Do NOT use emojis, quotes, or conversational preamble. Output ONLY the description text.
PROMPT;

        try {
            $response = Http::withToken($this->nvidiaApiKey)
                ->timeout(8)
                ->post("{$this->nvidiaBaseUrl}/chat/completions", [
                    'model' => $this->llmModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Collection: {$collectionName}"]
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 200,
                ]);

            if ($response->successful()) {
                $text = trim($response->json()['choices'][0]['message']['content'] ?? '');
                return !empty($text) ? $text : null;
            }
        } catch (Exception $e) {
            Log::warning("Collection description generation failed: " . $e->getMessage());
        }

        return "Koleksi lengkap {$collectionName} yang dikurasi khusus untuk penggemar film di Faiilmov.";
    }
}
