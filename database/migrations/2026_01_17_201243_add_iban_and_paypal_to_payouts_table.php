<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('bank_iban')->nullable()->after('bank_routing_number');
            $table->string('bank_swift')->nullable()->after('bank_iban');
            $table->string('paypal_email')->nullable()->after('bank_swift');
            $table->text('admin_notes')->nullable()->after('notes');
            $table->foreignId('processed_by')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['bank_iban', 'bank_swift', 'paypal_email', 'admin_notes', 'processed_by']);
        });
    }
};
