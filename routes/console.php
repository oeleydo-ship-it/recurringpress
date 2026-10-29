<?php

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\Ai\AiBlogService;
use App\Support\TenantDomain;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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
        $this->info('Custom domains are switched off or Cloudflare is not configured (Superadmin → Custom domains).');

        return;
    }

    $checked = 0;
    $failed = 0;

    CompanySetting::whereNotNull('custom_domain')->each(function (CompanySetting $settings) use (&$checked, &$failed) {
        $checked++;
        if (! TenantDomain::verify($settings)) {
            $failed++;
        }
    });

    $this->info("Checked {$checked} custom domains, {$failed} failing.");
})->purpose('Refresh tenant custom domain status from Cloudflare (verifies pending ones, un-verifies broken ones)');

Artisan::command('blog:ai-schedule', function (AiBlogService $blogService) {
    $dispatched = $blogService->runScheduledGeneration();
    if ($dispatched) {
        $this->info("Dispatched AI blog post generation job for: {$dispatched}");
    } else {
        $this->info('No AI blog generation required at this time.');
    }
})->purpose('Generate and schedule AI blog posts based on superadmin settings');

Artisan::command('blog:publish-scheduled', function (AiBlogService $blogService) {
    $published = $blogService->publishDuePosts();
    $this->info("Published {$published} scheduled blog post(s).");
})->purpose('Publish scheduled blog posts whose publish date has arrived');

// Laravel 11 only reads the schedule from this file; app/Console/Kernel.php is no longer loaded.
Schedule::command('invoices:check-overdue')->daily();
Schedule::command('quotations:check-expired')->daily();
Schedule::command('invoices:generate-recurring')->daily()->withoutOverlapping()->onOneServer();
Schedule::command('system:check-updates')->daily();
Schedule::command('domains:recheck')->hourly()->withoutOverlapping()->onOneServer();

// Automated AI Blog Post Generation & Scheduling
Schedule::command('blog:ai-schedule')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::command('blog:publish-scheduled')->everyMinute()->withoutOverlapping();

// Feeds the Horizon dashboard's throughput / runtime graphs.
Schedule::command('horizon:snapshot')->everyFiveMinutes();
