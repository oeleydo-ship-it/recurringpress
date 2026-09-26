<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Queued so the forgot-password endpoint responds in the same time whether or not the
 * account exists (sending SMTP inline made real accounts measurably slower to answer).
 */
class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $resetUrl)
    {
        $this->onQueue('mail');
    }

    public function build()
    {
        return $this->subject('Reset Your Password')
            ->view('emails.password-reset');
    }
}
