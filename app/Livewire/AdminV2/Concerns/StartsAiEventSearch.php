<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Jobs\RunAiEventSearch;
use App\Models\AiEventSearch;
use App\Support\AiSettings;
use Livewire\Attributes\Computed;

/**
 * KI-Suche nach aktuellen Ereignissen starten und ihren Stand anzeigen –
 * fuer System > KI und fuer die Ereignisliste.
 */
trait StartsAiEventSearch
{
    #[Computed]
    public function latestAiSearch(): ?AiEventSearch
    {
        return AiEventSearch::query()->with('starter')->latest('id')->first();
    }

    /**
     * @param  array<string, mixed>|null  $filters  Eingrenzung fuer eine gezielte Suche; null = allgemeine Suche
     */
    public function startAiSearch(?array $filters = null): void
    {
        if (! AiSettings::apiKey()) {
            $this->dispatch('adminv2-toast', message: 'Es ist kein OpenAI-Schlüssel hinterlegt (System › KI).', variant: 'danger');

            return;
        }

        // Nicht zwei Suchen gleichzeitig – sie wuerden dieselben Themen doppelt liefern.
        if ($this->latestAiSearch?->isRunning()) {
            return;
        }

        $search = AiEventSearch::create([
            'status' => AiEventSearch::STATUS_RUNNING,
            'exclude_existing' => AiSettings::eventSearchExcludesExisting(),
            'filters' => $filters,
            'started_by' => auth('web')->id(),
        ]);

        // Laeuft nach dem Absenden der Antwort weiter; die Seite fragt den Stand ab.
        RunAiEventSearch::dispatchAfterResponse($search->id);

        unset($this->latestAiSearch);
    }

    /**
     * Wird waehrend einer laufenden Suche in kurzen Abstaenden aufgerufen.
     */
    public function refreshAiSearch(): void
    {
        unset($this->latestAiSearch);
    }
}
