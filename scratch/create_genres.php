<?php

use App\Models\Genre;
use App\Models\Film;

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
    ['name' => 'Horror', 'slug' => 'horror'],
];

$existing = Genre::count();
if ($existing > 0) {
    echo "Genres already exist ({$existing}). Skipping.\n";
    exit;
}

echo "Creating genres...\n";
foreach ($genres as $g) {
    Genre::firstOrCreate(
        ['slug' => $g['slug']],
        ['name' => $g['name']]
    );
}

echo "Done! Created " . Genre::count() . " genres.\n";

// Check sample films
echo "\nSample films (with genres):\n";
Film::with('genres')->limit(5)->get()->each(function($f) {
    echo "- {$f->title} ({$f->release_year}) - Genres: " . $f->genres->pluck('name')->join(', ') . "\n";
});
