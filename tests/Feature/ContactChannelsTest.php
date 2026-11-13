<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The site must not promise e-mail replies until the business has its own address in Website settings.
 */
class ContactChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_without_a_business_email_only_phone_and_whatsapp_are_promised(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee(__('site.contact.form_lead_no_email', [], 'ru'))
            ->assertDontSee(__('site.contact.form_lead', [], 'ru'))
            ->assertSee('name="preferred_contact" value="whatsapp"', false)
            ->assertSee('name="preferred_contact" value="phone"', false)
            ->assertDontSee('name="preferred_contact" value="email"', false);

        foreach (['tk', 'en'] as $locale) {
            $this->get("/{$locale}/contact")->assertOk()->assertSee(__('site.contact.form_lead_no_email', [], $locale));
        }

        $this->get('/product/hap-ax2')->assertDontSee('name="preferred_contact" value="email"', false);
    }

    public function test_email_replies_are_offered_once_a_business_email_is_set(): void
    {
        app(Settings::class)->save(['email' => 'shop@example.com']);

        $this->get('/contact')
            ->assertOk()
            ->assertSee(__('site.contact.form_lead', [], 'ru'))
            ->assertSee('name="preferred_contact" value="email"', false);
    }

    public function test_instagram_is_shown_only_when_an_account_is_configured(): void
    {
        $this->get('/contact')->assertOk()->assertDontSee('instagram.com', false);

        config(['site.contact.instagram_handle' => 'example.shop']);
        app(Settings::class)->flush();

        $this->get('/contact')
            ->assertOk()
            ->assertSee('https://www.instagram.com/example.shop/', false)
            ->assertSee('@example.shop');
    }

    public function test_an_email_preference_still_requires_an_email_address(): void
    {
        $this->from('/contact')->post('/inquiries', [
            'type' => 'contact', 'name' => 'Тест', 'phone' => '+993 60 000000',
            'preferred_contact' => 'email', 'email' => '', 'message' => 'Проверка',
        ])->assertSessionHasErrors('email');
    }
}
