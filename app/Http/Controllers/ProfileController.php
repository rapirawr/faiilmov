<?php

namespace App\Http\Controllers;

use App\Models\WatchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $watchlists = $user->watchlists()
            ->with('film')
            ->latest()
            ->get();

        $reviews = $user->reviews()
            ->with('film')
            ->latest()
            ->get();

        $watchHistories = $user->watchHistories()
            ->with(['film.seasons.episodes'])
            ->latest('updated_at')
            ->get();

        return view('profile', compact('user', 'watchlists', 'reviews', 'watchHistories'));
    }

    public function destroyHistory(WatchHistory $watchHistory)
    {
        if ($watchHistory->user_id === Auth::id()) {
            $watchHistory->delete();
        }

        return redirect()->back()->with('success', 'Riwayat tontonan berhasil dihapus.');
    }

    public function clearHistory()
    {
        Auth::user()->watchHistories()->delete();

        return redirect()->back()->with('success', 'Seluruh riwayat tontonan berhasil dibersihkan.');
    }
}
