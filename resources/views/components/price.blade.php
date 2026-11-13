@props(['product', 'size' => 'md'])
@php
    $show = (bool) app(\App\Support\Settings::class)->get('show_prices', true);
    $hasPrice = $show && $product->hasPrice();
    $numberClass = match ($size) {
        'lg' => 'text-4xl sm:text-5xl',
        'sm' => 'text-xl',
        default => 'text-2xl',
    };
@endphp
<p {{ $attributes->class(['flex items-baseline gap-2']) }}>
    @if ($hasPrice)
        <span class="sr-only">{{ __('site.price.label') }}:</span>
        <span class="font-mono font-medium tracking-tight {{ $numberClass }}">{{ \App\Support\Money::number((float) $product->price) }}</span>
        <span class="text-sm text-graphite-500">{{ \App\Support\Money::label($product->currency) }}</span>
    @else
        <span class="font-medium {{ $size === 'lg' ? 'text-2xl' : 'text-base' }}">{{ __('site.price.on_request') }}</span>
    @endif
</p>
