<?php

namespace App\Livewire\AdminV2\MasterData\Continents;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\Continent;
use App\Support\AdminV2\MasterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Kontinente: Liste mit Suche, Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Kontinente')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesMasterDataList;

    #[Url(except: 'sort_order')]
    public string $sort = 'sort_order';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    protected function masterDataModel(): string
    {
        return Continent::class;
    }

    public function sortOptions(): array
    {
        return ['sort_order' => 'Sortierung', 'name' => 'Name', 'code' => 'Code', 'countries_count' => 'Anzahl Länder'];
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->listQuery(['code'])->withCount('countries');
        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'name' => $query->orderByRaw(MasterData::nameSql('continents').' '.$direction),
            'code' => $query->orderBy('code', $direction),
            'countries_count' => $query->orderBy('countries_count', $direction),
            default => $query->orderBy('sort_order', $direction),
        };

        return $query->orderByRaw(MasterData::nameSql('continents'))->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.continents.index');
    }
}
