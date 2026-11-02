<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortal;
use App\Jobs\SendClientPortalLink;
use App\Models\ClientAccount;
use App\Models\Company;
use App\Support\TenantDomain;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Client portal sign in and password links. Accounts are looked up within the portal's company
 * only, so the same email at another tenant is a different account.
 */
class AuthController extends Controller
{
    protected const MAX_ATTEMPTS = 5;

    protected const DECAY_SECONDS = 300;

    protected const GENERIC_STATUS = 'If this email has portal access, you will receive a password reset link.';

    /** /portal on a tenant's own host: that tenant's portal. There is no index on the platform host. */
    public function entry(Request $request)
    {
        $companyId = $request->attributes->get(TenantDomain::ATTRIBUTE);
        $slug = $companyId ? Company::whereKey($companyId)->value('client_portal_slug') : null;

        abort_unless($slug, 404);

        return redirect()->route('portal.login', $slug);
    }

    public function showLogin(Request $request)
    {
        /** @var ClientAccount|null $account */
        $account = Auth::guard('client')->user();

        if ($account && $account->company_id === $this->company($request)->id && $account->canSignIn()) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Portal/Login');
    }

    public function login(Request $request)
    {
        $company = $this->company($request);
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc,filter', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $throttleKey = 'portal-login-fail:' . $company->id . ':' . Str::transliterate($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            event(new Lockout($request));

            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :minutes minute(s).', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($throttleKey) / 60),
                ]),
            ]);
        }

        $guard = Auth::guard('client');
        $valid = $guard->validate($credentials + ['company_id' => $company->id]);
        /** @var ClientAccount|null $account */
        $account = $guard->getLastAttempted();

        // A switched-off account gets the same answer as a wrong password.
        if (! $valid || ! $account->canSignIn()) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            Log::notice('Failed client portal login attempt', ['company_id' => $company->id, 'email' => $credentials['email'], 'ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $this->signIn($request, $account);

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->forget(ClientAccount::SESSION_FINGERPRINT);

        // The session can also hold a team or superadmin login on this browser; leave those signed in.
        if (! Auth::guard('web')->check() && ! Auth::guard('superadmin')->check()) {
            $request->session()->invalidate();
        }

        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function showForgot()
    {
        return Inertia::render('Portal/ForgotPassword');
    }

    public function sendReset(Request $request)
    {
        $company = $this->company($request);
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $validated = $request->validate(['email' => 'required|string|email:rfc,filter|max:255']);

        $account = ClientAccount::where('company_id', $company->id)->where('email', $validated['email'])->first();

        // Same response whether or not the account exists (no client enumeration).
        if ($account && $account->is_enabled && $account->clientIsActive()) {
            $token = $account->issueSetupToken(now()->addMinutes(ClientAccount::RESET_LINK_MINUTES));
            SendClientPortalLink::dispatch($account, self::setPasswordUrl($company, $token), false);
        }

        return back()->with('status', self::GENERIC_STATUS);
    }

    public function showSetPassword(Request $request, string $token)
    {
        $account = ClientAccount::findBySetupToken($this->company($request)->id, $token);

        return Inertia::render('Portal/SetPassword', [
            'valid' => $account?->clientIsActive() ?? false,
            'email' => $account?->email,
            'action' => route('portal.password.store', ['token' => $token]),
        ]);
    }

    public function setPassword(Request $request, string $token)
    {
        $account = ClientAccount::findBySetupToken($this->company($request)->id, $token);

        if (! $account?->clientIsActive()) {
            return redirect()->route('portal.password.request')->with('error', 'This link is invalid or has expired. Request a new one below.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $account->forceFill([
            'password' => $validated['password'], // hashed by the model cast
            'remember_token' => Str::random(60),
            'setup_token_hash' => null,
            'setup_token_expires_at' => null,
            'activated_at' => $account->activated_at ?? now(),
        ])->save();

        $this->signIn($request, $account);

        return redirect()->route('portal.dashboard')->with('success', 'Your password has been set.');
    }

    public static function setPasswordUrl(Company $company, string $token): string
    {
        return TenantDomain::url($company, route('portal.password.set', ['portal' => $company->client_portal_slug, 'token' => $token], false));
    }

    private function signIn(Request $request, ClientAccount $account): void
    {
        Auth::guard('client')->login($account);
        $request->session()->regenerate();
        $request->session()->put(ClientAccount::SESSION_FINGERPRINT, $account->sessionFingerprint());

        $account->forceFill(['last_login_at' => now()])->save();
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get(ResolveClientPortal::COMPANY);
    }
}
