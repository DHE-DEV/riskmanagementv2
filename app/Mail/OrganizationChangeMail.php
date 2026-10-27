<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail einer Organisation zu Aenderungen ihrer Agenturen und Filialen –
 * Betreff und Inhalt hat die Organisation selbst gestaltet und
 * OrganizationChangeNotifier::render() bereits zusammengesetzt.
 */
class OrganizationChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailHtml,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->mailHtml);
    }
}
