<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitFilmRequestRequest;
use App\Http\Resources\FilmRequestResource;
use App\Jobs\ResolveFilmRequestJob;
use App\Services\FilmRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilmRequestApiController extends Controller
{
    public function __construct(
        private FilmRequestService $requestService
    ) {}

    /**
     * Submit a new film request.
     */
    public function store(SubmitFilmRequestRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->type === 'series' ? 'tv' : $request->type;

        $filmRequest = $this->requestService->submit(
            title: $request->title,
            type: $type,
            year: $request->year ? (int)$request->year : null,
            user: $user
        );

        // Dispatch background job to try auto-resolving
        ResolveFilmRequestJob::dispatch($filmRequest);

        return response()->json([
            'status' => 'success',
            'message' => 'Request film kamu berhasil dikirim! Sistem akan otomatis mencari film ini.',
            'data' => new FilmRequestResource($filmRequest),
        ], 201);
    }

    /**
     * Get film requests submitted by the authenticated user.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = $user->filmRequests()
            ->with('matchedFilm')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => FilmRequestResource::collection($requests),
        ]);
    }
}
