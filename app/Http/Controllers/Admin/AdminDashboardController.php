<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\User;
use App\Models\Review;
use App\Models\Genre;
use App\Models\WatchParty;
use App\Models\Setting;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalFilms = Film::count();
        $totalMovies = Film::where('subject_type', 'movie')->count();
        $totalSeries = Film::where('subject_type', 'series')->count();
        $totalDracin = Film::where('subject_type', 'dracin')->count();

        $stats = [
            'total_films' => $totalFilms,
            'total_movies' => $totalMovies,
            'total_series' => $totalSeries,
            'total_dracin' => $totalDracin,
            'total_users' => User::count(),
            'new_users_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'total_reviews' => Review::count(),
            'total_genres' => Genre::count(),
            'active_watch_parties' => WatchParty::where('status', 'active')->count(),
            'total_watch_parties' => WatchParty::count(),
        ];

        // Content Rating distribution
        $contentRatings = Film::select('content_rating', DB::raw('count(*) as count'))
            ->groupBy('content_rating')
            ->pluck('count', 'content_rating')
            ->toArray();

        $mostViewedFilms = Film::orderBy('view_count', 'desc')->limit(5)->get();
        $recentActivityLogs = AdminActivityLog::with('admin')->orderBy('created_at', 'desc')->limit(6)->get();

        $lastSyncAtRaw = Setting::get('last_api_sync_at');
        if (!$lastSyncAtRaw) {
            $latestSyncLog = AdminActivityLog::where('action', 'like', '%sync%')->latest()->first();
            if ($latestSyncLog) {
                $lastSyncAtRaw = $latestSyncLog->created_at->toDateTimeString();
                Setting::set('last_api_sync_at', $lastSyncAtRaw);
                Setting::set('last_api_sync_status', $latestSyncLog->description ?: 'Sinkronisasi API Berhasil.');
            } else {
                $latestFilm = Film::whereNotNull('moviebox_subject_id')->latest('updated_at')->first();
                if ($latestFilm) {
                    $lastSyncAtRaw = $latestFilm->updated_at->toDateTimeString();
                    Setting::set('last_api_sync_at', $lastSyncAtRaw);
                    Setting::set('last_api_sync_status', 'Sinkronisasi API film aktif.');
                }
            }
        }

        $lastSyncStatus = Setting::get('last_api_sync_status', 'Sinkronisasi API film aktif.');
        $lastSyncAt = $lastSyncAtRaw ? \Carbon\Carbon::parse($lastSyncAtRaw)->diffForHumans() . ' (' . \Carbon\Carbon::parse($lastSyncAtRaw)->format('d M Y H:i') . ')' : 'Baru Saja';
        $lastSyncDetailsRaw = Setting::get('last_api_sync_details');
        $lastSyncDetails = $lastSyncDetailsRaw ? json_decode($lastSyncDetailsRaw, true) : null;

        $pendingReportsCount = Review::has('reports')->count();
        $recentUsers = User::latest()->limit(5)->get();
        $activeWatchPartiesList = WatchParty::with('film')
            ->where('status', 'active')
            ->latest()
            ->limit(4)
            ->get();

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => config('database.default'),
            'cache_driver' => config('cache.default'),
        ];

        $initialSnapshot = app(\App\Services\DashboardSnapshotService::class)->getSnapshot();

        return view('admin.dashboard.index', compact(
            'stats',
            'contentRatings',
            'mostViewedFilms',
            'recentActivityLogs',
            'lastSyncStatus',
            'lastSyncAt',
            'lastSyncDetails',
            'pendingReportsCount',
            'recentUsers',
            'activeWatchPartiesList',
            'systemInfo',
            'initialSnapshot'
        ));
    }

    /**
     * Real-time Developer & Admin Dashboard Snapshot (System Health, Content Performance, User Analytics)
     */
    public function snapshot(\App\Services\DashboardSnapshotService $snapshotService): \Illuminate\Http\JsonResponse
    {
        return response()->json($snapshotService->getSnapshot());
    }

    /**
     * Actively ping API services & hosts on demand
     */
    public function pingApi(Request $request, \App\Services\SystemHealthService $healthService): \Illuminate\Http\JsonResponse
    {
        $service = $request->input('service');
        $host = $request->input('host');

        if (!empty($service)) {
            $result = $healthService->pingSingleService($service, $host);
        } else {
            $result = $healthService->pingAllServices();
        }

        $snapshot = app(\App\Services\DashboardSnapshotService::class)->getSnapshot();

        return response()->json([
            'status'        => 'success',
            'result'        => $result,
            'system_health' => $snapshot['system_health'] ?? [],
        ]);
    }
}

