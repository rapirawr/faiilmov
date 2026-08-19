<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EpisodeComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'film_id',
        'user_id',
        'season_number',
        'episode_number',
        'episode_id',
        'parent_id',
        'comment',
        'is_spoiler',
        'likes_count',
    ];

    protected $casts = [
        'season_number' => 'integer',
        'episode_number' => 'integer',
        'is_spoiler' => 'boolean',
        'likes_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }

    public function parent()
    {
        return $this->belongsTo(EpisodeComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(EpisodeComment::class, 'parent_id')->oldest();
    }

    public function likes()
    {
        return $this->hasMany(EpisodeCommentLike::class, 'comment_id');
    }

    public function reports()
    {
        return $this->hasMany(EpisodeCommentReport::class, 'comment_id');
    }

    public function isLikedBy(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function scopeForEpisode($query, int $filmId, int $season, int $episode)
    {
        return $query->where('film_id', $filmId)
            ->where('season_number', $season)
            ->where('episode_number', $episode)
            ->whereNull('parent_id');
    }
}
