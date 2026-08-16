<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Genre;
use App\Services\MovieBoxService;
use App\Services\FilmSearchService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class HomeController extends Controller
{
    public function __construct(
        protected MovieBoxService $movieBox,
        protected FilmSearchService $filmSearch,
        protected \App\Services\RecommendationService $recommendation
    ) {}

    public function index(Request $request)
    {
        $searchQuery = trim($request->input('q', ''));
        $genreSlug   = $request->input('genre');
        $type        = $request->input('type');
        $sort        = $request->input('sort', 'latest');

        // Non-blocking cached API sync (Only fetch when DB is nearly empty)
        if (Film::count() < 5) {
            try {
                $cacheKey = 'home_sync_' . md5($genreSlug . '_' . $type);
                Cache::remember($cacheKey, 3600, function () {
                    $feed = $this->movieBox->getHomepage('0', 1);
                    $apiSubjects = Film::extractHomepageSubjects($feed);
                    Film::syncFromApiBatch($apiSubjects);
                    return true;
                });
            } catch (Exception $e) {}
        }

        $filters = ['genre' => $genreSlug, 'type' => $type, 'sort' => $sort];

        // Use smart search when query is provided
        if ($searchQuery && mb_strlen($searchQuery) >= FilmSearchService::MIN_QUERY_LENGTH) {
            $films = $this->filmSearch->search($searchQuery, $filters, 30, $request->ip());
        } else {
            // Standard browse when no query
            $query = Film::forActiveProfile()->with('genres');

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

        $genres = Genre::all();
        
        $featuredIds = json_decode(\App\Models\Setting::get('featured_film_ids', '[]'), true) ?: [];
        if (!empty($featuredIds)) {
            $heroFilms = Film::forActiveProfile()->with('genres')->whereIn('id', array_map('intval', $featuredIds))->get();
            if ($heroFilms->isEmpty()) {
                $heroFilms = Film::forActiveProfile()->with('genres')->orderByDesc('rating')->limit(5)->get();
            }
        } else {
            $heroFilms = Film::forActiveProfile()->with('genres')->orderByDesc('rating')->limit(5)->get();
        }
        
        $popularSeries = Film::forActiveProfile()->with('genres')->where('subject_type', 'series')->orderByDesc('rating')->limit(12)->get();
        if ($popularSeries->isEmpty()) {
            $popularSeries = Film::forActiveProfile()->with('genres')->orderByDesc('rating')->limit(12)->get();
        }

        $popularDracin = Film::forActiveProfile()->with('genres')->where('subject_type', 'dracin')->orderByDesc('rating')->limit(12)->get();
        
        $trendingMovies = Film::forActiveProfile()->with('genres')
            ->where('subject_type', 'movie')
            ->orderByDesc('view_count')
            ->orderByDesc('rating')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
            
        if ($trendingMovies->isEmpty()) {
            $trendingMovies = Film::forActiveProfile()->with('genres')
                ->orderByDesc('view_count')
                ->orderByDesc('rating')
                ->orderByDesc('id')
                ->limit(12)
                ->get();
        }

        $continueWatching = null;
        $becauseYouWatched = null;
        $comingSoon = $this->recommendation->getComingSoon(12);
        
        if (Auth::check()) {
            $activeProfileId = session('active_profile_id');

            $continueWatching = \App\Models\WatchHistory::where('user_id', Auth::id())
                ->where('profile_id', $activeProfileId)
                ->has('film')
                ->with(['film' => fn($q) => $q->select('id', 'title', 'slug', 'poster_url', 'rating', 'release_year', 'subject_type', 'max_resolution', 'content_rating', 'duration_minutes')])
                ->whereNotExists(fn($q) => $q->from('watchlists')->whereColumn('watchlists.film_id', 'watch_histories.film_id')->whereColumn('watchlists.user_id', 'watch_histories.user_id')->where('status', 'completed'))
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get();
            
            $becauseRecommendations = $this->recommendation->getBecauseYouWatched(Auth::user(), $activeProfileId, 12);
            
            if ($becauseRecommendations->isNotEmpty()) {
                $lastWatchedFilm = \App\Models\WatchHistory::where('user_id', Auth::id())
                    ->where('profile_id', $activeProfileId)
                    ->has('film')
                    ->with('film')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($lastWatchedFilm && $lastWatchedFilm->film) {
                    $becauseYouWatched = [
                        'source_film' => $lastWatchedFilm->film,
                        'recommendations' => $becauseRecommendations,
                    ];
                }
            }
        }

        $featureBanners = \App\Models\FeatureBanner::active()->ordered()->get();
        $activeFeatureBanner = $featureBanners->first();

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('partials.catalog-grid', compact('films', 'searchQuery'));
        }

        return view('home', compact('films', 'genres', 'heroFilms', 'popularSeries', 'popularDracin', 'trendingMovies', 'continueWatching', 'becauseYouWatched', 'comingSoon', 'searchQuery', 'activeFeatureBanner', 'featureBanners'));
    }
}
