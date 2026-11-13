@props(['name', 'sizes' => '100vw', 'eager' => false, 'ratio' => '3:2'])
@php
    [$w, $h] = $ratio === '16:9' ? [1920, 1080] : [1920, 1280];
    $base = 'images/editorial/'.$name;
@endphp
<img src="{{ asset($base.'-1280.webp') }}"
     srcset="{{ asset($base.'-640.webp') }} 640w, {{ asset($base.'-1280.webp') }} 1280w, {{ asset($base.'-1920.webp') }} 1920w"
     sizes="{{ $sizes }}"
     width="{{ $w }}" height="{{ $h }}"
     alt="{{ __('site.images.'.$name) }}"
     loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
     {{ $attributes->class(['block']) }}>
