<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    protected const GENERIC_STATUS = 'If an account exists with this email, you will receive a password reset link.';

    /**
     * Show the forgot password form.
     */
    public function showForgotForm()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Send the password reset link.
     */
    public function sendResetLink(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $request->validate([
            'email' => 'required|string|email:rfc,filter|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        // Same response whether or not the account exists (no user enumeration).
        if (!$user || $user->is_active === false) {
            return back()->with('status', self::GENERIC_STATUS);
        }

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Generate token
        $token = Str::random(64);

        // Store token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Send email
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        try {
            Mail::to($user->email)->queue(new PasswordResetMail($user, $resetUrl));
        } catch (\Exception $e) {
            // Log the error but don't reveal it to user
            \Log::error('Password reset email failed: ' . $e->getMessage());
        }

        return back()->with('status', self::GENERIC_STATUS);
    }

    /**
     * Show the reset password form.
     */
    public function showResetForm(Request $request, $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the password.
     */
    public function reset(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $request->validate([
            'token' => 'required|string|max:255',
            'email' => 'required|string|email:rfc,filter|max:255',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        // A single generic error for every failure mode, so the form can't be used to probe accounts.
        $invalid = fn () => back()->withInput($request->only('email'))
            ->withErrors(['email' => 'This password reset link is invalid or has expired.']);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return $invalid();
        }

        // Check token expiration (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $invalid();
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $invalid();
        }

        // Rotating the remember token logs out every "remember me" session an attacker may hold.
        $user->forceFill([
            'password' => $request->password, // hashed by the model cast
            'remember_token' => Str::random(60),
        ])->save();

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Your password has been reset successfully. Please login with your new password.');
    }
}
