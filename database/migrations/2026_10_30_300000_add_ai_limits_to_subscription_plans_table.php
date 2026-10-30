<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-plan AI access. Existing plans keep AI on with the platform-wide quota
     * (max_ai_requests null = use Superadmin → AI's "requests per tenant per month").
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(true)->after('api_access');
            $table->unsignedInteger('max_ai_requests')->nullable()->after('ai_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['ai_enabled', 'max_ai_requests']);
        });
    }
};
