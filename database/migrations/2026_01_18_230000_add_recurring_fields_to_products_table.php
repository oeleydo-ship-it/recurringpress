<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('is_active');
            $table->enum('billing_cycle', ['weekly', 'monthly', 'quarterly', 'semi_annual', 'annual'])->nullable()->after('is_recurring');
            $table->integer('billing_cycle_count')->nullable()->after('billing_cycle');
            $table->boolean('has_trial')->default(false)->after('billing_cycle_count');
            $table->integer('trial_days')->nullable()->after('has_trial');
            $table->boolean('auto_renew')->default(true)->after('trial_days');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_recurring',
                'billing_cycle',
                'billing_cycle_count',
                'has_trial',
                'trial_days',
                'auto_renew',
            ]);
        });
    }
};
