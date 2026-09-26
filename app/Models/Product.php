<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'price',
        'unit',
        'tax_rate',
        'sku',
        'is_active',
        'type',
        'stock_quantity',
        'low_stock_threshold',
        'track_stock',
        'is_recurring',
        'billing_cycle',
        'billing_cycle_count',
        'has_trial',
        'trial_days',
        'auto_renew',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_recurring' => 'boolean',
        'has_trial' => 'boolean',
        'auto_renew' => 'boolean',
        'trial_days' => 'integer',
        'billing_cycle_count' => 'integer',
    ];

    const TYPE_PHYSICAL = 'physical';
    const TYPE_DIGITAL = 'digital';
    const TYPE_SERVICE = 'service';

    public static function productTypes(): array
    {
        return [
            self::TYPE_SERVICE => 'Service',
            self::TYPE_PHYSICAL => 'Physical Product',
            self::TYPE_DIGITAL => 'Digital Product',
        ];
    }

    public function isPhysical(): bool
    {
        return $this->type === self::TYPE_PHYSICAL;
    }

    public function isLowStock(): bool
    {
        if (!$this->track_stock || !$this->isPhysical()) {
            return false;
        }
        return $this->stock_quantity <= ($this->low_stock_threshold ?? 0);
    }

    public function isOutOfStock(): bool
    {
        if (!$this->track_stock || !$this->isPhysical()) {
            return false;
        }
        return $this->stock_quantity <= 0;
    }

    public function decrementStock(int $quantity): bool
    {
        if (!$this->track_stock || !$this->isPhysical()) {
            return true;
        }
        if ($this->stock_quantity >= $quantity) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }
        return false;
    }

    public function incrementStock(int $quantity): void
    {
        if ($this->track_stock && $this->isPhysical()) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getPriceWithTaxAttribute(): float
    {
        return $this->price * (1 + ($this->tax_rate / 100));
    }

    public static function units(): array
    {
        return [
            'unit' => 'Unit',
            'hour' => 'Hour',
            'day' => 'Day',
            'week' => 'Week',
            'month' => 'Month',
            'project' => 'Project',
            'piece' => 'Piece',
            'kg' => 'Kilogram',
            'liter' => 'Liter',
            'meter' => 'Meter',
        ];
    }

    public static function billingCycles(): array
    {
        return [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly (Every 3 months)',
            'semi_annual' => 'Semi-Annual (Every 6 months)',
            'annual' => 'Annual (Yearly)',
        ];
    }

    public function isRecurring(): bool
    {
        return $this->is_recurring;
    }

    public function getBillingCycleLabelAttribute(): ?string
    {
        if (!$this->billing_cycle) {
            return null;
        }
        return self::billingCycles()[$this->billing_cycle] ?? $this->billing_cycle;
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class);
    }
}
