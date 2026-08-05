<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'episode_number',
        'title',
        'synopsis',
        'duration_minutes',
        'thumbnail_url',
        'video_source',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function film()
    {
        return $this->hasOneThrough(Film::class, Season::class, 'id', 'id', 'season_id', 'film_id');
    }

    /**
     * Get the next episode in the same season, or season+1 episode 1
     */
    public function getNextEpisode()
    {
        // Check next episode in same season
        $nextInSeason = Episode::where('season_id', $this->season_id)
            ->where('episode_number', '>', $this->episode_number)
            ->orderBy('episode_number', 'asc')
            ->first();

        if ($nextInSeason) {
            return $nextInSeason;
        }

        // Otherwise check first episode of next season
        $currentSeason = $this->season;
        if ($currentSeason) {
            $nextSeason = Season::where('film_id', $currentSeason->film_id)
                ->where('season_number', '>', $currentSeason->season_number)
                ->orderBy('season_number', 'asc')
                ->first();

            if ($nextSeason) {
                return Episode::where('season_id', $nextSeason->id)
                    ->orderBy('episode_number', 'asc')
                    ->first();
            }
        }

        return null;
    }
}
