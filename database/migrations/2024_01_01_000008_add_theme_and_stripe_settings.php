<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#4f46e5')->after('theme_color');
            $table->string('secondary_color', 7)->default('#6366f1')->after('primary_color');
            $table->string('sidebar_color', 7)->default('#1f2937')->after('secondary_color');
            $table->string('sidebar_text_color', 7)->default('#ffffff')->after('sidebar_color');
            $table->string('invoice_template', 50)->default('classic')->after('show_logo_on_invoice');
            $table->string('stripe_public_key')->nullable()->after('paypal_email');
            $table->string('stripe_secret_key')->nullable()->after('stripe_public_key');
            $table->string('stripe_webhook_secret')->nullable()->after('stripe_secret_key');
            $table->boolean('stripe_enabled')->default(false)->after('stripe_webhook_secret');
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('quotation_number')->unique();
            $table->string('reference')->nullable();
            $table->date('issue_date');
            $table->date('valid_until');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('share_token', 64)->unique()->nullable();
            $table->foreignId('converted_invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->default('unit');
            $table->decimal('price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->timestamps();
        });

        // Payments table creation moved to 2024_01_02_000007_create_payments_table.php
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'primary_color',
                'secondary_color',
                'sidebar_color',
                'sidebar_text_color',
                'invoice_template',
                'stripe_public_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
                'stripe_enabled'
            ]);
        });
    }
};
