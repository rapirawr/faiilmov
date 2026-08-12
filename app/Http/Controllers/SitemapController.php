<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Genre;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Generate dynamic sitemap XML with caching
     */
    public function index()
    {
        $content = Cache::remember('sitemap_xml', 86400, function () {
            $films = Film::orderByDesc('updated_at')->get(['slug', 'updated_at']);
            $genres = Genre::all(['slug', 'updated_at']);

            return view('sitemap', compact('films', 'genres'))->render();
        });

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }

    /**
     * Generate robots.txt
     */
    public function robots()
    {
        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin/',
            'Disallow: /profile',
            'Disallow: /watch-party/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /logout',
            'Disallow: /api/',
            'Allow: /',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
