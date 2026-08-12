{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Static & Public Pages -->
    <url>
        <loc>{{ route('browse') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('changelog') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ route('download.app') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ route('privacy-policy') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.4</priority>
    </url>
    <url>
        <loc>{{ route('terms-of-service') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.4</priority>
    </url>

    <!-- Genre Filter Pages -->
    @foreach($genres as $genre)
    <url>
        <loc>{{ route('genre.show', $genre->slug) }}</loc>
        <lastmod>{{ $genre->updated_at ? $genre->updated_at->toAtomString() : date('c') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    <!-- Film Detail & Watch Pages -->
    @foreach($films as $film)
    <url>
        <loc>{{ route('film.show', $film->slug) }}</loc>
        <lastmod>{{ $film->updated_at ? $film->updated_at->toAtomString() : date('c') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('film.watch', $film->slug) }}</loc>
        <lastmod>{{ $film->updated_at ? $film->updated_at->toAtomString() : date('c') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    @endforeach
</urlset>
