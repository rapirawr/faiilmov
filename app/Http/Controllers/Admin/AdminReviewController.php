<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'film'])->withCount('reports');

        $filter = $request->get('filter', 'latest');

        if ($filter === 'reported') {
            $query->has('reports')->orderBy('reports_count', 'desc');
        } elseif ($filter === 'lowest_rating') {
            $query->orderBy('rating', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('film', fn($f) => $f->where('title', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->paginate(15)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function destroy(Request $request, Review $review)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $filmTitle = $review->film->title ?? 'Film';
        $userName = $review->user->name ?? 'User';
        $reviewId = $review->id;

        $review->delete();

        AdminActivityLog::log(
            'deleted_review',
            "Menghapus ulasan oleh '{$userName}' pada film '{$filmTitle}'. Alasan: {$validated['reason']}",
            'Review',
            $reviewId
        );

        return redirect()->route('admin.reviews.index')->with('success', 'Ulasan berhasil dihapus dan alasan dicatat di Activity Log.');
    }

    public function dismissReports(Review $review)
    {
        ReviewReport::where('review_id', $review->id)->update(['status' => 'dismissed']);

        AdminActivityLog::log('dismissed_review_reports', "Mengabaikan laporan ulasan ID: {$review->id}", 'Review', $review->id);

        return redirect()->route('admin.reviews.index')->with('success', 'Laporan ulasan berhasil diabaikan.');
    }

    public function storeReport(Request $request, Review $review)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        ReviewReport::create([
            'review_id' => $review->id,
            'user_id' => Auth::id(),
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Terima kasih, laporan ulasan berhasil dikirim untuk ditinjau oleh Admin.');
    }
}
