<?php

namespace App\Mail;

use App\Models\TravelAlertOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Double-Opt-in: der Kunde bestaetigt seine Bestellung ueber den Link.
 */
class TravelAlertOrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TravelAlertOrder $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                'Passolution Travel Information Platform'
            ),
            subject: 'Bitte bestätigen Sie Ihre Travel Alert-Bestellung',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travel-alert-confirm-order',
            with: [
                'orderData' => $this->order->only($this->order->getFillable()),
                'customerName' => trim(($this->order->first_name ?? '').' '.($this->order->last_name ?? '')),
                'confirmationUrl' => route('risk-overview.order.confirm', ['token' => $this->order->confirmation_token]),
                'expiresAt' => $this->order->confirmationExpiresAt(),
            ],
        );
    }
}
