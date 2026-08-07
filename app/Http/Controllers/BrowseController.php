<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Genre;
use App\Services\MovieBoxService;
use App\Services\FilmSearchService;
use Illuminate\Http\Request;
use Exception;

class BrowseController extends Controller
{
    public function __construct(
        protected MovieBoxService $movieBox,
        protected FilmSearchService $filmSearch,
        protected \App\Services\NvidiaAiService $nvidia
    ) {}

    public function index(Request $request)
    {
        $searchQuery = trim($request->input('q', ''));
        $type        = $request->input('type');
        $genreSlug   = $request->input('genre');
        $sort        = $request->input('sort', 'latest');
        $minRating   = $request->input('min_rating');
        $genres      = Genre::all();

        // Sync API data into local DB
        if ($searchQuery) {
            try {
                $searchResult = $this->movieBox->search($searchQuery, 1);
                $apiSubjects  = Film::extractSearchSubjects($searchResult);
                Film::syncFromApiBatch($apiSubjects);
            } catch (Exception $e) {}
        } else {
            try {
                $tabId = match(true) {
                    $type === 'movie'         => '1',
                    $type === 'series'        => '2',
                    $genreSlug === 'animation'=> '3',
                    default                   => '0',
                };
                $feed        = $this->movieBox->getHomepage($tabId, 1);
                $apiSubjects = Film::extractHomepageSubjects($feed);
                Film::syncFromApiBatch($apiSubjects);
            } catch (Exception $e) {}
        }

        $filters = [
            'genre'      => $genreSlug,
            'type'       => $type,
            'min_rating' => $minRating,
            'sort'       => $sort,
        ];

        // Use smart search when query is provided
        $aiInterpretation = null;
        if ($searchQuery && mb_strlen($searchQuery) >= FilmSearchService::MIN_QUERY_LENGTH) {
            $aiInterpretation = $this->filmSearch->getAiInterpretation($searchQuery);
            $films = $this->filmSearch->search($searchQuery, $filters, 30, $request->ip());
            $noResults = $films && $films->total() === 0;
        } else {
            // Standard filter browse
            $query = Film::with('genres');

            if ($genreSlug) {
                $query->whereHas('genres', fn($q) => $q->where('slug', $genreSlug));
            }
            if ($type) {
                $query->where('subject_type', $type);
            }
            if ($minRating) {
                $query->where('rating', '>=', (float)$minRating);
            }

            match ($sort) {
                'rating_desc' => $query->orderByDesc('rating'),
                'title_asc'   => $query->orderBy('title'),
                default       => $query->orderByDesc('release_year')->orderByDesc('id'),
            };

            $films     = $query->paginate(30)->withQueryString();
            $noResults = $films->total() === 0;
        }

        // Suggested films shown when results are empty
        $suggestedFilms = $noResults
            ? Film::orderByDesc('rating')->limit(6)->get()
            : collect();

        return view('browse', compact('films', 'genres', 'searchQuery', 'noResults', 'suggestedFilms', 'aiInterpretation'));
    }
}
