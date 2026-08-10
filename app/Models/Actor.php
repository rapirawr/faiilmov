<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'photo_url'];

    protected static function booted(): void
    {
        static::creating(function (Actor $actor) {
            if (empty($actor->slug)) {
                $actor->slug = \Illuminate\Support\Str::slug($actor->name ?: 'actor-' . rand(100, 999));
            }
        });

        static::updating(function (Actor $actor) {
            if (empty($actor->slug) && !empty($actor->name)) {
                $actor->slug = \Illuminate\Support\Str::slug($actor->name);
            }
        });
    }

    public function films()
    {
        return $this->belongsToMany(Film::class, 'film_actor')->withPivot('character_name');
    }

    /**
     * Get gray person avatar SVG data URI if photo_url is empty
     */
    public function getPhotoUrlAttribute($value): ?string
    {
        if (!empty($value) && !str_contains($value, 'unsplash.com')) {
            return $value;
        }

        // Return a clean gray person avatar SVG Data URI placeholder
        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 24 24" fill="%239ca3af"><rect width="100%" height="100%" fill="%2327272a"/><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
    }
}
