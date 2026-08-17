<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilmEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'film_id',
        'embedding',
        'model_version',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class);
    }
}
