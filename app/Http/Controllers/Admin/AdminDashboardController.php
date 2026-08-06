<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\User;
use App\Models\Review;
use App\Models\WatchParty;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_films' => Film::count(),
            'total_users' => User::count(),
            'total_reviews' => Review::count(),
            'active_watch_parties' => WatchParty::whereDate('created_at', today())->count(),
        ];

        $mostViewedFilms = Film::orderBy('view_count', 'desc')->limit(5)->get();
        $lastSyncStatus = Setting::get('last_api_sync_status', 'Belum pernah melakukan sync API.');
        $lastSyncAt = Setting::get('last_api_sync_at', null);

        return view('admin.dashboard.index', compact('stats', 'mostViewedFilms', 'lastSyncStatus', 'lastSyncAt'));
    }
}
