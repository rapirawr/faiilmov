<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Film;
use Illuminate\Support\Facades\DB;

class ClassifyFilmActors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'films:classify-actors {--limit=2 : Jumlah maksimal pemeran utama per film (default 2)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis mengklasifikasikan pemeran film menjadi Pemeran Utama (main) dan Pemeran Pendukung (regular)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        if ($limit < 1) {
            $limit = 2;
        }

        $films = Film::with('actors')->get();
        $this->info("Memproses " . $films->count() . " film untuk klasifikasi pemeran (Limit Pemeran Utama: {$limit})...");

        $updatedFilmsCount = 0;
        $mainActorsTotal = 0;

        foreach ($films as $film) {
            if ($film->actors->isEmpty()) {
                continue;
            }

            $actorIds = $film->actors->pluck('id')->toArray();

            foreach ($actorIds as $index => $actorId) {
                $roleType = ($index < $limit) ? 'main' : 'regular';

                DB::table('film_actor')
                    ->where('film_id', $film->id)
                    ->where('actor_id', $actorId)
                    ->update(['role_type' => $roleType]);

                if ($roleType === 'main') {
                    $mainActorsTotal++;
                }
            }

            $updatedFilmsCount++;
        }

        $this->info("✨ Selesai! Berhasil mengklasifikasikan pemeran untuk {$updatedFilmsCount} film.");
        $this->info("⭐ Total {$mainActorsTotal} aktor ditetapkan sebagai Pemeran Utama (main).");

        return Command::SUCCESS;
    }
}
