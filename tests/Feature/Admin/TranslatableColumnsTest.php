<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression: translatable attributes are {ru, tk, en} arrays. Filament renders an array state as a
 * list, so formatting it printed the Russian name once per locale ("Wi-Fi роутеры, Wi-Fi роутеры, …").
 */
class TranslatableColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        $this->actingAs($this->admin());
    }

    public function test_category_names_render_once_in_the_admin_table(): void
    {
        $category = Category::where('slug', 'wifi-routers')->firstOrFail();
        $name = $category->translate('name', 'ru');

        Livewire::test(ListCategories::class)
            ->assertTableColumnStateSet('name', $name, $category)
            ->assertSee($name)
            ->assertDontSee($name.', '.$name);
    }

    public function test_service_titles_render_once_in_the_admin_table(): void
    {
        $service = Service::query()->orderBy('sort_order')->firstOrFail();
        $title = $service->translate('title', 'ru');

        Livewire::test(ListServices::class)
            ->assertTableColumnStateSet('title', $title, $service)
            ->assertSee($title)
            ->assertDontSee($title.', '.$title);
    }

    public function test_category_translations_remain_editable(): void
    {
        $category = Category::where('slug', 'access-points')->firstOrFail();

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(['name' => $category->name])
            ->fillForm(['name' => ['ru' => 'Точки доступа', 'tk' => 'Elýeterlilik nokatlary (test)', 'en' => 'Access points (test)']])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('Точки доступа', $category->translate('name', 'ru'));
        $this->assertSame('Elýeterlilik nokatlary (test)', $category->translate('name', 'tk'));
        $this->assertSame('Access points (test)', $category->translate('name', 'en'));
    }

    public function test_public_category_labels_use_the_page_locale(): void
    {
        $category = Category::where('slug', 'wifi-routers')->firstOrFail();

        foreach (['ru' => '/catalog', 'tk' => '/tk/catalog', 'en' => '/en/catalog'] as $locale => $url) {
            $this->get($url)->assertOk()->assertSee($category->translate('name', $locale));
        }
    }
}
