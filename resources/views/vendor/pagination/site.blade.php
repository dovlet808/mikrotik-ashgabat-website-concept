@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4 border-t border-graphite-200 pt-6">
        @if ($paginator->onFirstPage())
            <span class="inline-flex size-11 items-center justify-center rounded-full border border-graphite-200 text-graphite-300" aria-hidden="true"><x-heroicon-m-arrow-left class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex size-11 items-center justify-center rounded-full border border-graphite-300 hover:border-ink" aria-label="{{ __('pagination.previous') }}"><x-heroicon-m-arrow-left class="size-4" aria-hidden="true" /></a>
        @endif

        <ul class="flex items-center gap-1 font-mono text-sm">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="px-2 text-graphite-500">{{ $element }}</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex size-11 items-center justify-center rounded-full bg-ink text-paper">{{ str_pad($page, 2, '0', STR_PAD_LEFT) }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex size-11 items-center justify-center rounded-full hover:bg-paper-2">{{ str_pad($page, 2, '0', STR_PAD_LEFT) }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex size-11 items-center justify-center rounded-full border border-graphite-300 hover:border-ink" aria-label="{{ __('pagination.next') }}"><x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></a>
        @else
            <span class="inline-flex size-11 items-center justify-center rounded-full border border-graphite-200 text-graphite-300" aria-hidden="true"><x-heroicon-m-arrow-right class="size-4" /></span>
        @endif
    </nav>
@endif
