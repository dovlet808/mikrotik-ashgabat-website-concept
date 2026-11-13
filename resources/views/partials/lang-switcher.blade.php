@php
    $dark = $dark ?? false;
    $landmark = $landmark ?? false;
    $tag = $landmark ? 'nav' : 'div';
@endphp
<{{ $tag }} @unless ($landmark) role="group" @endunless aria-label="{{ __('site.nav.language') }}" class="flex items-center">
    <ul class="flex items-center gap-0.5 font-mono text-xs">
        @foreach (config('site.locales') as $code => $meta)
            <li>
                <a href="{{ locale_switch_url($code) }}"
                   hreflang="{{ $meta['hreflang'] }}"
                   lang="{{ $meta['hreflang'] }}"
                   title="{{ $meta['name'] }}"
                   @if ($code === app()->getLocale()) aria-current="true" @endif
                   class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-full px-2 tracking-[0.08em] transition-colors duration-200 {{ $dark ? 'text-graphite-400 hover:text-paper aria-[current=true]:bg-graphite-800 aria-[current=true]:text-paper' : 'text-graphite-500 hover:text-ink aria-[current=true]:bg-paper-2 aria-[current=true]:text-ink' }}">
                    <span class="sr-only">{{ $meta['name'] }}</span>
                    <span aria-hidden="true">{{ $meta['short'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</{{ $tag }}>
