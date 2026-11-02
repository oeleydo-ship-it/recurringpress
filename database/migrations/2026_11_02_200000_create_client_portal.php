<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Client portal: a plan feature (off for existing plans until switched on in Superadmin → Plans),
     * a company-wide switch and one login per invited client. Client logins live in their own table,
     * never alongside team users or on the clients row that tenant pages serialize.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('client_portal')->default(false)->after('max_ai_requests');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('client_portal_enabled')->default(false);
            $table->string('client_portal_slug', 64)->nullable()->unique();
        });

        Schema::create('client_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->boolean('is_enabled')->default(true);
            $table->string('setup_token_hash', 64)->nullable()->unique();
            $table->timestamp('setup_token_expires_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_accounts');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['client_portal_slug']);
            $table->dropColumn(['client_portal_enabled', 'client_portal_slug']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('client_portal');
        });
    }
};
