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
        $siteName = Setting::get('site_name', 'faiilmov');
        $siteDescription = Setting::get('site_description', 'Platform streaming film dan nonton bareng gratis.');
        $featuredIds = json_decode(Setting::get('featured_film_ids', '[]'), true) ?: [];

        $allFilms = Film::whereIn('subject_type', ['movie', 'series'])->orderBy('title')->get(['id', 'title', 'release_year', 'subject_type', 'poster_url']);

        return view('admin.settings.index', compact('siteName', 'siteDescription', 'featuredIds', 'allFilms'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'site_description' => 'nullable|string|max:550',
            'featured_film_ids' => 'nullable|array',
            'featured_film_ids.*' => 'exists:films,id',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        Setting::set('site_name', $validated['site_name']);
        Setting::set('site_description', $validated['site_description'] ?? '');
        Setting::set('featured_film_ids', json_encode(array_values(array_map('intval', $validated['featured_film_ids'] ?? []))));

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('site_logo_url', Storage::url($path));
        }

        AdminActivityLog::log('updated_settings', 'Memperbarui Pengaturan Umum Situs.');

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan situs berhasil diperbarui.');
    }
}
