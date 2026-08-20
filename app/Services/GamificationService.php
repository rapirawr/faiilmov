<?php

namespace App\Services;

use App\Models\User;
use App\Models\Badge;
use App\Models\UserXpLog;
use App\Models\Film;
use App\Models\WatchHistory;
use App\Models\Review;
use App\Models\EpisodeComment;
use App\Models\WatchPartyParticipant;
use App\Models\Collection;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    /**
     * Tier definitions for Cinephile Levels (No emojis, Lucide icons only)
     */
    public const TIERS = [
        [
            'min_level' => 100,
            'title'     => 'Grand Auteur',
            'icon'      => 'trophy',
            'color'     => 'amber',
            'bg_class'  => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
        ],
        [
            'min_level' => 75,
            'title'     => 'Film Connoisseur',
            'icon'      => 'crown',
            'color'     => 'yellow',
            'bg_class'  => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/40',
        ],
        [
            'min_level' => 50,
            'title'     => 'Cinephile Buff',
            'icon'      => 'sparkles',
            'color'     => 'purple',
            'bg_class'  => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
        ],
        [
            'min_level' => 25,
            'title'     => 'Cinema Explorer',
            'icon'      => 'compass',
            'color'     => 'cyan',
            'bg_class'  => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40',
        ],
        [
            'min_level' => 10,
            'title'     => 'Casual Viewer',
            'icon'      => 'clapperboard',
            'color'     => 'emerald',
            'bg_class'  => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
        ],
        [
            'min_level' => 1,
            'title'     => 'Film Novice',
            'icon'      => 'film',
            'color'     => 'zinc',
            'bg_class'  => 'bg-zinc-700/40 text-zinc-300 border-zinc-600/40',
        ],
    ];

    /**
     * Calculate Level info from XP
     * Formula: XP required for level L = 100 * (L - 1)^1.4
     */
    public function calculateLevelInfo(int $xp): array
    {
        $xp = max(0, $xp);
        $level = 1;

        while (true) {
            $nextLevelRequiredXp = (int)round(100 * pow($level, 1.4));
            if ($xp < $nextLevelRequiredXp) {
                $prevLevelXp = $level > 1 ? (int)round(100 * pow($level - 1, 1.4)) : 0;
                $levelRange = max(1, $nextLevelRequiredXp - $prevLevelXp);
                $currentProgressXp = $xp - $prevLevelXp;
                $percent = min(100, max(0, round(($currentProgressXp / $levelRange) * 100)));

                $tier = $this->getTierForLevel($level);

                return [
                    'level'               => $level,
                    'title'               => $tier['title'],
                    'icon'                => $tier['icon'],
                    'color'               => $tier['color'],
                    'bg_class'            => $tier['bg_class'],
                    'total_xp'            => $xp,
                    'current_level_xp'    => $currentProgressXp,
                    'next_level_xp'       => $levelRange,
                    'total_next_level_xp' => $nextLevelRequiredXp,
                    'progress_percent'    => $percent,
                ];
            }
            $level++;
            if ($level >= 999) {
                break;
            }
        }

        $tier = self::TIERS[0];
        return [
            'level'               => $level,
            'title'               => $tier['title'],
            'icon'                => $tier['icon'],
            'color'               => $tier['color'],
            'bg_class'            => $tier['bg_class'],
            'total_xp'            => $xp,
            'current_level_xp'    => 100,
            'next_level_xp'       => 100,
            'total_next_level_xp' => $xp,
            'progress_percent'    => 100,
        ];
    }

    /**
     * Get Tier by level number
     */
    public function getTierForLevel(int $level): array
    {
        foreach (self::TIERS as $tier) {
            if ($level >= $tier['min_level']) {
                return $tier;
            }
        }
        return end(self::TIERS);
    }

    /**
     * Award XP to user and check level ups & badges
     */
    public function awardXp(User $user, int $amount, string $source, ?int $profileId = null, array $metadata = []): array
    {
        // Check if gamification is enabled globally
        if (Setting::get('feature_gamification', '1') !== '1') {
            return [
                'awarded'     => 0,
                'total_xp'    => (int)$user->xp_total,
                'leveled_up'  => false,
                'new_badges'  => [],
            ];
        }

        if ($amount <= 0) {
            return [
                'awarded'     => 0,
                'total_xp'    => $user->xp_total,
                'leveled_up'  => false,
                'new_badges'  => [],
            ];
        }

        $oldLevelInfo = $this->calculateLevelInfo((int)$user->xp_total);

        // Record XP log
        UserXpLog::create([
            'user_id'    => $user->id,
            'profile_id' => $profileId,
            'amount'     => $amount,
            'source'     => $source,
            'metadata'   => $metadata,
            'created_at' => now(),
        ]);

        // Update user XP
        $user->increment('xp_total', $amount);
        $user->refresh();

        $newLevelInfo = $this->calculateLevelInfo((int)$user->xp_total);
        $leveledUp = ($newLevelInfo['level'] > $oldLevelInfo['level']);

        if ($leveledUp || $user->current_level != $newLevelInfo['level']) {
            $user->update(['current_level' => $newLevelInfo['level']]);
        }

        // Check badge unlocks
        $newBadges = $this->checkAndUnlockBadges($user);

        return [
            'awarded'     => $amount,
            'total_xp'    => (int)$user->xp_total,
            'level_info'  => $newLevelInfo,
            'leveled_up'  => $leveledUp,
            'new_badges'  => $newBadges,
        ];
    }

    /**
     * Process daily watch streak logic
     */
    public function updateWatchStreak(User $user): array
    {
        $today = Carbon::today();
        $lastWatch = $user->last_watch_date ? Carbon::parse($user->last_watch_date)->startOfDay() : null;

        $baseBonus = (int)Setting::get('gamification_streak_bonus', 25);
        $streakBonus = 0;
        $streakUpdated = false;

        if (!$lastWatch) {
            // First time watching
            $user->update([
                'streak_count'    => 1,
                'last_watch_date' => $today->toDateString(),
            ]);
            $streakBonus = $baseBonus;
            $streakUpdated = true;
        } elseif ($lastWatch->eq($today)) {
            // Already watched today, no streak increment
            $streakUpdated = false;
        } elseif ($lastWatch->diffInDays($today) === 1) {
            // Continuous streak!
            $newStreak = (int)$user->streak_count + 1;
            $user->update([
                'streak_count'    => $newStreak,
                'last_watch_date' => $today->toDateString(),
            ]);
            $streakBonus = $baseBonus + min(100, $newStreak * 5); // escalating bonus
            $streakUpdated = true;
        } else {
            // Streak broken
            $user->update([
                'streak_count'    => 1,
                'last_watch_date' => $today->toDateString(),
            ]);
            $streakBonus = $baseBonus;
            $streakUpdated = true;
        }

        if ($streakBonus > 0 && $streakUpdated) {
            $this->awardXp($user, $streakBonus, 'daily_streak', null, [
                'streak_day' => $user->streak_count,
            ]);
        }

        return [
            'streak_count'   => (int)$user->streak_count,
            'streak_updated' => $streakUpdated,
            'streak_bonus'   => $streakBonus,
        ];
    }

    /**
     * Process watch progress event: awards watch time XP & checks badges
     */
    public function handleWatchProgress(User $user, Film $film, int $currentProgressSeconds, ?int $profileId = null): array
    {
        // 1. Process daily streak
        $streakResult = $this->updateWatchStreak($user);

        // 2. Award watch XP (1 XP per minute, capped at 10 XP per ping window)
        $minutesWatched = max(1, (int)round($currentProgressSeconds / 60));
        
        // Prevent duplicate spam by checking recent logs for same film in last 5 minutes
        $recentLog = UserXpLog::where('user_id', $user->id)
            ->where('source', 'watch_time')
            ->where('created_at', '>=', now()->subMinutes(3))
            ->whereJsonContains('metadata->film_id', $film->id)
            ->first();

        $xpEarned = 0;
        if (!$recentLog) {
            $xpEarned = 5; // Base XP for active watch session interval
            $this->awardXp($user, $xpEarned, 'watch_time', $profileId, [
                'film_id'    => $film->id,
                'film_title' => $film->title,
                'seconds'    => $currentProgressSeconds,
            ]);
        }

        // 3. Check specific time habits (Midnight Owl: 00:00 - 04:00)
        $currentHour = (int)now()->format('H');
        if ($currentHour >= 0 && $currentHour < 4) {
            $this->unlockBadgeByCode($user, 'midnight_owl');
        }

        // 4. Check First Watch milestone
        $this->unlockBadgeByCode($user, 'first_watch');

        // 5. Check other milestones & genre badges
        $newBadges = $this->checkAndUnlockBadges($user);

        return [
            'xp_earned'     => $xpEarned,
            'streak'        => $streakResult,
            'new_badges'    => $newBadges,
            'total_xp'      => (int)$user->xp_total,
            'current_level' => (int)$user->current_level,
        ];
    }

    /**
     * Evaluate and unlock all qualified badges for a user
     */
    public function checkAndUnlockBadges(User $user): array
    {
        $unlockedBadges = [];
        $existingBadgeIds = DB::table('user_badges')
            ->where('user_id', $user->id)
            ->pluck('badge_id')
            ->toArray();

        $allBadges = Badge::where('is_active', true)->get();

        // Count watch histories & total distinct films
        $watchHistories = WatchHistory::where('user_id', $user->id)->with('film.genres')->get();
        $totalWatches = $watchHistories->count();

        // Count reviews, comments, watch parties, collections
        $reviewsCount = Review::where('user_id', $user->id)->count();
        $commentsCount = EpisodeComment::where('user_id', $user->id)->count();
        $partiesCount = WatchPartyParticipant::where('user_id', $user->id)->count();
        $collectionsCount = Collection::where('created_by', $user->id)->count();
        $streak = (int)$user->streak_count;

        // Genre counts
        $genreCounts = [];
        foreach ($watchHistories as $wh) {
            if ($wh->film && $wh->film->genres) {
                foreach ($wh->film->genres as $genre) {
                    $slug = strtolower($genre->slug ?? '');
                    $genreCounts[$slug] = ($genreCounts[$slug] ?? 0) + 1;
                }
            }
        }

        foreach ($allBadges as $badge) {
            if (in_array($badge->id, $existingBadgeIds)) {
                continue;
            }

            $shouldUnlock = false;

            switch ($badge->code) {
                case 'first_watch':
                    $shouldUnlock = ($totalWatches >= 1);
                    break;
                case 'film_century':
                    $shouldUnlock = ($totalWatches >= 100);
                    break;
                case 'early_adopter':
                    $shouldUnlock = ($user->id <= 500);
                    break;
                case 'streak_3_days':
                    $shouldUnlock = ($streak >= 3);
                    break;
                case 'streak_7_days':
                    $shouldUnlock = ($streak >= 7);
                    break;
                case 'binge_champion':
                    // Check if watched >= 5 in a single day
                    $maxInDay = WatchHistory::where('user_id', $user->id)
                        ->select(DB::raw('DATE(updated_at) as watch_day'), DB::raw('count(*) as cnt'))
                        ->groupBy('watch_day')
                        ->orderByDesc('cnt')
                        ->first();
                    $shouldUnlock = ($maxInDay && $maxInDay->cnt >= 5);
                    break;
                case 'film_critic':
                    $shouldUnlock = ($reviewsCount >= 3);
                    break;
                case 'discussion_starter':
                    $shouldUnlock = ($commentsCount >= 5);
                    break;
                case 'party_goer':
                    $shouldUnlock = ($partiesCount >= 1);
                    break;
                case 'collection_curator':
                    $shouldUnlock = ($collectionsCount >= 1);
                    break;
                case 'horror_enthusiast':
                    $shouldUnlock = (($genreCounts['horror'] ?? 0) + ($genreCounts['thriller'] ?? 0) >= 5);
                    break;
                case 'anime_master':
                    $shouldUnlock = (($genreCounts['anime'] ?? 0) + ($genreCounts['animation'] ?? 0) >= 5);
                    break;
                case 'kdrama_lover':
                    $shouldUnlock = (($genreCounts['drama'] ?? 0) + ($genreCounts['k-drama'] ?? 0) >= 5);
                    break;
                case 'scifi_voyager':
                    $shouldUnlock = (($genreCounts['sci-fi'] ?? 0) + ($genreCounts['science-fiction'] ?? 0) >= 5);
                    break;
                case 'action_buff':
                    $shouldUnlock = (($genreCounts['action'] ?? 0) + ($genreCounts['adventure'] ?? 0) >= 5);
                    break;
            }

            if ($shouldUnlock) {
                DB::table('user_badges')->insert([
                    'user_id'     => $user->id,
                    'badge_id'    => $badge->id,
                    'unlocked_at' => now(),
                ]);

                // Award badge bonus XP
                if ($badge->xp_reward > 0) {
                    $user->increment('xp_total', $badge->xp_reward);
                    UserXpLog::create([
                        'user_id'    => $user->id,
                        'amount'     => $badge->xp_reward,
                        'source'     => 'badge_unlock',
                        'metadata'   => ['badge_code' => $badge->code, 'badge_name' => $badge->name],
                        'created_at' => now(),
                    ]);
                }

                $unlockedBadges[] = $badge;
            }
        }

        return $unlockedBadges;
    }

    /**
     * Unlock a specific badge by its code
     */
    public function unlockBadgeByCode(User $user, string $code): ?Badge
    {
        $badge = Badge::where('code', $code)->first();
        if (!$badge) {
            return null;
        }

        $exists = DB::table('user_badges')
            ->where('user_id', $user->id)
            ->where('badge_id', $badge->id)
            ->exists();

        if ($exists) {
            return null;
        }

        DB::table('user_badges')->insert([
            'user_id'     => $user->id,
            'badge_id'    => $badge->id,
            'unlocked_at' => now(),
        ]);

        if ($badge->xp_reward > 0) {
            $user->increment('xp_total', $badge->xp_reward);
            UserXpLog::create([
                'user_id'    => $user->id,
                'amount'     => $badge->xp_reward,
                'source'     => 'badge_unlock',
                'metadata'   => ['badge_code' => $badge->code, 'badge_name' => $badge->name],
                'created_at' => now(),
            ]);
        }

        return $badge;
    }

    /**
     * Get Global Leaderboard ranking with period filters
     */
    public function getLeaderboard(string $period = 'all', int $limit = 50, ?User $currentUser = null): array
    {
        $period = in_array($period, ['weekly', 'monthly', 'all']) ? $period : 'all';

        if ($period === 'all') {
            $usersQuery = User::select('id', 'name', 'avatar', 'xp_total', 'current_level', 'is_anonymous_leaderboard', 'created_at')
                ->where('xp_total', '>', 0)
                ->orderByDesc('xp_total')
                ->orderBy('id')
                ->limit($limit);

            $rawLeaderboard = $usersQuery->get();

            $rankings = [];
            $rank = 1;
            foreach ($rawLeaderboard as $u) {
                $levelInfo = $this->calculateLevelInfo((int)$u->xp_total);
                $displayName = $u->is_anonymous_leaderboard ? 'Cinephile #' . substr(md5($u->id), 0, 5) : $u->name;
                $avatarUrl = $u->is_anonymous_leaderboard 
                    ? 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($u->id)
                    : ($u->avatar ? (str_starts_with($u->avatar, 'http') ? $u->avatar : asset('storage/' . $u->avatar)) : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($u->name));

                $rankings[] = [
                    'rank'         => $rank++,
                    'user_id'      => $u->id,
                    'name'         => $displayName,
                    'avatar'       => $avatarUrl,
                    'xp'           => (int)$u->xp_total,
                    'level'        => $levelInfo['level'],
                    'tier_title'   => $levelInfo['title'],
                    'tier_icon'    => $levelInfo['icon'],
                    'tier_color'   => $levelInfo['color'],
                    'bg_class'     => $levelInfo['bg_class'],
                    'is_current'   => $currentUser && $currentUser->id === $u->id,
                    'is_anonymous' => (bool)$u->is_anonymous_leaderboard,
                ];
            }

            // Current user rank calculation if not in top list
            $currentUserRank = null;
            if ($currentUser) {
                $userPos = collect($rankings)->firstWhere('user_id', $currentUser->id);
                if ($userPos) {
                    $currentUserRank = $userPos;
                } else {
                    $higherCount = User::where('xp_total', '>', (int)$currentUser->xp_total)->count();
                    $levelInfo = $this->calculateLevelInfo((int)$currentUser->xp_total);
                    $currentUserRank = [
                        'rank'         => $higherCount + 1,
                        'user_id'      => $currentUser->id,
                        'name'         => $currentUser->is_anonymous_leaderboard ? 'Cinephile #' . substr(md5($currentUser->id), 0, 5) : $currentUser->name,
                        'avatar'       => $currentUser->avatar ? (str_starts_with($currentUser->avatar, 'http') ? $currentUser->avatar : asset('storage/' . $currentUser->avatar)) : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($currentUser->name),
                        'xp'           => (int)$currentUser->xp_total,
                        'level'        => $levelInfo['level'],
                        'tier_title'   => $levelInfo['title'],
                        'tier_icon'    => $levelInfo['icon'],
                        'tier_color'   => $levelInfo['color'],
                        'bg_class'     => $levelInfo['bg_class'],
                        'is_current'   => true,
                        'is_anonymous' => (bool)$currentUser->is_anonymous_leaderboard,
                    ];
                }
            }

            return [
                'period'            => $period,
                'podium'            => array_slice($rankings, 0, 3),
                'list'              => array_slice($rankings, 3),
                'all'               => $rankings,
                'current_user_rank' => $currentUserRank,
            ];
        }

        // Weekly & Monthly calculations from user_xp_logs
        $startDate = match ($period) {
            'weekly'  => now()->subDays(7)->startOfDay(),
            'monthly' => now()->startOfMonth(),
        };

        $periodQuery = DB::table('user_xp_logs')
            ->select('user_id', DB::raw('SUM(amount) as period_xp'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('user_id')
            ->orderByDesc('period_xp')
            ->limit($limit)
            ->get();

        $userIds = $periodQuery->pluck('user_id')->toArray();
        $usersMap = User::whereIn('id', $userIds)->get()->keyBy('id');

        $rankings = [];
        $rank = 1;
        foreach ($periodQuery as $row) {
            $u = $usersMap->get($row->user_id);
            if (!$u) continue;

            $levelInfo = $this->calculateLevelInfo((int)$u->xp_total);
            $displayName = $u->is_anonymous_leaderboard ? 'Cinephile #' . substr(md5($u->id), 0, 5) : $u->name;
            $avatarUrl = $u->is_anonymous_leaderboard 
                ? 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($u->id)
                : ($u->avatar ? (str_starts_with($u->avatar, 'http') ? $u->avatar : asset('storage/' . $u->avatar)) : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($u->name));

            $rankings[] = [
                'rank'         => $rank++,
                'user_id'      => $u->id,
                'name'         => $displayName,
                'avatar'       => $avatarUrl,
                'xp'           => (int)$row->period_xp,
                'level'        => $levelInfo['level'],
                'tier_title'   => $levelInfo['title'],
                'tier_icon'    => $levelInfo['icon'],
                'tier_color'   => $levelInfo['color'],
                'bg_class'     => $levelInfo['bg_class'],
                'is_current'   => $currentUser && $currentUser->id === $u->id,
                'is_anonymous' => (bool)$u->is_anonymous_leaderboard,
            ];
        }

        $currentUserRank = null;
        if ($currentUser) {
            $userPos = collect($rankings)->firstWhere('user_id', $currentUser->id);
            if ($userPos) {
                $currentUserRank = $userPos;
            } else {
                $userPeriodXp = (int)UserXpLog::where('user_id', $currentUser->id)
                    ->where('created_at', '>=', $startDate)
                    ->sum('amount');

                $levelInfo = $this->calculateLevelInfo((int)$currentUser->xp_total);
                $currentUserRank = [
                    'rank'         => count($rankings) + 1,
                    'user_id'      => $currentUser->id,
                    'name'         => $currentUser->is_anonymous_leaderboard ? 'Cinephile #' . substr(md5($currentUser->id), 0, 5) : $currentUser->name,
                    'avatar'       => $currentUser->avatar ? (str_starts_with($currentUser->avatar, 'http') ? $currentUser->avatar : asset('storage/' . $currentUser->avatar)) : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($currentUser->name),
                    'xp'           => $userPeriodXp,
                    'level'        => $levelInfo['level'],
                    'tier_title'   => $levelInfo['title'],
                    'tier_icon'    => $levelInfo['icon'],
                    'tier_color'   => $levelInfo['color'],
                    'bg_class'     => $levelInfo['bg_class'],
                    'is_current'   => true,
                    'is_anonymous' => (bool)$currentUser->is_anonymous_leaderboard,
                ];
            }
        }

        return [
            'period'            => $period,
            'podium'            => array_slice($rankings, 0, 3),
            'list'              => array_slice($rankings, 3),
            'all'               => $rankings,
            'current_user_rank' => $currentUserRank,
        ];
    }
}
