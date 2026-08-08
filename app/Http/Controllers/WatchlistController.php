<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WatchlistController extends Controller
{
    public function toggle(Request $request, Film $film)
    {
        $status = $request->input('status', 'plan_to_watch');

        return DB::transaction(function () use ($request, $film, $status) {
            $existing = Watchlist::where('user_id', Auth::id())
                ->where('film_id', $film->id)
                ->lockForUpdate()
                ->first();

            $message = '';
            $inWatchlist = true;
            
            if ($existing) {
                if ($existing->status === $status && !$request->has('force')) {
                    $existing->delete();
                    $message = 'Film dihapus dari Watchlist.';
                    $inWatchlist = false;
                } else {
                    $existing->update(['status' => $status]);
                    $message = 'Status Watchlist diperbarui.';
                }
            } else {
                Watchlist::create([
                    'user_id' => Auth::id(),
                    'film_id' => $film->id,
                    'status'  => $status,
                ]);
                $message = 'Film ditambahkan ke Watchlist.';
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status'      => 'ok',
                    'message'     => $message,
                    'inWatchlist' => $inWatchlist
                ]);
            }

            $redirectUrl = url()->previous();
            if (empty($redirectUrl) || str_contains($redirectUrl, '/search/') || str_contains($redirectUrl, '/moviebox/')) {
                $redirectUrl = route('film.show', $film->slug);
            }

            return redirect($redirectUrl)->with('success', $message);
        });
    }
}
