<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    protected $fillable = [
        'company_id',
        'payout_number',
        'amount',
        'gross_amount',
        'fee_percentage',
        'fee_amount',
        'currency',
        'status',
        'payout_method',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_routing_number',
        'bank_iban',
        'bank_swift',
        'paypal_email',
        'transaction_id',
        'notes',
        'admin_notes',
        'requested_at',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::addGlobalScope('company', function ($query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function generatePayoutNumber($companyId): string
    {
        // Use global count to ensure uniqueness across all companies
        $count = static::withoutGlobalScopes()->count() + 1;
        $number = 'PO-' . str_pad($count, 6, '0', STR_PAD_LEFT);

        // Handle race conditions - ensure the number is truly unique
        while (static::withoutGlobalScopes()->where('payout_number', $number)->exists()) {
            $count++;
            $number = 'PO-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        }

        return $number;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
