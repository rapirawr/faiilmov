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

    public int $timeout = 600;

    /**
     * Create a new job instance.
     * @param int|null $adminId Admin ID triggering the sync
     * @param bool $resetCheckpoint If true, restart sync from Tab 0 Page 1
     * @param int $maxPagesPerRun Number of pages to process in this job run (default 10)
     */
    public function __construct(
        public ?int $adminId = null,
        public bool $resetCheckpoint = false,
        public int $maxPagesPerRun = 10
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(MovieBoxService $movieBox): void
    {
        $movieBox->init();

        $tabs = ['0', '1', '2', '3']; // 0: Featured, 1: Movies, 2: Series, 3: Animation
        
        if ($this->resetCheckpoint) {
            $tabIdx = 0;
            $startPage = 1;
        } else {
            $tabIdx = (int)Setting::get('sync_checkpoint_tab_idx', 0);
            $startPage = (int)Setting::get('sync_checkpoint_page', 1);
        }

        $addedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $skippedDetails = [];
        $pagesProcessed = 0;

        try {
            while ($tabIdx < count($tabs) && $pagesProcessed < $this->maxPagesPerRun) {
                $currentTab = $tabs[$tabIdx];
                $currentPage = $startPage;

                while ($pagesProcessed < $this->maxPagesPerRun) {
                    // Fetch homepage feed for tab and page with retry logic
                    $feed = $this->fetchWithRetry(fn() => $movieBox->getHomepage($currentTab, $currentPage), 3);

                    if (empty($feed)) {
                        $skippedCount++;
                        $skippedDetails[] = "Tab {$currentTab} Page {$currentPage}: Empty/Failed feed response after 3 retries.";
                        break; // Move to next tab
                    }

                    $subjects = Film::extractHomepageSubjects($feed);

                    if (empty($subjects)) {
                        // Empty page means no more items in this tab, move to next tab
                        $startPage = 1;
                        $tabIdx++;
                        break;
                    }

                    foreach ($subjects as $subject) {
                        $subjectId = (string)($subject['subjectId'] ?? $subject['id'] ?? '');
                        $title = trim($subject['title'] ?? '');

                        if (empty($title) || Film::isExcludedTitle($title)) {
                            $skippedCount++;
                            $skippedDetails[] = "Subject ID {$subjectId}: Skipped excluded/empty title '{$title}'.";
                            continue;
                        }

                        if (empty($subjectId)) {
                            $skippedCount++;
                            $skippedDetails[] = "Title '{$title}': Skipped due to missing subjectId.";
                            continue;
                        }

                        try {
                            $existing = Film::where('moviebox_subject_id', $subjectId)->first();

                            // Get full detail with retry mechanism
                            $apiDetail = $this->fetchWithRetry(fn() => $movieBox->getDetails($subjectId), 3);
                            $detailPayload = (!empty($apiDetail) && is_array($apiDetail)) ? $apiDetail : $subject;

                            $film = Film::fromApiData($detailPayload);

                            if ($existing) {
                                $updatedCount++;
                            } else {
                                $addedCount++;
                            }
                        } catch (Exception $e) {
                            $skippedCount++;
                            $skippedDetails[] = "Subject ID {$subjectId} ('{$title}'): Exception " . $e->getMessage();
                            Log::warning("SyncFilmsJob item error: " . $e->getMessage());
                        }
                    }

                    $pagesProcessed++;
                    $currentPage++;

                    // Save checkpoint after each page
                    Setting::set('sync_checkpoint_tab_idx', $tabIdx);
                    Setting::set('sync_checkpoint_page', $currentPage);
                }

                if ($pagesProcessed >= $this->maxPagesPerRun) {
                    break;
                }
            }

            // Reset checkpoint if we cycled through all tabs
            if ($tabIdx >= count($tabs)) {
                Setting::set('sync_checkpoint_tab_idx', 0);
                Setting::set('sync_checkpoint_page', 1);
                $statusSummary = sprintf("Full Sync Selesai! %d Ditambahkan, %d Diperbarui, %d Diskip.", $addedCount, $updatedCount, $skippedCount);
            } else {
                $statusSummary = sprintf("Checkpoint Sync (Tab %d, Hal %d): %d Ditambahkan, %d Diperbarui, %d Diskip.", $tabIdx, $startPage, $addedCount, $updatedCount, $skippedCount);
            }

            Setting::set('last_api_sync_at', now()->toDateTimeString());
            Setting::set('last_api_sync_status', $statusSummary);
            Setting::set('last_api_sync_details', json_encode([
                'added' => $addedCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'pages_processed' => $pagesProcessed,
                'current_tab' => $tabs[$tabIdx] ?? '0',
                'current_page' => $startPage,
                'recent_skips' => array_slice($skippedDetails, 0, 10),
            ]));

            if ($this->adminId) {
                AdminActivityLog::create([
                    'admin_id' => $this->adminId,
                    'action' => 'sync_api_films',
                    'description' => $statusSummary,
                ]);
            }

            Log::info($statusSummary);

        } catch (Exception $e) {
            $errorMsg = "Sync API Error: " . $e->getMessage();
            Setting::set('last_api_sync_status', $errorMsg);
            Log::error($errorMsg);
        }
    }

    /**
     * Execute HTTP request callback with retry backoff mechanism
     */
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
                Log::debug("Sync retry attempt " . ($attempts + 1) . " failed: " . $e->getMessage());
            }
            $attempts++;
            usleep(300000); // 300ms backoff
        }
        return null;
    }
}
