<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A plan can now be sold monthly, yearly and/or as a one-time lifetime purchase. `price` and
 * `billing_cycle` stay as the plan's default option (SubscriptionPlan keeps them in sync).
 * Current plans map onto the period they already bill; subscribers keep their plan and end date.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('price_monthly', 10, 2)->nullable()->after('price');
            $table->decimal('price_yearly', 10, 2)->nullable()->after('price_monthly');
            $table->decimal('price_lifetime', 10, 2)->nullable()->after('price_yearly');
            $table->boolean('monthly_enabled')->default(false)->after('price_lifetime');
            $table->boolean('yearly_enabled')->default(false)->after('monthly_enabled');
            $table->boolean('lifetime_enabled')->default(false)->after('yearly_enabled');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime'])->default('monthly')->change();
        });

        Schema::table('companies', function (Blueprint $table) {
            // Null on free plans, otherwise the period the company pays (or will pay after a trial).
            $table->enum('billing_period', ['monthly', 'yearly', 'lifetime'])->nullable()->after('subscription_status');
        });

        // One row per successful plan payment. The unique intent id makes activation idempotent,
        // since both the success redirect and the webhook report the same payment.
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->enum('billing_period', ['monthly', 'yearly', 'lifetime']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('usd');
            $table->string('stripe_payment_intent_id')->unique();
            $table->timestamps();
        });

        foreach (DB::table('subscription_plans')->get() as $plan) {
            if ((float) $plan->price > 0) {
                DB::table('subscription_plans')->where('id', $plan->id)->update([
                    "price_{$plan->billing_cycle}" => $plan->price,
                    "{$plan->billing_cycle}_enabled" => true,
                ]);

                DB::table('companies')->where('subscription_plan_id', $plan->id)->update(['billing_period' => $plan->billing_cycle]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('billing_period');
        });

        DB::table('subscription_plans')->where('billing_cycle', 'lifetime')->update(['billing_cycle' => 'monthly']);

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['price_monthly', 'price_yearly', 'price_lifetime', 'monthly_enabled', 'yearly_enabled', 'lifetime_enabled']);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly')->change();
        });
    }
};
