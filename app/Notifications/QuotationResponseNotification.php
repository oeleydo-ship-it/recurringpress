<?php

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuotationResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Quotation $quotation;
    protected string $action;

    public function __construct(Quotation $quotation, string $action)
    {
        $this->quotation = $quotation;
        $this->action = $action;
        $this->onQueue('mail');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->action === 'accepted' 
            ? "Quotation {$this->quotation->quotation_number} Accepted!"
            : "Quotation {$this->quotation->quotation_number} Rejected";

        $greeting = $this->action === 'accepted' ? 'Great news!' : 'Update on your quotation';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting);

        if ($this->action === 'accepted') {
            $message->line("Your quotation {$this->quotation->quotation_number} has been accepted by {$this->quotation->client->name}.")
                ->line("Total Amount: " . number_format($this->quotation->total, 2))
                ->action('View Quotation', route('quotations.show', $this->quotation))
                ->line('You can now convert this quotation to an invoice.');
        } else {
            $message->line("Your quotation {$this->quotation->quotation_number} has been rejected by {$this->quotation->client->name}.");
            
            if ($this->quotation->response_notes) {
                $message->line("Reason: " . $this->quotation->response_notes);
            }
            
            $message->action('View Quotation', route('quotations.show', $this->quotation));
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'quotation_id' => $this->quotation->id,
            'quotation_number' => $this->quotation->quotation_number,
            'client_name' => $this->quotation->client->name,
            'action' => $this->action,
            'total' => $this->quotation->total,
            'response_notes' => $this->quotation->response_notes,
            'responded_at' => $this->quotation->responded_at,
        ];
    }
}
