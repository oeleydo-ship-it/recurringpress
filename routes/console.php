<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Invoice;
use App\Models\CompanySetting;
use App\Models\Quotation;
use App\Support\TenantDomain;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('invoices:check-overdue', function () {
    $updated = Invoice::where('status', 'sent')
        ->where('due_date', '<', now())
        ->update(['status' => 'overdue']);

    $this->info("Marked {$updated} invoices as overdue.");
})->purpose('Check and mark overdue invoices');

Artisan::command('quotations:check-expired', function () {
    $updated = Quotation::withoutGlobalScopes()
        ->whereIn('status', [Quotation::STATUS_SENT, Quotation::STATUS_DRAFT])
        ->where('valid_until', '<', now()->startOfDay())
        ->update(['status' => Quotation::STATUS_EXPIRED]);

    $this->info("Marked {$updated} quotations as expired.");
})->purpose('Check and mark expired quotations');

Artisan::command('domains:recheck', function () {
    if (! TenantDomain::enabled()) {
        $this->info('Custom domains are disabled (CUSTOM_DOMAINS_ENABLED).');

        return;
    }

    $checked = 0;
    $failed = 0;

    CompanySetting::whereNotNull('custom_domain')->whereNotNull('custom_domain_token')->each(function (CompanySetting $settings) use (&$checked, &$failed) {
        $checked++;
        if (! TenantDomain::verify($settings)) {
            $failed++;
        }
    });

    $this->info("Checked {$checked} custom domains, {$failed} failing.");
})->purpose('Re-check DNS for tenant custom domains (verifies pending ones, un-verifies broken ones)');

// Laravel 11 only reads the schedule from this file; app/Console/Kernel.php is no longer loaded.
Schedule::command('invoices:check-overdue')->daily();
Schedule::command('quotations:check-expired')->daily();
Schedule::command('invoices:generate-recurring')->daily()->withoutOverlapping()->onOneServer();
Schedule::command('system:check-updates')->daily();
Schedule::command('domains:recheck')->hourly()->withoutOverlapping()->onOneServer();

// Feeds the Horizon dashboard's throughput / runtime graphs.
Schedule::command('horizon:snapshot')->everyFiveMinutes();
