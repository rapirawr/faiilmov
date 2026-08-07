<?php

use App\Models\Genre;
use App\Models\Film;

// Check available genres
echo "=== AVAILABLE GENRES ===\n";
$genres = Genre::withCount('films')->get();
foreach ($genres as $genre) {
    echo "{$genre->name} (slug: {$genre->slug}) - {$genre->films_count} films\n";
}

echo "\n=== TESTING AI INTERPRETATION ===\n";
$nvidia = app(\App\Services\NvidiaAiService::class);

$testQueries = [
    'horor',
    'horror',
    'film horor',
];

foreach ($testQueries as $query) {
    echo "\nQuery: '{$query}'\n";
    $result = $nvidia->interpretQuery($query);
    if ($result) {
        echo "AI Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "AI returned null (API key not configured or error)\n";
    }
}

echo "\n=== FILM COUNTS ===\n";
echo "Total films: " . Film::count() . "\n";
echo "Films with embeddings: " . Film::whereNotNull('ai_embeddings')->count() . "\n";
