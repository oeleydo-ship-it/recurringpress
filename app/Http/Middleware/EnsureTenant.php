<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->company_id) {
            return redirect()->route('company.create')
                ->with('warning', 'Please create or join a company first.');
        }

        return $next($request);
    }
}
