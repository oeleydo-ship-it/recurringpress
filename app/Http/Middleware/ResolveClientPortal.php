<?php

namespace App\Http\Middleware;

use App\Models\ClientAccount;
use App\Models\Company;
use App\Support\PublicDocument;
use App\Support\TenantDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Finds the company a /portal/{portal}/... request belongs to and closes the portal when the
 * tenant switched it off or its plan no longer includes it. On a tenant host only that tenant's
 * portal exists.
 */
class ResolveClientPortal
{
    public const COMPANY = 'client_portal_company';

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('portal');
        $company = Company::where('client_portal_slug', $slug)->first();
        $hostCompanyId = $request->attributes->get(TenantDomain::ATTRIBUTE);

        abort_if(! $company || ($hostCompanyId && (int) $hostCompanyId !== $company->id), 404);

        $request->attributes->set(self::COMPANY, $company);
        $request->route()->forgetParameter('portal');
        URL::defaults(['portal' => $slug]);

        $guard = Auth::guard('client');
        /** @var ClientAccount|null $account */
        $account = $guard->user();
        $signedIn = $account && $account->company_id === $company->id;

        Inertia::share('portal', fn () => [
            'company' => PublicDocument::company($company),
            'currency' => $company->currency ?? 'USD',
            'date_format' => $company->settings->date_format ?? 'M d, Y',
            'client' => $signedIn && $guard->check() ? ['name' => $account->client?->bill_to_name, 'email' => $account->email] : null,
            'urls' => [
                'login' => route('portal.login'),
                'forgot' => route('portal.password.request'),
                'dashboard' => route('portal.dashboard'),
                'invoices' => route('portal.invoices'),
                'quotations' => route('portal.quotations'),
                'account' => route('portal.account'),
                'logout' => route('portal.logout'),
            ],
        ]);

        if (! $company->clientPortalOpen()) {
            if ($signedIn) {
                $guard->logout();
            }

            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                return redirect()->route('portal.login');
            }

            return Inertia::render('Portal/Unavailable')->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
