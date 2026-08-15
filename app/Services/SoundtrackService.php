<?php

namespace App\Services;

use App\Models\Film;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class SoundtrackService
{
    /**
     * Fetch soundtracks for a film:
     * 1. Priority: Manual soundtracks added in admin
     * 2. Fallback: iTunes Music API with caching
     */
    public function getSoundtracksForFilm(Film $film, int $limit = 6): array
    {
        // 1. Check if film has manually curated soundtracks
        $manualTracks = $film->soundtracks()->get();
        if ($manualTracks->isNotEmpty()) {
            return $manualTracks->map(function ($st) use ($film) {
                return [
                    'id' => $st->id,
                    'track_name' => $st->track_name,
                    'artist_name' => $st->artist_name,
                    'collection_name' => $st->collection_name ?? '',
                    'preview_audio_url' => $st->preview_audio_url,
                    'artwork_url' => $st->artwork_url ?: 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=300',
                    'track_view_url' => $st->track_view_url ?: ("https://open.spotify.com/search/" . urlencode($film->title . " " . $st->track_name)),
                    'is_manual' => true,
                ];
            })->toArray();
        }

        // 2. Fallback to iTunes API with caching
        $cacheKey = "film_soundtracks_v2_" . $film->id;

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($film, $limit) {
            try {
                $cleanTitle = preg_replace('/[^\w\s]/u', ' ', $film->title);
                $cleanTitle = trim(preg_replace('/\s+/', ' ', $cleanTitle));

                $url = "https://itunes.apple.com/search?term=" . urlencode($cleanTitle . " soundtrack") . "&entity=song&limit=" . $limit;
                $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(5)->get($url);

                if ($response->successful() && !empty($response->json()['results'])) {
                    $results = $response->json()['results'];
                    $soundtracks = [];

                    foreach ($results as $item) {
                        $soundtracks[] = [
                            'track_name' => $item['trackName'] ?? 'Unknown Track',
                            'artist_name' => $item['artistName'] ?? 'Unknown Artist',
                            'collection_name' => $item['collectionName'] ?? '',
                            'preview_audio_url' => $item['previewUrl'] ?? null,
                            'artwork_url' => isset($item['artworkUrl100']) ? str_replace('100x100bb', '300x300bb', $item['artworkUrl100']) : null,
                            'track_view_url' => $item['trackViewUrl'] ?? ("https://open.spotify.com/search/" . urlencode($film->title . " " . ($item['trackName'] ?? ''))),
                            'is_manual' => false,
                        ];
                    }

                    if (!empty($soundtracks)) {
                        return $soundtracks;
                    }
                }
            } catch (Exception $e) {
                // Fail silently
            }

            return [];
        });
    }

    /**
     * Search songs from iTunes Music API for admin picker & quick import
     */
    public function searchItunesApi(string $query, int $limit = 12): array
    {
        $cleanQuery = trim($query);
        if (strlen($cleanQuery) < 2) {
            return [];
        }

        try {
            $url = "https://itunes.apple.com/search?term=" . urlencode($cleanQuery) . "&entity=song&limit=" . $limit;
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(6)->get($url);

            if ($response->successful() && !empty($response->json()['results'])) {
                $results = $response->json()['results'];
                $tracks = [];

                foreach ($results as $item) {
                    $tracks[] = [
                        'track_name' => $item['trackName'] ?? 'Unknown Track',
                        'artist_name' => $item['artistName'] ?? 'Unknown Artist',
                        'collection_name' => $item['collectionName'] ?? '',
                        'preview_audio_url' => $item['previewUrl'] ?? null,
                        'artwork_url' => isset($item['artworkUrl100']) ? str_replace('100x100bb', '600x600bb', $item['artworkUrl100']) : null,
                        'track_view_url' => $item['trackViewUrl'] ?? null,
                    ];
                }

                return $tracks;
            }
        } catch (Exception $e) {
            // Fail silently
        }

        return [];
    }
}
