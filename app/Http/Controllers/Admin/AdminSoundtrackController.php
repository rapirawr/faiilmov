<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\Soundtrack;
use App\Models\AdminActivityLog;
use App\Services\SoundtrackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class AdminSoundtrackController extends Controller
{
    /**
     * Search songs from iTunes API for admin preview / quick import
     */
    public function searchApi(Request $request, SoundtrackService $soundtrackService)
    {
        $query = $request->input('q', '');
        $results = $soundtrackService->searchItunesApi($query, 15);

        return response()->json($results);
    }

    /**
     * Batch import songs from iTunes into the film's database
     */
    public function importBatch(Request $request, Film $film, SoundtrackService $soundtrackService)
    {
        $tracks = $request->input('tracks');

        if (empty($tracks) || !is_array($tracks)) {
            $tracks = $soundtrackService->searchItunesApi($film->title . ' soundtrack', 10);
        }

        if (empty($tracks)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Tidak ada lagu yang ditemukan untuk diimpor.'], 404);
            }
            return back()->with('error', 'Tidak ada lagu yang ditemukan untuk diimpor.');
        }

        $importedCount = 0;
        $currentOrder = $film->soundtracks()->count();

        foreach ($tracks as $track) {
            $trackName = $track['track_name'] ?? $track['trackName'] ?? null;
            $artistName = $track['artist_name'] ?? $track['artistName'] ?? 'Unknown Artist';

            if (!$trackName) continue;

            $exists = $film->soundtracks()
                ->where('track_name', $trackName)
                ->where('artist_name', $artistName)
                ->exists();

            if (!$exists) {
                $currentOrder++;
                $film->soundtracks()->create([
                    'track_name' => $trackName,
                    'artist_name' => $artistName,
                    'collection_name' => $track['collection_name'] ?? $track['collectionName'] ?? null,
                    'preview_audio_url' => $track['preview_audio_url'] ?? $track['previewUrl'] ?? null,
                    'artwork_url' => $track['artwork_url'] ?? $track['artworkUrl100'] ?? null,
                    'track_view_url' => $track['track_view_url'] ?? $track['trackViewUrl'] ?? null,
                    'order' => $currentOrder,
                ]);
                $importedCount++;
            }
        }

        Cache::forget("film_soundtracks_v2_" . $film->id);
        AdminActivityLog::log('batch_imported_soundtracks', "Mengimpor {$importedCount} lagu ke film '{$film->title}'", 'Film', $film->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => "Berhasil mengimpor {$importedCount} lagu ke database film.",
                'imported_count' => $importedCount,
                'soundtracks' => $film->soundtracks()->get(),
            ]);
        }

        return back()->with('success', "Berhasil mengimpor {$importedCount} lagu ke database film.");
    }

    /**
     * Store a new manual soundtrack for a film
     */
    public function store(Request $request, Film $film)
    {
        $validated = $request->validate([
            'track_name' => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'collection_name' => 'nullable|string|max:255',
            'preview_audio_url' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac,flac|max:25600',
            'artwork_url' => 'nullable|string',
            'artwork_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'track_view_url' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $audioUrl = $request->input('preview_audio_url');
        if ($request->hasFile('audio_file')) {
            $path = $request->file('audio_file')->store('soundtracks/audio', 'public');
            $audioUrl = Storage::url($path);
        }

        $artworkUrl = $request->input('artwork_url');
        if ($request->hasFile('artwork_file')) {
            $path = $request->file('artwork_file')->store('soundtracks/artworks', 'public');
            $artworkUrl = Storage::url($path);
        }

        $order = $request->filled('order') 
            ? (int)$request->input('order') 
            : ($film->soundtracks()->count() + 1);

        $soundtrack = $film->soundtracks()->create([
            'track_name' => $validated['track_name'],
            'artist_name' => $validated['artist_name'],
            'collection_name' => $validated['collection_name'] ?? null,
            'preview_audio_url' => $audioUrl,
            'artwork_url' => $artworkUrl,
            'track_view_url' => $validated['track_view_url'] ?? null,
            'order' => $order,
        ]);

        Cache::forget("film_soundtracks_v2_" . $film->id);

        AdminActivityLog::log('created_soundtrack', "Menambahkan lagu '{$soundtrack->track_name}' ke film '{$film->title}'", 'Film', $film->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => "Lagu '{$soundtrack->track_name}' berhasil ditambahkan ke film.",
                'soundtrack' => $soundtrack,
            ]);
        }

        return back()->with('success', "Lagu '{$soundtrack->track_name}' berhasil ditambahkan ke film.");
    }

    /**
     * Update an existing soundtrack
     */
    public function update(Request $request, Soundtrack $soundtrack)
    {
        $validated = $request->validate([
            'track_name' => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'collection_name' => 'nullable|string|max:255',
            'preview_audio_url' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac,flac|max:25600',
            'artwork_url' => 'nullable|string',
            'artwork_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'track_view_url' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $audioUrl = $request->input('preview_audio_url', $soundtrack->preview_audio_url);
        if ($request->hasFile('audio_file')) {
            // Delete previous local audio if existed
            if ($soundtrack->preview_audio_url && str_contains($soundtrack->preview_audio_url, '/storage/soundtracks/audio/')) {
                $oldPath = str_replace('/storage/', '', $soundtrack->preview_audio_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('audio_file')->store('soundtracks/audio', 'public');
            $audioUrl = Storage::url($path);
        }

        $artworkUrl = $request->input('artwork_url', $soundtrack->artwork_url);
        if ($request->hasFile('artwork_file')) {
            // Delete previous local artwork if existed
            if ($soundtrack->artwork_url && str_contains($soundtrack->artwork_url, '/storage/soundtracks/artworks/')) {
                $oldPath = str_replace('/storage/', '', $soundtrack->artwork_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('artwork_file')->store('soundtracks/artworks', 'public');
            $artworkUrl = Storage::url($path);
        }

        $soundtrack->update([
            'track_name' => $validated['track_name'],
            'artist_name' => $validated['artist_name'],
            'collection_name' => $validated['collection_name'] ?? $soundtrack->collection_name,
            'preview_audio_url' => $audioUrl,
            'artwork_url' => $artworkUrl,
            'track_view_url' => $validated['track_view_url'] ?? $soundtrack->track_view_url,
            'order' => $validated['order'] ?? $soundtrack->order,
        ]);

        Cache::forget("film_soundtracks_v2_" . $soundtrack->film_id);

        $filmTitle = $soundtrack->film ? $soundtrack->film->title : 'Film';
        AdminActivityLog::log('updated_soundtrack', "Mengubah data lagu '{$soundtrack->track_name}' pada film '{$filmTitle}'", 'Soundtrack', $soundtrack->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => "Lagu '{$soundtrack->track_name}' berhasil diperbarui.",
                'soundtrack' => $soundtrack,
            ]);
        }

        return back()->with('success', "Lagu '{$soundtrack->track_name}' berhasil diperbarui.");
    }

    /**
     * Delete a soundtrack
     */
    public function destroy(Soundtrack $soundtrack)
    {
        $filmId = $soundtrack->film_id;
        $filmTitle = $soundtrack->film ? $soundtrack->film->title : 'Film';
        $trackName = $soundtrack->track_name;

        // Clean up storage files if present
        if ($soundtrack->preview_audio_url && str_contains($soundtrack->preview_audio_url, '/storage/soundtracks/audio/')) {
            $oldPath = str_replace('/storage/', '', $soundtrack->preview_audio_url);
            Storage::disk('public')->delete($oldPath);
        }
        if ($soundtrack->artwork_url && str_contains($soundtrack->artwork_url, '/storage/soundtracks/artworks/')) {
            $oldPath = str_replace('/storage/', '', $soundtrack->artwork_url);
            Storage::disk('public')->delete($oldPath);
        }

        $soundtrack->delete();

        Cache::forget("film_soundtracks_v2_" . $filmId);

        AdminActivityLog::log('deleted_soundtrack', "Menghapus lagu '{$trackName}' dari film '{$filmTitle}'", 'Film', $filmId);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'ok',
                'message' => "Lagu '{$trackName}' berhasil dihapus dari film.",
            ]);
        }

        return back()->with('success', "Lagu '{$trackName}' berhasil dihapus dari film.");
    }
}
