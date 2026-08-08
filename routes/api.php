<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileApiController;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Auth endpoints
    Route::post('/login', [MobileApiController::class, 'login']);
    Route::post('/register', [MobileApiController::class, 'register']);

    // Public Catalog & Media
    Route::get('/movies', [MobileApiController::class, 'movies']);
    Route::get('/movies/featured', [MobileApiController::class, 'featured']);
    Route::get('/movies/trending', [MobileApiController::class, 'trending']);
    Route::get('/movies/popular-series', [MobileApiController::class, 'popularSeries']);
    Route::get('/movies/{id}', [MobileApiController::class, 'showMovie']);
    Route::get('/movies/{id}/reviews', [MobileApiController::class, 'getMovieReviews']);
    Route::get('/genres', [MobileApiController::class, 'genres']);
    Route::get('/browse', [MobileApiController::class, 'browse']);
    Route::get('/search', [MobileApiController::class, 'search']);

    // User Account & User Data Sync
    Route::get('/user', [MobileApiController::class, 'user']);
    Route::post('/user/profile', [MobileApiController::class, 'updateProfile']);
    Route::post('/user/change-password', [MobileApiController::class, 'changePassword']);
    Route::delete('/user/delete-account', [MobileApiController::class, 'deleteAccount']);
    Route::post('/logout', [MobileApiController::class, 'logout']);

    // Watchlist Sync
    Route::get('/watchlist', [MobileApiController::class, 'getWatchlist']);
    Route::post('/watchlist', [MobileApiController::class, 'addToWatchlist']);
    Route::delete('/watchlist/clear', [MobileApiController::class, 'clearWatchlist']);
    Route::delete('/watchlist/{film_id}', [MobileApiController::class, 'removeFromWatchlist']);

    // Watch History Sync
    Route::get('/watch-history', [MobileApiController::class, 'getWatchHistory']);
    Route::post('/watch-history', [MobileApiController::class, 'updateWatchHistory']);
    Route::delete('/watch-history/clear', [MobileApiController::class, 'clearWatchHistory']);

    // User Reviews Sync
    Route::get('/user/reviews', [MobileApiController::class, 'getUserReviews']);
    Route::delete('/user/reviews/{id}', [MobileApiController::class, 'deleteUserReview']);
    Route::post('/movies/{id}/reviews', [MobileApiController::class, 'postReview']);

    // Watch Party
    Route::get('/watch-party/{roomCode}/state', [\App\Http\Controllers\WatchPartyController::class, 'syncState']);
});
