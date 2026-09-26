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
        Schema::table('quotations', function (Blueprint $table) {
            // Drop the existing global unique key
            $table->dropUnique('quotations_quotation_number_unique');

            // Add new composite unique key
            $table->unique(['company_id', 'quotation_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // Drop the composite key
            $table->dropUnique(['company_id', 'quotation_number']);

            // Restore the global unique key
            $table->unique('quotation_number');
        });
    }
};
