<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Infomail an Passolution: Ein Kunde hat unter /customer/settings seine
 * Firmen- oder Rechnungsadresse geaendert. Enthaelt alte und neue Daten sowie
 * Links auf den Datensatz in Admin v2 und im alten pds-admin.
 */
class CustomerAddressChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public const TYPE_COMPANY = 'company';

    public const TYPE_BILLING = 'billing';

    /** Felder je Adresstyp mit Bezeichnung in der Mail. */
    public const FIELDS = [
        self::TYPE_COMPANY => [
            'company_name' => 'Firmenname',
            'company_additional' => 'Zusatz',
            'company_street' => 'Straße',
            'company_house_number' => 'Hausnummer',
            'company_postal_code' => 'PLZ',
            'company_city' => 'Ort',
            'company_country' => 'Land',
        ],
        self::TYPE_BILLING => [
            'billing_company_name' => 'Firmenname',
            'billing_additional' => 'Zusatz',
            'billing_street' => 'Straße',
            'billing_house_number' => 'Hausnummer',
            'billing_postal_code' => 'PLZ',
            'billing_city' => 'Ort',
            'billing_country' => 'Land',
        ],
    ];

    public const TYPE_LABELS = [
        self::TYPE_COMPANY => 'Firmenanschrift',
        self::TYPE_BILLING => 'Rechnungsadresse',
    ];

    /**
     * @param  array<string, ?string>  $old
     * @param  array<string, ?string>  $new
     */
    public function __construct(
        public Customer $customer,
        public string $type,
        public array $old,
        public array $new,
        public ?string $changedBy = null,
    ) {}

    /**
     * Hat sich an der Adresse tatsaechlich etwas geaendert? Leer und null gelten
     * als gleich, damit ein Speichern ohne Aenderung keine Mail ausloest.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public static function hasChanges(string $type, array $old, array $new): bool
    {
        foreach (array_keys(self::FIELDS[$type]) as $field) {
            if (trim((string) ($old[$field] ?? '')) !== trim((string) ($new[$field] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: self::TYPE_LABELS[$this->type].' geändert: '.$this->customerName(),
        );
    }

    public function content(): Content
    {
        $rows = [];
        foreach (self::FIELDS[$this->type] as $field => $label) {
            $old = trim((string) ($this->old[$field] ?? ''));
            $new = trim((string) ($this->new[$field] ?? ''));
            $rows[] = ['label' => $label, 'old' => $old, 'new' => $new, 'changed' => $old !== $new];
        }

        return new Content(
            view: 'emails.customer-address-changed',
            with: [
                'customer' => $this->customer,
                'customerName' => $this->customerName(),
                'typeLabel' => self::TYPE_LABELS[$this->type],
                'rows' => $rows,
                'changedBy' => $this->changedBy,
                'adminUrl' => route('adminv2.customer-management.customers.edit', $this->customer->id),
                'backendUrl' => $this->customer->pds_account_id
                    ? config('app.pds_backend_url').'/client/accounts/'.$this->customer->pds_account_id.'/details'
                    : null,
            ],
        );
    }

    protected function customerName(): string
    {
        return $this->customer->company_name ?: ($this->customer->name ?: $this->customer->email);
    }
}
