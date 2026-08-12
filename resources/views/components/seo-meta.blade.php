@props([
    'film' => null,
    'title' => null,
    'description' => null,
    'keywords' => null,
    'url' => null,
    'image' => null,
    'type' => null,
    'robots' => null,
    'schema' => null,
])

@php
    if ($film) {
        $finalTitle = $title ?? $film->seo_title;
        $finalDescription = $description ?? $film->seo_description;
        $finalKeywords = $keywords ?? $film->seo_keywords;
        $finalImage = $image ?? ($film->backdrop_url ?: $film->poster_url ?: asset('images/logo.png'));
        $finalType = $type ?? ($film->subject_type === 'series' ? 'video.tv_show' : 'video.movie');
        $finalUrl = $url ?? route('film.show', $film->slug);
        $finalSchema = $schema ?? $film->schema_json_ld_array;
    } else {
        $finalTitle = $title ?? 'faiilmov | Nonton Film & TV Series Streaming Subtitle Indonesia';
        $finalDescription = $description ?? 'Streaming & nonton film online subtitle Indonesia gratis HD. Katalog ribuan film bioskop, drama series, anime, dan serial TV favorit di faiilmov.';
        $finalKeywords = $keywords ?? 'nonton film, streaming film, film gratis, film sub indo, faiilmov, serial tv, moviebox, anime, streaming bioskop';
        $finalImage = $image ?? asset('images/logo.png');
        $finalType = $type ?? 'website';
        $finalUrl = $url ?? url()->current();
        $finalSchema = $schema ?? [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'faiilmov',
            'url' => url('/'),
            'description' => 'Streaming & nonton film online subtitle Indonesia gratis HD.',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('browse') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }
    $finalRobots = $robots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
@endphp

<!-- Primary Meta Tags -->
<title>{{ $finalTitle }}</title>
<meta name="title" content="{{ $finalTitle }}">
<meta name="description" content="{{ $finalDescription }}">
<meta name="keywords" content="{{ $finalKeywords }}">
<meta name="robots" content="{{ $finalRobots }}">
<meta name="author" content="faiilmov">
<link rel="canonical" href="{{ $finalUrl }}">

<!-- Open Graph / Facebook / WhatsApp -->
<meta property="og:type" content="{{ $finalType }}">
<meta property="og:site_name" content="faiilmov">
<meta property="og:url" content="{{ $finalUrl }}">
<meta property="og:title" content="{{ $finalTitle }}">
<meta property="og:description" content="{{ $finalDescription }}">
<meta property="og:image" content="{{ $finalImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="id_ID">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $finalUrl }}">
<meta name="twitter:title" content="{{ $finalTitle }}">
<meta name="twitter:description" content="{{ $finalDescription }}">
<meta name="twitter:image" content="{{ $finalImage }}">

<!-- Schema.org JSON-LD Structured Data -->
@if(!empty($finalSchema))
<script type="application/ld+json">
{!! json_encode($finalSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endif
