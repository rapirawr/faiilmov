<?php

namespace App\Http\Controllers;

use App\Services\FilmSearchService;
use App\Services\NvidiaAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected FilmSearchService $search,
        protected NvidiaAiService $nvidia
    ) {}

    public function autocomplete(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $isPopular = $request->boolean('popular');
        
        if ($isPopular) {
            $suggestions = $this->search->autocomplete($q, true);
            return response()->json($suggestions);
        }

        $suggestions = $this->search->autocomplete($q, false);
        return response()->json($suggestions);
    }

    public function aiInterpret(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (mb_strlen($query) < FilmSearchService::MIN_QUERY_LENGTH) {
            return response()->json(['success' => false, 'message' => 'Query too short'], 400);
        }

        $interpretation = $this->nvidia->interpretQuery($query);

        if ($interpretation === null) {
            return response()->json([
                'success' => false,
                'message' => 'AI interpretation unavailable',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'interpretation' => $interpretation,
        ]);
    }
}
