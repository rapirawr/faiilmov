<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\AdminActivityLog;
use App\Services\AdService;
use Illuminate\Http\Request;

class AdminAdController extends Controller
{
    /**
     * Display the Adsterra & Ads Management dashboard.
     */
    public function index()
    {
        $settings = AdService::getAllSettings();

        return view('admin.ads.index', compact('settings'));
    }

    /**
     * Update Ads settings and script codes.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Master Switch & Testing
            'ads_enabled'       => 'nullable|boolean',
            'ads_show_to_admin' => 'nullable|boolean',

            // Popunder (OnClick)
            'ads_popunder_enabled' => 'nullable|boolean',
            'ads_popunder_code'    => 'nullable|string',

            // Social Bar
            'ads_socialbar_enabled' => 'nullable|boolean',
            'ads_socialbar_code'    => 'nullable|string',

            // Banner Player Top
            'ads_banner_player_top_enabled' => 'nullable|boolean',
            'ads_banner_player_top_code'    => 'nullable|string',

            // Banner Player Bottom
            'ads_banner_player_bottom_enabled' => 'nullable|boolean',
            'ads_banner_player_bottom_code'    => 'nullable|string',

            // Banner Grid / Native
            'ads_banner_grid_enabled' => 'nullable|boolean',
            'ads_banner_grid_code'    => 'nullable|string',

            // Direct Link
            'ads_direct_link_enabled'         => 'nullable|boolean',
            'ads_direct_link_url'             => 'nullable|url|max:500',
            'ads_direct_link_on_download'   => 'nullable|boolean',
            'ads_direct_link_on_server_vip' => 'nullable|boolean',

            // Anti-Adblock
            'ads_anti_adblock_enabled' => 'nullable|boolean',
            'ads_anti_adblock_title'   => 'nullable|string|max:150',
            'ads_anti_adblock_message' => 'nullable|string|max:500',
        ]);

        // 1. Master Toggle & Admin Testing Switch
        Setting::set('ads_enabled', $request->has('ads_enabled') ? '1' : '0');
        Setting::set('ads_show_to_admin', $request->has('ads_show_to_admin') ? '1' : '0');

        // 2. Popunder
        Setting::set('ads_popunder_enabled', $request->has('ads_popunder_enabled') ? '1' : '0');
        Setting::set('ads_popunder_code', $request->input('ads_popunder_code', ''));

        // 3. Social Bar
        Setting::set('ads_socialbar_enabled', $request->has('ads_socialbar_enabled') ? '1' : '0');
        Setting::set('ads_socialbar_code', $request->input('ads_socialbar_code', ''));

        // 4. Banner Player Top
        Setting::set('ads_banner_player_top_enabled', $request->has('ads_banner_player_top_enabled') ? '1' : '0');
        Setting::set('ads_banner_player_top_code', $request->input('ads_banner_player_top_code', ''));

        // 5. Banner Player Bottom
        Setting::set('ads_banner_player_bottom_enabled', $request->has('ads_banner_player_bottom_enabled') ? '1' : '0');
        Setting::set('ads_banner_player_bottom_code', $request->input('ads_banner_player_bottom_code', ''));

        // 6. Banner Grid / Native
        Setting::set('ads_banner_grid_enabled', $request->has('ads_banner_grid_enabled') ? '1' : '0');
        Setting::set('ads_banner_grid_code', $request->input('ads_banner_grid_code', ''));

        // 7. Direct Link
        Setting::set('ads_direct_link_enabled', $request->has('ads_direct_link_enabled') ? '1' : '0');
        Setting::set('ads_direct_link_url', $request->input('ads_direct_link_url', ''));
        Setting::set('ads_direct_link_on_download', $request->has('ads_direct_link_on_download') ? '1' : '0');
        Setting::set('ads_direct_link_on_server_vip', $request->has('ads_direct_link_on_server_vip') ? '1' : '0');

        // 8. Anti-Adblock
        Setting::set('ads_anti_adblock_enabled', $request->has('ads_anti_adblock_enabled') ? '1' : '0');
        if ($request->filled('ads_anti_adblock_title')) {
            Setting::set('ads_anti_adblock_title', $request->input('ads_anti_adblock_title'));
        }
        if ($request->filled('ads_anti_adblock_message')) {
            Setting::set('ads_anti_adblock_message', $request->input('ads_anti_adblock_message'));
        }

        AdminActivityLog::log('updated_ads_settings', 'Memperbarui Konfigurasi Iklan Adsterra & Pengaturan Anti-Adblock.');

        return redirect()->route('admin.ads.index')->with('success', 'Konfigurasi iklan dan slot Adsterra berhasil disimpan.');
    }
}
