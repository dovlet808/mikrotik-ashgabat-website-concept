<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression: saving a product that already has specifications failed with HTTP 500
 * (LazyLoadingViolationException) because the specification `saved` hook read `$spec->product`.
 */
class ProductSpecificationSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        $this->actingAs($this->admin());
    }

    public function test_a_product_with_specifications_can_be_edited_and_saved(): void
    {
        $product = Product::where('slug', 'hap-ax2')->firstOrFail();
        $this->assertGreaterThan(0, $product->specifications()->count());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['is_featured' => ! $product->is_featured])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertHasNoErrors();

        $this->assertSame(! $product->is_featured, $product->fresh()->is_featured);
    }

    public function test_specification_changes_refresh_the_product_search_text(): void
    {
        $product = Product::where('slug', 'hap-ax2')->firstOrFail();
        $spec = $product->specifications()->firstOrFail();

        $spec->update(['value' => ['ru' => 'Уникальное значение QA-42', 'tk' => 'QA-42', 'en' => 'QA-42']]);

        $this->assertStringContainsString(mb_strtolower('Уникальное значение QA-42'), $product->fresh()->search_text);
        $this->get('/catalog?q='.urlencode('QA-42'))
            ->assertOk()
            ->assertSee('/product/hap-ax2', false)
            ->assertDontSee('/product/hex-s', false);
    }
}
