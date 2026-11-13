<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public static function pages(): array
    {
        return [
            'home ru' => ['/', 'ru'],
            'catalog ru' => ['/catalog', 'ru'],
            'category ru' => ['/catalog/wifi-routers', 'ru'],
            'product ru' => ['/product/hap-ax2', 'ru'],
            'services ru' => ['/services', 'ru'],
            'solutions ru' => ['/solutions', 'ru'],
            'contact ru' => ['/contact', 'ru'],
            'home tk' => ['/tk', 'tk'],
            'catalog tk' => ['/tk/catalog', 'tk'],
            'product tk' => ['/tk/product/wap-ax', 'tk'],
            'services tk' => ['/tk/services', 'tk'],
            'home en' => ['/en', 'en'],
            'solutions en' => ['/en/solutions', 'en'],
            'contact en' => ['/en/contact', 'en'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_in_locale(string $url, string $locale): void
    {
        $response = $this->get($url);

        $response->assertOk();
        $response->assertSee('<html lang="'.$locale.'">', false);
        $response->assertSee('hreflang="ru"', false);
        $response->assertSee('hreflang="tk"', false);
        $response->assertSee('hreflang="en"', false);
        $response->assertSee('rel="canonical"', false);
    }

    public function test_home_shows_core_offer_and_contacts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Сеть, которая стабильно работает')
            ->assertSee('Подбор')
            ->assertSee('Установка')
            ->assertSee('https://wa.me/99360000000', false)
            ->assertSee('tel:+99360000000', false)
            ->assertSee('Доставка по всему Туркменистану')
            ->assertSee('не является официальным сайтом');
    }

    public function test_language_switcher_keeps_the_current_page(): void
    {
        $this->get('/product/hap-ax2')
            ->assertSee('href="'.url('/tk/product/hap-ax2').'"', false)
            ->assertSee('href="'.url('/en/product/hap-ax2').'"', false);

        $this->get('/en/catalog?segment=office')
            ->assertSee('href="'.url('/catalog').'?segment=office"', false);
    }

    public function test_unknown_locale_and_missing_pages_return_404(): void
    {
        $this->get('/de/catalog')->assertNotFound();
        $this->get('/product/does-not-exist')->assertNotFound();
        $this->get('/catalog/does-not-exist')->assertNotFound();
    }

    public function test_unpublished_product_and_hidden_category_are_not_public(): void
    {
        $this->get('/product/hap-be-lite')->assertNotFound();

        Category::where('slug', 'firewalls')->update(['is_visible' => false]);
        $this->get('/catalog/firewalls')->assertNotFound();
        $this->get('/product/fortigate-90g')->assertNotFound();
    }

    public function test_security_and_indexing_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString("script-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_robots_blocks_the_concept_and_opens_after_launch(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

        config(['site.indexable' => true]);
        $this->get('/robots.txt')->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_sitemap_lists_products_in_every_language(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<loc>'.url('/product/hap-ax2').'</loc>', false);
        $response->assertSee('<loc>'.url('/tk/product/hap-ax2').'</loc>', false);
        $response->assertSee('<loc>'.url('/en/product/hap-ax2').'</loc>', false);
        $response->assertDontSee('hap-be-lite');
    }

    public function test_debug_information_is_not_exposed_in_production_mode(): void
    {
        config(['app.debug' => false]);
        Product::query()->update(['category_id' => null]);

        $this->get('/product/does-not-exist')->assertNotFound()->assertDontSee('Stack trace')->assertDontSee('vendor/laravel');
    }

    public function test_framework_paths_are_not_served(): void
    {
        $this->get('/storage/logs/laravel.log')->assertNotFound();
        $this->get('/.env')->assertNotFound();
    }
}
