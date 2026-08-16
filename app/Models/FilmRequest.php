<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FilmRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'year',
        'status',
        'request_count',
        'matched_film_id',
        'rejection_reason',
    ];

    protected $casts = [
        'year' => 'integer',
        'request_count' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'film_request_user')->withTimestamps();
    }

    public function matchedFilm()
    {
        return $this->belongsTo(Film::class, 'matched_film_id');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'searching']);
    }

    public function scopeSortedByPopularity($query)
    {
        return $query->orderByDesc('request_count')->orderByDesc('updated_at');
    }
}
