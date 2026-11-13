<?php

namespace Tests\Feature\Admin;

use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function adminUrls(): array
    {
        return [['/admin'], ['/admin/products'], ['/admin/products/create'], ['/admin/categories'], ['/admin/services'], ['/admin/inquiries'], ['/admin/settings'], ['/admin/profile']];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_regular_user_cannot_enter_the_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    #[DataProvider('adminUrls')]
    public function test_admin_can_open_every_section(string $url): void
    {
        $this->seedCatalog();

        $this->actingAs($this->admin())->get($url)->assertOk();
    }

    public function test_admin_can_open_edit_and_view_pages(): void
    {
        $this->seedCatalog();
        $admin = $this->admin();
        $product = Product::where('slug', 'ccr2004-16g-2s-v3')->first();
        $inquiry = Inquiry::factory()->create();

        $this->actingAs($admin)->get('/admin/products/'.$product->getRouteKey().'/edit')->assertOk()->assertSee('CCR2004-16G-2S+ v.3');
        $this->actingAs($admin)->get('/admin/inquiries/'.$inquiry->getKey())->assertOk()->assertSee('Тест Клиент');
    }

    public function test_admin_can_log_in_with_correct_password_only(): void
    {
        $admin = $this->admin(['email' => 'owner@example.com', 'password' => 'correct-horse-42']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'owner@example.com', 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
        $this->assertGuest();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'owner@example.com', 'password' => 'correct-horse-42'])
            ->call('authenticate')
            ->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_panel_language_follows_the_user_setting(): void
    {
        $this->actingAs($this->admin(['locale' => 'en']))->get('/admin/products')->assertSee('Products');
        $this->actingAs($this->admin(['locale' => 'ru']))->get('/admin/products')->assertSee('Товары');
    }
}
