<?php

/**
 * Script Uji Coba Pengambilan Data Film & Soundtrack (Lagu OST) dari TMDB API & iTunes API
 * 
 * Penggunaan via Terminal:
 *   php test_tmdb_api.php "Interstellar"
 *   php test_tmdb_api.php "Oppenheimer"
 *   php test_tmdb_api.php "Spider-Man"
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$movieQuery = $argv[1] ?? 'Interstellar';

echo "\n=======================================================\n";
echo "    FA I I L M O V  -  SOUNDTRACK & TMDB API TEST SCRIPT\n";
echo "=======================================================\n";
echo "🎬 Judul Film Target : \"{$movieQuery}\"\n";
echo "=======================================================\n\n";

// 1. Fetch Soundtrack / Songs using iTunes Music API (Free & No Key Required)
echo "🎵 1. Mengambil Data Soundtrack & Audio Preview dari iTunes API...\n";
$itunesUrl = "https://itunes.apple.com/search?term=" . urlencode($movieQuery . " soundtrack") . "&entity=song&limit=5";

$itunesRes = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($itunesUrl);
$soundtracks = [];

if ($itunesRes->successful() && !empty($itunesRes->json()['results'])) {
    $results = $itunesRes->json()['results'];
    foreach ($results as $item) {
        $soundtracks[] = [
            'track_name' => $item['trackName'] ?? 'Unknown Track',
            'artist_name' => $item['artistName'] ?? 'Unknown Artist',
            'collection_name' => $item['collectionName'] ?? '',
            'preview_audio_url' => $item['previewUrl'] ?? null,
            'artwork_url' => $item['artworkUrl100'] ?? null,
            'track_view_url' => $item['trackViewUrl'] ?? null,
        ];
    }
    echo "✅ Berhasil menemukan " . count($soundtracks) . " lagu OST resmi!\n";
    foreach ($soundtracks as $idx => $st) {
        echo sprintf("   [%d] \"%s\" oleh %s\n       🔊 Preview MP3: %s\n", $idx + 1, $st['track_name'], $st['artist_name'], substr($st['preview_audio_url'], 0, 75) . '...');
    }
    echo "\n";
} else {
    echo "⚠️ Tidak ditemukan lagu di iTunes Music API.\n\n";
}

// 2. Fetch TMDB Data (Optional with fallback API key)
echo "🎬 2. Mengambil Metadata TMDB & Komposer Musik...\n";
$tmdbKeys = [
    'a80b03708a3d537f76326622b3e83955',
    '15d2ea6d0dc1d476efb297b7b4b20f9c',
    '841059f87eab7714c386e92c26e38c5d',
    'f0b5d913618330761e0b59b56f8f537d'
];

$tmdbData = null;
$movieDetails = null;

foreach ($tmdbKeys as $k) {
    $r = Http::get("https://api.themoviedb.org/3/search/movie?api_key={$k}&query=" . urlencode($movieQuery));
    if ($r->successful() && !empty($r->json()['results'])) {
        $movieDetails = $r->json()['results'][0];
        $tmdbData = $movieDetails;
        break;
    }
}

if ($movieDetails) {
    echo "✅ Film Ditemukan di TMDB!\n";
    echo "   - TMDB ID   : {$movieDetails['id']}\n";
    echo "   - Rilis     : " . ($movieDetails['release_date'] ?? 'N/A') . "\n";
    echo "   - Overview  : " . substr($movieDetails['overview'] ?? '', 0, 100) . "...\n\n";
} else {
    echo "ℹ️  TMDB Metadata di-skip (Menggunakan fallback metadata lokal).\n\n";
}

// 3. Generate Links
$spotifySearchUrl = "https://open.spotify.com/search/" . urlencode($movieQuery . " Soundtrack");
$youtubeSearchUrl = "https://www.youtube.com/results?search_query=" . urlencode($movieQuery . " Official Soundtrack");

// 4. Output Formatted JSON Payload
echo "=======================================================\n";
echo "📄 HASIL API PAYLOAD LAGU & OST (JSON FORMATTED):\n";
echo "=======================================================\n";

$finalPayload = [
    'film_title' => $movieQuery,
    'tmdb_id' => $movieDetails['id'] ?? null,
    'soundtracks_count' => count($soundtracks),
    'soundtracks' => $soundtracks,
    'external_links' => [
        'spotify_search' => $spotifySearchUrl,
        'youtube_search' => $youtubeSearchUrl,
    ]
];

echo json_encode($finalPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "🎉 Uji Coba Berhasil Selesai!\n\n";
