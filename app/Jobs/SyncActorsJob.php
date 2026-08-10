<?php

namespace App\Jobs;

use App\Models\Film;
use App\Models\Actor;
use App\Models\Setting;
use App\Models\AdminActivityLog;
use App\Services\MovieBoxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class SyncActorsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $adminId = null,
        public bool $purgeDummy = false
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(MovieBoxService $movieBox): void
    {
        $movieBox->init();

        if ($this->purgeDummy) {
            try {
                DB::table('film_actor')->truncate();
                Actor::query()->delete();
                Log::info("SyncActorsJob: Data actor & relasi film_actor berhasil dikosongkan.");
            } catch (Exception $e) {
                Log::warning("SyncActorsJob truncate warning: " . $e->getMessage());
            }
        }

        $films = Film::whereNotNull('moviebox_subject_id')->get();
        $syncedActorsCount = 0;
        $processedFilms = 0;
        $failedCount = 0;
        $skippedDetails = [];

        foreach ($films as $film) {
            try {
                $details = $this->fetchWithRetry(fn() => $movieBox->getDetails($film->moviebox_subject_id), 3);

                if (empty($details) || !is_array($details)) {
                    $failedCount++;
                    $skippedDetails[] = "Film '{$film->title}' [ID: {$film->moviebox_subject_id}]: Details empty after retries.";
                    continue;
                }

                $actorsFound = [];
                $staffList = $details['staffList'] ?? $details['starList'] ?? $details['actors'] ?? $details['actorList'] ?? [];

                if (is_array($staffList)) {
                    $actorIndex = 0;
                    foreach ($staffList as $staff) {
                        $name = trim($staff['name'] ?? '');
                        if (empty($name)) continue;

                        $type = (int)($staff['staffType'] ?? 1);
                        $character = trim($staff['character'] ?? '');

                        if ($type !== 1 && in_array(strtolower($character), ['director', 'writer', 'producer', 'screenplay', 'creator'])) {
                            continue;
                        }

                        $avatarUrl = $staff['avatarUrl'] ?? $staff['avatar'] ?? $staff['photo'] ?? null;
                        if (empty($avatarUrl)) {
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
                            if ($avatarUrl && str_contains((string)$actor->photo_url, 'unsplash.com') && !str_contains($avatarUrl, 'unsplash.com')) {
                                $actor->update(['photo_url' => $avatarUrl]);
                            }
                        }

                        $roleType = ($actorIndex < 2) ? 'main' : 'regular';
                        $actorsFound[$actor->id] = [
                            'character_name' => $character ?: null,
                            'role_type' => $roleType,
                        ];
                        $actorIndex++;
                    }
                }

                if (!empty($actorsFound)) {
                    $film->actors()->syncWithoutDetaching($actorsFound);
                }

                $processedFilms++;
            } catch (Exception $e) {
                $failedCount++;
                $skippedDetails[] = "Film '{$film->title}': " . $e->getMessage();
                Log::warning("SyncActorsJob failed for film {$film->title}: " . $e->getMessage());
            }
        }

        $logMsg = sprintf(
            "Sinkronisasi Aktor selesai! Total %d aktor ditambahkan/diperbarui dari %d film (%d gagal/skip).",
            $syncedActorsCount, $processedFilms, $failedCount
        );

        Setting::set('last_actor_api_sync_at', now()->toDateTimeString());
        Setting::set('last_actor_api_sync_status', $logMsg);
        Setting::set('last_actor_api_sync_details', json_encode([
            'synced_actors' => $syncedActorsCount,
            'processed_films' => $processedFilms,
            'failed_films' => $failedCount,
            'recent_skips' => array_slice($skippedDetails, 0, 10),
        ]));

        if ($this->adminId) {
            AdminActivityLog::create([
                'admin_id' => $this->adminId,
                'action' => 'sync_api_actors',
                'description' => $logMsg,
            ]);
        }

        Log::info($logMsg);
    }

    private function fetchWithRetry(callable $callback, int $maxRetries = 3): mixed
    {
        $attempts = 0;
        while ($attempts < $maxRetries) {
            try {
                $result = $callback();
                if ($result !== null && $result !== false) {
                    return $result;
                }
            } catch (Exception $e) {
                Log::debug("SyncActorsJob retry attempt " . ($attempts + 1) . " failed: " . $e->getMessage());
            }
            $attempts++;
            usleep(300000);
        }
        return null;
    }
}
