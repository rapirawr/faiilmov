<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBannedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isBanned()) {
            $reason = Auth::user()->banned_reason ?: 'Akun Anda telah disuspen oleh Admin.';
            $until = Auth::user()->banned_until ? ' sampai ' . Auth::user()->banned_until->format('d M Y H:i') : ' secara permanen';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['error' => "Akun Anda telah dibanned{$until}. Alasan: {$reason}"], 403);
            }

            return redirect()->route('login')->with('error', "Akun Anda telah dibanned{$until}. Alasan: {$reason}");
        }

        return $next($request);
    }
}
