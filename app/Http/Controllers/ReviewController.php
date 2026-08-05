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

        return back()->with('success', 'Ulasan dan rating berhasil disimpan!');
    }

    public function destroy(Review $review)
    {
        if ($review->user_id !== Auth::id()) {
            abort(403);
        }

        $film = $review->film;
        $review->delete();
        $film->updateAverageRating();

        return back()->with('success', 'Ulasan berhasil dihapus.');
    }
}
