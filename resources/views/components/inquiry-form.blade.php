@props([
    'type' => 'consultation',
    'product' => null,
    'service' => null,
    'services' => null,
    'showType' => false,
    'submitLabel' => null,
    'messagePlaceholder' => null,
])
@php
    $settings = app(\App\Support\Settings::class);
    $sent = session('inquiry_sent');
    $uid = 'f'.substr(md5($type.($product?->id).($service?->id)), 0, 6);
    $preferred = old('preferred_contact', \App\Enums\PreferredContact::WhatsApp->value);
    $currentType = old('type', $type);
    $currentService = (int) old('service_id', $service?->id);
    $fieldError = fn (string $field) => $errors->first($field);
    $whatsapp = $settings->whatsappUrl($product
        ? __('site.product.whatsapp_message', ['name' => $product->displayName(), 'url' => localized_route('products.show', $product->slug)])
        : __('site.whatsapp.general'));
@endphp
<div id="inquiry" {{ $attributes->class(['scroll-mt-28']) }}>
    @if ($sent)
        <div role="status" class="mb-6 rounded-2xl border border-ok/25 bg-white p-6">
            <p class="flex items-center gap-3 text-lg font-semibold">
                <span class="inline-flex size-8 items-center justify-center rounded-full bg-ok text-white"><x-heroicon-m-check class="size-5" aria-hidden="true" /></span>
                {{ __('site.form.success_title') }}
            </p>
            <p class="mt-3 text-graphite-600">{{ __('site.form.success_text') }}</p>
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="link-arrow mt-4">{{ __('site.cta.whatsapp') }} <x-heroicon-m-arrow-up-right class="size-4" aria-hidden="true" /></a>
            @endif
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-2xl border border-danger/30 bg-white p-5 text-sm">
            <p class="font-semibold text-danger">{{ $errors->first('form') ?: __('site.form.error_title') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ localized_route('inquiries.store') }}" data-inquiry-form class="grid gap-5 sm:grid-cols-2">
        @csrf
        <input type="hidden" name="{{ \App\Http\Requests\StoreInquiryRequest::STARTED }}" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
        <div class="absolute -left-[10000px] h-px w-px overflow-hidden" aria-hidden="true">
            <label for="{{ $uid }}-website">{{ __('site.form.honeypot') }}</label>
            <input type="text" id="{{ $uid }}-website" name="{{ \App\Http\Requests\StoreInquiryRequest::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
        </div>

        @if ($product)
            <input type="hidden" name="product_id" value="{{ $product->id }}">
        @endif

        @if ($showType)
            <div class="sm:col-span-2">
                <label for="{{ $uid }}-type" class="field-label">{{ __('site.form.type') }}</label>
                <select id="{{ $uid }}-type" name="type" class="field">
                    @foreach (\App\Enums\InquiryType::cases() as $case)
                        <option value="{{ $case->value }}" @selected($currentType === $case->value)>{{ __('site.form.types.'.$case->value) }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="type" value="{{ $type }}">
        @endif

        @if ($services && $services->isNotEmpty())
            <div class="sm:col-span-2">
                <label for="{{ $uid }}-service" class="field-label">{{ __('site.form.service') }} <span class="font-normal text-graphite-500">({{ __('site.form.optional') }})</span></label>
                <select id="{{ $uid }}-service" name="service_id" class="field" @if ($fieldError('service_id')) aria-invalid="true" @endif>
                    <option value="">{{ __('site.form.choose_service') }}</option>
                    @foreach ($services as $option)
                        <option value="{{ $option->id }}" @selected($currentService === $option->id)>{{ $option->translate('title') }}</option>
                    @endforeach
                </select>
            </div>
        @elseif ($service)
            <input type="hidden" name="service_id" value="{{ $service->id }}">
        @endif

        <div>
            <label for="{{ $uid }}-name" class="field-label">{{ __('site.form.name') }}</label>
            <input id="{{ $uid }}-name" name="name" type="text" autocomplete="name" required maxlength="120" value="{{ old('name') }}" class="field"
                   @if ($fieldError('name')) aria-invalid="true" aria-describedby="{{ $uid }}-name-error" @endif>
            @if ($fieldError('name'))<p id="{{ $uid }}-name-error" class="field-error">{{ $fieldError('name') }}</p>@endif
        </div>

        <div>
            <label for="{{ $uid }}-phone" class="field-label">{{ __('site.form.phone') }}</label>
            <input id="{{ $uid }}-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" required maxlength="40" value="{{ old('phone') }}" class="field font-mono"
                   placeholder="+993 6_ __ __ __"
                   aria-describedby="{{ $uid }}-phone-hint{{ $fieldError('phone') ? ' '.$uid.'-phone-error' : '' }}"
                   @if ($fieldError('phone')) aria-invalid="true" @endif>
            <p id="{{ $uid }}-phone-hint" class="mt-2 text-xs text-graphite-500">{{ __('site.form.phone_hint') }}</p>
            @if ($fieldError('phone'))<p id="{{ $uid }}-phone-error" class="field-error">{{ $fieldError('phone') }}</p>@endif
        </div>

        <fieldset class="sm:col-span-2" data-contact-choice="{{ $uid }}-email">
            <legend class="field-label">{{ __('site.form.preferred') }}</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Enums\PreferredContact::cases() as $case)
                    {{-- "Reply by e-mail" is offered only when the business has an e-mail address in Website settings. --}}
                    @continue($case === \App\Enums\PreferredContact::Email && blank($settings->get('email')) && $preferred !== $case->value)
                    <label class="cursor-pointer">
                        <input type="radio" name="preferred_contact" value="{{ $case->value }}" class="peer sr-only" @checked($preferred === $case->value)>
                        <span class="inline-flex min-h-11 items-center gap-2 rounded-full border border-graphite-300 bg-white px-4 text-sm transition-colors peer-checked:border-ink peer-checked:bg-ink peer-checked:text-paper peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-signal-700">
                            {{ __('site.form.contact_via.'.$case->value) }}
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="sm:col-span-2">
            <label for="{{ $uid }}-email" class="field-label">{{ __('site.form.email') }} <span class="font-normal text-graphite-500">({{ __('site.form.optional') }})</span></label>
            <input id="{{ $uid }}-email" name="email" type="email" autocomplete="email" maxlength="160" value="{{ old('email') }}" class="field"
                   @if ($fieldError('email')) aria-invalid="true" aria-describedby="{{ $uid }}-email-error" @endif>
            @if ($fieldError('email'))<p id="{{ $uid }}-email-error" class="field-error">{{ $fieldError('email') }}</p>@endif
        </div>

        <div class="sm:col-span-2">
            <label for="{{ $uid }}-message" class="field-label">{{ __('site.form.message') }}@unless ($type === 'contact') <span class="font-normal text-graphite-500">({{ __('site.form.optional') }})</span>@endunless</label>
            <textarea id="{{ $uid }}-message" name="message" rows="4" maxlength="2000" class="field resize-y"
                      placeholder="{{ $messagePlaceholder ?? __('site.form.message_placeholder') }}"
                      @if ($type === 'contact' && ! $showType) required @endif
                      @if ($fieldError('message')) aria-invalid="true" aria-describedby="{{ $uid }}-message-error" @endif>{{ old('message') }}</textarea>
            @if ($fieldError('message'))<p id="{{ $uid }}-message-error" class="field-error">{{ $fieldError('message') }}</p>@endif
        </div>

        <div class="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-sm text-xs text-graphite-500">{{ __('site.form.consent') }}</p>
            <button type="submit" class="btn btn-primary w-full disabled:cursor-wait disabled:opacity-70 sm:w-auto">
                {{ $submitLabel ?? __('site.form.submit') }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
            </button>
        </div>
    </form>
</div>
