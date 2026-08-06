<?php

namespace App\Jobs;

use App\Models\Film;
use App\Models\Setting;
use App\Models\AdminActivityLog;
use App\Services\MovieBoxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncFilmsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(public ?int $adminId = null)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(MovieBoxService $movieBox): void
    {
        $movieBox->init();

        $addedCount = 0;
        $updatedCount = 0;
        $failedCount = 0;

        try {
            $homeData = $movieBox->getHomepage('0', 1);
            $subjectList = [];

            if (isset($homeData['operatingList']) && is_array($homeData['operatingList'])) {
                foreach ($homeData['operatingList'] as $op) {
                    if (isset($op['subjects']) && is_array($op['subjects'])) {
                        foreach ($op['subjects'] as $sub) {
                            if (!empty($sub['subjectId'])) {
                                $subjectList[] = (string) $sub['subjectId'];
                            }
                        }
                    }
                }
            }

            $subjectList = array_unique($subjectList);

            foreach (array_slice($subjectList, 0, 15) as $subjectId) {
                try {
                    $existing = Film::where('moviebox_subject_id', $subjectId)->first();
                    $apiDetail = $movieBox->getDetails($subjectId);
                    
                    if (empty($apiDetail) || !is_array($apiDetail)) {
                        $failedCount++;
                        continue;
                    }

                    $film = Film::fromApiData($apiDetail);

                    if ($existing) {
                        $updatedCount++;
                    } else {
                        $addedCount++;
                    }
                } catch (Exception $e) {
                    $failedCount++;
                    Log::warning("SyncFilmsJob failed for subjectId {$subjectId}: " . $e->getMessage());
                }
            }

            $logMsg = sprintf(
                "Sync API selesai: %d film baru ditambahkan, %d film diperbarui, %d gagal/skip.",
                $addedCount, $updatedCount, $failedCount
            );

            Setting::set('last_api_sync_at', now()->toDateTimeString());
            Setting::set('last_api_sync_status', $logMsg);

            if ($this->adminId) {
                AdminActivityLog::create([
                    'admin_id' => $this->adminId,
                    'action' => 'sync_api_films',
                    'description' => $logMsg,
                ]);
            }

            Log::info($logMsg);
        } catch (Exception $e) {
            $errorMsg = "Sync API Error: " . $e->getMessage();
            Setting::set('last_api_sync_status', $errorMsg);
            Log::error($errorMsg);
        }
    }
}
