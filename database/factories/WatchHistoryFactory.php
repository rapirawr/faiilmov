<?php

namespace Database\Factories;

use App\Models\WatchHistory;
use App\Models\User;
use App\Models\Film;
use Illuminate\Database\Eloquent\Factories\Factory;

class WatchHistoryFactory extends Factory
{
    protected $model = WatchHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'film_id' => Film::factory(),
            'progress_seconds' => $this->faker->numberBetween(60, 7200),
            'last_position' => $this->faker->numberBetween(60, 7200),
            'completed' => false,
        ];
    }
}
