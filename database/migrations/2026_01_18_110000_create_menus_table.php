<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('location')->unique(); // header, footer_product, footer_company, footer_legal
            $table->json('items')->nullable();
            $table->timestamps();
        });

        // Seed default menus
        DB::table('menus')->insert([
            [
                'location' => 'header',
                'items' => json_encode([
                    ['label' => 'Features', 'url' => '/features', 'type' => 'route', 'route' => 'landing.features'],
                    ['label' => 'Pricing', 'url' => '/pricing', 'type' => 'route', 'route' => 'landing.pricing'],
                    ['label' => 'About', 'url' => '/about', 'type' => 'route', 'route' => 'landing.about'],
                    ['label' => 'Contact', 'url' => '/contact', 'type' => 'route', 'route' => 'landing.contact'],
                    ['label' => 'Blog', 'url' => '/blog', 'type' => 'route', 'route' => 'blog.index'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location' => 'footer_product',
                'items' => json_encode([
                    ['label' => 'Features', 'url' => '/features', 'type' => 'route', 'route' => 'landing.features'],
                    ['label' => 'Pricing', 'url' => '/pricing', 'type' => 'route', 'route' => 'landing.pricing'],
                    ['label' => 'Templates', 'url' => '#', 'type' => 'url'],
                    ['label' => 'Integrations', 'url' => '#', 'type' => 'url'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location' => 'footer_company',
                'items' => json_encode([
                    ['label' => 'About', 'url' => '/about', 'type' => 'route', 'route' => 'landing.about'],
                    ['label' => 'Blog', 'url' => '/blog', 'type' => 'route', 'route' => 'blog.index'],
                    ['label' => 'Careers', 'url' => '#', 'type' => 'url'],
                    ['label' => 'Contact', 'url' => '/contact', 'type' => 'route', 'route' => 'landing.contact'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location' => 'footer_legal',
                'items' => json_encode([
                    ['label' => 'Privacy Policy', 'url' => '/page/privacy', 'type' => 'page', 'slug' => 'privacy'],
                    ['label' => 'Terms of Service', 'url' => '/page/terms', 'type' => 'page', 'slug' => 'terms'],
                    ['label' => 'Cookie Policy', 'url' => '#', 'type' => 'url'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
