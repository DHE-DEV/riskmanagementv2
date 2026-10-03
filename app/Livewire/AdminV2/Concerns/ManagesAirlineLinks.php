<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\MasterDataChange;
use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Verknuepfungen Airline <-> Flughafen (Richtung, Terminal) – aus Sicht des
 * Flughafens (Airlines, die ihn anfliegen) oder der Airline (ihre Ziele).
 */
trait ManagesAirlineLinks
{
    /** Gewaehlter Partner fuer eine neue Verknuepfung */
    public string $linkId = '';

    public string $linkDirection = 'both';

    public string $linkTerminal = '';

    /** Partner, dessen Verknuepfung gerade bearbeitet wird */
    public ?int $editingLinkId = null;

    /**
     * Die Beziehung mit Pivot direction/terminal.
     */
    abstract protected function linkRelation(): ?BelongsToMany;

    /**
     * Alle waehlbaren Partner (id, label, code).
     *
     * @return array<int, array{value: int, label: string, code: ?string}>
     */
    abstract protected function linkOptions(): array;

    /**
     * Die vorhandenen Verknuepfungen, nach Name.
     */
    #[Computed]
    public function links(): Collection
    {
        return $this->linkRelation()?->orderBy('name')->get() ?? collect();
    }

    /**
     * @return array<int, array{value: int, label: string, code: ?string}>
     */
    #[Computed]
    public function availableLinkOptions(): array
    {
        $linked = $this->links->pluck('id')->flip();

        return array_values(array_filter($this->linkOptions(), fn (array $option) => ! $linked->has($option['value'])));
    }

    public function addLink(): void
    {
        $relation = $this->linkRelation();

        if (! $relation) {
            return;
        }

        $this->validate([
            'linkId' => ['required', 'integer'],
            'linkDirection' => ['required', 'in:'.implode(',', array_keys(MasterData::LINK_DIRECTIONS))],
            'linkTerminal' => ['nullable', 'string', 'max:50'],
        ], [
            'linkId.required' => 'Bitte einen Eintrag auswählen.',
        ]);

        $partner = collect($this->linkOptions())->firstWhere('value', (int) $this->linkId);

        if (! $partner) {
            $this->addError('linkId', 'Bitte einen Eintrag auswählen.');

            return;
        }

        $relation->syncWithoutDetaching([
            (int) $this->linkId => ['direction' => $this->linkDirection, 'terminal' => trim($this->linkTerminal) ?: null],
        ]);
        $this->logLinkChange($relation);

        $this->reset('linkId', 'linkDirection', 'linkTerminal');
        unset($this->links, $this->availableLinkOptions);

        $this->dispatch('adminv2-toast', message: '„'.$partner['label'].'“ verknüpft.');
    }

    public function editLink(int $id): void
    {
        $link = $this->links->firstWhere('id', $id);

        if (! $link) {
            return;
        }

        $this->editingLinkId = $id;
        $this->linkDirection = (string) ($link->pivot->direction ?? 'both');
        $this->linkTerminal = (string) ($link->pivot->terminal ?? '');
        $this->resetErrorBag();
    }

    public function cancelEditLink(): void
    {
        $this->editingLinkId = null;
        $this->reset('linkDirection', 'linkTerminal');
    }

    public function saveLink(): void
    {
        $relation = $this->linkRelation();

        if (! $relation || ! $this->editingLinkId) {
            return;
        }

        $this->validate([
            'linkDirection' => ['required', 'in:'.implode(',', array_keys(MasterData::LINK_DIRECTIONS))],
            'linkTerminal' => ['nullable', 'string', 'max:50'],
        ]);

        $relation->updateExistingPivot($this->editingLinkId, [
            'direction' => $this->linkDirection,
            'terminal' => trim($this->linkTerminal) ?: null,
        ]);
        $this->logLinkChange($relation);

        $this->cancelEditLink();
        unset($this->links);

        $this->dispatch('adminv2-toast', message: 'Verknüpfung gespeichert.');
    }

    public function removeLink(int $id): void
    {
        $relation = $this->linkRelation();

        if (! $relation) {
            return;
        }

        $link = $this->links->firstWhere('id', $id);
        $relation->detach($id);
        $this->logLinkChange($relation);

        if ($this->editingLinkId === $id) {
            $this->cancelEditLink();
        }

        unset($this->links, $this->availableLinkOptions);

        $this->dispatch('adminv2-toast', message: $link ? '„'.MasterData::recordLabel($link).'“ entfernt.' : 'Verknüpfung entfernt.');
    }

    /**
     * Verknuepfungen zaehlen als Aenderung am Eintrag.
     */
    protected function logLinkChange(BelongsToMany $relation): void
    {
        MasterData::logChange($relation->getParent(), MasterDataChange::ACTION_UPDATED, [$relation->getRelationName()]);
    }

    /**
     * Alle Airlines als Auswahl.
     *
     * @return array<int, array{value: int, label: string, code: ?string}>
     */
    protected function airlineOptions(): array
    {
        return Airline::query()->orderBy('name')->get(['id', 'name', 'iata_code'])
            ->map(fn (Airline $airline) => ['value' => $airline->id, 'label' => $airline->name, 'code' => $airline->iata_code])
            ->all();
    }

    /**
     * Alle Flughaefen als Auswahl.
     *
     * @return array<int, array{value: int, label: string, code: ?string}>
     */
    protected function airportOptions(): array
    {
        return Airport::query()->orderBy('name')->get(['id', 'name', 'iata_code'])
            ->map(fn (Airport $airport) => ['value' => $airport->id, 'label' => $airport->name, 'code' => $airport->iata_code])
            ->all();
    }
}
