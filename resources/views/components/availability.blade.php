@props(['status', 'dark' => false])
@php
    $led = match ($status) {
        \App\Enums\Availability::InStock => 'bg-ok',
        \App\Enums\Availability::OnOrder => 'bg-signal',
        \App\Enums\Availability::OutOfStock => 'bg-danger',
        default => 'bg-graphite-400',
    };
@endphp
<p {{ $attributes->class(['flex items-center gap-2 text-sm', $dark ? 'text-graphite-300' : 'text-graphite-600']) }}>
    <span class="led {{ $led }}" aria-hidden="true"></span>
    <span>{{ __('site.availability.'.$status->value) }}</span>
</p>
