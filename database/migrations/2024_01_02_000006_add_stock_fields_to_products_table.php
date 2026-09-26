<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['physical', 'digital', 'service'])->default('service')->after('is_active');
            $table->integer('stock_quantity')->nullable()->after('type');
            $table->integer('low_stock_threshold')->nullable()->after('stock_quantity');
            $table->boolean('track_stock')->default(false)->after('low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'stock_quantity', 'low_stock_threshold', 'track_stock']);
        });
    }
};
