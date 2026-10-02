<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Gemeinsames der Stammdaten-Listen: Suche, Papierkorb, Sortierung, Seiten.
 *
 * Die Liste selbst liefert jede Seite als berechnete Eigenschaft "rows";
 * Sortierspalte ($sort) und Richtung ($direction) legt sie selbst fest.
 */
trait ManagesMasterDataList
{
    use DeletesMasterData, WithPagination;

    public const PER_PAGE = 25;

    #[Url(except: '')]
    public string $search = '';

    /** '' = ohne Papierkorb, 'with' = mit, 'only' = nur Papierkorb */
    #[Url(except: '')]
    public string $trashed = '';

    /**
     * Spalten, nach denen sortiert werden darf.
     *
     * @return array<int, string>
     */
    abstract protected function sortable(): array;

    /**
     * Namen der Filter dieser Liste (ohne Suche und Papierkorb).
     *
     * @return array<int, string>
     */
    protected function filterProperties(): array
    {
        return [];
    }

    public function updatedManagesMasterDataList(string $property): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, $this->sortable(), true)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'trashed', ...$this->filterProperties()]);
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        if ($this->search !== '' || $this->trashed !== '') {
            return true;
        }

        foreach ($this->filterProperties() as $property) {
            if (! in_array($this->{$property}, ['', [], false, null], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Abfrage mit Papierkorb-Filter und Suche.
     *
     * @param  array<int, string>  $searchColumns  weitere Spalten neben dem Namen
     */
    protected function listQuery(array $searchColumns = []): Builder
    {
        $query = $this->masterDataModel()::query();

        match ($this->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        return MasterData::search($query, $this->search, $searchColumns);
    }

    protected function sortDirection(): string
    {
        return $this->direction === 'desc' ? 'desc' : 'asc';
    }

    protected function sortColumn(): string
    {
        return in_array($this->sort, $this->sortable(), true) ? $this->sort : $this->sortable()[0];
    }

    protected function afterMasterDataChange(string $action, Model $record): bool
    {
        unset($this->rows);

        return false;
    }
}
