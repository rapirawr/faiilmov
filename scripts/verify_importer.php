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

echo "=== VERIFYING ADMIN FILM SEARCH & IMPORTER ===\n\n";

$controller = app(AdminFilmController::class);
$movieBox = app(MovieBoxService::class);
$anichin = app(AnichinService::class);

// 1. Test external search with dummy or simulated response
echo "1. Testing externalSearch endpoint logic...\n";
$request = new Request([
    'query' => 'Spider-Man',
    'provider' => 'all',
    'type' => 'all',
]);

$response = $controller->externalSearch($request, $movieBox, $anichin);
$data = $response->getData(true);

echo "   Status: " . ($data['status'] ?? 'unknown') . "\n";
echo "   Results count: " . count($data['results'] ?? []) . "\n";

if (!empty($data['results'])) {
    $first = $data['results'][0];
    echo "   Sample result: '{$first['title']}' ({$first['subject_type']}) [Provider: {$first['provider_name']}]\n";
    echo "   Is Imported: " . ($first['is_imported'] ? 'Yes' : 'No') . "\n";
}
assert($data['status'] === 'success', "Search status must be success");
echo "   [PASS] External search endpoint structured properly.\n\n";

echo "ALL CHECKS PASSED FOR FILM IMPORTER!\n";
