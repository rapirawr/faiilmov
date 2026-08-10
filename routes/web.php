<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\MovieDetailController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSwitchController;
use App\Http\Controllers\ParentalControlController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieBoxController;
use App\Http\Controllers\SearchController;

// SEO Routes
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\SitemapController::class, 'robots'])->name('robots');

// Public Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/browse', [BrowseController::class, 'index'])->name('browse');
Route::get('/film/{slug}', [MovieDetailController::class, 'show'])->name('film.show');
Route::get('/film/{slug}/watch', [MovieDetailController::class, 'watch'])->name('film.watch');

// Soundtrack MP3 Direct Download Proxy Route
Route::get('/soundtrack/download', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    $title = $request->query('title', 'soundtrack');
    $artist = $request->query('artist', 'artist');

    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        abort(400, 'URL audio tidak valid.');
    }

    $cleanFilename = \Illuminate\Support\Str::slug($title . '-' . $artist) . '.mp3';

    try {
        $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
        if ($response->successful()) {
            return response($response->body(), 200, [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'attachment; filename="' . $cleanFilename . '"',
                'Content-Length' => strlen($response->body()),
            ]);
        }
    } catch (\Exception $e) {
    }

    return redirect($url);
})->name('soundtrack.download');

Route::get('/download', [\App\Http\Controllers\DownloadAppController::class, 'index'])->name('download.app');
Route::get('/mobile-app', function() { return redirect()->route('download.app'); });
Route::post('/download/notify-me', [\App\Http\Controllers\DownloadAppController::class, 'notifyMe'])->name('download.notify-me');
Route::get('/privacy-policy', function() { return view('privacy-policy'); })->name('privacy-policy');
Route::get('/syarat-ketentuan', function() { return view('terms-of-service'); })->name('terms-of-service');
Route::get('/changelog', [\App\Http\Controllers\ChangelogController::class, 'index'])->name('changelog');

// Search Routes - ADD RATE LIMITING
Route::middleware('throttle:search')->group(function () {
    Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
    Route::get('/search/ai-interpret', [SearchController::class, 'aiInterpret'])->name('search.ai-interpret');
});

// Nonton Bareng (Watch Party) Routes - ADD RATE LIMITING
Route::middleware('throttle:watch-party-create')->post('/watch-party/create', [\App\Http\Controllers\WatchPartyController::class, 'create'])->name('watch-party.create');

Route::prefix('watch-party/{roomCode}')->name('watch-party.')->group(function () {
    Route::get('/', [\App\Http\Controllers\WatchPartyController::class, 'show'])->name('show');
    Route::get('/state', [\App\Http\Controllers\WatchPartyController::class, 'syncState'])->name('state');
    
    Route::middleware('throttle:watch-party-actions')->group(function () {
        Route::post('/playback', [\App\Http\Controllers\WatchPartyController::class, 'updatePlayback'])->name('playback');
        Route::post('/message', [\App\Http\Controllers\WatchPartyController::class, 'sendMessage'])->name('message');
        Route::post('/reaction', [\App\Http\Controllers\WatchPartyController::class, 'sendReaction'])->name('reaction');
        Route::post('/kick', [\App\Http\Controllers\WatchPartyController::class, 'kickParticipant'])->name('kick');
        Route::post('/mute', [\App\Http\Controllers\WatchPartyController::class, 'toggleMuteParticipant'])->name('mute');
        Route::post('/transfer-host', [\App\Http\Controllers\WatchPartyController::class, 'transferHost'])->name('transfer-host');
        Route::post('/toggle-lock', [\App\Http\Controllers\WatchPartyController::class, 'toggleLock'])->name('toggle-lock');
        Route::post('/switch-episode', [\App\Http\Controllers\WatchPartyController::class, 'switchEpisode'])->name('switch-episode');
        Route::post('/update-nickname', [\App\Http\Controllers\WatchPartyController::class, 'updateNickname'])->name('update-nickname');
        Route::post('/end', [\App\Http\Controllers\WatchPartyController::class, 'endRoom'])->name('end');
    });
});

