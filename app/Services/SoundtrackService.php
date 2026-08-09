<?php

namespace App\Services;

use App\Models\Film;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class SoundtrackService
{
    /**
     * Fetch soundtracks for a film from iTunes Music API with caching
     */
    public function getSoundtracksForFilm(Film $film, int $limit = 6): array
    {
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
                            'track_view_url' => $item['trackViewUrl'] ?? "https://open.spotify.com/search/" . urlencode($film->title . " " . ($item['trackName'] ?? '')),
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
}
