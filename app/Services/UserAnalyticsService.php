<?php

namespace App\Services;

use App\Models\User;
use App\Models\WatchParty;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserAnalyticsService
{
    /**
     * Daily Active Users (DAU): Distinct users with last_active_at >= today
     */
    public function getDAU(): int
    {
        return (int)User::activeToday()->count();
    }

    /**
     * Signups today
     */
    public function getSignupsToday(): int
    {
        return (int)User::where('created_at', '>=', Carbon::today()->startOfDay())->count();
    }

    /**
     * Currently active watch parties (nobar)
     */
    public function getActiveWatchParties(): int
    {
        return (int)WatchParty::where('status', 'active')->count();
    }

    /**
     * Total registered users
     */
    public function getTotalUsers(): int
    {
        return (int)User::count();
    }

    /**
     * Signups sparkline trend for the past N days
     */
    public function getSignupTrend(int $days = 7): array
    {
        $trend = [];
        $start = Carbon::today()->subDays($days - 1);

        for ($i = 0; $i < $days; $i++) {
            $current = $start->copy()->addDays($i);
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $signups = User::whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $activeUsers = User::whereBetween('last_active_at', [$dayStart, $dayEnd])->count();

            $trend[] = [
                'date'         => $current->toDateString(),
                'label'        => $current->format('d M'),
                'short_day'    => $current->format('D'),
                'signups'      => (int)$signups,
                'active_users' => (int)$activeUsers,
            ];
        }

        return $trend;
    }
}
