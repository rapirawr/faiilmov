<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'site_name',
        'site_tagline',
        'logo_path',
        'logo_dark_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'seo_meta_title',
        'seo_meta_description',
        'seo_meta_keywords',
        'seo_og_image',
        'seo_canonical_url',
        'footer_text',
        'social_links',
        'contact_email',
        'maintenance_mode',
        'maintenance_message',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'maintenance_mode' => 'boolean',
        ];
    }

    /**
     * Singleton Instance Getter (Cached as raw attributes array to avoid incomplete class deserialization)
     */
    public static function current(): self
    {
        $cachedAttributes = Cache::get('site_settings_data');
        if (is_array($cachedAttributes) && !empty($cachedAttributes)) {
            return (new static())->newFromBuilder($cachedAttributes);
        }

        $instance = static::firstOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Faiilmov',
                'site_tagline' => 'Streaming Movie, Anime & TV Series Subtitle Indonesia',
                'primary_color' => '#ffffff',
                'secondary_color' => '#a1a1aa',
                'accent_color' => '#f59e0b',
                'background_color' => '#09090b',
                'seo_meta_title' => 'Faiilmov | Nonton Film & TV Series Streaming Subtitle Indonesia',
                'seo_meta_description' => 'Streaming & nonton film online subtitle Indonesia gratis HD. Katalog ribuan film bioskop, drama series, anime, dan serial TV favorit di Faiilmov.',
                'seo_meta_keywords' => 'nonton film, streaming film, film gratis, film sub indo, faiilmov, serial tv, moviebox, anime, streaming bioskop',
                'footer_text' => '© ' . date('Y') . ' Faiilmov. Streaming platform for movie lovers.',
                'social_links' => [
                    'instagram' => '',
                    'twitter' => '',
                    'telegram' => '',
                    'discord' => '',
                    'youtube' => '',
                    'tiktok' => '',
                ],
                'contact_email' => 'support@faiilmov.my.id',
                'maintenance_mode' => false,
                'maintenance_message' => 'Sistem sedang dalam pemeliharaan berkala untuk meningkatkan performa streaming. Silakan kembali beberapa saat lagi.',
            ]
        );

        Cache::forever('site_settings_data', $instance->getAttributes());
        return $instance;
    }

    /**
     * Auto clear cache when model is saved or deleted
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });
        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Invalidate cached singleton
     */
    public static function clearCache(): void
    {
        Cache::forget('site_settings');
        Cache::forget('site_settings_data');
        Cache::forget('site_settings_public');
    }

    /**
     * Resolve full logo URL
     */
    public function getLogoUrlAttribute(): string
    {
        if (!empty($this->logo_path)) {
            return str_starts_with($this->logo_path, 'http') 
                ? $this->logo_path 
                : Storage::disk('public')->url($this->logo_path);
        }
        return asset('images/logo.png');
    }

    /**
     * Resolve full logo dark URL
     */
    public function getLogoDarkUrlAttribute(): string
    {
        if (!empty($this->logo_dark_path)) {
            return str_starts_with($this->logo_dark_path, 'http') 
                ? $this->logo_dark_path 
                : Storage::disk('public')->url($this->logo_dark_path);
        }
        return $this->logo_url;
    }

    /**
     * Resolve favicon URL
     */
    public function getFaviconUrlAttribute(): string
    {
        if (!empty($this->favicon_path)) {
            return str_starts_with($this->favicon_path, 'http') 
                ? $this->favicon_path 
                : Storage::disk('public')->url($this->favicon_path);
        }
        return asset('favicon.ico');
    }

    /**
     * Resolve SEO OG Image URL
     */
    public function getSeoOgImageUrlAttribute(): string
    {
        if (!empty($this->seo_og_image)) {
            return str_starts_with($this->seo_og_image, 'http') 
                ? $this->seo_og_image 
                : Storage::disk('public')->url($this->seo_og_image);
        }
        return $this->logo_url;
    }
}
