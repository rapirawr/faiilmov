<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileApiController;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // ---------------------------------------------------------
    // 1. AUTH & USER MANAGEMENT
    // ---------------------------------------------------------
    Route::post('/login', [MobileApiController::class, 'login']);
    Route::post('/register', [MobileApiController::class, 'register']);
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::get('/user', [MobileApiController::class, 'user']);
    Route::post('/user/profile', [MobileApiController::class, 'updateProfile']);
    Route::post('/user/change-password', [MobileApiController::class, 'changePassword']);
    Route::delete('/user/delete-account', [MobileApiController::class, 'deleteAccount']);
    Route::get('/users', [MobileApiController::class, 'getUsers']);

    // ---------------------------------------------------------
    // 2. PROFILES (MULTI-PROFILE USER MANAGEMENT)
    // ---------------------------------------------------------
    Route::get('/profiles', [MobileApiController::class, 'getProfiles']);
    Route::post('/profiles', [MobileApiController::class, 'createProfile']);
    Route::get('/profiles/{id}', [MobileApiController::class, 'getProfileDetail']);
    Route::put('/profiles/{id}', [MobileApiController::class, 'updateProfileDetail']);
    Route::delete('/profiles/{id}', [MobileApiController::class, 'deleteProfileDetail']);

    // ---------------------------------------------------------
    // 3. CATALOG & MOVIES
    // ---------------------------------------------------------
    Route::get('/movies', [MobileApiController::class, 'movies']);
    Route::get('/movies/featured', [MobileApiController::class, 'featured']);
    Route::get('/movies/trending', [MobileApiController::class, 'trending']);
    Route::get('/movies/popular-series', [MobileApiController::class, 'popularSeries']);
    Route::get('/movies/{id}', [MobileApiController::class, 'showMovie']);
    Route::get('/browse', [MobileApiController::class, 'browse']);

    // ---------------------------------------------------------
    // 4. SEASONS & EPISODES
    // ---------------------------------------------------------
    Route::get('/movies/{id}/seasons', [MobileApiController::class, 'getMovieSeasons']);
    Route::get('/seasons/{id}', [MobileApiController::class, 'getSeasonDetail']);
    Route::get('/seasons/{id}/episodes', [MobileApiController::class, 'getSeasonEpisodes']);
    Route::get('/episodes/{id}', [MobileApiController::class, 'getEpisodeDetail']);

    // ---------------------------------------------------------
    // 5. GENRES
    // ---------------------------------------------------------
    Route::get('/genres', [MobileApiController::class, 'genres']);
    Route::get('/genres/{id}', [MobileApiController::class, 'getGenreDetail']);
    Route::get('/genres/{id}/movies', [MobileApiController::class, 'getGenreMovies']);

    // ---------------------------------------------------------
    // 6. ACTORS / CAST
    // ---------------------------------------------------------
    Route::get('/actors', [MobileApiController::class, 'getActors']);
    Route::get('/actors/{id}', [MobileApiController::class, 'getActorDetail']);
    Route::get('/actors/{id}/movies', [MobileApiController::class, 'getActorMovies']);

    // ---------------------------------------------------------
    // 7. SEARCH & SEARCH ANALYTICS
    // ---------------------------------------------------------
    Route::get('/search', [MobileApiController::class, 'search']);
    Route::get('/search/popular', [MobileApiController::class, 'getPopularSearches']);

    // ---------------------------------------------------------
    // 8. WATCHLIST SYNC
    // ---------------------------------------------------------
    Route::get('/watchlist', [MobileApiController::class, 'getWatchlist']);
    Route::post('/watchlist', [MobileApiController::class, 'addToWatchlist']);
    Route::delete('/watchlist/clear', [MobileApiController::class, 'clearWatchlist']);
    Route::delete('/watchlist/{film_id}', [MobileApiController::class, 'removeFromWatchlist']);

    // ---------------------------------------------------------
    // 9. WATCH HISTORY SYNC
    // ---------------------------------------------------------
    Route::get('/watch-history', [MobileApiController::class, 'getWatchHistory']);
    Route::post('/watch-history', [MobileApiController::class, 'updateWatchHistory']);
    Route::delete('/watch-history/clear', [MobileApiController::class, 'clearWatchHistory']);

    // ---------------------------------------------------------
    // 10. REVIEWS & REVIEW REPORTS
    // ---------------------------------------------------------
    Route::get('/movies/{id}/reviews', [MobileApiController::class, 'getMovieReviews']);
    Route::post('/movies/{id}/reviews', [MobileApiController::class, 'postReview']);
    Route::get('/user/reviews', [MobileApiController::class, 'getUserReviews']);
    Route::delete('/user/reviews/{id}', [MobileApiController::class, 'deleteUserReview']);
    Route::post('/reviews/{id}/report', [MobileApiController::class, 'reportReview']);
    Route::get('/admin/reviews/reports', [MobileApiController::class, 'getReviewReports']);

    // ---------------------------------------------------------
    // 11. NOTIFICATIONS
    // ---------------------------------------------------------
    Route::get('/notifications', [MobileApiController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [MobileApiController::class, 'markNotificationAsRead']);
    Route::post('/notifications/read-all', [MobileApiController::class, 'markAllNotificationsAsRead']);
    Route::delete('/notifications/{id}', [MobileApiController::class, 'deleteNotification']);

    // ---------------------------------------------------------
    // 12. APP LAUNCH NOTIFICATIONS, SETTINGS & CHANGELOGS
    // ---------------------------------------------------------
    Route::get('/app-launch-notifications', [MobileApiController::class, 'getAppLaunchNotifications']);
    Route::post('/app-launch-notifications/subscribe', [MobileApiController::class, 'subscribeAppLaunch']);
    Route::get('/settings', [MobileApiController::class, 'getSettings']);
    Route::get('/changelogs', [MobileApiController::class, 'getChangelogs']);
    Route::get('/changelogs/latest', [MobileApiController::class, 'getLatestChangelog']);

    // ---------------------------------------------------------
    // 13. ADMIN ACTIVITY LOGS
    // ---------------------------------------------------------
    Route::get('/admin/activity-logs', [MobileApiController::class, 'getAdminActivityLogs']);

    // ---------------------------------------------------------
    // 14. WATCH PARTY API
    // ---------------------------------------------------------
    Route::post('/watch-party/create', [MobileApiController::class, 'createWatchPartyApi']);
    Route::get('/watch-party/{roomCode}', [MobileApiController::class, 'getWatchPartyApi']);
    Route::get('/watch-party/{roomCode}/state', [\App\Http\Controllers\WatchPartyController::class, 'syncState']);
    Route::post('/watch-party/{roomCode}/join', [MobileApiController::class, 'joinWatchPartyApi']);
    Route::get('/watch-party/{roomCode}/messages', [MobileApiController::class, 'getWatchPartyMessagesApi']);
    Route::post('/watch-party/{roomCode}/message', [MobileApiController::class, 'sendWatchPartyMessageApi']);
    Route::post('/watch-party/{roomCode}/reaction', [MobileApiController::class, 'sendWatchPartyReactionApi']);
    Route::post('/watch-party/{roomCode}/leave', [MobileApiController::class, 'leaveWatchPartyApi']);
});

