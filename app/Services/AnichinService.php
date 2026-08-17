<?php

namespace App\Services;

use App\Models\Film;
use App\Models\Season;
use App\Models\Episode;
use App\Models\Genre;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class AnichinService
{
    protected string $apiUrl;
    protected string $privApiUrl;
    protected string $apiKey;
    protected string $privApiKey;

    public const SOURCES = [
        'dramabox'   => 'DramaBox',
        'reelshort'  => 'ReelShort',
        'shortmax'   => 'ShortMax',
        'netshort'   => 'NetShort',
        'goodshort'  => 'GoodShort',
        'dramawave'  => 'DramaWave',
        'flickreels' => 'FlickReels',
        'freereels'  => 'FreeReels',
        'stardusttv' => 'StardustTV',
        'idrama'     => 'iDrama',
        'dramanova'  => 'DramaNova',
        'starshort'  => 'StarShort',
        'dramabite'  => 'DramaBite',
        'melolo'     => 'Melolo',
        'moboreels'  => 'MoboReels',
        'flareflow'  => 'FlareFlow',
    ];

    public function __construct()
    {
        $this->apiUrl     = config('services.anichin.api_url', 'https://api.anichin.bio');
        $this->privApiUrl = config('services.anichin.priv_api_url', 'https://priv-api.anichin.bio');
        $this->apiKey     = config('services.anichin.api_key', 'ANICHIN-285757D6C7247E91356ACD175840B15D');
        $this->privApiKey = config('services.anichin.priv_api_key', 'dk_live_d6350c820e0098a55f8d1e88c7c255c5');
    }

    /**
     * Call Anichin public API (api.anichin.bio)
     */
    protected function request(string $endpoint, array $queryParams = [], bool $usePriv = false): mixed
    {
        $baseUrl = $usePriv ? $this->privApiUrl : $this->apiUrl;
        $key = $usePriv ? $this->privApiKey : $this->apiKey;
        $host = parse_url($baseUrl, PHP_URL_HOST) ?? 'api.anichin.bio';

        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $startMicro = microtime(true);

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $key,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'application/json',
            ])->withoutVerifying()->timeout(6)->get($url, $queryParams);

            $latencyMs = (int)round((microtime(true) - $startMicro) * 1000);

            if ($response->successful()) {
                app(SystemHealthService::class)->logApiCall('anichin', $host, true, $response->status(), $latencyMs, null);
                return $response->json();
            }

            app(SystemHealthService::class)->logApiCall('anichin', $host, false, $response->status(), $latencyMs, 'HTTP ' . $response->status());
            Log::warning("AnichinService HTTP {$response->status()} for {$url}");
            return null;
        } catch (\Exception $e) {
            $latencyMs = (int)round((microtime(true) - $startMicro) * 1000);
            app(SystemHealthService::class)->logApiCall('anichin', $host, false, null, $latencyMs, $e->getMessage());
            Log::warning("AnichinService Exception on {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Trending dramas from a specific source
     */
    public function getTrending(string $source = 'dramabox'): array
    {
        $cacheKey = "anichin_trending_{$source}";
        return Cache::remember($cacheKey, 1800, function () use ($source) {
            $res = $this->request("/{$source}/trending");
            return $res['items'] ?? (is_array($res) ? $res : []);
        });
    }

    /**
     * Get For You (Paginated) dramas
     */
    public function getForYou(string $source = 'dramabox', int $page = 1): array
    {
        $cacheKey = "anichin_foryou_{$source}_{$page}";
        return Cache::remember($cacheKey, 1800, function () use ($source, $page) {
            $res = $this->request("/{$source}/foryou", ['page' => $page]);
            return $res['items'] ?? $res['list'] ?? (is_array($res) ? $res : []);
        });
    }

    /**
     * Search dramas by query
     */
    public function search(string $query, string $source = 'dramabox'): array
    {
        $cleanQuery = strtolower(trim($query));
        if (empty($cleanQuery)) {
            return [];
        }
        $cacheKey = "anichin_search_{$source}_" . md5($cleanQuery);
        return Cache::remember($cacheKey, 900, function () use ($source, $cleanQuery) {
            $res = $this->request("/{$source}/search", ['query' => $cleanQuery]);
            if (is_array($res)) {
                if (isset($res['items']) && is_array($res['items'])) return $res['items'];
                if (isset($res['results']) && is_array($res['results'])) return $res['results'];
                if (isset($res['list']) && is_array($res['list'])) return $res['list'];
                if (isset($res['data']) && is_array($res['data'])) return $res['data'];
                if (isset($res['rows']) && is_array($res['rows'])) return $res['rows'];
                if (array_is_list($res)) return $res;
            }
            return [];
        });
    }

    /**
     * Get drama detail & episode list
     */
    public function getDetail(string $source, string $id): ?array
    {
        $cacheKey = "anichin_detail_{$source}_{$id}";
        return Cache::remember($cacheKey, 3600, function () use ($source, $id) {
            $res = $this->request("/{$source}/detail", ['id' => $id]);
            return $res['data'] ?? $res ?? null;
        });
    }

    /**
     * Get single episode stream metadata
     */
    public function getEpisode(string $source, string $id, int $ep = 1): ?array
    {
        $cacheKey = "anichin_ep_{$source}_{$id}_{$ep}";
        return Cache::remember($cacheKey, 1800, function () use ($source, $id, $ep) {
            $res = $this->request("/{$source}/episode", ['id' => $id, 'ep' => $ep]);
            return $res['data'] ?? $res ?? null;
        });
    }

    /**
     * Get Hot Rank dramas
     */
    public function getHotRank(string $source = 'dramabox'): array
    {
        $cacheKey = "anichin_hotrank_{$source}";
        return Cache::remember($cacheKey, 1800, function () use ($source) {
            $res = $this->request("/{$source}/hotrank");
            return $res['items'] ?? (is_array($res) ? $res : []);
        });
    }

    /**
     * Get Recommended dramas
     */
    public function getRecommended(string $source = 'dramabox'): array
    {
        $cacheKey = "anichin_recommended_{$source}";
        return Cache::remember($cacheKey, 1800, function () use ($source) {
            $res = $this->request("/{$source}/recommended");
            return $res['items'] ?? (is_array($res) ? $res : []);
        });
    }

    /**
     * Get Homepage Feed dramas
     */
    public function getHomepageFeed(string $source = 'shortmax'): array
    {
        $cacheKey = "anichin_homepage_{$source}";
        return Cache::remember($cacheKey, 1800, function () use ($source) {
            $res = $this->request("/{$source}/homepage");
            return $res['items'] ?? (is_array($res) ? $res : []);
        });
    }

    /**
     * Get Latest dramas
     */
    public function getLatest(string $source = 'starshort', int $page = 1): array
    {
        $cacheKey = "anichin_latest_{$source}_{$page}";
        return Cache::remember($cacheKey, 1800, function () use ($source, $page) {
            $res = $this->request("/{$source}/latest", ['page' => $page]);
            return $res['items'] ?? (is_array($res) ? $res : []);
        });
    }

    /**
     * Get DramaNova Category (hot, new, hot+, drama18, romance)
     */
    public function getDramaNovaCategory(string $category = 'hot', int $page = 1): array
    {
        $cacheKey = "anichin_dramanova_{$category}_{$page}";
        return Cache::remember($cacheKey, 1800, function () use ($category, $page) {
            $res = $this->request("/dramanova/{$category}", ['page' => $page]);
            return $res['items'] ?? (is_array($res) ? $res : []);
        });
    }

    /**
     * Universal Stream Resolver: Resolve HLS Playlist / M3U8 content for any provider
     */
    public function getHlsStreamContent(string $source, string $id, int $ep = 1): ?array
    {
        $cacheKey = "anichin_m3u8_data_{$source}_{$id}_{$ep}";
        
        // Return cached response ONLY if it's a valid non-empty array
        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached) && !empty($cached['content'])) {
            return $cached;
        }

        try {
            // Strategy 1: Probe Episode API to get exact videoUrl / qualityList
            $epData = $this->getEpisode($source, $id, $ep);
            $videoUrl = $epData['videoUrl'] ?? $epData['video_url'] ?? null;
            if (!$videoUrl && !empty($epData['qualityList'])) {
                $videoUrl = $epData['qualityList'][0]['url'] ?? $epData['qualityList'][0]['videoUrl'] ?? null;
            }

            if ($videoUrl) {
                // If videoUrl is a relative priv-api path (e.g. /api/dramabox/hls?id=... or /api/goodshort/hls?bookId=...)
                if (str_starts_with($videoUrl, '/')) {
                    $fullUrl = rtrim($this->privApiUrl, '/') . $videoUrl;
                    $res = Http::withHeaders([
                        'X-API-Key' => $this->privApiKey,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ])->withoutVerifying()->timeout(15)->get($fullUrl);

                    if ($res->successful() && str_contains($res->body(), '#EXTM3U')) {
                        $data = [
                            'content'  => $res->body(),
                            'base_url' => (string)$res->effectiveUri(),
                        ];
                        Cache::put($cacheKey, $data, 900);
                        return $data;
                    }
                } elseif (str_starts_with($videoUrl, 'http')) {
                    // Direct M3U8 CDN URL (ReelShort, DramaWave, etc.)
                    if (str_contains($videoUrl, '.m3u8')) {
                        $res = Http::withoutVerifying()->timeout(15)->get($videoUrl);
                        if ($res->successful() && str_contains($res->body(), '#EXTM3U')) {
                            $data = [
                                'content'  => $res->body(),
                                'base_url' => (string)$res->effectiveUri(),
                            ];
                            Cache::put($cacheKey, $data, 900);
                            return $data;
                        }
                    } else {
                        // Direct MP4 / TS Video URL (NetShort, etc.)
                        $m3u8 = "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:600\n#EXT-X-MEDIA-SEQUENCE:0\n#EXTINF:600.000,\n{$videoUrl}\n#EXT-X-ENDLIST";
                        $data = [
                            'content'  => $m3u8,
                            'base_url' => $videoUrl,
                        ];
                        Cache::put($cacheKey, $data, 900);
                        return $data;
                    }
                }
            }

            // Strategy 2: Direct priv-api /api/{source}/hls?id=...&ep=...
            $privUrl = rtrim($this->privApiUrl, '/') . "/api/{$source}/hls";
            $response = Http::withHeaders([
                'X-API-Key' => $this->privApiKey,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->withoutVerifying()->timeout(15)->get($privUrl, ['id' => $id, 'ep' => $ep]);

            if ($response->successful() && str_contains($response->body(), '#EXTM3U')) {
                $data = [
                    'content'  => $response->body(),
                    'base_url' => (string)$response->effectiveUri(),
                ];
                Cache::put($cacheKey, $data, 900);
                return $data;
            }

            // Strategy 3: Fallback to public host /api/{source}/hls
            $publicUrl = rtrim($this->apiUrl, '/') . "/api/{$source}/hls";
            $fbRes = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->withoutVerifying()->timeout(15)->get($publicUrl, ['id' => $id, 'ep' => $ep]);

            if ($fbRes->successful() && str_contains($fbRes->body(), '#EXTM3U')) {
                $data = [
                    'content'  => $fbRes->body(),
                    'base_url' => (string)$fbRes->effectiveUri(),
                ];
                Cache::put($cacheKey, $data, 900);
                return $data;
            }
        } catch (Exception $e) {
            Log::error("AnichinService Universal Stream Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Sync single drama item from Anichin API into local database as 'dracin'
     */
    public function syncItemToFilmModel(string $source, array $data, bool $force = false): ?Film
    {
        $rawId = (string)($data['id'] ?? $data['dramaId'] ?? '');
        if (!$rawId) return null;

        $subjectId = "anichin:{$source}:{$rawId}";

        $rawTitle = trim($data['title'] ?? $data['name'] ?? 'Untitled Dracin');
        $cleanTitle = trim(preg_replace('/\[.*?\]/', '', $rawTitle));
        if (empty($cleanTitle)) $cleanTitle = $rawTitle;

        if (!$force && (Film::isExcludedTitle($rawTitle) || Film::isExcludedTitle($cleanTitle))) {
            return null;
        }

        $existing = Film::withTrashed()->where('moviebox_subject_id', $subjectId)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $slug = $existing->slug;
        } else {
            $slug = Str::slug($cleanTitle) . '-' . substr(md5($subjectId), 0, 6);
        }

        $synopsis = trim($data['synopsis'] ?? $data['description'] ?? $data['intro'] ?? $data['brief'] ?? '');

        $posterUrl = $data['posterImg'] ?? $data['cover'] ?? $data['poster'] ?? $data['horizontalCover'] ?? null;
        if (is_array($posterUrl)) {
            $posterUrl = $posterUrl['url'] ?? null;
        }

        $backdropUrl = $data['banner'] ?? $data['cover'] ?? $data['horizontalCover'] ?? $posterUrl;
        if (is_array($backdropUrl)) {
            $backdropUrl = $backdropUrl['url'] ?? null;
        }

        $totalEps = (int)($data['episodes'] ?? $data['totalEpisodes'] ?? 1);
        $totalEps = max(1, $totalEps);

        $film = Film::updateOrCreate(
            ['moviebox_subject_id' => $subjectId],
            [
                'title' => $cleanTitle,
                'slug' => $slug,
                'synopsis' => $synopsis,
                'release_year' => (int)date('Y'),
                'duration_minutes' => 15,
                'poster_url' => $posterUrl,
                'backdrop_url' => $backdropUrl,
                'rating' => 4.8,
                'subject_type' => 'dracin',
                'max_resolution' => '1080P',
            ]
        );

        // Ensure "Dracin" genre is attached
        $dracinGenre = Genre::firstOrCreate(
            ['slug' => 'dracin'],
            ['name' => 'Dracin']
        );
        $film->genres()->syncWithoutDetaching([$dracinGenre->id]);

        // Auto Sync Season 1 & Episodes Structure
        $season = Season::firstOrCreate(
            ['film_id' => $film->id, 'season_number' => 1],
            ['name' => 'Season 1']
        );

        $existingEpCount = Episode::where('season_id', $season->id)->count();
        if ($existingEpCount < $totalEps) {
            for ($i = 1; $i <= $totalEps; $i++) {
                Episode::firstOrCreate(
                    [
                        'season_id' => $season->id,
                        'episode_number' => $i,
                    ],
                    [
                        'title' => "Episode {$i}",
                        'duration_minutes' => 15,
                    ]
                );
            }
        }

        return $film;
    }
}
