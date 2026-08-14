<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Film;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'site_name' => Setting::get('site_name', 'faiilmov'),
            'site_description' => Setting::get('site_description', 'Platform streaming film dan nonton bareng gratis.'),
            'support_email' => Setting::get('support_email', 'support@faiilmov.my.id'),
            'site_logo_url' => Setting::get('site_logo_url', asset('images/logo.png')),
            'featured_film_ids' => json_decode(Setting::get('featured_film_ids', '[]'), true) ?: [],
            
            // Feature Flags
            'feature_watch_party' => Setting::get('feature_watch_party', '1') === '1',
            'feature_dracin' => Setting::get('feature_dracin', '1') === '1',
            'feature_ai_autorate' => Setting::get('feature_ai_autorate', '1') === '1',
            'feature_registration' => Setting::get('feature_registration', '1') === '1',

            // Masked API Keys
            'moviebox_api_key' => Setting::get('moviebox_api_key', ''),
            'nvidia_api_key' => Setting::get('nvidia_api_key', ''),
            'itunes_api_key' => Setting::get('itunes_api_key', ''),

            // Maintenance Mode
            'maintenance_mode' => Setting::get('maintenance_mode', '0') === '1',
            'maintenance_message' => Setting::get('maintenance_message', 'Sistem sedang dalam pemeliharaan berkala untuk meningkatkan performa streaming.'),
        ];

        $allFilms = Film::whereIn('subject_type', ['movie', 'series'])->orderBy('title')->get(['id', 'title', 'release_year', 'subject_type', 'poster_url']);

        return view('admin.settings.index', compact('settings', 'allFilms'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'site_description' => 'nullable|string|max:550',
            'support_email' => 'nullable|email|max:150',
            'featured_film_ids' => 'nullable|array',
            'featured_film_ids.*' => 'exists:films,id',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',

            // Feature flags
            'feature_watch_party' => 'nullable|boolean',
            'feature_dracin' => 'nullable|boolean',
            'feature_ai_autorate' => 'nullable|boolean',
            'feature_registration' => 'nullable|boolean',

            // API Keys
            'moviebox_api_key' => 'nullable|string|max:255',
            'nvidia_api_key' => 'nullable|string|max:255',
            'itunes_api_key' => 'nullable|string|max:255',

            // Maintenance
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:500',
        ]);

        Setting::set('site_name', $validated['site_name']);
        Setting::set('site_description', $validated['site_description'] ?? '');
        Setting::set('support_email', $validated['support_email'] ?? 'support@faiilmov.my.id');
        Setting::set('featured_film_ids', json_encode(array_values(array_map('intval', $validated['featured_film_ids'] ?? []))));

        // Feature flags
        Setting::set('feature_watch_party', $request->has('feature_watch_party') ? '1' : '0');
        Setting::set('feature_dracin', $request->has('feature_dracin') ? '1' : '0');
        Setting::set('feature_ai_autorate', $request->has('feature_ai_autorate') ? '1' : '0');
        Setting::set('feature_registration', $request->has('feature_registration') ? '1' : '0');

        // API keys (only update if provided and not masked dummy)
        if ($request->filled('moviebox_api_key')) {
            Setting::set('moviebox_api_key', $request->moviebox_api_key);
        }
        if ($request->filled('nvidia_api_key')) {
            Setting::set('nvidia_api_key', $request->nvidia_api_key);
        }
        if ($request->filled('itunes_api_key')) {
            Setting::set('itunes_api_key', $request->itunes_api_key);
        }

        // Maintenance Mode
        $maintenanceEnabled = $request->has('maintenance_mode');
        Setting::set('maintenance_mode', $maintenanceEnabled ? '1' : '0');
        if ($request->filled('maintenance_message')) {
            Setting::set('maintenance_message', $validated['maintenance_message']);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('site_logo_url', Storage::url($path));
        }

        AdminActivityLog::log('updated_settings', 'Memperbarui Pengaturan Sistem, API Keys, dan Status Maintenance.');

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan situs dan konfigurasi berhasil diperbarui.');
    }
}
