<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every web response.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        // SAMEORIGIN (not DENY) because settings/onboarding embed template previews in iframes.
        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()', false);

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        // Never cache authenticated or auth-form pages in shared caches.
        if ($request->user() || $request->user('superadmin') || $request->is('login', 'register', 'forgot-password', 'reset-password/*', 'superadmin/login')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
