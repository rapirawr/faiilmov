<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'film_id',
        'season_number',
        'title',
        'poster_url',
        'release_year',
    ];

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number', 'asc');
    }
}
