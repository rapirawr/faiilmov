<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Daily Sync for Films & Actors
Illuminate\Support\Facades\Schedule::job(new \App\Jobs\SyncFilmsJob(null, false, 15))->dailyAt('02:00');
Illuminate\Support\Facades\Schedule::job(new \App\Jobs\SyncActorsJob(null, false))->dailyAt('03:00');

// Scheduled System Health Monitoring & Near-Real-Time Content Analytics
Illuminate\Support\Facades\Schedule::command('monitor:recompute-status')->everyMinute();
Illuminate\Support\Facades\Schedule::command('analytics:rollup-daily-stats')->everyFiveMinutes();
