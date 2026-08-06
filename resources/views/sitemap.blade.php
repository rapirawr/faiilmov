{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ date('c') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Genre Pages -->
    @foreach($genres as $genre)
    <url>
        <loc>{{ url('/?genre=' . $genre->slug) }}</loc>
        <lastmod>{{ $genre->updated_at ? $genre->updated_at->toAtomString() : date('c') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    <!-- Film Pages -->
    @foreach($films as $film)
    <url>
        <loc>{{ route('film.show', $film->slug) }}</loc>
        <lastmod>{{ $film->updated_at ? $film->updated_at->toAtomString() : date('c') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    @endforeach
</urlset>
