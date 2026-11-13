<?php

namespace Tests;

use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep the real disk's URL setting, so tests see the same /uploads/... URLs as the website.
        Storage::fake('uploads', ['url' => config('filesystems.disks.uploads.url')]);
        config(['site.seed_images' => false]);
        app(Settings::class)->flush();
        RateLimiter::clear('inquiry-posts');
    }

    protected function seedCatalog(bool $withImages = false): void
    {
        config(['site.seed_images' => $withImages]);
        $this->seed(CatalogSeeder::class);
        app(Settings::class)->flush();
    }

    protected function admin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['locale' => 'ru']);
        $user->is_admin = true;
        $user->save();

        return $user;
    }
}
