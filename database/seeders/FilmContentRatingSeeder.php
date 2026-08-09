<?php

namespace Database\Seeders;

use App\Models\Film;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FilmContentRatingSeeder extends Seeder
{
    public function run(): void
    {
        $adultKeywords = ['lady', 'ladies', 'dormitory', 'sexy', 'hot', 'desire', 'passion', 'erotic', 'lust', 'vivamax', 'secret', 'nude', 'temptation', 'affair', 'night', 'sensual', 'virgin', 'bed'];

        Film::with('genres')->get()->each(function ($film) use ($adultKeywords) {
            $genreNames = $film->genres->pluck('name')->map(fn($n) => strtolower($n))->toArray();
            $titleLower = strtolower($film->title);
            $synopsisLower = strtolower($film->synopsis ?? '');

            $hasAdultKeyword = Str::contains($titleLower, $adultKeywords) || Str::contains($synopsisLower, $adultKeywords);

            if ($hasAdultKeyword || in_array('horror', $genreNames) || in_array('thriller', $genreNames) || in_array('crime', $genreNames) || in_array('erotic', $genreNames)) {
                $rating = '18+';
            } elseif (in_array('action', $genreNames) || in_array('mystery', $genreNames) || in_array('sci-fi', $genreNames) || in_array('romance', $genreNames)) {
                $rating = '16+';
            } elseif (in_array('animation', $genreNames) || in_array('family', $genreNames) || in_array('kids', $genreNames) || in_array('comedy', $genreNames)) {
                $rating = 'SU';
            } else {
                $rating = '13+';
            }

            $film->update(['content_rating' => $rating]);
        });
    }
}
