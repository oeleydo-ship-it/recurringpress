<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Jobs\SendDocumentEmail;
use App\Services\TenantNotifier;
use Illuminate\Support\Facades\DB;

class RecurringInvoice extends Model
{
    use HasFactory;
    use \App\Models\Concerns\HasCustomValues;

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
        
        // NoOverflow: Jan 31 + 1 month is Feb 28/29, not Mar 3 (which would drift forever).
        $date = $date->copy();

        return match($this->billing_cycle) {
            self::CYCLE_WEEKLY => $date->addWeek(),
            self::CYCLE_MONTHLY => $date->addMonthNoOverflow(),
            self::CYCLE_QUARTERLY => $date->addMonthsNoOverflow(3),
            self::CYCLE_SEMI_ANNUAL => $date->addMonthsNoOverflow(6),
            self::CYCLE_ANNUAL => $date->addYearNoOverflow(),
            default => $date->addMonthNoOverflow(),
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

    /**
     * Generate the next invoice. Runs in a transaction with the schedule row locked, so a
     * failure part-way can't leave an invoice without its schedule advancing (which would
     * bill the client twice on the next run) and concurrent runs can't both generate.
     * With auto_send, the invoice is actually emailed (previously it was only marked sent).
     */
    public function generateInvoice(): ?Invoice
    {
        $invoice = DB::transaction(function () {
            $fresh = static::withoutGlobalScope('company')->whereKey($this->id)->lockForUpdate()->first();

            if (! $fresh) {
                return null;
            }

            // Carry over an in-memory next_invoice_date override (used by "generate now").
            if ($this->isDirty('next_invoice_date')) {
                $fresh->next_invoice_date = $this->next_invoice_date;
            }

            $invoice = $fresh->generateInvoiceLocked();
            $this->setRawAttributes($fresh->getAttributes(), true);

            return $invoice;
        });

        if ($invoice && $this->auto_send) {
            $invoice->loadMissing('client');
            if ($invoice->client?->email) {
                SendDocumentEmail::dispatch($invoice);
                $invoice->markAsSent();
            }
        }

        if ($invoice) {
            TenantNotifier::notify(
                $invoice->company_id,
                'recurring_generated',
                "Recurring invoice generated: {$invoice->invoice_number}",
                "From schedule “{$this->name}” — " . TenantNotifier::money($invoice->total, $this->company?->currency)
                    . ($this->auto_send ? ', emailed automatically.' : ', saved as a draft.'),
                TenantNotifier::path('invoices.show', $invoice->id),
            );
        }

        return $invoice;
    }

    private function generateInvoiceLocked(): ?Invoice
    {
        if (!$this->shouldGenerateInvoice()) {
            return null;
        }

        $company = $this->company;
        if ($company->hasReachedLimit('invoices')) {
            return null;
        }

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
            // Values carry over where the invoice defines a field with the same key.
            'custom_values' => \App\Support\CustomFields::carryOver($this->custom_values, 'invoice', $this->company_id),
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
