<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete duplicate pending/failed payments
        DB::statement("
            DELETE p1 FROM payments p1
            INNER JOIN payments p2 
            WHERE p1.id > p2.id 
            AND p1.payment_number = p2.payment_number 
            AND p1.company_id = p2.company_id
            AND p1.status IN ('pending', 'failed')
        ");

        // Drop the old unique constraint
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['payment_number']);
        });

        // Add a new unique constraint that includes company_id
        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['company_id', 'payment_number'], 'payments_company_payment_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the compound unique constraint
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_company_payment_number_unique');
        });

        // Restore the old unique constraint
        Schema::table('payments', function (Blueprint $table) {
            $table->unique('payment_number');
        });
    }
};
