<x-layouts.app :title="__('site.errors.404_title')">
    <section class="container-site grid min-h-[60vh] content-center gap-8 py-20 lg:grid-cols-12">
        <p class="font-mono text-[clamp(5rem,14vw,12rem)] leading-none font-medium text-graphite-500 lg:col-span-5" aria-hidden="true">404</p>
        <div class="lg:col-span-7">
            <h1 class="text-title font-semibold">{{ __('site.errors.404_title') }}</h1>
            <p class="mt-5 max-w-lg text-lead text-graphite-600">{{ __('site.errors.404_text') }}</p>
            <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                <a href="{{ localized_route('catalog.index') }}" class="btn btn-primary">{{ __('site.cta.catalog') }}</a>
                <a href="{{ localized_route('home') }}" class="btn btn-outline">{{ __('site.errors.home') }}</a>
            </div>
        </div>
    </section>
</x-layouts.app>
