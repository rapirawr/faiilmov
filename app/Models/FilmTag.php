<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class FilmTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'film_id',
        'tag_type',
        'tag_value',
        'confidence',
        'source',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('tag_type', $type);
    }

    public function scopeFranchise(Builder $query): Builder
    {
        return $query->where('tag_type', 'franchise');
    }

    public function scopeUniverse(Builder $query): Builder
    {
        return $query->where('tag_type', 'universe');
    }

    public function scopeGenreMood(Builder $query): Builder
    {
        return $query->where('tag_type', 'genre_mood');
    }

    public function scopeEra(Builder $query): Builder
    {
        return $query->where('tag_type', 'era');
    }
}
