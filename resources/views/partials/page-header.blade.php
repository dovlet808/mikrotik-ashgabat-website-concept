<section class="border-b border-graphite-200">
    <div class="container-site pt-8 pb-12 lg:pt-12 lg:pb-16">
        @include('partials.breadcrumbs', ['items' => $breadcrumbs ?? []])
        <div class="mt-10 grid gap-6 lg:grid-cols-12 lg:items-end">
            <h1 class="text-display font-semibold text-balance lg:col-span-7">{{ $title }}</h1>
            @if (! empty($lead))
                <p class="max-w-xl text-lead text-pretty text-graphite-600 lg:col-span-5 lg:pb-3">{{ $lead }}</p>
            @endif
        </div>
    </div>
</section>
