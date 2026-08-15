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

// Storage Symlink Utility Route for cPanel Deployment
Route::get('/create-storage-link', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        
        // Manual fallback symlink if Artisan command is restricted on cPanel
        $target = storage_path('app/public');
        $shortcut = public_path('storage');
        if (!file_exists($shortcut)) {
            @symlink($target, $shortcut);
        }

        return response()->json([
            'success' => true,
            'message' => 'Simbolik link folder storage berhasil dibuat!',
            'target' => $target,
            'shortcut' => $shortcut
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
});

// SEO Routes
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\SitemapController::class, 'robots'])->name('robots');

// Public Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/browse', [BrowseController::class, 'index'])->name('browse');
Route::get('/genre/{slug}', [BrowseController::class, 'genre'])->name('genre.show');
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
Route::get('/version.json', [\App\Http\Controllers\DownloadAppController::class, 'getVersionJson']);
Route::get('/mobile-app', function() { return redirect()->route('download.app'); });
Route::post('/download/notify-me', [\App\Http\Controllers\DownloadAppController::class, 'notifyMe'])->name('download.notify-me');
Route::get('/privacy-policy', function() { return view('privacy-policy'); })->name('privacy-policy');
Route::get('/syarat-ketentuan', function() { return view('terms-of-service'); })->name('terms-of-service');
Route::get('/changelog', [\App\Http\Controllers\ChangelogController::class, 'index'])->name('changelog');
Route::get('/notifications/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('notifications.recent');

// Search Routes - ADD RATE LIMITING
Route::middleware('throttle:search')->group(function () {
    Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
    Route::get('/search/ai-interpret', [SearchController::class, 'aiInterpret'])->name('search.ai-interpret');
});

// Nonton Bareng (Watch Party) Routes - ADD RATE LIMITING
Route::middleware(['throttle:watch-party-create', 'auth'])->post('/watch-party/create', [\App\Http\Controllers\WatchPartyController::class, 'create'])->name('watch-party.create');

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

// Password Reset Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Social Auth Routes (Google, Facebook) — accessible from login & register pages
Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}', [\App\Http\Controllers\SocialAuthController::class, 'redirect'])
        ->name('social.redirect');
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialAuthController::class, 'callback'])
        ->name('social.callback');
});

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFilmController;
use App\Http\Controllers\Admin\AdminGenreController;
use App\Http\Controllers\Admin\AdminActorController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminAppReleaseController;

// Auth Routes (Authenticated Users)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/user/dismiss-welcome-modal', [\App\Http\Controllers\WelcomeModalController::class, 'dismiss'])->name('welcome-modal.dismiss');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::delete('/profile/watchlist', [ProfileController::class, 'clearWatchlist'])->name('profile.clear-watchlist');
    Route::delete('/profile/delete-account', [ProfileController::class, 'deleteAccount'])->name('profile.delete-account');

    // Review
    Route::middleware(['throttle:review'])->group(function () {
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

    // Notifications (Authenticated Actions)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
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
    Route::get('/quick-search', [AdminDashboardController::class, 'quickSearch'])->name('quick_search');

    // Film Management
    Route::post('/films/sync-api', [AdminFilmController::class, 'syncApi'])->name('films.sync_api');
    Route::post('/films/sync-dracin-api', [AdminFilmController::class, 'syncDracinApi'])->name('films.sync_dracin_api');
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
    Route::post('/actors/merge', [AdminActorController::class, 'merge'])->name('actors.merge');
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
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
    Route::delete('/users/{id}/force-delete', [AdminUserController::class, 'forceDelete'])->name('users.force_delete');

    // Push Notifications Broadcast Center
    Route::get('/notifications', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/search-users', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'searchUsers'])->name('notifications.search_users');
    Route::post('/notifications/send', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'send'])->name('notifications.send');
    Route::post('/notifications/generate-ai', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'generateAi'])->name('notifications.generate_ai');
    Route::delete('/notifications/destroy-broadcast', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'destroyBroadcast'])->name('notifications.destroy_broadcast');

    // Watch Party Management
    Route::get('/watch-parties', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'index'])->name('watch_parties.index');
    Route::get('/watch-parties/{watchParty}', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'show'])->name('watch_parties.show');
    Route::post('/watch-parties/{watchParty}/force-close', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'forceClose'])->name('watch_parties.force_close');
    Route::post('/watch-parties/{watchParty}/message', [\App\Http\Controllers\Admin\AdminWatchPartyController::class, 'sendMessage'])->name('watch_parties.send_message');

    // Activity Log
    Route::get('/activity-log', [AdminActivityLogController::class, 'index'])->name('activity_logs.index');
    Route::delete('/activity-log/clear-old', [AdminActivityLogController::class, 'clearOldLogs'])->name('activity_logs.clear_old');

    // Changelog & System Updates Management
    Route::post('/changelogs/import', [\App\Http\Controllers\Admin\AdminChangelogController::class, 'import'])->name('changelogs.import');
    Route::post('/changelogs/{changelog}/toggle-publish', [\App\Http\Controllers\Admin\AdminChangelogController::class, 'togglePublish'])->name('changelogs.toggle_publish');
    Route::resource('changelogs', \App\Http\Controllers\Admin\AdminChangelogController::class);

    // Navigation Menu Management (Drag & Drop Reorder)
    Route::get('/navigation', [\App\Http\Controllers\Admin\AdminNavigationController::class, 'index'])->name('navigation.index');
    Route::post('/navigation', [\App\Http\Controllers\Admin\AdminNavigationController::class, 'update'])->name('navigation.update');
    Route::post('/navigation/reset', [\App\Http\Controllers\Admin\AdminNavigationController::class, 'reset'])->name('navigation.reset');

    // Site Settings
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    // Custom PHP Script Runner & Saved Scripts
    Route::get('/scripts', [\App\Http\Controllers\Admin\AdminScriptController::class, 'index'])->name('scripts.index');
    Route::post('/scripts', [\App\Http\Controllers\Admin\AdminScriptController::class, 'store'])->name('scripts.store');
    Route::post('/scripts/execute', [\App\Http\Controllers\Admin\AdminScriptController::class, 'execute'])->name('scripts.execute');
    Route::post('/scripts/generate', [\App\Http\Controllers\Admin\AdminScriptController::class, 'generateScript'])->name('scripts.generate');
    Route::delete('/scripts/{script}', [\App\Http\Controllers\Admin\AdminScriptController::class, 'destroy'])->name('scripts.destroy');

    // API Tester & Postman Suite
    Route::get('/api-tester', [\App\Http\Controllers\Admin\AdminApiTesterController::class, 'index'])->name('api_tester.index');
    Route::get('/api-tester/export-postman', [\App\Http\Controllers\Admin\AdminApiTesterController::class, 'exportPostman'])->name('api_tester.export_postman');

    // APK Mobile Release Management
    Route::get('/app-release', [AdminAppReleaseController::class, 'index'])->name('app_release.index');
    Route::post('/app-release', [AdminAppReleaseController::class, 'store'])->name('app_release.store');
    Route::delete('/app-release/file/{filename}', [AdminAppReleaseController::class, 'destroyFile'])->name('app_release.destroy_file');
});

