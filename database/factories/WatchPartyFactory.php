<?php

namespace Database\Factories;

use App\Models\WatchParty;
use App\Models\Film;
use Illuminate\Database\Eloquent\Factories\Factory;

class WatchPartyFactory extends Factory
{
    protected $model = WatchParty::class;

    public function definition(): array
    {
        return [
            'film_id' => Film::factory(),
            'room_code' => strtoupper($this->faker->unique()->bothify('??##??##')),
            'host_user_id' => null,
            'host_guest_name' => $this->faker->name(),
            'status' => 'waiting',
            'season_number' => 1,
            'episode_number' => 1,
            'current_position_seconds' => 0,
            'is_playing' => false,
            'is_locked' => false,
            'playback_speed' => 1.0,
        ];
    }
}
