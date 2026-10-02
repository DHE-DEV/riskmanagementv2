<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Gemeinsames der Stammdaten-Bearbeitungsseiten: der geoeffnete Eintrag
 * (auch aus dem Papierkorb), Rueckmeldung nach dem Speichern, Loeschen.
 */
trait EditsMasterData
{
    use DeletesMasterData;

    /** Geoeffneter Eintrag; null = neuer Eintrag. */
    #[Locked]
    public ?int $recordId = null;

    /**
     * Routen-Name des Bereichs, z. B. "adminv2.master-data.countries".
     */
    abstract protected function routeBase(): string;

    #[Computed]
    public function record(): ?Model
    {
        return $this->recordId ? $this->masterDataModel()::withTrashed()->findOrFail($this->recordId) : null;
    }

    /**
     * Vorbelegung fuer "Speichern & weitere anlegen", z. B. das Land.
     *
     * @return array<string, mixed>
     */
    protected function createAnotherParameters(Model $record): array
    {
        return [];
    }

    protected function finishSave(Model $record, bool $created, bool $another = false): void
    {
        if (! $created) {
            unset($this->record);
            $this->dispatch('adminv2-toast', message: 'Gespeichert.');

            return;
        }

        session()->flash('adminv2-toast', '„'.MasterData::recordLabel($record).'“ angelegt.');

        $another
            ? $this->redirectRoute($this->routeBase().'.create', $this->createAnotherParameters($record))
            : $this->redirectRoute($this->routeBase().'.edit', $record->getKey());
    }

    protected function afterMasterDataChange(string $action, Model $record): bool
    {
        if ($action === 'restored') {
            unset($this->record);

            return false;
        }

        $this->redirectRoute($this->routeBase().'.index');

        return true;
    }

    /**
     * Schlagwoerter: kommagetrennter Text -> Liste.
     *
     * @return array<int, string>|null
     */
    protected function tags(string $text): ?array
    {
        $tags = array_values(array_unique(array_filter(array_map('trim', explode(',', $text)), fn ($tag) => $tag !== '')));

        return $tags ?: null;
    }

    /**
     * Uebersetzungen mit neuem deutschen und englischen Namen; weitere
     * Sprachen bleiben stehen.
     *
     * @return array<string, string>
     */
    protected function translations(?array $existing, string $german, string $english): array
    {
        $translations = $existing ?? [];
        $translations['de'] = trim($german);

        if (trim($english) === '') {
            unset($translations['en']);
        } else {
            $translations['en'] = trim($english);
        }

        return $translations;
    }
}
