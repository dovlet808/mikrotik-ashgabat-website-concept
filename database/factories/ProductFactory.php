<?php

namespace Database\Factories;

use App\Enums\Availability;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = 'Test '.Str::upper(Str::random(5));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'product_code' => 'TST-'.Str::upper(Str::random(6)),
            'brand' => 'MikroTik',
            'summary' => ['ru' => 'Тестовое описание', 'en' => 'Test summary'],
            'price' => 1000,
            'currency' => 'TMT',
            'availability' => Availability::Confirm,
            'segments' => ['home'],
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
