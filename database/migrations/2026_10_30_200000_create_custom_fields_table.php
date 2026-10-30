<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant-defined custom fields. Definitions live in custom_fields; each record keeps its
     * values in a JSON `custom_values` column ({key: value}) on its own table, so documents,
     * PDFs and copies (quotation → invoice, recurring → invoice) read one row with no joins.
     */
    private const TABLES = ['invoices', 'recurring_invoices', 'quotations', 'clients', 'products', 'expenses'];

    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity', 32);
            $table->string('label');
            $table->string('key', 64);
            $table->string('type', 16);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_pdf')->default(false);
            $table->boolean('show_in_list')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'entity', 'key']);
            $table->index(['company_id', 'entity', 'sort_order']);
        });

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->json('custom_values')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('custom_values');
            });
        }

        Schema::dropIfExists('custom_fields');
    }
};
