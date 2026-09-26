<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Superadmin is the most valuable account: stricter lockout than tenant logins. */
    protected const MAX_ATTEMPTS = 5;
    protected const DECAY_SECONDS = 900;

    public function showLoginForm()
    {
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }
        return view('superadmin.auth.login');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => 'required|string|email:rfc,filter|max:255',
            'password' => 'required|string|max:255',
        ]);

        $throttleKey = 'superadmin-login-fail:' . Str::transliterate($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            event(new Lockout($request));

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . (int) ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minute(s).',
            ])->onlyInput('email');
        }

        $credentials['is_active'] = fn ($query) => $query->where('is_active', true)->orWhereNull('is_active');

        if (Auth::guard('superadmin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            Log::info('Superadmin login', ['email' => $request->input('email'), 'ip' => $request->ip()]);
            return redirect()->intended(route('superadmin.dashboard'));
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
        Log::warning('Failed superadmin login attempt', ['email' => $request->input('email'), 'ip' => $request->ip()]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('superadmin.login');
    }
}
