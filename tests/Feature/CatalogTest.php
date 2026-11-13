<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_catalog_lists_published_products_with_pagination(): void
    {
        $this->get('/catalog')->assertOk()->assertSee('13 моделей')->assertSee('hAP ax²')->assertDontSee('hAP be lite');
        $this->get('/catalog')->assertSee('01 из 02', false);
        $this->get('/catalog?page=2')->assertOk()->assertSee('02 из 02', false)->assertSee('rel="prev"', false);
    }

    public function test_search_by_model_code_and_russian_text(): void
    {
        $this->get('/catalog?q=hEX')->assertSee('hEX S')->assertDontSee('>wAP ax<', false);
        $this->get('/catalog?q=RB760iGS')->assertSee('hEX S');
        $this->get('/catalog?q='.urlencode('потолочная'))->assertSee('cAP XL ac')->assertSee('1 модель');
        $this->get('/catalog?q='.urlencode('wi-fi 6 роутер'))->assertSee('hAP ax²');
        $this->get('/catalog?q=zzzzzz')->assertSee('Ничего не найдено');
    }

    public function test_category_page_only_shows_its_products(): void
    {
        $this->get('/catalog/firewalls')
            ->assertOk()
            ->assertSee('FortiGate 90G')
            ->assertSee('ASA 5506-X')
            ->assertSee('2 модели')
            ->assertDontSee('hAP lite TC');
    }

    public function test_filter_by_segment_and_availability(): void
    {
        $this->get('/catalog?segment=outdoor')->assertSee('SXTsq 5 ax')->assertSee('wAP ax')->assertSee('2 модели');

        Product::where('slug', 'hex-s')->update(['availability' => Availability::InStock]);
        $this->get('/catalog?availability=in_stock')->assertSee('hEX S')->assertSee('1 модель');
    }

    public function test_sorting_by_price_puts_price_on_request_last(): void
    {
        $asc = $this->get('/catalog?sort=price_asc&q=a')->getContent();
        $this->assertLessThan(strpos($asc, 'hAP ax²'), strpos($asc, 'hAP lite TC'));

        $html = $this->get('/catalog?sort=price_desc')->getContent();
        $this->assertLessThan(strpos($html, 'RB5009UG+S+IN'), strpos($html, 'CCR2004-16G-2S+ v.3'));
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->get('/catalog?segment=hacker&availability=<script>&sort=drop')->assertOk()->assertSee('13 моделей');
    }
}
