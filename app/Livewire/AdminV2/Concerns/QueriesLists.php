<?php

namespace App\Livewire\AdminV2\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Abfragen der Listen: Suche ueber mehrere Spalten und Blaettern.
 * Setzt Livewire\WithPagination voraus.
 */
trait QueriesLists
{
    /**
     * Suche wie im bisherigen Admin: jedes Wort muss vorkommen, darf aber in
     * einer anderen Spalte stehen – "Erika Muster" findet Vor- und Nachname.
     * Platzhalter des Nutzers gelten als Text.
     *
     * @param  array<int, string>  $columns
     */
    protected function whereEveryWord(Builder $query, string $term, array $columns): void
    {
        foreach (preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';

            $query->where(function ($query) use ($columns, $like) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', $like);
                }
            });
        }
    }

    /**
     * Seite einer Liste. Liegt die gewaehlte Seite hinter dem Ende – etwa weil
     * die letzten Eintraege der letzten Seite geloescht wurden –, gilt die letzte.
     */
    protected function paginateWithinRange(Builder $query, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        $paginator = $query->paginate($perPage, ['*'], $pageName);

        if ($paginator->isEmpty() && $paginator->currentPage() > 1) {
            $this->setPage($paginator->lastPage(), $pageName);
            $paginator = $query->paginate($perPage, ['*'], $pageName);
        }

        return $paginator;
    }
}
