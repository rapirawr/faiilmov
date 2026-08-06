<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\MovieDetailController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\ProfileController;
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
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');

// Nonton Bareng (Watch Party) Routes
Route::post('/watch-party/create', [\App\Http\Controllers\WatchPartyController::class, 'create'])->name('watch-party.create');
Route::get('/watch-party/{roomCode}', [\App\Http\Controllers\WatchPartyController::class, 'show'])->name('watch-party.show');
Route::post('/watch-party/{roomCode}/playback', [\App\Http\Controllers\WatchPartyController::class, 'updatePlayback'])->name('watch-party.playback');
Route::post('/watch-party/{roomCode}/message', [\App\Http\Controllers\WatchPartyController::class, 'sendMessage'])->name('watch-party.message');
Route::post('/watch-party/{roomCode}/reaction', [\App\Http\Controllers\WatchPartyController::class, 'sendReaction'])->name('watch-party.reaction');
Route::get('/watch-party/{roomCode}/state', [\App\Http\Controllers\WatchPartyController::class, 'syncState'])->name('watch-party.state');
Route::post('/watch-party/{roomCode}/kick', [\App\Http\Controllers\WatchPartyController::class, 'kickParticipant'])->name('watch-party.kick');
Route::post('/watch-party/{roomCode}/mute', [\App\Http\Controllers\WatchPartyController::class, 'toggleMuteParticipant'])->name('watch-party.mute');
Route::post('/watch-party/{roomCode}/transfer-host', [\App\Http\Controllers\WatchPartyController::class, 'transferHost'])->name('watch-party.transfer-host');
Route::post('/watch-party/{roomCode}/toggle-lock', [\App\Http\Controllers\WatchPartyController::class, 'toggleLock'])->name('watch-party.toggle-lock');
Route::post('/watch-party/{roomCode}/switch-episode', [\App\Http\Controllers\WatchPartyController::class, 'switchEpisode'])->name('watch-party.switch-episode');
Route::post('/watch-party/{roomCode}/update-nickname', [\App\Http\Controllers\WatchPartyController::class, 'updateNickname'])->name('watch-party.update-nickname');
Route::post('/watch-party/{roomCode}/end', [\App\Http\Controllers\WatchPartyController::class, 'endRoom'])->name('watch-party.end');

// Auth Routes (Guest)
Route::middleware('guest')->group(function () {
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
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

    Route::post('/film/{film}/review', [ReviewController::class, 'store'])->name('review.store');
    Route::post('/film/{film}/review/{review}/report', [AdminReviewController::class, 'storeReport'])->name('review.report');
    Route::delete('/review/{review}', [ReviewController::class, 'destroy'])->name('review.destroy');

    Route::post('/film/{film}/watchlist', [WatchlistController::class, 'toggle'])->name('watchlist.toggle');
    Route::post('/watch-history/progress', [MovieDetailController::class, 'updateProgress'])->name('watch-history.progress');
    Route::delete('/watch-history/clear-all', [ProfileController::class, 'clearHistory'])->name('watch-history.clear-all');
    Route::delete('/watch-history/{watchHistory}', [ProfileController::class, 'destroyHistory'])->name('watch-history.destroy');
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
    Route::resource('films', AdminFilmController::class);

    // Genre Management
    Route::resource('genres', AdminGenreController::class)->except(['create', 'show', 'edit']);

    // Actor Management
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

    // Activity Log
    Route::get('/activity-log', [AdminActivityLogController::class, 'index'])->name('activity_logs.index');

    // Site Settings
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

// MovieBox API Proxy Routes (For Stream Player & Modal)
Route::prefix('moviebox')->group(function () {
    Route::get('/search', [MovieBoxController::class, 'search']);
    Route::get('/detail/{id}', [MovieBoxController::class, 'detail']);
    Route::get('/resources/{id}', [MovieBoxController::class, 'resources']);
    Route::get('/subtitles/{id}', [MovieBoxController::class, 'subtitles']);
    Route::get('/homepage', [MovieBoxController::class, 'homepage']);
    Route::get('/proxy-stream', [MovieBoxController::class, 'proxyStream']);
    Route::get('/proxy-subtitle', [MovieBoxController::class, 'proxySubtitle']);
});
