<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Film;
use App\Models\Actor;
use App\Services\MovieBoxService;

class VerifySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'films:verify-sync {--sample=The Boys,Spider-Man,Avatar,Inception,Batman}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cross-check local DB film integrity against external API feed sample and report missing titles.';

    /**
     * Execute the console command.
     */
    public function handle(MovieBoxService $movieBox): int
    {
        $this->info("=======================================================");
        $this->info("   VERIFIKASI INTEGRITAS SINKRONISASI FILM & AKTOR    ");
        $this->info("=======================================================\n");

        $localFilmCount = Film::count();
        $localActorCount = Actor::count();
        $localWithSubjectId = Film::whereNotNull('moviebox_subject_id')->count();

        $this->line("<fg=cyan>Status Database Lokal:</fg=cyan>");
        $this->line("  • Total Film: <fg=green>{$localFilmCount}</fg=green>");
        $this->line("  • Film dengan External Subject ID: <fg=green>{$localWithSubjectId}</fg=green>");
        $this->line("  • Total Aktor: <fg=green>{$localActorCount}</fg=green>\n");

        $sampleInput = $this->option('sample');
        $sampleTitles = array_map('trim', explode(',', $sampleInput));

        $this->info("Menguji ketersediaan sampel kata kunci pada API vs Database Lokal:");
        $tableData = [];
        $missingCount = 0;

        foreach ($sampleTitles as $title) {
            if (empty($title)) continue;

            $localMatches = Film::where('title', 'LIKE', "%{$title}%")->get();
            $localFound = $localMatches->count();

            // Query API sample
            $apiFound = 0;
            try {
                $apiData = $movieBox->search($title, 1);
                $extracted = Film::extractSearchSubjects($apiData);
                $apiFound = count($extracted);
            } catch (\Exception $e) {
                $apiFound = -1; // Error fetching
            }

            $status = ($localFound >= 1) ? '✅ PASS' : '❌ MISSING (0 di DB)';
            if ($localFound == 0) {
                $missingCount++;
            }

            $tableData[] = [
                'Kata Kunci' => $title,
                'Found in Local DB' => "{$localFound} film",
                'Found in Upstream API' => ($apiFound >= 0) ? "{$apiFound} items" : 'Error Fetch',
                'Status Integritas' => $status,
            ];
        }

        $this->table(['Kata Kunci', 'Local DB Match', 'Upstream API Match', 'Status Integritas'], $tableData);

        if ($missingCount > 0) {
            $this->warn("\n⚠️ WARNING: Ditemukan {$missingCount} sampel kata kunci yang ada di API tetapi BELUM ke-sync di DB lokal.");
            $this->line("Jalankan <fg=yellow>php artisan films:sync</fg=yellow> atau trigger sync dari Admin Dashboard untuk melengkapi data.");
            return 1;
        }

        $this->info("\n✅ VERIFIKASI SUKSES: Seluruh sampel data film terkonfirmasi sinkron & lengkap di database!");
        return 0;
    }
}
