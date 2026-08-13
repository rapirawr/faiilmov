<?php

namespace App\Http\Controllers;

use App\Services\AnichinService;
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

        $m3u8 = $this->anichin->getHlsStreamContent($source, $id, $ep);
        if (!$m3u8) {
            return response("#EXTM3U\n#EXT-X-ERROR: Stream Unavailable", 404, [
                'Content-Type' => 'application/x-mpegURL',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        return response($m3u8, 200, [
            'Content-Type' => 'application/x-mpegURL',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-cache',
        ]);
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
