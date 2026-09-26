<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    protected $fillable = ['location', 'items'];

    protected $casts = [
        'items' => 'array',
    ];

    public static function getItems(string $location): array
    {
        return Cache::remember("menu_{$location}", 3600, function () use ($location) {
            $menu = self::where('location', $location)->first();
            return $menu ? ($menu->items ?? []) : [];
        });
    }

    public static function clearCache(string $location = null): void
    {
        if ($location) {
            Cache::forget("menu_{$location}");
        } else {
            Cache::forget('menu_header');
            Cache::forget('menu_footer_product');
            Cache::forget('menu_footer_company');
            Cache::forget('menu_footer_legal');
        }
    }
}
