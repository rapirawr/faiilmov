<?php

namespace App\Console\Commands;

use App\Models\Film;
use App\Models\FilmTag;
use App\Models\FilmEmbedding;
use App\Services\FilmTaggingService;
use App\Services\GeminiEmbeddingService;
use App\Jobs\TagFilmJob;
use Illuminate\Console\Command;

class BackfillFilmTagsAndEmbeddingsCommand extends Command
{
    protected $signature = 'collections:backfill 
                            {--limit= : Limit the number of films to process} 
                            {--queue : Dispatch as background queue jobs}
                            {--force : Re-tag and re-embed even if already exists}';

    protected $description = 'Backfill AI tags and Gemini vector embeddings for all films in catalog';

    public function handle(FilmTaggingService $taggingService, GeminiEmbeddingService $gemini): int
    {
        $this->info('Memulai proses backfill tags & embeddings untuk katalog film...');

        $query = Film::with(['genres', 'actors'])->orderBy('id', 'desc');

        if (!$this->option('force')) {
            // Find films without tags or without embeddings
            $query->where(function ($q) {
                $q->whereDoesntHave('tags')
                  ->orWhereDoesntHave('filmEmbedding');
            });
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int)$limit);
        }

        $films = $query->get();
        $total = $films->count();

        if ($total === 0) {
            $this->info('Semua film sudah memiliki tags dan embeddings!');
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$total} film yang perlu diproses.");

        if ($this->option('queue')) {
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($films as $film) {
                TagFilmJob::dispatch($film->id);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("Berhasil mendispatch {$total} TagFilmJob ke antrean!");
            return Command::SUCCESS;
        }

        // Direct Execution with Fast Batch Embeddings & Rule Tagging
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processedCount = 0;
        $textsToBatchEmbed = [];
        $filmsMap = [];

        foreach ($films as $film) {
            // 1. Tagging (Relation + LLM heuristics)
            $relationTags = $taggingService->extractRelationTags($film);
            foreach ($relationTags as $tagType => $tagValue) {
                FilmTag::updateOrCreate(
                    [
                        'film_id' => $film->id,
                        'tag_type' => $tagType,
                        'tag_value' => $tagValue,
                    ],
                    [
                        'confidence' => 0.95,
                        'source' => 'relation',
                    ]
                );
            }

            // Prepare text for batch embedding
            $genreNames = $film->genres->pluck('name')->join(', ');
            $tagsList = $film->tags->pluck('tag_value')->join(', ');

            $textToEmbed = trim(implode(' | ', array_filter([
                "Title: {$film->title}",
                $genreNames ? "Genres: {$genreNames}" : null,
                $tagsList ? "Tags: {$tagsList}" : null,
                $film->subject_type === 'dracin' ? 'Drama China' : ($film->subject_type === 'series' ? 'TV Series' : 'Movie'),
                $film->release_year ? "Year: {$film->release_year}" : null,
                "Synopsis: " . strip_tags($film->synopsis ?? ''),
            ])));

            $textsToBatchEmbed[$film->id] = $textToEmbed;
            $filmsMap[$film->id] = $film;

            // Batch embed every 50 items
            if (count($textsToBatchEmbed) >= 50) {
                $this->processEmbeddingsBatch($gemini, $textsToBatchEmbed);
                $textsToBatchEmbed = [];
            }

            $bar->advance();
            $processedCount++;
        }

        // Remaining embeddings
        if (!empty($textsToBatchEmbed)) {
            $this->processEmbeddingsBatch($gemini, $textsToBatchEmbed);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Backfill selesai! {$processedCount} film berhasil diproses.");

        return Command::SUCCESS;
    }

    private function processEmbeddingsBatch(GeminiEmbeddingService $gemini, array $textsToBatchEmbed): void
    {
        if (!$gemini->isConfigured()) {
            return;
        }

        $embeddings = $gemini->batchEmbed($textsToBatchEmbed);

        foreach ($embeddings as $filmId => $vector) {
            if (!empty($vector)) {
                FilmEmbedding::updateOrCreate(
                    ['film_id' => $filmId],
                    [
                        'embedding' => $vector,
                        'model_version' => $gemini->getModelVersion(),
                    ]
                );
            }
        }
    }
}
