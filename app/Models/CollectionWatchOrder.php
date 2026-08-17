<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CollectionWatchOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'film_id',
        'order_type',
        'sequence',
        'note',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class);
    }

    public function scopeRelease(Builder $query): Builder
    {
        return $query->where('order_type', 'release');
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->where('order_type', 'chronological');
    }
}
