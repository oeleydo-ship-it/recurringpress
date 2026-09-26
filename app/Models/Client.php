<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'client_type',
        'company_name',
        'email',
        'phone',
        'tax_number',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_COMPANY = 'company';

    public function isCompany(): bool
    {
        return $this->client_type === self::TYPE_COMPANY;
    }

    public function getSelectLabelAttribute(): string
    {
        if ($this->isCompany()) {
            return $this->company_name
                ? "{$this->name} – {$this->company_name} (Company)"
                : "{$this->name} (Company)";
        }

        return "{$this->name} (Individual)";
    }

    /**
     * The primary bold line on invoice/quotation "Bill To" blocks: the company
     * name for company clients, or the person's name for individual clients.
     */
    public function getBillToNameAttribute(): string
    {
        return ($this->isCompany() && $this->company_name) ? $this->company_name : $this->name;
    }

    /**
     * The secondary contact-person line shown under the company name on
     * "Bill To" blocks. Null for individual clients (name is already the bill-to name).
     */
    public function getBillToContactAttribute(): ?string
    {
        return ($this->isCompany() && $this->company_name) ? $this->name : null;
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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getTotalBilledAttribute(): float
    {
        return $this->invoices()->sum('total');
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->invoices()->where('status', 'paid')->sum('total');
    }

    public function getTotalOutstandingAttribute(): float
    {
        return $this->invoices()->whereIn('status', ['sent', 'overdue'])->sum('total');
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        return implode(', ', $parts);
    }
}
