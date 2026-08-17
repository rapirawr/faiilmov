<?php

namespace App\Services;

use App\Models\ApiHealthLog;
use App\Models\ApiServiceStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemHealthService
{
    /**
     * Default known external services & hosts to track
     */
    public const KNOWN_SERVICES = [
        'moviebox' => [
            'https://api6.aoneroom.com',
            'https://api5.aoneroom.com',
            'https://api4.aoneroom.com',
            'https://api4sg.aoneroom.com',
            'https://api3.aoneroom.com',
            'https://api6sg.aoneroom.com',
            'https://api.inmoviebox.com',
        ],
        'anichin' => [
            'api.anichin.bio',
            'priv-api.anichin.bio',
        ],
        'nvidia' => [
            'integrate.api.nvidia.com',
        ],
        'itunes' => [
            'itunes.apple.com',
        ],
        'dicebear' => [
            'api.dicebear.com',
        ],
    ];

    /**
     * Log an API call result and quickly update rolling status
     */
    public function logApiCall(
        string $service,
        ?string $host,
        bool $success,
        ?int $statusCode,
        int $latencyMs,
        ?string $errorMessage = null
    ): void {
        try {
            $now = Carbon::now();

            ApiHealthLog::create([
                'service'       => $service,
                'host'          => $host,
                'status'        => $success ? 'success' : 'failed',
                'status_code'   => $statusCode,
                'latency_ms'    => max(0, $latencyMs),
                'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 1000) : null,
                'checked_at'    => $now,
            ]);

            // Real-time incremental rollup for fast status reflections
            $statusRecord = ApiServiceStatus::firstOrNew([
                'service' => $service,
                'host'    => $host,
            ]);

            if ($success) {
                $statusRecord->consecutive_failures = 0;
                $statusRecord->last_success_at = $now;
                $statusRecord->current_status = 'up';
            } else {
                $statusRecord->consecutive_failures = ($statusRecord->consecutive_failures ?? 0) + 1;
                $statusRecord->last_failure_at = $now;
                if ($statusRecord->consecutive_failures >= 3) {
                    $statusRecord->current_status = 'down';
                } else {
                    $statusRecord->current_status = 'degraded';
                }
            }

            $statusRecord->last_checked_at = $now;
            $statusRecord->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to log API health call: ' . $e->getMessage());
        }
    }

    /**
     * Recompute full 24-hour service status, uptime, latency, and degradation levels
     */
    public function recomputeStatus(): void
    {
        $since = Carbon::now()->subHours(24);

        // Fetch all distinct service/host pairs from logs & seed known list
        $loggedPairs = ApiHealthLog::where('checked_at', '>=', $since)
            ->select('service', 'host')
            ->distinct()
            ->get();

        $pairsToProcess = [];
        foreach ($loggedPairs as $item) {
            $key = $item->service . '::' . ($item->host ?? '');
            $pairsToProcess[$key] = ['service' => $item->service, 'host' => $item->host];
        }

        // Seed known services if not in logs yet
        foreach (self::KNOWN_SERVICES as $service => $hosts) {
            foreach ($hosts as $host) {
                $key = $service . '::' . $host;
                if (!isset($pairsToProcess[$key])) {
                    $pairsToProcess[$key] = ['service' => $service, 'host' => $host];
                }
            }
        }

        foreach ($pairsToProcess as $pair) {
            $service = $pair['service'];
            $host = $pair['host'];

            $query = ApiHealthLog::where('service', $service)
                ->where('checked_at', '>=', $since);

            if ($host !== null) {
                $query->where('host', $host);
            } else {
                $query->whereNull('host');
            }

            $totalCalls = (clone $query)->count();
            $successCalls = (clone $query)->where('status', 'success')->count();
            $avgLatency = (clone $query)->where('status', 'success')->avg('latency_ms');

            $uptime24h = $totalCalls > 0 ? round(($successCalls / $totalCalls) * 100, 2) : 100.00;

            // Calculate consecutive failures from latest logs
            $recentLogs = (clone $query)->orderBy('checked_at', 'desc')->limit(10)->get();
            $consecutiveFailures = 0;
            foreach ($recentLogs as $log) {
                if ($log->status === 'failed') {
                    $consecutiveFailures++;
                } else {
                    break;
                }
            }

            // Determine status
            $currentStatus = 'up';
            if ($consecutiveFailures >= 3) {
                $currentStatus = 'down';
            } elseif ($uptime24h < 95.00 || ($avgLatency !== null && $avgLatency > 2500) || $consecutiveFailures > 0) {
                $currentStatus = 'degraded';
            }

            $lastSuccess = (clone $query)->where('status', 'success')->latest('checked_at')->first();
            $lastFailure = (clone $query)->where('status', 'failed')->latest('checked_at')->first();
            $lastChecked = (clone $query)->latest('checked_at')->first();

            ApiServiceStatus::updateOrCreate(
                ['service' => $service, 'host' => $host],
                [
                    'current_status'       => $currentStatus,
                    'consecutive_failures' => $consecutiveFailures,
                    'uptime_24h'           => $uptime24h,
                    'avg_latency_ms'       => $avgLatency ? (int)round($avgLatency) : null,
                    'last_success_at'      => $lastSuccess?->checked_at,
                    'last_failure_at'      => $lastFailure?->checked_at,
                    'last_checked_at'      => $lastChecked?->checked_at ?? Carbon::now(),
                ]
            );
        }
    }

    /**
     * Get real-time queue snapshot (pending & failed jobs)
     */
    public function getQueueSnapshot(): array
    {
        $pendingCount = 0;
        $failedCount = 0;
        $recentFailed = [];

        try {
            if (DB::getSchemaBuilder()->hasTable('jobs')) {
                $pendingCount = DB::table('jobs')->count();
            }
        } catch (\Throwable $e) {
            $pendingCount = 0;
        }

        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedCount = DB::table('failed_jobs')->count();
                $recentFailedRaw = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(5)->get();

                foreach ($recentFailedRaw as $item) {
                    $payload = json_decode($item->payload ?? '{}', true);
                    $jobDisplayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown Job');

                    $recentFailed[] = [
                        'id'               => $item->id,
                        'connection'       => $item->connection,
                        'queue'            => $item->queue,
                        'job_name'         => class_basename($jobDisplayName),
                        'exception_preview'=> mb_substr($item->exception ?? '', 0, 200),
                        'failed_at'        => $item->failed_at,
                        'failed_at_human'  => Carbon::parse($item->failed_at)->diffForHumans(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            $failedCount = 0;
        }

        return [
            'pending_count' => $pendingCount,
            'failed_count'  => $failedCount,
            'recent_failed' => $recentFailed,
            'status'        => $failedCount > 0 ? ($failedCount > 10 ? 'critical' : 'warning') : 'healthy',
        ];
    }

    /**
     * Get system environment snapshot
     */
    public function getServerSnapshot(): array
    {
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);

        return [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver'       => config('database.default'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'memory_used_mb'  => round($memUsage / 1024 / 1024, 2),
            'memory_peak_mb'  => round($memPeak / 1024 / 1024, 2),
            'server_time'     => Carbon::now()->format('Y-m-d H:i:s T'),
            'timestamp'       => Carbon::now()->timestamp,
        ];
    }

    /**
     * Get all active service status rows
     */
    public function getAllServiceStatuses(): array
    {
        $records = ApiServiceStatus::orderBy('service')->orderBy('host')->get();

        if ($records->isEmpty()) {
            $this->recomputeStatus();
            $records = ApiServiceStatus::orderBy('service')->orderBy('host')->get();
        }

        $overallHealthy = true;
        $downCount = 0;
        $degradedCount = 0;

        $formatted = $records->map(function ($rec) use (&$overallHealthy, &$downCount, &$degradedCount) {
            if ($rec->current_status === 'down') {
                $overallHealthy = false;
                $downCount++;
            } elseif ($rec->current_status === 'degraded') {
                $degradedCount++;
            }

            return [
                'id'                   => $rec->id,
                'service'              => $rec->service,
                'host'                 => $rec->host,
                'host_display'         => $rec->host ? parse_url($rec->host, PHP_URL_HOST) ?? $rec->host : 'Default Host',
                'current_status'       => $rec->current_status,
                'consecutive_failures' => $rec->consecutive_failures,
                'uptime_24h'           => (float)$rec->uptime_24h,
                'avg_latency_ms'       => $rec->avg_latency_ms,
                'last_success_at'      => $rec->last_success_at?->toISOString(),
                'last_failure_at'      => $rec->last_failure_at?->toISOString(),
                'last_checked_at'      => $rec->last_checked_at?->toISOString(),
                'last_checked_human'   => $rec->last_checked_at ? $rec->last_checked_at->diffForHumans() : 'Never',
            ];
        });

        return [
            'services'       => $formatted,
            'overall_status' => $downCount > 0 ? 'down' : ($degradedCount > 0 ? 'degraded' : 'up'),
            'down_count'     => $downCount,
            'degraded_count' => $degradedCount,
            'total_monitored'=> $records->count(),
        ];
    }

    /**
     * Actively ping all known external services & hosts
     */
    public function pingAllServices(): array
    {
        $results = [];
        $totalTested = 0;
        $upCount = 0;
        $totalLatency = 0;

        // 1. MovieBox Hosts
        $mb = app(\App\Services\MovieBoxService::class);
        $mbHosts = self::KNOWN_SERVICES['moviebox'] ?? [];
        foreach ($mbHosts as $host) {
            $totalTested++;
            $url = rtrim($host, '/') . '/wefeed-mobile-bff/tab-operating?page=1&tabId=0&version=';
            $headers = $mb->getSignedHeadersForUrl($url, 'GET');
            $start = microtime(true);
            try {
                $res = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(3)
                    ->withoutVerifying()
                    ->get($url);
                $latencyMs = (int)round((microtime(true) - $start) * 1000);
                $success = $res->successful() || $res->status() === 200;
                $this->logApiCall('moviebox', $host, $success, $res->status(), $latencyMs, $success ? null : 'HTTP ' . $res->status());
                if ($success) {
                    $upCount++;
                    $totalLatency += $latencyMs;
                }
                $results[] = [
                    'service'     => 'moviebox',
                    'host'        => $host,
                    'status'      => $success ? 'up' : 'down',
                    'status_code' => $res->status(),
                    'latency_ms'  => $latencyMs,
                ];
            } catch (\Throwable $e) {
                $latencyMs = (int)round((microtime(true) - $start) * 1000);
                $this->logApiCall('moviebox', $host, false, null, $latencyMs, $e->getMessage());
                $results[] = [
                    'service'     => 'moviebox',
                    'host'        => $host,
                    'status'      => 'down',
                    'status_code' => null,
                    'latency_ms'  => $latencyMs,
                    'error'       => $e->getMessage(),
                ];
            }
        }

        // 2. Anichin / Dracin API
        $anichinHosts = self::KNOWN_SERVICES['anichin'] ?? ['api.anichin.bio'];
        foreach ($anichinHosts as $host) {
            $totalTested++;
            $url = 'https://' . ltrim($host, 'https://') . '/api/search?source=dramabox&keyword=test';
            $start = microtime(true);
            try {
                $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-API-Key' => config('services.anichin.api_key', 'ANICHIN-285757D6C7247E91356ACD175840B15D'),
                ])->timeout(3)->withoutVerifying()->get($url);
                $latencyMs = (int)round((microtime(true) - $start) * 1000);
                $success = $res->successful() || $res->status() === 200;
                $this->logApiCall('anichin', $host, $success, $res->status(), $latencyMs, $success ? null : 'HTTP ' . $res->status());
                if ($success) {
                    $upCount++;
                    $totalLatency += $latencyMs;
                }
                $results[] = [
                    'service'     => 'anichin',
                    'host'        => $host,
                    'status'      => $success ? 'up' : 'down',
                    'status_code' => $res->status(),
                    'latency_ms'  => $latencyMs,
                ];
            } catch (\Throwable $e) {
                $latencyMs = (int)round((microtime(true) - $start) * 1000);
                $this->logApiCall('anichin', $host, false, null, $latencyMs, $e->getMessage());
                $results[] = [
                    'service'     => 'anichin',
                    'host'        => $host,
                    'status'      => 'down',
                    'status_code' => null,
                    'latency_ms'  => $latencyMs,
                    'error'       => $e->getMessage(),
                ];
            }
        }

        // 3. Dicebear Avatars
        $totalTested++;
        $start = microtime(true);
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)->withoutVerifying()->get('https://api.dicebear.com/9.x/bottts/svg?seed=ping');
            $latencyMs = (int)round((microtime(true) - $start) * 1000);
            $success = $res->successful() || $res->status() === 200;
            $this->logApiCall('dicebear', 'api.dicebear.com', $success, $res->status(), $latencyMs, $success ? null : 'HTTP ' . $res->status());
            if ($success) {
                $upCount++;
                $totalLatency += $latencyMs;
            }
            $results[] = [
                'service'     => 'dicebear',
                'host'        => 'api.dicebear.com',
                'status'      => $success ? 'up' : 'down',
                'status_code' => $res->status(),
                'latency_ms'  => $latencyMs,
            ];
        } catch (\Throwable $e) {
            $latencyMs = (int)round((microtime(true) - $start) * 1000);
            $this->logApiCall('dicebear', 'api.dicebear.com', false, null, $latencyMs, $e->getMessage());
            $results[] = [
                'service'     => 'dicebear',
                'host'        => 'api.dicebear.com',
                'status'      => 'down',
                'status_code' => null,
                'latency_ms'  => $latencyMs,
                'error'       => $e->getMessage(),
            ];
        }

        // 4. Apple iTunes Music API
        $totalTested++;
        $start = microtime(true);
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)->withoutVerifying()->get('https://itunes.apple.com/search?term=avatar&media=music&limit=1');
            $latencyMs = (int)round((microtime(true) - $start) * 1000);
            $success = $res->successful() || $res->status() === 200;
            $this->logApiCall('itunes', 'itunes.apple.com', $success, $res->status(), $latencyMs, $success ? null : 'HTTP ' . $res->status());
            if ($success) {
                $upCount++;
                $totalLatency += $latencyMs;
            }
            $results[] = [
                'service'     => 'itunes',
                'host'        => 'itunes.apple.com',
                'status'      => $success ? 'up' : 'down',
                'status_code' => $res->status(),
                'latency_ms'  => $latencyMs,
            ];
        } catch (\Throwable $e) {
            $latencyMs = (int)round((microtime(true) - $start) * 1000);
            $this->logApiCall('itunes', 'itunes.apple.com', false, null, $latencyMs, $e->getMessage());
            $results[] = [
                'service'     => 'itunes',
                'host'        => 'itunes.apple.com',
                'status'      => 'down',
                'status_code' => null,
                'latency_ms'  => $latencyMs,
                'error'       => $e->getMessage(),
            ];
        }

        // 5. NVIDIA AI Inference
        $totalTested++;
        $start = microtime(true);
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)
                ->withoutVerifying()
                ->withToken(config('services.nvidia.api_key', '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'))
                ->get('https://integrate.api.nvidia.com/v1/models');
            $latencyMs = (int)round((microtime(true) - $start) * 1000);
            $success = $res->successful() || $res->status() === 200;
            $this->logApiCall('nvidia', 'integrate.api.nvidia.com', $success, $res->status(), $latencyMs, $success ? null : 'HTTP ' . $res->status());
            if ($success) {
                $upCount++;
                $totalLatency += $latencyMs;
            }
            $results[] = [
                'service'     => 'nvidia',
                'host'        => 'integrate.api.nvidia.com',
                'status'      => $success ? 'up' : 'down',
                'status_code' => $res->status(),
                'latency_ms'  => $latencyMs,
            ];
        } catch (\Throwable $e) {
            $latencyMs = (int)round((microtime(true) - $start) * 1000);
            $this->logApiCall('nvidia', 'integrate.api.nvidia.com', false, null, $latencyMs, $e->getMessage());
            $results[] = [
                'service'     => 'nvidia',
                'host'        => 'integrate.api.nvidia.com',
                'status'      => 'down',
                'status_code' => null,
                'latency_ms'  => $latencyMs,
                'error'       => $e->getMessage(),
            ];
        }

        // Recompute rollup statuses
        $this->recomputeStatus();

        return [
            'total_tested'   => $totalTested,
            'up_count'       => $upCount,
            'down_count'     => $totalTested - $upCount,
            'avg_latency_ms' => $upCount > 0 ? (int)round($totalLatency / $upCount) : 0,
            'results'        => $results,
            'timestamp'      => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Ping a single specific service or host
     */
    public function pingSingleService(string $service, ?string $host = null): array
    {
        $start = microtime(true);
        $success = false;
        $statusCode = null;
        $error = null;

        try {
            if ($service === 'moviebox') {
                $mb = app(\App\Services\MovieBoxService::class);
                $targetHost = $host ?: 'https://api6.aoneroom.com';
                $url = rtrim($targetHost, '/') . '/wefeed-mobile-bff/tab-operating?page=1&tabId=0&version=';
                $headers = $mb->getSignedHeadersForUrl($url, 'GET');
                $res = \Illuminate\Support\Facades\Http::withHeaders($headers)->timeout(3)->withoutVerifying()->get($url);
                $statusCode = $res->status();
                $success = $res->successful() || $res->status() === 200;
                if (!$success) $error = 'HTTP ' . $res->status();
            } elseif ($service === 'anichin') {
                $targetHost = $host ?: 'api.anichin.bio';
                $url = 'https://' . ltrim($targetHost, 'https://') . '/api/search?source=dramabox&keyword=test';
                $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-API-Key' => config('services.anichin.api_key', 'ANICHIN-285757D6C7247E91356ACD175840B15D'),
                ])->timeout(3)->withoutVerifying()->get($url);
                $statusCode = $res->status();
                $success = $res->successful() || $res->status() === 200;
                if (!$success) $error = 'HTTP ' . $res->status();
            } elseif ($service === 'dicebear') {
                $res = \Illuminate\Support\Facades\Http::timeout(3)->withoutVerifying()->get('https://api.dicebear.com/9.x/bottts/svg?seed=ping');
                $statusCode = $res->status();
                $success = $res->successful() || $res->status() === 200;
                if (!$success) $error = 'HTTP ' . $res->status();
            } elseif ($service === 'itunes') {
                $res = \Illuminate\Support\Facades\Http::timeout(3)->withoutVerifying()->get('https://itunes.apple.com/search?term=avatar&media=music&limit=1');
                $statusCode = $res->status();
                $success = $res->successful() || $res->status() === 200;
                if (!$success) $error = 'HTTP ' . $res->status();
            } elseif ($service === 'nvidia') {
                $res = \Illuminate\Support\Facades\Http::timeout(3)
                    ->withoutVerifying()
                    ->withToken(config('services.nvidia.api_key', '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'))
                    ->get('https://integrate.api.nvidia.com/v1/models');
                $statusCode = $res->status();
                $success = $res->successful() || $res->status() === 200;
                if (!$success) $error = 'HTTP ' . $res->status();
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $success = false;
        }

        $latencyMs = (int)round((microtime(true) - $start) * 1000);
        $this->logApiCall($service, $host, $success, $statusCode, $latencyMs, $error);
        $this->recomputeStatus();

        return [
            'service'     => $service,
            'host'        => $host,
            'status'      => $success ? 'up' : 'down',
            'status_code' => $statusCode,
            'latency_ms'  => $latencyMs,
            'error'       => $error,
            'timestamp'   => Carbon::now()->toISOString(),
        ];
    }
}
