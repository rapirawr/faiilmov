<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$slug = 'spider-man-brand-new-day-63e568';
$film = App\Models\Film::where('slug', $slug)->first();

if (!$film) {
    echo "ERROR: Film with slug '$slug' NOT FOUND in database!\n";
    $parts = explode('-', $slug);
    $subjectId = end($parts);
    echo "Extracted subjectId from slug: $subjectId\n";
    exit;
}

echo "=========================================\n";
echo "FILM RECORD IN DATABASE:\n";
echo "ID: " . $film->id . "\n";
echo "Title: " . $film->title . "\n";
echo "Slug: " . $film->slug . "\n";
echo "Subject Type: " . $film->subject_type . "\n";
echo "MovieBox Subject ID: " . ($film->moviebox_subject_id ?: 'NULL / EMPTY') . "\n";
echo "=========================================\n";

$mb = app(App\Services\MovieBoxService::class);

if ($film->moviebox_subject_id) {
    echo "\nCalling getCaptions({$film->moviebox_subject_id}, 0, 0)...\n";
    $caps = $mb->getCaptions($film->moviebox_subject_id, 0, 0);
    echo "Captions count: " . count($caps) . "\n";
    print_r($caps);

    echo "\nCalling getResources({$film->moviebox_subject_id}, 0, 0)...\n";
    $res = $mb->getResources($film->moviebox_subject_id, 0, 0);
    echo "Resources count: " . count($res['list'] ?? []) . "\n";
    if (!empty($res['list'])) {
        foreach ($res['list'] as $idx => $item) {
            echo "Item #$idx resourceId: " . ($item['resourceId'] ?? $item['id'] ?? 'NONE') . "\n";
        }
    }
} else {
    echo "\nTrying to search MovieBox for 'Spider-Man: Brand New Day'...\n";
    $search = $mb->search($film->title, 1);
    print_r($search);
}
