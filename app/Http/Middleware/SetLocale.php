<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = array_keys(config('site.locales'));
        $locale = $request->route('locale');

        if (! in_array($locale, $locales, true)) {
            $locale = config('site.default_locale');
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
        $request->route()?->forgetParameter('locale');

        return $next($request);
    }
}
