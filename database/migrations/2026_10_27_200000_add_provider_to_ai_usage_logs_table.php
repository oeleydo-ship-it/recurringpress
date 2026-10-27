<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            // anthropic | openai | deepseek | kimi (see App\Services\Ai\AiProviders)
            $table->string('provider', 20)->default('anthropic')->after('feature');
        });

        // Logs written before multiple providers were all Claude.
        DB::table('ai_usage_logs')->update(['provider' => 'anthropic']);
    }

    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
