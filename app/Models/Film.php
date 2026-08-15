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

    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_xml');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap_xml');
        });

        static::creating(function (Film $film) {
            if (empty($film->slug) && !empty($film->title)) {
                $baseSlug = Str::slug($film->title);
                $film->slug = $baseSlug ? $baseSlug . '-' . Str::random(5) : 'film-' . rand(1000, 9999);
            }
        });
    }

    /**
     * Get trailer provider type: 'video', 'vimeo', 'dailymotion', 'youtube', 'none'
     */
    public function getTrailerProviderAttribute(): string
    {
        if (empty($this->trailer_url)) {
            return 'none';
        }

        $url = strtolower($this->trailer_url);

        if (preg_match('/\.(mp4|webm|m3u8|ogg)(\?.*)?$/i', $url) || str_contains($url, 'proxy-stream') || str_contains($url, 'aoneroom.com') || str_contains($url, 'macdn')) {
            return 'video';
        }

        if (str_contains($url, 'vimeo.com')) {
            return 'vimeo';
        }

        if (str_contains($url, 'dailymotion.com') || str_contains($url, 'dai.ly')) {
            return 'dailymotion';
        }

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        return 'video';
    }

    /**
     * Get normalized embed URL for iframe or direct video link
     */
    public function getEmbedTrailerUrlAttribute(): ?string
    {
        if (empty($this->trailer_url)) {
            return null;
        }

        $provider = $this->trailer_provider;

        if ($provider === 'vimeo') {
            if (preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/i', $this->trailer_url, $m)) {
                return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&autopause=0';
            }
        }

        if ($provider === 'dailymotion') {
            if (preg_match('/(?:dailymotion\.com\/(?:embed\/)?video\/|dai\.ly\/|player\.html\?video=)([a-zA-Z0-9]+)/i', $this->trailer_url, $m)) {
                return 'https://www.dailymotion.com/embed/video/' . $m[1] . '?autoplay=1&mute=1';
            }
        }

        if ($provider === 'youtube') {
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $this->trailer_url, $m)) {
                return 'https://www.youtube-nocookie.com/embed/' . $m[1];
            }
        }

        return $this->trailer_url;
    }

    /**
     * Legacy YouTube Embed URL accessor
     */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        return $this->embed_trailer_url;
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function actors()
    {
        return $this->belongsToMany(Actor::class, 'film_actor')->withPivot(['character_name', 'role_type']);
    }

    public function mainActors()
    {
        return $this->belongsToMany(Actor::class, 'film_actor')
            ->wherePivot('role_type', 'main')
            ->withPivot(['character_name', 'role_type']);
    }

    public function regularActors()
    {
        return $this->belongsToMany(Actor::class, 'film_actor')
            ->wherePivot('role_type', 'regular')
            ->withPivot(['character_name', 'role_type']);
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
    /**
     * Auto determine content rating based on genres, title, and synopsis with strict age classification
     */
    public function autoDetermineContentRating(): string
    {
        $genreNames = $this->genres()->pluck('name')->map(fn($g) => strtolower(trim($g)))->toArray();
        $titleLower = strtolower($this->title ?? '');
        $text = strtolower(($this->title ?? '') . ' ' . ($this->synopsis ?? ''));

        // 1. Check for 18+ (Explicit Adult, Heavy Horror, Bloody, Slashers, Erotica, Demons, Serial Killers)
        $heavyAdultKeywords = [
            'erotic', 'porn', 'nsfw', 'slasher', 'gore', 'psycho', 'brutal', 'massacre', 
            'serial killer', 'sex', 'nun', 'exorcist', 'possession', 'demon', 'devil', 
            'curse', 'blood', 'bloody', 'slaughter', 'killing', 'deadly', 'haunted',
            'watcher', 'watchers', 'bad nun', 'creepy', 'torture', 'hostel', 'saw', 'conjuring',
            'annabelle', 'insidious', 'paranormal', 'sinister', 'evil', 'satan', 'hell'
        ];

        if (in_array('horror', $genreNames, true) || in_array('erotic', $genreNames, true)) {
            foreach ($heavyAdultKeywords as $kw) {
                if (str_contains($text, $kw)) {
                    return '18+';
                }
            }
            return '18+';
        }

        foreach ($heavyAdultKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return '18+';
            }
        }

        // 2. Check for 16+ (Crime, Thriller, Heavy Action, Violence, Gangster, Mafia, Murder, Zombie, War)
        $matureKeywords = [
            'violence', 'mafia', 'murder', 'gangster', 'killer', 'drug', 'revenge', 
            'terror', 'zombie', 'war', 'assassin', 'shooting', 'weapon', 'thriller', 
            'suspense', 'kidnap', 'heist', 'syndicate', 'cartel', 'death', 'crime'
        ];

        if (in_array('thriller', $genreNames, true) || in_array('crime', $genreNames, true) || in_array('war', $genreNames, true)) {
            return '16+';
        }

        foreach ($matureKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return '16+';
            }
        }

        // 3. Check for 13+ (Action, Sci-Fi, Mystery, Fantasy, Dark Themes, Fighting)
        $teenKeywords = [
            'fight', 'gun', 'alien', 'threat', 'hero', 'superhero', 'monster', 'dark', 
            'ghost', 'action', 'mystery', 'sci-fi', 'fantasy', 'battle', 'warrior', 'space'
        ];

        if (in_array('action', $genreNames, true) || in_array('sci-fi', $genreNames, true) || in_array('mystery', $genreNames, true) || in_array('fantasy', $genreNames, true)) {
            return '13+';
        }

        foreach ($teenKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return '13+';
            }
        }

        // 4. Strict SU (Semua Umur / All Ages)
        // ONLY for Animation, Family, Children that DO NOT contain violence, horror, or dark keywords!
        $isKidsGenre = in_array('animation', $genreNames, true) || in_array('family', $genreNames, true) || in_array('children', $genreNames, true);
        $kidsKeywords = ['cartoon', 'kid', 'kids', 'toy', 'fairy', 'magic', 'disney', 'pixar', 'princess', 'barbie', 'cocomelon', 'chuchu', 'peppa'];
        
        $hasKidsKeyword = false;
        foreach ($kidsKeywords as $kw) {
            if (str_contains($text, $kw)) {
                $hasKidsKeyword = true;
                break;
            }
        }

        if ($isKidsGenre || $hasKidsKeyword) {
            return 'SU';
        }

        // 5. Default fallback for General Comedy, Romance, Drama, Documentary, Sports
        return '13+';
    }

    public function seasons()
    {
        return $this->hasMany(Season::class)->orderBy('season_number', 'asc');
    }

    public function episodes()
    {
        return $this->hasManyThrough(Episode::class, Season::class);
    }

    public function soundtracks()
    {
        return $this->hasMany(Soundtrack::class)->orderBy('order', 'asc')->orderBy('id', 'asc');
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

    /**
     * Check if film has episodes (Series or Dracin)
     */
    public function isEpisodic(): bool
    {
        return in_array($this->subject_type, ['series', 'dracin'], true);
    }

    /**
     * Get dynamic SEO title for film detail page
     */
    public function getSeoTitleAttribute(): string
    {
        $year = $this->release_year ?: date('Y');
        $typeLabel = match($this->subject_type) {
            'series' => 'Series',
            'dracin' => 'Drama China',
            default => 'Film'
        };
        return "{$this->title} ({$year}) - Nonton {$typeLabel} Subtitle Indonesia | faiilmov";
    }

    /**
     * Get dynamic SEO description clipped to 150-160 characters without cutting words
     */
    public function getSeoDescriptionAttribute(): string
    {
        $rawText = trim(strip_tags($this->synopsis ?: ''));
        if (empty($rawText)) {
            $typeLabel = match($this->subject_type) {
                'series' => 'TV Series',
                'dracin' => 'Drama China',
                default => 'film'
            };
            return "Streaming & nonton {$typeLabel} {$this->title} ({$this->release_year}) subtitle Indonesia gratis kualitas HD di faiilmov.";
        }

        if (mb_strlen($rawText) <= 155) {
            return $rawText;
        }

        // Clip cleanly to ~150 chars without cutting words in the middle
        $truncated = mb_substr($rawText, 0, 150);
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > 110) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return rtrim($truncated, '.,!?;:') . '...';
    }

    /**
     * Get dynamic SEO keywords
     */
    public function getSeoKeywordsAttribute(): string
    {
        $keywords = [
            $this->title,
            "nonton {$this->title}",
            "streaming {$this->title}",
            "{$this->title} sub indo",
            "{$this->title} {$this->release_year}",
            "faiilmov"
        ];

        if ($this->relationLoaded('genres') && $this->genres->isNotEmpty()) {
            foreach ($this->genres as $genre) {
                $keywords[] = strtolower($genre->name);
            }
        }

        if ($this->relationLoaded('actors') && $this->actors->isNotEmpty()) {
            foreach ($this->actors->take(3) as $actor) {
                $keywords[] = strtolower($actor->name);
            }
        }

        return implode(', ', array_unique($keywords));
    }

    /**
     * Get Schema.org JSON-LD data structure for Movie or TVSeries
     */
    public function getSchemaJsonLdArrayAttribute(): array
    {
        $isSeries = $this->isEpisodic();
        $canonicalUrl = route('film.show', $this->slug);

        $images = [];
        if (!empty($this->backdrop_url)) {
            $images[] = $this->backdrop_url;
        }
        if (!empty($this->poster_url)) {
            $images[] = $this->poster_url;
        }
        if (empty($images)) {
            $images[] = asset('images/logo.png');
        }

        $genres = [];
        if ($this->relationLoaded('genres') && $this->genres->isNotEmpty()) {
            $genres = $this->genres->pluck('name')->toArray();
        }

        $actors = [];
        if ($this->relationLoaded('actors') && $this->actors->isNotEmpty()) {
            foreach ($this->actors as $actor) {
                $actors[] = [
                    '@type' => 'Person',
                    'name' => $actor->name,
                ];
            }
        }

        $rating = $this->rating ?: 4.5;
        $reviewCount = max(1, (int)($this->reviews_count ?? $this->view_count ?: 10));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $isSeries ? 'TVSeries' : 'Movie',
            'name' => $this->title,
            'url' => $canonicalUrl,
            'description' => $this->seo_description,
            'image' => count($images) === 1 ? $images[0] : $images,
            'datePublished' => ($this->release_year ?: 2024) . '-01-01',
            'inLanguage' => 'id',
        ];

        if (!empty($genres)) {
            $schema['genre'] = $genres;
        }

        if (!empty($actors)) {
            $schema['actor'] = $actors;
        }

        if ($this->duration_minutes && $this->duration_minutes > 0) {
            $schema['duration'] = "PT{$this->duration_minutes}M";
        }

        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float)$rating, 1, '.', ''),
            'bestRating' => '5',
            'worstRating' => '1',
            'ratingCount' => (string)$reviewCount,
        ];

        return $schema;
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

        $existing = static::where('moviebox_subject_id', $subjectId)->first();

        $film = static::updateOrCreate(
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
                'subject_type' => $data['subject_type'] ?? (($existing && $existing->subject_type === 'dracin') ? 'dracin' : (($stype === 2) ? 'series' : 'movie')),
                'max_resolution' => $maxRes,
            ]
        );

        if (!$film->content_rating || $film->content_rating === 'SU') {
            $film->update(['content_rating' => $film->autoDetermineContentRating()]);
        }

        // Auto Sync Actors / Cast Relations
        $staffList = $data['staffList'] ?? $data['starList'] ?? $data['actors'] ?? $data['actorList'] ?? [];
        if (is_array($staffList) && !empty($staffList)) {
            $actorsFound = [];
            $actorIndex = 0;
            foreach ($staffList as $staff) {
                $name = trim($staff['name'] ?? '');
                if (empty($name)) continue;

                $type = (int)($staff['staffType'] ?? 1);
                $character = trim($staff['character'] ?? '');

                if ($type !== 1 && in_array(strtolower($character), ['director', 'writer', 'producer', 'screenplay', 'creator'])) {
                    continue;
                }

                $avatarUrl = $staff['avatarUrl'] ?? $staff['avatar'] ?? $staff['photo'] ?? null;
                $actorSlug = Str::slug($name);
                if (empty($actorSlug)) {
                    $actorSlug = 'actor-' . substr(md5($name), 0, 6);
                }

                $actor = \App\Models\Actor::where('name', $name)->first();
                if (!$actor) {
                    $baseSlug = $actorSlug;
                    $count = 1;
                    while (\App\Models\Actor::where('slug', $actorSlug)->exists()) {
                        $actorSlug = $baseSlug . '-' . $count++;
                    }

                    $actor = \App\Models\Actor::create([
                        'name' => $name,
                        'slug' => $actorSlug,
                        'photo_url' => $avatarUrl,
                    ]);
                } elseif ($avatarUrl && (!$actor->photo_url || str_contains($actor->photo_url, 'unsplash.com'))) {
                    $actor->update(['photo_url' => $avatarUrl]);
                }

                $roleType = ($actorIndex < 2) ? 'main' : 'regular';
                $actorsFound[$actor->id] = [
                    'character_name' => $character ?: null,
                    'role_type' => $roleType,
                ];
                $actorIndex++;
            }

            if (!empty($actorsFound)) {
                $film->actors()->syncWithoutDetaching($actorsFound);
            }
        }

        return $film;
    }

    public static function isExcludedTitle(string $title): bool
    {
        $t = strtolower($title);

        // 1. Word boundary checks for short acronyms or single-word tags that must not match inside larger words (e.g. 'ost' in 'ghost', 'amv' in 'jamv')
        $wordBoundaryPatterns = [
            'ost', 'amv', 'mv', 'shorts', 'batch', 'lesson', 'lecture', 'tutorial', 'ncert', 'cbse'
        ];
        foreach ($wordBoundaryPatterns as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/i', $t)) {
                return true;
            }
        }

        // 2. Substring exclusions for multi-word or distinct non-movie phrases
        $excludePatterns = [
            '[hindi]', '[tamil]', '[telugu]', '[kannada]', '[malayalam]',
            'class 1', 'class 2', 'class 3', 'class 4', 'class 5', 'class 6', 'class 7', 'class 8', 'class 9', 'class 10', 'class 11', 'class 12',
            'class 1st', 'class 2nd', 'class 3rd', 'class 4th', 'class 5th', 'class 6th', 'class 7th', 'class 8th', 'class 9th', 'class 10th', 'class 11th', 'class 12th',
            'grade 1', 'grade 2', 'grade 3', 'grade 4', 'grade 5', 'grade 6', 'grade 7', 'grade 8', 'grade 9', 'grade 10', 'grade 11', 'grade 12',
            'grammar', 'chuchu', 'alphablocks', 'playlist', 'one shot', 'oneshot', 'homeschooling', 'phonics', 'nursery', 'ekaksha', 'curriculum', 'mathematics', 'physics', 'chemistry', 'biology',
            'junoon', 'neev', 'udaan', 'uday', 'fastrack', 'champions', 'little masters', 'notices', 'revision', 'marathon', 'crash course', 'cocomelon', 'babybus', 'sesame street', 'nunu tv', 'wwe', 'wrestlemania',
            'fight irl', 'performed by', '24 jam', '//', 'reaction', 'gameplay', 'walkthrough', 'unboxing', 'tiktok', 'compilation', 'music video', 'official audio', 'remix',
            'opening 1', 'opening 2', 'opening 3', 'opening 4', 'opening 5', 'opening 6', 'opening 7', 'opening 8', 'opening 9', 'opening 10',
            'ending 1', 'ending 2', 'ending 3', 'ending 4', 'ending 5', 'theme song', 'cover song'
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
