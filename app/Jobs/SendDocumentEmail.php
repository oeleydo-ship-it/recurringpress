<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\TenantMailer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Renders an invoice or quotation PDF with the company's template and emails it to the
 * client. Runs on the "mail" queue (Horizon) so the request returns immediately.
 */
class SendDocumentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 120, 600];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Invoice|Quotation $document, public ?string $recipient = null)
    {
        $this->onQueue('mail');
    }

    public function handle(TenantMailer $mailers): void
    {
        $doc = $this->document->loadMissing(['client', 'items', 'company.settings']);
        $isInvoice = $doc instanceof Invoice;
        $recipient = $this->recipient ?: $doc->client?->email;

        if (! $recipient) {
            Log::warning('Document email skipped: client has no email address', ['type' => class_basename($doc), 'id' => $doc->id]);

            return;
        }

        $kind = $isInvoice ? 'invoices' : 'quotations';
        $template = $doc->company->settings->invoice_template ?? 'classic';
        $view = view()->exists("{$kind}.pdf.{$template}") ? "{$kind}.pdf.{$template}" : "{$kind}.pdf.classic";
        $var = $isInvoice ? 'invoice' : 'quotation';
        $number = $isInvoice ? $doc->invoice_number : $doc->quotation_number;
        $label = $isInvoice ? 'Invoice' : 'Quotation';

        $pdf = Pdf::loadView($view, [$var => $doc])->output();

        // Tenant's own SMTP when configured and tested; otherwise (or if it fails) the platform mailer.
        $mailers->send($doc->company, "emails.{$var}", [$var => $doc], function ($message) use ($recipient, $label, $number, $doc, $pdf) {
            $message->to($recipient);
            $message->subject("{$label} {$number} from {$doc->company->name}");
            $message->attachData($pdf, strtolower($label) . "-{$number}.pdf", ['mime' => 'application/pdf']);
        });
    }

    public function failed(Throwable $e): void
    {
        Log::error('Document email failed after retries', [
            'type' => class_basename($this->document),
            'id' => $this->document->id ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
