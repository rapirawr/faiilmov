<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Film;
use App\Models\Actor;
use App\Services\MovieBoxService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class SyncActorsCommand extends Command
{
    protected $signature = 'films:sync-actors {--purge-dummy : Hapus seluruh data dummy aktor sebelum sync API}';
    protected $description = 'Hapus data dummy aktor dan ambil data asli dari MovieBox API';

    public function handle(MovieBoxService $movieBox): int
    {
        $this->info("Memulai proses sinkronisasi data Aktor dari MovieBox API...");
        $movieBox->init();

        if ($this->option('purge-dummy')) {
            $this->warn("Menghapus seluruh relasi dan data dummy aktor lama...");
            DB::table('film_actor')->truncate();
            Actor::query()->delete();
            $this->info("Tempat data aktor berhasil dikosongkan.");
        }

        $films = Film::whereNotNull('moviebox_subject_id')->get();
        $this->info("Ditemukan {$films->count()} film dengan MovieBox Subject ID.");

        $syncedActorsCount = 0;
        $processedFilms = 0;

        foreach ($films as $film) {
            $this->output->write("Processing: {$film->title} [ID: {$film->moviebox_subject_id}] ... ");
            
            try {
                $details = $movieBox->getDetails($film->moviebox_subject_id);

                if (empty($details) || !is_array($details)) {
                    $this->line("<fg=yellow>SKIP (API detail empty)</>");
                    continue;
                }

                $actorsFound = [];
                $staffList = $details['staffList'] ?? $details['starList'] ?? $details['actors'] ?? $details['actorList'] ?? [];

                if (is_array($staffList)) {
                    foreach ($staffList as $staff) {
                        $name = trim($staff['name'] ?? '');
                        if (empty($name)) continue;

                        $type = (int)($staff['staffType'] ?? 1);
                        $character = trim($staff['character'] ?? '');

                        // Filter type 1 (Actor/Cast) or if character is not 'Director'/'Writer'
                        if ($type !== 1 && in_array(strtolower($character), ['director', 'writer', 'producer', 'screenplay', 'creator'])) {
                            continue;
                        }

                        $avatarUrl = $staff['avatarUrl'] ?? $staff['avatar'] ?? $staff['photo'] ?? null;
                        if (empty($avatarUrl)) {
                            $avatarUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=300';
                        }

                        $slug = Str::slug($name);
                        if (empty($slug)) {
                            $slug = 'actor-' . substr(md5($name), 0, 6);
                        }

                        // Get or create Actor
                        $actor = Actor::where('name', $name)->first();
                        if (!$actor) {
                            // Ensure unique slug
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
                            if ($avatarUrl && str_contains($actor->photo_url, 'unsplash.com') && !str_contains($avatarUrl, 'unsplash.com')) {
                                $actor->update(['photo_url' => $avatarUrl]);
                            }
                        }

                        $actorsFound[$actor->id] = ['character_name' => $character ?: null];
                    }
                }

                if (!empty($actorsFound)) {
                    $film->actors()->syncWithoutDetaching($actorsFound);
                    $this->line("<fg=green>OK (" . count($actorsFound) . " pemeran)</>");
                } else {
                    $this->line("<fg=gray>Tanpa data cast</>");
                }

                $processedFilms++;
            } catch (Exception $e) {
                $this->line("<fg=red>ERROR: " . $e->getMessage() . "</>");
            }
        }

        $logMsg = "Sinkronisasi Aktor selesai! Total {$syncedActorsCount} aktor ditambahkan dari {$processedFilms} film.";
        \App\Models\Setting::set('last_api_sync_at', now()->toDateTimeString());
        \App\Models\Setting::set('last_api_sync_status', $logMsg);

        $this->info($logMsg);
        return Command::SUCCESS;
    }
}
