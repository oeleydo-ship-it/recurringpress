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

        $middleware->web(append: [
            \App\Http\Middleware\CheckInstallation::class,
            \App\Http\Middleware\DetectPendingInstall::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
