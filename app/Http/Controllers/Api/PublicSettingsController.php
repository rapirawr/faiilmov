<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    public function __construct(
        protected SiteSettingsService $settingsService
    ) {}

    /**
     * Get public site settings payload for frontend context & meta rendering
     * GET /api/v1/settings
     */
    public function index(): JsonResponse
    {
        $payload = $this->settingsService->getPublicPayload();

        return response()->json($payload)
            ->header('Cache-Control', 'public, max-age=3600, stale-while-revalidate=86400');
    }
}
