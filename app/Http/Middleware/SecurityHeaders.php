<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! config('site.indexable')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($this->wantsContentSecurityPolicy($request)) {
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "img-src 'self' data:",
                "style-src 'self' 'unsafe-inline'",
                "script-src 'self'",
                "font-src 'self'",
                "connect-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]));
        }

        return $response;
    }

    /**
     * Public pages get a strict CSP. The admin panel (Livewire/Alpine) and the
     * Vite dev server are excluded.
     */
    protected function wantsContentSecurityPolicy(Request $request): bool
    {
        if ($request->is('admin', 'admin/*', 'livewire*', 'filament/*')) {
            return false;
        }

        return ! is_file(public_path('hot'));
    }
}
