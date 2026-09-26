<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'max_invoices',
        'max_clients',
        'max_users',
        'max_products',
        'stripe_enabled',
        'custom_branding',
        'api_access',
        'features',
        'is_active',
        'is_popular',
        'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stripe_enabled' => 'boolean',
        'custom_branding' => 'boolean',
        'api_access' => 'boolean',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('price');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'subscription_plan_id');
    }

    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }
        return '$' . number_format($this->price, 2) . '/' . ($this->billing_cycle === 'yearly' ? 'year' : 'month');
    }
}
