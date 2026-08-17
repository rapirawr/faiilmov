<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Services\WatchOrderService;
use Illuminate\Console\Command;

class GenerateWatchOrdersCommand extends Command
{
    protected $signature = 'collections:generate-watch-orders {--collection-id= : Generate for a specific collection ID} {--force : Re-generate even if watch order exists}';
    protected $description = 'Generate release and chronological watch orders for auto franchise collections';

    public function handle(WatchOrderService $watchOrderService): int
    {
        $this->info('Memulai pembuatan watch order franchise...');

        $query = Collection::query();

        if ($colId = $this->option('collection-id')) {
            $query->where('id', $colId);
        } else {
            $query->where('type', 'auto');
        }

        if (!$this->option('force')) {
            $query->whereDoesntHave('watchOrders');
        }

        $collections = $query->withCount('films')->having('films_count', '>=', 3)->get();

        if ($collections->isEmpty()) {
            $this->info('Tidak ada koleksi yang membutuhkan watch order.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($collections->count());
        $bar->start();

        foreach ($collections as $collection) {
            $watchOrderService->generateSuggestedOrder($collection);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Berhasil membuat watch order untuk {$collections->count()} koleksi!");

        return Command::SUCCESS;
    }
}
