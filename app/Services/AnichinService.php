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

        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $key,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'application/json',
            ])->withoutVerifying()->timeout(15)->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("AnichinService HTTP {$response->status()} for {$url}");
            return null;
        } catch (Exception $e) {
            Log::error("AnichinService Exception on {$url}: " . $e->getMessage());
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
        $cacheKey = "anichin_search_{$source}_" . md5($query);
        return Cache::remember($cacheKey, 900, function () use ($source, $query) {
            $res = $this->request("/{$source}/search", ['query' => $query]);
            return $res['items'] ?? $res['results'] ?? (is_array($res) ? $res : []);
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
     * Fetch HLS Playlist / M3U8 stream content from private stream server (priv-api.anichin.bio)
     */
    public function getHlsStreamContent(string $source, string $id, int $ep = 1): ?string
    {
        $url = rtrim($this->privApiUrl, '/') . "/api/{$source}/hls";
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->privApiKey,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->withoutVerifying()->timeout(15)->get($url, ['id' => $id, 'ep' => $ep]);

            if ($response->successful()) {
                return $response->body();
            }

            // Fallback to public host if private endpoint returns error
            $fallbackUrl = rtrim($this->apiUrl, '/') . "/api/{$source}/hls";
            $fbRes = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'User-Agent' => 'Mozilla/5.0',
            ])->withoutVerifying()->timeout(15)->get($fallbackUrl, ['id' => $id, 'ep' => $ep]);

            return $fbRes->successful() ? $fbRes->body() : null;
        } catch (Exception $e) {
            Log::error("AnichinService HLS Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync single drama item from Anichin API into local database as 'dracin'
     */
    public function syncItemToFilmModel(string $source, array $data): ?Film
    {
        $rawId = (string)($data['id'] ?? $data['dramaId'] ?? '');
        if (!$rawId) return null;

        $subjectId = "anichin:{$source}:{$rawId}";

        $rawTitle = trim($data['title'] ?? $data['name'] ?? 'Untitled Dracin');
        $cleanTitle = trim(preg_replace('/\[.*?\]/', '', $rawTitle));
        if (empty($cleanTitle)) $cleanTitle = $rawTitle;

        if (Film::isExcludedTitle($rawTitle) || Film::isExcludedTitle($cleanTitle)) {
            return null;
        }

        $slug = Str::slug($cleanTitle) . '-' . substr(md5($subjectId), 0, 6);
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
