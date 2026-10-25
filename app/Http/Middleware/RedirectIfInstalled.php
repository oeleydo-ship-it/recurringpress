<?php

namespace App\Http\Middleware;

use App\Services\AutoInstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the installer routes. They are open only while a manual install is pending
 * (install.json present, no storage/installed). runInstall() drops every table, so the
 * routes must stay closed on installed sites and on git / auto deployments.
 */
class RedirectIfInstalled
{
    public function __construct(protected AutoInstallerService $installer)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installer->isMarkedInstalled()) {
            return redirect()->route('login');
        }

        if (!$this->installer->hasPendingInstallation()) {
            abort(404);
        }

        return $next($request);
    }
}
