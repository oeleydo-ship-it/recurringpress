<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            // Custom domain for client-facing invoice/quotation links (see App\Support\TenantDomain).
            $table->string('custom_domain')->nullable()->unique();
            $table->string('custom_domain_token', 64)->nullable();
            $table->timestamp('custom_domain_verified_at')->nullable();
            $table->timestamp('custom_domain_checked_at')->nullable();
            $table->string('custom_domain_error', 500)->nullable();

            // Tenant's own SMTP for client emails (see App\Services\TenantMailer).
            $table->boolean('smtp_enabled')->default(false);
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 10)->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable(); // encrypted cast
            $table->string('smtp_from_address')->nullable();
            $table->string('smtp_from_name')->nullable();
            $table->timestamp('smtp_verified_at')->nullable();
            $table->string('smtp_last_error', 1000)->nullable();
            $table->timestamp('smtp_last_error_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'custom_domain', 'custom_domain_token', 'custom_domain_verified_at', 'custom_domain_checked_at', 'custom_domain_error',
                'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
                'smtp_from_address', 'smtp_from_name', 'smtp_verified_at', 'smtp_last_error', 'smtp_last_error_at',
            ]);
        });
    }
};
