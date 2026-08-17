<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;

class RecomputeApiStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:recompute-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recomputes API service statuses and uptime statistics from rolling 24h health logs';

    /**
     * Execute the console command.
     */
    public function handle(SystemHealthService $healthService): int
    {
        $this->info('Recomputing external API service status and uptime...');
        $healthService->recomputeStatus();
        $this->info('API status recomputation complete.');

        return Command::SUCCESS;
    }
}
