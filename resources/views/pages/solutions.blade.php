<x-layouts.app :title="__('site.solutions.title')" :description="__('site.meta.solutions_description')">

    @include('partials.page-header', [
        'title' => __('site.solutions.title'),
        'lead' => __('site.solutions.lead'),
        'breadcrumbs' => [['label' => __('site.nav.solutions')]],
    ])

    <nav aria-label="{{ __('site.solutions.title') }}" class="sticky top-16 z-20 border-b border-graphite-200 bg-paper lg:top-20">
        <ul class="container-site flex gap-1 overflow-x-auto py-3">
            @foreach ($solutions as $i => $solution)
                <li class="shrink-0">
                    <a href="#{{ $solution['segment']->value }}" class="inline-flex min-h-10 items-center gap-2 rounded-full px-4 text-sm text-graphite-600 transition-colors hover:bg-paper-2 hover:text-ink">
                        <span class="font-mono text-xs text-amber-700">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>{{ __('site.segments.'.$solution['segment']->value.'.label') }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @foreach ($solutions as $i => $solution)
        @php
            $segment = $solution['segment'];
            $key = 'site.segments.'.$segment->value;
            $wa = $settings->whatsappUrl(__('site.solutions.whatsapp_message', ['segment' => __($key.'.label')]));
        @endphp
        <section id="{{ $segment->value }}" class="scroll-mt-36 py-16 lg:py-24 {{ $i % 2 ? 'bg-white' : '' }}" aria-labelledby="{{ $segment->value }}-title">
            {{-- grid-cols-1 = minmax(0, 1fr): without it the implicit mobile track grows to the
                 unwrapped width of long model names in the equipment list and the page scrolls sideways. --}}
            <div class="container-site grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
                <figure class="lg:col-span-7 {{ $i % 2 ? 'lg:order-2' : '' }}" data-reveal>
                    <x-editorial-image :name="$segment->image()" sizes="(min-width: 1024px) 55vw, 92vw" class="aspect-[3/2] w-full object-cover" />
                </figure>
                <div class="min-w-0 lg:col-span-5 {{ $i % 2 ? 'lg:order-1' : '' }}">
                    <p class="eyebrow"><span class="text-amber-700">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span> / {{ __('site.home.solutions.label') }}</p>
                    <h2 id="{{ $segment->value }}-title" class="mt-5 text-title font-semibold">{{ __($key.'.label') }}</h2>
                    <p class="mt-5 text-lead text-graphite-600">{{ __($key.'.text') }}</p>
                    <ul class="mt-8 space-y-3">
                        @foreach (__($key.'.needs') as $need)
                            <li class="flex items-start gap-3">
                                <x-heroicon-m-check class="mt-0.5 size-5 shrink-0 text-amber-700" aria-hidden="true" /> <span>{{ $need }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-10">
                        <p class="eyebrow">{{ __('site.solutions.recommended') }}</p>
                        @if ($solution['products']->isEmpty())
                            <p class="mt-4 text-graphite-600">{{ __('site.solutions.no_products') }}</p>
                        @else
                            <ul class="mt-4 divide-y divide-graphite-200 border-y border-graphite-200">
                                @foreach ($solution['products'] as $product)
                                    <li>
                                        <a href="{{ localized_route('products.show', $product->slug) }}" class="group flex items-center gap-4 py-3">
                                            <span class="size-14 shrink-0 overflow-hidden bg-paper-2">
                                                @if ($product->primaryImage)
                                                    <img src="{{ $product->primaryImage->url(480) }}" alt="" width="56" height="56" loading="lazy" decoding="async" class="size-full object-cover">
                                                @endif
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate font-semibold group-hover:underline group-hover:underline-offset-4">{{ $product->displayName() }}</span>
                                                <span class="block truncate font-mono text-xs text-graphite-500">{{ $product->category?->translate('name') }}</span>
                                            </span>
                                            <span class="shrink-0 font-mono text-sm">
                                                {{ $settings->get('show_prices', true) && $product->hasPrice() ? $product->formattedPrice() : __('site.price.on_request') }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        @if ($wa)
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-accent">
                                <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" /> {{ __('site.cta.consult_whatsapp') }}
                            </a>
                        @endif
                        <a href="{{ localized_route('contact') }}#inquiry" class="btn btn-outline">{{ __('site.solutions.cta') }}</a>
                    </div>
                </div>
            </div>
        </section>
    @endforeach

</x-layouts.app>
