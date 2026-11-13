@php
    $title = $category ? $category->translate('name') : __('site.catalog.title');
    $lead = $category ? $category->translate('description') : __('site.catalog.lead');
    $action = $category ? localized_route('catalog.category', $category->slug) : localized_route('catalog.index');
    $breadcrumbs = $category
        ? [['label' => __('site.nav.catalog'), 'url' => localized_route('catalog.index')], ['label' => $title]]
        : [['label' => __('site.nav.catalog')]];
    $resetUrl = $action;
@endphp
<x-layouts.app :title="$title" :description="$lead ?? __('site.meta.catalog_description')">

    @include('partials.page-header', ['title' => $title, 'lead' => $lead, 'breadcrumbs' => $breadcrumbs])

    <div class="container-site py-10 lg:py-14">
        {{-- Category tabs --}}
        <nav aria-label="{{ __('site.catalog.category') }}" class="-mx-5 overflow-x-auto px-5 pb-2 sm:mx-0 sm:px-0 lg:overflow-visible">
            <ul class="flex min-w-max gap-2 lg:min-w-0 lg:flex-wrap">
                <li>
                    <a href="{{ localized_route('catalog.index') }}" @if (! $category) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center gap-2 rounded-full border border-graphite-300 px-5 text-sm transition-colors hover:border-ink aria-[current=page]:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-paper">
                        {{ __('site.catalog.all_categories') }} <span class="font-mono text-xs opacity-60">{{ $totalCount }}</span>
                    </a>
                </li>
                @foreach ($categories as $item)
                    <li>
                        <a href="{{ localized_route('catalog.category', $item->slug) }}" @if ($category?->is($item)) aria-current="page" @endif
                           class="inline-flex min-h-11 items-center gap-2 rounded-full border border-graphite-300 px-5 text-sm transition-colors hover:border-ink aria-[current=page]:border-ink aria-[current=page]:bg-ink aria-[current=page]:text-paper">
                            {{ $item->translate('name') }} <span class="font-mono text-xs opacity-60">{{ $item->published_count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="mt-10 grid gap-10 lg:grid-cols-12 lg:gap-12">
            {{-- Filters --}}
            <aside class="lg:col-span-3">
                <details open data-collapse-mobile @if ($filters->isFiltered()) data-keep-open @endif class="group rounded-2xl border border-graphite-200 bg-white lg:border-0 lg:bg-transparent">
                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between px-5 font-medium lg:hidden [&::-webkit-details-marker]:hidden">
                        <span class="flex items-center gap-2"><x-heroicon-o-adjustments-horizontal class="size-5" aria-hidden="true" /> {{ __('site.catalog.show_filters') }}</span>
                        <x-heroicon-m-chevron-down class="size-5 transition-transform group-open:rotate-180" aria-hidden="true" />
                    </summary>
                    <form method="GET" action="{{ $action }}" role="search" data-autosubmit class="space-y-8 px-5 pb-6 lg:block lg:px-0 lg:pb-0">
                        <div>
                            <label for="catalog-q" class="eyebrow mb-3 block">{{ __('site.catalog.search') }}</label>
                            <div class="relative">
                                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-graphite-400" aria-hidden="true" />
                                <input id="catalog-q" type="search" name="q" value="{{ $filters->search }}" maxlength="80" placeholder="{{ __('site.catalog.search_placeholder') }}" class="field pl-12" autocomplete="off">
                            </div>
                        </div>

                        <fieldset>
                            <legend class="eyebrow mb-3">{{ __('site.catalog.segment') }}</legend>
                            <div class="space-y-1">
                                @foreach ([null, ...$segments] as $segment)
                                    <label class="flex min-h-10 cursor-pointer items-center gap-3 rounded-lg px-2 text-sm hover:bg-white">
                                        <input type="radio" name="segment" value="{{ $segment?->value }}" @checked($filters->segment === $segment) class="size-4 accent-ink">
                                        {{ $segment ? __('site.segments.'.$segment->value.'.label') : __('site.catalog.all_segments') }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="eyebrow mb-3">{{ __('site.catalog.availability') }}</legend>
                            <div class="space-y-1">
                                @foreach ([null, ...$availabilities] as $availability)
                                    <label class="flex min-h-10 cursor-pointer items-center gap-3 rounded-lg px-2 text-sm hover:bg-white">
                                        <input type="radio" name="availability" value="{{ $availability?->value }}" @checked($filters->availability === $availability) class="size-4 accent-ink">
                                        {{ $availability ? __('site.availability.'.$availability->value) : __('site.catalog.any_availability') }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div>
                            <label for="catalog-sort" class="eyebrow mb-3 block">{{ __('site.catalog.sort') }}</label>
                            <select id="catalog-sort" name="sort" class="field">
                                @foreach (\App\Support\CatalogFilters::SORTS as $sort)
                                    <option value="{{ $sort }}" @selected($filters->sort === $sort)>{{ __('site.catalog.sorts.'.$sort) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="btn btn-primary min-h-11 flex-1">{{ __('site.catalog.apply') }}</button>
                            @if ($filters->isFiltered() || $filters->sort !== 'recommended')
                                <a href="{{ $resetUrl }}" class="btn btn-outline min-h-11">{{ __('site.catalog.reset') }}</a>
                            @endif
                        </div>
                    </form>
                </details>
            </aside>

            {{-- Results --}}
            <section class="lg:col-span-9" aria-labelledby="results-title">
                <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-graphite-200 pb-4">
                    <h2 id="results-title" class="font-mono text-sm text-graphite-600" aria-live="polite">
                        {{ trans_choice('site.catalog.results', $products->total(), ['count' => $products->total()]) }}
                        @if ($filters->search) <span class="text-ink">{{ __('site.catalog.results_for', ['query' => $filters->search]) }}</span> @endif
                    </h2>
                    @if ($products->lastPage() > 1)
                        <p class="font-mono text-xs text-graphite-500">{{ str_pad($products->currentPage(), 2, '0', STR_PAD_LEFT) }} {{ __('site.misc.of') }} {{ str_pad($products->lastPage(), 2, '0', STR_PAD_LEFT) }}</p>
                    @endif
                </div>

                @if ($products->isEmpty())
                    <div class="mt-10 rounded-3xl border border-dashed border-graphite-300 p-10 text-center">
                        <x-heroicon-o-magnifying-glass class="mx-auto size-8 text-graphite-400" aria-hidden="true" />
                        <p class="mt-5 text-heading font-semibold">{{ __('site.catalog.empty_title') }}</p>
                        <p class="mx-auto mt-3 max-w-md text-graphite-600">{{ __('site.catalog.empty_text') }}</p>
                        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                            <a href="{{ $resetUrl }}" class="btn btn-outline">{{ __('site.catalog.reset') }}</a>
                            @if ($wa = $settings->whatsappUrl(__('site.whatsapp.general')))
                                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-accent">{{ __('site.cta.whatsapp') }}</a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-8 grid gap-px overflow-hidden border border-graphite-200 bg-graphite-200 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $i => $product)
                            <x-product-card :product="$product" :eager="$i < 3" sizes="(min-width: 1280px) 24vw, (min-width: 640px) 46vw, 92vw" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links('vendor.pagination.site') }}</div>
                @endif

                <p class="mt-10 text-sm text-graphite-500">{{ setting_text('price_note') ?? __('site.price.default_note') }}</p>
            </section>
        </div>
    </div>

</x-layouts.app>
