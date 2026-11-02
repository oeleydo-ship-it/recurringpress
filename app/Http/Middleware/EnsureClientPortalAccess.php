<?php

namespace App\Http\Middleware;

use App\Models\ClientAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-checked on every request, so removing a client's access (or changing their password)
 * ends sessions that are already open. Runs after ResolveClientPortal.
 */
class EnsureClientPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('client');
        /** @var ClientAccount|null $account */
        $account = $guard->user();
        $company = $request->attributes->get(ResolveClientPortal::COMPANY);

        // Signed in at another company's portal: that session stays, but it means nothing here.
        if (! $account || $account->company_id !== $company->id) {
            return redirect()->route('portal.login');
        }

        $fingerprint = (string) $request->session()->get(ClientAccount::SESSION_FINGERPRINT);

        if (! $account->canSignIn() || ! hash_equals($account->sessionFingerprint(), $fingerprint)) {
            $guard->logout();

            return redirect()->route('portal.login')->with('error', 'Please sign in again. If you can no longer sign in, contact ' . $company->name . '.');
        }

        return $next($request);
    }
}
