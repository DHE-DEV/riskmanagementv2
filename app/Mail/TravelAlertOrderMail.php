<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Interne Bestellinfo an Passolution.
 *
 * Geht zweimal raus: beim Absenden des Formulars ($stage 'received') und
 * nachdem der Kunde die Bestellung per Mail bestaetigt hat ('confirmed').
 */
class TravelAlertOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $orderData,
        public bool $isNewCustomer = false,
        public ?int $customerId = null,
        public string $stage = 'received',
        public bool $awaitsApproval = false,
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->isNewCustomer ? 'Neukunde' : 'Bestandskunde';

        $subject = match (true) {
            $this->stage === 'confirmed' && $this->awaitsApproval => "Travel Alert-Bestellung wartet auf Freischaltung ({$status}): ",
            $this->stage === 'confirmed' => "Travel Alert-Bestellung bestätigt ({$status}): ",
            default => "Neue Travel Alert-Bestellung ({$status}): ",
        };

        return new Envelope(
            subject: $subject.$this->orderData['company'],
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
                'stage' => $this->stage,
                'awaitsApproval' => $this->awaitsApproval,
            ],
        );
    }
}
