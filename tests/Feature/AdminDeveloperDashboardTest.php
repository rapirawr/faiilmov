<?php

namespace Tests\Feature;

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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeveloperDashboardTest extends TestCase
{
    use RefreshDatabase;
    public function test_system_health_logging_and_status_recomputation(): void
    {
        $healthService = app(SystemHealthService::class);

        // 1. Log a success call
        $healthService->logApiCall('moviebox', 'https://api6.aoneroom.com', true, 200, 180, null);

        $status = ApiServiceStatus::where('service', 'moviebox')
            ->where('host', 'https://api6.aoneroom.com')
            ->first();

        $this->assertNotNull($status);
        $this->assertEquals('up', $status->current_status);
        $this->assertEquals(0, $status->consecutive_failures);

        // 2. Simulate 3 consecutive failures to trigger DOWN status
        $healthService->logApiCall('moviebox', 'https://api6.aoneroom.com', false, 502, 3000, 'Bad Gateway');
        $healthService->logApiCall('moviebox', 'https://api6.aoneroom.com', false, 502, 3000, 'Bad Gateway');
        $healthService->logApiCall('moviebox', 'https://api6.aoneroom.com', false, 502, 3000, 'Bad Gateway');

        $status->refresh();
        $this->assertEquals('down', $status->current_status);
        $this->assertEquals(3, $status->consecutive_failures);

        // 3. Recompute full status
        $healthService->recomputeStatus();
        $status->refresh();
        $this->assertEquals('down', $status->current_status);

        // 4. Log recovery call
        $healthService->logApiCall('moviebox', 'https://api6.aoneroom.com', true, 200, 150, null);
        $status->refresh();
        $this->assertEquals('up', $status->current_status);
        $this->assertEquals(0, $status->consecutive_failures);
    }

    public function test_content_and_user_analytics_aggregation(): void
    {
        $contentService = app(ContentAnalyticsService::class);
        $userService = app(UserAnalyticsService::class);

        // Test DAU computation
        $dau = $userService->getDAU();
        $this->assertIsInt($dau);

        // Test signups today
        $signups = $userService->getSignupsToday();
        $this->assertIsInt($signups);

        // Test total views & watch time
        $views = $contentService->getTotalViewsToday();
        $this->assertIsInt($views);

        $watchTime = $contentService->getTotalWatchTimeToday();
        $this->assertIsInt($watchTime);

        // Test snapshot payload structure
        $snapshotService = app(DashboardSnapshotService::class);
        $payload = $snapshotService->getSnapshot();

        $this->assertEquals('success', $payload['status']);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('system_health', $payload);
        $this->assertArrayHasKey('content_performance', $payload);
        $this->assertArrayHasKey('user_analytics', $payload);
        $this->assertArrayHasKey('activity_feed', $payload);

        $this->assertArrayHasKey('services', $payload['system_health']);
        $this->assertArrayHasKey('queue', $payload['system_health']);
        $this->assertArrayHasKey('server', $payload['system_health']);
        $this->assertArrayHasKey('top_films', $payload['content_performance']);
        $this->assertArrayHasKey('views_trend_7d', $payload['content_performance']);
        $this->assertArrayHasKey('signup_trend_7d', $payload['user_analytics']);
    }

    public function test_admin_snapshot_endpoint_access(): void
    {
        // 1. Create or get admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin_test_dev@faiilmov.com'],
            [
                'name' => 'Admin Tester',
                'password' => bcrypt('password123'),
                'is_admin' => true,
            ]
        );
        $admin->is_admin = true;
        $admin->save();

        // 2. Query snapshot endpoint
        $response = $this->actingAs($admin)->getJson('/admin/api/dashboard/snapshot');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'meta' => ['server_time', 'server_date', 'timestamp', 'iso_timestamp'],
            'system_health' => [
                'overall_status',
                'services',
                'down_count',
                'degraded_count',
                'total_monitored',
                'queue' => ['pending_count', 'failed_count', 'recent_failed', 'status'],
                'server' => ['php_version', 'laravel_version', 'db_driver'],
            ],
            'content_performance' => [
                'total_views_today',
                'total_watch_time_sec',
                'total_watch_time_human',
                'top_films',
                'views_trend_7d',
            ],
            'user_analytics' => [
                'dau',
                'signups_today',
                'active_watch_parties',
                'total_users',
                'signup_trend_7d',
            ],
            'activity_feed',
        ]);
    }
}
