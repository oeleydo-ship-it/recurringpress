<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->decimal('gross_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('fee_percentage', 5, 2)->default(0)->after('gross_amount');
            $table->decimal('fee_amount', 12, 2)->default(0)->after('fee_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'fee_percentage', 'fee_amount']);
        });
    }
};
