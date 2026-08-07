# NVIDIA AI Search - Request/Response Examples

## 1. LLM Query Interpretation API

### Request Format (OpenAI-compatible)
```bash
POST https://integrate.api.nvidia.com/v1/chat/completions
Authorization: Bearer nvapi-xxxxxxxxxxxxx
Content-Type: application/json

{
  "model": "meta/llama-3.1-8b-instruct",
  "messages": [
    {
      "role": "system",
      "content": "You are a film search query interpreter. Extract structured filters from natural language queries..."
    },
    {
      "role": "user",
      "content": "film action korea yang seru buat malam minggu"
    }
  ],
  "temperature": 0.2,
  "max_tokens": 200
}
```

### Response Format
```json
{
  "id": "chatcmpl-abc123",
  "object": "chat.completion",
  "created": 1723000000,
  "model": "meta/llama-3.1-8b-instruct",
  "choices": [
    {
      "index": 0,
      "message": {
        "role": "assistant",
        "content": "{\"genres\": [\"Action\"], \"type\": \"movie\", \"min_rating\": 7.0, \"mood_keywords\": [\"seru\", \"korea\"], \"similar_to_title\": null, \"year_range\": null}"
      },
      "finish_reason": "stop"
    }
  ],
  "usage": {
    "prompt_tokens": 450,
    "completion_tokens": 45,
    "total_tokens": 495
  }
}
```

## 2. Embedding Generation API

### Request Format
```bash
POST https://integrate.api.nvidia.com/v1/embeddings
Authorization: Bearer nvapi-xxxxxxxxxxxxx
Content-Type: application/json

{
  "model": "nvidia/nv-embed-v2",
  "input": "The Avengers Action Superhero team assembles to save the world from alien invasion. Released: 2012",
  "encoding_format": "float"
}
```

### Response Format
```json
{
  "object": "list",
  "data": [
    {
      "object": "embedding",
      "index": 0,
      "embedding": [
        0.0234, -0.0156, 0.0891, ..., 0.0023
      ]
    }
  ],
  "model": "nvidia/nv-embed-v2",
  "usage": {
    "prompt_tokens": 32,
    "total_tokens": 32
  }
}
```

**Note**: `nvidia/nv-embed-v2` menghasilkan vector 4096 dimensi.

## 3. Laravel Service Usage Examples

### NvidiaAiService - Query Interpretation
```php
use App\Services\NvidiaAiService;

$nvidia = app(NvidiaAiService::class);

// Interpret natural language query
$interpretation = $nvidia->interpretQuery("film sedih tentang keluarga");

// Result:
// [
//   'genres' => ['Drama'],
//   'type' => null,
//   'min_rating' => 6.5,
//   'mood_keywords' => ['sedih', 'keluarga'],
//   'similar_to_title' => null,
//   'year_range' => null
// ]
```

### NvidiaAiService - Film Embedding
```php
$film = Film::with('genres')->find(1);

// Generate embedding for a film
$embedding = $nvidia->generateFilmEmbedding($film);

// Result: array of 4096 floats
// [0.0234, -0.0156, 0.0891, ..., 0.0023]
```

### NvidiaAiService - Cosine Similarity
```php
$film1Embedding = json_decode($film1->ai_embeddings, true);
$film2Embedding = json_decode($film2->ai_embeddings, true);

$similarity = $nvidia->cosineSimilarity($film1Embedding, $film2Embedding);

// Result: float between 0.0 - 1.0
// 0.85 = very similar
// 0.20 = not similar
```

## 4. FilmSearchService - AI Search
```php
use App\Services\FilmSearchService;

$searchService = app(FilmSearchService::class);

// Natural language search (automatically uses AI interpretation)
$results = $searchService->search(
    query: "series kayak mirzapur tapi lebih pendek",
    filters: ['sort' => 'rating_desc'],
    perPage: 30,
    ip: request()->ip()
);

// Get AI interpretation without search
$interpretation = $searchService->getAiInterpretation("horror movies from 2023");
```

## 5. Similar Films by Embedding
```php
use App\Services\FilmSearchService;

$searchService = app(FilmSearchService::class);
$film = Film::with('genres')->find(1);

// Get similar films using embeddings (or fallback to genre-based)
$similarFilms = $searchService->getSimilarFilms($film, limit: 6);

// Returns array of Film models sorted by similarity
```

## 6. Queue Job - Background Embedding Generation
```php
use App\Jobs\GenerateFilmEmbeddingJob;

// Dispatch job to queue
GenerateFilmEmbeddingJob::dispatch($filmId)
    ->onQueue('embeddings')
    ->delay(now()->addSeconds(5));

// Process jobs manually
php artisan queue:work --queue=embeddings
```

