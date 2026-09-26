<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->timestamp('responded_at')->nullable()->after('status');
            $table->text('response_notes')->nullable()->after('responded_at');
            $table->string('response_ip')->nullable()->after('response_notes');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['responded_at', 'response_notes', 'response_ip']);
        });
    }
};
