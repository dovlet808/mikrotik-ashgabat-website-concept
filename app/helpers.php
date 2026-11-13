<?php

use App\Support\Settings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

if (! function_exists('localized_route')) {
    /**
     * Route URL for the given (or current) locale. The default locale has no prefix.
     */
    function localized_route(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        $locale ??= app()->getLocale();
        $parameters = Arr::wrap($parameters);

        if ($locale === config('site.default_locale')) {
            return route($name, $parameters, $absolute);
        }

        return route('localized.'.$name, ['locale' => $locale] + $parameters, $absolute);
    }
}

if (! function_exists('locale_switch_url')) {
    /**
     * The current page in another language, keeping route parameters and the query string.
     */
    function locale_switch_url(string $locale, bool $withQuery = true): string
    {
        $route = request()->route();
        $name = $route?->getName();

        if (! $name || ! request()->isMethod('GET') || Str::startsWith($name, ['filament.', 'livewire.'])) {
            return localized_route('home', [], $locale);
        }

        $name = Str::after($name, 'localized.');

        if (! Route::has($name) || ! Route::has('localized.'.$name)) {
            return localized_route('home', [], $locale);
        }

        $parameters = collect($route->parameters())->except('locale')->all();
        $url = localized_route($name, $parameters, $locale);
        $query = $withQuery ? Arr::except(request()->query(), ['page']) : [];

        return $query ? $url.'?'.http_build_query($query) : $url;
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('setting_text')) {
    function setting_text(string $key, ?string $locale = null): ?string
    {
        return app(Settings::class)->text($key, $locale);
    }
}
