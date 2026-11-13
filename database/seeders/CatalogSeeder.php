<?php

namespace Database\Seeders;

use App\Enums\Availability;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Imports the demo catalogue from database/data/catalog.php and, when the
 * folder exists, product photographs from media/products/<slug>/. The photo
 * masters are kept outside the repository, so a fresh clone seeds no images.
 *
 * Existing records (matched by slug) are left untouched so that re-running the
 * seeder never overwrites prices or texts the owner changed in the admin panel.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/catalog.php');

        DB::transaction(function () use ($data) {
            $categories = $this->seedCategories($data['categories']);
            $this->seedProducts($data, $categories);
            $this->seedServices($data['services']);
        });

        $this->seedSettings($data['settings']);
    }

    /**
     * @return array<string, Category>
     */
    protected function seedCategories(array $rows): array
    {
        $categories = [];

        foreach ($rows as $row) {
            $categories[$row['slug']] = Category::query()->firstOrCreate(['slug' => $row['slug']], [
                'name' => $row['name'],
                'description' => $row['description'],
                'sort_order' => $row['sort_order'],
                'is_visible' => true,
            ]);
        }

        return $categories;
    }

    protected function seedProducts(array $data, array $categories): void
    {
        foreach ($data['products'] as $row) {
            if (Product::withTrashed()->where('slug', $row['slug'])->exists()) {
                $this->command?->line("  skip product {$row['slug']} (exists)");

                continue;
            }

            $product = Product::create([
                'slug' => $row['slug'],
                'name' => $row['name'],
                'product_code' => $row['product_code'],
                'brand' => $row['brand'],
                'category_id' => $categories[$row['category']]->getKey(),
                'summary' => $row['summary'],
                'purpose' => $row['purpose'],
                'description' => $row['description'],
                'price' => $row['price'],
                'currency' => 'TMT',
                'availability' => Availability::Confirm,
                'segments' => $row['segments'],
                'is_featured' => $row['is_featured'],
                'is_published' => $row['is_published'] ?? true,
                'sort_order' => $row['sort_order'],
                'source_reference' => $row['source'],
            ]);

            $labels = $data['labels'] + ($row['extra_labels'] ?? []);
            foreach (array_values($row['specs']) as $i => [$group, $label, $value]) {
                $product->specifications()->create([
                    'group' => $data['groups'][$group],
                    'label' => $labels[$label],
                    'value' => $value,
                    'sort_order' => ($i + 1) * 10,
                ]);
            }

            $this->seedImages($product, $data['views']);
            $product->refreshSearchText();
        }
    }

    protected function seedImages(Product $product, array $views): void
    {
        $directory = base_path('media/products/'.$product->slug);

        if (! config('site.seed_images') || ! is_dir($directory)) {
            return;
        }

        $disk = Storage::disk(config('site.images.disk'));

        foreach (collect(File::files($directory))->sortBy(fn ($f) => $f->getFilename())->values() as $i => $file) {
            $target = 'products/'.$product->slug.'/'.$file->getFilename();
            $disk->put($target, File::get($file->getPathname()));

            $view = preg_replace('/^\d+-/', '', $file->getFilenameWithoutExtension());
            $alt = collect($views[$view] ?? [])->map(fn ($v) => $product->displayName().' — '.$v)->all();

            $product->images()->create([
                'path' => $target,
                'alt' => $alt ?: null,
                'sort_order' => ($i + 1) * 10,
            ]);
        }
    }

    protected function seedServices(array $rows): void
    {
        foreach ($rows as $row) {
            Service::query()->firstOrCreate(['slug' => $row['slug']], [
                'title' => $row['title'],
                'summary' => $row['summary'],
                'body' => $row['body'],
                'icon' => $row['icon'],
                'is_confirmed' => true,
                'source_reference' => $row['source'],
                'is_published' => true,
                'sort_order' => $row['sort_order'],
            ]);
        }
    }

    protected function seedSettings(array $values): void
    {
        $settings = app(Settings::class);
        $current = Setting::query()->pluck('key')->all();

        $settings->save(array_diff_key($values, array_flip($current)));
    }
}
