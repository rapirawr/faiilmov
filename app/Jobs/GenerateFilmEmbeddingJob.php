<?php

namespace App\Jobs;

use App\Models\Film;
use App\Services\NvidiaAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateFilmEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 10;

    public function __construct(
        private int $filmId
    ) {}

    public function handle(NvidiaAiService $nvidia): void
    {
        $film = Film::with('genres')->find($this->filmId);
        
        if (!$film) {
            Log::warning("Film {$this->filmId} not found for embedding generation");
            return;
        }

        if (!$nvidia->isConfigured()) {
            Log::info("NVIDIA API not configured, skipping embedding for film {$film->id}");
            return;
        }

        $embedding = $nvidia->generateFilmEmbedding($film);

        if ($embedding) {
            $film->update(['ai_embeddings' => json_encode($embedding)]);
            Log::info("Generated embedding for film: {$film->title} (ID: {$film->id})");
        } else {
            Log::warning("Failed to generate embedding for film: {$film->title} (ID: {$film->id})");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateFilmEmbeddingJob failed for film {$this->filmId}: " . $exception->getMessage());
    }
}
