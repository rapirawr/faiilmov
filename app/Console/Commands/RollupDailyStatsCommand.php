<?php

namespace App\Console\Commands;

use App\Services\ContentAnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RollupDailyStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:rollup-daily-stats {--date= : The date to rollup (YYYY-MM-DD), defaults to today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates watch history records into film daily stats';

    /**
     * Execute the console command.
     */
    public function handle(ContentAnalyticsService $analyticsService): int
    {
        $dateParam = $this->option('date');
        $date = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        $this->info("Rolling up film daily stats for date: {$date->toDateString()}...");
        $analyticsService->rollupDailyStats($date);
        $this->info('Daily stats rollup completed.');

        return Command::SUCCESS;
    }
}
