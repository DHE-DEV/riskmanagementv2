<?php

namespace App\Livewire\AdminV2\CustomerManagement\PluginClients;

use App\Livewire\AdminV2\Concerns\QueriesLists;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Gemeinsames der Plugin-Listen (Plugin-Kunden, ausstehende Registrierungen):
 * Suche, Sortierung, Seiten und die Auswahl mehrerer Eintraege zum Loeschen.
 *
 * Die Liste selbst liefert jede Seite als berechnete Eigenschaft "rows";
 * Sortierspalte ($sort) und Richtung ($direction) legt sie selbst fest.
 */
trait ManagesPluginList
{
    use QueriesLists, WithPagination;

    public const PER_PAGE = 25;

    #[Url(except: '')]
    public string $search = '';

    /** @var array<int, string> IDs der angehakten Eintraege */
    public array $selected = [];

    /**
     * Sortierungen: Spalte => Bezeichnung; die erste ist die Vorgabe.
     *
     * @return array<string, string>
     */
    abstract public function sortOptions(): array;

    /**
     * Filter dieser Liste (ohne Suche): Eigenschaft => Wert ohne Filter.
     *
     * @return array<string, mixed>
     */
    abstract protected function filterDefaults(): array;

    /**
     * Die angehakten Eintraege loeschen; liefert die Anzahl.
     *
     * @param  array<int, int>  $ids
     */
    abstract protected function deleteRecords(array $ids): int;

    public function updatedManagesPluginList(string $property): void
    {
        // Die Auswahl selbst aendert nichts an der Liste.
        if ($property === 'selected' || str_starts_with($property, 'selected.')) {
            return;
        }

        $this->selected = [];
        $this->resetPage();
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selected', ...array_keys($this->filterDefaults())]);
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        if ($this->search !== '') {
            return true;
        }

        foreach ($this->filterDefaults() as $property => $default) {
            if ($this->{$property} !== $default) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alle Eintraege der aktuellen Seite anhaken.
     */
    public function selectPage(): void
    {
        $this->selected = collect($this->rows->items())->map(fn ($row) => (string) $row->id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function deleteSelected(): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selected))));
        $count = $ids === [] ? 0 : $this->deleteRecords($ids);

        $this->selected = [];
        unset($this->rows);

        $this->dispatch('adminv2-toast', message: $count === 1 ? '1 Eintrag gelöscht.' : $count.' Einträge gelöscht.');
    }

    protected function sortDirection(): string
    {
        return $this->direction === 'asc' ? 'asc' : 'desc';
    }

    protected function sortColumn(): string
    {
        $sortable = array_keys($this->sortOptions());

        return in_array($this->sort, $sortable, true) ? $this->sort : $sortable[0];
    }

    /**
     * Suchbegriff als LIKE-Muster – Platzhalter des Nutzers gelten als Text.
     */
    protected function likeTerm(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
