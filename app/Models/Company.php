<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'website',
        'tax_number',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'logo',
        'authorized_signature',
        'currency',
        'invoice_prefix',
        'invoice_start_number',
        'invoice_footer',
        'payment_terms',
        'quotation_prefix',
        'quotation_validity_days',
        'is_active',
        'subscription_plan_id',
        'subscription_started_at',
        'subscription_ends_at',
        'subscription_status',
        'onboarding_completed',
        'onboarding_step',
    ];

    protected $attributes = [
        'quotation_prefix' => 'QT-',
        'quotation_validity_days' => 30,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'invoice_start_number' => 'integer',
        'payment_terms' => 'integer',
        'quotation_validity_days' => 'integer',
        'subscription_started_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'onboarding_completed' => 'boolean',
        'onboarding_step' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function getNextInvoiceNumber(): string
    {
        $prefix = $this->invoice_prefix ?? 'INV-';
        $startingNumber = $this->invoice_start_number ?? 1;

        return DB::transaction(function () use ($prefix, $startingNumber) {
            DB::table('companies')->where('id', $this->id)->lockForUpdate()->first();

            $lastInvoice = Invoice::withoutGlobalScopes()
                ->where('company_id', $this->id)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $lastNumeric = 0;

            if ($lastInvoice && $lastInvoice->invoice_number) {
                $numberPart = str_starts_with($lastInvoice->invoice_number, $prefix)
                    ? substr($lastInvoice->invoice_number, strlen($prefix))
                    : $lastInvoice->invoice_number;

                if (preg_match('/(\d+)$/', (string) $numberPart, $matches)) {
                    $lastNumeric = (int) $matches[1];
                }
            }

            $candidateNumber = $lastInvoice ? ($lastNumeric + 1) : (int) $startingNumber;

            for ($attempt = 0; $attempt < 100000; $attempt++) {
                $candidate = $prefix . str_pad((string) $candidateNumber, 5, '0', STR_PAD_LEFT);

                $exists = Invoice::withoutGlobalScopes()
                    ->where('company_id', $this->id)
                    ->where('invoice_number', $candidate)
                    ->lockForUpdate()
                    ->exists();

                if (!$exists) {
                    return $candidate;
                }

                $candidateNumber++;
            }

            throw new \RuntimeException('Unable to generate a unique invoice number.');
        });
    }

    public function getNextQuotationNumber(): string
    {
        $prefix = $this->quotation_prefix ?? 'QT-';

        return DB::transaction(function () use ($prefix) {
            DB::table('companies')->where('id', $this->id)->lockForUpdate()->first();

            $lastQuotation = Quotation::withoutGlobalScopes()
                ->where('company_id', $this->id)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $lastNumeric = 0;

            if ($lastQuotation && $lastQuotation->quotation_number) {
                $numberPart = str_starts_with($lastQuotation->quotation_number, $prefix)
                    ? substr($lastQuotation->quotation_number, strlen($prefix))
                    : $lastQuotation->quotation_number;

                if (preg_match('/(\d+)$/', (string) $numberPart, $matches)) {
                    $lastNumeric = (int) $matches[1];
                }
            }

            $candidateNumber = $lastQuotation ? ($lastNumeric + 1) : 1;

            for ($attempt = 0; $attempt < 100000; $attempt++) {
                $candidate = $prefix . str_pad((string) $candidateNumber, 5, '0', STR_PAD_LEFT);

                $exists = Quotation::withoutGlobalScopes()
                    ->where('company_id', $this->id)
                    ->where('quotation_number', $candidate)
                    ->lockForUpdate()
                    ->exists();

                if (!$exists) {
                    return $candidate;
                }

                $candidateNumber++;
            }

            throw new \RuntimeException('Unable to generate a unique quotation number.');
        });
    }

    public function getNextPaymentNumber(): string
    {
        $prefix = 'PAY-';

        return DB::transaction(function () use ($prefix) {
            DB::table('companies')->where('id', $this->id)->lockForUpdate()->first();

            $lastPayment = \App\Models\Payment::withoutGlobalScopes()
                ->where('company_id', $this->id)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $lastNumeric = 0;

            if ($lastPayment && $lastPayment->payment_number) {
                $numberPart = str_starts_with($lastPayment->payment_number, $prefix)
                    ? substr($lastPayment->payment_number, strlen($prefix))
                    : $lastPayment->payment_number;

                if (preg_match('/(\d+)$/', (string) $numberPart, $matches)) {
                    $lastNumeric = (int) $matches[1];
                }
            }

            $candidateNumber = $lastPayment ? ($lastNumeric + 1) : 1;

            for ($attempt = 0; $attempt < 100000; $attempt++) {
                $candidate = $prefix . str_pad((string) $candidateNumber, 6, '0', STR_PAD_LEFT);

                $exists = \App\Models\Payment::withoutGlobalScopes()
                    ->where('company_id', $this->id)
                    ->where('payment_number', $candidate)
                    ->exists();

                if (!$exists) {
                    return $candidate;
                }

                $candidateNumber++;
            }

            throw new \RuntimeException('Unable to generate a unique payment number.');
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->authorized_signature ? asset('storage/' . $this->authorized_signature) : null;
    }

    public function getCurrencySymbolAttribute(): string
    {
        return CompanySetting::getCurrencySymbol($this->currency ?? 'USD');
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' &&
            ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial';
    }

    public function canUseFeature(string $feature): bool
    {
        if (!$this->subscriptionPlan) {
            return false;
        }

        return match ($feature) {
            'stripe' => $this->subscriptionPlan->stripe_enabled,
            'custom_branding' => $this->subscriptionPlan->custom_branding,
            'api_access' => $this->subscriptionPlan->api_access,
            default => true,
        };
    }

    public function hasReachedLimit(string $resource): bool
    {
        if (!$this->subscriptionPlan) {
            return true;
        }

        return match ($resource) {
            'invoices' => $this->subscriptionPlan->max_invoices !== null &&
            $this->invoices()->count() >= $this->subscriptionPlan->max_invoices,
            'clients' => $this->subscriptionPlan->max_clients !== null &&
            $this->clients()->count() >= $this->subscriptionPlan->max_clients,
            'users' => $this->subscriptionPlan->max_users !== null &&
            $this->users()->count() >= $this->subscriptionPlan->max_users,
            'products' => $this->subscriptionPlan->max_products !== null &&
            $this->products()->count() >= $this->subscriptionPlan->max_products,
            default => false,
        };
    }
}

