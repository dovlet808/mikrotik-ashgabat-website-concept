{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as [$route, $params, $updated])
@foreach ($locales as $locale)
    <url>
        <loc>{{ localized_route($route, $params, $locale) }}</loc>
@foreach ($locales as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ config('site.locales.'.$alternate.'.hreflang') }}" href="{{ localized_route($route, $params, $alternate) }}"/>
@endforeach
@if ($updated)
        <lastmod>{{ $updated->toAtomString() }}</lastmod>
@endif
    </url>
@endforeach
@endforeach
</urlset>
