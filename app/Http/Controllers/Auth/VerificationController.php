<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : Inertia::render('Auth/VerifyEmail');
    }

    public function verify(EmailVerificationRequest $request)
    {
        // The route's 'signed' middleware rejects tampered/expired links before we get here;
        // this is a defence-in-depth check. If valid links fail, APP_URL doesn't match the host
        // users visit (or a proxy isn't trusted) — fix that config rather than skipping the check.
        if (!$request->hasValidSignature()) {
            \Illuminate\Support\Facades\Log::warning('Email verification signature invalid', [
                'url' => $request->url(),
                'app_url_config' => config('app.url'),
                'user_id' => $request->user()?->id,
            ]);

            abort(403, 'This verification link is invalid or has expired.');
        }

        $request->fulfill();

        return redirect()->route('onboarding.welcome')->with('verified', true);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
