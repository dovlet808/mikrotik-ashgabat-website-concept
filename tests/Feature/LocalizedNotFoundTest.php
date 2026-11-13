<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 404 pages under /tk and /en used to be rendered in Russian: the `locale` route middleware never runs
 * for unmatched URLs, and route-model binding fails before it for missing products and categories.
 */
class LocalizedNotFoundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_not_found_pages_follow_the_url_language(): void
    {
        $cases = [
            '/en/no-such-page' => 'en',
            '/en/product/no-such-model' => 'en',
            '/tk/no-such-page' => 'tk',
            '/tk/catalog/no-such-category' => 'tk',
            '/no-such-page' => 'ru',
            '/product/no-such-model' => 'ru',
            '/de/catalog' => 'ru',
        ];

        foreach ($cases as $url => $locale) {
            $prefix = $locale === 'ru' ? '' : '/'.$locale;

            $this->get($url)
                ->assertNotFound()
                ->assertSee('<html lang="'.$locale.'"', false)
                ->assertSee(__('site.errors.404_title', [], $locale))
                ->assertSee('href="'.url($prefix.'/catalog').'"', false);
        }
    }
}
