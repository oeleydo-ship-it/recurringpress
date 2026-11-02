<?php

namespace App\Jobs;

use App\Models\ClientAccount;
use App\Services\TenantMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Emails a client their portal invite or password reset link, through the tenant's own SMTP
 * when configured (see TenantMailer). Runs on the "mail" queue.
 */
class SendClientPortalLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public ClientAccount $account, public string $url, public bool $invite)
    {
        $this->onQueue('mail');
    }

    public function handle(TenantMailer $mailers): void
    {
        $company = $this->account->company;
        $email = $this->account->email;
        $subject = $this->invite
            ? "Access your invoices and quotations from {$company->name}"
            : "Reset your {$company->name} portal password";

        $mailers->send($company, 'emails.client-portal-link', [
            'companyName' => $company->name,
            'clientName' => $this->account->client?->name,
            'setupUrl' => $this->url,
            'invite' => $this->invite,
        ], function ($message) use ($email, $subject) {
            $message->to($email);
            $message->subject($subject);
        });
    }
}
