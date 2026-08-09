<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Season;
use App\Models\Episode;
use App\Models\WatchHistory;
use App\Services\MovieBoxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MovieDetailController extends Controller
{
    protected MovieBoxService $movieBox;
    protected \App\Services\FilmSearchService $filmSearch;

    public function __construct(MovieBoxService $movieBox, \App\Services\FilmSearchService $filmSearch)
    {
        $this->movieBox = $movieBox;
        $this->filmSearch = $filmSearch;
        $this->movieBox->init();
    }

    public function show(string $slug)
    {
        $film = Film::with(['genres', 'actors', 'seasons.episodes'])->where('slug', $slug)->first();

        if (!$film) {
            $subjectId = $slug;
            if (str_contains($slug, '-')) {
                $parts = explode('-', $slug);
                $subjectId = end($parts);
            }

            try {
                $apiDetail = $this->movieBox->getDetails($subjectId);
                $film = Film::fromApiData($apiDetail);
            } catch (Exception $e) {
                abort(404, 'Film tidak ditemukan.');
            }
        }

        if (!$this->isAllowedForActiveProfile($film)) {
            return redirect()->route('home')->with('error', 'Profil Anak tidak dapat mengakses film atau series dengan batasan usia ini.');
        }

        if (empty($film->synopsis) && $film->moviebox_subject_id) {
            try {
                $apiDetail = $this->movieBox->getDetails($film->moviebox_subject_id);
                $desc = trim($apiDetail['description'] ?? $apiDetail['intro'] ?? $apiDetail['synopsis'] ?? $apiDetail['brief'] ?? $apiDetail['summary'] ?? '');
                if (!empty($desc)) {
                    $film->update(['synopsis' => $desc]);
                    $film->synopsis = $desc;
                }
            } catch (Exception $e) {}
        }

        if ($film->subject_type === 'series') {
            $this->syncSeriesStructure($film);
            $film->load('seasons.episodes');
        }

        // Increment view count (deferred to avoid blocking)
        $film->increment('view_count');

        $userWatchlist = null;
        $userReview = null;
        $lastWatchedHistory = null;
        $reviews = collect();

        if (Auth::check()) {
            // Combine 3 queries into one batch query
            $userData = Auth::user()
                ->where('id', Auth::id())
                ->with([
                    'watchlists' => fn($q) => $q->where('film_id', $film->id),
                    'reviews' => fn($q) => $q->where('film_id', $film->id),
                    'watchHistories' => fn($q) => $q->where('film_id', $film->id),
                ])
                ->first();
            
            $userWatchlist = $userData?->watchlists->first();
            $userReview = $userData?->reviews->first();
            $lastWatchedHistory = $userData?->watchHistories->first();
            
            // Load reviews separately with limit to avoid loading all
            $reviews = $film->reviews()->with('user')->latest()->limit(20)->get();
        } else {
            $reviews = $film->reviews()->with('user')->latest()->limit(20)->get();
        }

        $relatedFilms = $this->filmSearch->getSimilarFilms($film, 6);

        return view('detail', compact('film', 'userWatchlist', 'userReview', 'lastWatchedHistory', 'relatedFilms', 'reviews'));
    }

    public function watch(Request $request, string $slug)
    {
        $film = Film::with(['genres', 'actors', 'seasons.episodes'])->where('slug', $slug)->first();

        if (!$film) {
            $subjectId = $slug;
            if (str_contains($slug, '-')) {
                $parts = explode('-', $slug);
                $subjectId = end($parts);
            }

            try {
                $apiDetail = $this->movieBox->getDetails($subjectId);
                $film = Film::fromApiData($apiDetail);
            } catch (Exception $e) {
                abort(404, 'Film tidak ditemukan.');
            }
        }

        if (!$this->isAllowedForActiveProfile($film)) {
            return redirect()->route('home')->with('error', 'Profil Anak tidak dapat mengakses film atau series dengan batasan usia ini.');
        }

        $season = (int)($request->query('season') ?? $request->query('se') ?? 0);
        $episode = (int)($request->query('episode') ?? $request->query('ep') ?? 0);
        $resParam = $request->query('resolution');

        if ($film->subject_type === 'series') {
            $this->syncSeriesStructure($film);
            $film->load('seasons.episodes');

            // Default to last watched or season 1 episode 1
            if ($season === 0 || $episode === 0) {
                if (Auth::check()) {
                    $history = WatchHistory::where('user_id', Auth::id())
                        ->where('profile_id', session('active_profile_id'))
                        ->where('film_id', $film->id)
                        ->first();
                    if ($history) {
                        $season = $history->season_number;
                        $episode = $history->episode_number;
                    }
                }
                if ($season === 0) {
                    $season = 1;
                    $episode = 1;
                }
            }

            // Record watch history for logged in user
            if (Auth::check()) {
                WatchHistory::updateOrCreate(
                    ['user_id' => Auth::id(), 'profile_id' => session('active_profile_id'), 'film_id' => $film->id],
                    ['season_number' => $season, 'episode_number' => $episode]
                );
            }
        }

        $resourcesData = [];
        if ($film->moviebox_subject_id) {
            try {
                $resourcesData = $this->movieBox->getResources($film->moviebox_subject_id, $season, $episode, 1, $resParam);
            } catch (Exception $e) {
            }
        }

        $resourceList = $resourcesData['list'] ?? (is_array($resourcesData) ? $resourcesData : []);
        $activeStream = null;

        if (!empty($resourceList)) {
            $h264Item = null;
            foreach ($resourceList as $resItem) {
                $codec = strtolower($resItem['codecName'] ?? '');
                if ($codec === 'h264' || $codec === 'avc') {
                    $h264Item = $resItem;
                    break;
                }
            }
            $selectedItem = $h264Item ?? $resourceList[0];
            $activeStream = $selectedItem['resourceLink'] ?? $selectedItem['url'] ?? $selectedItem['playUrl'] ?? null;

            // Compute real max resolution
            $resOrder = ['2160' => '4K', '1080' => '1080P', '720' => '720P', '480' => '480P', '360' => '360P', '240' => '240P'];
            $detectedRes = null;
            $allResolutions = array_filter(array_map(fn($r) => (int)($r['resolution'] ?? 0), $resourceList));
            if (!empty($allResolutions)) {
                $maxNum = max($allResolutions);
                foreach ($resOrder as $num => $label) {
                    if ($maxNum >= (int)$num) {
                        $detectedRes = $label;
                        break;
                    }
                }
            }
            if ($detectedRes && $film->max_resolution !== $detectedRes) {
                $film->update(['max_resolution' => $detectedRes]);
            }
        }

        // Get Next Episode for Series
        $activeEpisode = null;
        $nextEpisode = null;

        if ($film->subject_type === 'series') {
            $activeEpisode = Episode::whereHas('season', fn($q) => $q->where('film_id', $film->id)->where('season_number', $season))
                ->where('episode_number', $episode)
                ->first();

            if ($activeEpisode) {
                $nextEpisode = $activeEpisode->getNextEpisode();
            }
        }

        $proxyActiveStream = $activeStream ? url('/moviebox/proxy-stream') . '?url=' . urlencode($activeStream) : '';
        $subtitles = $film->moviebox_subject_id ? $this->movieBox->getCaptions($film->moviebox_subject_id, $season, $episode) : [];

        if (Auth::check()) {
            WatchHistory::firstOrCreate(
                ['user_id' => Auth::id(), 'film_id' => $film->id],
                [
                    'season_number'    => $season,
                    'episode_number'   => $episode,
                    'progress_seconds' => 0,
                ]
            )->touch();
        }

        // If AJAX request, return JSON for seamless episode switching without full page reload
        if ($request->wantsJson() || $request->ajax()) {
            $nextUrl = null;
            if ($nextEpisode) {
                $nextSeasonNum = $nextEpisode->season->season_number;
                $nextUrl = route('film.watch', $film->slug) . "?season={$nextSeasonNum}&episode={$nextEpisode->episode_number}";
            }

            return response()->json([
                'success'           => true,
                'season'            => $season,
                'episode'           => $episode,
                'activeStream'      => $activeStream,
                'proxyActiveStream' => $proxyActiveStream,
                'resourceList'      => $resourceList,
                'subtitles'         => $subtitles,
                'nextEpisode'       => $nextEpisode ? [
                    'season_number'  => $nextEpisode->season->season_number,
                    'episode_number' => $nextEpisode->episode_number,
                    'title'          => $nextEpisode->title,
                    'url'            => $nextUrl,
                ] : null,
            ]);
        }

        $relatedFilms = $this->filmSearch->getSimilarFilms($film, 6);

        return view('watch', compact(
            'film',
            'resourceList',
            'activeStream',
            'proxyActiveStream',
            'subtitles',
            'season',
            'episode',
            'activeEpisode',
            'nextEpisode',
            'relatedFilms'
        ));
    }

    /**
     * Update user watch history progress (seconds watched)
     */
    public function updateProgress(Request $request)
    {
        $request->validate([
            'film_id'          => 'required|exists:films,id',
            'season_number'    => 'required|integer',
            'episode_number'   => 'required|integer',
            'progress_seconds' => 'required|integer',
        ]);

        if (Auth::check()) {
            \DB::transaction(function () use ($request) {
                $history = \App\Models\WatchHistory::where('user_id', Auth::id())
                    ->where('profile_id', session('active_profile_id'))
                    ->where('film_id', $request->film_id)
                    ->lockForUpdate()
                    ->first();

                if ($history) {
                    $isNewer = ($request->season_number > $history->season_number) ||
                               ($request->season_number == $history->season_number && 
                                $request->episode_number > $history->episode_number) ||
                               ($request->season_number == $history->season_number && 
                                $request->episode_number == $history->episode_number &&
                                $request->progress_seconds > $history->progress_seconds);

                    if ($isNewer) {
                        $history->update([
                            'season_number'    => $request->season_number,
                            'episode_number'   => $request->episode_number,
                            'progress_seconds' => $request->progress_seconds,
                        ]);
                    }
                } else {
                    \App\Models\WatchHistory::create([
                        'user_id'          => Auth::id(),
                        'profile_id'       => session('active_profile_id'),
                        'film_id'          => $request->film_id,
                        'season_number'    => $request->season_number,
                        'episode_number'   => $request->episode_number,
                        'progress_seconds' => $request->progress_seconds,
                    ]);
                }
            });
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Helper to sync seasons & episodes structure from MovieBox API for TV series
     */
    protected function syncSeriesStructure(Film $film): void
    {
        if ($film->subject_type !== 'series' || !$film->moviebox_subject_id) return;

        try {
            $details = $this->movieBox->getDetails($film->moviebox_subject_id);
            $seasonsData = $details['seasons']['seasons'] ?? [];

            if (empty($seasonsData)) {
                $seasonInfo = $this->movieBox->get('/wefeed-mobile-bff/subject-api/season-info?subjectId=' . $film->moviebox_subject_id);
                $seasonsData = $seasonInfo['seasons'] ?? [];
            }

            foreach ($seasonsData as $sData) {
                $seNum = (int)($sData['se'] ?? 1);
                $maxEp = (int)($sData['maxEp'] ?? 1);

                $season = Season::firstOrCreate(
                    ['film_id' => $film->id, 'season_number' => $seNum],
                    ['title' => "Season {$seNum}", 'poster_url' => $film->poster_url, 'release_year' => $film->release_year]
                );

                // Fetch real episode durations from MovieBox resources API
                $epDurations = [];
                try {
                    $resData = $this->movieBox->getResources($film->moviebox_subject_id, $seNum, 0);
                    $resList = $resData['list'] ?? [];
                    foreach ($resList as $rItem) {
                        $eNum = (int)($rItem['ep'] ?? 0);
                        $dSec = (int)($rItem['duration'] ?? 0);
                        if ($eNum > 0 && $dSec > 0) {
                            $epDurations[$eNum] = (int)round($dSec / 60);
                        }
                    }
                } catch (Exception $e) {}

                for ($epNum = 1; $epNum <= $maxEp; $epNum++) {
                    $realDuration = $epDurations[$epNum] ?? ($film->duration_minutes > 0 && $film->duration_minutes != 120 ? $film->duration_minutes : 45);

                    $episode = Episode::firstOrCreate(
                        ['season_id' => $season->id, 'episode_number' => $epNum],
                        [
                            'title'            => "Episode {$epNum}",
                            'synopsis'         => "Episode {$epNum} of Season {$seNum}",
                            'duration_minutes' => $realDuration,
                            'thumbnail_url'    => $film->backdrop_url ?: $film->poster_url,
                        ]
                    );

                    if (isset($epDurations[$epNum]) && $episode->duration_minutes != $epDurations[$epNum]) {
                        $episode->update(['duration_minutes' => $epDurations[$epNum]]);
                    }
                }
            }
        } catch (Exception $e) {
            // Fail gracefully if API season info fails
        }
    }

    protected function isAllowedForActiveProfile(?Film $film): bool
    {
        if (!$film) {
            return true;
        }

        if (!Auth::check()) {
            return true;
        }

        $user = Auth::user();
        $activeProfile = method_exists($user, 'activeProfile') ? $user->activeProfile() : null;
        if (!$activeProfile) {
            return true;
        }

        // Child Profile restrictions
        if ($activeProfile->is_child) {
            $allowedRatings = ['SU', 'G', 'PG', null];
            if (!in_array($film->content_rating, $allowedRatings, true)) {
                return false;
            }
        }

        // Max rating restrictions
        if ($activeProfile->max_content_rating) {
            $ratingOrder = ['SU' => 1, 'G' => 1, 'PG' => 2, '13+' => 3, '16+' => 4, '18+' => 5];
            $filmRatingVal = $ratingOrder[$film->content_rating ?? 'SU'] ?? 1;
            $maxRatingVal = $ratingOrder[$activeProfile->max_content_rating] ?? 5;
            if ($filmRatingVal > $maxRatingVal) {
                return false;
            }
        }

        return true;
    }
}
