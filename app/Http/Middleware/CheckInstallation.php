<?php

namespace App\Http\Middleware;

use App\Services\AutoInstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    public function __construct(protected AutoInstallerService $installer)
    {
    }

    /**
     * Sends visitors to the web installer only while a manual (control-panel) install is pending.
     * Git / auto deployments have no install.json, so they are never redirected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installer->hasPendingInstallation()) {
            if (!$request->is('install*') && !$request->is('auto-install*')) {
                return redirect()->route('install.index');
            }
        }

        return $next($request);
    }
}
