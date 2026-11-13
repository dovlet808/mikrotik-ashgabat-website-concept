@props(['image', 'product', 'sizes' => '100vw', 'eager' => false, 'width' => 960])
<img src="{{ $image->url($width) }}"
     srcset="{{ $image->srcset() }}"
     sizes="{{ $sizes }}"
     alt="{{ $image->altText($product) }}"
     @if ($image->width) width="{{ $image->width }}" height="{{ $image->height }}" @endif
     loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
     @if ($eager) fetchpriority="high" @endif
     {{ $attributes }}>
