<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdministratorMiddleware
{
    /**
     * Handle an incoming request.
     * Ensure only full Administrators (Superadmins) can access restricted system features.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'))->with('error', 'Silakan masuk terlebih dahulu.');
        }

        if (!Auth::user()->isAdministrator()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Akses ditolak. Fitur ini hanya untuk Administrator Utama.'], 403);
            }
            abort(403, 'Akses ditolak. Fitur ini hanya dapat dikelola oleh Administrator Utama (Superadmin).');
        }

        return $next($request);
    }
}
