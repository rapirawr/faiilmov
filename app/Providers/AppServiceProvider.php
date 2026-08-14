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

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
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

        // Shared Welcome Modal Visibility Logic (Disabled on auth pages)
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $isAuthPage = request()->is('login*', 'register*', 'password*', 'auth*') || request()->routeIs('login', 'register', 'password.*');
            $shouldShow = !$isAuthPage && (!\Illuminate\Support\Facades\Auth::check() || !\Illuminate\Support\Facades\Auth::user()?->has_seen_welcome_modal);
            $view->with('shouldShowWelcomeModal', $shouldShow);
        });

        // Admin Shell View Composer for Badges & Activity
        \Illuminate\Support\Facades\View::composer(['layouts.admin', 'admin.*'], function ($view) {
            if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->isAdmin()) {
                $pendingReportsCount = \App\Models\ReviewReport::where('status', 'pending')->count();
                $activeWatchPartiesCount = \App\Models\WatchParty::where('status', 'active')->count();
                $recentAdminLogs = \App\Models\AdminActivityLog::with('admin')->latest()->take(6)->get();

                $view->with([
                    'adminPendingReportsCount' => $pendingReportsCount,
                    'adminActiveWatchPartiesCount' => $activeWatchPartiesCount,
                    'adminRecentActivityLogs' => $recentAdminLogs,
                ]);
            }
        });
    }
}
