<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Product photos must load on whichever address the site is opened: a LAN IP from a phone, localhost, or www and
 * non-www in production. They used to be built from APP_URL, so on any other host the browser either could not reach
 * them or the strict Content-Security-Policy (img-src 'self') blocked them.
 */
class ImageUrlHostTest extends TestCase
{
    use RefreshDatabase;

    private const LAN = 'http://192.168.1.50:8097';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://127.0.0.1:8097']);
        $this->seedCatalog();

        Storage::disk('uploads')->put('products/test/photo.jpg', UploadedFile::fake()->image('photo.jpg', 2000, 2500)->getContent());
        Product::query()->where('slug', 'hap-ax2')->firstOrFail()
            ->images()->create(['path' => 'products/test/photo.jpg', 'sort_order' => 0, 'width' => 2000, 'height' => 2500]);
    }

    public function test_product_page_photos_use_the_requested_host(): void
    {
        $html = $this->get(self::LAN.'/product/hap-ax2')->assertOk()->getContent();

        $this->assertStringContainsString('src="'.self::LAN.'/uploads/products/test/photo-960.webp"', $html);
        $this->assertStringContainsString(self::LAN.'/uploads/products/test/photo-480.webp 480w', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.self::LAN.'/uploads/products/test/photo-1600.webp">', $html);
        $this->assertStringContainsString(json_encode(self::LAN.'/uploads/products/test/photo-1600.webp', JSON_UNESCAPED_SLASHES), str_replace('\/', '/', $html));
        $this->assertStringNotContainsString('127.0.0.1:8097/uploads', $html);
    }

    public function test_catalog_and_home_photos_use_the_requested_host(): void
    {
        foreach (['/', '/catalog', '/en/catalog'] as $path) {
            $html = $this->get(self::LAN.$path)->assertOk()->getContent();

            $this->assertStringContainsString(self::LAN.'/uploads/products/test/photo-', $html, $path);
            $this->assertStringNotContainsString('127.0.0.1:8097/uploads', $html, $path);
        }
    }

    public function test_upload_disk_has_no_fixed_host(): void
    {
        // Filament's photo previews in the admin read the disk URL directly, so it must not carry a fixed host either.
        $this->assertSame('/uploads', config('filesystems.disks.uploads.url'));
        $this->assertSame('/uploads/products/test/photo.jpg', Storage::disk('uploads')->url('products/test/photo.jpg'));
    }
}
