<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, Film $film)
    {
        if (session()->has('active_profile_id')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fitur ulasan hanya dapat digunakan oleh Akun Utama.'
                ], 403);
            }
            return back()->with('error', 'Fitur ulasan hanya dapat digunakan oleh Akun Utama.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'film_id' => $film->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]
        );

        $film->updateAverageRating();

        // Award Cinephile XP
        try {
            app(\App\Services\GamificationService::class)->awardXp(
                Auth::user(),
                50,
                'review',
                null,
                ['film_id' => $film->id, 'film_title' => $film->title, 'rating' => $validated['rating']]
            );
        } catch (\Exception $e) {
            \Log::error('Gamification review XP error: ' . $e->getMessage());
        }

        return back()->with('success', 'Ulasan berhasil disimpan! +50 Cinephile XP diperoleh.');
    }

    public function destroy(Review $review)
    {
        if ($review->user_id !== Auth::id()) {
            abort(403);
        }

        if (session()->has('active_profile_id')) {
            return back()->with('error', 'Hanya Akun Utama yang dapat mengelola ulasan.');
        }

        $film = $review->film;
        $review->delete();
        $film->updateAverageRating();

        return back()->with('success', 'Ulasan berhasil dihapus.');
    }
}
