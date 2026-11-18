<?php

namespace App\Jobs;

use App\Models\CustomEvent;
use App\Services\EventTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Übersetzt Titel und Beschreibung eines freigegebenen globalen Ereignisses per DeepL
 * in alle Sprachen, die noch fehlen. Vorhandene (auch manuell gepflegte) Übersetzungen
 * bleiben unangetastet. Schlägt eine Sprache fehl, wird das Erreichte gespeichert und
 * der Job später erneut versucht – dann nur noch für die weiterhin fehlenden Felder.
 */
class TranslateCustomEvent implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 600];

    public function __construct(public int $eventId) {}

    public function uniqueId(): string
    {
        return (string) $this->eventId;
    }

    public function handle(EventTranslationService $translator): void
    {
        $event = CustomEvent::find($this->eventId);

        if (! $event || ! $event->qualifiesForAutoTranslation() || ! $translator->isConfigured()) {
            return;
        }

        $result = $translator->translateMissing($event);

        if ($result['errors'] !== []) {
            throw new RuntimeException("Automatische Übersetzung von Ereignis {$event->id} unvollständig – ".implode(' | ', $result['errors']));
        }
    }
}
