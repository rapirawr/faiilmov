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
        'age_rating_style',
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
        'page_transition_enabled',
        'page_transition_gif_path',
        'page_transition_gif_loaded_path',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'age_rating_style' => 'array',
            'maintenance_mode' => 'boolean',
            'page_transition_enabled' => 'boolean',
        ];
    }

    /**
     * Default Age Rating Style Settings
     */
    public static function defaultAgeRatingStyle(): array
    {
        return [
            'preset' => 'squircle_bordered',
            'border_radius' => 'rounded-lg', // rounded-lg, rounded-md, rounded-full, rounded-sm
            'border_width' => 'border-2', // border-2, border, border-[1.5px], border-0
            'font_weight' => 'font-black', // font-black, font-extrabold, font-bold
            'font_size' => 'text-[11px]',
            'has_glow' => true,
            'has_shadow' => true,
            'badges' => [
                'SU' => [
                    'label' => 'SU',
                    'full_label' => 'SU (Semua Umur)',
                    'bg_color' => '#064e3b',
                    'border_color' => '#10b981',
                    'text_color' => '#ffffff',
                ],
                '13+' => [
                    'label' => '13+',
                    'full_label' => '13+ (Remaja)',
                    'bg_color' => '#0c4a6e',
                    'border_color' => '#0284c7',
                    'text_color' => '#ffffff',
                ],
                '16+' => [
                    'label' => '16+',
                    'full_label' => '16+ (Dewasa Muda)',
                    'bg_color' => '#78350f',
                    'border_color' => '#f59e0b',
                    'text_color' => '#ffffff',
                ],
                '18+' => [
                    'label' => '18+',
                    'full_label' => '18+ (Dewasa)',
                    'bg_color' => '#4c0519',
                    'border_color' => '#f43f5e',
                    'text_color' => '#ffffff',
                ],
                'unrated' => [
                    'label' => 'UNRATED',
                    'full_label' => 'UNRATED',
                    'bg_color' => '#27272a',
                    'border_color' => '#52525b',
                    'text_color' => '#d4d4d8',
                ],
            ]
        ];
    }

    public function getAgeRatingStyle(): array
    {
        $defaults = static::defaultAgeRatingStyle();
        if (empty($this->age_rating_style) || !is_array($this->age_rating_style)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $this->age_rating_style);
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
            if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://') || str_starts_with($this->logo_path, '/')) {
                return $this->logo_path;
            }
            return '/storage/' . ltrim($this->logo_path, '/');
        }
        return asset('images/logo.png');
    }

    /**
     * Resolve full logo dark URL
     */
    public function getLogoDarkUrlAttribute(): string
    {
        if (!empty($this->logo_dark_path)) {
            if (str_starts_with($this->logo_dark_path, 'http://') || str_starts_with($this->logo_dark_path, 'https://') || str_starts_with($this->logo_dark_path, '/')) {
                return $this->logo_dark_path;
            }
            return '/storage/' . ltrim($this->logo_dark_path, '/');
        }
        return $this->logo_url;
    }

    /**
     * Resolve favicon URL
     */
    public function getFaviconUrlAttribute(): string
    {
        if (!empty($this->favicon_path)) {
            if (str_starts_with($this->favicon_path, 'http://') || str_starts_with($this->favicon_path, 'https://') || str_starts_with($this->favicon_path, '/')) {
                return $this->favicon_path;
            }
            return '/storage/' . ltrim($this->favicon_path, '/');
        }
        return asset('favicon.ico');
    }

    /**
     * Resolve SEO OG Image URL
     */
    public function getSeoOgImageUrlAttribute(): string
    {
        if (!empty($this->seo_og_image)) {
            if (str_starts_with($this->seo_og_image, 'http://') || str_starts_with($this->seo_og_image, 'https://') || str_starts_with($this->seo_og_image, '/')) {
                return $this->seo_og_image;
            }
            return '/storage/' . ltrim($this->seo_og_image, '/');
        }
        return $this->logo_url;
    }

    /**
     * Resolve Page Transition GIF URL (isLoad / Saat Memuat)
     */
    public function getPageTransitionGifUrlAttribute(): ?string
    {
        if (!empty($this->page_transition_gif_path)) {
            if (str_starts_with($this->page_transition_gif_path, 'http://') || str_starts_with($this->page_transition_gif_path, 'https://') || str_starts_with($this->page_transition_gif_path, '/')) {
                return $this->page_transition_gif_path;
            }
            return '/storage/' . ltrim($this->page_transition_gif_path, '/');
        }
        return null;
    }

    public function getPageTransitionGifIsloadUrlAttribute(): ?string
    {
        return $this->page_transition_gif_url;
    }

    /**
     * Resolve Page Transition Loaded GIF URL (load / Saat Selesai Memuat)
     */
    public function getPageTransitionGifLoadedUrlAttribute(): ?string
    {
        if (!empty($this->page_transition_gif_loaded_path)) {
            if (str_starts_with($this->page_transition_gif_loaded_path, 'http://') || str_starts_with($this->page_transition_gif_loaded_path, 'https://') || str_starts_with($this->page_transition_gif_loaded_path, '/')) {
                return $this->page_transition_gif_loaded_path;
            }
            return '/storage/' . ltrim($this->page_transition_gif_loaded_path, '/');
        }
        return null;
    }

    public function getPageTransitionGifLoadUrlAttribute(): ?string
    {
        return $this->page_transition_gif_loaded_url;
    }
}
