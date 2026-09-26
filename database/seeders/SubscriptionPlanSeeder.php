<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free Tier',
                'description' => '10 Invoice Unlimited Customer',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'max_invoices' => 10,
                'max_clients' => null,
                'max_users' => 1,
                'max_products' => 10,
                'stripe_enabled' => false,
                'custom_branding' => false,
                'api_access' => false,
                'is_active' => true,
                'is_popular' => false,
                'order' => 1,
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Perfect for growing businesses',
                'price' => 19,
                'billing_cycle' => 'monthly',
                'max_invoices' => null,
                'max_clients' => null,
                'max_users' => 5,
                'max_products' => null,
                'stripe_enabled' => true,
                'custom_branding' => true,
                'api_access' => false,
                'is_active' => true,
                'is_popular' => true,
                'order' => 2,
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'For large teams and enterprises',
                'price' => 49,
                'billing_cycle' => 'monthly',
                'max_invoices' => null,
                'max_clients' => null,
                'max_users' => null,
                'max_products' => null,
                'stripe_enabled' => true,
                'custom_branding' => true,
                'api_access' => true,
                'is_active' => true,
                'is_popular' => false,
                'order' => 3,
            ]
        );
    }
}

