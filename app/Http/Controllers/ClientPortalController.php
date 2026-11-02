<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Portal\AuthController as PortalAuthController;
use App\Jobs\SendClientPortalLink;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\Company;
use App\Support\TenantDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The tenant's side of the client portal: the company-wide switch (Settings → Client portal) and
 * per-client access (client page). Clients get in by invitation only.
 */
class ClientPortalController extends Controller
{
    public function updateSettings(Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);
        $company = auth()->user()->company;

        // Switching off stays possible on any plan; only switching on needs the feature.
        abort_if($validated['enabled'] && ! $company->canUseFeature('client_portal'), 403, 'Your plan does not include the client portal.');

        $company->forceFill([
            'client_portal_enabled' => $validated['enabled'],
            'client_portal_slug' => $company->client_portal_slug ?? self::newSlug($company),
        ])->save();

        return back()->with('success', $validated['enabled'] ? 'Client portal switched on.' : 'Client portal switched off. Clients can no longer sign in.');
    }

    public function invite(Client $client): RedirectResponse
    {
        $error = self::sendInvite(auth()->user()->company, $client, $message);

        return $error ? back()->with('error', $error) : back()->with('success', $message);
    }

    /**
     * Create or refresh the client's login and email them a link.
     *
     * @return string|null why the invite could not be sent; otherwise $message says what was sent
     */
    public static function sendInvite(Company $company, Client $client, ?string &$message = null): ?string
    {
        if (! $company->clientPortalOpen()) {
            return 'Switch on the client portal in Settings first.';
        }

        $email = Str::lower(trim((string) $client->email));

        if ($email === '') {
            return 'Add an email address to this client first.';
        }

        $account = ClientAccount::where('client_id', $client->id)->first();

        if (ClientAccount::where('company_id', $company->id)->where('email', $email)->where('client_id', '!=', $client->id)->exists()) {
            return "Another client already signs in with {$email}. Each client needs their own email address.";
        }

        $account ??= new ClientAccount(['company_id' => $company->id, 'client_id' => $client->id, 'email' => $email]);
        $account->is_enabled = true;
        $account->invited_at = now();
        $account->save();

        // Someone who already has a password gets the shorter reset link.
        $invite = blank($account->password);
        $token = $account->issueSetupToken($invite ? now()->addDays(ClientAccount::INVITE_LINK_DAYS) : now()->addMinutes(ClientAccount::RESET_LINK_MINUTES));

        SendClientPortalLink::dispatch($account, PortalAuthController::setPasswordUrl($company, $token), $invite);

        $message = ($invite ? 'Invitation sent to ' : 'Password link sent to ') . $email . '.';

        return null;
    }

    public function revoke(Client $client): RedirectResponse
    {
        ClientAccount::where('client_id', $client->id)->update(['is_enabled' => false, 'setup_token_hash' => null, 'setup_token_expires_at' => null]);

        return back()->with('success', 'Portal access removed. This client can no longer sign in.');
    }

    public function restore(Client $client): RedirectResponse
    {
        if (! auth()->user()->company->clientPortalOpen()) {
            return back()->with('error', 'Switch on the client portal in Settings first.');
        }

        ClientAccount::where('client_id', $client->id)->update(['is_enabled' => true]);

        return back()->with('success', 'Portal access restored.');
    }

    /** Portal state for the tenant's Settings tab. */
    public static function settingsPayload(Company $company): array
    {
        return [
            'included' => $company->canUseFeature('client_portal'),
            'enabled' => (bool) $company->client_portal_enabled,
            'url' => $company->client_portal_slug ? $company->clientPortalUrl() : null,
            'accounts' => ClientAccount::where('company_id', $company->id)->where('is_enabled', true)->count(),
        ];
    }

    /** Portal state for one client's page in the tenant app. */
    public static function clientPayload(Company $company, ?Client $client = null): array
    {
        $account = $client ? ClientAccount::where('client_id', $client->id)->first() : null;

        return [
            'included' => $company->canUseFeature('client_portal'),
            'open' => $company->clientPortalOpen(),
            'account' => $account ? [
                'email' => $account->email,
                'is_enabled' => $account->is_enabled,
                'activated' => filled($account->password),
                'invited_at' => $account->invited_at?->toIso8601String(),
                'last_login_at' => $account->last_login_at?->toIso8601String(),
            ] : null,
        ];
    }

    private static function newSlug(Company $company): string
    {
        $base = Str::limit(Str::slug($company->name), 40, '') ?: 'portal';

        do {
            $slug = $base . '-' . Str::lower(Str::random(6));
        } while (Company::where('client_portal_slug', $slug)->exists());

        return $slug;
    }
}
