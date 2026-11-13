<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);

        if ($publicPath = config('app.public_path')) {
            $this->app->usePublicPath($publicPath);
        }
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for('inquiry-posts', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        View::composer(['components.layouts.app', 'partials.*', 'pages.*', 'errors.*'], function ($view) {
            $view->with('settings', app(Settings::class));
        });

        View::composer(['partials.header', 'partials.footer'], function ($view) {
            $view->with('navCategories', Category::query()->visible()->whereHas('products', fn ($q) => $q->published())->get());
            $view->with('navItems', $this->navigationItems());
        });
    }

    /**
     * @return array<int, array{route: string, label: string, active: bool}>
     */
    protected function navigationItems(): array
    {
        $items = [
            ['route' => 'home', 'match' => ['home'], 'label' => __('site.nav.home')],
            ['route' => 'catalog.index', 'match' => ['catalog.*', 'products.*'], 'label' => __('site.nav.catalog')],
            ['route' => 'solutions.index', 'match' => ['solutions.*'], 'label' => __('site.nav.solutions')],
            ['route' => 'services.index', 'match' => ['services.*'], 'label' => __('site.nav.services')],
            ['route' => 'contact', 'match' => ['contact'], 'label' => __('site.nav.contact')],
        ];

        return array_map(function (array $item) {
            $patterns = array_merge($item['match'], array_map(fn ($p) => 'localized.'.$p, $item['match']));
            $item['active'] = request()->routeIs(...$patterns);
            unset($item['match']);

            return $item;
        }, $items);
    }
}
