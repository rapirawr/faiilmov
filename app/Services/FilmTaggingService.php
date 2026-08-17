<?php

namespace App\Services;

use App\Models\Film;
use App\Models\FilmTag;
use App\Models\FilmEmbedding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class FilmTaggingService
{
    private string $nvidiaApiKey;
    private string $nvidiaBaseUrl;
    private string $llmModel;

    public function __construct(
        private GeminiEmbeddingService $gemini,
        private GeminiVisionService $vision
    ) {
        $this->nvidiaApiKey = config('services.nvidia.api_key', '');
        $this->nvidiaBaseUrl = rtrim(config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->llmModel = config('services.nvidia.llm_model', 'meta/llama-3.1-8b-instruct');
    }

    /**
     * Tag a film with Franchise, Universe, Genre/Mood, Era, Vision Poster attributes, and create its Vector Embedding
     */
    public function tagFilm(Film $film): void
    {
        $film->loadMissing(['genres', 'actors']);

        // 1. LLM Tagging (Llama-3.1 8B)
        $llmTags = $this->extractTagsWithLlm($film);
        if (!empty($llmTags)) {
            $this->storeTags($film->id, $llmTags, 'llm');
        }

        // 2. Relation / Rule-based Tagging (Cast, Title, Studio matching)
        $relationTags = $this->extractRelationTags($film);
        if (!empty($relationTags)) {
            $this->storeTags($film->id, $relationTags, 'relation');
        }

        // 3. AI Vision Poster Tagging (Gemini Multimodal Vision)
        $this->extractVisionPosterTags($film);

        // 4. Generate and Store Vector Embedding via Gemini text-embedding-004
        $this->generateAndStoreEmbedding($film);
    }

    /**
     * Analyze movie poster with AI Vision and store visual tags
     */
    public function extractVisionPosterTags(Film $film): void
    {
        if (!$this->vision->isConfigured()) {
            return;
        }

        $visionData = $this->vision->analyzePoster($film);
        if (!$visionData) {
            return;
        }

        $visualStyle = $visionData['visual_style'] ?? null;
        $colorMood = $visionData['color_mood'] ?? null;
        $visualElements = $visionData['visual_elements'] ?? [];
        $franchiseCues = $visionData['franchise_cues'] ?? null;
        $visualSummary = $visionData['visual_summary'] ?? null;

        // Update film model fields if present
        $updates = [];
        if (!empty($visualStyle) && empty($film->visual_style)) {
            $updates['visual_style'] = $visualStyle;
        }
        if (!empty($visualSummary) && empty($film->poster_visual_summary)) {
            $updates['poster_visual_summary'] = $visualSummary;
        }
        if (!empty($updates)) {
            $film->update($updates);
        }

        // Store visual tags
        if (!empty($visualStyle) && is_string($visualStyle)) {
            $this->storeTags($film->id, ['visual_style' => \Illuminate\Support\Str::limit(trim($visualStyle), 60, '')], 'vision');
        }
        if (!empty($colorMood) && is_string($colorMood)) {
            $this->storeTags($film->id, ['aesthetic' => \Illuminate\Support\Str::limit(trim($colorMood), 60, '')], 'vision');
        }
        if (!empty($visualElements)) {
            $elementsArray = is_array($visualElements) ? $visualElements : explode(';', (string)$visualElements);
            foreach (array_slice($elementsArray, 0, 4) as $element) {
                if (is_string($element) && !empty(trim($element))) {
                    $this->storeTags($film->id, ['visual_element' => \Illuminate\Support\Str::limit(trim($element), 60, '')], 'vision');
                }
            }
        }
        if (!empty($franchiseCues) && is_string($franchiseCues)) {
            $this->storeTags($film->id, ['franchise' => \Illuminate\Support\Str::limit(trim($franchiseCues), 60, '')], 'vision');
        }
    }

    /**
     * Call Llama-3.1 8B to classify film tags
     */
    public function extractTagsWithLlm(Film $film): ?array
    {
        if (empty($this->nvidiaApiKey)) {
            return null;
        }

        $genreNames = $film->genres->pluck('name')->join(', ');
        $castNames = $film->actors->take(5)->pluck('name')->join(', ');
        $typeLabel = $film->subject_type === 'dracin' ? 'Drama China' : ($film->subject_type === 'series' ? 'TV Series' : 'Movie');

        $systemPrompt = <<<PROMPT
You are an expert film taxonomist and curator.
Classify the given film into structured tags.
Fields:
- "franchise": Name of the movie franchise if applicable (e.g., "Marvel Cinematic Universe", "Star Wars", "Fast & Furious", "Harry Potter", "Transformers", "John Wick", "Spider-Man", "MonsterVerse", "Mission Impossible", "The Conjuring Universe", "Hospital Playlist"). Null if standalone.
- "universe": Broader fictional universe or media franchise (e.g., "Marvel Universe", "DC Universe", "Wizarding World", "Star Wars", "Toei Tokusatsu", "Cyberpunk", "Disney Princess"). Null if none.
- "genre_mood": Specific thematic mood or micro-genre (e.g., "K-Drama Romance", "Dark Psychological Thriller", "Cyberpunk Dystopia", "Supernatural Horror", "90s Action Comedy", "Romantic Melodrama", "Xianxia Fantasy", "High School Youth"). Null if generic.
- "era": Time period or decade aesthetic (e.g., "90s", "80s Classic", "2000s Nostalgia", "Modern 2020s", "Historical / Period", "Futuristic").

Output ONLY valid JSON:
{
  "franchise": string or null,
  "universe": string or null,
  "genre_mood": string or null,
  "era": string or null
}
PROMPT;

        $userPrompt = "Title: {$film->title}\nType: {$typeLabel}\nYear: {$film->release_year}\nGenres: {$genreNames}\nCast: {$castNames}\nSynopsis: " . Str::limit(strip_tags($film->synopsis ?? ''), 600);

        try {
            $response = Http::withToken($this->nvidiaApiKey)
                ->timeout(12)
                ->post("{$this->nvidiaBaseUrl}/chat/completions", [
                    'model' => $this->llmModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 200,
                ]);

            if (!$response->successful()) {
                Log::warning("LLM Film tagging failed for film {$film->id}: " . $response->body());
                return null;
            }

            $content = trim($response->json()['choices'][0]['message']['content'] ?? '');
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $m)) {
                $content = $m[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $m)) {
                $content = $m[1];
            }

            $json = json_decode(trim($content), true);
            if (!is_array($json)) {
                return null;
            }

            $tags = [];
            foreach (['franchise', 'universe', 'genre_mood', 'era'] as $key) {
                $val = trim((string)($json[$key] ?? ''));
                if (!empty($val) && strtolower($val) !== 'null' && strtolower($val) !== 'none') {
                    $tags[$key] = $val;
                }
            }

            return $tags;
        } catch (Exception $e) {
            Log::error("LLM Film tagging exception for film {$film->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Rule-based & heuristic tagging based on title patterns, casts, and keywords
     */
    public function extractRelationTags(Film $film): array
    {
        $tags = [];
        $titleLower = strtolower($film->title ?? '');
        $synopsisLower = strtolower($film->synopsis ?? '');
        $combinedText = "{$titleLower} {$synopsisLower}";

        // Franchise / Universe Rules
        $franchiseRules = [
            'Marvel Cinematic Universe' => [
                'patterns' => ['avengers', 'iron man', 'thor:', 'thor ', 'captain america', 'guardians of the galaxy', 'black panther', 'doctor strange', 'ant-man', 'spider-man: homecoming', 'spider-man: far from home', 'spider-man: no way home', 'shang-chi', 'eternals', 'the marvels', 'loki', 'wandavision', 'falcon and the winter soldier', 'hawkeye', 'moon knight', 'secret invasion', 'deadpool & wolverine', 'deadpool and wolverine'],
                'universe' => 'Marvel Universe',
            ],
            'DC Extended Universe' => [
                'patterns' => ['justice league', 'batman v superman', 'man of steel', 'wonder woman', 'aquaman', 'shazam!', 'black adam', 'the flash', 'blue beetle', 'suicide squad', 'peacemaker'],
                'universe' => 'DC Universe',
            ],
            'Star Wars' => [
                'patterns' => ['star wars', 'mandalorian', 'boba fett', 'obi-wan', 'andor', 'ahsoka', 'the acolyte', 'bad batch', 'the clone wars'],
                'universe' => 'Star Wars',
            ],
            'Wizarding World' => [
                'patterns' => ['harry potter', 'fantastic beasts', 'dumbledore', 'grindelwald', 'hogwarts'],
                'universe' => 'Wizarding World',
            ],
            'Fast & Furious' => [
                'patterns' => ['fast & furious', 'fast and furious', 'fast five', 'furious 7', 'the fate of the furious', 'f9', 'fast x', 'hobbs & shaw'],
                'universe' => 'Fast & Furious Universe',
            ],
            'MonsterVerse' => [
                'patterns' => ['godzilla vs. kong', 'godzilla vs kong', 'godzilla x kong', 'godzilla: king of the monsters', 'kong: skull island', 'monarch: legacy of monsters'],
                'universe' => 'MonsterVerse',
            ],
            'The Conjuring Universe' => [
                'patterns' => ['the conjuring', 'annabelle', 'the nun', 'the curse of la llorona'],
                'universe' => 'The Conjuring Universe',
            ],
            'Transformers' => [
                'patterns' => ['transformers', 'bumblebee', 'rise of the beasts', 'dark of the moon', 'age of extinction', 'the last knight'],
                'universe' => 'Transformers',
            ],
            'John Wick' => [
                'patterns' => ['john wick', 'the continental: from the world of john wick', 'ballerina'],
                'universe' => 'John Wick Universe',
            ],
            'Dragon Ball' => [
                'patterns' => ['dragon ball', 'dragon ball z', 'dragon ball super', 'dragon ball gt', 'dragon ball daima'],
                'universe' => 'Dragon Ball',
            ],
            'One Piece' => [
                'patterns' => ['one piece', 'luffy', 'straw hat'],
                'universe' => 'One Piece',
            ],
            'Hospital Playlist' => [
                'patterns' => ['hospital playlist', 'wise resident life'],
                'universe' => 'K-Drama Medical Universe',
            ],
        ];

        foreach ($franchiseRules as $franchiseName => $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (str_contains($titleLower, $pattern) || str_contains($synopsisLower, $pattern)) {
                    $tags['franchise'] = $franchiseName;
                    if (!empty($rule['universe'])) {
                        $tags['universe'] = $rule['universe'];
                    }
                    break 2;
                }
            }
        }

        // Dracin Micro-genres
        if ($film->subject_type === 'dracin') {
            if (str_contains($combinedText, 'xianxia') || str_contains($combinedText, 'immortal') || str_contains($combinedText, 'dewa') || str_contains($combinedText, 'kultivasi')) {
                $tags['genre_mood'] = 'Dracin Xianxia Fantasy';
            } elseif (str_contains($combinedText, 'wuxia') || str_contains($combinedText, 'pendekar') || str_contains($combinedText, 'silat') || str_contains($combinedText, 'martial arts')) {
                $tags['genre_mood'] = 'Dracin Wuxia Martial Arts';
            } elseif (str_contains($combinedText, 'kerajaan') || str_contains($combinedText, 'kaisar') || str_contains($combinedText, 'selir') || str_contains($combinedText, 'istana') || str_contains($combinedText, 'historical') || str_contains($combinedText, 'dinasti')) {
                $tags['genre_mood'] = 'Dracin Historical Romance';
            } elseif (str_contains($combinedText, 'ceo') || str_contains($combinedText, 'direktur') || str_contains($combinedText, 'modern') || str_contains($combinedText, 'bos')) {
                $tags['genre_mood'] = 'Dracin Modern Romance';
            }
        }

        // Era Heuristics
        $year = (int)($film->release_year ?? 0);
        if ($year >= 1990 && $year <= 1999) {
            $tags['era'] = '90s';
        } elseif ($year >= 1980 && $year <= 1989) {
            $tags['era'] = '80s Classic';
        } elseif ($year >= 2000 && $year <= 2009) {
            $tags['era'] = '2000s Nostalgia';
        } elseif ($year >= 2020) {
            $tags['era'] = 'Modern 2020s';
        }

        return $tags;
    }

    /**
     * Store tags in database
     */
    private function storeTags(int $filmId, array $tags, string $source): void
    {
        foreach ($tags as $tagType => $tagValue) {
            $tagValue = trim((string)$tagValue);
            if (empty($tagValue)) continue;

            FilmTag::updateOrCreate(
                [
                    'film_id' => $filmId,
                    'tag_type' => $tagType,
                    'tag_value' => $tagValue,
                ],
                [
                    'confidence' => $source === 'relation' ? 0.95 : 0.85,
                    'source' => $source,
                ]
            );
        }
    }

    /**
     * Generate embedding via Gemini and save to film_embeddings table
     */
    public function generateAndStoreEmbedding(Film $film): ?array
    {
        $genreNames = $film->genres->pluck('name')->join(', ');
        $tagsList = $film->tags->pluck('tag_value')->join(', ');

        $textToEmbed = trim(implode(' | ', array_filter([
            "Title: {$film->title}",
            $genreNames ? "Genres: {$genreNames}" : null,
            $film->visual_style ? "Visual Style: {$film->visual_style}" : null,
            $tagsList ? "Tags: {$tagsList}" : null,
            $film->subject_type === 'dracin' ? 'Drama China' : ($film->subject_type === 'series' ? 'TV Series' : 'Movie'),
            $film->release_year ? "Year: {$film->release_year}" : null,
            $film->poster_visual_summary ? "Visual Description: {$film->poster_visual_summary}" : null,
            "Synopsis: " . strip_tags($film->synopsis ?? ''),
        ])));

        $vector = $this->gemini->embedText($textToEmbed);

        if (!empty($vector)) {
            FilmEmbedding::updateOrCreate(
                ['film_id' => $film->id],
                [
                    'embedding' => $vector,
                    'model_version' => $this->gemini->getModelVersion(),
                ]
            );
            return $vector;
        }

        return null;
    }
}
