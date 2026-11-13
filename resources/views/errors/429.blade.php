<x-layouts.app :title="__('site.errors.429_title')">
    <section class="container-site grid min-h-[50vh] content-center gap-6 py-20">
        <p class="eyebrow">429</p>
        <h1 class="text-title font-semibold">{{ __('site.errors.429_title') }}</h1>
        <p class="max-w-lg text-lead text-graphite-600">{{ __('site.errors.429_text') }}</p>
        <div><a href="{{ localized_route('home') }}" class="btn btn-primary">{{ __('site.errors.home') }}</a></div>
    </section>
</x-layouts.app>
