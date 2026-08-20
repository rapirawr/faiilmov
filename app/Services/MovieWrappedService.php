<?php

namespace App\Services;

use App\Models\User;
use App\Models\WatchHistory;
use App\Models\Film;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MovieWrappedService
{
    protected GamificationService $gamification;

    public function __construct(GamificationService $gamification)
    {
        $this->gamification = $gamification;
    }

    /**
     * Generate complete Movie Wrapped statistics for a given user & period
     */
    public function generateWrappedData(User $user, string $period = 'year', ?int $year = null, ?int $month = null): array
    {
        $year = $year ?: (int)date('Y');
        $month = $month ?: (int)date('n');

        $query = WatchHistory::where('user_id', $user->id)
            ->with(['film.genres', 'film.actors']);

        if ($period === 'year') {
            $query->whereYear('updated_at', $year);
            $periodLabel = "Tahun {$year}";
        } elseif ($period === 'month') {
            $query->whereYear('updated_at', $year)->whereMonth('updated_at', $month);
            $monthName = Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM YYYY');
            $periodLabel = "Bulan {$monthName}";
        } else {
            $periodLabel = 'Sepanjang Masa';
        }

        $histories = $query->get();

        // 1. Total watch time & counts
        $totalSeconds = $histories->sum('progress_seconds');
        $totalMinutes = (int)round($totalSeconds / 60);
        $totalHours = round($totalMinutes / 60, 1);
        $totalTitles = $histories->pluck('film_id')->unique()->count();
        $totalEpisodes = $histories->count();

        // 2. Top 5 Most Watched Films/Series
        $topFilmsGrouped = $histories->groupBy('film_id')->map(function ($items) {
            $film = $items->first()->film;
            if (!$film) return null;

            $watchSecs = $items->sum('progress_seconds');
            return [
                'id'         => $film->id,
                'title'      => $film->title,
                'slug'       => $film->slug,
                'poster_url' => $film->poster_url,
                'type'       => $film->subject_type_label ?? 'Film',
                'minutes'    => (int)round($watchSecs / 60),
                'genres'     => $film->genres->pluck('name')->take(2)->toArray(),
            ];
        })->filter()->sortByDesc('minutes')->values()->take(5);

        // 3. Top Genres Breakdown
        $genreCounts = [];
        foreach ($histories as $h) {
            if ($h->film && $h->film->genres) {
                foreach ($h->film->genres as $genre) {
                    $name = $genre->name;
                    $genreCounts[$name] = ($genreCounts[$name] ?? 0) + 1;
                }
            }
        }
        arsort($genreCounts);
        $totalGenreTally = array_sum($genreCounts) ?: 1;

        $topGenres = [];
        $rank = 1;
        foreach (array_slice($genreCounts, 0, 5, true) as $genreName => $count) {
            $topGenres[] = [
                'rank'       => $rank++,
                'name'       => $genreName,
                'count'      => $count,
                'percentage' => round(($count / $totalGenreTally) * 100),
            ];
        }

        // 4. Top Actors
        $actorCounts = [];
        foreach ($histories as $h) {
            if ($h->film && $h->film->actors) {
                foreach ($h->film->actors as $actor) {
                    $actorCounts[$actor->name] = ($actorCounts[$actor->name] ?? 0) + 1;
                }
            }
        }
        arsort($actorCounts);
        $topActors = array_slice(array_keys($actorCounts), 0, 3);

        // 5. Watch Time Habits Breakdown (Hour of day)
        $timeHabits = [
            'midnight'  => 0, // 00:00 - 04:59
            'morning'   => 0, // 05:00 - 11:59
            'afternoon' => 0, // 12:00 - 17:59
            'evening'   => 0, // 18:00 - 23:59
        ];

        foreach ($histories as $h) {
            $hour = (int)$h->updated_at->format('H');
            if ($hour >= 0 && $hour < 5) {
                $timeHabits['midnight']++;
            } elseif ($hour >= 5 && $hour < 12) {
                $timeHabits['morning']++;
            } elseif ($hour >= 12 && $hour < 18) {
                $timeHabits['afternoon']++;
            } else {
                $timeHabits['evening']++;
            }
        }
        arsort($timeHabits);
        $dominantTimeSlot = array_key_first($timeHabits);

        $timeHabitLabels = [
            'midnight'  => ['title' => 'Midnight Cinephile', 'desc' => 'Paling produktif nonton saat sunyinya larut malam (00:00 - 05:00)', 'icon' => 'moon'],
            'morning'   => ['title' => 'Morning Explorer', 'desc' => 'Menikmati film sebagai penyemangat pagi hari', 'icon' => 'sun'],
            'afternoon' => ['title' => 'Daytime Binger', 'desc' => 'Gemar menghabiskan waktu siang dengan tontonan seru', 'icon' => 'coffee'],
            'evening'   => ['title' => 'Prime Time Watcher', 'desc' => 'Menutup hari yang panjang dengan sesi santai nonton malam', 'icon' => 'tv'],
        ];

        // 6. Determine Cinephile Persona / Archetype
        $archetype = $this->determineArchetype($topGenres, $dominantTimeSlot, $totalHours);

        // 7. Gamification info
        $levelInfo = $this->gamification->calculateLevelInfo((int)$user->xp_total);
        $badgesCount = DB::table('user_badges')->where('user_id', $user->id)->count();

        return [
            'period'             => $period,
            'period_label'       => $periodLabel,
            'user'               => [
                'name'         => $user->name,
                'avatar'       => $user->avatar ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar)) : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($user->name),
                'total_xp'     => (int)$user->xp_total,
                'level'        => $levelInfo['level'],
                'tier_title'   => $levelInfo['title'],
                'tier_icon'    => $levelInfo['icon'],
                'tier_color'   => $levelInfo['color'],
                'bg_class'     => $levelInfo['bg_class'],
                'streak_count' => (int)$user->streak_count,
                'badges_count' => $badgesCount,
            ],
            'stats'              => [
                'total_minutes'   => $totalMinutes,
                'total_hours'     => $totalHours,
                'total_titles'    => $totalTitles,
                'total_episodes'  => $totalEpisodes,
            ],
            'top_films'          => $topFilmsGrouped,
            'top_genres'         => $topGenres,
            'top_actors'         => $topActors,
            'habit'              => $timeHabitLabels[$dominantTimeSlot] ?? $timeHabitLabels['evening'],
            'archetype'          => $archetype,
        ];
    }

    /**
     * Determine Cinephile Archetype based on genres and habits (Icons only, no emojis)
     */
    protected function determineArchetype(array $topGenres, string $dominantTimeSlot, float $totalHours): array
    {
        $topGenreName = strtolower($topGenres[0]['name'] ?? '');

        if (str_contains($topGenreName, 'horror') || str_contains($topGenreName, 'thriller')) {
            return [
                'title'       => 'The Midnight Thrillseeker',
                'tagline'     => 'Pemberani yang selalu mencari sensasi detak jantung tercepat.',
                'icon'        => 'skull',
                'badge_color' => 'rose',
                'gradient'    => 'from-rose-600 via-purple-700 to-zinc-950',
            ];
        }

        if (str_contains($topGenreName, 'anime') || str_contains($topGenreName, 'animation')) {
            return [
                'title'       => 'The Otaku Virtuoso',
                'tagline'     => 'Penjelajah dunia fantasi dan animasi dengan imajinasi tanpa batas.',
                'icon'        => 'sparkles',
                'badge_color' => 'purple',
                'gradient'    => 'from-purple-600 via-indigo-700 to-zinc-950',
            ];
        }

        if (str_contains($topGenreName, 'drama') || str_contains($topGenreName, 'romance')) {
            return [
                'title'       => 'The Emotional Visionary',
                'tagline'     => 'Penikmat alur cerita mendalam yang selalu tenggelam dalam perasaan karakter.',
                'icon'        => 'heart',
                'badge_color' => 'pink',
                'gradient'    => 'from-pink-600 via-rose-700 to-zinc-950',
            ];
        }

        if (str_contains($topGenreName, 'sci-fi') || str_contains($topGenreName, 'science')) {
            return [
                'title'       => 'The Sci-Fi Voyager',
                'tagline'     => 'Pemikir masa depan yang terpikat oleh misteri kosmos dan teknologi.',
                'icon'        => 'rocket',
                'badge_color' => 'cyan',
                'gradient'    => 'from-cyan-600 via-blue-700 to-zinc-950',
            ];
        }

        if (str_contains($topGenreName, 'action') || str_contains($topGenreName, 'adventure')) {
            return [
                'title'       => 'The Adrenaline Hunter',
                'tagline'     => 'Pencari ledakan aksi spektakuler dan petualangan tanpa henti.',
                'icon'        => 'flame',
                'badge_color' => 'amber',
                'gradient'    => 'from-amber-600 via-orange-700 to-zinc-950',
            ];
        }

        if ($totalHours > 50) {
            return [
                'title'       => 'The Grand Marathoner',
                'tagline'     => 'Kolektor jam tayang sejati yang mampu menghabiskan puluhan episode dalam sekejap.',
                'icon'        => 'trophy',
                'badge_color' => 'yellow',
                'gradient'    => 'from-amber-500 via-yellow-600 to-zinc-950',
            ];
        }

        return [
            'title'       => 'The Eclectic Cinephile',
            'tagline'     => 'Penjelajah sinema sejati dengan selera yang kaya dan terbuka pada segala genre.',
            'icon'        => 'clapperboard',
            'badge_color' => 'indigo',
            'gradient'    => 'from-indigo-600 via-violet-700 to-zinc-950',
        ];
    }
}
