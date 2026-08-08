<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ParentalControlController extends Controller
{
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|max:4',
        ]);

        $user = Auth::user();
        if (!$user->parental_pin) {
            return response()->json(['status' => 'ok', 'message' => 'No PIN set']);
        }

        if (Hash::check($request->pin, $user->parental_pin)) {
            session(['parental_verified' => true]);
            return response()->json(['status' => 'ok', 'message' => 'PIN verified']);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid PIN'], 422);
    }

    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|max:4|min:4',
        ]);

        Auth::user()->update([
            'parental_pin' => Hash::make($request->pin),
        ]);

        return response()->json(['status' => 'ok', 'message' => 'PIN updated']);
    }

    public function setMaxRating(Request $request)
    {
        $request->validate([
            'max_rating' => 'required|string|in:G,PG,PG-13,R,NC-17',
        ]);

        Auth::user()->update([
            'max_allowed_rating' => $request->max_rating,
        ]);

        return response()->json(['status' => 'ok', 'message' => 'Max rating updated']);
    }

    public function isContentAllowed(Film $film)
    {
        $user = Auth::user();
        if (!$user->parental_pin || !$user->max_allowed_rating) {
            return response()->json(['allowed' => true]);
        }

        if (!session('parental_verified')) {
            return response()->json(['allowed' => false, 'message' => 'PIN required']);
        }

        $filmRatingOrder = ['G' => 1, 'PG' => 2, 'PG-13' => 3, 'R' => 4, 'NC-17' => 5];
        $filmRating = $filmRatingOrder[$film->content_rating ?? 'G'] ?? 1;
        $maxRating = $filmRatingOrder[$user->max_allowed_rating] ?? 5;

        return response()->json(['allowed' => $filmRating <= $maxRating]);
    }
}
