<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactUsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fromName,
        public string $fromEmail,
        public string $subjectLine,
        public string $messageBody
    ) {}

    public function build()
    {
        return $this->subject('[Contact] '.$this->subjectLine)
            ->replyTo($this->fromEmail, $this->fromName)
            ->html('
                <p><b>Name:</b> '.e($this->fromName).'</p>
                <p><b>Email:</b> '.e($this->fromEmail).'</p>
                <p><b>Subject:</b> '.e($this->subjectLine).'</p>
                <hr/>
                <p>'.nl2br(e($this->messageBody)).'</p>
            ');
    }
}