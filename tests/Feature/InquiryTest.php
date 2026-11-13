<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Mail\NewInquiryMail;
use App\Models\Inquiry;
use App\Models\Product;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'consultation',
            'name' => 'Тестовый клиент',
            'phone' => '+993 61 23 45 67',
            'preferred_contact' => 'whatsapp',
            'message' => 'Нужен Wi-Fi для офиса',
            'form_started' => Crypt::encryptString((string) (time() - 30)),
            'website' => '',
        ], $overrides);
    }

    public function test_valid_inquiry_is_stored_and_user_sees_confirmation(): void
    {
        $this->from('/contact')
            ->post('/inquiries', $this->payload())
            ->assertRedirect(url('/contact').'#inquiry')
            ->assertSessionHas('inquiry_sent', true);

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Тестовый клиент',
            'phone' => '+993 61 23 45 67',
            'status' => InquiryStatus::New->value,
            'type' => 'consultation',
            'locale' => 'ru',
        ]);

        $this->get('/contact')->assertSee('Заявка отправлена');
    }

    public function test_product_inquiry_keeps_a_snapshot_of_model_and_price(): void
    {
        $product = Product::where('slug', 'hap-ax2')->first();

        $this->from('/en/product/hap-ax2')->post('/en/inquiries', $this->payload(['type' => 'product', 'product_id' => $product->id]))
            ->assertRedirect(url('/en/product/hap-ax2').'#inquiry');

        $inquiry = Inquiry::sole();
        $this->assertSame($product->id, $inquiry->product_id);
        $this->assertStringContainsString('hAP ax²', $inquiry->product_snapshot);
        $this->assertStringContainsString('C52iG-5HaxD2HaxD-TC', $inquiry->product_snapshot);
        $this->assertStringContainsString("2\u{202F}850", $inquiry->product_snapshot);
        $this->assertSame('en', $inquiry->locale);
    }

    public function test_required_fields_and_formats_are_validated(): void
    {
        $this->from('/contact')->post('/inquiries', $this->payload(['name' => '', 'phone' => 'abc']))
            ->assertRedirect(url('/contact').'#inquiry')
            ->assertSessionHasErrors(['name', 'phone']);

        $this->from('/contact')->post('/inquiries', $this->payload(['preferred_contact' => 'email', 'email' => '']))
            ->assertSessionHasErrors(['email']);

        $this->from('/contact')->post('/inquiries', $this->payload(['type' => 'contact', 'message' => '']))
            ->assertSessionHasErrors(['message']);

        $this->from('/contact')->post('/inquiries', $this->payload(['type' => 'free-iphone']))
            ->assertSessionHasErrors(['type']);

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_validation_messages_are_localised(): void
    {
        $this->from('/tk/contact')->post('/tk/inquiries', $this->payload(['phone' => 'x']))
            ->assertSessionHasErrors(['phone' => 'Dogry telefon belgisini görkeziň.']);
    }

    public function test_unpublished_product_cannot_be_referenced(): void
    {
        $draft = Product::where('slug', 'hap-be-lite')->first();

        $this->from('/contact')->post('/inquiries', $this->payload(['type' => 'product', 'product_id' => $draft->id]))
            ->assertSessionHasErrors(['product_id']);
    }

    public function test_honeypot_submissions_are_silently_discarded(): void
    {
        $this->from('/contact')->post('/inquiries', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHas('inquiry_sent', true);

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_instant_or_tampered_submissions_are_discarded(): void
    {
        $this->from('/contact')->post('/inquiries', $this->payload(['form_started' => Crypt::encryptString((string) time())]));
        $this->from('/contact')->post('/inquiries', $this->payload(['form_started' => 'forged']));
        $this->from('/contact')->post('/inquiries', $this->payload(['form_started' => null]));

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_successful_submissions_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/contact')->post('/inquiries', $this->payload(['name' => 'Клиент '.$i]))->assertSessionHasNoErrors();
        }

        $this->from('/contact')->post('/inquiries', $this->payload(['name' => 'Шестой']))->assertSessionHasErrors(['form']);

        $this->assertDatabaseCount('inquiries', 5);
    }

    public function test_markup_is_stripped_from_user_input(): void
    {
        $this->from('/contact')->post('/inquiries', $this->payload([
            'name' => '<b>Иван</b><script>alert(1)</script>',
            'message' => '<img src=x onerror=alert(1)>Привет',
        ]));

        $inquiry = Inquiry::sole();
        $this->assertSame('Иванalert(1)', $inquiry->name);
        $this->assertSame('Привет', $inquiry->message);
    }

    public function test_notification_email_is_sent_when_configured(): void
    {
        Mail::fake();
        app(Settings::class)->save(['notification_email' => 'owner@example.com']);

        $this->from('/contact')->post('/inquiries', $this->payload());

        Mail::assertSent(NewInquiryMail::class, fn (NewInquiryMail $mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_no_email_is_sent_without_a_recipient(): void
    {
        Mail::fake();

        $this->from('/contact')->post('/inquiries', $this->payload());

        Mail::assertNothingSent();
        $this->assertDatabaseCount('inquiries', 1);
    }

    public function test_forms_carry_csrf_token_and_spam_traps(): void
    {
        $this->get('/')
            ->assertSee('name="_token"', false)
            ->assertSee('name="website"', false)
            ->assertSee('name="form_started"', false);
    }
}
