<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'title',
        'type',
        'release_date',
        'summary',
        'changes',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'changes' => 'array',
        'is_published' => 'boolean',
        'release_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('release_date', 'desc');
    }
}
