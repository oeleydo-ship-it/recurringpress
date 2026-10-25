<?php

namespace Database\Seeders;

use App\Models\Superadmin;
use App\Services\SuperadminProvisioner;
use Illuminate\Database\Seeder;

class SuperadminSeeder extends Seeder
{
    public const DEFAULT_EMAIL = 'admin@invoiceapp.com';
    public const DEFAULT_PASSWORD = 'password';

    /**
     * Creates the superadmin only when none exists. The web installer creates the superadmin
     * from its form before seeding, and an account whose login was changed must never be
     * reset (or joined by a second default account) by a later db:seed.
     *
     * With SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD set, that login is used; otherwise the
     * well-known default is created and a warning printed.
     */
    public function run(): void
    {
        $result = app(SuperadminProvisioner::class)->ensureFromConfig();

        if ($result['status'] === 'created') {
            $this->command?->info($result['message']);

            return;
        }

        if ($result['status'] === 'invalid') {
            // Don't fall back to the default login when a custom one was intended.
            $this->command?->error('SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD rejected, no superadmin created: ' . $result['message']);

            return;
        }

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
            'Created the default superadmin (%s / %s). Sign in at /superadmin/login and change the email and password under "Your account" right away, or set SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD before seeding.',
            self::DEFAULT_EMAIL,
            self::DEFAULT_PASSWORD,
        ));
    }
}
