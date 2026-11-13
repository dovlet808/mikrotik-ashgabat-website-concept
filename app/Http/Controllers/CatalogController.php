<?php

namespace App\Http\Controllers;

use App\Enums\Availability;
use App\Enums\Segment;
use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request, null);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_visible, 404);

        return $this->render($request, $category);
    }

    protected function render(Request $request, ?Category $category): View
    {
        $filters = CatalogFilters::fromRequest($request, $category);

        $products = $filters->apply(Product::query()->published()->with(['primaryImage', 'category']))
            ->paginate(config('site.catalog_per_page'))
            ->withQueryString();

        return view('pages.catalog', [
            'products' => $products,
            'filters' => $filters,
            'category' => $category,
            'categories' => Category::query()->visible()
                ->withCount(['products as published_count' => fn ($q) => $q->published()])
                ->get()
                ->filter(fn ($c) => $c->published_count > 0),
            'segments' => Segment::cases(),
            'availabilities' => Availability::cases(),
            'totalCount' => Product::query()->published()->count(),
        ]);
    }
}
