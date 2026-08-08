<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
        
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
        
        RateLimiter::for('review', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });
        
        RateLimiter::for('watch-party-create', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
        
        RateLimiter::for('watch-party-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Shared Welcome Modal Visibility Logic
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $shouldShow = !\Illuminate\Support\Facades\Auth::check() || !\Illuminate\Support\Facades\Auth::user()?->has_seen_welcome_modal;
            $view->with('shouldShowWelcomeModal', $shouldShow);
        });
    }
}
