<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_blog_runs', function (Blueprint $table) {
            $table->id();
            $table->string('slot')->nullable()->unique();
            $table->string('keyword');
            $table->string('status')->default('queued')->index();
            $table->json('options');
            $table->foreignId('post_id')->nullable()->constrained('cms_posts')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_blog_runs');
    }
};
