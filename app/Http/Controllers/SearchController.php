<?php

namespace App\Http\Controllers;

use App\Services\FilmSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(protected FilmSearchService $search) {}

    /**
     * GET /search/autocomplete?q=...
     * Returns max 8 suggestions for the live dropdown.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $suggestions = $this->search->autocomplete($q);
        return response()->json($suggestions);
    }
}
