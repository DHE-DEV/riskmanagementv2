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
 * Der Zugang steht bereit – entweder direkt nach der Bestaetigung oder
 * nachdem ein Mitarbeiter freigeschaltet hat.
 */
class TravelAlertAccessActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TravelAlertOrder $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: 'Ihr Travel Alert-Zugang ist freigeschaltet',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travel-alert-activated',
            with: [
                'orderData' => $this->order->only($this->order->getFillable()),
                'customerName' => trim(($this->order->first_name ?? '').' '.($this->order->last_name ?? '')),
                'loginUrl' => route('customer.login'),
                'passwordResetUrl' => route('customer.password.request'),
                'travelAlertUrl' => route('risk-overview'),
            ],
        );
    }
}
