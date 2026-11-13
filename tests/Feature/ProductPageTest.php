<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_product_page_shows_exact_source_data(): void
    {
        $this->get('/product/hap-ax2')
            ->assertOk()
            ->assertSee('<h1 id="product-title" class="mt-4 text-title font-semibold">hAP ax²</h1>', false)
            ->assertSee('C52iG-5HaxD2HaxD-TC')
            ->assertSee("2\u{202F}850", false)
            ->assertSee('манат')
            ->assertSee('Двухъядерный ARM, 800 МГц')
            ->assertSee('Современная домашняя или офисная сеть «всё в одном».');
    }

    public function test_unconfirmed_availability_never_claims_in_stock(): void
    {
        $this->get('/product/hap-ax2')
            ->assertSee('Наличие уточняйте')
            ->assertDontSee('В наличии');

        $this->get('/en/product/hap-ax2')->assertSee('Contact us to confirm availability');
    }

    public function test_product_without_price_shows_price_on_request(): void
    {
        $this->get('/product/fortigate-90g')->assertOk()->assertSee('Цена по запросу')->assertSee('Уточнить цену и наличие');
    }

    public function test_whatsapp_link_contains_the_product(): void
    {
        $html = $this->get('/product/hex-s')->getContent();

        $this->assertMatchesRegularExpression('#https://wa\.me/99360000000\?text=[^"]*hEX%20S#', $html);
        $this->assertStringContainsString(rawurlencode(url('/product/hex-s')), $html);
    }

    public function test_product_json_ld_and_open_graph(): void
    {
        $this->get('/product/hap-ac3')
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"priceCurrency":"TMT"', false)
            ->assertSee('"price":"3100.00"', false)
            ->assertSee('<meta property="og:type" content="product">', false);
    }

    public function test_translations_fall_back_to_russian_when_missing(): void
    {
        Product::where('slug', 'hex-s')->first()->update(['summary' => ['ru' => 'Только русский текст']]);

        $this->get('/en/product/hex-s')->assertSee('Только русский текст');
    }

    public function test_related_products_and_product_inquiry_form(): void
    {
        $product = Product::where('slug', 'hap-ax2')->first();

        $this->get('/product/hap-ax2')
            ->assertSee('Похожее оборудование')
            ->assertSee('name="product_id" value="'.$product->id.'"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_price_and_availability_changes_appear_immediately(): void
    {
        Product::where('slug', 'wap-ax')->first()->update(['price' => 2999, 'availability' => Availability::InStock]);

        $this->get('/product/wap-ax')->assertSee("2\u{202F}999", false)->assertSee('В наличии');
        $this->get('/catalog')->assertSee("2\u{202F}999", false);
    }
}
