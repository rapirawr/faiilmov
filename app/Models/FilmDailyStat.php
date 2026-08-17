<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FilmDailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'film_id',
        'date',
        'views',
        'unique_viewers',
        'watch_time_seconds',
        'completion_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'unique_viewers' => 'integer',
        'watch_time_seconds' => 'integer',
        'completion_rate' => 'float',
    ];

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function scopeForDate($query, $date)
    {
        $d = $date instanceof Carbon ? $date->toDateString() : $date;
        return $query->where('date', $d);
    }

    public function scopeTopByViews($query, $date = null, int $limit = 10)
    {
        $d = $date ? ($date instanceof Carbon ? $date->toDateString() : $date) : now()->toDateString();
        return $query->where('date', $d)->orderBy('views', 'desc')->limit($limit);
    }

    public function scopeTopByWatchTime($query, $date = null, int $limit = 10)
    {
        $d = $date ? ($date instanceof Carbon ? $date->toDateString() : $date) : now()->toDateString();
        return $query->where('date', $d)->orderBy('watch_time_seconds', 'desc')->limit($limit);
    }
}
