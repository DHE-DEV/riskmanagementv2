<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelAlertOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $orderData
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neue TravelAlert-Bestellung: '.$this->orderData['company'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travel-alert-order',
        );
    }
}
