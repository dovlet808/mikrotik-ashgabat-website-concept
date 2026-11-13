<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless(Product::query()->published()->whereKey($product->getKey())->exists(), 404);

        $product->load(['images', 'specifications', 'category']);

        $related = Product::query()->published()
            ->whereKeyNot($product->getKey())
            ->where('category_id', $product->category_id)
            ->with(['primaryImage', 'category'])->ordered()->limit(4)->get();

        if ($related->count() < 4 && $product->segments) {
            $related = $related->concat(
                Product::query()->published()
                    ->whereKeyNot($product->getKey())
                    ->whereNotIn('id', $related->modelKeys())
                    ->where(function ($q) use ($product) {
                        foreach ($product->segments as $segment) {
                            $q->orWhere('segments', 'like', '%"'.$segment.'"%');
                        }
                    })
                    ->with(['primaryImage', 'category'])->ordered()->limit(4 - $related->count())->get()
            );
        }

        return view('pages.product', [
            'product' => $product,
            'related' => $related,
            'specGroups' => $product->specifications->groupBy(fn ($spec) => $spec->translate('group') ?? ''),
        ]);
    }
}
