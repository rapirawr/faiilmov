<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\WatchHistory;
use App\Models\Review;
use App\Models\EpisodeComment;
use App\Models\WatchPartyParticipant;
use App\Services\GamificationService;
use App\Models\UserXpLog;
use Illuminate\Support\Facades\DB;

class RecomputeGamificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:recompute {--user= : Optional specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill and calculate Cinephile XP, levels, and badges from existing histories, reviews, and comments.';

    /**
     * Execute the console command.
     */
    public function handle(GamificationService $gamification): int
    {
        $userId = $this->option('user');
        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $this->info("Starting gamification recalculation for {$users->count()} users...");

        foreach ($users as $user) {
            $this->line("Processing User #{$user->id}: {$user->name}...");

            // 1. Calculate XP from watch histories (1 min = 1 XP)
            $watchHistories = WatchHistory::where('user_id', $user->id)->get();
            $totalWatchSeconds = $watchHistories->sum('progress_seconds');
            $watchXp = (int)round($totalWatchSeconds / 60);

            // 2. Reviews XP (+50 per review)
            $reviewsCount = Review::where('user_id', $user->id)->count();
            $reviewXp = $reviewsCount * 50;

            // 3. Comments XP (+15 per comment)
            $commentsCount = EpisodeComment::where('user_id', $user->id)->count();
            $commentXp = $commentsCount * 15;

            // 4. Watch Party XP (+30 per party)
            $partyCount = WatchPartyParticipant::where('user_id', $user->id)->count();
            $partyXp = $partyCount * 30;

            $calculatedTotalXp = max(0, $watchXp + $reviewXp + $commentXp + $partyXp);

            // Ensure base user has at least 1 streak day if watched anything
            $streak = ($watchHistories->count() > 0 && ($user->streak_count ?? 0) === 0) ? 1 : ($user->streak_count ?? 0);
            $lastWatch = $watchHistories->sortByDesc('updated_at')->first();

            $user->update([
                'xp_total'        => $calculatedTotalXp,
                'streak_count'    => $streak,
                'last_watch_date' => $lastWatch ? $lastWatch->updated_at->toDateString() : null,
            ]);

            // Clear old logs to prevent duplications and seed initial backfill log
            UserXpLog::where('user_id', $user->id)->delete();
            if ($calculatedTotalXp > 0) {
                UserXpLog::create([
                    'user_id'    => $user->id,
                    'amount'     => $calculatedTotalXp,
                    'source'     => 'watch_time',
                    'metadata'   => ['note' => 'Initial backfill from watch history & activity'],
                    'created_at' => now(),
                ]);
            }

            // Check & Unlock Badges
            $unlocked = $gamification->checkAndUnlockBadges($user);
            $user->refresh();

            $levelInfo = $gamification->calculateLevelInfo((int)$user->xp_total);
            $user->update(['current_level' => $levelInfo['level']]);

            $this->info(" -> XP: {$user->xp_total}, Level: {$levelInfo['level']} ({$levelInfo['title']}), Badges Unlocked: " . count($unlocked));
        }

        $this->info("Gamification recalculation completed successfully!");
        return Command::SUCCESS;
    }
}
