<?php

namespace App\Mail;

use App\Models\CustomerContract;
use App\Models\ProductVersionPrice;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Erinnerung an Passolution: In einem Kundenvertrag ist eine jaehrliche
 * Preiserhoehung vereinbart, der naechste Stichtag steht an. Zeigt die
 * individuellen Preise heute und nach der Erhoehung – geaendert wird nichts.
 */
class ContractPriceIncreaseReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerContract $contract,
        public CarbonInterface $anniversary,
    ) {}

    public function envelope(): Envelope
    {
        $customer = $this->contract->customer;

        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: 'Preiserhöhung '.$this->contract->increaseLabel().' zum '.$this->anniversary->format('d.m.Y').': '.($customer->company_name ?: $customer->name),
        );
    }

    public function content(): Content
    {
        $factor = 1 + (float) $this->contract->annual_increase_percent / 100;
        $format = fn ($value) => number_format((float) $value, 2, ',', '.').' €';

        $rows = [];
        foreach ($this->contract->prices as $price) {
            $label = trim(($price->version?->product?->name ?? 'Produkt').' '.($price->version && $price->version->name !== 'Allgemein' ? $price->version->name : ''));
            foreach (ProductVersionPrice::AMOUNTS as $column => $suffix) {
                if (! $price->is_free && $price->{$column} !== null) {
                    $rows[] = [
                        'label' => $label.' · '.$suffix.($price->pax ? ' · '.number_format($price->pax, 0, ',', '.').' PAX' : ''),
                        'old' => $format($price->{$column}),
                        'new' => $format(round((float) $price->{$column} * $factor, 2)),
                    ];
                }
            }
        }

        return new Content(
            view: 'emails.contract-price-increase-reminder',
            with: [
                'contract' => $this->contract,
                'customer' => $this->contract->customer,
                'anniversary' => $this->anniversary,
                'rows' => $rows,
                'adminUrl' => route('adminv2.customer-management.customers.edit', ['customer' => $this->contract->customer_id, 'tab' => 'subscription']),
            ],
        );
    }
}
