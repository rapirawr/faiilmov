<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class MovieBoxService
{
    protected string $secretKey;
    protected array $hostPool;
    protected ?string $runtimeToken = null;
    protected int $activeHostIdx = 0;
    protected string $userAgent;
    protected string $clientInfo;
    protected string $spoofedIp;

    public function __construct()
    {
        $this->secretKey = config('services.moviebox.secret_key', '76iRl07s0xSN9jqmEWAt79EBJZulIQIsV64FZr2O');
        $this->hostPool = config('services.moviebox.hosts', [
            'https://api6.aoneroom.com',
            'https://api5.aoneroom.com',
            'https://api4.aoneroom.com',
            'https://api4sg.aoneroom.com',
            'https://api3.aoneroom.com',
            'https://api6sg.aoneroom.com',
            'https://api.inmoviebox.com',
        ]);

        [$this->userAgent, $this->clientInfo] = $this->generateClientInfoAndUA();
        $this->spoofedIp = $this->generateSpoofedIp();
        $this->runtimeToken = Cache::get('moviebox_runtime_token');
    }

    /**
     * Lazy session init - only fetches token if missing from Cache
     */
    public function init(): bool
    {
        if ($this->runtimeToken) {
            return true;
        }

        $cacheKey = 'moviebox_init_token_lock';
        return Cache::remember($cacheKey, 3600, function () {
            $path = '/wefeed-mobile-bff/tab-operating?page=1&tabId=0&version=';
            try {
                $this->getUncached($path);
                return !empty($this->runtimeToken);
            } catch (Exception $e) {
                Log::warning('MovieBoxService init failed: ' . $e->getMessage());
                return false;
            }
        });
    }

    /**
     * Search movies/series with caching
     */
    public function search(string $keyword, int $page = 1): mixed
    {
        $cacheKey = 'mb_search_' . md5($keyword . '_' . $page);
        return Cache::remember($cacheKey, 900, function () use ($keyword, $page) {
            $payload = [
                'keyword' => $keyword,
                'page' => $page,
                'perPage' => 20,
                'subjectType' => 'All',
                'tabId' => 'All',
            ];
            return $this->post('/wefeed-mobile-bff/subject-api/search/v2', $payload);
        });
    }

    /**
     * Get subject details with caching
     */
    public function getDetails(string $subjectId): mixed
    {
        $cacheKey = 'mb_detail_' . $subjectId;
        return Cache::remember($cacheKey, 3600, function () use ($subjectId) {
            $path = sprintf('/wefeed-mobile-bff/subject-api/get?subjectId=%s', $subjectId);
            $details = $this->get($path);

            $stype = $details['subjectType'] ?? $details['stype'] ?? 1;

            if ((int)$stype === 2) {
                $seasonPath = sprintf('/wefeed-mobile-bff/subject-api/season-info?subjectId=%s', $subjectId);
                try {
                    $seasonInfo = $this->get($seasonPath);
                    if (is_array($details)) {
                        $details['seasons'] = $seasonInfo;
                    }
                } catch (Exception $e) {
                }
            }

            return $details;
        });
    }

    /**
     * Get homepage feed with caching
     */
    public function getHomepage(string $tabId = '0', int $page = 1): mixed
    {
        $cacheKey = sprintf('mb_homepage_%s_%d', $tabId, $page);
        return Cache::remember($cacheKey, 1800, function () use ($tabId, $page) {
            $path = sprintf('/wefeed-mobile-bff/tab-operating?page=%d&tabId=%s&version=', $page, $tabId);
            return $this->get($path);
        });
    }

    /**
     * Get streaming resources with short caching
     */
    public function getResources(
        string $subjectId,
        int $season = 0,
        int $episode = 0,
        int $page = 1,
        ?string $resolution = null,
        int $perPage = 20
    ): mixed {
        $cacheKey = sprintf('mb_res_%s_%d_%d_%d_%s', $subjectId, $season, $episode, $page, $resolution ?? 'all');
        return Cache::remember($cacheKey, 60, function () use ($subjectId, $season, $episode, $page, $resolution, $perPage) {
            $resolutionsToFetch = $resolution ? [$resolution] : ['1080', '720', '480', '360'];
            $combinedList = [];
            $firstResponse = null;

            foreach ($resolutionsToFetch as $resVal) {
                if ($season === 0 && $episode === 0) {
                    $path = sprintf(
                        '/wefeed-mobile-bff/subject-api/resource?subjectId=%s&page=%d&perPage=%d&resolution=%s',
                        $subjectId, $page, $perPage, $resVal
                    );
                } else {
                    $path = sprintf(
                        '/wefeed-mobile-bff/subject-api/resource?subjectId=%s&se=%d&ep=%d&page=%d&perPage=%d&resolution=%s',
                        $subjectId, $season, $episode, $page, $perPage, $resVal
                    );
                }

                try {
                    $res = $this->get($path);
                    if (!$firstResponse && is_array($res)) {
                        $firstResponse = $res;
                    }
                    $list = $res['list'] ?? [];
                    if (is_array($list)) {
                        foreach ($list as $item) {
                            $combinedList[] = $item;
                        }
                    }
                } catch (Exception $e) {
                    // Continue fetching other resolutions if one fails
                }
            }

            $response = $firstResponse ?: ['list' => []];

            // Filter response list by requested episode number if specified
            if (!empty($combinedList) && $episode > 0) {
                $epList = array_values(array_filter($combinedList, function ($item) use ($episode) {
                    $epNum = (int)($item['ep'] ?? $item['episode'] ?? 0);
                    return $epNum === $episode;
                }));

                if (!empty($epList)) {
                    $response['list'] = $epList;
                } else {
                    $response['list'] = $combinedList;
                }
            } else {
                $response['list'] = $combinedList;
            }

            return $response;
        });
    }

    /**
     * Get captions / subtitles for a subject and episode
     */
    public function getCaptions(string $subjectId, int $season = 0, int $episode = 0): array
    {
        try {
            $resourcesData = $this->getResources($subjectId, $season, $episode, 1);
            $resourceList = $resourcesData['list'] ?? (is_array($resourcesData) ? $resourcesData : []);

            $captions = [];
            $seenLangs = [];

            foreach ($resourceList as $res) {
                $extCaptions = $res['extCaptions'] ?? $res['subtitles'] ?? [];
                if (!is_array($extCaptions)) continue;

                foreach ($extCaptions as $cap) {
                    $rawUrl = $cap['url'] ?? $cap['subPath'] ?? $cap['captionUrl'] ?? $cap['path'] ?? '';
                    if (!$rawUrl) continue;

                    $langName = $cap['lanName'] ?? $cap['languageName'] ?? $cap['lan'] ?? $cap['language'] ?? 'English';
                    $langCode = strtolower($cap['lan'] ?? $cap['lang'] ?? $cap['language'] ?? 'en');

                    $labelMap = [
                        'ind' => 'Bahasa Indonesia',
                        'id'  => 'Bahasa Indonesia',
                        'in'  => 'Bahasa Indonesia',
                        'eng' => 'English',
                        'en'  => 'English',
                        'spa' => 'Español',
                        'es'  => 'Español',
                        'zho' => '中文',
                        'zh'  => '中文',
                        'jpn' => '日本語',
                        'ja'  => '日本語',
                        'kor' => '한국어',
                        'ko'  => '한국어',
                    ];

                    $label = $labelMap[$langCode] ?? ucfirst($langName);
                    $key = $langCode . '_' . md5($rawUrl);

                    if (isset($seenLangs[$key])) continue;
                    $seenLangs[$key] = true;

                    $captions[] = [
                        'id'      => $cap['id'] ?? md5($rawUrl),
                        'label'   => $label,
                        'srclang' => $langCode,
                        'url'     => url('/moviebox/proxy-subtitle') . '?url=' . urlencode($rawUrl),
                        'raw_url' => $rawUrl,
                    ];
                }
            }

            return $captions;
        } catch (Exception $e) {
            return [];
        }
    }

    public function get(string $pathAndQuery): mixed
    {
        return $this->request('GET', $pathAndQuery);
    }

    public function post(string $pathAndQuery, array $body = []): mixed
    {
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);
        return $this->request('POST', $pathAndQuery, $bodyJson);
    }

    protected function getUncached(string $pathAndQuery): mixed
    {
        return $this->request('GET', $pathAndQuery);
    }

    protected function request(string $method, string $pathAndQuery, ?string $bodyStr = null): mixed
    {
        if (empty($this->runtimeToken) && !str_contains($pathAndQuery, 'tab-operating')) {
            $this->init();
        }

        $retryStatusCodes = [403, 406, 407, 429, 500, 502, 503, 504];
        $totalHosts = count($this->hostPool);
        $startIdx = $this->activeHostIdx;

        for ($i = 0; $i < $totalHosts; $i++) {
            $idx = ($startIdx + $i) % $totalHosts;
            $baseHost = $this->hostPool[$idx];
            $url = $baseHost . $pathAndQuery;

            $headers = $this->buildSignedHeaders($method, $url, $bodyStr);

            try {
                // Fast 3s timeout per host instead of 12s
                $http = Http::withHeaders($headers)
                    ->connectTimeout(2)
                    ->timeout(3);

                if ($method === 'POST') {
                    $response = $http->withBody($bodyStr ?? '{}', 'application/json')->post($url);
                } else {
                    $response = $http->get($url);
                }

                $this->absorbXUserToken($response->headers());

                if (in_array($response->status(), $retryStatusCodes, true)) {
                    continue;
                }

                if (!$response->successful()) {
                    continue;
                }

                $this->activeHostIdx = $idx;
                $data = $response->json();

                if (is_array($data) && isset($data['data'])) {
                    return $data['data'];
                }

                return $data;
            } catch (Exception $e) {
                Log::debug(sprintf('MovieBox host %s failed: %s', $baseHost, $e->getMessage()));
                continue;
            }
        }

        throw new Exception('All MovieBox hosts exhausted or failed.');
    }

    public function getSignedHeadersForUrl(string $url, string $method = 'GET'): array
    {
        return $this->buildSignedHeaders($method, $url, null);
    }

    protected function absorbXUserToken(array $headers): void
    {
        $xUserHeader = $headers['x-user'][0] ?? $headers['X-User'][0] ?? null;
        if (!$xUserHeader) {
            return;
        }

        $json = json_decode($xUserHeader, true);
        if (is_array($json) && !empty($json['token'])) {
            $this->runtimeToken = $json['token'];
            Cache::put('moviebox_runtime_token', $json['token'], 3600);
        }
    }

    protected function buildSignedHeaders(string $method, string $url, ?string $bodyStr = null): array
    {
        $ts = (int)(microtime(true) * 1000);
        $accept = 'application/json';
        $contentType = 'application/json';

        $clientToken = $this->generateXClientToken($ts);
        $signature = $this->generateXTrSignature($method, $accept, $contentType, $url, $bodyStr, $ts);

        $headers = [
            'User-Agent' => $this->userAgent,
            'Accept' => $accept,
            'Content-Type' => $contentType,
            'Connection' => 'keep-alive',
            'X-Client-Token' => $clientToken,
            'x-tr-signature' => $signature,
            'X-Client-Info' => $this->clientInfo,
            'X-Client-Status' => '0',
            'X-Forwarded-For' => $this->spoofedIp,
        ];

        if ($this->runtimeToken) {
            $headers['Authorization'] = 'Bearer ' . $this->runtimeToken;
        }

        return $headers;
    }

    protected function generateXClientToken(int $ts): string
    {
        $tsStr = (string)$ts;
        $reversedTs = strrev($tsStr);
        $hashVal = md5($reversedTs);
        return sprintf('%s,%s', $tsStr, $hashVal);
    }

    protected function generateXTrSignature(
        string $method,
        ?string $accept,
        ?string $contentType,
        string $url,
        ?string $bodyStr,
        int $ts
    ): string {
        $canonicalUrl = $this->getCanonicalUrl($url);
        
        $bodyLength = '';
        $bodyHash = '';
        if ($bodyStr !== null && $bodyStr !== '') {
            $bodyLength = (string)strlen($bodyStr);
            $truncated = strlen($bodyStr) > 102400 ? substr($bodyStr, 0, 102400) : $bodyStr;
            $bodyHash = md5($truncated);
        }

        $canonical = implode("\n", [
            strtoupper($method),
            $accept ?? '',
            $contentType ?? '',
            $bodyLength,
            $ts,
            $bodyHash,
            $canonicalUrl,
        ]);

        $secretBytes = base64_decode($this->secretKey);
        $hmacRaw = hash_hmac('md5', $canonical, $secretBytes, true);
        $sigB64 = base64_encode($hmacRaw);

        return sprintf('%d|2|%s', $ts, $sigB64);
    }

    protected function getCanonicalUrl(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        
        if (empty($parsed['query'])) {
            return $path;
        }

        parse_str($parsed['query'], $queryParams);
        ksort($queryParams);

        $queryParts = [];
        foreach ($queryParams as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $v) {
                    $queryParts[] = sprintf('%s=%s', $key, $v);
                }
            } else {
                $queryParts[] = sprintf('%s=%s', $key, $val);
            }
        }

        $queryStr = implode('&', $queryParts);
        return $queryStr ? sprintf('%s?%s', $path, $queryStr) : $path;
    }

    protected function generateClientInfoAndUA(): array
    {
        $androidVersions = [
            ['9', 'PQ3A.190605.03081104'],
            ['10', 'QP1A.191005.007.A3'],
            ['11', 'RP1A.200720.011'],
            ['12', 'S1B.220414.015'],
            ['13', 'TQ2A.230405.003'],
        ];

        $redmiDevices = [
            ['23078RKD5C', 'Redmi'],
            ['2201117TY', 'Redmi'],
            ['2201117TG', 'Redmi'],
            ['22101316G', 'Redmi'],
            ['21121210G', 'Redmi'],
        ];

        $versionCodes = [50020042, 50020043, 50020044, 50020045, 50020046];
        $networkTypes = ['NETWORK_WIFI', 'NETWORK_MOBILE'];
        $timezones = ['Asia/Kolkata', 'Asia/Shanghai', 'Asia/Tokyo', 'America/New_York'];

        $android = $androidVersions[array_rand($androidVersions)];
        $device = $redmiDevices[array_rand($redmiDevices)];
        $versionCode = $versionCodes[array_rand($versionCodes)];
        $network = $networkTypes[array_rand($networkTypes)];
        $timezone = $timezones[array_rand($timezones)];

        $gaid = sprintf('%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
        $deviceId = bin2hex(random_bytes(16));

        $userAgent = sprintf(
            'com.community.oneroom/%d (Linux; U; Android %s; en_US; %s; Build/%s; Cronet/135.0.7012.3)',
            $versionCode, $android[0], $device[0], $android[1]
        );

        $clientInfo = sprintf(
            '{"package_name":"com.community.oneroom","version_name":"3.0.03.0529.03","version_code":%d,"os":"android","os_version":"%s","install_ch":"ps","device_id":"%s","install_store":"ps","gaid":"%s","brand":"%s","model":"%s","system_language":"en","net":"%s","region":"US","timezone":"America/New_York","sp_code":"40401","X-Play-Mode":"2"}',
            $versionCode, $android[0], $deviceId, $gaid, $device[1], $device[0], $network
        );

        return [$userAgent, $clientInfo];
    }

    protected function generateSpoofedIp(): string
    {
        $prefixes = ['104.16', '52.90', '54.210', '13.228', '18.136', '54.169', '142.250', '172.217'];
        $prefix = $prefixes[array_rand($prefixes)];
        return sprintf('%s.%d.%d', $prefix, mt_rand(1, 254), mt_rand(1, 254));
    }
}
