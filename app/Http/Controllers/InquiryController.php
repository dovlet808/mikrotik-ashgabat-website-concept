<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Http\Requests\StoreInquiryRequest;
use App\Mail\NewInquiryMail;
use App\Models\Inquiry;
use App\Models\Product;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request, Settings $settings): RedirectResponse
    {
        $redirect = redirect()->to($request->safePreviousUrl().'#inquiry');

        if ($request->isSpam()) {
            return $redirect->with('inquiry_sent', true);
        }

        $key = 'inquiry-submit:'.hash('sha256', (string) $request->ip());
        $maxAttempts = (int) config('site.inquiries.max_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return $redirect->withInput()->withErrors(['form' => __('site.form.too_many', ['minutes' => max(1, $minutes)])]);
        }

        $data = $request->validated();
        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;

        $inquiry = Inquiry::create([
            'type' => $data['type'],
            'status' => InquiryStatus::New,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'preferred_contact' => $data['preferred_contact'],
            'message' => $data['message'] ?? null,
            'product_id' => $product?->getKey(),
            'service_id' => $data['service_id'] ?? null,
            'product_snapshot' => $product ? Str::limit(trim($product->displayName().($product->product_code ? ' ('.$product->product_code.')' : '').($product->hasPrice() ? ' — '.$product->formattedPrice('ru') : '')), 250) : null,
            'locale' => app()->getLocale(),
            'page_url' => Str::limit($request->safePreviousUrl(), 490, ''),
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        RateLimiter::hit($key, (int) config('site.inquiries.decay_minutes', 10) * 60);

        $this->notify($inquiry, $settings);

        return $redirect->with('inquiry_sent', true);
    }

    protected function notify(Inquiry $inquiry, Settings $settings): void
    {
        $recipient = $settings->get('notification_email') ?: config('mail.inquiry_recipient');

        if (blank($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new NewInquiryMail($inquiry));
        } catch (Throwable $e) {
            Log::warning('Inquiry notification email failed', ['inquiry' => $inquiry->getKey(), 'error' => $e->getMessage()]);
        }
    }
}
