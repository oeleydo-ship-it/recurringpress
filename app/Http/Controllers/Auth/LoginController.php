<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /** Failed attempts allowed per email+IP before a lockout. */
    protected const MAX_ATTEMPTS = 5;

    /** Lockout window in seconds. */
    protected const DECAY_SECONDS = 300;

    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc,filter', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :minutes minute(s).', [
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        // Deactivated users are rejected without revealing whether the password was right.
        $credentials['is_active'] = fn ($query) => $query->where('is_active', true)->orWhereNull('is_active');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            Log::notice('Failed login attempt', ['email' => $request->input('email'), 'ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    protected function throttleKey(Request $request): string
    {
        return 'login-fail:' . Str::transliterate($request->input('email')) . '|' . $request->ip();
    }
}
