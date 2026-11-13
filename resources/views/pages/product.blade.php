@php
    $images = $product->images;
    $main = $images->first();
    $summary = $product->translate('summary');
    $purpose = $product->translate('purpose');
    $description = collect(preg_split('/\R{2,}/u', (string) $product->translate('description')))->map(fn ($p) => trim($p))->filter();
    $url = localized_route('products.show', $product->slug);
    $whatsapp = $settings->whatsappUrl(__('site.product.whatsapp_message', ['name' => $product->displayName().($product->product_code ? ' ('.$product->product_code.')' : ''), 'url' => $url]));
    $showPrice = $settings->get('show_prices', true) && $product->hasPrice();

    $offer = $showPrice ? array_filter([
        '@type' => 'Offer',
        'price' => number_format((float) $product->price, 2, '.', ''),
        'priceCurrency' => $product->currency,
        'url' => $url,
        'availability' => $product->availability->schemaOrg(),
    ]) : null;
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->displayName(),
        'sku' => $product->product_code,
        'mpn' => $product->product_code,
        'brand' => ['@type' => 'Brand', 'name' => $product->brand],
        'description' => $summary,
        'image' => $images->map(fn ($i) => $i->url(1600))->values()->all(),
        'category' => $product->category?->translate('name'),
        'offers' => $offer,
    ]);
    $breadcrumbs = array_values(array_filter([
        ['label' => __('site.nav.catalog'), 'url' => localized_route('catalog.index')],
        $product->category ? ['label' => $product->category->translate('name'), 'url' => localized_route('catalog.category', $product->category->slug)] : null,
        ['label' => $product->name],
    ]));