// Auth Routes (Guest) - ADD RATE LIMITING
Route::middleware(['guest', 'throttle:auth'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFilmController;
use App\Http\Controllers\Admin\AdminGenreController;
use App\Http\Controllers\Admin\AdminActorController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminActivityLogController;

// Auth Routes (Authenticated Users)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/user/dismiss-welcome-modal', [\App\Http\Controllers\WelcomeModalController::class, 'dismiss'])->name('welcome-modal.dismiss');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::delete('/profile/watchlist', [ProfileController::class, 'clearWatchlist'])->name('profile.clear-watchlist');
    Route::delete('/profile/delete-account', [ProfileController::class, 'deleteAccount'])->name('profile.delete-account');
    
    // Review with rate limiting
    Route::middleware('throttle:review')->group(function () {
        Route::post('/film/{film}/review', [ReviewController::class, 'store'])->name('review.store');
        Route::post('/film/{film}/review/{review}/report', [AdminReviewController::class, 'storeReport'])->name('review.report');
    });
    
    Route::delete('/review/{review}', [ReviewController::class, 'destroy'])->name('review.destroy');
    
    Route::post('/film/{film}/watchlist', [WatchlistController::class, 'toggle'])->name('watchlist.toggle');
    Route::post('/watch-history/progress', [MovieDetailController::class, 'updateProgress'])->name('watch-history.progress');
    Route::delete('/watch-history/clear-all', [ProfileController::class, 'clearHistory'])->name('watch-history.clear-all');
    Route::delete('/watch-history/{watchHistory}', [ProfileController::class, 'destroyHistory'])->name('watch-history.destroy');
    
    // Profiles (Multi-Profile)
    Route::get('/profiles', [ProfileSwitchController::class, 'index'])->name('profiles.index');
    Route::post('/profiles', [ProfileSwitchController::class, 'store'])->name('profiles.store');
    Route::post('/profiles/switch-main', [ProfileSwitchController::class, 'switchMain'])->name('profiles.switch-main');
    Route::post('/profiles/{profile}/switch', [ProfileSwitchController::class, 'switch'])->name('profiles.switch');
    Route::put('/profiles/{profile}/pin', [ProfileSwitchController::class, 'updatePin'])->name('profiles.update-pin');
    Route::delete('/profiles/{profile}', [ProfileSwitchController::class, 'destroy'])->name('profiles.destroy');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    
    // Parental Control
    Route::post('/parental/verify-pin', [ParentalControlController::class, 'verifyPin'])->name('parental.verify-pin');
    Route::post('/parental/set-pin', [ParentalControlController::class, 'setPin'])->name('parental.set-pin');
    Route::post('/parental/set-max-rating', [ParentalControlController::class, 'setMaxRating'])->name('parental.set-max-rating');
    Route::get('/parental/check-content/{film}', [ParentalControlController::class, 'isContentAllowed'])->name('parental.check-content');
});

