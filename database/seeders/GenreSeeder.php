<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            ['name' => 'Action', 'slug' => 'action'],
            ['name' => 'Drama', 'slug' => 'drama'],
            ['name' => 'Comedy', 'slug' => 'comedy'],
            ['name' => 'Horror', 'slug' => 'horror'],
            ['name' => 'Sci-Fi', 'slug' => 'sci-fi'],
            ['name' => 'Romance', 'slug' => 'romance'],
            ['name' => 'Thriller', 'slug' => 'thriller'],
            ['name' => 'Animation', 'slug' => 'animation'],
            ['name' => 'Documentary', 'slug' => 'documentary'],
            ['name' => 'Crime', 'slug' => 'crime'],
            ['name' => 'Mystery', 'slug' => 'mystery'],
            ['name' => 'Adventure', 'slug' => 'adventure'],
            ['name' => 'Family', 'slug' => 'family'],
            ['name' => 'Fantasy', 'slug' => 'fantasy'],
            ['name' => 'War', 'slug' => 'war'],
            ['name' => 'Musical', 'slug' => 'musical'],
            ['name' => 'Biography', 'slug' => 'biography'],
            ['name' => 'Sport', 'slug' => 'sport'],
            ['name' => 'History', 'slug' => 'history'],
            ['name' => 'Western', 'slug' => 'western'],
        ];

        foreach ($genres as $genre) {
            Genre::firstOrCreate(
                ['slug' => $genre['slug']],
                ['name' => $genre['name']]
            );
        }

        $this->command->info('Created ' . count($genres) . ' genres.');
    }
}
