<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionFilm;
use App\Models\Film;
use App\Models\FilmEmbedding;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class CollectionPromptService
{
    private string $geminiApiKey;
    private string $geminiBaseUrl;
    private string $geminiModel;
    private string $nvidiaApiKey;
    private string $nvidiaBaseUrl;
    private string $llmModel;

    public function __construct(
        private GeminiEmbeddingService $gemini
    ) {
        $this->geminiApiKey = config('services.gemini.api_key', '');
        $this->geminiBaseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->geminiModel = config('services.gemini.vision_model', 'gemini-flash-lite-latest');

        $this->nvidiaApiKey = config('services.nvidia.api_key', '');
        $this->nvidiaBaseUrl = rtrim(config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->llmModel = config('services.nvidia.llm_model', 'meta/llama-3.1-8b-instruct');
    }

    /**
     * Create an AI curated collection from natural language prompt
     */
    public function createFromPrompt(string $prompt, ?User $user = null, int $topK = 20): Collection
    {
        $cleanPrompt = trim($prompt);

        // 1. Stage 1: Retrieve candidate pool via Hybrid Vector Search + Entity/Keyword Match
        $candidateFilms = $this->retrieveCandidateFilms($cleanPrompt, 40);

        // 2. Stage 2: LLM Verification, Filtering & Metadata Generation
        $curationResult = $this->curateAndFilterWithLlm($cleanPrompt, $candidateFilms);

        $collectionName = $curationResult['title'] ?? Str::title($cleanPrompt);
        $collectionDesc = $curationResult['description'] ?? "Koleksi film pilihan bertema {$cleanPrompt} yang dikurasi khusus dengan AI di Faiilmov.";
        $selectedFilmIds = $curationResult['selected_film_ids'] ?? [];

        // Fallback: If LLM returned no IDs, use candidate IDs with highest vector score
        if (empty($selectedFilmIds) && $candidateFilms->isNotEmpty()) {
            $selectedFilmIds = $candidateFilms->take($topK)->pluck('id')->toArray();
        }

        // 3. Find cover image from top selected film
        $topFilm = !empty($selectedFilmIds) ? Film::find($selectedFilmIds[0]) : null;
        $coverImage = $topFilm ? ($topFilm->backdrop_url ?: $topFilm->poster_url) : null;

        // 4. Create Collection
        $collection = Collection::create([
            'name' => $collectionName,
            'slug' => Str::slug($collectionName) . '-' . Str::random(5),
            'type' => 'prompt',
            'description' => $collectionDesc,
            'cover_image' => $coverImage,
            'source_tag' => $cleanPrompt,
            'created_by' => $user?->id,
            'status' => 'published',
        ]);

        // 5. Attach Verified Films to Collection
        foreach ($selectedFilmIds as $filmId) {
            CollectionFilm::updateOrCreate(
                [
                    'collection_id' => $collection->id,
                    'film_id' => $filmId,
                ],
                [
                    'added_by' => $user ? 'user' : 'system',
                ]
            );
        }

        return $collection;
    }

    /**
     * Stage 1: Hybrid Candidate Retrieval (Vector Embedding + Smart Keywords & Tags)
     * @return \Illuminate\Support\Collection<Film>
     */
    private function retrieveCandidateFilms(string $prompt, int $limit = 40): \Illuminate\Support\Collection
    {
        $scoredCandidates = [];

        // A. Vector Similarity Retrieval
        $promptVector = $this->gemini->embedText($prompt);
        if (!empty($promptVector)) {
            $embeddings = FilmEmbedding::all();
            foreach ($embeddings as $item) {
                if (is_array($item->embedding) && count($item->embedding) === count($promptVector)) {
                    $sim = $this->gemini->cosineSimilarity($promptVector, $item->embedding);
                    if ($sim > 0.35) {
                        $scoredCandidates[$item->film_id] = $sim;
                    }
                }
            }
        }

        // B. Keyword & Entity Extraction (Filter out common stopwords)
        $stopwords = [
            'kumpulkan', 'film', 'movie', 'yang', 'membentuk', 'salah', 'satu', 'kisah', 
            'terbesar', 'dalam', 'sejarah', 'perfilman', 'dari', 'hingga', 'sampai', 'dan', 
            'atau', 'buatkan', 'daftar', 'koleksi', 'semua', 'the', 'of', 'and', 'in', 'to', 'a', 'is', 'man'
        ];

        // Specific named entity phrases
        $phrases = [
            'marvel', 'marvel cinematic universe', 'mcu', 'avengers', 'iron man', 'spider-man', 
            'spiderman', 'captain america', 'thor', 'hulk', 'doctor strange', 'black panther', 
            'guardians of the galaxy', 'deadpool', 'wolverine', 'star wars', 'harry potter', 
            'batman', 'superman', 'drakor', 'one piece', 'dracin', 'xianxia', 'anime'
        ];

        $lowerPrompt = mb_strtolower($prompt);
        $matchedEntities = [];
        foreach ($phrases as $phrase) {
            if (str_contains($lowerPrompt, $phrase)) {
                $matchedEntities[] = $phrase;
            }
        }

        $words = preg_split('/[\s,\.\-\:]+/', $lowerPrompt);
        $meaningfulKeywords = array_filter($words, fn($w) => mb_strlen($w) >= 4 && !in_array($w, $stopwords));
        $allSearchTerms = array_unique(array_merge($matchedEntities, $meaningfulKeywords));

        if (!empty($allSearchTerms)) {
            $kwQuery = Film::query();
            foreach ($allSearchTerms as $kw) {
                $kwQuery->orWhere('title', 'LIKE', "%{$kw}%")
                        ->orWhere('synopsis', 'LIKE', "%{$kw}%")
                        ->orWhereHas('tags', fn($q) => $q->where('tag_value', 'LIKE', "%{$kw}%"));
            }

            $kwMatches = $kwQuery->with('tags')->limit($limit)->get();
            foreach ($kwMatches as $f) {
                $currentScore = $scoredCandidates[$f->id] ?? 0.40;
                $scoredCandidates[$f->id] = max($currentScore, 0.80);
            }
        }

        if (empty($scoredCandidates)) {
            return Film::with(['genres', 'tags'])->orderByDesc('rating')->limit($limit)->get();
        }

        arsort($scoredCandidates);
        $candidateIds = array_slice(array_keys($scoredCandidates), 0, $limit);

        return Film::whereIn('id', $candidateIds)
            ->with(['genres', 'tags'])
            ->get()
            ->sortByDesc(fn($f) => $scoredCandidates[$f->id] ?? 0)
            ->values();
    }

    /**
     * Stage 2: LLM Verification, Relevance Filtering & Metadata Generation
     */
    private function curateAndFilterWithLlm(string $prompt, \Illuminate\Support\Collection $candidateFilms): array
    {
        if ($candidateFilms->isEmpty()) {
            return [
                'title' => Str::title($prompt),
                'description' => "Koleksi kurasi film berdasarkan tema: '{$prompt}'.",
                'selected_film_ids' => [],
            ];
        }

        $candidatesPayload = $candidateFilms->map(fn($f) => [
            'id' => $f->id,
            'title' => $f->title,
            'year' => $f->release_year,
            'genres' => $f->genres->pluck('name')->join(', '),
            'tags' => $f->tags->pluck('tag_value')->join(', '),
            'synopsis' => Str::limit(strip_tags($f->synopsis ?? ''), 80),
        ])->toArray();

        $candidatesJson = json_encode($candidatesPayload, JSON_UNESCAPED_SLASHES);

        $systemPrompt = <<<PROMPT
You are a master film curator and editor for a streaming platform.
A user asked for a curated film collection with this prompt:
"{$prompt}"

Here is the candidate list of films retrieved from our database:
{$candidatesJson}

TASK:
1. Examine each candidate film and determine if it TRULY belongs to the user's requested theme/franchise.
2. CRITICAL FILTERING RULE: STRICTLY EXCLUDE and DISCARD any film that does NOT fit the user's prompt (e.g. if the user asks for Marvel/MCU superhero films, you MUST REJECT unrelated animations like Upin & Ipin, unrelated romantic dramas, or random sports videos). Only keep films that genuinely belong to the requested universe/genre/theme.
3. Order the selected film IDs in the best viewing or narrative order.
4. Create a catchy, elegant Indonesian title (max 5-7 words) and an engaging 2-sentence description in natural Bahasa Indonesia.

Output ONLY valid JSON in this exact structure:
{
  "title": "Title in Indonesian",
  "description": "Description in Indonesian",
  "selected_film_ids": [id1, id2, id3, ...]
}
PROMPT;

        // Try Gemini first (super fast < 2s response)
        if (!empty($this->geminiApiKey)) {
            try {
                $endpoint = "{$this->geminiBaseUrl}/models/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";
                $response = Http::timeout(12)->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.2,
                    ]
                ]);

                if ($response->successful()) {
                    $parts = $response->json()['candidates'][0]['content']['parts'] ?? [];
                    $content = '';
                    foreach ($parts as $p) {
                        if (isset($p['text'])) $content .= $p['text'];
                    }

                    $data = json_decode(trim($content), true);
                    if (is_array($data) && !empty($data['selected_film_ids'])) {
                        $validIds = $candidateFilms->pluck('id')->toArray();
                        $filteredIds = array_values(array_filter($data['selected_film_ids'], fn($id) => in_array((int)$id, $validIds, true)));

                        if (!empty($filteredIds)) {
                            return [
                                'title' => trim($data['title'] ?? Str::title($prompt)),
                                'description' => trim($data['description'] ?? "Koleksi film bertema {$prompt}."),
                                'selected_film_ids' => $filteredIds,
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning('Gemini curation attempt exception: ' . $e->getMessage());
            }
        }

        // Fallback to NVIDIA Llama-3.1 8B
        if (!empty($this->nvidiaApiKey)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->nvidiaApiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post("{$this->nvidiaBaseUrl}/chat/completions", [
                    'model' => $this->llmModel,
                    'messages' => [
                        ['role' => 'user', 'content' => $systemPrompt]
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1024,
                ]);

                if ($response->successful()) {
                    $content = $response->json()['choices'][0]['message']['content'] ?? '';
                    $content = trim($content);

                    if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                        $content = $matches[1];
                    }

                    $data = json_decode(trim($content), true);
                    if (is_array($data) && !empty($data['selected_film_ids'])) {
                        $validIds = $candidateFilms->pluck('id')->toArray();
                        $filteredIds = array_values(array_filter($data['selected_film_ids'], fn($id) => in_array((int)$id, $validIds, true)));

                        if (!empty($filteredIds)) {
                            return [
                                'title' => trim($data['title'] ?? Str::title($prompt)),
                                'description' => trim($data['description'] ?? "Koleksi film bertema {$prompt}."),
                                'selected_film_ids' => $filteredIds,
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                Log::error('NVIDIA curation attempt exception: ' . $e->getMessage());
            }
        }

        return [
            'title' => Str::title($prompt),
            'description' => "Koleksi film kurasi AI untuk tema '{$prompt}'.",
            'selected_film_ids' => $candidateFilms->take(20)->pluck('id')->toArray(),
        ];
    }
}
