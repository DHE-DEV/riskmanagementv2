<?php

namespace App\Mail;

use App\Models\TravelAlertOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bestellung bestaetigt, die Freischaltung uebernimmt ein Mitarbeiter
 * (TRAVEL_ALERT_AUTO_ACTIVATION=false).
 */
class TravelAlertOrderPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TravelAlertOrder $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: 'Ihre Travel Alert-Bestellung wird geprüft',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travel-alert-pending-approval',
            with: [
                'customerName' => trim(($this->order->first_name ?? '').' '.($this->order->last_name ?? '')),
                'company' => $this->order->company,
            ],
        );
    }
}
