<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$actors = ['Leonardo DiCaprio', 'Pedro Pascal', 'Robert Downey Jr.', 'Elliot Page', 'Joseph Gordon-Levitt'];

foreach ($actors as $name) {
    echo "=== Searching photo for '$name' ===\n";

    // 1. Test IMDb Suggestion API
    $clean = preg_replace('/[^\w\s]/u', '', trim($name));
    $prefix = strtolower(substr($clean, 0, 1));
    $query = urlencode(strtolower(str_replace(' ', '_', $clean)));
    $url = "https://v3.sg.media-imdb.com/suggestion/{$prefix}/{$query}.json";

    try {
        $res = Http::timeout(4)->get($url);
        if ($res->successful() && !empty($res->json()['d'])) {
            foreach ($res->json()['d'] as $item) {
                // Check if it's a person/actor
                if (!empty($item['id']) && str_starts_with($item['id'], 'nm') && !empty($item['i']['imageUrl'])) {
                    echo "Found in IMDb Suggestion: " . $item['i']['imageUrl'] . "\n";
                    break;
                }
            }
        }
    } catch (\Exception $e) {
        echo "IMDb SG Error: " . $e->getMessage() . "\n";
    }

    // 2. Test Wikipedia API
    $wikiUrl = "https://en.wikipedia.org/w/api.php?action=query&titles=" . urlencode($name) . "&prop=pageimages&format=json&pithumbsize=300";
    try {
        $wikiRes = Http::timeout(4)->get($wikiUrl);
        if ($wikiRes->successful()) {
            $pages = $wikiRes->json()['query']['pages'] ?? [];
            foreach ($pages as $p) {
                if (!empty($p['thumbnail']['source'])) {
                    echo "Found in Wikipedia: " . $p['thumbnail']['source'] . "\n";
                    break;
                }
            }
        }
    } catch (\Exception $e) {
        echo "Wiki Error: " . $e->getMessage() . "\n";
    }
}
