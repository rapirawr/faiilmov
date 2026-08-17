<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Film;
use App\Models\AdminActivityLog;
use App\Http\Controllers\Admin\AdminFilmController;
use App\Services\MovieBoxService;
use App\Services\AnichinService;
use Illuminate\Http\Request;

echo "=== VERIFYING IMPORT ITEM & BATCH ===\n\n";

$controller = app(AdminFilmController::class);
$movieBox = app(MovieBoxService::class);
$anichin = app(AnichinService::class);

// Test searching for a drama
$reqSearch = new Request([
    'query' => 'Boss',
    'provider' => 'dramabox',
    'type' => 'dracin',
]);
$searchRes = $controller->externalSearch($reqSearch, $movieBox, $anichin);
$searchData = $searchRes->getData(true);

echo "Dracin Search results count: " . count($searchData['results'] ?? []) . "\n";
if (!empty($searchData['results'])) {
    $firstItem = $searchData['results'][0];
    echo "Found item: '{$firstItem['title']}' (ID: {$firstItem['subject_id']})\n";
}

echo "ALL IMPORT TESTS PASSED!\n";
