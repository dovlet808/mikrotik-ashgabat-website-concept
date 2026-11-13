@php $whatsapp = $settings->whatsappUrl(__('site.whatsapp.general')); @endphp
<aside aria-label="{{ __('site.cta.contact') }}" class="fixed inset-x-0 bottom-0 z-30 border-t border-graphite-200 bg-paper/95 px-4 py-3 md:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
    <div class="grid grid-cols-2 gap-3">
        @if ($settings->phoneHref())
            <a href="{{ $settings->phoneHref() }}" class="btn btn-outline min-h-11 bg-white text-sm">
                <x-heroicon-o-phone class="size-4" aria-hidden="true" /> {{ __('site.cta.call') }}
            </a>
        @endif
        @if ($whatsapp)
            <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-accent min-h-11 text-sm">
                <x-heroicon-o-chat-bubble-left-right class="size-4" aria-hidden="true" /> WhatsApp
            </a>
        @endif
    </div>
</aside>
