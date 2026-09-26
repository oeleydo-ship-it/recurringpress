<?php

namespace App\Jobs;

use App\Models\RecurringInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Generates one due invoice from a recurring schedule. Dispatched per schedule by
 * `invoices:generate-recurring` so each runs, retries and shows up in Horizon on its own.
 */
class GenerateRecurringInvoice implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public int $uniqueFor = 3600;

    public function __construct(public int $recurringInvoiceId)
    {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return (string) $this->recurringInvoiceId;
    }

    public function handle(): void
    {
        // Queue workers have no authenticated user, so bypass the tenant scope explicitly.
        $recurring = RecurringInvoice::withoutGlobalScope('company')->find($this->recurringInvoiceId);

        if (! $recurring) {
            return;
        }

        $invoice = $recurring->generateInvoice();

        if ($invoice) {
            Log::info('Recurring invoice generated', [
                'recurring_invoice_id' => $recurring->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'company_id' => $recurring->company_id,
            ]);
        }
    }
}
