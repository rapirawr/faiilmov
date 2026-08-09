<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Film extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'moviebox_subject_id',
        'title',
        'slug',
        'synopsis',
        'ai_embeddings',
        'release_year',
        'duration_minutes',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'rating',
        'view_count',
        'subject_type',
        'content_rating',
        'max_resolution',
    ];

    protected $casts = [
        'ai_embeddings' => 'array',
        'rating' => 'float',
        'view_count' => 'integer',
        'release_year' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected $dispatchesEvents = [
        'created' => \App\Events\FilmCreated::class,
        'updated' => \App\Events\FilmUpdated::class,
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function actors()
    {
        return $this->belongsToMany(Actor::class, 'film_actor')->withPivot('character_name');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }

    public function scopeForActiveProfile($query)
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $activeProfile = \Illuminate\Support\Facades\Auth::user()->activeProfile();
            if ($activeProfile && $activeProfile->is_child) {
                return $query->where(function ($q) {
                    $q->whereIn('content_rating', ['SU', 'G', 'PG'])
                      ->orWhereNull('content_rating');
                });
            }
        }
        return $query;
    }

    /**
     * Auto determine content rating based on genres, title, and synopsis
     */
    public function autoDetermineContentRating(): string
    {
        $genreNames = $this->genres()->pluck('name')->map(fn($g) => strtolower($g))->toArray();
        $text = strtolower(($this->title ?? '') . ' ' . ($this->synopsis ?? ''));

        // 18+ Keywords / Genres
        $adultKeywords = ['erotic', 'porn', 'nsfw', 'slasher', 'gore', 'psycho', 'brutal', 'massacre', 'serial killer', 'sex'];
        if (in_array('horror', $genreNames, true) && in_array('crime', $genreNames, true)) {
            return '18+';
        }
        foreach ($adultKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return '18+';
            }
        }

        // 16+ Keywords / Genres
        $matureKeywords = ['violence', 'blood', 'mafia', 'murder', 'gangster', 'killer', 'drug', 'revenge', 'terror', 'zombie', 'war'];
        if (in_array('horror', $genreNames, true) || in_array('crime', $genreNames, true)) {
            return '16+';
        }
        foreach ($matureKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return '16+';
            }
        }

        // 13+ Keywords / Genres
        $teenKeywords = ['fight', 'weapon', 'gun', 'alien', 'threat', 'hero', 'superhero', 'monster', 'dark', 'ghost', 'action'];
        if (in_array('action', $genreNames, true) || in_array('thriller', $genreNames, true) || in_array('sci-fi', $genreNames, true)) {
            return '13+';
        }
        foreach ($teenKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return '13+';
            }
        }

        // SU / G (Kids & Family & Animation)
        if (in_array('animation', $genreNames, true) || in_array('family', $genreNames, true) || in_array('children', $genreNames, true)) {
            return 'SU';
        }
        $kidsKeywords = ['cartoon', 'kid', 'toy', 'fairy', 'magic', 'disney', 'princess', 'school', 'barbie'];
        foreach ($kidsKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return 'SU';
            }
        }

        // PG (Default for General Adventure / Romance / Comedy)
        if (in_array('adventure', $genreNames, true) || in_array('comedy', $genreNames, true) || in_array('romance', $genreNames, true)) {
            return 'PG';
        }

        return 'PG';
    }

    public function seasons()
    {
        return $this->hasMany(Season::class)->orderBy('season_number', 'asc');
    }

    public function episodes()
    {
        return $this->hasManyThrough(Episode::class, Season::class);
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function updateAverageRating(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            $film = static::where('id', $this->id)->lockForUpdate()->first();
            if ($film) {
                $avg = $film->reviews()->avg('rating');
                $film->update(['rating' => round($avg ?? 0.0, 1)]);
            }
        });
    }

    public function getMaxResolutionAttribute(): string
    {
        return strtoupper($this->attributes['max_resolution'] ?? '1080P');
    }

    public function getThumbnailUrlAttribute(): string
    {
        $url = $this->poster_url ?: 'https://images.unsplash.com/photo-1518676599602-2170de9df05d?q=70&w=320&auto=format';
        if (str_contains($url, 'unsplash.com')) {
            if (!str_contains($url, 'w=')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'w=320&q=70&auto=format';
            }
        }
        return $url;
    }

    public static function fromApiData(array $data): ?self
    {
        $subjectId = (string)($data['subjectId'] ?? $data['id'] ?? '');
        if (!$subjectId || $subjectId === '0') {
            return null;
        }

        $rawTitle = $data['title'] ?? $data['name'] ?? 'Untitled';
        $cleanTitle = trim(preg_replace('/\[.*?\]/', '', $rawTitle));
        if (empty($cleanTitle)) {
            $cleanTitle = $rawTitle;
        }

        if (static::isExcludedTitle($rawTitle) || static::isExcludedTitle($cleanTitle)) {
            return null;
        }

        $slug = Str::slug($cleanTitle) . '-' . substr(md5($subjectId), 0, 6);
        $releaseYear = isset($data['releaseDate']) ? (int)substr($data['releaseDate'], 0, 4) : 2024;
        $duration = isset($data['durationSeconds']) ? (int)round($data['durationSeconds'] / 60) : 120;

        $posterUrl = null;
        if (isset($data['cover']) && is_array($data['cover'])) {
            $posterUrl = $data['cover']['url'] ?? null;
        } elseif (isset($data['cover']) && is_string($data['cover'])) {
            $posterUrl = $data['cover'];
        } elseif (isset($data['poster'])) {
            $posterUrl = is_array($data['poster']) ? ($data['poster']['url'] ?? null) : $data['poster'];
        } elseif (isset($data['pic'])) {
            $posterUrl = is_array($data['pic']) ? ($data['pic']['url'] ?? null) : $data['pic'];
        }

        $backdropUrl = null;
        if (isset($data['banner']) && is_array($data['banner'])) {
            $backdropUrl = $data['banner']['url'] ?? null;
        } elseif (isset($data['banner']) && is_string($data['banner'])) {
            $backdropUrl = $data['banner'];
        } elseif (isset($data['bgCover'])) {
            $backdropUrl = is_array($data['bgCover']) ? ($data['bgCover']['url'] ?? null) : $data['bgCover'];
        } elseif (isset($data['backdrop'])) {
            $backdropUrl = is_array($data['backdrop']) ? ($data['backdrop']['url'] ?? null) : $data['backdrop'];
        } elseif (isset($data['horizontalCover'])) {
            $backdropUrl = is_array($data['horizontalCover']) ? ($data['horizontalCover']['url'] ?? null) : $data['horizontalCover'];
        }
        if (empty($backdropUrl)) {
            $backdropUrl = $posterUrl;
        }

        $stype = (int)($data['subjectType'] ?? $data['stype'] ?? 1);

        $resRaw = $data['maxResolution'] ?? $data['resolution'] ?? $data['quality'] ?? $data['sharpness'] ?? '1080P';
        if (is_array($resRaw)) {
            $resRaw = implode(' ', $resRaw);
        }
        $resLower = strtolower((string)$resRaw);
        if (str_contains($resLower, '4k') || str_contains($resLower, '2160')) {
            $maxRes = '4K';
        } elseif (str_contains($resLower, '720')) {
            $maxRes = '720P';
        } elseif (str_contains($resLower, '480')) {
            $maxRes = '480P';
        } else {
            $maxRes = '1080P';
        }

        return static::updateOrCreate(
            ['moviebox_subject_id' => $subjectId],
            [
                'title' => $cleanTitle,
                'slug' => $slug,
                'synopsis' => $data['description'] ?? $data['intro'] ?? $data['synopsis'] ?? $data['brief'] ?? $data['summary'] ?? '',
                'release_year' => $releaseYear > 0 ? $releaseYear : 2024,
                'duration_minutes' => $duration > 0 ? $duration : 120,
                'poster_url' => $posterUrl,
                'backdrop_url' => $backdropUrl,
                'rating' => (float)($data['imdbRatingValue'] ?? $data['score'] ?? 0.0),
                'subject_type' => ($stype === 2) ? 'series' : 'movie',
                'max_resolution' => $maxRes,
            ]
        );
    }

    public static function isExcludedTitle(string $title): bool
    {
        $t = strtolower($title);
        $excludePatterns = [
            '[hindi]', '[tamil]', '[telugu]', '[kannada]', '[malayalam]',
            'class 1', 'class 2', 'class 3', 'class 4', 'class 5', 'class 6', 'class 7', 'class 8', 'class 9', 'class 10', 'class 11', 'class 12',
            'class 1st', 'class 2nd', 'class 3rd', 'class 4th', 'class 5th', 'class 6th', 'class 7th', 'class 8th', 'class 9th', 'class 10th', 'class 11th', 'class 12th',
            'grade 1', 'grade 2', 'grade 3', 'grade 4', 'grade 5', 'grade 6', 'grade 7', 'grade 8', 'grade 9', 'grade 10', 'grade 11', 'grade 12',
            'ncert', 'cbse', 'grammar', 'chuchu', 'alphablocks', 'playlist', 'one shot', 'oneshot', 'lecture', 'lesson', 'tutorial', 'homeschooling', 'phonics', 'nursery', 'ekaksha', 'curriculum', 'mathematics', 'physics', 'chemistry', 'biology',
            'junoon', 'neev', 'udaan', 'uday', 'fastrack', 'champions', 'little masters', 'notices', 'batch', 'revision', 'marathon', 'crash course', 'cocomelon', 'babybus', 'sesame street', 'nunu tv', 'wwe', 'wrestlemania',
            'amv', 'fight irl', 'performed by', '24 jam', '#', '//', 'reaction', 'gameplay', 'walkthrough', 'unboxing', 'shorts', 'tiktok', 'compilation', 'music video', 'official audio', 'remix', 'opening 1', 'opening 2', 'opening 3', 'opening 4', 'opening 5', 'opening 6', 'opening 7', 'opening 8', 'opening 9', 'opening 10', 'opening 11', 'opening 12', 'opening 13', 'opening 14', 'opening 15', 'opening 16', 'opening 17', 'opening 18', 'opening 19', 'opening 20', 'ending 1', 'ending 2', 'ending 3', 'ending 4', 'ending 5', 'ost', 'theme song', 'cover song'
        ];
        return Str::contains($t, $excludePatterns);
    }

    public static function extractHomepageSubjects(array $payload): array
    {
        $extracted = [];
        $items = $payload['items'] ?? [];
        if (!is_array($items)) return [];

        foreach ($items as $item) {
            if (isset($item['banner']['banners']) && is_array($item['banner']['banners'])) {
                foreach ($item['banner']['banners'] as $b) {
                    if (isset($b['subject']) && is_array($b['subject'])) {
                        $title = $b['subject']['title'] ?? '';
                        if (!static::isExcludedTitle($title)) {
                            $extracted[] = $b['subject'];
                        }
                    }
                }
            }
            if (isset($item['customData']['items']) && is_array($item['customData']['items'])) {
                foreach ($item['customData']['items'] as $c) {
                    if (isset($c['subject']) && is_array($c['subject'])) {
                        $title = $c['subject']['title'] ?? '';
                        if (!static::isExcludedTitle($title)) {
                            $extracted[] = $c['subject'];
                        }
                    }
                }
            }
            if (isset($item['subjects']) && is_array($item['subjects'])) {
                foreach ($item['subjects'] as $s) {
                    if (is_array($s)) {
                        $title = $s['title'] ?? '';
                        if (!static::isExcludedTitle($title)) {
                            $extracted[] = $s;
                        }
                    }
                }
            }
        }

        return $extracted;
    }

    public static function extractSearchSubjects(array $payload): array
    {
        $raw = [];
        $results = $payload['results'] ?? [];
        if (is_array($results) && count($results) > 0) {
            foreach ($results as $group) {
                if (isset($group['subjects']) && is_array($group['subjects'])) {
                    foreach ($group['subjects'] as $sub) {
                        if (is_array($sub) && !empty($sub['title'])) {
                            $raw[] = $sub;
                        }
                    }
                }
            }
        } elseif (isset($payload['list']) && is_array($payload['list'])) {
            $raw = $payload['list'];
        }

        return array_values(array_filter($raw, function($item) {
            $title = $item['title'] ?? '';
            return !static::isExcludedTitle($title);
        }));
    }

    public static function syncFromApiBatch(array $items): void
    {
        if (empty($items)) return;

        $subjectIds = array_values(array_filter(array_map(fn($i) => (string)($i['subjectId'] ?? $i['id'] ?? ''), $items)));
        if (empty($subjectIds)) return;

        $existingIds = static::whereIn('moviebox_subject_id', $subjectIds)->pluck('moviebox_subject_id')->toArray();

        foreach ($items as $item) {
            $id = (string)($item['subjectId'] ?? $item['id'] ?? '');
            if ($id && !in_array($id, $existingIds, true)) {
                static::fromApiData($item);
            }
        }
    }
}