## 7. Artisan Command - Batch Processing
```bash
# Generate embeddings for all films without embeddings
php artisan films:generate-embeddings

# Custom batch size (process 100 at a time)
php artisan films:generate-embeddings --batch-size=100

# Force regenerate all embeddings (even existing ones)
php artisan films:generate-embeddings --force

# Output:
# Processing 523 films in batches of 50...
# ================================================== 523/523
# ✓ Dispatched 523 embedding generation jobs to queue 'embeddings'
# Run: php artisan queue:work --queue=embeddings
```

## 8. Frontend - AI Search Indicator
```blade
{{-- Browse page - AI interpretation badge --}}
@if(!empty($aiInterpretation) && !empty($searchQuery))
    <div class="glass-chip p-4 rounded-2xl border border-amber-500/30">
        <p class="text-xs font-bold text-amber-300">
            AI menerjemahkan pencarian Anda:
        </p>
        {{-- Badges for extracted filters --}}
        @if(!empty($aiInterpretation['genres']))
            @foreach($aiInterpretation['genres'] as $genre)
                <span class="badge">{{ $genre }}</span>
            @endforeach
        @endif
    </div>
@endif
```

## 9. API Error Handling Examples

### Timeout (Automatic Fallback)
```php
// If NVIDIA API times out (3 seconds), automatically falls back to standard search
try {
    $interpretation = $nvidia->interpretQuery($query);
} catch (Exception $e) {
    // Fallback to relevance search (no AI)
    Log::warning('NVIDIA API timeout, using fallback search');
    return $this->buildQuery($query, $filters, $perPage);
}
```

### API Key Invalid
```php
// Response:
{
  "error": {
    "message": "Invalid API key",
    "type": "invalid_request_error",
    "code": "invalid_api_key"
  }
}

// Service returns null, search continues without AI
```

### Rate Limit Exceeded
```php
// Response:
{
  "error": {
    "message": "Rate limit exceeded",
    "type": "rate_limit_error",
    "code": "rate_limit_exceeded"
  }
}

// Cache prevents repeated requests for same query
// Fallback to standard search
```

## 10. Test Queries & Expected Results

### Test Case 1: Genre + Mood
```
Query: "film action korea yang seru"
Expected AI Interpretation:
{
  "genres": ["Action"],
  "mood_keywords": ["seru", "korea"],
  "type": "movie",
  "min_rating": 7.0
}
Result: Action movies with "korea" in title/synopsis, rating ≥ 7.0
```

### Test Case 2: Similar To
```
Query: "series kayak mirzapur tapi lebih pendek"
Expected AI Interpretation:
{
  "similar_to_title": "mirzapur",
  "type": "series",
  "mood_keywords": ["crime", "thriller"]
}
Result: Crime/thriller series with genres matching Mirzapur
```

### Test Case 3: Year Range
```
Query: "horror movies from 2023"
Expected AI Interpretation:
{
  "genres": ["Horror"],
  "type": "movie",
  "year_range": {"min": 2023, "max": 2023}
}
Result: Horror movies released in 2023
```

### Test Case 4: Emotional + Family
```
Query: "film sedih tentang keluarga"
Expected AI Interpretation:
{
  "genres": ["Drama"],
  "mood_keywords": ["sedih", "keluarga"],
  "min_rating": 6.5
}
Result: Drama films about family (sedih/keluarga in synopsis)
```

## 11. Performance Metrics

### Query Interpretation
- **Average latency**: 300-800ms (first request)
- **Cached latency**: <10ms (subsequent same query)
- **Timeout**: 3 seconds (then fallback)

### Embedding Generation
- **Per film**: 500-1000ms
- **Batch 100 films**: ~60 seconds (via queue)
- **Cache**: Stored in DB (ai_embeddings column)

### Similar Film Search
- **With embeddings**: 100-200ms (100 candidates)
- **Fallback genre**: 50ms (simple SQL join)

## 12. Database Schema

### films table - ai_embeddings column
```sql
-- Column type: JSON (stores array of 4096 floats)
ALTER TABLE films ADD COLUMN ai_embeddings JSON NULL;
CREATE INDEX idx_film_embeddings ON films(ai_embeddings);

-- Example stored value:
-- [0.0234, -0.0156, 0.0891, ..., 0.0023] (4096 elements)

-- Query films with embeddings
SELECT COUNT(*) FROM films WHERE ai_embeddings IS NOT NULL;
```

### cache table - query interpretation cache
```sql
-- Cached entries (key format: nvidia_interpret_{md5_hash})
SELECT * FROM cache WHERE key LIKE 'nvidia_interpret_%';

-- Clear AI cache
DELETE FROM cache WHERE key LIKE 'nvidia_interpret_%';
```
