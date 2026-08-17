<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     * Throttled update of last_active_at (at most once every 60s per user).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if last_active_at is null or older than 60 seconds
            $shouldUpdate = is_null($user->last_active_at) 
                || $user->last_active_at->diffInSeconds(now()) >= 60;

            if ($shouldUpdate) {
                // Save quietly so we don't dispatch extra model events or touch updated_at
                $user->forceFill([
                    'last_active_at' => now(),
                ])->saveQuietly();
            }
        }

        return $next($request);
    }
}
