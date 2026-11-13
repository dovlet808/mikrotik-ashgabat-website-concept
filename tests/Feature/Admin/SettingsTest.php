<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\ManageSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        $this->actingAs($this->admin());
    }

    public function test_contact_settings_update_the_public_site(): void
    {
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'phone' => '+99365000001',
                'whatsapp' => '99365000001',
                'email' => 'shop@example.com',
                'address' => ['ru' => 'ул. Примерная, 1'],
                'delivery_text' => ['ru' => 'Доставка по Ашхабаду и велаятам'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')
            ->assertSee('https://wa.me/99365000001', false)
            ->assertSee('tel:+99365000001', false)
            ->assertSee('shop@example.com')
            ->assertSee('ул. Примерная, 1')
            ->assertSee('Доставка по Ашхабаду и велаятам')
            ->assertDontSee('wa.me/99360000000', false);
    }

    public function test_invalid_settings_are_rejected(): void
    {
        Livewire::test(ManageSettings::class)
            ->fillForm(['whatsapp' => 'call me', 'email' => 'not-an-email'])
            ->call('save')
            ->assertHasFormErrors(['whatsapp', 'email']);
    }

    public function test_prices_can_be_hidden_site_wide(): void
    {
        Livewire::test(ManageSettings::class)->fillForm(['show_prices' => false])->call('save')->assertHasNoFormErrors();

        $this->get('/product/hap-ax2')->assertSee('Цена по запросу')->assertDontSee("2\u{202F}850", false);
    }
}
