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

        // Non-blocking cached API sync (Only fetch when DB is nearly empty)
        if (Film::count() < 5) {
            try {
                $cacheKey = 'browse_sync_' . md5($type . '_' . $genreSlug);
                \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($type, $genreSlug) {
                    $tabId = match(true) {
                        $type === 'movie'          => '1',
                        $type === 'series'         => '2',
                        $genreSlug === 'animation' => '3',
                        default                    => '0',
                    };
                    $feed        = $this->movieBox->getHomepage($tabId, 1);
                    $apiSubjects = Film::extractHomepageSubjects($feed);
                    Film::syncFromApiBatch($apiSubjects);
                    return true;
                });
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
        $aiRecommendations = collect();

        if ($searchQuery && mb_strlen($searchQuery) >= FilmSearchService::MIN_QUERY_LENGTH) {
            $films = $this->filmSearch->search($searchQuery, $filters, 30, $request->ip());
            $noResults = $films && $films->total() === 0;

            $aiInterpretation = $this->filmSearch->getAiInterpretation($searchQuery);
            if ($aiInterpretation) {
                $excludeIds = $films ? $films->pluck('id')->toArray() : [];
                $aiRecommendations = $this->filmSearch->getAiRecommendations($searchQuery, $aiInterpretation, $excludeIds, 6);
            }
        } else {
            // Standard filter browse
            $query = Film::forActiveProfile()->with('genres');

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

        $suggestedFilms = $noResults
            ? Film::forActiveProfile()->with('genres')->orderByDesc('rating')->limit(6)->get()
            : collect();

        return view('browse', compact('films', 'genres', 'searchQuery', 'noResults', 'suggestedFilms', 'aiInterpretation', 'aiRecommendations'));
    }

    public function genre(string $slug, Request $request)
    {
        $genreModel = Genre::where('slug', $slug)->first();
        if (!$genreModel && !in_array($slug, ['action', 'comedy', 'drama', 'horror', 'romance', 'sci-fi', 'animation', 'thriller', 'adventure'])) {
            abort(404, 'Genre tidak ditemukan.');
        }

        $request->merge(['genre' => $slug]);
        $genreName = $genreModel ? $genreModel->name : ucfirst($slug);

        $seoTitle = "Katalog Film Genre {$genreName} - Subtitle Indonesia | faiilmov";
        $seoDesc = "Daftar koleksi film dan serial TV genre {$genreName} subtitle Indonesia gratis kualitas HD. Temukan ribuan film {$genreName} terbaik di faiilmov.";

        \Illuminate\Support\Facades\View::share('title', $seoTitle);
        \Illuminate\Support\Facades\View::share('meta_description', $seoDesc);

        return $this->index($request);
    }
}
