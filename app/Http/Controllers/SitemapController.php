<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\Genre;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $films = Film::orderByDesc('updated_at')->get(['slug', 'updated_at']);
        $genres = Genre::all(['slug', 'updated_at']);

        $content = view('sitemap', compact('films', 'genres'))->render();

        return response($content, 200)->header('Content-Type', 'text/xml');
    }

    public function robots()
    {
        $content = "User-agent: *\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /watch-party/room/\n";
        $content .= "Allow: /\n\n";
        $content .= "Sitemap: " . url('/sitemap.xml') . "\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
