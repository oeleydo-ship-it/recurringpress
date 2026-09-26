<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('expense_number')->nullable();
            $table->string('title');
            $table->string('category')->default('other');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('vendor')->nullable();
            $table->string('payment_method')->default('cash');
            $table->string('reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->boolean('is_reimbursed')->default(false);
            $table->date('expense_date');
            $table->timestamps();

            $table->index(['company_id', 'expense_date']);
            $table->index(['company_id', 'category']);
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
