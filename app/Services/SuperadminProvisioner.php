<?php

namespace App\Services;

use App\Models\Superadmin;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first superadmin from SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD (config/superadmin.php).
 * Used by SuperadminSeeder and after `migrate`, so Git / auto deployments get a superadmin
 * without the web installer and without the well-known default login.
 */
class SuperadminProvisioner
{
    /**
     * @return array{status: 'created'|'exists'|'not_configured'|'invalid'|'no_table', message?: string}
     */
    public function ensureFromConfig(): array
    {
        if (!Schema::hasTable('superadmins')) {
            return ['status' => 'no_table'];
        }

        if (Superadmin::query()->exists()) {
            return ['status' => 'exists'];
        }

        // env() fallback: a config cache built before config/superadmin.php existed lacks these keys
        // on the first deploy; platform-dashboard variables are still real environment variables.
        $email = Str::lower(trim((string) (config('superadmin.email') ?? env('SUPERADMIN_EMAIL'))));
        $password = (string) (config('superadmin.password') ?? env('SUPERADMIN_PASSWORD'));

        if ($email === '' || $password === '') {
            return ['status' => 'not_configured'];
        }

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => 'required|email:rfc,filter|max:255',
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return ['status' => 'invalid', 'message' => implode(' ', $validator->errors()->all())];
        }

        Superadmin::create([
            'name' => config('superadmin.name') ?: 'Super Admin',
            'email' => $email,
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return ['status' => 'created', 'message' => "Created the superadmin {$email} from SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD."];
    }
}
