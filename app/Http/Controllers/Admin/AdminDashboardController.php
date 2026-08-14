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
            'systemInfo'
        ));
    }

    /**
     * Global Quick Search for Admin Shell (Ctrl+K Modal)
     */
    public function quickSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string)$request->query('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['films' => [], 'users' => [], 'menus' => []]);
        }

        // 1. Search Films
        $films = Film::select('id', 'title', 'slug', 'subject_type', 'release_year', 'poster_url')
            ->where(function ($sub) use ($q) {
                $sub->where('title', 'LIKE', "%{$q}%")
                    ->orWhere('synopsis', 'LIKE', "%{$q}%");
            })
            ->take(6)
            ->get()
            ->map(function ($film) {
                return [
                    'id' => $film->id,
                    'title' => $film->title,
                    'type' => strtoupper($film->subject_type),
                    'year' => $film->release_year,
                    'poster' => $film->poster_url ?: asset('images/placeholder.jpg'),
                    'url' => route('admin.films.edit', $film->id),
                ];
            });

        // 2. Search Users
        $users = User::select('id', 'name', 'email', 'avatar_url', 'is_admin', 'is_banned')
            ->where(function ($sub) use ($q) {
                $sub->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('email', 'LIKE', "%{$q}%");
            })
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => (bool)$user->is_admin,
                    'is_banned' => (bool)$user->is_banned,
                    'avatar' => $user->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
                    'url' => route('admin.users.show', $user->id),
                ];
            });

        // 3. Navigation Pages
        $allMenus = [
            ['title' => 'Dashboard Overview', 'category' => 'Utama', 'icon' => 'home', 'url' => route('admin.dashboard')],
            ['title' => 'Semua Film & Dracin', 'category' => 'Konten', 'icon' => 'film', 'url' => route('admin.films.index')],
            ['title' => 'Tambah Film Baru', 'category' => 'Konten', 'icon' => 'plus-circle', 'url' => route('admin.films.create')],
            ['title' => 'Editor Rating Massal', 'category' => 'Konten', 'icon' => 'shield-alert', 'url' => route('admin.films.content_rating')],
            ['title' => 'Manajemen Genre Film', 'category' => 'Konten', 'icon' => 'tags', 'url' => route('admin.genres.index')],
            ['title' => 'Manajemen Aktor & Cast', 'category' => 'Konten', 'icon' => 'users', 'url' => route('admin.actors.index')],
            ['title' => 'Moderasi Ulasan Pengguna', 'category' => 'Moderasi', 'icon' => 'message-square', 'url' => route('admin.reviews.index')],
            ['title' => 'Manajemen Pengguna', 'category' => 'Pengguna', 'icon' => 'user-check', 'url' => route('admin.users.index')],
            ['title' => 'Watch Parties (Nobar)', 'category' => 'Moderasi', 'icon' => 'tv', 'url' => route('admin.watch_parties.index')],
            ['title' => 'API Tester & Docs', 'category' => 'Sistem', 'icon' => 'terminal', 'url' => route('admin.api_tester.index')],
            ['title' => 'PHP Script Runner', 'category' => 'Sistem', 'icon' => 'code', 'url' => route('admin.scripts.index')],
            ['title' => 'Changelog & Updates', 'category' => 'Sistem', 'icon' => 'file-clock', 'url' => route('admin.changelogs.index')],
            ['title' => 'Activity Audit Logs', 'category' => 'Sistem', 'icon' => 'history', 'url' => route('admin.activity_logs.index')],
            ['title' => 'Rilis APK Mobile', 'category' => 'Sistem', 'icon' => 'smartphone', 'url' => route('admin.app_release.index')],
            ['title' => 'Pengaturan Umum & API Keys', 'category' => 'Pengaturan', 'icon' => 'sliders', 'url' => route('admin.settings.index')],
        ];

        $matchedMenus = array_values(array_filter($allMenus, function ($m) use ($q) {
            return stripos($m['title'], $q) !== false || stripos($m['category'], $q) !== false;
        }));

        return response()->json([
            'films' => $films,
            'users' => $users,
            'menus' => $matchedMenus,
        ]);
    }
}
