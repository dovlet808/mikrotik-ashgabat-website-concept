<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SolutionController;
use Illuminate\Support\Facades\Route;

$pages = function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('catalog/{category:slug}', [CatalogController::class, 'category'])->name('catalog.category');
    Route::get('product/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('solutions', [SolutionController::class, 'index'])->name('solutions.index');
    Route::get('contact', [ContactController::class, 'index'])->name('contact');
    Route::post('inquiries', [InquiryController::class, 'store'])
        ->middleware('throttle:inquiry-posts')
        ->name('inquiries.store');
};

Route::middleware('locale')->group($pages);

Route::prefix('{locale}')
    ->where(['locale' => implode('|', array_diff(array_keys(config('site.locales')), [config('site.default_locale')]))])
    ->middleware('locale')
    ->name('localized.')
    ->group($pages);

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsController::class)->name('robots');
