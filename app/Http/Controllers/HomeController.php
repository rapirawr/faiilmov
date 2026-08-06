<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Genre;
use App\Services\MovieBoxService;
use App\Services\FilmSearchService;
use Illuminate\Http\Request;
use Exception;

class HomeController extends Controller
{
    public function __construct(
        protected MovieBoxService $movieBox,
        protected FilmSearchService $filmSearch
    ) {}

    public function index(Request $request)
    {
        $searchQuery = trim($request->input('q', ''));
        $genreSlug   = $request->input('genre');
        $type        = $request->input('type');
        $sort        = $request->input('sort', 'latest');

        // Sync API data into local DB
        try {
            if ($searchQuery) {
                $searchResult = $this->movieBox->search($searchQuery, 1);
                $apiSubjects  = Film::extractSearchSubjects($searchResult);
            } else {
                $feed        = $this->movieBox->getHomepage('0', 1);
                $apiSubjects = Film::extractHomepageSubjects($feed);
            }
            Film::syncFromApiBatch($apiSubjects);
        } catch (Exception $e) {}

        $filters = ['genre' => $genreSlug, 'type' => $type, 'sort' => $sort];

        // Use smart search when query is provided
        if ($searchQuery && mb_strlen($searchQuery) >= FilmSearchService::MIN_QUERY_LENGTH) {
            $films = $this->filmSearch->search($searchQuery, $filters, 30, $request->ip());
        } else {
            // Standard browse when no query
            $query = Film::with('genres');

            if ($genreSlug) {
                $query->whereHas('genres', fn($q) => $q->where('slug', $genreSlug));
            }
            if ($type) {
                $query->where('subject_type', $type);
            }

            match ($sort) {
                'rating_desc' => $query->orderByDesc('rating'),
                'title_asc'   => $query->orderBy('title'),
                default       => $query->orderByDesc('release_year')->orderByDesc('id'),
            };

            $films = $query->paginate(30)->withQueryString();
        }

        $genres        = Genre::all();
        
        $featuredIds   = json_decode(\App\Models\Setting::get('featured_film_ids', '[]'), true) ?: [];
        if (!empty($featuredIds)) {
            $heroFilms = Film::whereIn('id', array_map('intval', $featuredIds))->get();
            if ($heroFilms->isEmpty()) {
                $heroFilms = Film::orderByDesc('rating')->limit(5)->get();
            }
        } else {
            $heroFilms = Film::orderByDesc('rating')->limit(5)->get();
        }
        $popularSeries = Film::where('subject_type', 'series')->orderByDesc('rating')->limit(12)->get();
        if ($popularSeries->isEmpty()) {
            $popularSeries = Film::orderByDesc('rating')->limit(12)->get();
        }
        $trendingMovies = Film::where('subject_type', 'movie')->orderByDesc('id')->limit(12)->get();
        if ($trendingMovies->isEmpty()) {
            $trendingMovies = Film::orderByDesc('id')->limit(12)->get();
        }

        return view('home', compact('films', 'genres', 'heroFilms', 'popularSeries', 'trendingMovies', 'searchQuery'));
    }
}
