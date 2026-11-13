<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        $this->actingAs($this->admin());
    }

    /**
     * The owner's end-to-end workflow: create, upload, price, publish,
     * see it on the website, change the price, see the new price.
     */
    public function test_owner_creates_product_with_photo_and_it_appears_on_the_site(): void
    {
        $undoRepeaterFake = Repeater::fake();
        $category = Category::where('slug', 'wifi-routers')->first();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Demo Router X1',
                'slug' => 'demo-router-x1',
                'product_code' => 'DEMO-X1-TEST',
                'brand' => 'MikroTik',
                'category_id' => $category->id,
                'segments' => ['home', 'office'],
                'is_published' => true,
                'price' => 4200,
                'currency' => 'TMT',
                'availability' => 'in_stock',
                'summary' => ['ru' => 'Тестовый роутер для демонстрации', 'en' => 'Demo router'],
                'images' => [
                    ['path' => UploadedFile::fake()->image('demo-router.jpg', 1200, 1500), 'alt' => ['ru' => 'Demo Router X1 спереди']],
                ],
                'specifications' => [
                    ['group' => ['ru' => 'Порты'], 'label' => ['ru' => 'Ethernet'], 'value' => ['ru' => '5 портов']],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $product = Product::where('slug', 'demo-router-x1')->firstOrFail();
        $image = $product->images()->sole();

        Storage::disk('uploads')->assertExists($image->path);
        Storage::disk('uploads')->assertExists(preg_replace('/\.jpg$/', '-480.webp', $image->path));
        Storage::disk('uploads')->assertExists(preg_replace('/\.jpg$/', '-960.webp', $image->path));
        $this->assertSame(1200, $image->width);
        $this->assertSame(1, $product->specifications()->count());

        $this->get('/catalog')->assertSee('Demo Router X1')->assertSee("4\u{202F}200", false);
        $this->get('/product/demo-router-x1')
            ->assertOk()
            ->assertSee('DEMO-X1-TEST')
            ->assertSee('В наличии')
            ->assertSee('5 портов')
            ->assertSee(Storage::disk('uploads')->url(preg_replace('/\.jpg$/', '-960.webp', $image->path)), false);
        $this->get('/catalog?q=demo-x1')->assertSee('Demo Router X1');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['price' => 3950])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/product/demo-router-x1')->assertSee("3\u{202F}950", false)->assertDontSee("4\u{202F}200", false);
    }

    public function test_price_can_be_changed_inline_in_the_product_table(): void
    {
        $product = Product::where('slug', 'hex-s')->first();

        Livewire::test(ListProducts::class)
            ->call('updateTableColumnState', 'price', (string) $product->getKey(), '2299');

        $this->assertEquals(2299, (float) $product->fresh()->price);
        $this->get('/product/hex-s')->assertSee("2\u{202F}299", false);
    }

    public function test_unpublishing_hides_the_product_from_visitors(): void
    {
        $product = Product::where('slug', 'wap-ax')->first();

        Livewire::test(ListProducts::class)
            ->call('updateTableColumnState', 'is_published', (string) $product->getKey(), false);

        $this->get('/product/wap-ax')->assertNotFound();
        $this->get('/catalog')->assertDontSee('wAPG-5HaxD2HaxD');
    }

    public function test_validation_rejects_bad_product_data(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => '', 'slug' => 'hap-ax2', 'price' => -5, 'category_id' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'unique', 'price', 'category_id' => 'required']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'X', 'slug' => 'Not A Slug!', 'category_id' => Category::first()->id, 'summary' => ['ru' => 'x']])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_delete_is_soft_and_can_be_restored(): void
    {
        $product = Product::where('slug', 'cap-xl-ac')->first();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])->callAction(DeleteAction::class);

        $this->assertSoftDeleted($product);
        $this->get('/product/cap-xl-ac')->assertNotFound();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])->callAction(RestoreAction::class);

        $this->assertNotSoftDeleted($product);
        $this->get('/product/cap-xl-ac')->assertOk();
    }

    public function test_force_delete_removes_image_files(): void
    {
        $product = Product::factory()->create();
        Storage::disk('uploads')->put('products/test/photo.jpg', UploadedFile::fake()->image('photo.jpg', 800, 1000)->getContent());
        $image = $product->images()->create(['path' => 'products/test/photo.jpg', 'sort_order' => 1]);
        Storage::disk('uploads')->assertExists('products/test/photo-480.webp');

        $product->forceDelete();

        Storage::disk('uploads')->assertMissing('products/test/photo.jpg');
        Storage::disk('uploads')->assertMissing('products/test/photo-480.webp');
        $this->assertModelMissing($image);
    }

    public function test_duplicate_creates_an_unpublished_copy_with_specs(): void
    {
        $product = Product::where('slug', 'hap-ac3')->first();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])->callAction('duplicate');

        $copy = Product::where('name', 'like', 'hAP ac³ (копия)')->firstOrFail();
        $this->assertFalse($copy->is_published);
        $this->assertSame($product->specifications()->count(), $copy->specifications()->count());
    }
}
