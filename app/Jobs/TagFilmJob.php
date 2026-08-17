<?php

namespace App\Jobs;

use App\Models\Film;
use App\Services\FilmTaggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TagFilmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    public function __construct(
        private int $filmId
    ) {}

    public function handle(FilmTaggingService $taggingService): void
    {
        $film = Film::with(['genres', 'actors'])->find($this->filmId);

        if (!$film) {
            Log::warning("TagFilmJob: Film {$this->filmId} not found.");
            return;
        }

        try {
            $taggingService->tagFilm($film);
            Log::info("TagFilmJob: Successfully tagged and embedded film: {$film->title} (ID: {$film->id})");
        } catch (\Exception $e) {
            Log::error("TagFilmJob failed for film {$this->filmId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("TagFilmJob permanently failed for film {$this->filmId}: " . $exception->getMessage());
    }
}
