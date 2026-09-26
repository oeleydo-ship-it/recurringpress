<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRecurringInvoice;
use App\Models\RecurringInvoice;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring
                            {--dry-run : Show what would be generated without actually creating invoices}
                            {--sync : Generate inline instead of dispatching queue jobs}';

    protected $description = 'Generate invoices from active recurring invoices that are due (one Horizon job per schedule)';

    public function handle(): int
    {
        $due = RecurringInvoice::withoutGlobalScope('company')
            ->with('client')
            ->where('status', RecurringInvoice::STATUS_ACTIVE)
            ->where('next_invoice_date', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->info('No recurring invoices are due for generation.');

            return Command::SUCCESS;
        }

        $this->info("Found {$due->count()} recurring invoice(s) due.");

        foreach ($due as $recurring) {
            $label = "{$recurring->name} (ID {$recurring->id}, client: " . ($recurring->client?->name ?? 'n/a') . ", {$recurring->total})";

            if ($this->option('dry-run')) {
                $this->line("  [DRY RUN] would generate: {$label}");
                continue;
            }

            if ($this->option('sync')) {
                (new GenerateRecurringInvoice($recurring->id))->handle();
                $this->line("  Generated inline: {$label}");
                continue;
            }

            GenerateRecurringInvoice::dispatch($recurring->id);
            $this->line("  Queued: {$label}");
        }

        return Command::SUCCESS;
    }
}
