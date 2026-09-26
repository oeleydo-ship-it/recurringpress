<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('id')->constrained('subscription_plans')->nullOnDelete();
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->enum('subscription_status', ['active', 'trial', 'cancelled', 'expired'])->default('trial');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn(['subscription_plan_id', 'subscription_started_at', 'subscription_ends_at', 'subscription_status']);
        });
    }
};
