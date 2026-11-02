<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    public const MONTHLY = 'monthly';
    public const YEARLY = 'yearly';
    public const LIFETIME = 'lifetime';
    public const PERIODS = [self::MONTHLY, self::YEARLY, self::LIFETIME];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'price_monthly',
        'price_yearly',
        'price_lifetime',
        'monthly_enabled',
        'yearly_enabled',
        'lifetime_enabled',
        'billing_cycle',
        'trial_days',
        'max_invoices',
        'max_clients',
        'max_users',
        'max_products',
        'stripe_enabled',
        'custom_branding',
        'white_label',
        'custom_domain',
        'subdomain',
        'api_access',
        'ai_enabled',
        'max_ai_requests',
        'client_portal',
        'features',
        'is_active',
        'is_popular',
        'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'price_lifetime' => 'decimal:2',
        'monthly_enabled' => 'boolean',
        'yearly_enabled' => 'boolean',
        'lifetime_enabled' => 'boolean',
        'trial_days' => 'integer',
        'stripe_enabled' => 'boolean',
        'custom_branding' => 'boolean',
        'white_label' => 'boolean',
        'custom_domain' => 'boolean',
        'subdomain' => 'boolean',
        'api_access' => 'boolean',
        'ai_enabled' => 'boolean',
        'max_ai_requests' => 'integer',
        'client_portal' => 'boolean',
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

        // `price` and `billing_cycle` always describe the plan's default option, for sorting and
        // anywhere that only needs one headline price. A plan offering no option is free.
        static::saving(function ($plan) {
            $default = $plan->defaultPeriod();
            $plan->price = $default ? $plan->priceFor($default) : 0;
            $plan->billing_cycle = $default ?? self::MONTHLY;
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

    public function offersPeriod(mixed $period): bool
    {
        return in_array($period, self::PERIODS, true) && $this->{"{$period}_enabled"} && $this->priceFor($period) > 0;
    }

    /** The period's stored price, kept while the option is switched off so current subscribers still see what they pay. */
    public function priceFor(?string $period): float
    {
        return in_array($period, self::PERIODS, true) ? (float) $this->{"price_{$period}"} : 0.0;
    }

    public function defaultPeriod(): ?string
    {
        foreach (self::PERIODS as $period) {
            if ($this->offersPeriod($period)) {
                return $period;
            }
        }

        return null;
    }

    /** Offered options only, as period => price. */
    public function prices(): array
    {
        $prices = [];
        foreach (self::PERIODS as $period) {
            if ($this->offersPeriod($period)) {
                $prices[$period] = $this->priceFor($period);
            }
        }

        return $prices;
    }

    /** Percent saved by paying yearly instead of twelve monthly payments; null when there is no saving. */
    public function yearlySavingPercent(): ?int
    {
        if (! $this->offersPeriod(self::MONTHLY) || ! $this->offersPeriod(self::YEARLY)) {
            return null;
        }

        $twelveMonths = $this->priceFor(self::MONTHLY) * 12;
        $saving = (int) round(($twelveMonths - $this->priceFor(self::YEARLY)) / $twelveMonths * 100);

        return $saving > 0 ? $saving : null;
    }

    /** Shared by every page that shows the plan's billing options. */
    public function pricingPayload(): array
    {
        return [
            'price' => (float) $this->price,
            'billing_cycle' => $this->billing_cycle,
            'prices' => (object) $this->prices(),
            'yearly_saving_percent' => $this->yearlySavingPercent(),
        ];
    }

    /** When a term bought now (or added to an existing term) ends. Lifetime never does. */
    public static function termEnd(string $period, \DateTimeInterface $from): ?Carbon
    {
        return match ($period) {
            self::YEARLY => Carbon::instance($from)->addYearNoOverflow(),
            self::MONTHLY => Carbon::instance($from)->addMonthNoOverflow(),
            default => null,
        };
    }

    public function initialSubscription(?string $period = null): array
    {
        if ($this->isFree()) {
            return ['subscription_status' => 'active', 'subscription_ends_at' => null, 'billing_period' => null];
        }
        $days = max(0, (int) $this->trial_days);

        return [
            'subscription_status' => $days > 0 ? 'trial' : 'expired',
            'subscription_ends_at' => now()->addDays($days),
            'billing_period' => $this->offersPeriod($period) ? $period : $this->defaultPeriod(),
        ];
    }

    public function isFree(): bool
    {
        return $this->defaultPeriod() === null;
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->formattedPriceFor($this->billing_cycle);
    }

    public function formattedPriceFor(?string $period): string
    {
        $price = $this->priceFor($period);

        if ($price <= 0) {
            return 'Free';
        }

        return '$' . number_format($price, 2) . match ($period) {
            self::YEARLY => '/year',
            self::LIFETIME => ' one-time',
            default => '/month',
        };
    }

    /** Every offered option on one line, for admin tables. */
    public function priceSummary(): string
    {
        return collect($this->prices())->keys()->map(fn ($period) => $this->formattedPriceFor($period))->join(' · ') ?: 'Free';
    }
}