@endphp
<x-layouts.app :title="$product->displayName()" :description="$summary" :image="$main?->url(1600)" type="product" :json-ld="[$schema]">

    <div class="container-site pt-8 lg:pt-12">
        @include('partials.breadcrumbs', ['items' => $breadcrumbs])
    </div>

    <section class="container-site grid gap-10 pt-8 pb-16 lg:grid-cols-12 lg:gap-12 lg:pb-24" aria-labelledby="product-title">
        {{-- Gallery --}}
        <div class="lg:col-span-7" data-gallery>
            @if ($main)
                <figure class="flex items-center justify-center bg-paper-2">
                    <img data-gallery-main
                         src="{{ $main->url(960) }}" srcset="{{ $main->srcset() }}"
                         sizes="(min-width: 1024px) 55vw, 100vw"
                         alt="{{ $main->altText($product) }}"
                         @if ($main->width) width="{{ $main->width }}" height="{{ $main->height }}" @endif
                         fetchpriority="high" decoding="async"
                         class="max-h-[78vh] w-auto object-contain">
                </figure>
                @if ($images->count() > 1)
                    <div class="mt-4 flex items-center gap-4">
                        <p class="hidden shrink-0 font-mono text-xs text-graphite-500 sm:block">
                            <span data-gallery-counter>01</span> / {{ str_pad($images->count(), 2, '0', STR_PAD_LEFT) }}
                        </p>
                        <ul class="flex gap-2 overflow-x-auto pb-1" aria-label="{{ __('site.product.gallery') }}">
                            @foreach ($images as $i => $image)
                                <li class="shrink-0">
                                    <button type="button" data-gallery-thumb
                                            data-src="{{ $image->url(960) }}" data-srcset="{{ $image->srcset() }}" data-alt="{{ $image->altText($product) }}"
                                            data-width="{{ $image->width }}" data-height="{{ $image->height }}"
                                            aria-current="{{ $i === 0 ? 'true' : 'false' }}"
                                            class="block size-20 overflow-hidden border-2 border-transparent bg-paper-2 transition-colors hover:border-graphite-300 aria-[current=true]:border-ink sm:size-24">
                                        <span class="sr-only">{{ __('site.product.photo', ['n' => $i + 1]) }}</span>
                                        <img src="{{ $image->url(480) }}" alt="" width="96" height="96" loading="lazy" decoding="async" class="size-full object-cover">
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <div class="flex aspect-[4/5] items-center justify-center bg-paper-2">
                    <x-heroicon-o-cube class="size-12 text-graphite-300" aria-hidden="true" />
                </div>
            @endif
        </div>

        {{-- Summary --}}
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-28">
                <p class="eyebrow">{{ $product->brand }}@if ($product->category) <span aria-hidden="true">·</span> {{ $product->category->translate('name') }}@endif</p>
                <h1 id="product-title" class="mt-4 text-title font-semibold">{{ $product->name }}</h1>

                @if ($product->product_code)
                    <dl class="mt-5 flex flex-wrap items-baseline gap-x-3 font-mono text-sm">
                        <dt class="text-graphite-500">{{ __('site.product.code') }}:</dt>
                        <dd class="select-all text-ink">{{ $product->product_code }}</dd>
                    </dl>
                @endif

                @if ($summary)
                    <p class="mt-6 text-lead text-pretty text-graphite-600">{{ $summary }}</p>
                @endif

                <div class="mt-8 border-y border-graphite-200 py-6">
                    <x-price :product="$product" size="lg" />
                    <x-availability :status="$product->availability" class="mt-4" />
                    <p class="mt-4 text-xs text-graphite-500">{{ setting_text('price_note') ?? __('site.price.default_note') }}</p>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
                    <a href="#inquiry" class="btn btn-primary flex-1">{{ $showPrice ? __('site.cta.request_info') : __('site.cta.request_price') }}</a>
                    @if ($whatsapp)
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-accent flex-1">
                            <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" /> WhatsApp
                        </a>
                    @endif
                </div>
                @if ($settings->phoneHref())
                    <p class="mt-4 text-sm text-graphite-600">{{ __('site.cta.call') }}: <a href="{{ $settings->phoneHref() }}" class="font-mono text-ink underline decoration-graphite-300 underline-offset-4 hover:decoration-ink">{{ $settings->phoneDisplay() }}</a></p>
                @endif

                @if ($purpose)
                    <div class="mt-10 rounded-2xl bg-white p-6">
                        <p class="eyebrow">{{ __('site.product.purpose') }}</p>
                        <p class="mt-3">{{ $purpose }}</p>
                    </div>
                @endif

                @if ($product->segmentEnums())
                    <div class="mt-8">
                        <p class="eyebrow">{{ __('site.product.suited') }}</p>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($product->segmentEnums() as $segment)
                                <li>
                                    <a href="{{ localized_route('solutions.index') }}#{{ $segment->value }}" class="inline-flex min-h-9 items-center rounded-full border border-graphite-300 px-4 text-sm transition-colors hover:border-ink">
                                        {{ __('site.segments.'.$segment->value.'.label') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Description & specifications --}}
    @if ($description->isNotEmpty() || $specGroups->isNotEmpty())
        <section class="bg-white py-16 lg:py-24" aria-labelledby="specs-title">
            <div class="container-site space-y-16">
                @if ($description->isNotEmpty())
                    <div class="grid gap-6 border-t border-graphite-200 pt-6 lg:grid-cols-12">
                        <h2 class="eyebrow lg:col-span-3">{{ __('site.product.overview') }}</h2>
                        <div class="prose-site max-w-3xl text-lg leading-relaxed text-graphite-700 lg:col-span-9">
                            @foreach ($description as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($specGroups->isNotEmpty())
                    <div class="grid gap-6 border-t border-graphite-200 pt-6 lg:grid-cols-12">
                        <div class="lg:col-span-3">
                            <h2 id="specs-title" class="eyebrow">{{ __('site.product.specs') }}</h2>
                            <p class="mt-4 max-w-xs text-xs text-graphite-500">{{ __('site.product.specs_note') }}</p>
                        </div>
                        <div class="space-y-10 lg:col-span-9">
                            @foreach ($specGroups as $group => $specs)
                                <div>
                                    @if ($group !== '')
                                        <h3 class="mb-3 font-semibold">{{ $group }}</h3>
                                    @endif
                                    <dl class="divide-y divide-graphite-200 border-y border-graphite-200">
                                        @foreach ($specs as $spec)
                                            <div class="grid gap-1 py-3 sm:grid-cols-3 sm:gap-6">
                                                <dt class="text-sm text-graphite-500">{{ $spec->translate('label') }}</dt>
                                                <dd class="sm:col-span-2">{{ $spec->translate('value') }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Inquiry --}}
    <section class="py-16 lg:py-24" aria-labelledby="ask-title">
        <div class="container-site grid gap-10 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <p class="eyebrow normal-case tracking-[0.06em]">{{ $product->displayName() }}</p>
                <h2 id="ask-title" class="mt-4 text-title font-semibold">{{ __('site.product.ask') }}</h2>
                <p class="mt-5 text-graphite-600">{{ __('site.product.ask_lead') }}</p>
            </div>
            <div class="rounded-[2rem] bg-white p-6 sm:p-10 lg:col-span-8">
                <x-inquiry-form type="product" :product="$product" />
            </div>
        </div>
    </section>

    {{-- Related --}}
    @if ($related->isNotEmpty())
        <section class="pb-20 lg:pb-28" aria-labelledby="related-title">
            <div class="container-site">
                <x-section-head :label="__('site.home.featured.label')" :title="__('site.product.related')" id="related-title"
                                :action="localized_route('catalog.index')" :action-label="__('site.cta.all_products')" />
                <div class="mt-12 grid gap-px overflow-hidden border border-graphite-200 bg-graphite-200 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.app>
