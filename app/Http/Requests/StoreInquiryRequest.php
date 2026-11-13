<?php

namespace App\Http\Requests;

use App\Enums\InquiryType;
use App\Enums\PreferredContact;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class StoreInquiryRequest extends FormRequest
{
    public const HONEYPOT = 'website';

    public const STARTED = 'form_started';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim(strip_tags($this->name)) : $this->name,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
            'email' => is_string($this->email) && trim($this->email) !== '' ? trim($this->email) : null,
            'message' => is_string($this->message) ? trim(strip_tags($this->message)) : $this->message,
            'preferred_contact' => $this->preferred_contact ?: PreferredContact::WhatsApp->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(InquiryType::class)],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^\+?[\d\s()\-]{6,40}$/', function ($attribute, $value, $fail) {
                if (strlen(preg_replace('/\D+/', '', (string) $value)) < 6) {
                    $fail(__('site.form.errors.phone'));
                }
            }],
            'email' => ['nullable', 'email:rfc', 'max:160', Rule::requiredIf(fn () => $this->input('preferred_contact') === PreferredContact::Email->value)],
            'preferred_contact' => ['required', Rule::enum(PreferredContact::class)],
            'message' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => $this->input('type') === InquiryType::Contact->value)],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('is_published', true)->whereNull('deleted_at')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->where('is_published', true)],
            self::HONEYPOT => ['nullable', 'string', 'max:255'],
            self::STARTED => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('site.form.errors.name'),
            'name.min' => __('site.form.errors.name'),
            'name.max' => __('site.form.errors.too_long'),
            'phone.required' => __('site.form.errors.phone'),
            'phone.regex' => __('site.form.errors.phone'),
            'phone.max' => __('site.form.errors.phone'),
            'email.email' => __('site.form.errors.email'),
            'email.required' => __('site.form.errors.email_required'),
            'email.max' => __('site.form.errors.too_long'),
            'message.required' => __('site.form.errors.message'),
            'message.max' => __('site.form.errors.too_long'),
            'type.*' => __('site.form.errors.generic'),
            'preferred_contact.*' => __('site.form.errors.generic'),
            'product_id.*' => __('site.form.errors.product'),
            'service_id.*' => __('site.form.errors.generic'),
        ];
    }

    /**
     * Bots fill the hidden honeypot field or submit faster than a person can type.
     */
    public function isSpam(): bool
    {
        if (filled($this->input(self::HONEYPOT))) {
            return true;
        }

        try {
            $started = (int) Crypt::decryptString((string) $this->input(self::STARTED));
        } catch (DecryptException) {
            return true;
        }

        return (time() - $started) < (int) config('site.inquiries.min_seconds_to_submit', 3);
    }

    protected function getRedirectUrl()
    {
        return $this->safePreviousUrl().'#inquiry';
    }

    public function safePreviousUrl(): string
    {
        $previous = url()->previous();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $requestHost = $this->getHost();
        $previousHost = parse_url($previous, PHP_URL_HOST);

        if ($previousHost && ! in_array($previousHost, array_filter([$appHost, $requestHost]), true)) {
            return localized_route('home');
        }

        return strtok($previous, '#') ?: localized_route('home');
    }
}
