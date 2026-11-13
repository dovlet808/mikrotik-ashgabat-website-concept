@props(['index' => null, 'label', 'title', 'lead' => null, 'dark' => false, 'action' => null, 'actionLabel' => null, 'id' => null, 'level' => 'h2'])
<div {{ $attributes->class(['grid gap-5 border-t pt-6 lg:grid-cols-12 lg:gap-8', $dark ? 'border-graphite-700' : 'border-graphite-200']) }}>
    <p class="{{ $dark ? 'eyebrow-dark' : 'eyebrow' }} lg:col-span-3 lg:pt-2">
        @if ($index)<span class="{{ $dark ? 'text-amber-500' : 'text-amber-700' }}">{{ $index }}</span> <span aria-hidden="true">/</span> @endif{{ $label }}
    </p>
    <div class="flex flex-col gap-6 lg:col-span-9 md:flex-row md:items-end md:justify-between">
        <div class="max-w-3xl">
            <{{ $level }} @if ($id) id="{{ $id }}" @endif class="text-title font-semibold text-balance">{{ $title }}</{{ $level }}>
            @if ($lead)
                <p class="mt-5 max-w-2xl text-lead text-pretty {{ $dark ? 'text-graphite-300' : 'text-graphite-500' }}">{{ $lead }}</p>
            @endif
        </div>
        @if ($action)
            <a href="{{ $action }}" class="link-arrow shrink-0 {{ $dark ? 'decoration-graphite-600 hover:decoration-paper' : '' }}">
                {{ $actionLabel }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
            </a>
        @endif
    </div>
</div>
