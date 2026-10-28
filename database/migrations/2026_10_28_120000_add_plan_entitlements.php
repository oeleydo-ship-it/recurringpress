<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('white_label')->default(false);
            $table->boolean('custom_domain')->default(false);
            $table->boolean('subdomain')->default(false);
        });

        // Existing branding plans keep the branded-domain experience after upgrading.
        DB::table('subscription_plans')->where('custom_branding', true)->update([
            'white_label' => true,
            'custom_domain' => true,
            'subdomain' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('subscription_plans', fn (Blueprint $table) => $table->dropColumn(['white_label', 'custom_domain', 'subdomain']));
    }
};
