<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionWatchOrder;
use App\Models\Film;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WatchOrderService
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
     * Generate release and chronological watch orders for franchise collection
     */
    public function generateSuggestedOrder(Collection $collection): void
    {
        $films = $collection->films()->orderBy('release_year', 'asc')->get();

        if ($films->count() < 3) {
            return;
        }

        // 1. Release Watch Order (Ordered strictly by release_year and ID)
        CollectionWatchOrder::where('collection_id', $collection->id)
            ->where('order_type', 'release')
            ->delete();

        $seq = 1;
        foreach ($films as $film) {
            CollectionWatchOrder::create([
                'collection_id' => $collection->id,
                'film_id' => $film->id,
                'order_type' => 'release',
                'sequence' => $seq++,
                'note' => $film->release_year ? "Rilis tahun {$film->release_year}" : null,
            ]);
        }

        // 2. Chronological (In-Universe Timeline) Watch Order via LLM
        $chronologicalList = $this->generateChronologicalOrderWithLlm($collection->name, $films);

        if (!empty($chronologicalList)) {
            $insertedFilmIds = [];
            foreach ($chronologicalList as $item) {
                $fId = (int)($item['film_id'] ?? 0);
                $sequence = (int)($item['sequence'] ?? 1);
                $note = trim((string)($item['note'] ?? ''));

                if ($films->contains('id', $fId) && !in_array($fId, $insertedFilmIds, true)) {
                    CollectionWatchOrder::updateOrCreate(
                        [
                            'collection_id' => $collection->id,
                            'film_id' => $fId,
                            'order_type' => 'chronological',
                        ],
                        [
                            'sequence' => $sequence,
                            'note' => !empty($note) ? $note : null,
                        ]
                    );
                    $insertedFilmIds[] = $fId;
                }
            }

            // Append any missed films
            $missingFilms = $films->whereNotIn('id', $insertedFilmIds);
            $nextSeq = count($insertedFilmIds) + 1;
            foreach ($missingFilms as $mf) {
                CollectionWatchOrder::updateOrCreate(
                    [
                        'collection_id' => $collection->id,
                        'film_id' => $mf->id,
                        'order_type' => 'chronological',
                    ],
                    [
                        'sequence' => $nextSeq++,
                        'note' => null,
                    ]
                );
            }
        }
    }

    /**
     * Query LLM for chronological in-universe timeline
     */
    private function generateChronologicalOrderWithLlm(string $franchiseName, \Illuminate\Support\Collection $films): array
    {
        if (empty($this->nvidiaApiKey)) {
            // Default fallback: copy release order
            return $films->map(fn($f, $idx) => [
                'film_id' => $f->id,
                'sequence' => $idx + 1,
                'note' => null,
            ])->toArray();
        }

        $filmListStr = "";
        foreach ($films as $f) {
            $filmListStr .= "- ID: {$f->id} | Title: {$f->title} | Year: {$f->release_year}\n";
        }

        $systemPrompt = <<<PROMPT
You are a film historian and lore expert.
Given a list of films from the franchise "{$franchiseName}", determine the strictly CHRONOLOGICAL in-universe storyline watch order (not release date order).
Provide a sequence number starting from 1 for every film, along with an Indonesian concise note explaining its place in the lore (e.g., "Awal mula sebelum perang", "Prekuel era 1940-an", "Sekuel langsung film X", "Side story / spin-off").

Output ONLY a valid JSON array of objects:
[
  {
    "film_id": 123,
    "sequence": 1,
    "note": "Keterangan kronologi singkat"
  }
]
PROMPT;

        try {
            $response = Http::withToken($this->nvidiaApiKey)
                ->timeout(15)
                ->post("{$this->nvidiaBaseUrl}/chat/completions", [
                    'model' => $this->llmModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Franchise: {$franchiseName}\nFilms:\n{$filmListStr}"]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 800,
                ]);

            if ($response->successful()) {
                $content = trim($response->json()['choices'][0]['message']['content'] ?? '');
                if (preg_match('/```json\s*(.*?)\s*```/s', $content, $m)) {
                    $content = $m[1];
                } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $m)) {
                    $content = $m[1];
                }

                $json = json_decode(trim($content), true);
                if (is_array($json) && count($json) > 0) {
                    return $json;
                }
            }
        } catch (Exception $e) {
            Log::warning("WatchOrderService LLM chronological ordering failed: " . $e->getMessage());
        }

        return $films->map(fn($f, $idx) => [
            'film_id' => $f->id,
            'sequence' => $idx + 1,
            'note' => null,
        ])->toArray();
    }
}
