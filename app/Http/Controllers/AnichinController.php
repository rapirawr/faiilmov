<?php

namespace App\Http\Controllers;

use App\Services\AnichinService;
use App\Services\SsrfGuard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnichinController extends Controller
{
    public function __construct(protected AnichinService $anichin)
    {
    }

    /**
     * Proxy HLS M3U8 Playlist Stream from Anichin Private API (priv-api.anichin.bio)
     * GET /anichin/hls?source=dramabox&id=42000022778&ep=1
     */
    public function hlsStream(Request $request)
    {
        $source = $request->query('source', 'dramabox');
        $id = $request->query('id');
        $ep = (int)$request->query('ep', 1);

        if (!$id) {
            return response("#EXTM3U\n#EXT-X-ERROR: Missing ID", 400, [
                'Content-Type' => 'application/x-mpegURL',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $streamData = $this->anichin->getHlsStreamContent($source, $id, $ep);
        $m3u8 = $streamData['content'] ?? null;
        $baseUrl = $streamData['base_url'] ?? '';

        // Fallback 1: Try getEpisode API if getHlsStreamContent failed
        if (!$m3u8 || !str_contains($m3u8, '#EXTM3U')) {
            $epData = $this->anichin->getEpisode($source, $id, $ep);
            $streamLink = $epData['m3u8_url'] ?? $epData['video_url'] ?? $epData['stream_url'] ?? $epData['play_url'] ?? $epData['url'] ?? null;
            if ($streamLink) {
                if (str_contains($streamLink, '.m3u8')) {
                    try {
                        if (SsrfGuard::isSafeUrl($streamLink)) {
                            $m3u8Res = \Illuminate\Support\Facades\Http::timeout(5)->get($streamLink);
                            if ($m3u8Res->successful() && str_contains($m3u8Res->body(), '#EXTM3U')) {
                                $m3u8 = $m3u8Res->body();
                                $baseUrl = (string)$m3u8Res->effectiveUri();
                            }
                        }
                    } catch (\Exception $e) {}
                }
                
                if (!$m3u8) {
                    $m3u8 = "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:10\n#EXT-X-MEDIA-SEQUENCE:0\n#EXTINF:10.000,\n{$streamLink}\n#EXT-X-ENDLIST";
                }
            }
        }

        // Final Fallback if external APIs returned no content for this item
        if (!$m3u8 || !str_contains($m3u8, '#EXTM3U')) {
            $m3u8 = "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:10\n#EXT-X-MEDIA-SEQUENCE:0\n#EXTINF:10.000,\nhttps://vjs.zencdn.net/v/oceans.mp4\n#EXT-X-ENDLIST";
        }

        // Extract base path directory from baseUrl if available
        $baseDir = '';
        if ($baseUrl) {
            $baseDir = preg_replace('/\/[^\/]*$/', '/', $baseUrl);
        }

        // Process line by line to rewrite both absolute and relative segment URLs
        $lines = explode("\n", $m3u8);
        $rewrittenLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                $rewrittenLines[] = $line;
                continue;
            }

            if (str_starts_with($trimmed, '#EXT-X-MAP:')) {
                // Rewrite initialization segment URI in fMP4 stream headers
                $line = preg_replace_callback('/URI="([^"]+)"/', function($m) use ($baseDir) {
                    $mapUrl = $m[1];
                    if (!str_starts_with($mapUrl, 'http://') && !str_starts_with($mapUrl, 'https://')) {
                        if ($baseDir) {
                            $mapUrl = $baseDir . ltrim($mapUrl, '/');
                        }
                    }
                    if (str_starts_with($mapUrl, 'http://') || str_starts_with($mapUrl, 'https://')) {
                        return 'URI="' . route('anichin.ts_proxy', ['url' => $mapUrl]) . '"';
                    }
                    return $m[0];
                }, $line);

                $rewrittenLines[] = $line;
                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                // Header line or tag
                $rewrittenLines[] = $line;
                continue;
            }

            // It's a segment URL line!
            $segmentUrl = $trimmed;
            if (!str_starts_with($segmentUrl, 'http://') && !str_starts_with($segmentUrl, 'https://')) {
                if ($baseDir) {
                    $segmentUrl = $baseDir . ltrim($segmentUrl, '/');
                }
            }

            if (str_starts_with($segmentUrl, 'http://') || str_starts_with($segmentUrl, 'https://')) {
                $rewrittenLines[] = route('anichin.ts_proxy', ['url' => $segmentUrl]);
            } else {
                $rewrittenLines[] = $line;
            }
        }

        $m3u8Rewritten = implode("\n", $rewrittenLines);

        return response($m3u8Rewritten, 200, [
            'Content-Type' => 'application/x-mpegURL',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Proxy TS video segments with CORS headers & custom user-agent
     * GET /anichin/ts-proxy?url=...
     */
    public function tsProxy(Request $request)
    {
        $targetUrl = $request->query('url');

        if (empty($targetUrl) || !SsrfGuard::isSafeUrl($targetUrl)) {
            return response('Invalid or Forbidden TS Segment URL', 403);
        }

        try {
            $host = parse_url($targetUrl, PHP_URL_HOST) ?: '';
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: */*',
            ];

            // Only attach Anichin referer header for Anichin & DramaBox CDN domains
            if (str_contains($host, 'anichin') || str_contains($host, 'dramabox') || str_contains($host, 'hwzthls') || str_contains($host, 'shortmax')) {
                $headers[] = 'Referer: https://anichin.bio/';
            }

            $ch = curl_init($targetUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $body = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'video/mp2t';
            curl_close($ch);

            if ($statusCode >= 200 && $statusCode < 400 && $body !== false && strlen($body) > 0) {
                return response($body, 200, [
                    'Content-Type' => $contentType,
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => '*',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Anichin TS Proxy Exception: " . $e->getMessage());
        }

        // Secondary fallback using Guzzle Http client if cURL failed
        try {
            $res = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(10)->get($targetUrl);
            if ($res->successful()) {
                return response($res->body(), 200, [
                    'Content-Type' => $res->header('Content-Type') ?: 'video/mp2t',
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => '*',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        } catch (\Exception $e) {}

        return response('Segment Proxy Error', 502);
    }

    /**
     * Proxy Anichin detail API
     * GET /anichin/detail/{source}/{id}
     */
    public function detail(string $source, string $id): JsonResponse
    {
        $data = $this->anichin->getDetail($source, $id);
        return response()->json($data ?: ['error' => 'Detail not found']);
    }

    /**
     * Proxy Anichin trending API
     * GET /anichin/trending/{source}
     */
    public function trending(string $source = 'dramabox'): JsonResponse
    {
        $data = $this->anichin->getTrending($source);
        return response()->json($data);
    }

    /**
     * Proxy Anichin For You feed API
     * GET /anichin/foryou/{source}?page=1
     */
    public function forYou(Request $request, string $source = 'dramabox'): JsonResponse
    {
        $page = (int)$request->query('page', 1);
        $data = $this->anichin->getForYou($source, $page);
        return response()->json($data);
    }

    /**
     * Proxy Anichin Search API
     * GET /anichin/search/{source}?query=love
     */
    public function search(Request $request, string $source = 'dramabox'): JsonResponse
    {
        $query = (string)$request->query('query', '');
        $data = $this->anichin->search($query, $source);
        return response()->json($data);
    }

    /**
     * Proxy Anichin Hot Rank API
     * GET /anichin/hotrank/{source}
     */
    public function hotRank(string $source = 'dramabox'): JsonResponse
    {
        $data = $this->anichin->getHotRank($source);
        return response()->json($data);
    }

    /**
     * Proxy Anichin Recommended API
     * GET /anichin/recommended/{source}
     */
    public function recommended(string $source = 'dramabox'): JsonResponse
    {
        $data = $this->anichin->getRecommended($source);
        return response()->json($data);
    }

    /**
     * Proxy Anichin Latest API
     * GET /anichin/latest/{source}?page=1
     */
    public function latest(Request $request, string $source = 'starshort'): JsonResponse
    {
        $page = (int)$request->query('page', 1);
        $data = $this->anichin->getLatest($source, $page);
        return response()->json($data);
    }
}
