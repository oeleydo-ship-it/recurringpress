<?php

namespace App\Http\Middleware;

use App\Support\TenantDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requests on a tenant's custom domain or platform subdomain may only reach that tenant's public invoice and
 * quotation pages; everything else is sent to the platform. Unknown hosts get a bare 404
 * (no session, no branding, nothing about any tenant).
 *
 * Runs before StartSession (prepended to the web group).
 */
class ResolveCustomDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // With custom domains and subdomains both switched off, host handling is unchanged.
        if (! TenantDomain::anyEnabled() || TenantDomain::isPlatformHost($host)) {
            return $next($request);
        }

        $company = TenantDomain::companyForHost($host);

        if (! $company) {
            return response('Not Found', 404, ['Content-Type' => 'text/plain']);
        }

        if (! $request->is('invoice/*', 'quotation/*')) {
            return $request->isMethod('GET')
                ? redirect()->away(rtrim((string) config('app.url'), '/') . '/' . ltrim($request->path(), '/'))
                : response('Not Found', 404, ['Content-Type' => 'text/plain']);
        }

        $request->attributes->set(TenantDomain::ATTRIBUTE, $company->id);

        // A platform-wide cookie domain (e.g. ".recurringpress.com") would be rejected by the
        // browser here, breaking CSRF on the accept/reject forms. Use a host-only cookie.
        config(['session.domain' => null]);

        return $next($request);
    }
}
