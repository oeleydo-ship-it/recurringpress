<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureTenant::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'installed' => \App\Http\Middleware\CheckInstallation::class,
            'not.installed' => \App\Http\Middleware\RedirectIfInstalled::class,
            'pending.install' => \App\Http\Middleware\DetectPendingInstall::class,
            'verified' => \App\Http\Middleware\CheckEmailVerification::class,
            'honeypot' => \App\Http\Middleware\ProtectAgainstSpam::class,
        ]);

        // Stripe calls the webhook server-to-server without a CSRF token; it is authenticated
        // by its signature instead (PaymentController::handleWebhook). Without this every
        // webhook was rejected with 419.
        $middleware->preventRequestForgery(except: ['stripe/webhook']);

        $middleware->web(append: [
            \App\Http\Middleware\CheckInstallation::class,
            \App\Http\Middleware\DetectPendingInstall::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Branded error pages (Inertia) for web requests outside debug mode.
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            $status = $response->getStatusCode();

            if (! app()->hasDebugModeEnabled() && in_array($status, [403, 404, 419, 429, 500, 503], true)
                && ! $request->expectsJson() && ! $request->is('horizon*', 'stripe/webhook')) {
                if ($status === 419) {
                    return back()->with('error', 'Your session expired. Please try again.');
                }

                return \Inertia\Inertia::render('Errors/Show', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
