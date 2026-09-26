<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->enum('payment_gateway_mode', ['app', 'own'])->default('app')->after('stripe_enabled');
            $table->boolean('accept_bank_transfer')->default(true)->after('payment_gateway_mode');
            $table->boolean('accept_cash')->default(true)->after('accept_bank_transfer');
            $table->boolean('accept_stripe')->default(false)->after('accept_cash');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway_mode', 'accept_bank_transfer', 'accept_cash', 'accept_stripe']);
        });
    }
};
