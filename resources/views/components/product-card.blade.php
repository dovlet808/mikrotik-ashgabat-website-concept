@props(['product', 'sizes' => '(min-width: 1280px) 22vw, (min-width: 1024px) 30vw, (min-width: 640px) 46vw, 92vw', 'eager' => false])
@php
    $image = $product->primaryImage;
    $url = localized_route('products.show', $product->slug);
    $ratio = $image && $image->width && $image->height ? $image->width / $image->height : null;
    $cover = $ratio !== null && $ratio >= 0.7 && $ratio <= 1.2;
@endphp
<article {{ $attributes->class(['group relative flex h-full flex-col bg-paper transition-colors duration-300 hover:bg-white']) }}>
    <div class="relative aspect-[4/5] overflow-hidden bg-paper-2">
        @if ($image)
            <img src="{{ $image->url(960) }}"
                 srcset="{{ $image->srcset() }}"
                 sizes="{{ $sizes }}"
                 alt="{{ $image->altText($product) }}"
                 width="{{ $image->width }}" height="{{ $image->height }}"
                 loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
                 class="size-full transition-transform duration-700 ease-(--ease-out-quart) group-hover:scale-[1.03] {{ $cover ? 'object-cover' : 'object-contain p-5 sm:p-7' }}">
        @else
            <div class="flex size-full items-center justify-center">
                <x-heroicon-o-cube class="size-10 text-graphite-300" aria-hidden="true" />
            </div>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5 sm:p-6">
        <p class="eyebrow truncate">{{ $product->brand }}@if ($product->product_code) <span aria-hidden="true">·</span> <span class="normal-case tracking-[0.06em]">{{ $product->product_code }}</span>@endif</p>
        <h3 class="mt-3 text-heading font-semibold">
            <a href="{{ $url }}" class="after:absolute after:inset-0 focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-signal-700">{{ $product->name }}</a>
        </h3>
        @if ($summary = $product->translate('summary'))
            <p class="mt-3 line-clamp-2 text-sm text-graphite-500">{{ $summary }}</p>
        @endif
        <div class="mt-auto pt-5">
            <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-2 border-t border-graphite-200 pt-4">
                <x-price :product="$product" size="sm" />
                <x-availability :status="$product->availability" class="text-xs" />
            </div>
        </div>
    </div>
</article>
