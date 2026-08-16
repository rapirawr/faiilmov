<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class SynopsisAiService
{
    private string $apiKey;
    private string $baseUrl = 'https://integrate.api.nvidia.com/v1';
    private string $llmModel = 'meta/llama-3.1-8b-instruct';
    private int $timeout = 8;

    public function __construct()
    {
        $this->apiKey = config('services.nvidia.api_key', '');
    }

    /**
     * Translate text between Indonesian <-> English (or auto -> target)
     */
    public function translate(string $text, string $targetLang = 'id'): array
    {
        $cleanText = trim($text);
        if (empty($cleanText)) {
            return [
                'success' => false,
                'translated_text' => '',
                'source_lang' => 'auto',
                'target_lang' => $targetLang,
            ];
        }

        $cacheKey = 'synopsis_trans_' . md5($cleanText . '_' . $targetLang);

        return Cache::remember($cacheKey, 86400 * 30, function () use ($cleanText, $targetLang) {
            $langName = $targetLang === 'id' ? 'Bahasa Indonesia' : 'English';

            // 1. Try Nvidia AI LLM for natural, cinematic translation
            if (!empty($this->apiKey)) {
                try {
                    $systemPrompt = "You are a professional cinematic translator for the movie streaming platform faiilmov. Translate the provided film synopsis into fluent, engaging {$langName}. Do NOT add conversational filler, notes, or explanations. Output ONLY the translated text.";
                    
                    $response = Http::withToken($this->apiKey)
                        ->timeout($this->timeout)
                        ->post("{$this->baseUrl}/chat/completions", [
                            'model' => $this->llmModel,
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $cleanText]
                            ],
                            'temperature' => 0.2,
                            'max_tokens' => 600,
                        ]);

                    if ($response->successful()) {
                        $translated = trim($response->json()['choices'][0]['message']['content'] ?? '');
                        if (!empty($translated)) {
                            return [
                                'success' => true,
                                'translated_text' => $translated,
                                'provider' => 'nvidia_ai',
                                'target_lang' => $targetLang,
                            ];
                        }
                    }
                } catch (Exception $e) {
                    Log::warning("SynopsisAiService Nvidia translate failed: " . $e->getMessage());
                }
            }

            // 2. Fallback: MyMemory Translation API
            try {
                $langpair = $targetLang === 'id' ? 'en|id' : 'id|en';
                $encoded = urlencode(mb_substr($cleanText, 0, 500));
                $memRes = Http::timeout(5)->get("https://api.mymemory.translated.net/get?q={$encoded}&langpair={$langpair}");

                if ($memRes->successful()) {
                    $t = trim($memRes->json()['responseData']['translatedText'] ?? '');
                    if (!empty($t) && !str_contains($t, 'MYMEMORY WARNING')) {
                        return [
                            'success' => true,
                            'translated_text' => $t,
                            'provider' => 'mymemory',
                            'target_lang' => $targetLang,
                        ];
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }

            // 3. Fallback: Google Translate API
            try {
                $gUrl = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl={$targetLang}&dt=t&q=" . urlencode($cleanText);
                $gRes = Http::timeout(5)->get($gUrl);
                if ($gRes->successful()) {
                    $data = $gRes->json();
                    $translated = '';
                    if (isset($data[0]) && is_array($data[0])) {
                        foreach ($data[0] as $part) {
                            if (isset($part[0])) {
                                $translated .= $part[0];
                            }
                        }
                    }
                    if (!empty($translated)) {
                        return [
                            'success' => true,
                            'translated_text' => trim($translated),
                            'provider' => 'google_translate',
                            'target_lang' => $targetLang,
                        ];
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }

            return [
                'success' => false,
                'translated_text' => $cleanText,
                'provider' => 'original_fallback',
                'target_lang' => $targetLang,
            ];
        });
    }

    /**
     * Generate structured AI summary, key story hooks, vibes, and why-to-watch
     */
    public function summarize(string $title, string $synopsis, array $genres = []): array
    {
        $cleanTitle = trim($title);
        $cleanSynopsis = trim($synopsis);
        $genresStr = implode(', ', $genres);

        if (empty($cleanSynopsis) || $cleanSynopsis === 'Plot under wraps.') {
            $cleanSynopsis = "Film {$cleanTitle} ({$genresStr}) menghadirkan kisah penuh aksi, drama, dan petualangan seru.";
        }

        $cacheKey = 'synopsis_summary_' . md5($cleanTitle . '_' . $cleanSynopsis);

        return Cache::remember($cacheKey, 86400 * 30, function () use ($cleanTitle, $cleanSynopsis, $genresStr, $genres) {
            // 1. Try Nvidia AI LLM
            if (!empty($this->apiKey)) {
                try {
                    $systemPrompt = <<<PROMPT
You are an expert film critic and synopsis summarizer for the movie streaming platform "faiilmov".
Analyze the film title, genres, and synopsis. Generate a structured, captivating, spoiler-free summary in fluent Bahasa Indonesia.

Output ONLY valid JSON with exact keys:
{
  "summary": "Ringkasan alur padat 2-3 kalimat Bahasa Indonesia yang jelas dan memikat.",
  "key_points": [
    "Poin hook/konflik utama 1 (singkat, padat)",
    "Poin hook/konflik utama 2",
    "Poin hook/konflik utama 3"
  ],
  "vibes": ["Vibe 1", "Vibe 2", "Vibe 3"],
  "why_watch": "1 kalimat kuat mengapa film ini wajib ditonton dan layak dinikmati."
}
PROMPT;

                    $userPrompt = "Film: {$cleanTitle}\nGenre: {$genresStr}\nSinopsis: {$cleanSynopsis}";

                    $response = Http::withToken($this->apiKey)
                        ->timeout($this->timeout)
                        ->post("{$this->baseUrl}/chat/completions", [
                            'model' => $this->llmModel,
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $userPrompt]
                            ],
                            'temperature' => 0.3,
                            'max_tokens' => 500,
                        ]);

                    if ($response->successful()) {
                        $rawContent = $response->json()['choices'][0]['message']['content'] ?? '';
                        $cleanJson = preg_replace('/^```json|```$/m', '', trim($rawContent));
                        $decoded = json_decode($cleanJson, true);

                        if (is_array($decoded) && !empty($decoded['summary'])) {
                            return [
                                'success' => true,
                                'summary' => trim($decoded['summary']),
                                'key_points' => array_slice($decoded['key_points'] ?? [], 0, 3),
                                'vibes' => array_slice($decoded['vibes'] ?? $genres, 0, 4),
                                'why_watch' => trim($decoded['why_watch'] ?? "Film {$cleanTitle} menghadirkan pengalaman sinematik yang memukau dan sayang untuk dilewatkan."),
                                'provider' => 'nvidia_ai',
                            ];
                        }
                    }
                } catch (Exception $e) {
                    Log::warning("SynopsisAiService summarize failed: " . $e->getMessage());
                }
            }

            // 2. Intelligent Local Fallback
            $sentences = preg_split('/(?<=[.?!])\s+/', $cleanSynopsis, -1, PREG_SPLIT_NO_EMPTY);
            $shortSummary = count($sentences) > 0 ? implode(' ', array_slice($sentences, 0, 2)) : $cleanSynopsis;

            $keyPoints = [];
            if (count($sentences) >= 3) {
                $keyPoints = array_slice($sentences, 0, 3);
            } else {
                $keyPoints = [
                    "Kisah sentral berfokus pada perjalanan dan petualangan {$cleanTitle}.",
                    "Menghadapi konflik intens dengan tantangan yang mempertaruhkan segalanya.",
                    "Dinamika karakter dan alur cerita yang penuh kejutan dari awal hingga akhir."
                ];
            }

            $vibes = !empty($genres) ? array_slice($genres, 0, 3) : ['Seru', 'Menegangkan', 'Dramatis'];

            return [
                'success' => true,
                'summary' => $shortSummary,
                'key_points' => $keyPoints,
                'vibes' => $vibes,
                'why_watch' => "Karya {$cleanTitle} memadukan genre " . ($genresStr ?: 'pilihan') . " dengan alur yang memikat penonton.",
                'provider' => 'local_fallback',
            ];
        });
    }

    /**
     * Generate or refine synopsis copywriting for Admin (with tone options)
     */
    public function generateSynopsisCopy(string $title, ?string $rawText = null, array $genres = [], string $tone = 'cinematic'): array
    {
        $cleanTitle = trim($title);
        $genresStr = implode(', ', $genres);

        if (empty($this->apiKey)) {
            // Fallback translate if raw text exists
            if (!empty($rawText)) {
                $trans = $this->translate($rawText, 'id');
                return [
                    'success' => true,
                    'synopsis' => $trans['translated_text'],
                ];
            }
            return [
                'success' => true,
                'synopsis' => "Film {$cleanTitle} ({$genresStr}) menyajikan kisah penuh dinamika dan petualangan yang memikat bagi para pecinta sinema.",
            ];
        }

        try {
            $toneInstructions = match ($tone) {
                'short' => 'Buat sinopsis sangat singkat dan padat (maksimal 2 kalimat hook).',
                'catchy' => 'Gunakan gaya penulisan promo yang memikat, penasaran, dan menggugah minat penonton.',
                default => 'Gunakan gaya narasi sinematik yang elegan, mendalam, dan bebas spoiler dalam Bahasa Indonesia.',
            };

            $systemPrompt = "You are an award-winning synopsis copywriter for the Indonesian streaming platform faiilmov. {$toneInstructions} Output ONLY the written synopsis paragraph in Indonesian without quote marks or introductory talk.";

            $content = "Judul Film: {$cleanTitle}\nGenre: {$genresStr}";
            if (!empty($rawText)) {
                $content .= "\nSinopsis Asal/Referensi: {$rawText}";
            }

            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->llmModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $content]
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 450,
                ]);

            if ($response->successful()) {
                $synopsis = trim($response->json()['choices'][0]['message']['content'] ?? '');
                if (!empty($synopsis)) {
                    return [
                        'success' => true,
                        'synopsis' => $synopsis,
                    ];
                }
            }
        } catch (Exception $e) {
            Log::warning("generateSynopsisCopy error: " . $e->getMessage());
        }

        return [
            'success' => false,
            'synopsis' => $rawText ?: "Film {$cleanTitle} menghadirkan pengalaman visual dan alur cerita yang tak terlupakan.",
        ];
    }
}
