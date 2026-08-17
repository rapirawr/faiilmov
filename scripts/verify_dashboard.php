<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApiHealthLog;
use App\Models\ApiServiceStatus;
use App\Models\Film;
use App\Models\FilmDailyStat;
use App\Models\User;
use App\Models\WatchHistory;
use App\Models\WatchParty;
use App\Services\ContentAnalyticsService;
use App\Services\DashboardSnapshotService;
use App\Services\SystemHealthService;
use App\Services\UserAnalyticsService;

echo "=== FAIILMOV DEVELOPER DASHBOARD VERIFICATION ===\n\n";

$healthService = app(SystemHealthService::class);
$contentService = app(ContentAnalyticsService::class);
$userService = app(UserAnalyticsService::class);
$snapshotService = app(DashboardSnapshotService::class);

// 1. Test logApiCall
echo "1. Testing SystemHealthService::logApiCall...\n";
$healthService->logApiCall('moviebox', 'https://api6.aoneroom.com', true, 200, 145, null);
$healthService->logApiCall('anichin', 'api.anichin.bio', true, 200, 220, null);
$healthService->logApiCall('nvidia', 'integrate.api.nvidia.com', true, 200, 480, null);
$healthService->logApiCall('itunes', 'itunes.apple.com', true, 200, 310, null);

$recentLogs = ApiHealthLog::latest('checked_at')->take(4)->get();
echo "   Logged API health entries count: " . $recentLogs->count() . "\n";
assert($recentLogs->count() >= 4, "Should have logged API health entries");
echo "   [PASS] API Health logs recorded successfully.\n\n";

// 2. Test status determination & simulation
echo "2. Testing Status Determination & Failover Simulation...\n";
// Simulate 3 consecutive failures for one host
$testHost = 'https://api.inmoviebox.com';
$healthService->logApiCall('moviebox', $testHost, false, 503, 3000, 'Service Unavailable');
$healthService->logApiCall('moviebox', $testHost, false, 503, 3000, 'Service Unavailable');
$healthService->logApiCall('moviebox', $testHost, false, 503, 3000, 'Service Unavailable');

$status = ApiServiceStatus::where('service', 'moviebox')->where('host', $testHost)->first();
echo "   Host status after 3 failures: {$status->current_status} (consecutive failures: {$status->consecutive_failures})\n";
assert($status->current_status === 'down', "Host should be marked as DOWN after 3 failures");

$healthService->recomputeStatus();
$status->refresh();
echo "   Host status after recompute: {$status->current_status}\n";
assert($status->current_status === 'down', "Host should remain DOWN after recompute");

// Simulate recovery
$healthService->logApiCall('moviebox', $testHost, true, 200, 120, null);
$status->refresh();
echo "   Host status after recovery: {$status->current_status} (consecutive failures: {$status->consecutive_failures})\n";
assert($status->current_status === 'up', "Host should recover to UP");
echo "   [PASS] Failover and recovery status logic verified.\n\n";

// 3. Test Content & User Analytics
echo "3. Testing ContentAnalyticsService & UserAnalyticsService...\n";
$contentService->rollupDailyStats();
$topFilms = $contentService->getTopFilms(5);
$totalViews = $contentService->getTotalViewsToday();
$totalWatchTime = $contentService->getTotalWatchTimeToday();

echo "   Top films retrieved: " . $topFilms->count() . "\n";
echo "   Total views today: {$totalViews}\n";
echo "   Total watch time today (sec): {$totalWatchTime}\n";

$dau = $userService->getDAU();
$signups = $userService->getSignupsToday();
$activeParties = $userService->getActiveWatchParties();
$totalUsers = $userService->getTotalUsers();

echo "   DAU: {$dau}, Signups: {$signups}, Active Nobar: {$activeParties}, Total Users: {$totalUsers}\n";
echo "   [PASS] Analytics services aggregated without errors.\n\n";

// 4. Test DashboardSnapshotService
echo "4. Testing DashboardSnapshotService payload structure...\n";
$snapshot = $snapshotService->getSnapshot();

assert($snapshot['status'] === 'success', "Snapshot status must be success");
assert(isset($snapshot['meta']['server_time']), "Snapshot meta.server_time must exist");
assert(isset($snapshot['system_health']['services']), "Snapshot system_health.services must exist");
assert(isset($snapshot['system_health']['queue']), "Snapshot system_health.queue must exist");
assert(isset($snapshot['content_performance']['top_films']), "Snapshot content_performance.top_films must exist");
assert(isset($snapshot['user_analytics']['dau']), "Snapshot user_analytics.dau must exist");
assert(isset($snapshot['activity_feed']), "Snapshot activity_feed must exist");

echo "   Snapshot meta time: {$snapshot['meta']['server_time']}\n";
echo "   Services monitored: " . count($snapshot['system_health']['services']) . "\n";
echo "   Top films count: " . count($snapshot['content_performance']['top_films']) . "\n";
echo "   Activity feed items: " . count($snapshot['activity_feed']) . "\n";
echo "   [PASS] Complete Dashboard Snapshot payload verified!\n\n";

echo "ALL VERIFICATION CHECKS PASSED SUCCESSFULLY!\n";
