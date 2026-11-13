<x-layouts.app :title="__('site.contact.title')" :description="__('site.meta.contact_description')">

    @include('partials.page-header', [
        'title' => __('site.contact.title'),
        'lead' => __('site.contact.lead'),
        'breadcrumbs' => [['label' => __('site.nav.contact')]],
    ])

    <section class="py-16 lg:py-24" aria-labelledby="form-title">
        <div class="container-site grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <h2 class="eyebrow">{{ __('site.home.contact.label') }}</h2>
                <ul class="mt-6 divide-y divide-graphite-200 border-y border-graphite-200">
                    @if ($settings->phoneHref())
                        <li class="flex items-center justify-between gap-4 py-5">
                            <span class="text-sm text-graphite-500">{{ __('site.contact.phone') }}</span>
                            <a href="{{ $settings->phoneHref() }}" class="font-mono text-lg font-medium hover:underline hover:underline-offset-4">{{ $settings->phoneDisplay() }}</a>
                        </li>
                    @endif
                    @if ($wa = $settings->whatsappUrl(__('site.whatsapp.general')))
                        <li class="flex items-center justify-between gap-4 py-5">
                            <span class="text-sm text-graphite-500">{{ __('site.contact.whatsapp') }}</span>
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 font-medium hover:underline hover:underline-offset-4">{{ __('site.cta.whatsapp') }} <x-heroicon-m-arrow-up-right class="size-4" aria-hidden="true" /></a>
                        </li>
                    @endif
                    @if ($settings->get('instagram_url'))
                        <li class="flex items-center justify-between gap-4 py-5">
                            <span class="text-sm text-graphite-500">{{ __('site.contact.instagram') }}</span>
                            <a href="{{ $settings->get('instagram_url') }}" target="_blank" rel="noopener" class="text-right">
                                <span class="block font-mono font-medium hover:underline hover:underline-offset-4">{{ '@'.$settings->get('instagram_handle') }}</span>
                                <span class="block text-xs text-graphite-500">{{ __('site.contact.instagram_note') }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($settings->get('email'))
                        <li class="flex items-center justify-between gap-4 py-5">
                            <span class="text-sm text-graphite-500">{{ __('site.contact.email') }}</span>
                            <a href="mailto:{{ $settings->get('email') }}" class="font-medium hover:underline hover:underline-offset-4">{{ $settings->get('email') }}</a>
                        </li>
                    @endif
                    <li class="flex items-center justify-between gap-4 py-5">
                        <span class="text-sm text-graphite-500">{{ setting_text('address') ? __('site.contact.address') : __('site.contact.city') }}</span>
                        <span class="text-right font-medium">{{ setting_text('address') ?? __('site.contact.city_value') }}</span>
                    </li>
                    @if (setting_text('working_hours'))
                        <li class="flex items-center justify-between gap-4 py-5">
                            <span class="text-sm text-graphite-500">{{ __('site.contact.hours') }}</span>
                            <span class="text-right font-medium">{{ setting_text('working_hours') }}</span>
                        </li>
                    @endif
                </ul>

                <div class="mt-10 flex gap-4 rounded-2xl bg-white p-6">
                    <x-heroicon-o-truck class="size-6 shrink-0 text-amber-700" aria-hidden="true" />
                    <div>
                        <p class="font-semibold">{{ setting_text('delivery_text') ?? __('site.home.delivery.title') }}</p>
                        <p class="mt-1 text-sm text-graphite-600">{{ setting_text('delivery_note') ?? __('site.home.delivery.text') }}</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7">
                <div class="rounded-[2rem] bg-white p-6 sm:p-10">
                    <h2 id="form-title" class="text-heading font-semibold sm:text-[2rem]">{{ __('site.contact.form_title') }}</h2>
                    {{-- Promise e-mail replies only once the business has entered its own address in Website settings. --}}
                    <p class="mt-3 text-graphite-600">{{ __(filled($settings->get('email')) ? 'site.contact.form_lead' : 'site.contact.form_lead_no_email') }}</p>
                    <div class="mt-8">
                        <x-inquiry-form
                            :type="$selectedProduct ? 'product' : ($selectedService ? 'service' : 'consultation')"
                            :product="$selectedProduct"
                            :service="$selectedService"
                            :services="$selectedProduct ? null : $services"
                            show-type />
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>
