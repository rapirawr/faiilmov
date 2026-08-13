<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\AdminActivityLog;
use App\Services\AnichinService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncDracinJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public ?int $adminId = null,
        public array $sources = ['dramabox', 'reelshort', 'shortmax', 'goodshort', 'dramawave', 'dramanova', 'flickreels', 'freereels']
    ) {
    }

    public function handle(AnichinService $anichin): void
    {
        $addedCount = 0;
        $updatedCount = 0;
        $failedCount = 0;

        foreach ($this->sources as $source) {
            try {
                // 1. Fetch Trending
                $trending = $anichin->getTrending($source);
                foreach ($trending as $item) {
                    try {
                        $film = $anichin->syncItemToFilmModel($source, $item);
                        if ($film) {
                            if ($film->wasRecentlyCreated) {
                                $addedCount++;
                            } else {
                                $updatedCount++;
                            }
                        }
                    } catch (Exception $e) {
                        $failedCount++;
                    }
                }

                // 2. Fetch ForYou Page 1
                $forYou = $anichin->getForYou($source, 1);
                foreach ($forYou as $item) {
                    try {
                        $film = $anichin->syncItemToFilmModel($source, $item);
                        if ($film) {
                            if ($film->wasRecentlyCreated) {
                                $addedCount++;
                            } else {
                                $updatedCount++;
                            }
                        }
                    } catch (Exception $e) {
                        $failedCount++;
                    }
                }

            } catch (Exception $e) {
                Log::warning("SyncDracinJob error for source {$source}: " . $e->getMessage());
            }
        }

        $statusMsg = sprintf("Dracin API Sync Selesai! %d Ditambahkan, %d Diperbarui, %d Gagal.", $addedCount, $updatedCount, $failedCount);
        Setting::set('last_dracin_sync_at', now()->toDateTimeString());
        Setting::set('last_dracin_sync_status', $statusMsg);

        if ($this->adminId) {
            AdminActivityLog::create([
                'admin_id' => $this->adminId,
                'action' => 'sync_dracin_api',
                'description' => $statusMsg,
            ]);
        }

        Log::info($statusMsg);
    }
}