// MovieBox API Proxy Routes (For Stream Player & Modal) - ADD RATE LIMITING    
Route::prefix('moviebox')->middleware('throttle:120,1')->group(function () {
    Route::get('/search', [MovieBoxController::class, 'search']);
    Route::get('/detail/{id}', [MovieBoxController::class, 'detail']);
    Route::get('/resources/{id}', [MovieBoxController::class, 'resources']);
    Route::get('/subtitles/{id}', [MovieBoxController::class, 'subtitles']);
    Route::get('/audios/{id}', [MovieBoxController::class, 'audios']);
    Route::get('/homepage', [MovieBoxController::class, 'homepage']);
    
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/proxy-stream', [MovieBoxController::class, 'proxyStream']);
        Route::get('/proxy-subtitle', [MovieBoxController::class, 'proxySubtitle']);
    });
});

// Anichin API Proxy & Stream Routes (For Dracin Player & Feeds)
Route::prefix('anichin')->middleware('throttle:240,1')->group(function () {
    Route::get('/hls', [\App\Http\Controllers\AnichinController::class, 'hlsStream'])->name('anichin.hls');
    Route::get('/ts-proxy', [\App\Http\Controllers\AnichinController::class, 'tsProxy'])->name('anichin.ts_proxy');
    Route::get('/detail/{source}/{id}', [\App\Http\Controllers\AnichinController::class, 'detail']);
    Route::get('/trending/{source?}', [\App\Http\Controllers\AnichinController::class, 'trending']);
    Route::get('/foryou/{source?}', [\App\Http\Controllers\AnichinController::class, 'forYou']);
    Route::get('/search/{source?}', [\App\Http\Controllers\AnichinController::class, 'search']);
    Route::get('/hotrank/{source?}', [\App\Http\Controllers\AnichinController::class, 'hotRank']);
    Route::get('/recommended/{source?}', [\App\Http\Controllers\AnichinController::class, 'recommended']);
    Route::get('/latest/{source?}', [\App\Http\Controllers\AnichinController::class, 'latest']);
});

// Dedicated Dracin Vertical Feed Routes
Route::prefix('dracin')->group(function () {
    Route::get('/', [\App\Http\Controllers\DracinController::class, 'index'])->name('dracin.index');
    Route::get('/api/feed', [\App\Http\Controllers\DracinController::class, 'feedApi'])->name('dracin.api.feed');
    Route::get('/api/search', [\App\Http\Controllers\DracinController::class, 'searchApi'])->name('dracin.api.search');
    Route::get('/api/detail/{source}/{id}', [\App\Http\Controllers\DracinController::class, 'detailApi'])->name('dracin.api.detail');
    Route::post('/api/watch-progress', [\App\Http\Controllers\DracinController::class, 'watchProgressApi'])->name('dracin.api.watch-progress');
    Route::get('/{source}/{id}', [\App\Http\Controllers\DracinController::class, 'index'])->name('dracin.show');
});

