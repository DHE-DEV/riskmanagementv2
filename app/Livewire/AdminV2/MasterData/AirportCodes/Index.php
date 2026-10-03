<?php

namespace App\Livewire\AdminV2\MasterData\AirportCodes;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\AirportCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Flughafen-Codes: das vollstaendige Verzeichnis (ueber 80.000
 * Flugplaetze) mit Suche, Filtern, Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Flughafen-Codes')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesMasterDataList;

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $continent = '';

    /** Land als ISO-Code, z. B. DE */
    #[Url(except: '')]
    public string $isoCountry = '';

    /** '' = alle, 'yes', 'no' */
    #[Url(except: '')]
    public string $scheduled = '';

    /** '' = alle, 'iata' = mit IATA-Code, 'icao' = mit ICAO-Code, 'none' = ohne beides */
    #[Url(except: '')]
    public string $codes = '';

    /** '' = alle, 'active', 'inactive' */
    #[Url(except: '')]
    public string $active = '';

    /** 'unmanaged' = grosse Flughaefen mit Linienverkehr ohne gepflegten Flughafen */
    #[Url(except: '')]
    public string $managed = '';

    protected function masterDataModel(): string
    {
        return AirportCode::class;
    }

    public function sortOptions(): array
    {
        return ['name' => 'Name', 'ident' => 'Ident', 'iata_code' => 'IATA-Code', 'icao_code' => 'ICAO-Code', 'municipality' => 'Ort', 'iso_country' => 'Land', 'type' => 'Typ'];
    }

    protected function filterProperties(): array
    {
        return ['type', 'continent', 'isoCountry', 'scheduled', 'codes', 'active', 'managed'];
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->masterDataModel()::query();

        match ($this->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        if (($term = trim($this->search)) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $code = mb_strtoupper($term);
            $byCode = fn ($query) => $query->where('iata_code', $code)->orWhere('icao_code', $code)->orWhere('ident', $code);

            // Ein kurzer Begriff ist meist ein Code: trifft er einen, zaehlt nur das –
            // sonst wuerde "MUC" auch jeden Namen mit "muc" darin liefern.
            if (mb_strlen($term) <= 4 && (clone $query)->where($byCode)->exists()) {
                $query->where($byCode);
            } else {
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('municipality', 'like', $like)->orWhere('ident', 'like', $like));
            }
        }

        if ($this->type !== '' && isset(AirportCode::getTypeOptions()[$this->type])) {
            $query->where('type', $this->type);
        }

        if ($this->continent !== '' && isset(AirportCode::getContinentOptions()[$this->continent])) {
            $query->where('continent', $this->continent);
        }

        if (($iso = mb_strtoupper(trim($this->isoCountry))) !== '') {
            $query->where('iso_country', $iso);
        }

        if (in_array($this->scheduled, ['yes', 'no'], true)) {
            $query->where('scheduled_service', $this->scheduled);
        }

        match ($this->codes) {
            'iata' => $query->whereNotNull('iata_code')->where('iata_code', '<>', ''),
            'icao' => $query->whereNotNull('icao_code')->where('icao_code', '<>', ''),
            'none' => $query->where(fn ($query) => $query->whereNull('iata_code')->orWhere('iata_code', ''))
                ->where(fn ($query) => $query->whereNull('icao_code')->orWhere('icao_code', '')),
            default => null,
        };

        match ($this->active) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => null,
        };

        if ($this->managed === 'unmanaged') {
            $query->unmanagedLarge();
        }

        $direction = $this->sortDirection();
        $column = $this->sortColumn();

        // Leere Codes stehen immer am Ende.
        if (in_array($column, ['iata_code', 'icao_code'], true)) {
            $query->orderByRaw("({$column} is null or {$column} = '') asc");
        }

        return $query->orderBy($column, $direction)->orderBy('name')->orderBy('id')->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.airport-codes.index');
    }
}
