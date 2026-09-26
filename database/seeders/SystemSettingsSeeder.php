<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Set initial application version
        if (!SystemSetting::where('key', 'app_version')->exists()) {
            SystemSetting::set('app_version', config('installer.version', '1.0.0'));
        }

        // Set default site name if not exists
        if (!SystemSetting::where('key', 'site_name')->exists()) {
            SystemSetting::set('site_name', 'RecurringPress');
        }

        // Set email verification disabled by default
        if (!SystemSetting::where('key', 'email_verification_enabled')->exists()) {
            SystemSetting::set('email_verification_enabled', '0');
        }
    }
}
