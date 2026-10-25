<?php

namespace Database\Seeders;

use App\Models\Superadmin;
use Illuminate\Database\Seeder;

class SuperadminSeeder extends Seeder
{
    public const DEFAULT_EMAIL = 'admin@invoiceapp.com';
    public const DEFAULT_PASSWORD = 'password';

    /**
     * Creates the default superadmin only when none exists. The web installer creates the
     * superadmin from its form before seeding, and an account whose login was changed must
     * never be reset (or joined by a second default account) by a later db:seed.
     */
    public function run(): void
    {
        if (Superadmin::query()->exists()) {
            return;
        }

        Superadmin::create([
            'name' => 'Super Admin',
            'email' => self::DEFAULT_EMAIL,
            'password' => self::DEFAULT_PASSWORD,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command?->warn(sprintf(
            'Created the default superadmin (%s / %s). Sign in at /superadmin/login and change the email and password under "Your account" right away.',
            self::DEFAULT_EMAIL,
            self::DEFAULT_PASSWORD,
        ));
    }
}
