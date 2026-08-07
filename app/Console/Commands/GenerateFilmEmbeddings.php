<?php

namespace App\Console\Commands;

use App\Models\Film;
use App\Jobs\GenerateFilmEmbeddingJob;
use Illuminate\Console\Command;

class GenerateFilmEmbeddings extends Command
{
    protected $signature = 'films:generate-embeddings 
                            {--batch-size=50 : Number of films to process per batch}
                            {--force : Regenerate embeddings even if they already exist}';

    protected $description = 'Generate AI embeddings for all films using NVIDIA API (queued batch processing)';

    public function handle(): int
    {
        $batchSize = (int)$this->option('batch-size');
        $force = $this->option('force');

        $query = Film::with('genres');

        if (!$force) {
            $query->whereNull('ai_embeddings');
        }

        $totalFilms = $query->count();

        if ($totalFilms === 0) {
            $this->info('No films to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$totalFilms} films in batches of {$batchSize}...");

        $bar = $this->output->createProgressBar($totalFilms);
        $bar->start();

        $query->chunk($batchSize, function ($films) use ($bar) {
            foreach ($films as $film) {
                GenerateFilmEmbeddingJob::dispatch($film->id)
                    ->onQueue('embeddings');
                
                $bar->advance();
            }

            sleep(1);
        });

        $bar->finish();
        $this->newLine();
        
        $this->info("✓ Dispatched {$totalFilms} embedding generation jobs to queue 'embeddings'");
        $this->info("Run: php artisan queue:work --queue=embeddings");

        return self::SUCCESS;
    }
}
