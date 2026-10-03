<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Models\MasterDataChange;
use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Loeschen, Wiederherstellen und endgueltiges Loeschen von Stammdaten – fuer
 * Liste und Bearbeitungsseite gleich.
 *
 * "Loeschen" legt den Eintrag in den Papierkorb (er laesst sich
 * wiederherstellen). Endgueltig loeschen geht nur aus dem Papierkorb und nur,
 * solange nichts mehr an dem Eintrag haengt.
 */
trait DeletesMasterData
{
    /** Eintrag, zu dem gerade die Rueckfrage offen ist. */
    #[Locked]
    public ?int $pendingDeleteId = null;

    #[Locked]
    public bool $pendingForce = false;

    /**
     * @return class-string<Model>
     */
    abstract protected function masterDataModel(): string;

    /**
     * Nach einer Aenderung: Liste neu laden bzw. die Seite verlassen.
     * Liefert true, wenn dabei weitergeleitet wurde.
     */
    abstract protected function afterMasterDataChange(string $action, Model $record): bool;

    protected function findMasterData(int $id): Model
    {
        return $this->masterDataModel()::withTrashed()->findOrFail($id);
    }

    public function confirmDelete(int $id, bool $force = false): void
    {
        $record = $this->findMasterData($id);

        $this->pendingDeleteId = $record->getKey();
        // Endgueltig loeschen laesst sich nur, was schon im Papierkorb liegt.
        $this->pendingForce = $force && $record->trashed();
        unset($this->pendingDelete);

        $this->modal('master-data-delete')->show();
    }

    /**
     * @return array{label: string, force: bool, dependents: array<string, int>}|null
     */
    #[Computed]
    public function pendingDelete(): ?array
    {
        if (! $this->pendingDeleteId) {
            return null;
        }

        $record = $this->masterDataModel()::withTrashed()->find($this->pendingDeleteId);

        return $record ? [
            'label' => MasterData::recordLabel($record),
            'force' => $this->pendingForce,
            'dependents' => MasterData::dependents($record),
        ] : null;
    }

    public function deleteConfirmed(): void
    {
        if (! $this->pendingDeleteId) {
            return;
        }

        $record = $this->findMasterData($this->pendingDeleteId);
        $label = MasterData::recordLabel($record);
        $force = $this->pendingForce;

        $this->modal('master-data-delete')->close();
        $this->pendingDeleteId = null;
        $this->pendingForce = false;
        unset($this->pendingDelete);

        if (! $force) {
            $record->delete();
            MasterData::logChange($record, MasterDataChange::ACTION_DELETED);
            $this->finishMasterDataChange('deleted', $record, '„'.$label.'“ liegt jetzt im Papierkorb.');

            return;
        }

        if (MasterData::dependents($record) !== []) {
            $this->dispatch('adminv2-toast', message: '„'.$label.'“ lässt sich nicht endgültig löschen, solange noch Einträge daran hängen.', variant: 'danger');

            return;
        }

        try {
            $record->forceDelete();
            MasterData::logChange($record, MasterDataChange::ACTION_FORCE_DELETED);
        } catch (QueryException) {
            $this->dispatch('adminv2-toast', message: '„'.$label.'“ wird an anderer Stelle noch verwendet und lässt sich nicht endgültig löschen.', variant: 'danger');

            return;
        }

        $this->finishMasterDataChange('force-deleted', $record, '„'.$label.'“ endgültig gelöscht.');
    }

    public function restore(int $id): void
    {
        $record = $this->findMasterData($id);

        if (! $record->trashed()) {
            return;
        }

        $record->restore();
        MasterData::logChange($record, MasterDataChange::ACTION_RESTORED);

        $this->finishMasterDataChange('restored', $record, '„'.MasterData::recordLabel($record).'“ wiederhergestellt.');
    }

    /**
     * Rueckmeldung zeigen – nach einer Weiterleitung aus der Session.
     */
    protected function finishMasterDataChange(string $action, Model $record, string $message): void
    {
        if ($this->afterMasterDataChange($action, $record)) {
            session()->flash('adminv2-toast', $message);

            return;
        }

        $this->dispatch('adminv2-toast', message: $message);
    }
}
