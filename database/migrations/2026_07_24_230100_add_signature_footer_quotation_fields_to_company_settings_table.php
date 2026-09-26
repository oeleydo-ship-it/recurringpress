<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->boolean('show_authorized_signature')->default(false);
            $table->boolean('show_invoice_footer')->default(true);
            $table->boolean('show_quotation_website')->default(false);
            $table->boolean('show_quotation_message')->default(true);
            $table->text('quotation_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_authorized_signature',
                'show_invoice_footer',
                'show_quotation_website',
                'show_quotation_message',
                'quotation_message',
            ]);
        });
    }
};
