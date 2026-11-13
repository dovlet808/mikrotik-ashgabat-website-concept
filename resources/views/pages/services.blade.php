<x-layouts.app :title="__('site.services.title')" :description="__('site.meta.services_description')">

    @include('partials.page-header', [
        'title' => __('site.services.title'),
        'lead' => __('site.services.lead'),
        'breadcrumbs' => [['label' => __('site.nav.services')]],
    ])

    {{-- Services list --}}
    <section class="py-16 lg:py-24" aria-label="{{ __('site.services.title') }}">
        <div class="container-site">
            <ol class="grid gap-px overflow-hidden border border-graphite-200 bg-graphite-200 md:grid-cols-2">
                @foreach ($services as $i => $service)
                    @php $wa = $settings->whatsappUrl(__('site.services.whatsapp_message', ['service' => $service->translate('title')])); @endphp
                    <li id="{{ $service->slug }}" class="flex scroll-mt-28 flex-col bg-paper p-6 sm:p-10" data-reveal style="--reveal-delay: {{ ($i % 2) * 70 }}ms">
                        <div class="flex items-start justify-between gap-6">
                            <span class="font-mono text-xs text-amber-700">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <x-dynamic-component :component="'heroicon-o-'.$service->icon" class="size-7 text-graphite-500" aria-hidden="true" />
                        </div>
                        <h2 class="mt-8 text-heading font-semibold sm:text-[1.9rem]">{{ $service->translate('title') }}</h2>
                        <p class="mt-4 text-lg text-graphite-700">{{ $service->translate('summary') }}</p>
                        @if ($paragraphs = $service->bodyParagraphs())
                            <div class="prose-site mt-4 text-graphite-600">
                                @foreach ($paragraphs as $paragraph)
                                    <p>{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-auto flex flex-wrap items-center gap-x-6 gap-y-3 pt-8">
                            <a href="{{ localized_route('contact', ['service' => $service->slug]) }}#inquiry" class="link-arrow text-sm">{{ __('site.misc.service_request') }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></a>
                            @if ($wa)
                                <a href="{{ $wa }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-graphite-600 hover:text-ink">
                                    <x-heroicon-o-chat-bubble-left-right class="size-4" aria-hidden="true" /> WhatsApp
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Existing or new network --}}
    <section class="bg-graphite-900 py-16 text-paper lg:py-24" aria-labelledby="network-title">
        <div class="container-site grid gap-10 lg:grid-cols-12 lg:items-stretch">
            <figure class="lg:col-span-6">
                <x-editorial-image name="services-cabling" ratio="16:9" sizes="(min-width: 1024px) 46vw, 92vw" class="aspect-[16/10] h-full w-full object-cover" />
            </figure>
            <div class="flex flex-col gap-px overflow-hidden border border-graphite-700 bg-graphite-700 lg:col-span-6">
                <h2 id="network-title" class="sr-only">{{ __('site.home.services.title') }}</h2>
                <div class="flex-1 bg-graphite-900 p-8 sm:p-10">
                    <p class="eyebrow-dark">A</p>
                    <h3 class="mt-6 text-heading font-semibold">{{ __('site.services.existing_title') }}</h3>
                    <p class="mt-3 text-graphite-300">{{ __('site.services.existing_text') }}</p>
                </div>
                <div class="flex-1 bg-graphite-900 p-8 sm:p-10">
                    <p class="eyebrow-dark">B</p>
                    <h3 class="mt-6 text-heading font-semibold">{{ __('site.services.new_title') }}</h3>
                    <p class="mt-3 text-graphite-300">{{ __('site.services.new_text') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Process as ports --}}
    <section class="py-16 lg:py-24" aria-labelledby="process-title">
        <div class="container-site">
            <x-section-head :label="__('site.services.process_label')" :title="__('site.services.process_title')" id="process-title" />
            <ol class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-5 lg:gap-4">
                @foreach (__('site.services.steps') as $i => $step)
                    <li class="relative" data-reveal style="--reveal-delay: {{ $i * 80 }}ms">
                        <div class="flex items-center gap-3">
                            <svg viewBox="0 0 48 40" class="h-10 w-12 shrink-0" aria-hidden="true">
                                <rect x="1" y="1" width="46" height="38" rx="3" class="fill-white stroke-graphite-300" stroke-width="1.5" />
                                <path d="M10 11h28v15h-6v5H16v-5h-6z" class="fill-graphite-900" />
                                <path d="M15 14v4M19 14v4M23 14v4M27 14v4M31 14v4" class="stroke-amber-400" stroke-width="1.4" />
                            </svg>
                            <span class="font-mono text-xs text-graphite-500">{{ $i + 1 }}</span>
                            @unless ($loop->last)
                                <span class="hidden h-px flex-1 bg-graphite-300 lg:block" aria-hidden="true"></span>
                            @endunless
                        </div>
                        <h3 class="mt-6 font-semibold">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-graphite-600">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Request --}}
    <section class="pb-20 lg:pb-28" aria-labelledby="request-title">
        <div class="container-site grid gap-10 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <p class="eyebrow">{{ __('site.nav.services') }}</p>
                <h2 id="request-title" class="mt-4 text-title font-semibold">{{ __('site.services.request_title') }}</h2>
                <p class="mt-5 text-graphite-600">{{ __('site.services.request_lead') }}</p>
            </div>
            <div class="rounded-[2rem] bg-white p-6 sm:p-10 lg:col-span-8">
                <x-inquiry-form type="service" :services="$services" />
            </div>
        </div>
    </section>

</x-layouts.app>
