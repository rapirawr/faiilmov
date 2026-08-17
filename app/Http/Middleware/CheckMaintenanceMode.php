<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $siteSetting = SiteSetting::current();

        if (!$siteSetting->maintenance_mode) {
            return $next($request);
        }

        // 1. Authenticated Administrators are ALWAYS allowed through
        $user = $request->user();
        if ($user && ($user->is_admin || (method_exists($user, 'isAdmin') && $user->isAdmin()))) {
            return $next($request);
        }

        // 2. Essential routes that must stay accessible to prevent admin lockout
        $exemptPatterns = [
            'admin*',
            'login*',
            'logout*',
            'api/v1/settings*',
            'up',
            'health',
        ];

        foreach ($exemptPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // 3. Handle JSON / API Requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'maintenance',
                'message' => $siteSetting->maintenance_message ?: 'Platform sedang dalam pemeliharaan berkala.',
                'code' => 503,
            ], 503);
        }

        // 4. Render 503 Maintenance Page for Visitors
        return response()->view('errors.503', [
            'maintenanceMessage' => $siteSetting->maintenance_message ?: 'Sistem sedang dalam pemeliharaan berkala untuk meningkatkan performa streaming. Silakan kembali beberapa saat lagi.'
        ], 503);
    }
}
