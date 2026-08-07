<?php

namespace App\Listeners;

use App\Events\FilmCreated;
use App\Events\FilmUpdated;
use App\Jobs\GenerateFilmEmbeddingJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateFilmEmbeddingOnUpdate implements ShouldQueue
{
    public $queue = 'embeddings';
    public $delay = 5;

    public function handle(FilmCreated|FilmUpdated $event): void
    {
        $film = $event->film;

        if ($event instanceof FilmUpdated) {
            $changed = $film->getChanges();
            $significantFields = ['title', 'synopsis', 'release_year'];
            if (!array_intersect($significantFields, array_keys($changed))) {
                return;
            }
        }

        GenerateFilmEmbeddingJob::dispatch($film->id)
            ->delay(now()->addSeconds($this->delay));
    }

    public function failed(FilmCreated|FilmUpdated $event, \Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error(
            "GenerateFilmEmbeddingOnUpdate failed for film {$event->film->id}: " . $exception->getMessage()
        );
    }
}
