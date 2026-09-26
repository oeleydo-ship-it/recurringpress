<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'paypal', 'stripe', 'other'])->default('cash');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'partially_refunded'])->default('completed');
            $table->string('transaction_id')->nullable();
            $table->string('gateway_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'payment_date']);
            $table->index(['company_id', 'status']);
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
