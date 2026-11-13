<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $entries = collect([
            ['home', [], null],
            ['catalog.index', [], null],
            ['services.index', [], null],
            ['solutions.index', [], null],
            ['contact', [], null],
        ]);

        Category::query()->visible()->whereHas('products', fn ($q) => $q->published())->get()
            ->each(fn (Category $c) => $entries->push(['catalog.category', ['category' => $c->slug], $c->updated_at]));

        Product::query()->published()->get()
            ->each(fn (Product $p) => $entries->push(['products.show', ['product' => $p->slug], $p->updated_at]));

        $locales = array_keys(config('site.locales'));

        return response()
            ->view('sitemap', compact('entries', 'locales'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
