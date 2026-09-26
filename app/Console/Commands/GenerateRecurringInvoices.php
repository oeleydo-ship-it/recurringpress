<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring {--dry-run : Show what would be generated without actually creating invoices}';

    protected $description = 'Generate invoices from active recurring invoices that are due';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Checking for recurring invoices due for generation...');

        $recurringInvoices = RecurringInvoice::withoutGlobalScope('company')
            ->where('status', RecurringInvoice::STATUS_ACTIVE)
            ->where('next_invoice_date', '<=', now())
            ->get();

        if ($recurringInvoices->isEmpty()) {
            $this->info('No recurring invoices are due for generation.');
            return Command::SUCCESS;
        }

        $this->info("Found {$recurringInvoices->count()} recurring invoice(s) due for generation.");

        $generated = 0;
        $failed = 0;

        foreach ($recurringInvoices as $recurringInvoice) {
            $this->line("Processing: {$recurringInvoice->name} (ID: {$recurringInvoice->id})");

            if ($dryRun) {
                $this->info("  [DRY RUN] Would generate invoice for client: {$recurringInvoice->client->name}");
                $this->info("  [DRY RUN] Amount: {$recurringInvoice->total}");
                continue;
            }

            try {
                $invoice = $recurringInvoice->generateInvoice();

                if ($invoice) {
                    $this->info("  Generated invoice: {$invoice->invoice_number}");
                    $generated++;

                    Log::info('Recurring invoice generated', [
                        'recurring_invoice_id' => $recurringInvoice->id,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'company_id' => $recurringInvoice->company_id,
                    ]);
                } else {
                    $this->warn("  Could not generate invoice (conditions not met)");
                }
            } catch (\Exception $e) {
                $this->error("  Failed to generate invoice: {$e->getMessage()}");
                $failed++;

                Log::error('Failed to generate recurring invoice', [
                    'recurring_invoice_id' => $recurringInvoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary: {$generated} invoice(s) generated, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
