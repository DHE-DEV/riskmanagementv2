<?php

namespace App\Mail;

use App\Models\Branch;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Infomail an Passolution: Ein Kunde hat unter Einstellungen ›
 * Organisationsstruktur eine Filiale angelegt, geaendert oder geloescht.
 * Start- und Enddatum stehen in den Zeilen – Passolution rechnet danach ab.
 */
class BranchChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{label: string, old: string, new: string, changed: bool}>  $rows
     */
    public function __construct(
        public Branch $branch,
        public string $kind,
        public array $rows,
        public ?string $changedBy = null,
        public ?string $reason = null,
        public ?CarbonInterface $scheduledAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: $this->kind.': '.$this->branch->name.' ('.$this->ownerName().')',
        );
    }

    public function content(): Content
    {
        $owner = $this->branch->customer;

        return new Content(
            view: 'emails.branch-changed',
            with: [
                'branch' => $this->branch,
                'owner' => $owner,
                'ownerName' => $this->ownerName(),
                'kind' => $this->kind,
                'rows' => $this->rows,
                'changedBy' => $this->changedBy,
                'reason' => $this->reason,
                'scheduledAt' => $this->scheduledAt,
                'adminUrl' => $owner ? route('adminv2.customer-management.customers.edit', ['customer' => $owner->id, 'tab' => 'branches']) : null,
                'backendUrl' => $owner?->pds_account_id
                    ? config('app.pds_backend_url').'/client/accounts/'.$owner->pds_account_id.'/details'
                    : null,
            ],
        );
    }

    protected function ownerName(): string
    {
        $owner = $this->branch->customer;

        return $owner ? ($owner->company_name ?: ($owner->name ?: (string) $owner->email)) : 'unbekannter Kunde';
    }
}
