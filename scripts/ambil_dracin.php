<?php

/**
 * Script Pengambilan / Sinkronisasi Data Dracin (Chinese Short Drama)
 * Cara menjalankan:
 *   php scripts/ambil_dracin.php
 *   php scripts/ambil_dracin.php --source=dramabox --pages=2
 *   php scripts/ambil_dracin.php --search="CEO"
 *   php scripts/ambil_dracin.php --all
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$anichin = app(App\Services\AnichinService::class);

// Parse CLI options
$options = getopt('', ['source:', 'pages:', 'search:', 'all']);

$search = $options['search'] ?? null;
$source = $options['source'] ?? null;
$pages = isset($options['pages']) ? (int)$options['pages'] : 1;
$allSources = isset($options['all']);

echo "=======================================================\n";
echo "            FAIILMOV - AMBIL DATA DRACIN               \n";
echo "=======================================================\n\n";

if ($search) {
    echo "🔍 Mencari Dracin dengan keyword: '{$search}'...\n";
    $sources = $source ? [$source] : ['dramabox', 'reelshort', 'shortmax', 'goodshort'];
    $saved = 0;
    foreach ($sources as $s) {
        echo " - Query ke {$s}...\n";
        $results = $anichin->search($search, $s);
        echo "   Ditemukan: " . count($results) . " judul.\n";
        foreach ($results as $item) {
            $film = $anichin->syncItemToFilmModel($s, $item);
            if ($film) {
                $saved++;
                echo "   -> [OK] {$film->title} (Ep: " . ($film->seasons()->first()?->episodes()->count() ?? 1) . ")\n";
            }
        }
    }
    echo "\n✅ Selesai! Berhasil menyimpan {$saved} dracin ke database.\n";
    exit(0);
}

$targetSources = $allSources 
    ? array_keys(App\Services\AnichinService::SOURCES)
    : ($source ? explode(',', $source) : ['dramabox', 'reelshort', 'shortmax', 'goodshort', 'dramawave']);

echo "Memproses sumber: " . implode(', ', $targetSources) . " (Pages: {$pages})\n\n";

$totalNew = 0;
$totalUpdated = 0;

foreach ($targetSources as $src) {
    $src = trim($src);
    echo "----------------------------------------\n";
    echo "⏳ Mengambil data dari: {$src}...\n";
    
    $items = [];
    
    // Trending
    try {
        $tr = $anichin->getTrending($src);
        if (is_array($tr)) {
            foreach ($tr as $i) $items[] = $i;
        }
    } catch (Exception $e) {}

    // For You
    for ($p = 1; $p <= $pages; $p++) {
        try {
            $fy = $anichin->getForYou($src, $p);
            if (is_array($fy)) {
                foreach ($fy as $i) $items[] = $i;
            }
        } catch (Exception $e) {}
    }

    $unique = [];
    foreach ($items as $raw) {
        $id = (string)($raw['id'] ?? $raw['dramaId'] ?? '');
        if ($id && !isset($unique[$id])) {
            $unique[$id] = $raw;
        }
    }

    echo "   Total judul unik: " . count($unique) . "\n";
    foreach ($unique as $rawItem) {
        try {
            $film = $anichin->syncItemToFilmModel($src, $rawItem);
            if ($film) {
                if ($film->wasRecentlyCreated) {
                    $totalNew++;
                    echo "   [BARU]   {$film->title}\n";
                } else {
                    $totalUpdated++;
                    echo "   [UPDATE] {$film->title}\n";
                }
            }
        } catch (Exception $e) {}
    }
}

echo "\n=======================================================\n";
echo "Selesai! Dracin Baru: {$totalNew} | Diperbarui: {$totalUpdated}\n";
echo "Total Dracin di Database Sekarang: " . App\Models\Film::where('subject_type', 'dracin')->count() . "\n";
echo "=======================================================\n";
