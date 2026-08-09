<?php

/**
 * Script Standalone untuk Membil dan Menyinkronkan Seluruh Data Aktor / Cast dari MovieBox API
 * 
 * Penggunaan via Terminal:
 *   php sync_all_actors.php [--purge]
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Film;
use App\Models\Actor;
use App\Models\Setting;
use App\Services\MovieBoxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$purgeDummy = in_array('--purge', $argv, true);

echo "\n=======================================================\n";
echo "    FA I I L M O V  -  SYNC ALL ACTORS / CAST SCRIPT\n";
echo "=======================================================\n\n";

if ($purgeDummy) {
    echo "⚠️  [PURGE] Menghapus data aktor & relasi lama...\n";
    DB::table('film_actor')->truncate();
    Actor::query()->delete();
    echo "✅  Data aktor lama berhasil dibersihkan.\n\n";
}

$movieBox = app(MovieBoxService::class);
$movieBox->init();

$films = Film::whereNotNull('moviebox_subject_id')->get();
$totalFilms = $films->count();

echo "📦 Ditemukan {$totalFilms} film dengan MovieBox Subject ID di basis data.\n";
echo "🚀 Memulai pengambilan data aktor dari MovieBox API...\n\n";

$syncedActorsCount = 0;
$totalRelationsCount = 0;
$processedCount = 0;
$failedCount = 0;

foreach ($films as $index => $film) {
    $num = $index + 1;
    echo sprintf("[%d/%d] %s (ID: %s)... ", $num, $totalFilms, Str::limit($film->title, 35), $film->moviebox_subject_id);

    try {
        $details = $movieBox->getDetails($film->moviebox_subject_id);

        if (empty($details) || !is_array($details)) {
            echo "⚠️ SKIP (Detail API kosong)\n";
            $failedCount++;
            continue;
        }

        $actorsFound = [];
        $staffList = $details['staffList'] ?? $details['starList'] ?? $details['actors'] ?? $details['actorList'] ?? [];

        if (is_array($staffList) && count($staffList) > 0) {
            foreach ($staffList as $staff) {
                $name = trim($staff['name'] ?? '');
                if (empty($name)) continue;

                $type = (int)($staff['staffType'] ?? 1);
                $character = trim($staff['character'] ?? '');

                // Filter non-actor staff (Directors, Writers, Creators)
                if ($type !== 1 && in_array(strtolower($character), ['director', 'writer', 'producer', 'screenplay', 'creator'])) {
                    continue;
                }

                $avatarUrl = $staff['avatarUrl'] ?? $staff['avatar'] ?? $staff['photo'] ?? null;
                if (empty($avatarUrl) || str_contains($avatarUrl, 'unsplash.com')) {
                    $avatarUrl = null;
                }

                $slug = Str::slug($name);
                if (empty($slug)) {
                    $slug = 'actor-' . substr(md5($name), 0, 6);
                }

                $actor = Actor::where('name', $name)->first();
                if (!$actor) {
                    $baseSlug = $slug;
                    $count = 1;
                    while (Actor::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $count++;
                    }

                    $actor = Actor::create([
                        'name' => $name,
                        'slug' => $slug,
                        'photo_url' => $avatarUrl,
                    ]);
                    $syncedActorsCount++;
                } else {
                    if ($avatarUrl && empty($actor->getRawOriginal('photo_url'))) {
                        $actor->update(['photo_url' => $avatarUrl]);
                    }
                }

                $actorsFound[$actor->id] = ['character_name' => $character ?: null];
            }
        }

        if (!empty($actorsFound)) {
            $film->actors()->syncWithoutDetaching($actorsFound);
            $totalRelationsCount += count($actorsFound);
            echo "✅ OK (" . count($actorsFound) . " pemeran)\n";
        } else {
            echo "⚪ Tanpa data pemeran\n";
        }

        $processedCount++;
    } catch (Exception $e) {
        $failedCount++;
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

$summaryMsg = sprintf(
    "Sinkronisasi Aktor selesai! %d film diproses (%d gagal), %d aktor baru ditambahkan, %d relasi terhubung.",
    $processedCount, $failedCount, $syncedActorsCount, $totalRelationsCount
);

Setting::set('last_actor_api_sync_at', now()->toDateTimeString());
Setting::set('last_actor_api_sync_status', $summaryMsg);

echo "\n=======================================================\n";
echo "🎉 SINKRONISASI AKTOR SELESAI!\n";
echo "   - Total Film Diproses        : {$processedCount} / {$totalFilms}\n";
echo "   - Total Aktor Ditambahkan   : {$syncedActorsCount}\n";
echo "   - Total Relasi Disinkronkan  : {$totalRelationsCount}\n";
echo "   - Gagal / Skip              : {$failedCount}\n";
echo "=======================================================\n\n";
