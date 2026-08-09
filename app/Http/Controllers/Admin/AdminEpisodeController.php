<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\Season;
use App\Models\Episode;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class AdminEpisodeController extends Controller
{
    public function storeSeason(Request $request, Film $film)
    {
        $validated = $request->validate([
            'season_number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'release_year' => 'nullable|integer|min:1900|max:2099',
            'poster_url' => 'nullable|string',
        ]);

        // Prevent duplicate season number for same film
        $existing = Season::where('film_id', $film->id)
            ->where('season_number', $validated['season_number'])
            ->first();

        if ($existing) {
            return back()->with('error', "Season {$validated['season_number']} sudah ada pada film ini.");
        }

        $season = Season::create([
            'film_id' => $film->id,
            'season_number' => $validated['season_number'],
            'title' => $validated['title'] ?: "Season {$validated['season_number']}",
            'release_year' => $validated['release_year'] ?? $film->release_year,
            'poster_url' => $validated['poster_url'] ?? $film->poster_url,
        ]);

        AdminActivityLog::log('created_season', "Menambahkan Season {$season->season_number} ke film '{$film->title}'", 'Film', $film->id);

        return back()->with('success', "Season {$season->season_number} berhasil ditambahkan.");
    }

    public function destroySeason(Season $season)
    {
        $filmTitle = $season->film ? $season->film->title : 'Unknown';
        $seasonNum = $season->season_number;

        // Delete associated episodes
        $season->episodes()->delete();
        $season->delete();

        AdminActivityLog::log('deleted_season', "Menghapus Season {$seasonNum} dari film '{$filmTitle}'", 'Season', $season->id);

        return back()->with('success', "Season {$seasonNum} beserta episode di dalamnya berhasil dihapus.");
    }

    public function storeEpisode(Request $request, Season $season)
    {
        $validated = $request->validate([
            'episode_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'thumbnail_url' => 'nullable|string',
            'video_source' => 'nullable|string', // Support direct video URL / MovieBox stream link
        ]);

        // Prevent duplicate episode number in same season
        $existing = Episode::where('season_id', $season->id)
            ->where('episode_number', $validated['episode_number'])
            ->first();

        if ($existing) {
            return back()->with('error', "Episode {$validated['episode_number']} sudah ada di Season {$season->season_number}.");
        }

        $episode = Episode::create([
            'season_id' => $season->id,
            'episode_number' => $validated['episode_number'],
            'title' => $validated['title'],
            'synopsis' => $validated['synopsis'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? 24,
            'thumbnail_url' => $validated['thumbnail_url'] ?? $season->poster_url,
            'video_source' => $validated['video_source'] ?? null,
        ]);

        AdminActivityLog::log('created_episode', "Menambahkan Ep {$episode->episode_number} '{$episode->title}' ke Season {$season->season_number}", 'Episode', $episode->id);

        return back()->with('success', "Episode {$episode->episode_number} ('{$episode->title}') berhasil ditambahkan.");
    }

    public function updateEpisode(Request $request, Episode $episode)
    {
        $validated = $request->validate([
            'episode_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'thumbnail_url' => 'nullable|string',
            'video_source' => 'nullable|string',
        ]);

        $episode->update([
            'episode_number' => $validated['episode_number'],
            'title' => $validated['title'],
            'synopsis' => $validated['synopsis'] ?? $episode->synopsis,
            'duration_minutes' => $validated['duration_minutes'] ?? $episode->duration_minutes,
            'thumbnail_url' => $validated['thumbnail_url'] ?? $episode->thumbnail_url,
            'video_source' => $validated['video_source'] ?? $episode->video_source,
        ]);

        AdminActivityLog::log('updated_episode', "Mengubah data Episode {$episode->episode_number} '{$episode->title}'", 'Episode', $episode->id);

        return back()->with('success', "Episode {$episode->episode_number} berhasil diperbarui.");
    }

    public function destroyEpisode(Episode $episode)
    {
        $epNum = $episode->episode_number;
        $title = $episode->title;
        $episode->delete();

        AdminActivityLog::log('deleted_episode', "Menghapus Episode {$epNum} '{$title}'", 'Episode', $episode->id);

        return back()->with('success', "Episode {$epNum} ('{$title}') berhasil dihapus.");
    }
}
