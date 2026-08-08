<?php

namespace Database\Factories;

use App\Models\Film;
use Illuminate\Database\Eloquent\Factories\Factory;

class FilmFactory extends Factory
{
    protected $model = Film::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(3);
        return [
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1, 999999),
            'synopsis' => $this->faker->paragraph(),
            'poster_url' => $this->faker->imageUrl(),
            'backdrop_url' => $this->faker->imageUrl(1920, 1080),
            'release_year' => $this->faker->numberBetween(2000, 2025),
            'rating' => $this->faker->randomFloat(1, 1, 10),
            'subject_type' => $this->faker->randomElement(['movie', 'series']),
            'duration_minutes' => $this->faker->numberBetween(60, 180),
            'view_count' => $this->faker->numberBetween(0, 10000),
            'moviebox_subject_id' => 'mb_' . $this->faker->unique()->numberBetween(100000, 999999),
        ];
    }
}
