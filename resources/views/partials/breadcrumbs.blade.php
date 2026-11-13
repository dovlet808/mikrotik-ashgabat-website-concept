@php $items = $items ?? []; @endphp
<nav aria-label="{{ __('site.nav.breadcrumbs') }}" class="font-mono text-xs text-graphite-500">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <li><a href="{{ localized_route('home') }}" class="hover:text-ink">{{ __('site.nav.home') }}</a></li>
        @foreach ($items as $item)
            <li aria-hidden="true">/</li>
            <li>
                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-ink">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="text-ink">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
