<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.contact', [
            'services' => Service::query()->published()->get(),
            'selectedService' => Service::query()->published()->where('slug', $request->query('service'))->first(),
            'selectedProduct' => Product::query()->published()->where('slug', $request->query('product'))->first(),
        ]);
    }
}
