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
        public array $orderData,
        public bool $isNewCustomer = false,
        public ?int $customerId = null,
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->isNewCustomer ? 'Neukunde' : 'Bestandskunde';

        return new Envelope(
            subject: "Neue Travel Alert-Bestellung ({$status}): " . $this->orderData['company'],
        );
    }

    public function content(): Content
    {
        $customerUrl = $this->customerId
            ? url("/admin/customers/{$this->customerId}")
            : null;

        return new Content(
            view: 'emails.travel-alert-order',
            with: [
                'isNewCustomer' => $this->isNewCustomer,
                'customerUrl' => $customerUrl,
            ],
        );
    }
}
