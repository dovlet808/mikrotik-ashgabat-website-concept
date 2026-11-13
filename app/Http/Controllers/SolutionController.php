<?php

namespace App\Http\Controllers;

use App\Enums\Segment;
use App\Models\Product;
use Illuminate\View\View;

class SolutionController extends Controller
{
    public function index(): View
    {
        $products = Product::query()->published()->with(['primaryImage', 'category'])->ordered()->get();

        $solutions = collect(Segment::cases())->map(fn (Segment $segment) => [
            'segment' => $segment,
            'products' => $products->filter(fn (Product $p) => in_array($segment->value, $p->segments ?? [], true))->values(),
        ]);

        return view('pages.solutions', ['solutions' => $solutions]);
    }
}
