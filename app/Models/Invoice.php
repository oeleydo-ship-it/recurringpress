<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Support\TenantDomain;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'recurring_invoice_id',
        'created_by',
        'invoice_number',
        'reference',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'discount_type',
        'total',
        'paid_amount',
        'notes',
        'terms',
        'share_token',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('invoices.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function ($invoice) {
            if (!$invoice->share_token) {
                $invoice->share_token = Str::random(32);
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SENT => 'Sent',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withoutGlobalScope('company');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function isFromRecurring(): bool
    {
        return !is_null($this->recurring_invoice_id);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_SENT => 'bg-blue-100 text-blue-800',
            self::STATUS_PAID => 'bg-green-100 text-green-800',
            self::STATUS_PARTIAL => 'bg-yellow-100 text-yellow-800',
            self::STATUS_OVERDUE => 'bg-red-100 text-red-800',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-800',
            self::STATUS_REFUNDED => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getShareUrlAttribute(): string
    {
        // On the company's verified custom domain when it has one (also from queued jobs).
        return TenantDomain::url($this->company, route('invoices.public', $this->share_token, false));
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && !in_array($this->status, [self::STATUS_PAID, self::STATUS_CANCELLED, self::STATUS_REFUNDED]);
    }

    public function calculateTotals(): void
    {
        $this->loadMissing('items');

        $this->subtotal = $this->items->sum(fn($item) => $item->quantity * $item->price);
        $this->tax_amount = $this->items->sum(fn($item) => $item->quantity * $item->price * ($item->tax_rate / 100));
        
        $discount = 0;
        if ($this->discount_amount > 0) {
            $discount = $this->discount_type === 'percentage' 
                ? ($this->subtotal * $this->discount_amount / 100)
                : $this->discount_amount;
        }
        
        $this->total = max(0, $this->subtotal + $this->tax_amount - $discount);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function isPartiallyPaid(): bool
    {
        return $this->paid_amount > 0 && $this->paid_amount < $this->total;
    }

    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= $this->total;
    }

    public function addPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        
        if ($this->isFullyPaid()) {
            $this->status = self::STATUS_PAID;
            $this->paid_at = now();
        } elseif ($this->isPartiallyPaid()) {
            $this->status = self::STATUS_PARTIAL;
        }
        
        $this->save();
    }
}
