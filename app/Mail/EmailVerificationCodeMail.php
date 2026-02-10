<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function build()
    {
        return $this->subject('Your verification code')
            ->html('
                <p>Your verification code is:</p>
                <h2 style="letter-spacing:4px;">'.e($this->code).'</h2>
                <p>This code expires soon. If you didn’t request it, ignore this email.</p>
            ');
    }
}