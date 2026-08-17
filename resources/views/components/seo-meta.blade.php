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
    $cmsSetting = \App\Models\SiteSetting::current();

    if ($film) {
        $finalTitle = $title ?? $film->seo_title;
        $finalDescription = $description ?? $film->seo_description;
        $finalKeywords = $keywords ?? $film->seo_keywords;
        $finalImage = $image ?? ($film->backdrop_url ?: $film->poster_url ?: $cmsSetting->logo_url);
        $finalType = $type ?? ($film->subject_type === 'series' ? 'video.tv_show' : 'video.movie');
        $finalUrl = $url ?? route('film.show', $film->slug);
        $finalSchema = $schema ?? $film->schema_json_ld_array;
    } else {
        $finalTitle = $title ?? ($cmsSetting->seo_meta_title ?: ($cmsSetting->site_name . ' | Streaming Film & Series Subtitle Indonesia'));
        $finalDescription = $description ?? ($cmsSetting->seo_meta_description ?: 'Streaming & nonton film online subtitle Indonesia gratis HD.');
        $finalKeywords = $keywords ?? ($cmsSetting->seo_meta_keywords ?: 'nonton film, streaming film, film gratis, film sub indo, serial tv');
        $finalImage = $image ?? ($cmsSetting->seo_og_image_url ?: $cmsSetting->logo_url);
        $finalType = $type ?? 'website';
        $finalUrl = $url ?? ($cmsSetting->seo_canonical_url ?: url()->current());
        $finalSchema = $schema ?? [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $cmsSetting->site_name,
            'url' => url('/'),
            'description' => $finalDescription,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('browse') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }
    
    // Normalize and decode pre-escaped HTML entities so Blade does not double-escape in tags
    $finalTitle = html_entity_decode(strip_tags((string)$finalTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $finalDescription = html_entity_decode(strip_tags((string)$finalDescription), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $finalKeywords = html_entity_decode(strip_tags((string)$finalKeywords), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $finalRobots = $robots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
@endphp

<!-- Primary Meta Tags -->
<title>{{ $finalTitle }}</title>
<meta name="title" content="{{ $finalTitle }}">
<meta name="description" content="{{ $finalDescription }}">
<meta name="keywords" content="{{ $finalKeywords }}">
<meta name="robots" content="{{ $finalRobots }}">
<meta name="author" content="{{ $cmsSetting->site_name }}">
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
