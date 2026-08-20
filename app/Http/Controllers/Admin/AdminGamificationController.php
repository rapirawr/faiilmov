<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserXpLog;
use App\Models\Setting;
use App\Models\AdminActivityLog;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminGamificationController extends Controller
{
    public function __construct(
        protected GamificationService $gamificationService
    ) {}

    /**
     * Display the Gamification CMS Hub
     */
    public function index(Request $request)
    {
        // 1. KPI & Overview Metrics
        $totalXpDistributed = (int)User::sum('xp_total');
        $totalBadgesCount   = Badge::count();
        $totalBadgeUnlocks  = DB::table('user_badges')->count();
        $activeStreakUsers  = User::where('streak_count', '>', 0)->count();
        $avgUserLevel       = round(User::avg('current_level') ?? 1, 1);
        $topLeader          = User::orderByDesc('xp_total')->first();

        // 2. Badges Management with search and filter
        $categoryFilter = $request->query('category', 'all');
        $searchQuery    = $request->query('q', '');

        $badgesQuery = Badge::withCount('users')->orderByDesc('is_active')->orderBy('category')->orderBy('name');

        if ($categoryFilter && $categoryFilter !== 'all') {
            $badgesQuery->where('category', $categoryFilter);
        }

        if ($searchQuery) {
            $badgesQuery->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', "%{$searchQuery}%")
                  ->orWhere('code', 'like', "%{$searchQuery}%")
                  ->orWhere('description', 'like', "%{$searchQuery}%");
            });
        }

        $badges = $badgesQuery->paginate(15)->withQueryString();

        // 3. Gamification Settings
        $settings = [
            'gamification_xp_watch_minute' => (int)Setting::get('gamification_xp_watch_minute', 1),
            'gamification_xp_review'       => (int)Setting::get('gamification_xp_review', 50),
            'gamification_xp_comment'      => (int)Setting::get('gamification_xp_comment', 15),
            'gamification_xp_watch_party'  => (int)Setting::get('gamification_xp_watch_party', 30),
            'gamification_streak_bonus'    => (int)Setting::get('gamification_streak_bonus', 25),
            'feature_gamification'         => Setting::get('feature_gamification', '1') === '1',
            'feature_movie_wrapped'        => Setting::get('feature_movie_wrapped', '1') === '1',
            'feature_leaderboard_anonymous_toggle' => Setting::get('feature_leaderboard_anonymous_toggle', '1') === '1',
        ];

        // 4. Recent XP Activity Logs
        $recentXpLogs = UserXpLog::with('user')
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        // 5. Top Leaderboard Preview
        $topUsers = User::orderByDesc('xp_total')
            ->take(10)
            ->get();

        // 6. Users List for quick modal dropdown
        $usersList = User::select('id', 'name', 'email', 'xp_total', 'current_level')
            ->orderBy('name')
            ->take(100)
            ->get();

        $allBadges = Badge::where('is_active', true)->orderBy('name')->get();

        return view('admin.gamification.index', compact(
            'totalXpDistributed',
            'totalBadgesCount',
            'totalBadgeUnlocks',
            'activeStreakUsers',
            'avgUserLevel',
            'topLeader',
            'badges',
            'settings',
            'recentXpLogs',
            'topUsers',
            'usersList',
            'allBadges',
            'categoryFilter',
            'searchQuery'
        ));
    }

    /**
     * Create a new Badge
     */
    public function storeBadge(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'nullable|string|max:60|unique:badges,code',
            'description'    => 'required|string|max:500',
            'category'       => 'required|string|in:milestone,genre,habit,community',
            'icon'           => 'required|string|max:50',
            'color'          => 'required|string|max:30',
            'xp_reward'      => 'required|integer|min:0|max:10000',
            'required_count' => 'required|integer|min:1|max:10000',
            'is_active'      => 'nullable|boolean',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
            // Ensure unique code
            $baseCode = $validated['code'];
            $counter = 1;
            while (Badge::where('code', $validated['code'])->exists()) {
                $validated['code'] = $baseCode . '_' . $counter++;
            }
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $badge = Badge::create($validated);

        AdminActivityLog::log(
            'create_badge',
            "Membuat badge baru: {$badge->name} ({$badge->category}) dengan reward +{$badge->xp_reward} XP",
            'Badge',
            $badge->id
        );

        return redirect()->route('admin.gamification.index', ['category' => $badge->category])
            ->with('success', "Badge \"{$badge->name}\" berhasil dibuat!");
    }

    /**
     * Update an existing Badge
     */
    public function updateBadge(Request $request, Badge $badge)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'required|string|max:60|unique:badges,code,' . $badge->id,
            'description'    => 'required|string|max:500',
            'category'       => 'required|string|in:milestone,genre,habit,community',
            'icon'           => 'required|string|max:50',
            'color'          => 'required|string|max:30',
            'xp_reward'      => 'required|integer|min:0|max:10000',
            'required_count' => 'required|integer|min:1|max:10000',
            'is_active'      => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $badge->update($validated);

        AdminActivityLog::log(
            'update_badge',
            "Memperbarui badge: {$badge->name} (ID: {$badge->id})",
            'Badge',
            $badge->id
        );

        return redirect()->back()->with('success', "Badge \"{$badge->name}\" berhasil diperbarui!");
    }

    /**
     * Quick toggle Badge active status
     */
    public function toggleBadge(Badge $badge)
    {
        $badge->update([
            'is_active' => !$badge->is_active,
        ]);

        $status = $badge->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AdminActivityLog::log(
            'toggle_badge',
            "Status badge {$badge->name} diubah menjadi {$status}",
            'Badge',
            $badge->id
        );

        return redirect()->back()->with('success', "Badge \"{$badge->name}\" berhasil {$status}!");
    }

    /**
     * Delete a Badge
     */
    public function destroyBadge(Badge $badge)
    {
        $name = $badge->name;
        $badgeId = $badge->id;

        // Detach from all users first
        $badge->users()->detach();
        $badge->delete();

        AdminActivityLog::log(
            'delete_badge',
            "Menghapus badge: {$name} (ID: {$badgeId})",
            'Badge',
            $badgeId
        );

        return redirect()->back()->with('success', "Badge \"{$name}\" berhasil dihapus!");
    }

    /**
     * Update Gamification XP & Rate Settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'gamification_xp_watch_minute' => 'required|integer|min:0|max:100',
            'gamification_xp_review'       => 'required|integer|min:0|max:1000',
            'gamification_xp_comment'      => 'required|integer|min:0|max:500',
            'gamification_xp_watch_party'  => 'required|integer|min:0|max:1000',
            'gamification_streak_bonus'    => 'required|integer|min:0|max:500',
            'feature_gamification'         => 'nullable|boolean',
            'feature_movie_wrapped'        => 'nullable|boolean',
            'feature_leaderboard_anonymous_toggle' => 'nullable|boolean',
        ]);

        Setting::set('gamification_xp_watch_minute', (string)$validated['gamification_xp_watch_minute']);
        Setting::set('gamification_xp_review', (string)$validated['gamification_xp_review']);
        Setting::set('gamification_xp_comment', (string)$validated['gamification_xp_comment']);
        Setting::set('gamification_xp_watch_party', (string)$validated['gamification_xp_watch_party']);
        Setting::set('gamification_streak_bonus', (string)$validated['gamification_streak_bonus']);

        Setting::set('feature_gamification', $request->boolean('feature_gamification') ? '1' : '0');
        Setting::set('feature_movie_wrapped', $request->boolean('feature_movie_wrapped') ? '1' : '0');
        Setting::set('feature_leaderboard_anonymous_toggle', $request->boolean('feature_leaderboard_anonymous_toggle') ? '1' : '0');

        AdminActivityLog::log(
            'update_gamification_settings',
            'Memperbarui konfigurasi tarif XP dan fitur Gamification'
        );

        return redirect()->back()->with('success', 'Konfigurasi Gamification & Movie Wrapped berhasil disimpan!');
    }

    /**
     * Manually award or deduct XP for a user
     */
    public function awardUserXp(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount'  => 'required|integer|not_in:0|min:-50000|max:50000',
            'reason'  => 'required|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $amount = (int)$validated['amount'];
        $reason = $validated['reason'];

        if ($amount > 0) {
            $this->gamificationService->awardXp(
                $user,
                $amount,
                'admin_manual',
                null,
                [
                    'reason'   => $reason,
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()->name,
                ]
            );
            $actionLabel = "diberikan +{$amount} XP";
        } else {
            // Deduct XP
            $deduction = abs($amount);
            $newXp = max(0, (int)$user->xp_total - $deduction);
            $user->update(['xp_total' => $newXp]);

            $levelInfo = $this->gamificationService->calculateLevelInfo($newXp);
            $user->update(['current_level' => $levelInfo['level']]);

            UserXpLog::create([
                'user_id'    => $user->id,
                'amount'     => $amount,
                'source'     => 'admin_manual_deduct',
                'metadata'   => [
                    'reason'     => $reason,
                    'admin_id'   => auth()->id(),
                    'admin_name' => auth()->user()->name,
                ],
                'created_at' => now(),
            ]);

            $actionLabel = "dikurangi {$deduction} XP";
        }

        AdminActivityLog::log(
            'adjust_user_xp',
            "Pengguna {$user->name} (#{$user->id}) {$actionLabel}. Alasan: {$reason}",
            'User',
            $user->id
        );

        return redirect()->back()->with('success', "Berhasil menyesuaikan XP untuk {$user->name} ({$actionLabel})!");
    }

    /**
     * Manually grant a badge to a user
     */
    public function awardUserBadge(Request $request)
    {
        $validated = $request->validate([
            'user_id'  => 'required|exists:users,id',
            'badge_id' => 'required|exists:badges,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $badge = Badge::findOrFail($validated['badge_id']);

        if ($user->badges()->where('badge_id', $badge->id)->exists()) {
            return redirect()->back()->with('error', "Pengguna {$user->name} sudah memiliki badge \"{$badge->name}\"!");
        }

        // Attach badge
        $user->badges()->attach($badge->id, ['unlocked_at' => now()]);

        // Award badge XP reward
        if ($badge->xp_reward > 0) {
            $this->gamificationService->awardXp(
                $user,
                (int)$badge->xp_reward,
                'badge_unlock',
                null,
                [
                    'badge_id'   => $badge->id,
                    'badge_name' => $badge->name,
                    'note'       => 'Manually granted by admin',
                ]
            );
        }

        AdminActivityLog::log(
            'award_user_badge',
            "Memberikan badge {$badge->name} kepada pengguna {$user->name} (#{$user->id})",
            'User',
            $user->id
        );

        return redirect()->back()->with('success', "Badge \"{$badge->name}\" berhasil diberikan kepada {$user->name}!");
    }

    /**
     * Revoke a badge from a user
     */
    public function revokeUserBadge(User $user, Badge $badge)
    {
        $user->badges()->detach($badge->id);

        AdminActivityLog::log(
            'revoke_user_badge',
            "Mencabut badge {$badge->name} dari pengguna {$user->name} (#{$user->id})",
            'User',
            $user->id
        );

        return redirect()->back()->with('success', "Badge \"{$badge->name}\" berhasil dicabut dari {$user->name}!");
    }

    /**
     * Recompute & sync all users' XP, streaks, and badges
     */
    public function recomputeAll()
    {
        try {
            Artisan::call('gamification:recompute');
            $output = Artisan::output();

            AdminActivityLog::log(
                'recompute_gamification',
                'Menjalankan sinkronisasi dan kalkulasi ulang XP seluruh pengguna platform'
            );

            return redirect()->back()->with('success', 'Kalkulasi ulang XP, level, dan lencana seluruh pengguna berhasil dijalankan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melakukan kalkulasi ulang: ' . $e->getMessage());
        }
    }
}
