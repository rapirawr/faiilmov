<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnichinService;
use App\Models\Film;
use App\Models\Genre;
use App\Models\Setting;
use Exception;

class SyncDracinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dracin:sync 
                            {--source= : Provider sumber spesifik (misal: dramabox, reelshort, shortmax, goodshort, dramawave, dramanova, starshort, melolo)}
                            {--pages=1 : Jumlah halaman ForYou / Latest yang akan ditarik}
                            {--search= : Cari kata kunci judul tertentu dan simpan ke database}
                            {--all-sources : Sinkronisasi dari seluruh 16 provider Dracin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ambil dan sinkronkan data Dracin (Chinese Short Drama) dari API ke database lokal';

    /**
     * Execute the console command.
     */
    public function handle(AnichinService $anichin): int
    {
        $this->info("=======================================================");
        $this->info("      SCRAPER / SINKRONISASI DATA DRACIN (ANICHIN)     ");
        $this->info("=======================================================\n");

        $searchQuery = $this->option('search');
        $pages = max(1, (int)$this->option('pages'));
        $specifiedSource = $this->option('source');
        $allSourcesFlag = $this->option('all-sources');

        $availableSources = AnichinService::SOURCES;

        // Mode 1: Search by query
        if (!empty($searchQuery)) {
            $sourcesToSearch = $specifiedSource ? [$specifiedSource] : ['dramabox', 'reelshort', 'shortmax', 'goodshort'];
            $this->info("🔍 Mencari Dracin dengan kata kunci: '{$searchQuery}' pada sumber: " . implode(', ', $sourcesToSearch));
            
            $totalSaved = 0;
            $itemsFound = [];

            foreach ($sourcesToSearch as $src) {
                $this->line(" - Mencari di <fg=cyan>{$src}</fg=cyan>...");
                try {
                    $results = $anichin->search($searchQuery, $src);
                    $this->line("   Ditemukan: " . count($results) . " judul.");
                    foreach ($results as $item) {
                        $film = $anichin->syncItemToFilmModel($src, $item);
                        if ($film) {
                            $totalSaved++;
                            $itemsFound[] = [
                                'Source' => strtoupper($src),
                                'Title' => $film->title,
                                'Episodes' => $film->seasons()->first()?->episodes()->count() ?? 1,
                                'Action' => $film->wasRecentlyCreated ? '✨ Added' : '🔄 Updated'
                            ];
                        }
                    }
                } catch (Exception $e) {
                    $this->error("   Error pada {$src}: " . $e->getMessage());
                }
            }

            if (!empty($itemsFound)) {
                $this->table(['Source', 'Title', 'Total Ep', 'Status'], $itemsFound);
            }

            $this->info("\n✅ Selesai! Berhasil menyimpan/memperbarui {$totalSaved} Dracin dari pencarian.");
            return 0;
        }

        // Mode 2: Bulk Sync from Providers
        if ($allSourcesFlag) {
            $targetSources = array_keys($availableSources);
        } elseif ($specifiedSource) {
            $targetSources = array_map('trim', explode(',', strtolower($specifiedSource)));
        } else {
            // Default top popular providers
            $targetSources = ['dramabox', 'reelshort', 'shortmax', 'goodshort', 'dramawave', 'dramanova', 'flickreels', 'freereels'];
        }

        $this->line("<fg=yellow>Sumber yang akan diproses (" . count($targetSources) . " provider):</fg=yellow>");
        foreach ($targetSources as $src) {
            $providerName = $availableSources[$src] ?? ucfirst($src);
            $this->line(" • <fg=cyan>{$src}</fg=cyan> ({$providerName})");
        }
        $this->line("");

        $totalNew = 0;
        $totalUpdated = 0;
        $totalErrors = 0;
        $savedTitles = [];

        foreach ($targetSources as $source) {
            $providerName = $availableSources[$source] ?? ucfirst($source);
            $this->info("⏳ Memproses Provider: [{$providerName}] ({$source})");

            $itemsToProcess = [];

            // 1. Trending
            try {
                $trending = $anichin->getTrending($source);
                if (is_array($trending)) {
                    foreach ($trending as $t) {
                        $itemsToProcess[] = $t;
                    }
                }
            } catch (Exception $e) {
                $this->warn("   ⚠️ Gagal mengambil trending untuk {$source}: " . $e->getMessage());
            }

            // 2. Recommended / HotRank
            try {
                $hot = $anichin->getHotRank($source);
                if (is_array($hot)) {
                    foreach ($hot as $h) {
                        $itemsToProcess[] = $h;
                    }
                }
            } catch (Exception $e) {}

            // 3. For You (Paginated)
            for ($p = 1; $p <= $pages; $p++) {
                try {
                    $forYou = $anichin->getForYou($source, $p);
                    if (is_array($forYou)) {
                        foreach ($forYou as $fy) {
                            $itemsToProcess[] = $fy;
                        }
                    }
                } catch (Exception $e) {
                    $this->warn("   ⚠️ Gagal mengambil ForYou page {$p} untuk {$source}: " . $e->getMessage());
                }
            }

            // Filter unique by ID
            $uniqueMap = [];
            foreach ($itemsToProcess as $raw) {
                $rawId = (string)($raw['id'] ?? $raw['dramaId'] ?? '');
                if ($rawId && !isset($uniqueMap[$rawId])) {
                    $uniqueMap[$rawId] = $raw;
                }
            }

            $uniqueCount = count($uniqueMap);
            $this->line("   -> Mengambil {$uniqueCount} judul unik dari {$source}...");

            if ($uniqueCount > 0) {
                $bar = $this->output->createProgressBar($uniqueCount);
                $bar->start();

                foreach ($uniqueMap as $rawItem) {
                    try {
                        $film = $anichin->syncItemToFilmModel($source, $rawItem);
                        if ($film) {
                            if ($film->wasRecentlyCreated) {
                                $totalNew++;
                                $status = 'Baru';
                            } else {
                                $totalUpdated++;
                                $status = 'Update';
                            }

                            if (count($savedTitles) < 20) {
                                $savedTitles[] = [
                                    'Provider' => $providerName,
                                    'Title' => $film->title,
                                    'Status' => $status,
                                    'Episodes' => $film->seasons()->first()?->episodes()->count() ?? 1,
                                ];
                            }
                        }
                    } catch (Exception $e) {
                        $totalErrors++;
                    }
                    $bar->advance();
                }
                $bar->finish();
                $this->line("");
            }
        }

        // Summary Table
        $this->info("\n=======================================================");
        $this->info("                 RINGKASAN HASIL SYNC                  ");
        $this->info("=======================================================");
        $this->line("  • Dracin Baru Ditambahkan : <fg=green>{$totalNew}</fg=green>");
        $this->line("  • Dracin Diperbarui       : <fg=cyan>{$totalUpdated}</fg=cyan>");
        $this->line("  • Gagal / Dilewati        : <fg=red>{$totalErrors}</fg=red>");
        $this->line("  • Total Dracin di Database: <fg=yellow>" . Film::where('subject_type', 'dracin')->count() . "</fg=yellow>");

        if (!empty($savedTitles)) {
            $this->line("\n<fg=gray>Sampel judul yang berhasil diproses:</fg=gray>");
            $this->table(['Provider', 'Judul Dracin', 'Status', 'Total Ep'], $savedTitles);
        }

        Setting::set('last_dracin_sync_at', now()->toDateTimeString());
        Setting::set('last_dracin_sync_status', "Dracin Sync Selesai: {$totalNew} Baru, {$totalUpdated} Update, {$totalErrors} Gagal.");

        $this->info("\n✅ SINKRONISASI DATA DRACIN BERHASIL!");
        return 0;
    }
}
