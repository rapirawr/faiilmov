<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PageElement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'title',
        'content',
        'image_url',
        'button_text',
        'button_url',
        'button_target',
        'icon',
        'position',
        'theme_color',
        'custom_css',
        'custom_html',
        'target_page',
        'target_path_pattern',
        'target_device',
        'target_audience',
        'is_dismissible',
        'dismiss_duration_hours',
        'order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_dismissible' => 'boolean',
        'is_active'      => 'boolean',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'order'          => 'integer',
        'dismiss_duration_hours' => 'integer',
    ];

    /**
     * Scope for currently active elements (within scheduled window)
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Scope for specific page targeting
     */
    public function scopeForPage(Builder $query, string $pageName, ?string $currentPath = null): Builder
    {
        return $query->where(function ($q) use ($pageName, $currentPath) {
            $q->where('target_page', 'all')
              ->orWhere('target_page', $pageName);

            if ($currentPath) {
                $q->orWhere(function ($sub) use ($currentPath) {
                    $sub->where('target_page', 'custom')
                        ->whereNotNull('target_path_pattern')
                        ->whereRaw('? LIKE target_path_pattern', [$currentPath]);
                });
            }
        });
    }

    /**
     * Scope for target audience (guest / logged in user)
     */
    public function scopeForAudience(Builder $query, bool $isLoggedIn): Builder
    {
        return $query->where(function ($q) use ($isLoggedIn) {
            $q->where('target_audience', 'all');
            if ($isLoggedIn) {
                $q->orWhere('target_audience', 'user');
            } else {
                $q->orWhere('target_audience', 'guest');
            }
        });
    }

    /**
     * Scope for target device (mobile / desktop / all)
     */
    public function scopeForDevice(Builder $query, string $device = 'all'): Builder
    {
        if ($device === 'all') {
            return $query;
        }

        return $query->where(function ($q) use ($device) {
            $q->where('target_device', 'all')
              ->orWhere('target_device', $device);
        });
    }

    /**
     * Determine if element is currently schedule-valid
     */
    public function isScheduleActive(): bool
    {
        if (!$this->is_active) return false;
        $now = Carbon::now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        return true;
    }

    /**
     * Human-readable type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'broadcast_bar'   => 'Announcement Bar',
            'floating_widget' => 'Floating Widget',
            'popup_modal'     => 'Popup Modal',
            'custom_block'    => 'Custom HTML / Embed',
            'promo_banner'    => 'Promo / Highlight Card',
            default           => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Predefined presets/templates for quick creation
     */
    public static function getPresets(): array
    {
        return [
            [
                'id' => 'telegram_widget',
                'name' => 'Gabung Channel Telegram',
                'type' => 'floating_widget',
                'title' => 'Channel Telegram Faiilmov',
                'content' => 'Update film baru & request langsung di Telegram!',
                'button_text' => 'Gabung Sekarang',
                'button_url' => 'https://t.me/faiilmov',
                'button_target' => '_blank',
                'icon' => 'send',
                'position' => 'bottom_right',
                'theme_color' => 'blue',
                'target_page' => 'all',
                'target_device' => 'all',
                'target_audience' => 'all',
                'is_dismissible' => true,
                'dismiss_duration_hours' => 24,
            ],
            [
                'id' => 'request_film_bar',
                'name' => 'Bar Request Film Cepat',
                'type' => 'broadcast_bar',
                'title' => 'Film favoritmu belum ada di katalog?',
                'content' => 'Kirim permintaan judul film sekarang, tim kami akan segera menambahkannya!',
                'button_text' => 'Request Film',
                'button_url' => '/film-requests',
                'button_target' => '_self',
                'icon' => 'film',
                'position' => 'top',
                'theme_color' => 'amber',
                'target_page' => 'all',
                'target_device' => 'all',
                'target_audience' => 'all',
                'is_dismissible' => true,
                'dismiss_duration_hours' => 12,
            ],
            [
                'id' => 'saweria_widget',
                'name' => 'Donasi / Dukung Server (Saweria)',
                'type' => 'floating_widget',
                'title' => 'Dukung Server Faiilmov',
                'content' => 'Bantu biaya server & CDN agar streaming tetap lancar bebas lemot!',
                'button_text' => 'Donasi Saweria',
                'button_url' => 'https://saweria.co/faiilmov',
                'button_target' => '_blank',
                'icon' => 'heart',
                'position' => 'bottom_left',
                'theme_color' => 'rose',
                'target_page' => 'all',
                'target_device' => 'all',
                'target_audience' => 'all',
                'is_dismissible' => true,
                'dismiss_duration_hours' => 48,
            ],
            [
                'id' => 'welcome_modal',
                'name' => 'Popup Selamat Datang & Panduan',
                'type' => 'popup_modal',
                'title' => 'Selamat Datang di Faiilmov!',
                'content' => 'Nikmati ribuan film, series, dan drama pendek (dracin) kualitas HD dengan subtitle Indonesia terlengkap dan fitur Nonton Bareng (Watch Party).',
                'image_url' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=800&q=80',
                'button_text' => 'Mulai Nonton',
                'button_url' => '/',
                'button_target' => '_self',
                'icon' => 'sparkles',
                'position' => 'top',
                'theme_color' => 'amber',
                'target_page' => 'home',
                'target_device' => 'all',
                'target_audience' => 'guest',
                'is_dismissible' => true,
                'dismiss_duration_hours' => -1, // once forever
            ],
            [
                'id' => 'maintenance_bar',
                'name' => 'Pemberitahuan Pemeliharaan Server',
                'type' => 'broadcast_bar',
                'title' => 'Pemberitahuan Pemeliharaan Server',
                'content' => 'Kami sedang mengoptimasi server streaming untuk kecepatan yang lebih stabil.',
                'button_text' => 'Cek Status',
                'button_url' => '#',
                'button_target' => '_self',
                'icon' => 'wrench',
                'position' => 'top',
                'theme_color' => 'purple',
                'target_page' => 'all',
                'target_device' => 'all',
                'target_audience' => 'all',
                'is_dismissible' => false,
                'dismiss_duration_hours' => 0,
            ],
            [
                'id' => 'promo_highlight',
                'name' => 'Promo Nonton Bareng (Watch Party)',
                'type' => 'promo_banner',
                'title' => 'Nonton Bareng Teman & Komunitas',
                'content' => 'Buat room Watch Party kamu sendiri, ajak teman streaming bareng dengan live chat interaktif!',
                'image_url' => 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?w=800&q=80',
                'button_text' => 'Buka Watch Party',
                'button_url' => '/watch-parties',
                'button_target' => '_self',
                'icon' => 'tv',
                'position' => 'content_top',
                'theme_color' => 'emerald',
                'target_page' => 'home',
                'target_device' => 'all',
                'target_audience' => 'all',
                'is_dismissible' => true,
                'dismiss_duration_hours' => 24,
            ],
        ];
    }
}
