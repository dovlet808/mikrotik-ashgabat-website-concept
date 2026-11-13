@php
    $whatsapp = $settings->whatsappUrl(__('site.whatsapp.general'));
    $heroImage = $hero?->primaryImage;
@endphp
<x-layouts.app>

    {{-- 1. Hero --}}
    <section aria-labelledby="hero-title" class="relative">
        <div class="container-site grid gap-12 pt-8 pb-16 sm:pt-12 lg:grid-cols-12 lg:gap-10 lg:pt-16 lg:pb-24">
            <div class="flex flex-col lg:col-span-7 lg:pr-6">
                <p class="eyebrow flex items-center gap-3" data-reveal>
                    <span class="led led-live bg-signal" aria-hidden="true"></span>{{ __('site.home.hero.kicker') }}
                </p>
                <h1 id="hero-title" class="mt-7 text-display font-semibold text-balance" data-reveal style="--reveal-delay: 80ms">
                    {{ __('site.home.hero.title') }}
                </h1>
                <p class="mt-7 max-w-xl text-lead text-pretty text-graphite-600" data-reveal style="--reveal-delay: 160ms">
                    {{ __('site.home.hero.lead') }}
                </p>
                <div class="mt-10 flex flex-col gap-3 sm:flex-row" data-reveal style="--reveal-delay: 240ms">
                    <a href="{{ localized_route('catalog.index') }}" class="btn btn-primary">
                        {{ __('site.cta.catalog') }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                    </a>
                    @if ($whatsapp)
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-outline">
                            <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" /> {{ __('site.cta.consult_whatsapp') }}
                        </a>
                    @endif
                </div>

                <dl class="mt-14 grid grid-cols-3 gap-4 border-t border-graphite-200 pt-6 lg:mt-auto lg:pt-8" data-reveal style="--reveal-delay: 320ms">
                    @foreach ([[$productCount, __('site.facts.models')], [$services->count(), __('site.facts.services')], [__('site.facts.currency_code'), __('site.facts.currency')]] as [$value, $label])
                        <div class="flex flex-col-reverse justify-end gap-2">
                            <dt class="text-xs text-graphite-500 sm:text-sm">{{ $label }}</dt>
                            <dd class="font-mono text-2xl font-medium sm:text-3xl">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            @if ($hero && $heroImage)
                <figure class="lg:col-span-5" data-reveal style="--reveal-delay: 120ms">
                    <a href="{{ localized_route('products.show', $hero->slug) }}" class="group block overflow-hidden bg-paper-2">
                        <x-product-image :image="$heroImage" :product="$hero" eager sizes="(min-width: 1024px) 38vw, 92vw"
                                         class="aspect-[4/5] w-full object-cover transition-transform duration-[1.4s] ease-(--ease-out-quart) group-hover:scale-[1.025]" />
                    </a>
                    <figcaption class="mt-4 flex items-baseline justify-between gap-4 border-t border-graphite-200 pt-4 font-mono text-xs">
                        <span class="hidden uppercase tracking-[0.12em] text-graphite-500 sm:inline">{{ __('site.home.hero.caption') }}</span>
                        <a href="{{ localized_route('products.show', $hero->slug) }}" class="min-w-0 truncate text-ink underline decoration-graphite-300 underline-offset-4 hover:decoration-ink">
                            {{ $hero->name }}@if ($hero->product_code) · {{ $hero->product_code }}@endif
                        </a>
                        @if ($hero->hasPrice() && $settings->get('show_prices', true))
                            <span class="shrink-0 text-ink">{{ $hero->formattedPrice() }}</span>
                        @endif
                    </figcaption>
                </figure>
            @endif
        </div>
        <div class="border-y border-graphite-200 bg-paper-2/60">
            <p class="container-site py-4 font-mono text-xs uppercase tracking-[0.12em] text-graphite-600">{{ __('site.home.hero.lines') }}</p>
        </div>
    </section>

    {{-- 2. Value proposition --}}
    <section class="bg-white py-20 lg:py-28" aria-labelledby="values-title">
        <div class="container-site">
            <x-section-head index="01" :label="__('site.home.values.label')" :title="__('site.home.values.title')" id="values-title" />
            <ol class="mt-14 grid gap-px overflow-hidden border border-graphite-200 bg-graphite-200 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (__('site.home.values.items') as $i => $item)
                    <li class="flex flex-col bg-white p-6 sm:p-8" data-reveal style="--reveal-delay: {{ $i * 70 }}ms">
                        <span class="font-mono text-xs text-graphite-500" aria-hidden="true">PORT {{ $i + 1 }}</span>
                        <span class="mt-10 flex items-center gap-3 text-heading font-semibold">
                            <span class="led bg-amber-500" aria-hidden="true"></span>{{ $item['title'] }}
                        </span>
                        <span class="mt-4 text-graphite-600">{{ $item['text'] }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 3. Featured equipment --}}
    <section class="py-20 lg:py-28" aria-labelledby="featured-title">
        <div class="container-site">
            <x-section-head index="02" :label="__('site.home.featured.label')" :title="__('site.home.featured.title')" :lead="__('site.home.featured.lead')"
                            :action="localized_route('catalog.index')" :action-label="__('site.cta.all_products')" id="featured-title" />
            <div class="mt-14 grid gap-px overflow-hidden border border-graphite-200 bg-graphite-200 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $i => $product)
                    <x-product-card :product="$product" data-reveal style="--reveal-delay: {{ $i * 70 }}ms" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Product categories --}}
    <section class="pb-20 lg:pb-28" aria-labelledby="categories-title">
        <div class="container-site">
            <x-section-head index="03" :label="__('site.home.categories.label')" :title="__('site.home.categories.title')" id="categories-title" />
            <ul class="mt-14 border-t border-graphite-200">
                @foreach ($categories->values() as $i => $category)
                    <li class="border-b border-graphite-200">
                        <a href="{{ localized_route('catalog.category', $category->slug) }}" class="group grid grid-cols-[2.5rem_1fr_auto] items-center gap-4 py-6 transition-colors duration-300 hover:bg-white sm:grid-cols-[4rem_1fr_auto_auto] sm:gap-6 sm:px-4 lg:py-8">
                            <span class="font-mono text-xs text-graphite-500">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>
                                <span class="block text-heading font-semibold sm:text-[1.9rem] lg:text-[2.2rem]">{{ $category->translate('name') }}</span>
                                @if ($description = $category->translate('description'))
                                    <span class="mt-1 hidden max-w-xl text-sm text-graphite-500 md:block">{{ $description }}</span>
                                @endif
                            </span>
                            <span class="hidden items-center gap-2 sm:flex" aria-hidden="true">
                                @foreach ($category->products->take(3) as $product)
                                    @if ($product->primaryImage)
                                        <img src="{{ $product->primaryImage->url(480) }}" alt="" width="56" height="56" loading="lazy" decoding="async"
                                             class="size-14 rounded-full border border-graphite-200 bg-paper-2 object-cover transition-transform duration-500 group-hover:-translate-y-0.5">
                                    @endif
                                @endforeach
                            </span>
                            <span class="flex items-center gap-3 font-mono text-xs text-graphite-600">
                                <span class="hidden sm:inline">{{ trans_choice('site.catalog.models', $category->published_count, ['count' => $category->published_count]) }}</span>
                                <span class="inline-flex size-10 items-center justify-center rounded-full border border-graphite-300 transition-colors duration-300 group-hover:border-ink group-hover:bg-ink group-hover:text-paper">
                                    <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 5. Network services (the one dark section) --}}
    <section class="bg-graphite-900 py-20 text-paper lg:py-28" aria-labelledby="services-title">
        <div class="container-site">
            <x-section-head dark index="04" :label="__('site.home.services.label')" :title="__('site.home.services.title')" :lead="__('site.home.services.lead')"
                            :action="localized_route('services.index')" :action-label="__('site.cta.services')" id="services-title" />
            <div class="mt-14 grid gap-12 lg:grid-cols-12 lg:gap-10">
                <ul class="grid gap-px self-start overflow-hidden border border-graphite-700 bg-graphite-700 sm:grid-cols-2 lg:col-span-7">
                    @foreach ($services as $i => $service)
                        <li class="flex gap-4 bg-graphite-900 p-6" data-reveal style="--reveal-delay: {{ ($i % 4) * 60 }}ms">
                            <x-dynamic-component :component="'heroicon-o-'.$service->icon" class="mt-0.5 size-6 shrink-0 text-amber-500" aria-hidden="true" />
                            <span>
                                <span class="block font-semibold">{{ $service->translate('title') }}</span>
                                <span class="mt-2 block text-sm text-graphite-300">{{ $service->translate('summary') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
                <figure class="lg:col-span-5" data-reveal>
                    <x-editorial-image name="services-cabling" ratio="16:9" sizes="(min-width: 1024px) 36vw, 92vw" class="aspect-[4/5] w-full object-cover sm:aspect-[16/10] lg:aspect-[4/5]" />
                </figure>
            </div>
        </div>
    </section>

    {{-- 6. Solutions --}}
    <section class="py-20 lg:py-28" aria-labelledby="solutions-title">
        <div class="container-site">
            <x-section-head index="05" :label="__('site.home.solutions.label')" :title="__('site.home.solutions.title')"
                            :action="localized_route('solutions.index')" :action-label="__('site.cta.solutions')" id="solutions-title" />
            <ul class="mt-14 grid gap-x-6 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($segments as $i => $segment)
                    <li data-reveal style="--reveal-delay: {{ ($i % 3) * 70 }}ms">
                        <a href="{{ localized_route('solutions.index') }}#{{ $segment->value }}" class="group block">
                            <span class="block overflow-hidden bg-paper-2">
                                <x-editorial-image :name="$segment->image()" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 46vw, 92vw"
                                                   class="aspect-[3/2] w-full object-cover transition-transform duration-700 ease-(--ease-out-quart) group-hover:scale-[1.03]" />
                            </span>
                            <span class="mt-5 flex items-baseline gap-3">
                                <span class="font-mono text-xs text-amber-700">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-heading font-semibold underline decoration-transparent underline-offset-[6px] transition-colors group-hover:decoration-ink">{{ __('site.segments.'.$segment->value.'.label') }}</span>
                            </span>
                            <span class="mt-3 block text-graphite-600">{{ __('site.segments.'.$segment->value.'.text') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 7. Why choose --}}
    <section class="bg-white py-20 lg:py-28" aria-labelledby="why-title">
        <div class="container-site">
            <x-section-head index="06" :label="__('site.home.why.label')" :title="__('site.home.why.title')" id="why-title" />
            <ul class="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                @foreach (__('site.home.why.items') as $i => $item)
                    <li class="border-t-2 border-ink pt-6" data-reveal style="--reveal-delay: {{ $i * 70 }}ms">
                        <span class="font-mono text-xs text-graphite-500">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mt-4 text-heading font-semibold">{{ $item['title'] }}</h3>
                        <p class="mt-3 text-graphite-600">{{ $item['text'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 8. Consultation CTA --}}
    <section class="py-20 lg:py-28" aria-labelledby="consult-title">
        <div class="container-site">
            <div class="grid overflow-hidden rounded-[2rem] lg:grid-cols-12">
                <div class="flex flex-col bg-amber-500 p-8 text-ink sm:p-12 lg:col-span-5">
                    <p class="font-mono text-label uppercase"><span>07</span> / {{ __('site.home.consult.label') }}</p>
                    <h2 id="consult-title" class="mt-8 text-title font-semibold text-balance">{{ __('site.home.consult.title') }}</h2>
                    <p class="mt-6 text-lead">{{ __('site.home.consult.lead') }}</p>
                    <div class="mt-10 flex flex-col gap-3 lg:mt-auto lg:pt-12">
                        @if ($whatsapp)
                            <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-primary w-full sm:w-auto sm:self-start">
                                <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" /> {{ __('site.cta.consult_whatsapp') }}
                            </a>
                        @endif
                        @if ($settings->phoneHref())
                            <a href="{{ $settings->phoneHref() }}" class="font-mono text-sm underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ $settings->phoneDisplay() }}</a>
                        @endif
                    </div>
                </div>
                <div class="bg-white p-6 sm:p-12 lg:col-span-7">
                    <x-inquiry-form type="consultation" />
                </div>
            </div>
        </div>
    </section>

    {{-- 9. Delivery --}}
    <section class="pb-20 lg:pb-28" aria-labelledby="delivery-title">
        <div class="container-site">
            <div class="grid items-center gap-8 border-y border-graphite-200 py-10 lg:grid-cols-12 lg:py-14">
                <p class="eyebrow lg:col-span-3"><span class="text-amber-700">08</span> / {{ __('site.home.delivery.label') }}</p>
                <div class="flex items-start gap-5 lg:col-span-6">
                    <x-heroicon-o-truck class="mt-1 size-8 shrink-0 text-amber-700" aria-hidden="true" />
                    <div>
                        <h2 id="delivery-title" class="text-heading font-semibold sm:text-[2rem]">{{ setting_text('delivery_text') ?? __('site.home.delivery.title') }}</h2>
                        <p class="mt-3 text-graphite-600">{{ setting_text('delivery_note') ?? __('site.home.delivery.text') }}</p>
                    </div>
                </div>
                <div class="lg:col-span-3 lg:text-right">
                    <a href="{{ localized_route('contact') }}" class="link-arrow">{{ __('site.cta.contact') }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></a>
                </div>
            </div>
        </div>
    </section>

    {{-- 10. Contact --}}
    <section class="bg-white py-20 lg:py-28" aria-labelledby="contact-title">
        <div class="container-site">
            <x-section-head index="09" :label="__('site.home.contact.label')" :title="__('site.home.contact.title')" id="contact-title" />
            @include('partials.contact-tiles')
        </div>
    </section>

</x-layouts.app>
