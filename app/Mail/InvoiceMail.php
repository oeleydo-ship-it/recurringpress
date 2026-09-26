<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $email = $this->subject('Invoice #' . $this->invoice->invoice_number . ' from ' . config('app.name'))
            ->view('emails.invoice');

        // Attach PDF if it exists (assuming PDF generation is handled elsewhere or on the fly)
        // For now, we will just send the link.
        // If you had a PDF path:
        // $email->attach($pathToFile);

        return $email;
    }
}
