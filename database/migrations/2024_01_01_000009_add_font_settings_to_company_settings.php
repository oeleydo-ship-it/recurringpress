<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('font_family')->default('Inter')->after('stripe_enabled');
            $table->string('font_size')->default('base')->after('font_family');
            $table->string('heading_color')->default('#111827')->after('font_size');
            $table->string('text_color')->default('#374151')->after('heading_color');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['font_family', 'font_size', 'heading_color', 'text_color']);
        });
    }
};
