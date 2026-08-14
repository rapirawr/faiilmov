<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if database maintenance mode is enabled
        try {
            $isMaintenance = Setting::get('maintenance_mode', '0') === '1';
        } catch (\Throwable $e) {
            $isMaintenance = false;
        }

        if ($isMaintenance) {
            // Allow admin users or admin auth paths
            if (Auth::check() && Auth::user()->isAdmin()) {
                return $next($request);
            }

            if ($request->is('admin*', 'login*', 'logout*', 'auth*', 'up*')) {
                return $next($request);
            }

            $message = Setting::get('maintenance_message', 'Sistem sedang dalam pemeliharaan berkala untuk meningkatkan performa streaming.');
            abort(503, $message);
        }

        return $next($request);
    }
}
