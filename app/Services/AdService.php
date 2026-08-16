<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class AdService
{
    /**
     * Determine if ads should be rendered for the current user/visitor.
     * Admin users are exempt from ads.
     */
    public static function shouldShowAds(): bool
    {
        // 1. Check Master Switch
        $masterEnabled = Setting::get('ads_enabled', '1') === '1';
        if (!$masterEnabled) {
            return false;
        }

        // 2. Check Admin Exemption (bypassable via 'ads_show_to_admin' toggle for testing)
        $showToAdmin = Setting::get('ads_show_to_admin', '0') === '1';
        if (!$showToAdmin && Auth::check() && Auth::user()->isAdmin()) {
            return false;
        }

        // 3. Check Account-Specific Ad-Free flag
        if (Auth::check() && Auth::user()->is_ad_free) {
            return false;
        }

        return true;
    }

    /**
     * Check if a specific ad slot is enabled.
     */
    public static function isSlotEnabled(string $slot): bool
    {
        if (!static::shouldShowAds()) {
            return false;
        }

        return Setting::get("ads_{$slot}_enabled", '0') === '1';
    }

    /**
     * Get the configured code/script for a specific slot.
     */
    public static function getSlotCode(string $slot): string
    {
        return (string) Setting::get("ads_{$slot}_code", '');
    }

    /**
     * Render the slot if it is enabled and contains code.
     */
    public static function renderSlot(string $slot): ?string
    {
        if (!static::isSlotEnabled($slot)) {
            return null;
        }

        $code = trim(static::getSlotCode($slot));
        return $code !== '' ? $code : null;
    }

    /**
     * Get Direct Link URL (Adsterra Smartlink).
     */
    public static function getDirectLinkUrl(): ?string
    {
        if (!static::shouldShowAds()) {
            return null;
        }

        $enabled = Setting::get('ads_direct_link_enabled', '0') === '1';
        if (!$enabled) {
            return null;
        }

        $url = trim((string) Setting::get('ads_direct_link_url', ''));
        return $url !== '' ? $url : null;
    }

    /**
     * Check if Direct Link is active for Download button.
     */
    public static function isDirectLinkOnDownload(): bool
    {
        return static::shouldShowAds() 
            && Setting::get('ads_direct_link_enabled', '0') === '1'
            && Setting::get('ads_direct_link_on_download', '1') === '1'
            && !empty(Setting::get('ads_direct_link_url', ''));
    }

    /**
     * Check if Direct Link is active for VIP Server button.
     */
    public static function isDirectLinkOnServerVip(): bool
    {
        return static::shouldShowAds() 
            && Setting::get('ads_direct_link_enabled', '0') === '1'
            && Setting::get('ads_direct_link_on_server_vip', '1') === '1'
            && !empty(Setting::get('ads_direct_link_url', ''));
    }

    /**
     * Check if Anti-Adblock Detector is enabled.
     */
    public static function isAntiAdblockEnabled(): bool
    {
        // Don't show anti-adblock prompt to admins unless testing mode is on
        $showToAdmin = Setting::get('ads_show_to_admin', '0') === '1';
        if (!$showToAdmin && Auth::check() && Auth::user()->isAdmin()) {
            return false;
        }

        // Don't show anti-adblock to ad-free users
        if (Auth::check() && Auth::user()->is_ad_free) {
            return false;
        }

        $masterEnabled = Setting::get('ads_enabled', '1') === '1';
        $antiAdblockEnabled = Setting::get('ads_anti_adblock_enabled', '0') === '1';

        return $masterEnabled && $antiAdblockEnabled;
    }

    /**
     * Get all advertising settings for the Admin Management interface.
     */
    public static function getAllSettings(): array
    {
        return [
            // Master Toggle & Testing
            'ads_enabled'       => Setting::get('ads_enabled', '1') === '1',
            'ads_show_to_admin' => Setting::get('ads_show_to_admin', '0') === '1',

            // Popunder (OnClick)
            'ads_popunder_enabled' => Setting::get('ads_popunder_enabled', '0') === '1',
            'ads_popunder_code'    => Setting::get('ads_popunder_code', ''),

            // Social Bar (In-Page Push)
            'ads_socialbar_enabled' => Setting::get('ads_socialbar_enabled', '0') === '1',
            'ads_socialbar_code'    => Setting::get('ads_socialbar_code', ''),

            // Video Player Banners
            'ads_banner_player_top_enabled'    => Setting::get('ads_banner_player_top_enabled', '0') === '1',
            'ads_banner_player_top_code'       => Setting::get('ads_banner_player_top_code', ''),
            'ads_banner_player_bottom_enabled' => Setting::get('ads_banner_player_bottom_enabled', '0') === '1',
            'ads_banner_player_bottom_code'    => Setting::get('ads_banner_player_bottom_code', ''),

            // Grid / Native Banners (Home & Browse)
            'ads_banner_grid_enabled' => Setting::get('ads_banner_grid_enabled', '0') === '1',
            'ads_banner_grid_code'    => Setting::get('ads_banner_grid_code', ''),

            // Direct Link / Smartlink
            'ads_direct_link_enabled'         => Setting::get('ads_direct_link_enabled', '0') === '1',
            'ads_direct_link_url'             => Setting::get('ads_direct_link_url', ''),
            'ads_direct_link_on_download'   => Setting::get('ads_direct_link_on_download', '1') === '1',
            'ads_direct_link_on_server_vip' => Setting::get('ads_direct_link_on_server_vip', '1') === '1',

            // Anti-Adblock Detector
            'ads_anti_adblock_enabled' => Setting::get('ads_anti_adblock_enabled', '0') === '1',
            'ads_anti_adblock_title'   => Setting::get('ads_anti_adblock_title', 'Mohon Nonaktifkan Adblock'),
            'ads_anti_adblock_message' => Setting::get('ads_anti_adblock_message', 'Dukung kami agar tetap bisa menyajikan film & anime berkualitas secara gratis dengan menonaktifkan pemblokir iklan (Adblock) Anda.'),
        ];
    }
}
