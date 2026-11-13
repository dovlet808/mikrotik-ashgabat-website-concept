<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards the source-derived catalogue against accidental edits:
 * every model, product code and price must match the supplied PDF price list.
 */
class SourceDataIntegrityTest extends TestCase
{
    public static function pdfProducts(): array
    {
        return [
            'hAP lite TC' => ['hAP lite TC', 'RB941-2nD-TC', 850],
            'hAP ax²' => ['hAP ax²', 'C52iG-5HaxD2HaxD-TC', 2850],
            'hAP ac³' => ['hAP ac³', 'RBD53iG-5HacD2HnD', 3100],
            'hEX S' => ['hEX S', 'RB760iGS', 2150],
            'wAP ax' => ['wAP ax', 'wAPG-5HaxD2HaxD', 2700],
            'hAP ax S' => ['hAP ax S', 'E62iUGS-2axD5axT', 2850],
            'cAP XL ac' => ['cAP XL ac', 'RBcAPGi-5acD2nD-XL', 2700],
            'RB5009UG+S+IN' => ['RB5009UG+S+IN', 'RB5009UG+S+IN', 6000],
            'CCR2004-16G-2S+ v.3' => ['CCR2004-16G-2S+ v.3', null, 14000],
            'SXTsq 5 ax' => ['SXTsq 5 ax', 'SXTsq-5axD', 3580],
            'L009UiGS-2HaxD-IN' => ['L009UiGS-2HaxD-IN', 'L009UiGS-2HaxD-IN', 3700],
        ];
    }

    protected function catalog(): array
    {
        return require dirname(__DIR__, 2).'/database/data/catalog.php';
    }

    #[DataProvider('pdfProducts')]
    public function test_pdf_product_matches_source(string $name, ?string $code, int $price): void
    {
        $product = collect($this->catalog()['products'])->firstWhere('name', $name);

        $this->assertNotNull($product, "{$name} missing from catalogue data");
        $this->assertSame($code, $product['product_code']);
        $this->assertSame($price, $product['price']);
        $this->assertTrue($product['is_published'] ?? true);
    }

    public function test_products_without_a_source_price_are_price_on_request(): void
    {
        $products = collect($this->catalog()['products'])->keyBy('slug');

        $this->assertNull($products['fortigate-90g']['price']);
        $this->assertNull($products['cisco-asa-5506-x']['price']);
        $this->assertFalse($products['hap-be-lite']['is_published']);
    }

    public function test_every_service_cites_its_source(): void
    {
        foreach ($this->catalog()['services'] as $service) {
            $this->assertNotEmpty($service['source'], $service['slug']);
            $this->assertArrayHasKey('ru', $service['title']);
        }
    }

    public function test_contact_channels_are_configuration_not_seed_data(): void
    {
        $settings = $this->catalog()['settings'];

        foreach (['phone', 'whatsapp', 'instagram_handle', 'instagram_url'] as $key) {
            $this->assertArrayNotHasKey($key, $settings);
        }
    }
}
