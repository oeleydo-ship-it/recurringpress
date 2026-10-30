<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\TenantDomain;
use Illuminate\Support\Str;

class Quotation extends Model
{
    use \App\Models\Concerns\HasCustomValues;

    protected $fillable = [
        'company_id', 'client_id', 'user_id', 'quotation_number', 'reference',
        'issue_date', 'valid_until', 'status', 'subtotal', 'tax_amount',
        'discount_amount', 'discount_type', 'total', 'notes', 'terms',
        'share_token', 'converted_invoice_id', 'responded_at', 'response_notes', 'response_ip',
        'sent_at', 'viewed_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'responded_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CONVERTED = 'converted';

    protected static function booted(): void
    {
        static::addGlobalScope('company', function ($query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('quotations.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function ($quotation) {
            if (!$quotation->share_token) {
                $quotation->share_token = Str::random(64);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Draft</span>',
            self::STATUS_SENT => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Sent</span>',
            self::STATUS_ACCEPTED => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Accepted</span>',
            self::STATUS_REJECTED => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Rejected</span>',
            self::STATUS_EXPIRED => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Expired</span>',
            self::STATUS_CONVERTED => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Converted</span>',
            default => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Unknown</span>',
        };
    }

    public function getShareUrlAttribute(): string
    {
        return TenantDomain::url($this->company, route('quotations.public', $this->share_token, false));
    }

    public function isExpired(): bool
    {
        // A quotation is still valid for the whole of its valid_until day, so compare
        // dates rather than timestamps (the date cast puts valid_until at 00:00:00).
        return $this->valid_until->copy()->startOfDay()->lt(now()->startOfDay())
            && !in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_CONVERTED]);
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->valid_until->copy()->startOfDay(), false);
    }

    public function canConvert(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_ACCEPTED]) && !$this->converted_invoice_id;
    }

    /**
     * Which statuses each status may move to. `converted` is terminal: an invoice
     * already exists against it, so letting it move would leave converted_invoice_id
     * pointing at an invoice while the quotation claims it was never converted.
     */
    public static function allowedTransitions(): array
    {
        return [
            self::STATUS_DRAFT => [self::STATUS_SENT, self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_EXPIRED],
            self::STATUS_SENT => [self::STATUS_SENT, self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_EXPIRED],
            self::STATUS_EXPIRED => [self::STATUS_SENT, self::STATUS_ACCEPTED, self::STATUS_REJECTED],
            self::STATUS_ACCEPTED => [self::STATUS_REJECTED, self::STATUS_CONVERTED],
            self::STATUS_REJECTED => [self::STATUS_ACCEPTED],
            self::STATUS_CONVERTED => [],
        ];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, static::allowedTransitions()[$this->status] ?? [], true);
    }

    public function isEditable(): bool
    {
        return $this->status !== self::STATUS_CONVERTED;
    }

    public function calculateTotals(): void
    {
        $this->loadMissing('items');

        $this->subtotal = $this->items->sum(fn($item) => $item->quantity * $item->price);
        $this->tax_amount = $this->items->sum(fn($item) => ($item->quantity * $item->price) * ($item->tax_rate / 100));

        $discount = $this->discount_type === 'percentage'
            ? $this->subtotal * ($this->discount_amount / 100)
            : $this->discount_amount;

        $this->total = max(0, $this->subtotal + $this->tax_amount - $discount);
        $this->save();
    }
}
