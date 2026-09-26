<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'user_id',
        'expense_number',
        'title',
        'category',
        'description',
        'amount',
        'tax_amount',
        'currency',
        'vendor',
        'payment_method',
        'reference',
        'receipt_path',
        'is_billable',
        'is_reimbursed',
        'expense_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_reimbursed' => 'boolean',
        'expense_date' => 'date',
    ];

    public static function categories(): array
    {
        return [
            'office_supplies' => 'Office Supplies',
            'software' => 'Software & Subscriptions',
            'travel' => 'Travel',
            'meals' => 'Meals & Entertainment',
            'utilities' => 'Utilities',
            'rent' => 'Rent',
            'marketing' => 'Marketing',
            'professional_services' => 'Professional Services',
            'equipment' => 'Equipment',
            'salaries' => 'Salaries & Wages',
            'taxes' => 'Taxes',
            'bank_fees' => 'Bank Fees',
            'shipping' => 'Shipping',
            'other' => 'Other',
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'paypal' => 'PayPal',
            'check' => 'Check',
            'other' => 'Other',
        ];
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->amount + (float) $this->tax_amount;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('expenses.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
