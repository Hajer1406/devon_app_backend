<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function build()
    {
        return $this->subject('Your password reset code')
            ->html('
                <p>Your password reset code is:</p>
                <h2 style="letter-spacing:4px;">'.e($this->code).'</h2>
                <p>This code expires soon. If you didn’t request it, ignore this email.</p>
            ');
    }
}