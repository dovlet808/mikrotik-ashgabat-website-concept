@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'jsonLd' => [],
])
@php
    $locale = app()->getLocale();
    $siteName = __('site.meta.site_name');
    $fullTitle = $title ? $title.' — '.__('site.meta.title_suffix') : __('site.meta.default_title').' | '.$siteName;
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags($description ?? __('site.meta.default_description')), 158);
    $page = (int) request()->query('page', 1);
    $canonical = url()->current().($page > 1 ? '?page='.$page : '');
    $ogImage = $image ?? asset('images/og-default.jpg');
    $ogLocales = ['ru' => 'ru_RU', 'tk' => 'tk_TM', 'en' => 'en_US'];
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => localized_route('home'),
        'telephone' => $settings->get('phone'),
        'areaServed' => ['@type' => 'Country', 'name' => 'Turkmenistan'],
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Ashgabat', 'addressCountry' => 'TM'],
        'sameAs' => array_values(array_filter([$settings->get('instagram_url')])),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical }}">
    @foreach (config('site.locales') as $code => $meta)
        <link rel="alternate" hreflang="{{ $meta['hreflang'] }}" href="{{ locale_switch_url($code, false) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ locale_switch_url(config('site.default_locale'), false) }}">
    @unless (config('site.indexable'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? __('site.meta.default_title') }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="{{ $ogLocales[$locale] ?? 'ru_RU' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#f3f0eb">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @foreach (array_filter([$organization, ...$jsonLd]) as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endforeach
</head>
<body class="min-h-dvh pb-[4.5rem] md:pb-0">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-ink focus:px-5 focus:py-3 focus:text-paper">{{ __('site.nav.skip') }}</a>

    @include('partials.header')

    <main id="main" tabindex="-1" class="focus:outline-none">
        {{ $slot }}
    </main>

    @include('partials.footer')
    @include('partials.mobile-actions')
</body>
</html>
