<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_posts', 'is_ai_generated')) {
                $table->boolean('is_ai_generated')->default(false)->after('is_published');
            }
            if (! Schema::hasColumn('cms_posts', 'target_keyword')) {
                $table->string('target_keyword')->nullable()->after('is_ai_generated');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            if (Schema::hasColumn('cms_posts', 'target_keyword')) {
                $table->dropColumn('target_keyword');
            }
            if (Schema::hasColumn('cms_posts', 'is_ai_generated')) {
                $table->dropColumn('is_ai_generated');
            }
        });
    }
};
