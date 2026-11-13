@php
    $whatsapp = $settings->whatsappUrl(__('site.whatsapp.general'));
    $tiles = array_values(array_filter([
        $settings->phoneHref() ? ['label' => __('site.contact.phone'), 'value' => $settings->phoneDisplay(), 'href' => $settings->phoneHref(), 'icon' => 'phone', 'mono' => true, 'external' => false] : null,
        $whatsapp ? ['label' => __('site.contact.whatsapp'), 'value' => __('site.cta.whatsapp'), 'href' => $whatsapp, 'icon' => 'chat-bubble-left-right', 'mono' => false, 'external' => true] : null,
        $settings->get('instagram_url') ? ['label' => __('site.contact.instagram'), 'value' => '@'.$settings->get('instagram_handle'), 'note' => __('site.contact.instagram_note'), 'href' => $settings->get('instagram_url'), 'icon' => 'camera', 'mono' => true, 'external' => true] : null,
        $settings->get('email') ? ['label' => __('site.contact.email'), 'value' => $settings->get('email'), 'href' => 'mailto:'.$settings->get('email'), 'icon' => 'envelope', 'mono' => false, 'external' => false] : null,
        ['label' => setting_text('address') ? __('site.contact.address') : __('site.contact.city'), 'value' => setting_text('address') ?? __('site.contact.city_value'), 'note' => setting_text('working_hours'), 'href' => null, 'icon' => 'map-pin', 'mono' => false, 'external' => false],
    ]));
@endphp
<ul class="mt-14 grid gap-px overflow-hidden border border-graphite-200 bg-graphite-200 sm:grid-cols-2 {{ count($tiles) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
    @foreach ($tiles as $tile)
        <li class="bg-white">
            @if ($tile['href'])
                <a href="{{ $tile['href'] }}" @if ($tile['external']) target="_blank" rel="noopener" @endif class="group flex h-full flex-col gap-10 p-6 transition-colors duration-300 hover:bg-paper sm:p-8">
            @else
                <div class="flex h-full flex-col gap-10 p-6 sm:p-8">
            @endif
                <span class="flex items-center justify-between">
                    <span class="eyebrow">{{ $tile['label'] }}</span>
                    <x-dynamic-component :component="'heroicon-o-'.$tile['icon']" class="size-5 text-graphite-500 transition-colors group-hover:text-ink" aria-hidden="true" />
                </span>
                <span>
                    <span class="block text-xl font-semibold {{ $tile['mono'] ? 'font-mono font-medium tracking-tight' : '' }}">{{ $tile['value'] }}</span>
                    @if (! empty($tile['note']))
                        <span class="mt-2 block text-sm text-graphite-500">{{ $tile['note'] }}</span>
                    @endif
                </span>
            @if ($tile['href'])
                </a>
            @else
                </div>
            @endif
        </li>
    @endforeach
</ul>
