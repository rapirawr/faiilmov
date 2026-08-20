<?php

namespace App\Http\Controllers;

use App\Services\GamificationService;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
    protected GamificationService $gamification;

    public function __construct(GamificationService $gamification)
    {
        $this->gamification = $gamification;
    }

    /**
     * Display the Global Cinephile Leaderboard
     */
    public function index(Request $request)
    {
        $period = $request->query('period', 'all');
        if (!in_array($period, ['weekly', 'monthly', 'all'])) {
            $period = 'all';
        }

        $user = Auth::user();
        $leaderboardData = $this->gamification->getLeaderboard($period, 50, $user);

        // Fetch badges list for the side-drawer / showcase
        $badges = Badge::orderBy('category')->get()->groupBy('category');

        $userBadgesIds = $user ? $user->badges()->pluck('badges.id')->toArray() : [];
        $userLevelInfo = $user ? $this->gamification->calculateLevelInfo((int)$user->xp_total) : null;

        return view('leaderboard.index', [
            'period'          => $period,
            'podium'          => $leaderboardData['podium'],
            'rankings'        => $leaderboardData['list'],
            'currentUserRank' => $leaderboardData['current_user_rank'],
            'badges'          => $badges,
            'userBadgesIds'   => $userBadgesIds,
            'userLevelInfo'   => $userLevelInfo,
            'tiers'           => GamificationService::TIERS,
        ]);
    }

    /**
     * Toggle leaderboard anonymous privacy mode
     */
    public function togglePrivacy(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        $newVal = !$user->is_anonymous_leaderboard;
        $user->update(['is_anonymous_leaderboard' => $newVal]);

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'is_anonymous' => $newVal,
                'message'      => $newVal ? 'Mode Anonim Leaderboard diaktifkan.' : 'Mode Nama Publik diaktifkan.',
            ]);
        }

        return back()->with('success', $newVal ? 'Mode Anonim diaktifkan. Nama Anda akan disamarkan di Leaderboard.' : 'Nama Anda sekarang ditampilkan di Leaderboard.');
    }
}
