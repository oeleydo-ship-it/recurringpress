<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Invites a new team member to set their own password (no shared/temporary passwords).
 */
class TeamInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $inviterName, public string $companyName, public string $setupUrl)
    {
        $this->onQueue('mail');
    }

    public function build()
    {
        return $this->subject("You've been invited to join {$this->companyName}")
            ->view('emails.team-invite');
    }
}
