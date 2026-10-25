<?php

namespace App\Console\Commands;

use App\Models\Superadmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateSuperadmin extends Command
{
    protected $signature = 'superadmin:create
        {--email= : Sign-in email}
        {--name= : Display name (default: Super Admin)}
        {--password= : Password (prompted, hidden, when left out)}
        {--force : Reset the password of an existing superadmin with this email without asking}';

    protected $description = 'Create a superadmin, or reset the password of an existing one';

    public function handle(): int
    {
        $email = Str::lower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        $existing = Superadmin::where('email', $email)->first();

        if ($existing && !$this->option('force') && !$this->confirm("A superadmin with {$email} exists. Reset its password?")) {
            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        if ($password === '') {
            $password = (string) $this->secret('Password');
            if ($password !== (string) $this->secret('Confirm password')) {
                $this->error('The passwords do not match.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => 'required|email:rfc,filter|max:255',
            'password' => ['required', Password::defaults()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $admin = $existing ?? new Superadmin([
            'name' => $this->option('name') ?: 'Super Admin',
            'email' => $email,
        ]);
        if ($existing && $this->option('name')) {
            $admin->name = $this->option('name');
        }

        // Rotating the remember token signs out "remember me" sessions.
        $admin->forceFill([
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'remember_token' => Str::random(60),
        ])->save();

        $this->info($existing ? "Password reset for superadmin {$email}." : "Created superadmin {$email}.");
        $this->line('Sign in at ' . rtrim((string) config('app.url'), '/') . '/superadmin/login');

        return self::SUCCESS;
    }
}
