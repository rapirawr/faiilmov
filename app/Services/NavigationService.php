<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    private const CACHE_KEY_SIDEBAR = 'faiilmov_nav_sidebar';
    private const CACHE_KEY_SIDEBAR_WIDGET = 'faiilmov_nav_sidebar_widget';
    private const CACHE_KEY_NAVBAR  = 'faiilmov_nav_navbar';

    /**
     * Get active sidebar navigation menu items.
     */
    public static function getSidebarMenu(): array
    {
        return Cache::remember(self::CACHE_KEY_SIDEBAR, 3600, function () {
            $saved = Setting::get('navigation_sidebar');
            if ($saved) {
                $decoded = is_array($saved) ? $saved : json_decode($saved, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return $decoded;
                }
            }
            return self::getDefaultSidebarMenu();
        });
    }

    /**
     * Get bottom sidebar widget settings (Get faiilmov App Card).
     */
    public static function getSidebarWidget(): array
    {
        return Cache::remember(self::CACHE_KEY_SIDEBAR_WIDGET, 3600, function () {
            $saved = Setting::get('navigation_sidebar_widget');
            if ($saved) {
                $decoded = is_array($saved) ? $saved : json_decode($saved, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return $decoded;
                }
            }
            return self::getDefaultSidebarWidget();
        });
    }

    /**
     * Save sidebar menu items order and settings.
     */
    public static function saveSidebarMenu(array $items): void
    {
        Setting::set('navigation_sidebar', json_encode($items));
        Cache::forget(self::CACHE_KEY_SIDEBAR);
    }

    /**
     * Save bottom sidebar widget settings.
     */
    public static function saveSidebarWidget(array $widget): void
    {
        Setting::set('navigation_sidebar_widget', json_encode($widget));
        Cache::forget(self::CACHE_KEY_SIDEBAR_WIDGET);
    }

    /**
     * Reset sidebar menu to default.
     */
    public static function resetToDefault(): void
    {
        Setting::set('navigation_sidebar', json_encode(self::getDefaultSidebarMenu()));
        Setting::set('navigation_sidebar_widget', json_encode(self::getDefaultSidebarWidget()));
        Cache::forget(self::CACHE_KEY_SIDEBAR);
        Cache::forget(self::CACHE_KEY_SIDEBAR_WIDGET);
    }

    /**
     * Default bottom sidebar widget banner.
     */
    public static function getDefaultSidebarWidget(): array
    {
        return [
            'is_active'      => true,
            'title'          => 'Get Faiilmov',
            'button_text'    => 'Mobile',
            'button_url'     => '/download-app',
            'button_icon'    => 'smartphone',
            'button2_active' => false,
            'button2_text'   => 'macOS',
            'button2_url'    => '#',
            'button2_icon'   => 'laptop',
        ];
    }

    /**
     * Default public sidebar menu arrangement.
     */
    public static function getDefaultSidebarMenu(): array
    {
        return [
            [
                'id'         => 'home',
                'label'      => 'Home',
                'icon'       => 'home',
                'url'        => '/',
                'route'      => 'home',
                'is_active'  => true,
                'badge'      => '',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'series',
                'label'      => 'Series',
                'icon'       => 'tv',
                'url'        => '/browse?type=series',
                'route'      => '',
                'is_active'  => true,
                'badge'      => '',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'dracin',
                'label'      => 'Dracin (Feed)',
                'icon'       => 'tv-2',
                'url'        => '/dracin',
                'route'      => 'dracin.index',
                'is_active'  => true,
                'badge'      => 'HOT',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'movie',
                'label'      => 'Movie',
                'icon'       => 'clapperboard',
                'url'        => '/browse?type=movie',
                'route'      => '',
                'is_active'  => true,
                'badge'      => '',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'animation',
                'label'      => 'Animation',
                'icon'       => 'sparkles',
                'url'        => '/browse?genre=animation',
                'route'      => '',
                'is_active'  => true,
                'badge'      => '',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'collections',
                'label'      => 'Koleksi AI',
                'icon'       => 'layers',
                'url'        => '/collections',
                'route'      => 'collections.index',
                'is_active'  => true,
                'badge'      => 'AI',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'most_watched',
                'label'      => 'Most Watched',
                'icon'       => 'flame',
                'url'        => '/browse?sort=rating_desc',
                'route'      => '',
                'is_active'  => true,
                'badge'      => '',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'changelog',
                'label'      => 'Changelog',
                'icon'       => 'history',
                'url'        => '/changelog',
                'route'      => 'changelog',
                'is_active'  => true,
                'badge'      => '',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'watch_party',
                'label'      => 'Watch Party',
                'icon'       => 'users',
                'url'        => '/watch-party',
                'route'      => 'watch-party.index',
                'is_active'  => true,
                'badge'      => 'LIVE',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'leaderboard',
                'label'      => 'Leaderboard',
                'icon'       => 'trophy',
                'url'        => '/leaderboard',
                'route'      => 'leaderboard',
                'is_active'  => true,
                'badge'      => 'TOP',
                'target'     => '_self',
                'visibility' => 'all',
            ],
            [
                'id'         => 'wrapped',
                'label'      => 'Movie Wrapped',
                'icon'       => 'sparkles',
                'url'        => '/wrapped',
                'route'      => 'wrapped',
                'is_active'  => true,
                'badge'      => '2026',
                'target'     => '_self',
                'visibility' => 'auth_only',
            ],
            [
                'id'         => 'get_app',
                'label'      => 'Download App',
                'icon'       => 'smartphone',
                'url'        => '/download-app',
                'route'      => 'download.app',
                'is_active'  => true,
                'badge'      => 'APK',
                'target'     => '_self',
                'visibility' => 'all',
            ],
        ];
    }

    /**
     * Default top navbar menu arrangement (Action Pill Buttons).
     */
    public static function getDefaultNavbarMenu(): array
    {
        return [
            [
                'id'         => 'nav_download',
                'label'      => 'App Mobile',
                'icon'       => 'smartphone',
                'url'        => '/download-app',
                'route'      => 'download.app',
                'is_active'  => true,
                'badge'      => '',
                'target'     => '_self',
                'visibility' => 'all',
            ],
        ];
    }
}
