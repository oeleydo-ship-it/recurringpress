<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\Ai\AiBlogService;
use App\Services\TenantNotifier;
use App\Support\TenantDomain;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('invoices:check-overdue', function () {
    $updated = 0;

    // Row by row (not a bulk update) so each tenant's team gets a bell notification.
    Invoice::withoutGlobalScopes()
        ->with(['company', 'client' => fn ($q) => $q->withoutGlobalScopes()])
        ->where('status', Invoice::STATUS_SENT)
        ->where('due_date', '<', now())
        ->chunkById(200, function ($invoices) use (&$updated) {
            foreach ($invoices as $invoice) {
                $invoice->forceFill(['status' => Invoice::STATUS_OVERDUE])->save();
                $updated++;

                TenantNotifier::notify(
                    $invoice->company_id,
                    'invoice_overdue',
                    "Invoice overdue: {$invoice->invoice_number}",
                    TenantNotifier::money($invoice->remaining_amount, $invoice->company?->currency) . ' due from '
                        . ($invoice->client?->bill_to_name ?? 'client') . ' since ' . $invoice->due_date->format('M j, Y') . '.',
                    TenantNotifier::path('invoices.show', $invoice->id),
                    dedupeKey: "invoice_overdue:{$invoice->id}",
                );
            }
        });

    $this->info("Marked {$updated} invoices as overdue.");
})->purpose('Check and mark overdue invoices');

Artisan::command('subscriptions:notify-trial-ending', function () {
    $notified = 0;

    Company::where('subscription_status', 'trial')
        ->whereNotNull('subscription_ends_at')
        ->whereBetween('subscription_ends_at', [now(), now()->addDays(3)])
        ->each(function (Company $company) use (&$notified) {
            $days = max(1, (int) ceil(now()->diffInHours($company->subscription_ends_at) / 24));
            TenantNotifier::notify(
                $company,
                'trial_ending',
                $days === 1 ? 'Your free trial ends tomorrow' : "Your free trial ends in {$days} days",
                'Choose a plan before ' . $company->subscription_ends_at->format('M j, Y') . ' to keep sending invoices.',
                TenantNotifier::path('settings.subscription'),
                TenantNotifier::AUDIENCE_ADMINS,
                'trial_ending:' . $company->subscription_ends_at->toDateString(),
            );
            $notified++;
        });

    $this->info("Checked {$notified} trials ending soon.");
})->purpose('Notify tenants whose free trial ends within three days');

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
Schedule::command('subscriptions:notify-trial-ending')->daily();
Schedule::command('invoices:generate-recurring')->daily()->withoutOverlapping()->onOneServer();
Schedule::command('system:check-updates')->daily();
Schedule::command('domains:recheck')->hourly()->withoutOverlapping()->onOneServer();

// Automated AI Blog Post Generation & Scheduling
Schedule::command('blog:ai-schedule')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::command('blog:publish-scheduled')->everyMinute()->withoutOverlapping();

// Feeds the Horizon dashboard's throughput / runtime graphs.
Schedule::command('horizon:snapshot')->everyFiveMinutes();
