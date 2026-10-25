<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * The signed-in superadmin's own login: name, email and password.
 */
class AccountController extends Controller
{
    public function edit()
    {
        $admin = Auth::guard('superadmin')->user();

        return Inertia::render('Superadmin/Account', [
            'account' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar_url' => $admin->avatar_url,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $admin = Auth::guard('superadmin')->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $emailChanging = $request->input('email') !== Str::lower($admin->email);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc,filter', 'max:255', Rule::unique('superadmins', 'email')->ignore($admin->id)],
            // Changing the login email needs the password, so a hijacked session can't take over the account.
            'current_password' => [Rule::requiredIf($emailChanging), 'nullable', 'current_password:superadmin'],
        ]);
        unset($validated['current_password']);

        $admin->update($validated);

        if ($emailChanging) {
            Log::info('Superadmin email changed', ['id' => $admin->id, 'ip' => $request->ip()]);
        }

        return back()->with('success', 'Account updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password:superadmin',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $admin = Auth::guard('superadmin')->user();
        // Rotating the remember token signs out "remember me" sessions on other devices.
        $admin->forceFill([
            'password' => $request->password,
            'remember_token' => Str::random(60),
        ])->save();

        Auth::guard('superadmin')->login($admin); // refresh this session's password hash
        $request->session()->regenerate();

        Log::info('Superadmin password changed', ['id' => $admin->id, 'ip' => $request->ip()]);

        return back()->with('success', 'Password changed.');
    }
}