// Admin Panel Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Film Management
    Route::post('/films/sync-api', [AdminFilmController::class, 'syncApi'])->name('films.sync_api');
    Route::post('/films/bulk-delete', [AdminFilmController::class, 'bulkDelete'])->name('films.bulk_delete');
    Route::post('/films/bulk-restore', [AdminFilmController::class, 'bulkRestore'])->name('films.bulk_restore');
    Route::delete('/films/empty-trash', [AdminFilmController::class, 'emptyTrash'])->name('films.empty_trash');
    Route::delete('/films/{id}/force-delete', [AdminFilmController::class, 'forceDelete'])->name('films.force_delete');
    Route::post('/films/{id}/restore', [AdminFilmController::class, 'restore'])->name('films.restore');
    Route::get('/films-content-rating', [AdminFilmController::class, 'contentRatingEditor'])->name('films.content_rating');
    Route::post('/films-content-rating', [AdminFilmController::class, 'updateContentRatings'])->name('films.update_content_ratings');
    Route::post('/films/auto-rate-all', [AdminFilmController::class, 'autoRateAll'])->name('films.auto_rate_all');
    Route::post('/films/{film}/auto-rate', [AdminFilmController::class, 'autoRate'])->name('films.auto_rate');
    Route::resource('films', AdminFilmController::class);

    // Season & Episode Management
    Route::post('/films/{film}/seasons', [\App\Http\Controllers\Admin\AdminEpisodeController::class, 'storeSeason'])->name('seasons.store');
    Route::delete('/seasons/{season}', [\App\Http\Controllers\Admin\AdminEpisodeController::class, 'destroySeason'])->name('seasons.destroy');
    Route::post('/seasons/{season}/episodes', [\App\Http\Controllers\Admin\AdminEpisodeController::class, 'storeEpisode'])->name('episodes.store');
    Route::put('/episodes/{episode}', [\App\Http\Controllers\Admin\AdminEpisodeController::class, 'updateEpisode'])->name('episodes.update');
    Route::delete('/episodes/{episode}', [\App\Http\Controllers\Admin\AdminEpisodeController::class, 'destroyEpisode'])->name('episodes.destroy');

    // Genre Management
    Route::resource('genres', AdminGenreController::class)->except(['create', 'show', 'edit']);

    // Actor Management
    Route::get('/actors/search-api', [AdminActorController::class, 'searchApi'])->name('actors.search_api');
    Route::post('/actors/sync-api', [AdminActorController::class, 'syncApi'])->name('actors.sync_api');
    Route::resource('actors', AdminActorController::class)->except(['create', 'show', 'edit']);

    // Review Moderation
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/dismiss-reports', [AdminReviewController::class, 'dismissReports'])->name('reviews.dismiss_reports');

    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');

    // Watch Party Management
    Route::get('/watch-parties', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'index'])->name('watch_parties.index');
    Route::get('/watch-parties/{watchParty}', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'show'])->name('watch_parties.show');
    Route::post('/watch-parties/{watchParty}/force-close', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'forceClose'])->name('watch_parties.force_close');
    Route::post('/watch-parties/{watchParty}/message', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'sendMessage'])->name('watch_parties.send_message');

    // Activity Log
    Route::get('/activity-log', [AdminActivityLogController::class, 'index'])->name('activity_logs.index');

    // Changelog & System Updates Management
    Route::post('/changelogs/import', [\App\Http\Controllers\Admin\AdminChangelogController::class, 'import'])->name('changelogs.import');
    Route::post('/changelogs/{changelog}/toggle-publish', [\App\Http\Controllers\Admin\AdminChangelogController::class, 'togglePublish'])->name('changelogs.toggle_publish');
    Route::resource('changelogs', \App\Http\Controllers\Admin\AdminChangelogController::class);

    // Site Settings
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    // Custom PHP Script Runner & Saved Scripts
    Route::get('/scripts', [\App\Http\Controllers\Admin\AdminScriptController::class, 'index'])->name('scripts.index');
    Route::post('/scripts', [\App\Http\Controllers\Admin\AdminScriptController::class, 'store'])->name('scripts.store');
    Route::post('/scripts/execute', [\App\Http\Controllers\Admin\AdminScriptController::class, 'execute'])->name('scripts.execute');
    Route::delete('/scripts/{script}', [\App\Http\Controllers\Admin\AdminScriptController::class, 'destroy'])->name('scripts.destroy');

    // API Tester & Postman Suite
    Route::get('/api-tester', [\App\Http\Controllers\Admin\AdminApiTesterController::class, 'index'])->name('api_tester.index');
    Route::get('/api-tester/export-postman', [\App\Http\Controllers\Admin\AdminApiTesterController::class, 'exportPostman'])->name('api_tester.export_postman');
});

// MovieBox API Proxy Routes (For Stream Player & Modal) - ADD RATE LIMITING
Route::prefix('moviebox')->middleware('throttle:120,1')->group(function () {
    Route::get('/search', [MovieBoxController::class, 'search']);
    Route::get('/detail/{id}', [MovieBoxController::class, 'detail']);
    Route::get('/resources/{id}', [MovieBoxController::class, 'resources']);
    Route::get('/subtitles/{id}', [MovieBoxController::class, 'subtitles']);
    Route::get('/homepage', [MovieBoxController::class, 'homepage']);
    
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/proxy-stream', [MovieBoxController::class, 'proxyStream']);
        Route::get('/proxy-subtitle', [MovieBoxController::class, 'proxySubtitle']);
    });
});
