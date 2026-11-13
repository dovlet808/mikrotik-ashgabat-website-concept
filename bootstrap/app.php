<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
        ]);
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Links and image URLs are built from the request's host, so production only answers for APP_URL's host and
        // its subdomains (www). Laravel skips this check in the local environment, so LAN testing from a phone works.
        $middleware->trustHosts();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Unmatched URLs and missing records (route-model binding runs before the `locale` middleware)
        // never reach SetLocale, so take the language from the URL prefix for the 404 page.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $prefix = $request->segment(1);

            if ($prefix !== 'admin') {
                app()->setLocale($prefix !== null && array_key_exists($prefix, config('site.locales')) ? $prefix : config('site.default_locale'));
            }

            return null;
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->routeIs('inquiries.store', 'localized.inquiries.store')) {
                return back()->withInput($request->except('_token'))->withErrors(['form' => __('site.form.session_expired')]);
            }
        });
    })->create();
