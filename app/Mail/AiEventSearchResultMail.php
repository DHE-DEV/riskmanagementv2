<?php

namespace App\Mail;

use App\Models\AiEventSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ergebnis einer hinterlegten KI-Suche nach Ereignissen – mit Link direkt zu
 * den Ergebnissen dieses Laufs.
 */
class AiEventSearchResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AiEventSearch $search,
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->search->profile?->name ?? 'KI-Suche';

        $result = match (true) {
            $this->search->status !== AiEventSearch::STATUS_DONE => 'fehlgeschlagen',
            $this->search->new_count === 0 => 'nichts Neues',
            $this->search->new_count === 1 => '1 neuer Vorschlag',
            default => $this->search->new_count.' neue Vorschläge',
        };

        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: 'KI-Suche „'.$name.'“: '.$result,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ai-event-search-result',
            with: [
                'search' => $this->search,
                'name' => $this->search->profile?->name ?? 'KI-Suche',
                'suggestions' => $this->search->suggestions()->oldest('id')->get(),
                // Direkt zu den Ergebnissen dieses Laufs – gleich in welchem Zustand sie inzwischen sind.
                'url' => route('adminv2.events.ai-results', ['run' => $this->search->id, 'status' => 'all']),
            ],
        );
    }
}
