<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Models\AiEventSuggestion;
use App\Services\AiEventSearchService;

/**
 * Aktionen an KI-Vorschlaegen: als Entwurf anlegen, verwerfen, wieder
 * vorschlagen – fuer die Ereignisliste und die Seite "KI Suchergebnisse".
 */
trait HandlesAiSuggestions
{
    /**
     * Die zwischengespeicherten Listen der Vorschlaege verwerfen.
     */
    abstract protected function forgetAiSuggestions(): void;

    /**
     * Aus einem KI-Vorschlag ein Ereignis als Entwurf anlegen und oeffnen.
     */
    public function createDraftFromSuggestion(int $suggestionId)
    {
        $suggestion = AiEventSuggestion::query()->open()->find($suggestionId);

        if (! $suggestion) {
            $this->dispatch('adminv2-toast', message: 'Dieser Vorschlag ist nicht mehr offen.', variant: 'danger');
            $this->forgetAiSuggestions();

            return;
        }

        $event = app(AiEventSearchService::class)->createDraft($suggestion, auth('web')->id());

        session()->flash('adminv2-toast', 'Entwurf aus dem KI-Vorschlag angelegt. Bitte Standort, Text und Quellen prüfen.');

        return $this->redirectRoute('adminv2.events.edit', $event);
    }

    public function dismissSuggestion(int $suggestionId): void
    {
        AiEventSuggestion::query()->open()->whereKey($suggestionId)->update([
            'status' => AiEventSuggestion::STATUS_DISMISSED,
            'handled_by' => auth('web')->id(),
        ]);

        $this->forgetAiSuggestions();

        $this->dispatch('adminv2-toast', message: 'Vorschlag verworfen – er wird nicht erneut vorgeschlagen.');
    }

    /**
     * Einen verworfenen Vorschlag wieder oeffnen.
     */
    public function restoreSuggestion(int $suggestionId): void
    {
        AiEventSuggestion::query()
            ->where('status', AiEventSuggestion::STATUS_DISMISSED)
            ->whereKey($suggestionId)
            ->update(['status' => AiEventSuggestion::STATUS_NEW, 'handled_by' => null]);

        $this->forgetAiSuggestions();

        $this->dispatch('adminv2-toast', message: 'Der Vorschlag ist wieder offen.');
    }
}
