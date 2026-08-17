<?php

namespace App\Services;

use App\Models\Film;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeminiVisionService
{
    private string $apiKey;
    private string $baseUrl;
    private string $visionModel;
    private int $timeout = 30;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->visionModel = config('services.gemini.vision_model', 'gemini-flash-lite-latest');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Analyze movie poster with Gemini Multimodal Vision
     * Returns structured visual metadata: visual_style, color_mood, visual_elements, franchise_cues, visual_summary
     */
    public function analyzePoster(Film|string $poster): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $imageUrl = $poster instanceof Film ? ($poster->poster_url ?: $poster->backdrop_url) : $poster;
        if (empty($imageUrl)) {
            return null;
        }

        $cacheKey = 'gemini_vision_' . md5($imageUrl);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $imageData = $this->fetchImageBase64($imageUrl);
        if (!$imageData) {
            return null;
        }

        $filmTitle = $poster instanceof Film ? "Title: {$poster->title}" : "";

        $prompt = <<<PROMPT
You are an expert cinematic visual analyst for a streaming movie platform.
Analyze this movie poster image in detail. {$filmTitle}

Extract the following structured visual properties:
1. "visual_style": Classification of the visual medium/art style. Pick the most accurate: "2D Anime / Manga", "3D CGI Animation", "Live-Action Cinematic", "Dark Fantasy / Gothic", "Retro 80s/90s Grain", "Tokusatsu / Kaiju", "Stop Motion", "Pixel / Indie Art", "Historical Drama".
2. "color_mood": Visual lighting & color palette mood. (e.g. "Dark Neon Noir", "Warm Pastel Romance", "Blood Red Horror", "Cold Desaturated Thriller", "Cyberpunk Cyan-Amber", "Golden Hour Nostalgia", "Vibrant Technicolor").
3. "visual_elements": Array of 3-6 distinct visual objects/motifs seen on the poster (e.g. ["Superhero Armor", "Outer Space Nebula", "Futuristic Cityscape", "Samurai Sword", "Hospital Coat", "Haunted House", "Explosion"]).
4. "franchise_cues": Any recognizable superhero emblem, franchise logo, iconic mask, or weapon (e.g. "Avengers Logo", "Spider-Man Mask", "Lightsaber", "Straw Hat", "Batman Cowl", "Gundam Mecha", or null if standalone).
5. "visual_summary": A crisp 1-sentence description in natural Bahasa Indonesia explaining what is visually depicted on the poster.

Output ONLY valid JSON:
{
  "visual_style": "...",
  "color_mood": "...",
  "visual_elements": ["..."],
  "franchise_cues": "...",
  "visual_summary": "..."
}
PROMPT;

        $result = $this->callGeminiVision($imageData['data'], $imageData['mime'], $prompt);
        if ($result !== null) {
            Cache::put($cacheKey, $result, now()->addDays(14));
        }

        return $result;
    }

    /**
     * Identify a movie or search by uploaded user image (poster, screenshot, photo)
     */
    public function identifyFilmFromImage(string $base64OrBinary, string $mimeType = 'image/jpeg'): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        if (str_starts_with($base64OrBinary, 'data:')) {
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-]+);base64,(.*)$/s', $base64OrBinary, $matches)) {
                $mimeType = $matches[1];
                $base64 = $matches[2];
            } else {
                $base64 = explode(',', $base64OrBinary)[1] ?? $base64OrBinary;
            }
        } elseif (base64_encode(base64_decode($base64OrBinary, true) ?: '') === $base64OrBinary) {
            $base64 = $base64OrBinary;
        } else {
            $base64 = base64_encode($base64OrBinary);
        }

        $prompt = <<<PROMPT
You are a movie identification expert.
Analyze this user-uploaded movie poster, scene screenshot, or artwork.
Identify what film, franchise, actors, or visual vibe it represents.

Output ONLY valid JSON:
{
  "detected_title": "Likely Movie/Series Title or null",
  "franchise": "Franchise name or null",
  "visual_style": "Live-Action Cinematic | 2D Anime | 3D Animation | etc.",
  "color_mood": "Dark Neon Noir | Pastel Romance | etc.",
  "search_keywords": ["keyword1", "keyword2", "keyword3"],
  "visual_description": "Concise description in Indonesian of the image"
}
PROMPT;

        return $this->callGeminiVision($base64, $mimeType, $prompt);
    }

    /**
     * Helper to call Gemini Multimodal Vision API
     */
    private function callGeminiVision(string $base64Data, string $mimeType, string $promptText): ?array
    {
        $startMicro = microtime(true);
        try {
            $endpoint = "{$this->baseUrl}/models/{$this->visionModel}:generateContent?key={$this->apiKey}";

            $response = Http::timeout($this->timeout)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $promptText],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Data,
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.2,
                ]
            ]);

            $latencyMs = (int)round((microtime(true) - $startMicro) * 1000);

            if (!$response->successful()) {
                Log::warning("Gemini Vision API request failed: HTTP {$response->status()}", [
                    'body' => $response->body()
                ]);
                return null;
            }

            $parts = $response->json()['candidates'][0]['content']['parts'] ?? [];
            $content = '';
            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $content .= $part['text'];
                }
            }
            $content = trim($content);

            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $m)) {
                $content = $m[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $m)) {
                $content = $m[1];
            }

            $decoded = json_decode(trim($content), true);
            return is_array($decoded) ? $decoded : null;
        } catch (Exception $e) {
            Log::error("Gemini Vision exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download remote image and convert to base64
     */
    private function fetchImageBase64(string $imageUrl): ?array
    {
        try {
            // If local storage path
            if (str_starts_with($imageUrl, '/storage/') || str_starts_with($imageUrl, 'storage/')) {
                $relPath = ltrim(str_replace('/storage/', '', $imageUrl), '/');
                $fullPath = storage_path('app/public/' . $relPath);
                if (file_exists($fullPath)) {
                    $binary = file_get_contents($fullPath);
                    $mime = mime_content_type($fullPath) ?: 'image/jpeg';
                    return ['data' => base64_encode($binary), 'mime' => $mime];
                }
            }

            // Remote URL
            $res = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://moviebox.ph/',
            ])->timeout(12)->withoutVerifying()->get($imageUrl);

            if ($res->successful()) {
                $binary = $res->body();
                $mime = $res->header('Content-Type') ?: 'image/jpeg';
                if (!str_starts_with($mime, 'image/')) {
                    $mime = 'image/jpeg';
                }
                return ['data' => base64_encode($binary), 'mime' => $mime];
            }
        } catch (Exception $e) {
            Log::debug("fetchImageBase64 failed for {$imageUrl}: " . $e->getMessage());
        }

        return null;
    }
}
