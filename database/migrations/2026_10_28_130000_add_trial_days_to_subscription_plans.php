<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', fn (Blueprint $table) => $table->unsignedInteger('trial_days')->default(14));
        DB::table('subscription_plans')->where('price', '<=', 0)->update(['trial_days' => 0]);
    }

    public function down(): void
    {
        Schema::table('subscription_plans', fn (Blueprint $table) => $table->dropColumn('trial_days'));
    }
};
