<?php

namespace App\Livewire\AdminV2\MasterData\RegionInfoSources;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Region;
use App\Models\RegionInfoSource;
use App\Models\Subregion;
use App\Support\AdminV2\RegionInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Quellen der KI-Recherche zu einer Region oder einem Teilgebiet – je Feld,
 * zum manuellen Gegenpruefen. Eigene Seite, damit die Quellen nirgends sonst
 * mitgeladen werden (Editor zeigt nur die Anzahl, App und API gar nichts).
 */
#[Layout('components.layouts.adminv2.app')]
class Show extends Component
{
    use AuthorizesAdminV2;

    public const TYPES = ['region' => Region::class, 'subregion' => Subregion::class];

    #[Locked]
    public string $type;

    #[Locked]
    public int $recordId;

    public function mount(string $type, int $id): void
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $this->type = $type;
        $this->recordId = (self::TYPES[$type])::withTrashed()->findOrFail($id)->getKey();
    }

    #[Computed]
    public function record(): Model
    {
        return (self::TYPES[$this->type])::withTrashed()->findOrFail($this->recordId);
    }

    /**
     * Je Feld (in der Reihenfolge des Editors) die Quellen, belegte zuerst;
     * zuletzt die nur besuchten Seiten.
     *
     * @return Collection<string, Collection<int, RegionInfoSource>>
     */
    #[Computed]
    public function groups(): Collection
    {
        $order = array_flip(['subregions', ...array_keys(RegionInfo::allTexts()), ...array_keys(RegionInfo::FACT_LABELS)]);

        return RegionInfoSource::query()
            ->where('sourceable_type', $this->record->getMorphClass())
            ->where('sourceable_id', $this->recordId)
            ->orderByDesc('cited')->orderBy('domain')->orderBy('id')
            ->get()
            ->groupBy(fn (RegionInfoSource $source) => (string) $source->field)
            ->sortBy(fn ($group, $field) => $field === '' ? PHP_INT_MAX : ($order[$field] ?? PHP_INT_MAX - 1));
    }

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            '' => 'Weitere besuchte Seiten',
            'subregions' => 'Suche nach Teilgebieten',
            default => RegionInfo::fieldLabels([$field]),
        };
    }

    public function editUrl(): string
    {
        return route('adminv2.master-data.'.$this->type.'s.edit', $this->recordId);
    }

    public function render()
    {
        $name = $this->record->getName('de');

        return view('livewire.admin-v2.master-data.region-info-sources.show', ['name' => $name])
            ->title('Quellen – '.$name);
    }
}
