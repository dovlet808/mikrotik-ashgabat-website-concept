<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        $this->actingAs($this->admin());
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::where('slug', 'firewalls')->first();

        Livewire::test(ListCategories::class)->callTableAction('delete', $category)->assertNotified();

        $this->assertModelExists($category);
        $this->assertSame(2, $category->products()->count());
    }

    public function test_model_level_guard_also_blocks_deletion(): void
    {
        $this->expectException(LogicException::class);

        Category::where('slug', 'wifi-routers')->first()->delete();
    }

    public function test_products_can_be_reassigned_then_category_deleted(): void
    {
        $from = Category::where('slug', 'outdoor-wireless')->first();
        $to = Category::where('slug', 'access-points')->first();

        Livewire::test(ListCategories::class)
            ->callTableAction('reassignAndDelete', $from, data: ['target_category_id' => $to->id]);

        $this->assertModelMissing($from);
        $this->assertSame($to->id, Product::where('slug', 'sxtsq-5-ax')->first()->category_id);
        $this->get('/product/sxtsq-5-ax')->assertOk();
    }

    public function test_empty_category_can_be_created_and_deleted(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => ['ru' => 'Коммутаторы', 'en' => 'Switches'], 'slug' => 'switches', 'is_visible' => true, 'sort_order' => 60])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'switches')->firstOrFail();
        $this->assertSame('Коммутаторы', $category->translate('name', 'ru'));

        Livewire::test(ListCategories::class)->callTableAction('delete', $category);
        $this->assertModelMissing($category);
    }

    public function test_hidden_categories_disappear_from_navigation(): void
    {
        Category::where('slug', 'firewalls')->update(['is_visible' => false]);

        $this->get('/catalog')->assertDontSee('href="'.url('/catalog/firewalls').'"', false);
    }
}
