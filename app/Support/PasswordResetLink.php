<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Issues single-use password reset links (validated by PasswordResetController::reset).
 * Only a hash of the token is stored; any previous token for the email is replaced.
 */
class PasswordResetLink
{
    public static function for(User $user): string
    {
        $email = Str::lower($user->email);
        $token = Str::random(64);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        return route('password.reset', ['token' => $token, 'email' => $email]);
    }
}
