<?php

namespace App\Http\Middleware;

use App\Services\AutoInstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectPendingInstall
{
    protected AutoInstallerService $installer;

    public function __construct(AutoInstallerService $installer)
    {
        $this->installer = $installer;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if autoinstaller is disabled
        if (!config('autoinstaller.enabled', true)) {
            return $next($request);
        }

        // Skip if already on autoinstall routes
        if ($request->is('auto-install*')) {
            return $next($request);
        }

        // Skip API and install routes
        if ($request->is('api/*') || $request->is('install*')) {
            return $next($request);
        }

        // Check for pending installation
        if ($this->installer->hasPendingInstallation()) {
            return redirect()->route('autoinstall.detect');
        }

        return $next($request);
    }
}
