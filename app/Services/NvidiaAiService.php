<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class NvidiaAiService
{
    private string $apiKey;
    private string $baseUrl = 'https://integrate.api.nvidia.com/v1';
    private int $timeout = 3;
    
    private string $llmModel = 'meta/llama-3.1-8b-instruct';
    private string $embeddingModel = 'nvidia/nv-embed-v2';

    public function __construct()
    {
        $this->apiKey = config('services.nvidia.api_key', '');
    }

    public function interpretQuery(string $query): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('NVIDIA API key not configured');
            return null;
        }

        $cacheKey = 'nvidia_interpret_' . md5($query);
        
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($query) {
            try {
                $systemPrompt = $this->buildSystemPrompt();
                
                $response = Http::withToken($this->apiKey)
                    ->timeout($this->timeout)
                    ->post("{$this->baseUrl}/chat/completions", [
                        'model' => $this->llmModel,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $query]
                        ],
                        'temperature' => 0.2,
                        'max_tokens' => 200,
                    ]);

                if (!$response->successful()) {
                    Log::warning('NVIDIA API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return null;
                }

                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                return $this->parseInterpretationResult($content);

            } catch (Exception $e) {
                Log::error('NVIDIA interpretQuery exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    public function generateEmbedding(string $text): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout + 2)
                ->post("{$this->baseUrl}/embeddings", [
                    'model' => $this->embeddingModel,
                    'input' => $text,
                    'encoding_format' => 'float',
                ]);

            if (!$response->successful()) {
                Log::warning('NVIDIA embedding API failed', [
                    'status' => $response->status()
                ]);
                return null;
            }

            $data = $response->json();
            return $data['data'][0]['embedding'] ?? null;

        } catch (Exception $e) {
            Log::error('NVIDIA generateEmbedding exception: ' . $e->getMessage());
            return null;
        }
    }

    public function generateFilmEmbedding(object $film): ?array
    {
        $genreNames = $film->genres->pluck('name')->join(', ');
        
        $text = trim(implode(' ', array_filter([
            $film->title,
            $genreNames,
            $film->synopsis,
            $film->subject_type === 'dracin' ? 'Drama China' : ($film->subject_type === 'series' ? 'TV Series' : 'Movie'),
            "Released: {$film->release_year}",
        ])));

        return $this->generateEmbedding($text);
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are a film search query interpreter. Extract structured filters from natural language queries.

Available genres: Action, Comedy, Drama, Horror, Thriller, Romance, Sci-Fi, Fantasy, Animation, Documentary, Crime, Mystery, Adventure, Family, War, Western, Musical, Biography, Sport, History

Output ONLY valid JSON with these fields (all optional):
{
  "genres": ["genre1", "genre2"],
  "type": "movie" or "series" or "dracin" or null,
  "min_rating": float between 0-10 or null,
  "mood_keywords": ["keyword1", "keyword2"],
  "similar_to_title": "film title" or null,
  "year_range": {"min": 2020, "max": 2024} or null
}

Examples:
User: "film action korea yang seru"
Output: {"genres": ["Action"], "mood_keywords": ["seru", "korea"], "type": "movie", "min_rating": 7.0}

User: "series kayak mirzapur tapi lebih pendek"
Output: {"similar_to_title": "mirzapur", "type": "series", "mood_keywords": ["crime", "thriller"]}

User: "film sedih tentang keluarga"
Output: {"genres": ["Drama"], "mood_keywords": ["sedih", "keluarga"], "min_rating": 6.5}

User: "horror movies from 2023"
Output: {"genres": ["Horror"], "type": "movie", "year_range": {"min": 2023, "max": 2023}}

Respond with JSON only, no explanation.
PROMPT;
    }

    private function parseInterpretationResult(string $content): ?array
    {
        $content = trim($content);
        
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        try {
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            
            return [
                'genres' => $json['genres'] ?? [],
                'type' => $json['type'] ?? null,
                'min_rating' => isset($json['min_rating']) ? (float)$json['min_rating'] : null,
                'mood_keywords' => $json['mood_keywords'] ?? [],
                'similar_to_title' => $json['similar_to_title'] ?? null,
                'year_range' => $json['year_range'] ?? null,
            ];
        } catch (Exception $e) {
            Log::warning('Failed to parse NVIDIA interpretation result', [
                'content' => $content,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        if (count($vec1) !== count($vec2)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $mag1 = 0.0;
        $mag2 = 0.0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $mag1 += $vec1[$i] * $vec1[$i];
            $mag2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude = sqrt($mag1) * sqrt($mag2);
        
        return $magnitude > 0 ? $dotProduct / $magnitude : 0.0;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
