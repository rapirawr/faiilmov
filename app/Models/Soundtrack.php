<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Soundtrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'film_id',
        'track_name',
        'artist_name',
        'collection_name',
        'preview_audio_url',
        'artwork_url',
        'track_view_url',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (Soundtrack $soundtrack) {
            Cache::forget("film_soundtracks_v2_" . $soundtrack->film_id);
        });

        static::deleted(function (Soundtrack $soundtrack) {
            Cache::forget("film_soundtracks_v2_" . $soundtrack->film_id);
        });
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }
}
