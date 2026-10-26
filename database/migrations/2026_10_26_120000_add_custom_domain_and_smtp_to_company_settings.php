<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            // Platform subdomain slug: <subdomain>.<platform domain> (see App\Support\TenantDomain).
            $table->string('subdomain', 63)->nullable()->unique();

            // Custom domain, provisioned as a Cloudflare for SaaS custom hostname
            // (see App\Services\CloudflareCustomHostnames).
            $table->string('custom_domain')->nullable()->unique();
            $table->string('cf_hostname_id', 64)->nullable();
            $table->string('cf_hostname_status', 50)->nullable();
            $table->string('cf_ssl_status', 50)->nullable();
            $table->json('cf_dns_records')->nullable();
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
            $table->dropUnique(['subdomain']);
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'subdomain',
                'custom_domain', 'cf_hostname_id', 'cf_hostname_status', 'cf_ssl_status', 'cf_dns_records',
                'custom_domain_verified_at', 'custom_domain_checked_at', 'custom_domain_error',
                'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
                'smtp_from_address', 'smtp_from_name', 'smtp_verified_at', 'smtp_last_error', 'smtp_last_error_at',
            ]);
        });
    }
};
