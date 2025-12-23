<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PluginVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $code,
        public string $contactName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihr Verifizierungscode für Global Travel Monitor',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plugin-verification-code',
            with: [
                'code' => $this->code,
                'contactName' => $this->contactName,
                'expiryMinutes' => 15,
            ],
        );
    }
}
