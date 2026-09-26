<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Essential System Data
        $this->call([
            SubscriptionPlanSeeder::class,
            SuperadminSeeder::class,
            CmsPagesSeeder::class,
            SystemSettingsSeeder::class,
        ]);

        // Demo data removed for production installation
        // The installer will create the primary admin user and company.
    }
}
