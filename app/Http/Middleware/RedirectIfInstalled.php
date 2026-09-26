<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installedFile = storage_path('installed');

        if (File::exists($installedFile)) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
