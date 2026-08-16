<?php

namespace App\Services;

use App\Models\Genre;
use App\Models\Actor;
use App\Models\Film;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class ImdbService
{
    public function __construct(
        protected SoundtrackService $soundtrackService,
        protected ?MovieBoxService $movieBox = null
    ) {}

    /**
     * Extract IMDb ID (e.g. tt1375666) from input link or string
     */
    public function extractImdbId(string $input): ?string
    {
        $clean = trim($input);

        // Matches https://www.imdb.com/title/tt1375666/... or tt1375666
        if (preg_match('/(tt\d{5,11})/i', $clean, $m)) {
            return strtolower($m[1]);
        }

        // Numeric only IMDb ID
        if (preg_match('/^\d{5,10}$/', $clean)) {
            return 'tt' . str_pad($clean, 7, '0', STR_PAD_LEFT);
        }

        return null;
    }

    /**
     * Fetch complete film metadata, cast, and OST from IMDb link
     */
    public function fetchFilmData(string $imdbInput): ?array
    {
        $imdbId = $this->extractImdbId($imdbInput);

        if (!$imdbId) {
            // Try searching IMDb by title if not a direct URL/ID
            $imdbId = $this->searchImdbIdByTitle($imdbInput);
        }

        if (!$imdbId) {
            return null;
        }

        $cacheKey = "imdb_fetch_data_v3_" . $imdbId;

        return Cache::remember($cacheKey, 3600, function () use ($imdbId) {
            try {
                $html = $this->fetchImdbPage("https://www.imdb.com/title/{$imdbId}/");
                if (empty($html)) {
                    return null;
                }

                $jsonLd = $this->extractJsonLd($html);
                $nextData = $this->extractNextData($html);

                // 1. Title
                $title = $this->extractTitle($jsonLd, $nextData, $html);
                if (empty($title)) {
                    return null;
                }

                // 2. Release Year & Date
                $releaseYear = $this->extractReleaseYear($jsonLd, $nextData, $html);
                $availableFrom = null;

                // 3. Duration in Minutes
                $duration = $this->extractDuration($jsonLd, $nextData, $html);

                // 4. Rating (Scale 0.0 - 5.0)
                $rating = $this->extractRating($jsonLd, $nextData, $html);

                // 5. Content Rating
                $contentRating = $this->extractContentRating($jsonLd, $nextData, $html);

                // 6. Subject Type (movie, series, dracin)
                $subjectType = $this->extractSubjectType($jsonLd, $nextData, $html, $title);

                // 7. Synopsis
                $synopsis = $this->extractSynopsis($jsonLd, $nextData, $html);

                // 8. Poster URL
                $posterUrl = $this->extractPosterUrl($jsonLd, $nextData, $html);

                // 9. Backdrop URL
                $backdropUrl = $this->extractBackdropUrl($jsonLd, $nextData, $html);

                // 10. Trailer URL
                $trailerUrl = $this->extractTrailerUrl($jsonLd, $nextData, $html, $title, $releaseYear);

                // 11. Genres
                $genres = $this->extractGenres($jsonLd, $nextData, $html);

                // 12. Cast & Actors
                $actors = $this->extractActors($jsonLd, $nextData, $html);

                // 13. Soundtracks (OST) - Fetch from IMDb soundtrack page & enrich via iTunes
                $soundtracks = $this->fetchSoundtracks($imdbId, $title, $releaseYear);

                // 14. Try matching with MovieBox for stream subject ID
                $movieboxSubjectId = $this->findMovieBoxSubjectId($title, $releaseYear, $subjectType);

                return [
                    'imdb_id' => $imdbId,
                    'title' => $title,
                    'synopsis' => $synopsis,
                    'release_year' => $releaseYear,
                    'duration_minutes' => $duration,
                    'rating' => $rating,
                    'content_rating' => $contentRating,
                    'subject_type' => $subjectType,
                    'max_resolution' => '1080P',
                    'poster_url' => $posterUrl,
                    'backdrop_url' => $backdropUrl,
                    'trailer_url' => $trailerUrl,
                    'available_from' => $availableFrom,
                    'moviebox_subject_id' => $movieboxSubjectId,
                    'genres' => $genres,
                    'actors' => $actors,
                    'soundtracks' => $soundtracks,
                ];
            } catch (Exception $e) {
                Log::error("ImdbService fetch failed for {$imdbId}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Fetch HTML content with spoofed browser headers
     */
    protected function fetchImdbPage(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
                'Sec-Ch-Ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
                'Sec-Ch-Ua-Mobile' => '?0',
                'Sec-Ch-Ua-Platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ])->timeout(12)->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (Exception $e) {
            Log::warning("IMDb page fetch request failed for {$url}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Search IMDb suggestion API to find IMDb ID by title
     */
    protected function searchImdbIdByTitle(string $title): ?string
    {
        $cleanTitle = preg_replace('/[^\w\s]/u', '', trim($title));
        if (strlen($cleanTitle) < 2) {
            return null;
        }

        $prefix = strtolower(substr($cleanTitle, 0, 1));
        $encoded = urlencode(strtolower(str_replace(' ', '_', $cleanTitle)));

        try {
            $url = "https://v3.sg.media-imdb.com/suggestion/{$prefix}/{$encoded}.json";
            $res = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(6)->get($url);

            if ($res->successful() && !empty($res->json()['d'])) {
                foreach ($res->json()['d'] as $item) {
                    if (!empty($item['id']) && str_starts_with($item['id'], 'tt')) {
                        return $item['id'];
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore
        }

        return null;
    }

    /**
     * Extract JSON-LD array from HTML
     */
    protected function extractJsonLd(string $html): array
    {
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            $decoded = json_decode($matches[1], true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Extract Next.js __NEXT_DATA__ array from HTML
     */
    protected function extractNextData(string $html): array
    {
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
            $decoded = json_decode($matches[1], true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Extract Film Title
     */
    protected function extractTitle(array $jsonLd, array $nextData, string $html): string
    {
        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['titleText']['text'])) {
            return trim($nextData['props']['pageProps']['aboveTheFoldData']['titleText']['text']);
        }

        if (!empty($jsonLd['name'])) {
            return trim(html_entity_decode($jsonLd['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<meta property="og:title" content="(.*?)(?:\s*\([^)]*\))?\s*-\s*IMDb"\/?>/i', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<title>(.*?)(?:\s*\([^)]*\))?\s*-\s*IMDb<\/title>/i', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    }

    /**
     * Extract Release Year
     */
    protected function extractReleaseYear(array $jsonLd, array $nextData, string $html): int
    {
        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['releaseYear']['year'])) {
            return (int)$nextData['props']['pageProps']['aboveTheFoldData']['releaseYear']['year'];
        }

        if (!empty($jsonLd['datePublished'])) {
            if (preg_match('/(\d{4})/', $jsonLd['datePublished'], $m)) {
                return (int)$m[1];
            }
        }

        if (preg_match('/<title>[^<]*\((\d{4})\)[^<]*<\/title>/i', $html, $m)) {
            return (int)$m[1];
        }

        return (int)date('Y');
    }

    /**
     * Extract Duration in minutes
     */
    protected function extractDuration(array $jsonLd, array $nextData, string $html): int
    {
        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['runtime']['seconds'])) {
            return max(1, (int)round($nextData['props']['pageProps']['aboveTheFoldData']['runtime']['seconds'] / 60));
        }

        if (!empty($jsonLd['duration'])) {
            $durationStr = $jsonLd['duration']; // e.g. PT2H28M, PT148M
            $hours = 0;
            $mins = 0;
            if (preg_match('/(\d+)H/i', $durationStr, $m)) {
                $hours = (int)$m[1];
            }
            if (preg_match('/(\d+)M/i', $durationStr, $m)) {
                $mins = (int)$m[1];
            }
            $total = ($hours * 60) + $mins;
            if ($total > 0) {
                return $total;
            }
        }

        return 120;
    }

    /**
     * Extract Rating (converted to 0.0 - 5.0 scale)
     */
    protected function extractRating(array $jsonLd, array $nextData, string $html): float
    {
        $rawRating = null;

        if (isset($nextData['props']['pageProps']['aboveTheFoldData']['ratingsSummary']['aggregateRating'])) {
            $rawRating = (float)$nextData['props']['pageProps']['aboveTheFoldData']['ratingsSummary']['aggregateRating'];
        } elseif (isset($jsonLd['aggregateRating']['ratingValue'])) {
            $rawRating = (float)$jsonLd['aggregateRating']['ratingValue'];
        }

        if ($rawRating !== null && $rawRating > 0) {
            // IMDb is out of 10.0 -> convert to our 5.0 scale (e.g. 8.8 -> 4.4)
            return round($rawRating / 2, 1);
        }

        return 4.5;
    }

    /**
     * Extract Content Rating (SU, 13+, 16+, 18+)
     */
    protected function extractContentRating(array $jsonLd, array $nextData, string $html): string
    {
        $cert = '';

        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['certificate']['rating'])) {
            $cert = strtoupper(trim($nextData['props']['pageProps']['aboveTheFoldData']['certificate']['rating']));
        } elseif (!empty($jsonLd['contentRating'])) {
            $cert = strtoupper(trim($jsonLd['contentRating']));
        }

        if (in_array($cert, ['R', 'NC-17', '18+', 'TV-MA', '21+'], true)) {
            return '18+';
        }
        if (in_array($cert, ['16+', 'TV-14', 'M'], true)) {
            return '16+';
        }
        if (in_array($cert, ['13+', 'PG-13', 'PG'], true)) {
            return '13+';
        }
        if (in_array($cert, ['SU', 'G', 'TV-Y', 'TV-Y7', 'TV-G', 'ALL'], true)) {
            return 'SU';
        }

        return '13+';
    }

    /**
     * Extract Subject Type (movie, series, dracin)
     */
    protected function extractSubjectType(array $jsonLd, array $nextData, string $html, string $title): string
    {
        $type = $jsonLd['@type'] ?? '';
        $titleType = $nextData['props']['pageProps']['aboveTheFoldData']['titleType']['id'] ?? '';

        $isSeries = (
            str_contains(strtolower($type), 'tvseries') ||
            str_contains(strtolower($type), 'tvminiseries') ||
            in_array(strtolower($titleType), ['tvseries', 'tvminiseries', 'tvepisode'], true)
        );

        $lowerHtml = strtolower($html);
        $lowerTitle = strtolower($title);

        $isChinese = (
            str_contains($lowerHtml, 'china') ||
            str_contains($lowerHtml, 'chinese') ||
            str_contains($lowerHtml, 'c-drama') ||
            str_contains($lowerHtml, 'mandarin') ||
            preg_match('/[\x{4e00}-\x{9fa5}]/u', $title)
        );

        if ($isSeries && $isChinese) {
            return 'dracin';
        }
        if ($isSeries) {
            return 'series';
        }
        if ($isChinese && (str_contains($lowerHtml, 'drama') || str_contains($lowerHtml, 'romance'))) {
            return 'dracin';
        }

        return 'movie';
    }

    /**
     * Extract Synopsis
     */
    protected function extractSynopsis(array $jsonLd, array $nextData, string $html): string
    {
        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['plot']['plotText']['plainText'])) {
            return trim($nextData['props']['pageProps']['aboveTheFoldData']['plot']['plotText']['plainText']);
        }

        if (!empty($jsonLd['description'])) {
            return trim(html_entity_decode($jsonLd['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<meta property="og:description" content="(.*?)"\/?>/i', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    }

    /**
     * Extract Poster URL
     */
    protected function extractPosterUrl(array $jsonLd, array $nextData, string $html): string
    {
        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['primaryImage']['url'])) {
            return $nextData['props']['pageProps']['aboveTheFoldData']['primaryImage']['url'];
        }

        if (!empty($jsonLd['image'])) {
            return is_string($jsonLd['image']) ? $jsonLd['image'] : ($jsonLd['image']['url'] ?? '');
        }

        if (preg_match('/<meta property="og:image" content="(.*?)"\/?>/i', $html, $m)) {
            return $m[1];
        }

        return 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=80&w=600';
    }

    /**
     * Extract Backdrop / Banner URL
     */
    protected function extractBackdropUrl(array $jsonLd, array $nextData, string $html): ?string
    {
        // Check mainColumnData for image gallery or titleMainImages
        if (!empty($nextData['props']['pageProps']['mainColumnData']['titleMainImages']['edges'])) {
            foreach ($nextData['props']['pageProps']['mainColumnData']['titleMainImages']['edges'] as $edge) {
                $node = $edge['node'] ?? [];
                if (!empty($node['url']) && ($node['width'] ?? 0) > ($node['height'] ?? 0)) {
                    return $node['url'];
                }
            }
        }

        // Fallback to high res poster
        $poster = $this->extractPosterUrl($jsonLd, $nextData, $html);
        return $poster ?: null;
    }

    /**
     * Extract Trailer URL
     */
    protected function extractTrailerUrl(array $jsonLd, array $nextData, string $html, string $title, int $year): ?string
    {
        // 1. Check direct playback URL from IMDb
        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['primaryVideos']['edges'][0]['node']['playbackURLs'])) {
            $playbackUrls = $nextData['props']['pageProps']['aboveTheFoldData']['primaryVideos']['edges'][0]['node']['playbackURLs'];
            foreach ($playbackUrls as $pUrl) {
                if (!empty($pUrl['url'])) {
                    return $pUrl['url'];
                }
            }
        }

        // 2. Check JSON-LD trailer embedUrl
        if (!empty($jsonLd['trailer']['embedUrl'])) {
            return $jsonLd['trailer']['embedUrl'];
        }

        // 3. Fallback to YouTube Trailer Search URL
        $ytQuery = urlencode("{$title} {$year} official trailer");
        return "https://www.youtube.com/results?search_query={$ytQuery}";
    }

    /**
     * Extract Genres List
     */
    protected function extractGenres(array $jsonLd, array $nextData, string $html): array
    {
        $genres = [];

        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['genres']['genres'])) {
            foreach ($nextData['props']['pageProps']['aboveTheFoldData']['genres']['genres'] as $g) {
                if (!empty($g['text'])) {
                    $genres[] = trim($g['text']);
                }
            }
        }

        if (empty($genres) && !empty($jsonLd['genre'])) {
            $rawGenres = is_array($jsonLd['genre']) ? $jsonLd['genre'] : [$jsonLd['genre']];
            foreach ($rawGenres as $g) {
                $genres[] = trim($g);
            }
        }

        // Filter and deduplicate
        $genres = array_unique(array_filter($genres));

        return array_values($genres);
    }

    /**
     * Extract Cast and Actors
     */
    protected function extractActors(array $jsonLd, array $nextData, string $html): array
    {
        $actors = [];

        // Try from NEXT_DATA cast
        if (!empty($nextData['props']['pageProps']['aboveTheFoldData']['castPageTitle']['edges'])) {
            $edges = $nextData['props']['pageProps']['aboveTheFoldData']['castPageTitle']['edges'];
            foreach ($edges as $index => $edge) {
                $node = $edge['node'] ?? [];
                $name = $node['name']['nameText']['text'] ?? null;
                $photo = $node['name']['primaryImage']['url'] ?? null;
                $charName = $node['characters'][0]['name'] ?? null;

                if ($name) {
                    $actors[] = [
                        'name' => trim($name),
                        'photo_url' => $photo,
                        'character_name' => $charName ? trim($charName) : null,
                        'role_type' => $index < 3 ? 'main' : 'regular',
                    ];
                }
            }
        }

        // Fallback from JSON-LD actors
        if (empty($actors) && !empty($jsonLd['actor'])) {
            $rawActors = is_array($jsonLd['actor']) ? $jsonLd['actor'] : [$jsonLd['actor']];
            foreach ($rawActors as $index => $act) {
                $name = is_array($act) ? ($act['name'] ?? null) : $act;
                if ($name) {
                    $actors[] = [
                        'name' => trim($name),
                        'photo_url' => null,
                        'character_name' => null,
                        'role_type' => $index < 3 ? 'main' : 'regular',
                    ];
                }
            }
        }

        return array_slice($actors, 0, 15);
    }

    /**
     * Fetch Soundtracks (OST):
     * 1. Scrapes track titles from IMDb soundtrack page: https://www.imdb.com/title/{imdbId}/soundtrack/
     * 2. Enriches each track using iTunes Search API to get playable 30s preview MP3, artwork, Spotify link
     * 3. Fallback: Searches iTunes API for '{Title} Soundtrack' if IMDb soundtrack list is empty
     */
    public function fetchSoundtracks(string $imdbId, string $filmTitle, int $releaseYear): array
    {
        $tracks = [];
        $stHtml = $this->fetchImdbPage("https://www.imdb.com/title/{$imdbId}/soundtrack/");

        if (!empty($stHtml)) {
            // Check Next.js soundtrack items
            $stNext = $this->extractNextData($stHtml);
            $stProps = $stNext['props']['pageProps']['contentData']['section']['items'] ?? [];

            if (!empty($stProps)) {
                foreach ($stProps as $item) {
                    $rawTitle = $item['rowTitle'] ?? '';
                    $rawText = $item['text'] ?? '';
                    $cleanTrack = trim(preg_replace('/^"|"$/', '', $rawTitle));

                    // Extract performer/artist
                    $artist = 'Various Artists';
                    if (preg_match('/Performed by\s+([^,\n\r\.\<\(]+)/i', $rawText, $m)) {
                        $artist = trim($m[1]);
                    } elseif (preg_match('/Written by\s+([^,\n\r\.\<\(]+)/i', $rawText, $m)) {
                        $artist = trim($m[1]);
                    }

                    if (!empty($cleanTrack)) {
                        $tracks[] = [
                            'track_name' => $cleanTrack,
                            'artist_name' => $artist,
                        ];
                    }
                }
            }

            // If empty, extract via regex from HTML content
            if (empty($tracks)) {
                if (preg_match_all('/<div class="ipc-html-content-inner-div">(.*?)<\/div>/s', $stHtml, $matches)) {
                    foreach ($matches[1] as $block) {
                        $plain = strip_tags($block);
                        if (preg_match('/^"([^"]+)"/i', trim($plain), $tm)) {
                            $trackTitle = trim($tm[1]);
                            $artist = 'Various Artists';
                            if (preg_match('/Performed by\s+([^,\n\r\.\<\(]+)/i', $plain, $am)) {
                                $artist = trim($am[1]);
                            } elseif (preg_match('/Written by\s+([^,\n\r\.\<\(]+)/i', $plain, $am)) {
                                $artist = trim($am[1]);
                            }

                            $tracks[] = [
                                'track_name' => $trackTitle,
                                'artist_name' => $artist,
                            ];
                        }
                    }
                }
            }
        }

        // Limit tracks to top 15
        $tracks = array_slice($tracks, 0, 15);

        $enrichedTracks = [];
        $order = 1;

        // Enrich extracted tracks with iTunes Music API audio previews & artworks
        foreach ($tracks as $t) {
            $query = "{$filmTitle} {$t['track_name']}";
            $itunesResults = $this->soundtrackService->searchItunesApi($query, 1);

            if (!empty($itunesResults[0])) {
                $it = $itunesResults[0];
                $enrichedTracks[] = [
                    'track_name' => $t['track_name'],
                    'artist_name' => !empty($it['artist_name']) && $it['artist_name'] !== 'Unknown Artist' ? $it['artist_name'] : $t['artist_name'],
                    'collection_name' => $it['collection_name'] ?: "{$filmTitle} (Original Soundtrack)",
                    'preview_audio_url' => $it['preview_audio_url'],
                    'artwork_url' => $it['artwork_url'],
                    'track_view_url' => $it['track_view_url'] ?: ("https://open.spotify.com/search/" . urlencode("{$filmTitle} {$t['track_name']}")),
                    'order' => $order++,
                ];
            } else {
                $enrichedTracks[] = [
                    'track_name' => $t['track_name'],
                    'artist_name' => $t['artist_name'],
                    'collection_name' => "{$filmTitle} (Original Soundtrack)",
                    'preview_audio_url' => null,
                    'artwork_url' => null,
                    'track_view_url' => "https://open.spotify.com/search/" . urlencode("{$filmTitle} {$t['track_name']}"),
                    'order' => $order++,
                ];
            }
        }

        // If no tracks found from IMDb soundtrack page, fallback to iTunes Soundtrack search
        if (empty($enrichedTracks)) {
            $itunesSoundtracks = $this->soundtrackService->searchItunesApi("{$filmTitle} soundtrack", 10);
            if (empty($itunesSoundtracks)) {
                $itunesSoundtracks = $this->soundtrackService->searchItunesApi("{$filmTitle} OST", 8);
            }

            foreach ($itunesSoundtracks as $st) {
                $enrichedTracks[] = [
                    'track_name' => $st['track_name'],
                    'artist_name' => $st['artist_name'],
                    'collection_name' => $st['collection_name'] ?: "{$filmTitle} (Original Soundtrack)",
                    'preview_audio_url' => $st['preview_audio_url'],
                    'artwork_url' => $st['artwork_url'],
                    'track_view_url' => $st['track_view_url'],
                    'order' => $order++,
                ];
            }
        }

        return $enrichedTracks;
    }

    /**
     * Search upstream MovieBox API to connect streaming subject ID if available
     */
    protected function findMovieBoxSubjectId(string $title, int $year, string $subjectType): ?string
    {
        if (!$this->movieBox) {
            try {
                $this->movieBox = app(MovieBoxService::class);
            } catch (Exception $e) {
                return null;
            }
        }

        try {
            $cleanTitle = trim($title);
            $searchRes = $this->movieBox->search($cleanTitle, 1);

            if (!empty($searchRes)) {
                $subjects = Film::extractSearchSubjects($searchRes);
                if (!empty($subjects)) {
                    $cleanTitleLower = strtolower(trim($title));

                    foreach ($subjects as $subj) {
                        $sTitle = strtolower(trim($subj['title'] ?? $subj['subjectName'] ?? ''));
                        $sYear = (int)($subj['releaseYear'] ?? $subj['year'] ?? 0);
                        $sId = (string)($subj['subjectId'] ?? $subj['id'] ?? '');

                        if (!empty($sId)) {
                            // Exact title match or match within 1 year
                            if ($sTitle === $cleanTitleLower || str_contains($sTitle, $cleanTitleLower)) {
                                if ($year <= 0 || $sYear === 0 || abs($year - $sYear) <= 1) {
                                    return $sId;
                                }
                            }
                        }
                    }

                    // If close match not verified, return the top result subject ID if titles closely resemble
                    $topSubj = $subjects[0];
                    $topTitle = strtolower(trim($topSubj['title'] ?? $topSubj['subjectName'] ?? ''));
                    if (similar_text($cleanTitleLower, $topTitle) > (strlen($cleanTitleLower) * 0.7)) {
                        return (string)($topSubj['subjectId'] ?? $topSubj['id'] ?? null);
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore upstream search failure
        }

        return null;
    }
}
