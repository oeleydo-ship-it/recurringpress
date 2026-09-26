<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    public function handle(Request $request, Closure $next): Response
    {
        $installedFile = storage_path('installed');

        if (!File::exists($installedFile)) {
            if (!$request->is('install*') && !$request->is('auto-install*')) {
                return redirect()->route('install.index');
            }
        }

        return $next($request);
    }
}
