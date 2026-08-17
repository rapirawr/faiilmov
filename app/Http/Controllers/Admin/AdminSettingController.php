<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Setting;
use App\Models\Film;
use App\Models\AdminActivityLog;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSettingController extends Controller
{
    public function __construct(
        protected SiteSettingsService $settingsService
    ) {}

    /**
     * Render the unified Admin General Settings page
     * GET /admin/settings
     */
    public function index()
    {
        $siteSetting = $this->settingsService->get();

        $legacySettings = [
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
        ];

        $allFilms = Film::whereIn('subject_type', ['movie', 'series'])
            ->orderBy('title')
            ->get(['id', 'title', 'release_year', 'subject_type', 'poster_url']);

        return view('admin.settings.index', compact('siteSetting', 'legacySettings', 'allFilms'));
    }

    /**
     * API: Get complete settings payload for Admin UI
     * GET /admin/api/settings
     */
    public function apiGet(): JsonResponse
    {
        $siteSetting = $this->settingsService->get();

        return response()->json([
            'site_setting' => [
                'id' => $siteSetting->id,
                'site_name' => $siteSetting->site_name,
                'site_tagline' => $siteSetting->site_tagline,
                'logo_path' => $siteSetting->logo_path,
                'logo_url' => $siteSetting->logo_url,
                'logo_dark_path' => $siteSetting->logo_dark_path,
                'logo_dark_url' => $siteSetting->logo_dark_url,
                'favicon_path' => $siteSetting->favicon_path,
                'favicon_url' => $siteSetting->favicon_url,
                'primary_color' => $siteSetting->primary_color ?: '#ffffff',
                'secondary_color' => $siteSetting->secondary_color ?: '#a1a1aa',
                'background_color' => $siteSetting->background_color ?: '#09090b',
                'seo_meta_title' => $siteSetting->seo_meta_title,
                'seo_meta_description' => $siteSetting->seo_meta_description,
                'seo_meta_keywords' => $siteSetting->seo_meta_keywords,
                'seo_og_image' => $siteSetting->seo_og_image,
                'seo_og_image_url' => $siteSetting->seo_og_image_url,
                'seo_canonical_url' => $siteSetting->seo_canonical_url,
                'footer_text' => $siteSetting->footer_text,
                'social_links' => $siteSetting->social_links ?: [
                    'instagram' => '',
                    'twitter' => '',
                    'telegram' => '',
                    'discord' => '',
                    'youtube' => '',
                    'tiktok' => '',
                ],
                'contact_email' => $siteSetting->contact_email,
                'maintenance_mode' => (bool)$siteSetting->maintenance_mode,
                'maintenance_message' => $siteSetting->maintenance_message,
            ],
            'features' => [
                'feature_watch_party' => Setting::get('feature_watch_party', '1') === '1',
                'feature_dracin' => Setting::get('feature_dracin', '1') === '1',
                'feature_ai_autorate' => Setting::get('feature_ai_autorate', '1') === '1',
                'feature_registration' => Setting::get('feature_registration', '1') === '1',
            ],
            'api_keys' => [
                'moviebox_api_key' => Setting::get('moviebox_api_key', '') ? '••••••••••••' : '',
                'nvidia_api_key' => Setting::get('nvidia_api_key', '') ? '••••••••••••' : '',
                'itunes_api_key' => Setting::get('itunes_api_key', '') ? '••••••••••••' : '',
            ],
            'featured_film_ids' => json_decode(Setting::get('featured_film_ids', '[]'), true) ?: [],
        ]);
    }

    /**
     * API: Update Global Settings
     * PUT /admin/api/settings
     */
    public function apiUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Branding
            'site_name' => 'required|string|max:120',
            'site_tagline' => 'nullable|string|max:255',

            // Colors (Hex regex)
            'primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'background_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],

            // SEO
            'seo_meta_title' => 'required|string|max:150',
            'seo_meta_description' => 'nullable|string|max:1000',
            'seo_meta_keywords' => 'nullable|string|max:500',
            'seo_canonical_url' => 'nullable|string|max:255',

            // Display & Social
            'footer_text' => 'nullable|string|max:1000',
            'social_links' => 'nullable',
            'contact_email' => 'nullable|email|max:150',

            // Maintenance
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:1000',

            // Features & API keys (Optional)
            'feature_watch_party' => 'nullable|boolean',
            'feature_dracin' => 'nullable|boolean',
            'feature_ai_autorate' => 'nullable|boolean',
            'feature_registration' => 'nullable|boolean',
            'moviebox_api_key' => 'nullable|string|max:255',
            'nvidia_api_key' => 'nullable|string|max:255',
            'itunes_api_key' => 'nullable|string|max:255',
            'featured_film_ids' => 'nullable|array',
            'featured_film_ids.*' => 'exists:films,id',
        ]);

        // 1. Update CMS Global Settings singleton
        $siteData = [
            'site_name' => $validated['site_name'],
            'site_tagline' => $validated['site_tagline'] ?? null,
            'primary_color' => $validated['primary_color'] ?? '#ffffff',
            'secondary_color' => $validated['secondary_color'] ?? '#a1a1aa',
            'background_color' => $validated['background_color'] ?? '#09090b',
            'seo_meta_title' => $validated['seo_meta_title'],
            'seo_meta_description' => $validated['seo_meta_description'] ?? null,
            'seo_meta_keywords' => $validated['seo_meta_keywords'] ?? null,
            'seo_canonical_url' => $validated['seo_canonical_url'] ?? null,
            'footer_text' => $validated['footer_text'] ?? null,
            'social_links' => $validated['social_links'] ?? [],
            'contact_email' => $validated['contact_email'] ?? null,
            'maintenance_mode' => (bool)($validated['maintenance_mode'] ?? false),
            'maintenance_message' => $validated['maintenance_message'] ?? null,
        ];

        $updatedSetting = $this->settingsService->update($siteData);

        // 2. Update Feature flags & API Keys in Setting model
        if ($request->has('feature_watch_party')) {
            Setting::set('feature_watch_party', $request->boolean('feature_watch_party') ? '1' : '0');
        }
        if ($request->has('feature_dracin')) {
            Setting::set('feature_dracin', $request->boolean('feature_dracin') ? '1' : '0');
        }
        if ($request->has('feature_ai_autorate')) {
            Setting::set('feature_ai_autorate', $request->boolean('feature_ai_autorate') ? '1' : '0');
        }
        if ($request->has('feature_registration')) {
            Setting::set('feature_registration', $request->boolean('feature_registration') ? '1' : '0');
        }

        if ($request->filled('moviebox_api_key') && !str_contains($request->moviebox_api_key, '••••')) {
            Setting::set('moviebox_api_key', $request->moviebox_api_key);
        }
        if ($request->filled('nvidia_api_key') && !str_contains($request->nvidia_api_key, '••••')) {
            Setting::set('nvidia_api_key', $request->nvidia_api_key);
        }
        if ($request->filled('itunes_api_key') && !str_contains($request->itunes_api_key, '••••')) {
            Setting::set('itunes_api_key', $request->itunes_api_key);
        }
        if ($request->has('featured_film_ids')) {
            Setting::set('featured_film_ids', json_encode(array_values(array_map('intval', $validated['featured_film_ids'] ?? []))));
        }

        AdminActivityLog::log('updated_settings', 'Memperbarui CMS Global Settings (Branding, Warna, SEO, Tampilan, Maintenance).');

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan global CMS berhasil disimpan & cache telah diperbarui!',
            'site_setting' => $updatedSetting,
        ]);
    }

    /**
     * API: Dedicated instant upload for Logo, Favicon, and OG Image
     * POST /admin/api/settings/logo
     */
    public function apiUploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg,svg,webp,ico|max:5120',
            'type' => 'required|string|in:logo,logo_dark,favicon,og_image',
        ]);

        $url = $this->settingsService->uploadLogo($request->file('file'), $request->input('type'));

        AdminActivityLog::log('uploaded_logo', "Mengunggah aset branding {$request->input('type')}.");

        return response()->json([
            'success' => true,
            'message' => 'File branding berhasil diunggah!',
            'url' => $url,
            'type' => $request->input('type'),
        ]);
    }

    /**
     * Classic HTML Form fallback submission
     * POST /admin/settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:120',
            'site_tagline' => 'nullable|string|max:255',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'background_color' => 'nullable|string|max:20',
            'seo_meta_title' => 'required|string|max:150',
            'seo_meta_description' => 'nullable|string|max:1000',
            'seo_meta_keywords' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:150',
            'maintenance_message' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
            'logo_dark' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
            'favicon' => 'nullable|file|mimes:png,ico,svg|max:2048',
            'seo_og_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $siteData = [
            'site_name' => $validated['site_name'],
            'site_tagline' => $validated['site_tagline'] ?? null,
            'primary_color' => $validated['primary_color'] ?? '#ffffff',
            'secondary_color' => $validated['secondary_color'] ?? '#a1a1aa',
            'background_color' => $validated['background_color'] ?? '#09090b',
            'seo_meta_title' => $validated['seo_meta_title'],
            'seo_meta_description' => $validated['seo_meta_description'] ?? null,
            'seo_meta_keywords' => $validated['seo_meta_keywords'] ?? null,
            'footer_text' => $validated['footer_text'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'maintenance_mode' => $request->has('maintenance_mode'),
            'maintenance_message' => $validated['maintenance_message'] ?? null,
        ];

        // Process Social links array from inputs
        if ($request->has('social_links')) {
            $siteData['social_links'] = $request->input('social_links');
        }

        $this->settingsService->update($siteData);

        // Upload files if provided
        if ($request->hasFile('logo')) {
            $this->settingsService->uploadLogo($request->file('logo'), 'logo');
        }
        if ($request->hasFile('logo_dark')) {
            $this->settingsService->uploadLogo($request->file('logo_dark'), 'logo_dark');
        }
        if ($request->hasFile('favicon')) {
            $this->settingsService->uploadLogo($request->file('favicon'), 'favicon');
        }
        if ($request->hasFile('seo_og_image')) {
            $this->settingsService->uploadLogo($request->file('seo_og_image'), 'og_image');
        }

        // Feature flags
        Setting::set('feature_watch_party', $request->has('feature_watch_party') ? '1' : '0');
        Setting::set('feature_dracin', $request->has('feature_dracin') ? '1' : '0');
        Setting::set('feature_ai_autorate', $request->has('feature_ai_autorate') ? '1' : '0');
        Setting::set('feature_registration', $request->has('feature_registration') ? '1' : '0');

        // API keys
        if ($request->filled('moviebox_api_key')) {
            Setting::set('moviebox_api_key', $request->moviebox_api_key);
        }
        if ($request->filled('nvidia_api_key')) {
            Setting::set('nvidia_api_key', $request->nvidia_api_key);
        }
        if ($request->filled('itunes_api_key')) {
            Setting::set('itunes_api_key', $request->itunes_api_key);
        }

        AdminActivityLog::log('updated_settings', 'Memperbarui CMS Global Settings via form.');

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan CMS Global berhasil diperbarui!');
    }
}
