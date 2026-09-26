<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("system_setting_{$key}");
    }

    /**
     * Get multiple settings
     */
    public static function getMany(array $keys): array
    {
        $settings = [];
        foreach ($keys as $key => $default) {
            if (is_int($key)) {
                $settings[$default] = static::get($default);
            } else {
                $settings[$key] = static::get($key, $default);
            }
        }
        return $settings;
    }

    /**
     * Set multiple settings
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::set($key, $value);
        }
    }
}
