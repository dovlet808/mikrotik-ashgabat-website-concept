@php $whatsapp = $settings->whatsappUrl(__('site.whatsapp.general')); @endphp
<footer class="bg-graphite-950 text-paper" aria-labelledby="footer-title">
    <div class="container-site pt-20 pb-10">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <p id="footer-title" class="flex items-center gap-3 text-xl font-semibold tracking-tight">
                    <span class="led bg-amber-500" aria-hidden="true"></span> MikroTik <span class="font-normal text-graphite-400">Ashgabat</span>
                </p>
                <p class="mt-5 max-w-sm text-graphite-300">{{ __('site.footer.about') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($whatsapp)
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-accent min-h-11 text-sm">
                            <x-heroicon-o-chat-bubble-left-right class="size-4" aria-hidden="true" /> WhatsApp
                        </a>
                    @endif
                    @if ($settings->get('instagram_url'))
                        <a href="{{ $settings->get('instagram_url') }}" target="_blank" rel="noopener" class="btn btn-outline-dark min-h-11 text-sm">
                            <x-heroicon-o-camera class="size-4" aria-hidden="true" /> <span>{{ '@'.$settings->get('instagram_handle') }}</span>
                        </a>
                    @endif
                </div>
            </div>

            <nav id="footer-nav" aria-label="{{ __('site.footer.navigation') }}" class="lg:col-span-2 lg:col-start-6">
                <p class="eyebrow-dark mb-5">{{ __('site.footer.navigation') }}</p>
                <ul class="space-y-3">
                    @foreach ($navItems as $item)
                        <li><a href="{{ localized_route($item['route']) }}" class="text-graphite-200 transition-colors hover:text-amber-400">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <nav aria-label="{{ __('site.footer.catalog') }}" class="lg:col-span-3">
                <p class="eyebrow-dark mb-5">{{ __('site.footer.catalog') }}</p>
                <ul class="space-y-3">
                    @foreach ($navCategories as $category)
                        <li><a href="{{ localized_route('catalog.category', $category->slug) }}" class="text-graphite-200 transition-colors hover:text-amber-400">{{ $category->translate('name') }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div class="lg:col-span-2">
                <p class="eyebrow-dark mb-5">{{ __('site.footer.contacts') }}</p>
                <ul class="space-y-3">
                    @if ($settings->phoneHref())
                        <li><a href="{{ $settings->phoneHref() }}" class="font-mono text-graphite-200 transition-colors hover:text-amber-400">{{ $settings->phoneDisplay() }}</a></li>
                    @endif
                    @if ($settings->get('email'))
                        <li><a href="mailto:{{ $settings->get('email') }}" class="text-graphite-200 transition-colors hover:text-amber-400">{{ $settings->get('email') }}</a></li>
                    @endif
                    <li class="text-graphite-300">{{ setting_text('address') ?? __('site.contact.city_value') }}</li>
                    @if (setting_text('working_hours'))
                        <li class="text-graphite-300">{{ setting_text('working_hours') }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-16 grid gap-6 border-t border-graphite-800 pt-8 lg:grid-cols-12">
            <div class="space-y-3 text-xs leading-relaxed text-graphite-400 lg:col-span-8">
                @if (config('site.concept_mode'))
                    <p class="flex items-start gap-2 text-graphite-300"><span class="led mt-1.5 bg-signal" aria-hidden="true"></span>{{ __('site.footer.concept') }}</p>
                @endif
                <p>{{ __('site.footer.trademarks') }}</p>
            </div>
            <div class="flex items-start justify-between gap-6 lg:col-span-4 lg:justify-end">
                @include('partials.lang-switcher', ['dark' => true])
                <p class="font-mono text-xs text-graphite-400">© {{ now()->year }} MikroTik Ashgabat</p>
            </div>
        </div>
    </div>
</footer>
