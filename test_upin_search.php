<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$upinFilms = App\Models\Film::where('title', 'LIKE', '%Upin%')->get();

$query = "upin ipin";
$cleanQ = preg_replace('/[+\-><\(\)~*"@]+/', ' ', $query);

// Strip 'and', 'dan', '&', '-', spaces, punctuation for super-tolerant matching
$normalizedQ = str_replace(['and', 'dan', '&', '-', ' ', ':', '.', "'", '"', '!', '?'], '', strtolower($cleanQ));

echo "Query: '$query' -> Normalized Query: '$normalizedQ'\n\n";

$sqlNormalizeTitle = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, 'and', ''), 'dan', ''), '&', ''), '-', ''), ' ', ''), ':', ''), '.', ''), '!', ''), '?', ''))";

$matches = App\Models\Film::where(function ($sub) use ($cleanQ, $normalizedQ, $sqlNormalizeTitle) {
    $sub->where('title', 'LIKE', '%' . $cleanQ . '%')
        ->orWhereRaw("{$sqlNormalizeTitle} LIKE ?", ['%' . $normalizedQ . '%']);
})->get();

echo "Matches count: " . $matches->count() . "\n";
foreach ($matches as $m) {
    echo " - ID: {$m->id} | Title: '{$m->title}' | Type: {$m->subject_type}\n";
}
