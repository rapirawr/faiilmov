<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Film extends Model
{
    use HasFactory;

    protected $fillable = [
        'moviebox_subject_id',
        'title',
        'slug',
        'synopsis',
        'release_year',
        'duration_minutes',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'rating',
        'subject_type',
        'max_resolution',
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
        $avg = $this->reviews()->avg('rating');
        $this->update(['rating' => round($avg ?? 0.0, 1)]);
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
                'backdrop_url' => $posterUrl,
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
