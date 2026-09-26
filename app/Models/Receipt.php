<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Receipt extends Model
{
    protected $fillable = [
        'company_id',
        'payment_id',
        'invoice_id',
        'client_id',
        'receipt_number',
        'amount',
        'payment_method',
        'issued_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('receipts.company_id', auth()->user()->company_id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public static function generateReceiptNumber(int $companyId): string
    {
        $prefix = 'RCP-';
        
        $lastReceipt = self::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        $lastNumeric = 0;

        if ($lastReceipt && $lastReceipt->receipt_number) {
            $numberPart = str_starts_with($lastReceipt->receipt_number, $prefix)
                ? substr($lastReceipt->receipt_number, strlen($prefix))
                : $lastReceipt->receipt_number;

            if (preg_match('/(\d+)$/', (string) $numberPart, $matches)) {
                $lastNumeric = (int) $matches[1];
            }
        }

        $candidateNumber = $lastReceipt ? ($lastNumeric + 1) : 1;

        for ($attempt = 0; $attempt < 100000; $attempt++) {
            $candidate = $prefix . str_pad((string) $candidateNumber, 6, '0', STR_PAD_LEFT);

            $exists = self::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('receipt_number', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            $candidateNumber++;
        }

        throw new \RuntimeException('Unable to generate a unique receipt number.');
    }
}
