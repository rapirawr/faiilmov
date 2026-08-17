<?php

namespace App\Console\Commands;

use App\Services\CollectionClusterService;
use Illuminate\Console\Command;

class RebuildAutoCollectionsCommand extends Command
{
    protected $signature = 'collections:rebuild-auto {--threshold=5 : Minimum number of films to publish collection}';
    protected $description = 'Cluster film tags and generate/rebuild auto franchise & thematic collections';

    public function handle(CollectionClusterService $clusterService): int
    {
        $threshold = (int)$this->option('threshold');
        $this->info("Memulai rebuild auto-collections dengan threshold {$threshold} film...");

        $res = $clusterService->generateAutoCollections($threshold);

        $this->info(sprintf(
            "Rebuild selesai! Created: %d, Updated: %d, Published: %d, Draft: %d.",
            $res['created'],
            $res['updated'],
            $res['published'],
            $res['draft']
        ));

        return Command::SUCCESS;
    }
}
