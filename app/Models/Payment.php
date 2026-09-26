<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'company_id', 'invoice_id', 'client_id', 'payment_number', 'payment_method', 
        'transaction_id', 'stripe_payment_intent_id', 'gateway_response',
        'amount', 'currency', 'status', 'via_app_gateway', 'payout_id',
        'notes', 'payment_date', 'refunded_amount', 'refunded_at', 'refund_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'refunded_at' => 'datetime',
        'via_app_gateway' => 'boolean',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    const METHOD_MANUAL = 'manual';
    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_STRIPE = 'stripe';
    const METHOD_OTHER = 'other';

    protected static function booted(): void
    {
        static::addGlobalScope('company', function ($query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('payments.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withoutGlobalScope('company');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
            self::STATUS_REFUNDED => 'bg-gray-100 text-gray-800',
            self::STATUS_PARTIALLY_REFUNDED => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_MANUAL => 'Manual',
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CREDIT_CARD => 'Credit Card',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_OTHER => 'Other',
            default => ucfirst($this->payment_method ?? 'Unknown'),
        };
    }

    public static function paymentMethods(): array
    {
        return [
            self::METHOD_MANUAL => 'Manual',
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CREDIT_CARD => 'Credit Card',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_OTHER => 'Other',
        ];
    }

    public function canRefund(): bool
    {
        // Partially refunded payments can be refunded further, up to the original amount.
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_PARTIALLY_REFUNDED], true) &&
               $this->amount > $this->refunded_amount;
    }

    public function getRemainingRefundableAttribute(): float
    {
        return $this->amount - $this->refunded_amount;
    }
}
