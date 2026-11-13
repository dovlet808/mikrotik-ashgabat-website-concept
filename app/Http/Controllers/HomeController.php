<?php

namespace App\Http\Controllers;

use App\Enums\Segment;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featured = Product::query()->published()->where('is_featured', true)
            ->with(['primaryImage', 'category'])->ordered()->limit(4)->get();

        if ($featured->count() < 4) {
            $featured = $featured->concat(
                Product::query()->published()->whereNotIn('id', $featured->modelKeys())
                    ->with(['primaryImage', 'category'])->ordered()->limit(4 - $featured->count())->get()
            );
        }

        $hero = Product::query()->published()->where('slug', 'hap-ax2')->with('primaryImage')->first()
            ?? $featured->first();

        return view('pages.home', [
            'hero' => $hero,
            'featured' => $featured,
            'categories' => Category::query()->visible()
                ->withCount(['products as published_count' => fn ($q) => $q->published()])
                ->with(['products' => fn ($q) => $q->published()->with('primaryImage')->ordered()])
                ->get()
                ->filter(fn ($c) => $c->published_count > 0),
            'services' => Service::query()->published()->get(),
            'segments' => Segment::cases(),
            'productCount' => Product::query()->published()->count(),
        ]);
    }
}
