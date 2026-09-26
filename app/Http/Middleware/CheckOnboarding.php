<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboarding
{
    /**
     * Routes that should be accessible during onboarding
     */
    protected $allowedRoutes = [
        'onboarding.*',
        'logout',
        'profile.*',
        'verification.*',
        'settings.subscription',
        'settings.subscription.*',
        'settings.template.preview',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip if user doesn't have a company (shouldn't happen but safety check)
        if (!$user->company) {
            return $next($request);
        }

        // Skip if onboarding is completed
        if ($user->company->onboarding_completed) {
            return $next($request);
        }

        // Check if current route is allowed during onboarding
        foreach ($this->allowedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Redirect to onboarding setup
        return redirect()->route('onboarding.welcome');
    }
}
