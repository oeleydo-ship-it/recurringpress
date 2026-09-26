<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Symfony\Component\HttpFoundation\Response;

class CheckEmailVerification
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks if email verification is required (via system settings)
     * and if so, ensures the user has verified their email address.
     */
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        // Check if email verification is enabled in system settings
        $emailVerificationEnabled = SystemSetting::get('email_verification_enabled', '0') === '1';

        // If email verification is disabled, allow all users through
        if (!$emailVerificationEnabled) {
            return $next($request);
        }

        // If user is not logged in or hasn't verified email, redirect to verification notice
        if (
            !$request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
                !$request->user()->hasVerifiedEmail())
        ) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : redirect()->route($redirectToRoute ?: 'verification.notice');
        }

        return $next($request);
    }
}
