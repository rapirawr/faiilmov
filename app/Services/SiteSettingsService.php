<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteSettingsService
{
    /**
     * Get current singleton settings
     */
    public function get(): SiteSetting
    {
        return SiteSetting::current();
    }

    /**
     * Update global site settings and invalidate cache
     */
    public function update(array $data): SiteSetting
    {
        $setting = $this->get();

        // Sanitize Hex Colors if provided
        $colorFields = ['primary_color', 'secondary_color', 'background_color'];
        foreach ($colorFields as $field) {
            if (isset($data[$field])) {
                $color = trim((string)$data[$field]);
                if (!empty($color) && !str_starts_with($color, '#')) {
                    $color = '#' . $color;
                }
                $data[$field] = $color;
            }
        }

        // Handle Social Links JSON array
        if (isset($data['social_links']) && is_string($data['social_links'])) {
            $decoded = json_decode($data['social_links'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['social_links'] = $decoded;
            }
        }

        $setting->update($data);
        SiteSetting::clearCache();

        return $this->get();
    }

    /**
     * Upload and store logo, favicon, or OG image
     */
    public function uploadLogo(UploadedFile $file, string $type = 'logo'): string
    {
        $setting = $this->get();
        $filename = $type . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('branding', $filename, 'public');

        switch ($type) {
            case 'logo':
            case 'site_logo':
                $setting->update(['logo_path' => $path]);
                $url = $setting->logo_url;
                break;
            case 'logo_dark':
                $setting->update(['logo_dark_path' => $path]);
                $url = $setting->logo_dark_url;
                break;
            case 'favicon':
                $setting->update(['favicon_path' => $path]);
                $url = $setting->favicon_url;
                break;
            case 'og_image':
            case 'seo_og_image':
                $setting->update(['seo_og_image' => $path]);
                $url = $setting->seo_og_image_url;
                break;
            default:
                $setting->update(['logo_path' => $path]);
                $url = $setting->logo_url;
                break;
        }

        SiteSetting::clearCache();
        return $url;
    }

    /**
     * Get sanitized public settings payload (cached for high performance)
     */
    public function getPublicPayload(): array
    {
        return Cache::remember('site_settings_public', 86400, function () {
            $setting = $this->get();

            return [
                'branding' => [
                    'site_name' => $setting->site_name,
                    'site_tagline' => $setting->site_tagline,
                    'logo_url' => $setting->logo_url,
                    'logo_dark_url' => $setting->logo_dark_url,
                    'favicon_url' => $setting->favicon_url,
                ],
                'theme' => [
                    'primary_color' => $setting->primary_color ?: '#ffffff',
                    'secondary_color' => $setting->secondary_color ?: '#a1a1aa',
                    'background_color' => $setting->background_color ?: '#09090b',
                ],
                'seo' => [
                    'meta_title' => $setting->seo_meta_title,
                    'meta_description' => $setting->seo_meta_description,
                    'meta_keywords' => $setting->seo_meta_keywords,
                    'og_image_url' => $setting->seo_og_image_url,
                    'canonical_url' => $setting->seo_canonical_url,
                ],
                'display' => [
                    'footer_text' => $setting->footer_text,
                    'social_links' => $setting->social_links ?: [],
                    'contact_email' => $setting->contact_email,
                ],
                'maintenance' => [
                    'is_maintenance' => (bool)$setting->maintenance_mode,
                    'message' => $setting->maintenance_message,
                ],
            ];
        });
    }
    
}
