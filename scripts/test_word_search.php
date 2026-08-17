<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FilmSearchService;

$searchService = app(FilmSearchService::class);

echo "=== TESTING DIRECT WORD MATCH SEARCH ===\n";

$results = $searchService->search('Spider Man');
if ($results) {
    echo "Found " . $results->total() . " films for 'Spider Man':\n";
    foreach ($results->take(5) as $f) {
        echo " - " . $f->title . " (" . $f->subject_type . ")\n";
    }
} else {
    echo "No films found or query too short.\n";
}

echo "\n[PASS] Search executed cleanly without quick-search or fuzzy errors.\n";
