<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('date_format')->default('Y-m-d');
            $table->string('timezone')->default('UTC');
            $table->string('language', 10)->default('en');
            $table->boolean('tax_enabled')->default(true);
            $table->decimal('default_tax_rate', 5, 2)->default(0);
            $table->boolean('email_notifications')->default(true);
            $table->boolean('invoice_due_reminder')->default(true);
            $table->boolean('overdue_reminder')->default(true);
            $table->string('theme_color', 20)->default('#6366f1');
            $table->boolean('show_logo_on_invoice')->default(true);
            $table->boolean('show_payment_instructions')->default(true);
            $table->text('payment_instructions')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('paypal_email')->nullable();
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
