<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class RecurringInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'created_by',
        'name',
        'billing_cycle',
        'billing_cycle_count',
        'start_date',
        'end_date',
        'next_invoice_date',
        'last_invoice_date',
        'invoices_generated',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'discount_type',
        'total',
        'notes',
        'terms',
        'status',
        'auto_send',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_invoice_date' => 'date',
        'last_invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'auto_send' => 'boolean',
        'invoices_generated' => 'integer',
        'billing_cycle_count' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    const CYCLE_WEEKLY = 'weekly';
    const CYCLE_MONTHLY = 'monthly';
    const CYCLE_QUARTERLY = 'quarterly';
    const CYCLE_SEMI_ANNUAL = 'semi_annual';
    const CYCLE_ANNUAL = 'annual';

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('recurring_invoices.company_id', auth()->user()->company_id);
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public static function billingCycles(): array
    {
        return [
            self::CYCLE_WEEKLY => 'Weekly',
            self::CYCLE_MONTHLY => 'Monthly',
            self::CYCLE_QUARTERLY => 'Quarterly (Every 3 months)',
            self::CYCLE_SEMI_ANNUAL => 'Semi-Annual (Every 6 months)',
            self::CYCLE_ANNUAL => 'Annual (Yearly)',
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
        return $this->hasMany(RecurringInvoiceItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'bg-green-100 text-green-800',
            self::STATUS_PAUSED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            self::STATUS_COMPLETED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
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
        
        $this->total = $this->subtotal + $this->tax_amount - $discount;
    }

    public function getNextBillingDate(): Carbon
    {
        $date = $this->next_invoice_date ?? Carbon::parse($this->start_date);
        
        return match($this->billing_cycle) {
            self::CYCLE_WEEKLY => $date->addWeek(),
            self::CYCLE_MONTHLY => $date->addMonth(),
            self::CYCLE_QUARTERLY => $date->addMonths(3),
            self::CYCLE_SEMI_ANNUAL => $date->addMonths(6),
            self::CYCLE_ANNUAL => $date->addYear(),
            default => $date->addMonth(),
        };
    }

    public function shouldGenerateInvoice(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->end_date && $this->next_invoice_date->gt($this->end_date)) {
            return false;
        }

        if ($this->billing_cycle_count && $this->invoices_generated >= $this->billing_cycle_count) {
            return false;
        }

        return $this->next_invoice_date->lte(now());
    }

    public function generateInvoice(): ?Invoice
    {
        if (!$this->shouldGenerateInvoice()) {
            return null;
        }

        $company = $this->company;
        $invoiceNumber = $company->getNextInvoiceNumber();

        $invoice = Invoice::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'created_by' => $this->created_by,
            'recurring_invoice_id' => $this->id,
            'invoice_number' => $invoiceNumber,
            'reference' => 'Recurring: ' . $this->name,
            'issue_date' => now(),
            'due_date' => now()->addDays($company->payment_terms ?? 30),
            'discount_amount' => $this->discount_amount,
            'discount_type' => $this->discount_type,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'status' => 'draft',
        ]);

        foreach ($this->items as $item) {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'tax_rate' => $item->tax_rate,
                'unit' => $item->unit,
                'sort_order' => $item->sort_order,
            ]);
        }

        $invoice->calculateTotals();
        $invoice->save();

        $this->last_invoice_date = now();
        $this->next_invoice_date = $this->getNextBillingDate();
        $this->invoices_generated++;

        if ($this->billing_cycle_count && $this->invoices_generated >= $this->billing_cycle_count) {
            $this->status = self::STATUS_COMPLETED;
        }

        if ($this->end_date && $this->next_invoice_date->gt($this->end_date)) {
            $this->status = self::STATUS_COMPLETED;
        }

        $this->save();

        if ($this->auto_send && $invoice->status === 'draft') {
            $invoice->markAsSent();
        }

        return $invoice;
    }

    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    public function resume(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
