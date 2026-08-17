<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiEmbeddingService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout = 10;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->model = config('services.gemini.embedding_model', 'text-embedding-004');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getModelVersion(): string
    {
        return $this->model;
    }

    /**
     * Generate 768-dimension embedding vector for a single text
     */
    public function embedText(string $text): ?array
    {
        $clean = trim($text);
        if (empty($clean) || !$this->isConfigured()) {
            return null;
        }

        $startMicro = microtime(true);
        try {
            $endpoint = "{$this->baseUrl}/models/{$this->model}:embedContent?key={$this->apiKey}";

            $response = Http::timeout($this->timeout)->post($endpoint, [
                'model' => "models/{$this->model}",
                'content' => [
                    'parts' => [
                        ['text' => mb_substr($clean, 0, 8000)]
                    ]
                ]
            ]);

            $latencyMs = (int)round((microtime(true) - $startMicro) * 1000);

            if (!$response->successful()) {
                if (class_exists(SystemHealthService::class)) {
                    app(SystemHealthService::class)->logApiCall('gemini_embed', 'generativelanguage.googleapis.com', false, $response->status(), $latencyMs, 'HTTP ' . $response->status());
                }
                Log::warning('Gemini embedContent request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            if (class_exists(SystemHealthService::class)) {
                app(SystemHealthService::class)->logApiCall('gemini_embed', 'generativelanguage.googleapis.com', true, $response->status(), $latencyMs, null);
            }

            $data = $response->json();
            return $data['embedding']['values'] ?? null;

        } catch (Exception $e) {
            $latencyMs = (int)round((microtime(true) - $startMicro) * 1000);
            if (class_exists(SystemHealthService::class)) {
                app(SystemHealthService::class)->logApiCall('gemini_embed', 'generativelanguage.googleapis.com', false, null, $latencyMs, $e->getMessage());
            }
            Log::error('Gemini embedText exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Batch generate embeddings for up to 100 texts in a single HTTP request
     * @param string[] $texts
     * @return array<int, array<float>|null>
     */
    public function batchEmbed(array $texts): array
    {
        if (empty($texts) || !$this->isConfigured()) {
            return [];
        }

        $results = [];
        $chunks = array_chunk($texts, 90, true);

        foreach ($chunks as $chunk) {
            $requests = [];
            $keysMap = [];
            $i = 0;

            foreach ($chunk as $originalKey => $txt) {
                $requests[] = [
                    'model' => "models/{$this->model}",
                    'content' => [
                        'parts' => [
                            ['text' => mb_substr(trim((string)$txt), 0, 8000)]
                        ]
                    ]
                ];
                $keysMap[$i] = $originalKey;
                $i++;
            }

            $startMicro = microtime(true);
            try {
                $endpoint = "{$this->baseUrl}/models/{$this->model}:batchEmbedContents?key={$this->apiKey}";

                $response = Http::timeout($this->timeout * 2)->post($endpoint, [
                    'requests' => $requests
                ]);

                $latencyMs = (int)round((microtime(true) - $startMicro) * 1000);

                if (!$response->successful()) {
                    if (class_exists(SystemHealthService::class)) {
                        app(SystemHealthService::class)->logApiCall('gemini_embed_batch', 'generativelanguage.googleapis.com', false, $response->status(), $latencyMs, 'HTTP ' . $response->status());
                    }
                    Log::warning('Gemini batchEmbedContents failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    foreach ($keysMap as $idx => $origKey) {
                        $results[$origKey] = null;
                    }
                    continue;
                }

                if (class_exists(SystemHealthService::class)) {
                    app(SystemHealthService::class)->logApiCall('gemini_embed_batch', 'generativelanguage.googleapis.com', true, $response->status(), $latencyMs, null);
                }

                $data = $response->json();
                $embeddingsList = $data['embeddings'] ?? [];

                foreach ($keysMap as $idx => $origKey) {
                    $results[$origKey] = $embeddingsList[$idx]['values'] ?? null;
                }

            } catch (Exception $e) {
                $latencyMs = (int)round((microtime(true) - $startMicro) * 1000);
                if (class_exists(SystemHealthService::class)) {
                    app(SystemHealthService::class)->logApiCall('gemini_embed_batch', 'generativelanguage.googleapis.com', false, null, $latencyMs, $e->getMessage());
                }
                Log::error('Gemini batchEmbed exception: ' . $e->getMessage());
                foreach ($keysMap as $origKey) {
                    $results[$origKey] = null;
                }
            }
        }

        return $results;
    }

    /**
     * Compute cosine similarity between two vector arrays
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        $count = count($vec1);
        if ($count === 0 || $count !== count($vec2)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $mag1 = 0.0;
        $mag2 = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $v1 = (float)$vec1[$i];
            $v2 = (float)$vec2[$i];
            $dotProduct += $v1 * $v2;
            $mag1 += $v1 * $v1;
            $mag2 += $v2 * $v2;
        }

        $magnitude = sqrt($mag1) * sqrt($mag2);

        return $magnitude > 0 ? $dotProduct / $magnitude : 0.0;
    }
}
