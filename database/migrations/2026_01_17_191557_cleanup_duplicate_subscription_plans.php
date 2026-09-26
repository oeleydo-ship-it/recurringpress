<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPlan;
use App\Models\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find the correct free plan (the one with slug 'free' and correct limits)
        $freePlan = SubscriptionPlan::where('slug', 'free')->orderBy('id', 'desc')->first();
        
        if ($freePlan) {
            // Update the free plan with correct limits
            $freePlan->update([
                'name' => 'Free Tier',
                'max_invoices' => 10,
                'max_clients' => null,
                'max_users' => 1,
                'max_products' => 10,
            ]);
            
            // Delete any duplicate free plans (keep only the one we just updated)
            SubscriptionPlan::where('slug', 'free')
                ->where('id', '!=', $freePlan->id)
                ->delete();
            
            // Update all companies without a plan to use the free plan
            Company::whereNull('subscription_plan_id')
                ->update([
                    'subscription_plan_id' => $freePlan->id,
                    'subscription_status' => 'active',
                ]);
            
            // Also update companies on free plan to have 'active' status instead of 'trial'
            Company::where('subscription_plan_id', $freePlan->id)
                ->where('subscription_status', 'trial')
                ->update(['subscription_status' => 'active']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
