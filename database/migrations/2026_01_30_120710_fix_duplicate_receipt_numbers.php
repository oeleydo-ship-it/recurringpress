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
        // Delete duplicate receipts (keep the oldest one)
        DB::statement("
            DELETE r1 FROM receipts r1
            INNER JOIN receipts r2 
            WHERE r1.id > r2.id 
            AND r1.receipt_number = r2.receipt_number 
            AND r1.company_id = r2.company_id
        ");

        // Drop the old unique constraint
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
        });

        // Add a new unique constraint that includes company_id
        Schema::table('receipts', function (Blueprint $table) {
            $table->unique(['company_id', 'receipt_number'], 'receipts_company_receipt_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the compound unique constraint
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique('receipts_company_receipt_number_unique');
        });

        // Restore the old unique constraint
        Schema::table('receipts', function (Blueprint $table) {
            $table->unique('receipt_number');
        });
    }
};
