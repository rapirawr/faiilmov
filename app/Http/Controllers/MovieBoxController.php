<?php

namespace App\Http\Controllers;

use App\Services\MovieBoxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class MovieBoxController extends Controller
{
    protected MovieBoxService $movieBox;

    public function __construct(MovieBoxService $movieBox)
    {
        $this->movieBox = $movieBox;
    }

    /**
     * Render main streaming web interface
     */
    public function index()
    {
        return view('movie');
    }

    /**
     * Search movies or series
     * GET /moviebox/search?q=avatar&page=1
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $page = (int)$request->query('page', 1);

        if (empty($query)) {
            return response()->json(['error' => 'Query parameter "q" is required.'], 400);
        }

        try {
            $data = $this->movieBox->search($query, $page);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get details of a movie or series
     * GET /moviebox/detail/{id}
     */
    public function detail(string $id): JsonResponse
    {
        try {
            $data = $this->movieBox->getDetails($id);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get streaming resources / links
     * GET /moviebox/resources/{id}?se=0&ep=0&page=1&resolution=1080
     */
    public function resources(Request $request, string $id): JsonResponse
    {
        $season = (int)$request->query('se', 0);
        $episode = (int)$request->query('ep', 0);
        $page = (int)$request->query('page', 1);
        $resolution = $request->query('resolution');

        try {
            $data = $this->movieBox->getResources($id, $season, $episode, $page, $resolution);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get subtitles for a subject and episode
     * GET /moviebox/subtitles/{id}?se=0&ep=0
     */
    public function subtitles(Request $request, string $id): JsonResponse
    {
        $season = (int)$request->query('se', 0);
        $episode = (int)$request->query('ep', 0);

        try {
            $data = $this->movieBox->getCaptions($id, $season, $episode);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get homepage feed
     * GET /moviebox/homepage?tabId=0&page=1
     */
    public function homepage(Request $request): JsonResponse
    {
        $tabId = $request->query('tabId', '0');
        $page = (int)$request->query('page', 1);

        try {
            $data = $this->movieBox->getHomepage($tabId, $page);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy stream requests with proper Referer & User-Agent headers to bypass 429/403 blocks
     * Auto-refreshes stream URL if upstream CDN returns HTTP 403/410 expired token
     * GET /moviebox/proxy-stream?url=...&id=...&se=...&ep=...
     */
    public function proxyStream(Request $request)
    {
        $targetUrl = $request->query('url');
        $subjectId = $request->query('id');
        $season = (int)$request->query('se', 0);
        $episode = (int)$request->query('ep', 0);

        if (!$targetUrl) {
            return response()->json(['error' => 'URL parameter is required.'], 400);
        }

        $requestHeaders = [
            'User-Agent: com.community.oneroom/50020044 (Linux; U; Android 11; en_US; Redmi 2201117TY; Build/RP1A.200720.011; Cronet/135.0.7012.3)',
            'X-Forwarded-For: 104.16.20.10',
        ];

        if ($rangeHeader = $request->header('Range')) {
            $requestHeaders[] = 'Range: ' . $rangeHeader;
        }

        // Try initial stream URL, if 403/410 and subjectId is present, auto-refresh fresh stream link
        $freshUrl = null;
        if ($subjectId) {
            $chCheck = curl_init($targetUrl);
            curl_setopt($chCheck, CURLOPT_NOBODY, true);
            curl_setopt($chCheck, CURLOPT_HTTPHEADER, $requestHeaders);
            curl_setopt($chCheck, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($chCheck, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chCheck, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($chCheck, CURLOPT_TIMEOUT, 5);
            curl_exec($chCheck);
            $statusCode = curl_getinfo($chCheck, CURLINFO_HTTP_CODE);
            curl_close($chCheck);

            if ($statusCode === 403 || $statusCode === 410) {
                try {
                    $freshData = $this->movieBox->getResources($subjectId, $season, $episode, 1, null, 20, true);
                    $list = $freshData['list'] ?? [];
                    if (!empty($list)) {
                        $freshUrl = $list[0]['resourceLink'] ?? $list[0]['url'] ?? $list[0]['playUrl'] ?? null;
                    }
                } catch (Exception $e) {}
            }
        }

        $finalUrl = $freshUrl ?: $targetUrl;

        return response()->stream(function () use ($finalUrl, $requestHeaders) {
            $ch = curl_init($finalUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);

            // Forward HTTP Status (206 Partial Content) & Content-Range / Content-Length headers to browser
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) {
                $len = strlen($header);
                $headerParts = explode(':', $header, 2);
                if (count($headerParts) === 2) {
                    $name = strtolower(trim($headerParts[0]));
                    $value = trim($headerParts[1]);

                    if (in_array($name, ['content-type', 'content-length', 'content-range', 'accept-ranges'], true)) {
                        header("{$headerParts[0]}: {$value}");
                    }
                } elseif (str_starts_with(strtolower($header), 'http/')) {
                    header($header);
                }
                return $len;
            });

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) {
                echo $chunk;
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                return strlen($chunk);
            });

            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Proxy subtitle requests and convert SRT to WebVTT format for browser HTML5 <track> compatibility
     * GET /moviebox/proxy-subtitle?url=...
     */
    public function proxySubtitle(Request $request)
    {
        $targetUrl = $request->query('url');
        if (!$targetUrl) {
            return response("WEBVTT\n\n", 200, [
                'Content-Type' => 'text/vtt; charset=utf-8',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $cacheKey = 'subtitle_vtt_' . md5($targetUrl);

        try {
            $vttContent = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(24), function () use ($targetUrl) {
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get($targetUrl);
                if (!$response->successful()) {
                    return "WEBVTT\n\n1\n00:00:00.000 --> 00:00:05.000\n[Subtitle tidak dapat dimuat]";
                }

                $content = $response->body();
                // Strip UTF-8 BOM
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                // Normalize line breaks to \n
                $content = str_replace(["\r\n", "\r"], "\n", $content);

                // Convert SRT to WebVTT format if needed
                if (!str_starts_with(trim($content), 'WEBVTT')) {
                    // Convert commas in timestamps (00:01:23,456 or 01:23,456 -> 00:01:23.456)
                    $content = preg_replace('/(\d{1,2}:\d{2}:\d{2})[,.](\d{2,3})/', '$1.$2', $content);
                    $content = "WEBVTT\n\n" . ltrim($content);
                }

                return $content;
            });

            return response($vttContent, 200, [
                'Content-Type' => 'text/vtt; charset=utf-8',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (Exception $e) {
            return response("WEBVTT\n\n1\n00:00:00.000 --> 00:00:05.000\n[Subtitle Error]", 200, [
                'Content-Type' => 'text/vtt; charset=utf-8',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }
    }
}
