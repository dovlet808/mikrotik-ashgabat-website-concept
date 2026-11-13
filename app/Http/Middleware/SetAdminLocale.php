<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        app()->setLocale(array_key_exists((string) $locale, config('site.admin_locales')) ? $locale : config('site.default_locale'));

        return $next($request);
    }
}
