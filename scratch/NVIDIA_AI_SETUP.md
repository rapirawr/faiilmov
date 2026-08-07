# NVIDIA AI Search Integration - Setup Guide

## Overview
AI-powered search menggunakan NVIDIA API dengan pendekatan hybrid:
- **LLM Query Interpretation**: Natural language → structured filters
- **Embedding-based Similarity**: Film recommendations berdasarkan semantic similarity

## 1. Setup NVIDIA API Key

### Mendapatkan API Key (GRATIS untuk testing)
1. Kunjungi: https://build.nvidia.com
2. Sign in dengan NVIDIA account (atau buat baru)
3. Navigate ke: **API Catalog** → pilih model (contoh: `meta/llama-3.1-8b-instruct`)
4. Klik **Get API Key** → copy API key yang digenerate
5. Free tier: 1000 requests/day untuk testing (cukup untuk MVP)

### Konfigurasi di Laravel
```bash
# Edit file .env
NVIDIA_API_KEY=nvapi-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
NVIDIA_API_URL=https://integrate.api.nvidia.com/v1
NVIDIA_LLM_MODEL=meta/llama-3.1-8b-instruct
NVIDIA_EMBEDDING_MODEL=nvidia/nv-embed-v2
```

## 2. Migrasi Database

```bash
# Jalankan migrasi untuk menambah kolom ai_embeddings
php artisan migrate

# Jika ada error "column already exists", drop dulu:
php artisan migrate:rollback --step=1
php artisan migrate
```

## 3. Generate Embeddings untuk Film yang Sudah Ada

### Batch Processing (RECOMMENDED untuk film existing)
```bash
# Generate embeddings untuk semua film (akan masuk queue)
php artisan films:generate-embeddings

# Jalankan queue worker untuk process jobs
php artisan queue:work --queue=embeddings --tries=2 --timeout=15

# Dengan batch size custom (default 50)
php artisan films:generate-embeddings --batch-size=20

# Force regenerate (bahkan yang sudah ada embeddings)
php artisan films:generate-embeddings --force
```

### Auto-generate untuk Film Baru
Film baru yang ditambahkan akan otomatis generate embedding via event listener (async via queue).

## 4. Testing AI Search

### Test Natural Language Query
```bash
# Browser test
https://yourapp.test/browse?q=film action korea yang seru

# Expected: AI badge muncul dengan filter extracted: Action, Korea, rating tinggi
```

### Test AI Interpretation API Endpoint
```bash
curl -X GET "http://yourapp.test/search/ai-interpret?q=film sedih tentang keluarga"

# Expected JSON response:
{
  "success": true,
  "interpretation": {
    "genres": ["Drama"],
    "type": null,
    "min_rating": 6.5,
    "mood_keywords": ["sedih", "keluarga"],
    "similar_to_title": null,
    "year_range": null
  }
}
```

## 5. Model Pilihan & Estimasi Biaya

### LLM Models (Query Interpretation)
| Model | Speed | Accuracy | Cost (per 1M tokens) | Recommended For |
|-------|-------|----------|---------------------|-----------------|
| `meta/llama-3.1-8b-instruct` | Fast | Good | ~$0.20 | **MVP/Production** |
| `meta/llama-3.1-70b-instruct` | Slow | Excellent | ~$0.45 | High accuracy needs |
| `mistralai/mixtral-8x7b-instruct` | Medium | Good | ~$0.24 | Alternative |

### Embedding Models
| Model | Dimension | Performance | Cost |
|-------|-----------|-------------|------|
| `nvidia/nv-embed-v2` | 4096 | Best | ~$0.20/1M tokens |
| `nvidia/nv-embed-qa-4` | 1024 | Faster | ~$0.15/1M tokens |

### Estimasi Biaya Real (1000 users active/day)
- **LLM Interpretation**: 1000 searches × 50 tokens = 50K tokens/day = **$0.01/day** (~$3/bulan)
- **Embeddings** (one-time): 5000 films × 200 tokens = 1M tokens = **$0.20 sekali**
- **Total MVP**: < $5/bulan untuk 30K searches

### Free Tier Limits
- 1000 API calls/day GRATIS
- Cukup untuk ~100-200 MAU (monthly active users) di early stage
- Upgrade ke paid tier saat traffic meningkat

## 6. Optimization & Caching

### Cache Strategy (sudah implemented)
- **Query Interpretation**: Cache 6 jam per unique query
- **Autocomplete**: Cache 5 menit per query
- **Popular Films**: Cache 15 menit

### Rate Limiting (TODO - implement jika perlu)
```php
// Di routes/web.php atau middleware
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/search/ai-interpret', ...);
});
```

## 7. Fallback Mechanism

AI search memiliki fallback otomatis:
- **API timeout/error** → fallback ke relevance search biasa
- **API key tidak dikonfigurasi** → search tetap jalan tanpa AI
- **Embedding null** → similarity fallback ke genre-based matching

## 8. Monitoring & Debugging

### Check Logs
```bash
# Laravel log untuk AI errors
tail -f storage/logs/laravel.log | grep NVIDIA

# Queue job failures
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Debug Mode
```bash
# Test single film embedding
php artisan tinker
>>> $film = App\Models\Film::first();
>>> $nvidia = app(App\Services\NvidiaAiService::class);
>>> $result = $nvidia->generateFilmEmbedding($film);
>>> dd($result);
```

## 9. Production Deployment Checklist

- [ ] Set `NVIDIA_API_KEY` di production .env
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Generate embeddings: `php artisan films:generate-embeddings`
- [ ] Setup queue worker daemon (Supervisor/systemd)
- [ ] Configure cache driver (Redis recommended)
- [ ] Monitor API usage di NVIDIA dashboard
- [ ] Setup rate limiting jika perlu

## 10. Upgrade Path

### Saat Traffic Meningkat
1. **Upgrade NVIDIA tier**: Pay-as-you-go (~$0.20/1M tokens)
2. **Local embedding storage**: Store embeddings di vector DB (Milvus/Pinecone) untuk similarity search yang lebih cepat
3. **Batch embedding updates**: Jalankan `films:generate-embeddings` via cron weekly untuk film baru
4. **CDN caching**: Cache AI responses di CDN edge

### Alternative (jika ingin self-hosted)
- Replace NVIDIA dengan Ollama (local LLM) + sentence-transformers
- Trade-off: Lebih murah tapi perlu server GPU

## Troubleshooting

### "NVIDIA API key not configured"
- Pastikan `NVIDIA_API_KEY` ada di `.env`
- Restart Laravel: `php artisan config:clear`

### "Failed to generate embedding"
- Check internet connection
- Verify API key valid
- Check NVIDIA API status: https://status.nvidia.com

### Queue jobs stuck
```bash
php artisan queue:restart
php artisan queue:work --queue=embeddings --tries=2
```

### AI interpretation tidak muncul
- Check cache: `php artisan cache:clear`
- Check API response di logs
- Fallback otomatis ke search biasa (by design)
