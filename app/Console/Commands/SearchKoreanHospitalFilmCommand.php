<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MovieBoxService;
use App\Services\FilmSearchService;
use App\Models\Film;
use Exception;

class SearchKoreanHospitalFilmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'film:search-korean-hospital
                            {query=402 Rumah Sakit Angker Korea : Kata kunci pencarian film rumah sakit Korea}
                            {--sync : Paksa sinkronisasi hasil pencarian dari MovieBox ke database lokal}
                            {--limit=10 : Jumlah maksimal hasil yang ditampilkan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script khusus untuk mencari film rumah sakit Korea (seperti Gonjiam: Haunted Asylum / 402 Rumah Sakit Angker Korea) & sinkron ke DB';

    /**
     * Keyword mapping dictionary to enhance search accuracy
     */
    protected array $keywordMappings = [
        '402 rumah sakit angker korea' => ['Gonjiam: Haunted Asylum', 'Gonjiam', 'Haunted Asylum', '402', 'Rumah Sakit Korea'],
        '402 rumah sakit'               => ['Gonjiam: Haunted Asylum', '402', 'Asylum', 'Haunted'],
        'rumah sakit angker'           => ['Gonjiam: Haunted Asylum', 'Asylum', 'Haunted Hospital', 'Horror Hospital'],
        'rumah sakit korea'            => ['Gonjiam: Haunted Asylum', 'Doctor on the Edge', 'Hospital Playlist', 'Hospital', 'Korean Hospital'],
        'rumah sakit'                  => ['Hospital', 'Asylum', 'Medical', 'Doctor'],
        'angker'                       => ['Haunted', 'Asylum', 'Horror', 'Ghost'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(MovieBoxService $movieBox, FilmSearchService $filmSearch): int
    {
        $rawQuery = (string)$this->argument('query');
        $forceSync = (bool)$this->option('sync');
        $limit = max(1, (int)$this->option('limit'));

        $this->info("==========================================================================");
        $this->info("      PENCARIAN FILM RUMAH SAKIT KOREA (GONJIAM / HOSPITAL HORROR)       ");
        $this->info("==========================================================================\n");

        $this->line("🔍 Kata kunci asal: <fg=yellow>{$rawQuery}</fg=yellow>");

        // Determine extended search terms
        $lowerQuery = strtolower(trim($rawQuery));
        $searchTerms = [$rawQuery];

        foreach ($this->keywordMappings as $key => $mappings) {
            if (str_contains($lowerQuery, $key) || str_contains($key, $lowerQuery)) {
                $searchTerms = array_merge($searchTerms, $mappings);
            }
        }
        $searchTerms = array_values(array_unique($searchTerms));

        $this->line("🌐 Istilah pencarian diperluas: <fg=cyan>" . implode(', ', $searchTerms) . "</fg=cyan>\n");

        $foundFilms = collect();

        // 1. Search in local database
        $this->line("📦 <fg=blue>[Step 1]</fg=blue> Mencari di database lokal...");

        $localQuery = Film::query();
        $localQuery->where(function ($q) use ($searchTerms, $lowerQuery) {
            foreach ($searchTerms as $term) {
                $q->orWhere('title', 'LIKE', '%' . $term . '%')
                  ->orWhere('synopsis', 'LIKE', '%' . $term . '%');
            }
            // Specific tokens
            $tokens = array_filter(explode(' ', $lowerQuery), fn($t) => strlen($t) >= 3);
            foreach ($tokens as $token) {
                $q->orWhere('title', 'LIKE', '%' . $token . '%')
                  ->orWhere('synopsis', 'LIKE', '%' . $token . '%');
            }
        });

        $localResults = $localQuery->get()->map(function ($film) use ($lowerQuery) {
            $score = 0;
            $t = strtolower($film->title);
            $s = strtolower($film->synopsis ?? '');

            if (str_contains($t, 'gonjiam')) $score += 200;
            if (str_contains($t, 'haunted asylum')) $score += 180;
            if (str_contains($t, 'asylum')) $score += 150;
            if (str_contains($t, 'hospital')) $score += 100;
            if (str_contains($t, 'rumah sakit')) $score += 120;
            if (str_contains($t, '402')) $score += 150;

            if (str_contains($s, 'gonjiam')) $score += 90;
            if (str_contains($s, 'asylum')) $score += 80;
            if (str_contains($s, 'hospital')) $score += 60;
            if (str_contains($s, 'korean')) $score += 30;

            $film->relevance_score = $score;
            return $film;
        })->sortByDesc('relevance_score')->values();

        if ($localResults->isNotEmpty()) {
            $this->info("✓ Ditemukan " . $localResults->count() . " film di database lokal.");
            $foundFilms = $foundFilms->merge($localResults);
        } else {
            $this->warn("! Tidak ada hasil langsung di database lokal.");
        }

        // 2. Upstream Live API Search (MovieBox)
        if ($forceSync || $foundFilms->count() < 3) {
            $this->line("\n🚀 <fg=magenta>[Step 2]</fg=magenta> Mengambil data langsung dari API MovieBox upstream...");

            foreach ($searchTerms as $term) {
                $this->line(" -> Querying API: <fg=yellow>{$term}</fg=yellow>...");
                try {
                    $apiRes = $movieBox->search($term, 1);
                    if (!empty($apiRes)) {
                        $subjects = Film::extractSearchSubjects($apiRes);
                        if (!empty($subjects)) {
                            $this->line("    ✓ " . count($subjects) . " judul ditemukan dari API. Menyinkronkan ke database...");
                            foreach ($subjects as $subj) {
                                $subjId = (string)($subj['subjectId'] ?? $subj['id'] ?? '');
                                if (!$subjId) continue;
                                $details = $movieBox->getDetails($subjId);
                                if (!empty($details)) {
                                    $syncedFilm = Film::fromApiData($details);
                                    if ($syncedFilm) {
                                        $foundFilms->push($syncedFilm);
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->error("   Error API untuk term '{$term}': " . $e->getMessage());
                }
            }
        }

        $uniqueFilms = $foundFilms->unique('id')
            ->sortByDesc(function ($film) {
                $score = $film->relevance_score ?? 0;
                $t = strtolower($film->title);
                if (str_contains($t, 'gonjiam')) $score += 200;
                if (str_contains($t, 'haunted asylum')) $score += 180;
                if (str_contains($t, 'asylum')) $score += 150;
                if (str_contains($t, 'hospital')) $score += 100;
                return $score;
            })
            ->take($limit)
            ->values();

        if ($uniqueFilms->isEmpty()) {
            $this->error("\n❌ Tidak ada film rumah sakit Korea yang ditemukan.");
            return 1;
        }

        $this->info("\n==========================================================================");
        $this->info("                         HASIL PENCARIAN FILM                             ");
        $this->info("==========================================================================");

        $tableData = [];
        foreach ($uniqueFilms as $film) {
            $tableData[] = [
                'ID'          => $film->id,
                'Judul Film'  => $film->title,
                'Tahun'       => $film->release_year ?: 'N/A',
                'Tipe'        => strtoupper($film->subject_type),
                'Rating'      => $film->rating ? "⭐ {$film->rating}" : 'N/A',
                'URL Web'     => route('film.show', $film->slug),
            ];
        }

        $this->table(['ID', 'Judul Film', 'Tahun', 'Tipe', 'Rating', 'URL Web'], $tableData);

        // Highlight details for top result (e.g. Gonjiam / 402 Rumah Sakit Angker Korea)
        $topFilm = $uniqueFilms->first();
        $this->info("\n🎬 <fg=cyan>HIGHLIGHT FILM RUMAH SAKIT KOREA UTAMA:</fg=cyan>");
        $this->line("   📌 Judul    : <fg=green>{$topFilm->title}</fg=green>");
        $this->line("   📅 Tahun    : {$topFilm->release_year}");
        $this->line("   ⭐ Rating   : {$topFilm->rating}");
        $this->line("   🔗 Link Web : " . route('film.show', $topFilm->slug));
        $this->line("   📝 Sinopsis : " . substr($topFilm->synopsis, 0, 180) . "...\n");

        $this->info("✅ Pencarian dan sinkronisasi selesai!");
        return 0;
    }
}
