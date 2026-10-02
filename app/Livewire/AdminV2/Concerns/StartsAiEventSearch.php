<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Jobs\RunAiEventSearch;
use App\Models\AiEventSearch;
use App\Models\AiEventSearchProfile;
use App\Services\AiEventSearchService;
use App\Support\AiSettings;
use Livewire\Attributes\Computed;

/**
 * Eine hinterlegte KI-Suche von Hand ausfuehren und den Stand der letzten
 * Suche anzeigen. Gesucht wird ausschliesslich ueber hinterlegte Suchen
 * (System > KI) – dort stehen Auftrag, Filter und Zeitplan.
 */
trait StartsAiEventSearch
{
    #[Computed]
    public function latestAiSearch(): ?AiEventSearch
    {
        return AiEventSearch::query()->with(['starter', 'profile'])->latest('id')->first();
    }

    /**
     * Die hinterlegten Suchen fuer die Auswahl "Suche ausfuehren".
     */
    #[Computed]
    public function aiSearchProfiles()
    {
        return AiEventSearchProfile::query()->orderBy('name')->get(['id', 'name', 'is_active']);
    }

    /**
     * Eine hinterlegte Suche sofort ausfuehren – ausser der Reihe.
     */
    public function runAiProfile(int $profileId): void
    {
        if (! AiSettings::apiKey()) {
            $this->dispatch('adminv2-toast', message: 'Es ist kein OpenAI-Schlüssel hinterlegt (System › KI).', variant: 'danger');

            return;
        }

        // Nicht zwei Suchen gleichzeitig – sie wuerden dieselben Themen doppelt liefern.
        if ($this->latestAiSearch?->isRunning()) {
            $this->dispatch('adminv2-toast', message: 'Es läuft bereits eine Suche. Bitte warten, bis sie fertig ist.', variant: 'danger');

            return;
        }

        $profile = AiEventSearchProfile::findOrFail($profileId);
        $search = app(AiEventSearchService::class)->createSearchFor($profile, auth('web')->id());

        $profile->forceFill(['last_run_at' => now()])->save();

        // Laeuft nach dem Absenden der Antwort weiter; die Seite fragt den Stand ab.
        RunAiEventSearch::dispatchAfterResponse($search->id);

        $this->refreshAiSearch();
    }

    /**
     * Wird waehrend einer laufenden Suche in kurzen Abstaenden aufgerufen.
     */
    public function refreshAiSearch(): void
    {
        unset($this->latestAiSearch);
    }
}
