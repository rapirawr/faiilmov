<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Carbon\Carbon;

class DashboardSnapshotService
{
    public function __construct(
        protected SystemHealthService $healthService,
        protected ContentAnalyticsService $contentService,
        protected UserAnalyticsService $userService
    ) {}

    /**
     * Get complete aggregated dashboard snapshot
     */
    public function getSnapshot(): array
    {
        $now = Carbon::now();

        // 1. System Health & External API Status
        $healthStatuses = $this->healthService->getAllServiceStatuses();
        $queueSnapshot = $this->healthService->getQueueSnapshot();
        $serverSnapshot = $this->healthService->getServerSnapshot();

        // 2. Content Performance
        $topFilms = $this->contentService->getTopFilms(10);
        $totalViewsToday = $this->contentService->getTotalViewsToday();
        $totalWatchTimeToday = $this->contentService->getTotalWatchTimeToday();
        $viewsTrend7d = $this->contentService->getDailyViewsTrend(7);
        $topGenres = $this->contentService->getTopGenresByViews(6);

        // 3. User Analytics
        $dau = $this->userService->getDAU();
        $signupsToday = $this->userService->getSignupsToday();
        $activeWatchParties = $this->userService->getActiveWatchParties();
        $totalUsers = $this->userService->getTotalUsers();
        $signupTrend7d = $this->userService->getSignupTrend(7);

        // 4. Live Activity Feed (Latest 20)
        $activityLogs = AdminActivityLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($log) {
                $admin = $log->admin;
                return [
                    'id'               => $log->id,
                    'admin_id'         => $log->admin_id,
                    'admin_name'       => $admin?->name ?? 'System Bot',
                    'admin_email'      => $admin?->email ?? 'bot@faiilmov.com',
                    'admin_avatar'     => $admin?->avatar_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=Admin',
                    'action'           => $log->action,
                    'description'      => $log->description,
                    'target_type'      => $log->target_type,
                    'target_id'        => $log->target_id,
                    'created_at'       => $log->created_at?->toISOString(),
                    'created_at_human' => $log->created_at ? $log->created_at->diffForHumans() : 'Baru saja',
                ];
            });

        // 5. Catalog Composition & Ratings
        $totalFilms = \App\Models\Film::count();
        $totalMovies = \App\Models\Film::where('subject_type', 'movie')->count();
        $totalSeries = \App\Models\Film::where('subject_type', 'series')->count();
        $totalDracin = \App\Models\Film::where('subject_type', 'dracin')->count();
        $contentRatings = \App\Models\Film::select('content_rating', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('content_rating')
            ->pluck('count', 'content_rating')
            ->toArray();

        $pendingReportsCount = \App\Models\ReviewReport::where('status', 'pending')->count();
        $pendingRequestsCount = \App\Models\FilmRequest::where('status', 'pending')->count();

        $lastSyncAtRaw = \App\Models\Setting::get('last_api_sync_at');
        $lastSyncStatus = \App\Models\Setting::get('last_api_sync_status', 'Sinkronisasi API film aktif.');
        $lastSyncHuman = $lastSyncAtRaw ? \Carbon\Carbon::parse($lastSyncAtRaw)->diffForHumans() : 'Baru saja';

        return [
            'status' => 'success',
            'meta'   => [
                'server_time'       => $now->format('H:i:s T'),
                'server_date'       => $now->format('d M Y'),
                'timestamp'         => $now->timestamp,
                'iso_timestamp'     => $now->toISOString(),
            ],
            'system_health' => [
                'overall_status'    => $healthStatuses['overall_status'],
                'services'          => $healthStatuses['services'],
                'down_count'        => $healthStatuses['down_count'],
                'degraded_count'    => $healthStatuses['degraded_count'],
                'total_monitored'   => $healthStatuses['total_monitored'],
                'queue'             => $queueSnapshot,
                'server'            => $serverSnapshot,
                'sync_gateway'      => [
                    'last_sync_at'      => $lastSyncAtRaw,
                    'last_sync_human'   => $lastSyncHuman,
                    'last_sync_status'  => $lastSyncStatus,
                ],
            ],
            'content_performance' => [
                'total_views_today'      => $totalViewsToday,
                'total_watch_time_sec'   => $totalWatchTimeToday,
                'total_watch_time_human' => $this->formatHoursMinutes($totalWatchTimeToday),
                'top_films'              => $topFilms,
                'views_trend_7d'         => $viewsTrend7d,
                'top_genres'             => $topGenres,
                'catalog'                => [
                    'total_films'     => $totalFilms,
                    'total_movies'    => $totalMovies,
                    'total_series'    => $totalSeries,
                    'total_dracin'    => $totalDracin,
                    'content_ratings' => $contentRatings,
                ],
            ],
            'user_analytics' => [
                'dau'                  => $dau,
                'signups_today'        => $signupsToday,
                'active_watch_parties' => $activeWatchParties,
                'total_users'          => $totalUsers,
                'signup_trend_7d'      => $signupTrend7d,
                'moderation'           => [
                    'pending_reports'  => $pendingReportsCount,
                    'pending_requests' => $pendingRequestsCount,
                ],
            ],
            'activity_feed' => $activityLogs,
        ];
    }

    private function formatHoursMinutes(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dj %dm', $hours, $minutes);
        }
        return sprintf('%dm', $minutes);
    }
}
