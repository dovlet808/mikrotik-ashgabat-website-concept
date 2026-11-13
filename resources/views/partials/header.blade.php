@php $whatsapp = $settings->whatsappUrl(__('site.whatsapp.general')); @endphp
<header data-site-header class="sticky top-0 z-40 border-b border-transparent bg-paper transition-[border-color,box-shadow] duration-300 [&.is-scrolled]:border-graphite-200">
    <div class="container-site flex h-16 items-center justify-between gap-4 lg:h-20">
        <a href="{{ localized_route('home') }}" class="flex shrink-0 items-center gap-3 rounded-sm" aria-label="MikroTik Ashgabat — {{ __('site.nav.home') }}">
            <span class="led led-live bg-amber-500" aria-hidden="true"></span>
            <span class="text-[1.05rem] font-semibold tracking-tight sm:text-lg">MikroTik <span class="font-normal text-graphite-500">Ashgabat</span></span>
        </a>

        <nav aria-label="{{ __('site.nav.primary') }}" class="hidden lg:block">
            <ul class="flex items-center gap-1">
                @foreach (array_slice($navItems, 1) as $item)
                    <li>
                        <a href="{{ localized_route($item['route']) }}"
                           @if ($item['active']) aria-current="page" @endif
                           class="inline-flex min-h-10 items-center rounded-full px-4 text-[0.95rem] text-graphite-600 transition-colors duration-200 hover:text-ink aria-[current=page]:bg-paper-2 aria-[current=page]:text-ink">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
            <div class="hidden md:block">@include('partials.lang-switcher', ['landmark' => true])</div>
            @if ($settings->phoneHref())
                <a href="{{ $settings->phoneHref() }}" class="hidden font-mono text-sm text-graphite-600 transition-colors hover:text-ink xl:inline">{{ $settings->phoneDisplay() }}</a>
            @endif
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-accent hidden min-h-10 px-5 text-sm sm:inline-flex">
                    <x-heroicon-o-chat-bubble-left-right class="size-4" aria-hidden="true" />
                    WhatsApp
                </a>
            @endif
            <a href="#footer-nav" role="button" data-menu-toggle aria-controls="mobile-menu" aria-expanded="false"
               class="inline-flex size-11 items-center justify-center rounded-full border border-graphite-300 text-ink transition-colors hover:border-ink lg:hidden">
                <span class="sr-only">{{ __('site.nav.menu') }}</span>
                <x-heroicon-o-bars-2 class="size-5" aria-hidden="true" />
            </a>
        </div>
    </div>

    <div id="mobile-menu" hidden class="fixed inset-0 z-50 overflow-y-auto bg-graphite-900 text-paper lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('site.nav.menu') }}">
        <div class="container-site flex h-16 items-center justify-between">
            <span class="flex items-center gap-3 text-[1.05rem] font-semibold tracking-tight">
                <span class="led bg-amber-500" aria-hidden="true"></span> MikroTik <span class="font-normal text-graphite-400">Ashgabat</span>
            </span>
            <button type="button" data-menu-close class="inline-flex size-11 items-center justify-center rounded-full border border-graphite-600 hover:border-paper">
                <span class="sr-only">{{ __('site.nav.close') }}</span>
                <x-heroicon-o-x-mark class="size-5" aria-hidden="true" />
            </button>
        </div>
        <nav aria-label="{{ __('site.nav.primary') }}" class="container-site pt-8">
            <ul class="border-t border-graphite-700">
                @foreach ($navItems as $i => $item)
                    <li class="border-b border-graphite-700">
                        <a href="{{ localized_route($item['route']) }}" @if ($item['active']) aria-current="page" @endif
                           class="flex items-baseline gap-5 py-5 text-3xl font-semibold tracking-tight aria-[current=page]:text-amber-500">
                            <span class="font-mono text-xs font-normal text-graphite-400">0{{ $i + 1 }}</span>{{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-10 flex flex-col gap-3">
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-accent w-full">
                        <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" /> {{ __('site.cta.whatsapp') }}
                    </a>
                @endif
                @if ($settings->phoneHref())
                    <a href="{{ $settings->phoneHref() }}" class="btn btn-outline-dark w-full font-mono">{{ $settings->phoneDisplay() }}</a>
                @endif
            </div>
            <div class="mt-10 pb-12">
                <p class="eyebrow-dark mb-3">{{ __('site.nav.language') }}</p>
                @include('partials.lang-switcher', ['dark' => true])
            </div>
        </nav>
    </div>
</header>
