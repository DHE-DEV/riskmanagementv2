<?php

namespace App\Livewire\AdminV2\MasterData\Airports;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\ManagesMasterDataList;
use App\Models\Airport;
use App\Models\AirportCode;
use App\Models\City;
use App\Models\Country;
use App\Support\AdminV2\MasterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Stammdaten > Flughaefen: die gepflegten Flughaefen mit Suche, Filtern,
 * Sortierung und Papierkorb.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Flughäfen')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesMasterDataList;

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    /** @var array<int, string> */
    #[Url(as: 'country')]
    public array $countryIds = [];

    #[Url(except: '')]
    public string $type = '';

    /** '' = alle, 'active', 'inactive' */
    #[Url(except: '')]
    public string $active = '';

    /** '' = alle, 'missing' = ohne Koordinaten */
    #[Url(except: '')]
    public string $coordinates = '';

    /** Kontinent (ID) ueber das Land des Flughafens */
    #[Url(except: '')]
    public string $continent = '';

    /** Pruefliste: 'no-airlines', 'unknown-iata', 'coordinates-off', 'stale' */
    #[Url(except: '')]
    public string $check = '';

    /** Besonderheit: '24h', 'lounges', 'hotels' */
    #[Url(except: '')]
    public string $feature = '';

    /** Ab wie vielen Kilometern Abstand zum Verzeichnis die Koordinaten als abweichend gelten. */
    public const COORDINATE_DEVIATION_KM = 50;

    /** Nach wie vielen Tagen ohne Aenderung ein Flughafen als lange nicht gepflegt gilt. */
    public const STALE_DAYS = 365;

    public const CHECKS = [
        'no-airlines' => 'Ohne verknüpfte Airline',
        'unknown-iata' => 'IATA-Code nicht im Verzeichnis',
        'coordinates-off' => 'Koordinaten weichen vom Verzeichnis ab',
        'stale' => 'Seit über einem Jahr unverändert',
    ];

    public const FEATURES = [
        '24h' => '24-Stunden-Betrieb',
        'lounges' => 'Mit Lounges',
        'hotels' => 'Mit Hotels',
    ];

    /**
     * Der Eintrag im Verzeichnis (airport_codes_1) zum IATA-Code des Flughafens.
     * Beide Tabellen haben unterschiedliche Kollationen – daher beidseitig angeglichen.
     */
    protected const CODE_MATCH = 'airport_codes_1.iata_code collate utf8mb4_unicode_ci = airports.iata_code collate utf8mb4_unicode_ci and airport_codes_1.deleted_at is null';

    protected function masterDataModel(): string
    {
        return Airport::class;
    }

    public function sortOptions(): array
    {
        return ['name' => 'Name', 'iata_code' => 'IATA-Code', 'city' => 'Stadt', 'country' => 'Land', 'type' => 'Typ', 'airlines_count' => 'Anzahl Airlines'];
    }

    protected function filterProperties(): array
    {
        return ['countryIds', 'type', 'active', 'coordinates', 'continent', 'check', 'feature'];
    }

    /**
     * Die Pruefung als Abfrage-Bedingung – fuer Liste und Kennzahlen gleich.
     */
    protected function applyCheck(Builder $query, string $check): Builder
    {
        return match ($check) {
            'no-airlines' => $query->whereDoesntHave('airlines'),
            'unknown-iata' => $query->whereNotExists(fn ($sub) => $sub->selectRaw('1')->from('airport_codes_1')->whereRaw(self::CODE_MATCH)),
            'coordinates-off' => $query->whereNotNull('airports.lat')->whereNotNull('airports.lng')->whereExists(fn ($sub) => $sub->selectRaw('1')->from('airport_codes_1')
                ->whereRaw(self::CODE_MATCH)
                ->whereNotNull('airport_codes_1.latitude_deg')
                ->whereRaw('ST_Distance_Sphere(point(airports.lng, airports.lat), point(airport_codes_1.longitude_deg, airport_codes_1.latitude_deg)) > ?', [self::COORDINATE_DEVIATION_KM * 1000])),
            'stale' => $query->where('airports.updated_at', '<', now()->subDays(self::STALE_DAYS)),
            default => $query,
        };
    }

    protected function applyFeature(Builder $query, string $feature): Builder
    {
        return match ($feature) {
            '24h' => $query->where('operates_24h', true),
            'lounges' => $query->whereNotNull('lounges')->whereRaw('json_length(lounges) > 0'),
            'hotels' => $query->whereNotNull('nearby_hotels')->whereRaw('json_length(nearby_hotels) > 0'),
            default => $query,
        };
    }

    protected function applyContinent(Builder $query, string $continent): Builder
    {
        if ($continent === '') {
            return $query;
        }

        return $query->whereIn('country_id', Country::query()->where('continent_id', (int) $continent)->select('id'));
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()->orderByRaw(MasterData::nameSql('countries'))->get(['id', 'iso_code', 'name_translations']);
    }

    /**
     * Kennzahlen ueber den gesamten Bestand – wie die Statistik-Kacheln des
     * bisherigen Admins: Bestand, Zugang, Datenqualitaet, Airline-Verknuepfungen.
     *
     * @return array{total: int, deleted: int, inactive: int, thisMonth: int, lastMonth: int, trend: ?float, withWebsite: int, quality: int, airlineLinks: int, airportsWithAirlines: int, lastDays: array<string, int>}
     */
    #[Computed]
    public function stats(): array
    {
        $total = Airport::count();
        $thisMonth = Airport::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $lastMonth = Airport::whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->count();
        $withWebsite = Airport::whereNotNull('website')->where('website', '<>', '')->count();

        // Neuanlagen der letzten sieben Tage, aeltester Tag zuerst.
        $perDay = Airport::query()
            ->where('created_at', '>=', today()->subDays(6))
            ->selectRaw('date(created_at) as day, count(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');
        $lastDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $lastDays[$day->format('d.m.')] = (int) ($perDay[$day->format('Y-m-d')] ?? 0);
        }

        // Pruefliste: je Punkt die Anzahl der betroffenen Flughaefen.
        $checks = [];
        foreach (array_keys(self::CHECKS) as $check) {
            $checks[$check] = $this->applyCheck(Airport::query(), $check)->count();
        }

        $features = [];
        foreach (array_keys(self::FEATURES) as $feature) {
            $features[$feature] = $this->applyFeature(Airport::query(), $feature)->count();
        }

        // Grosse Flughaefen mit Linienverkehr im Verzeichnis, die noch nicht gepflegt sind.
        $unmanaged = AirportCode::query()->unmanagedLarge()->count();

        // Nutzung: Flugsegmente in Reisen verweisen auf das Verzeichnis – ueber den IATA-Code zum gepflegten Flughafen.
        $usage = DB::selectOne(
            'select count(*) as segments, count(distinct airports.id) as airports
             from folder_flight_segments s
             join airport_codes_1 on airport_codes_1.id in (s.departure_airport_id, s.arrival_airport_id)
             join airports on '.self::CODE_MATCH.' and airports.deleted_at is null',
        );

        return [
            'total' => $total,
            'checks' => $checks,
            'features' => $features,
            'unmanaged' => $unmanaged,
            'countriesWithAirport' => Airport::query()->distinct()->count('country_id'),
            'countries' => Country::count(),
            'usageSegments' => (int) ($usage->segments ?? 0),
            'usageAirports' => (int) ($usage->airports ?? 0),
            'deleted' => Airport::onlyTrashed()->count(),
            'inactive' => Airport::where('is_active', false)->count(),
            'thisMonth' => $thisMonth,
            'lastMonth' => $lastMonth,
            'trend' => $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 1) : null,
            'withWebsite' => $withWebsite,
            'quality' => $total > 0 ? (int) round($withWebsite / $total * 100) : 0,
            'airlineLinks' => DB::table('airline_airport')->whereIn('airport_id', Airport::select('id'))->count(),
            'airportsWithAirlines' => DB::table('airline_airport')->whereIn('airport_id', Airport::select('id'))->distinct()->count('airport_id'),
            'lastDays' => $lastDays,
        ];
    }

    /**
     * Neu angelegte Flughaefen je Monat, die letzten sechs Monate.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function monthlyGrowth(): array
    {
        $from = now()->subMonthsNoOverflow(5)->startOfMonth();
        $perMonth = Airport::query()
            ->where('created_at', '>=', $from)
            ->selectRaw("date_format(created_at, '%Y-%m') as month, count(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonthsNoOverflow($i);
            $months[$month->translatedFormat('M Y')] = (int) ($perMonth[$month->format('Y-m')] ?? 0);
        }

        return $months;
    }

    /**
     * Datenvollstaendigkeit: Anteil der Flughaefen mit Angabe je Feld, in Prozent.
     *
     * @return array<string, array{count: int, percent: float}>
     */
    #[Computed]
    public function completeness(): array
    {
        $total = Airport::count();

        $fields = [
            'Website' => Airport::whereNotNull('website')->where('website', '<>', '')->count(),
            'Lounges' => Airport::whereNotNull('lounges')->whereRaw('json_length(lounges) > 0')->count(),
            'Hotels' => Airport::whereNotNull('nearby_hotels')->whereRaw('json_length(nearby_hotels) > 0')->count(),
            'Mobilität' => Airport::whereNotNull('mobility_options')->count(),
            'Security-URL' => Airport::whereNotNull('security_timeslot_url')->where('security_timeslot_url', '<>', '')->count(),
            'Koordinaten' => Airport::whereNotNull('lat')->whereNotNull('lng')->count(),
            'Zeitzone' => Airport::whereNotNull('timezone')->where('timezone', '<>', '')->count(),
        ];

        return array_map(fn (int $count) => [
            'count' => $count,
            'percent' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
        ], $fields);
    }

    /**
     * Verteilung auf die Kontinente (ueber das Land), groesste zuerst.
     *
     * @return array<int, array{id: int, label: string, count: int}>
     */
    #[Computed]
    public function byContinent(): array
    {
        return Airport::query()
            ->join('countries', 'countries.id', '=', 'airports.country_id')
            ->join('continents', 'continents.id', '=', 'countries.continent_id')
            ->selectRaw('continents.id, continents.name_translations, count(*) as count')
            ->groupBy('continents.id', 'continents.name_translations')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'label' => json_decode((string) $row->name_translations, true)['de'] ?? 'Kontinent '.$row->id,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Die fuenf Laender mit den meisten Flughaefen.
     *
     * @return array<int, array{id: int, label: string, count: int}>
     */
    #[Computed]
    public function topCountries(): array
    {
        return Airport::query()
            ->join('countries', 'countries.id', '=', 'airports.country_id')
            ->selectRaw('countries.id, countries.name_translations, count(*) as count')
            ->groupBy('countries.id', 'countries.name_translations')
            ->orderByDesc('count')
            ->orderByRaw(MasterData::nameSql('countries'))
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'label' => json_decode((string) $row->name_translations, true)['de'] ?? 'Land '.$row->id,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Verteilung auf die Typen.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function byType(): array
    {
        $counts = Airport::query()->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type');

        return collect(Airport::getTypeOptions())
            ->map(fn (string $label, string $type) => (int) ($counts[$type] ?? 0))
            ->filter()
            ->sortDesc()
            ->all();
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->masterDataModel()::query()
            ->with(['country' => fn ($query) => $query->withTrashed(), 'city' => fn ($query) => $query->withTrashed()])
            ->withCount('airlines');

        match ($this->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        if (($term = trim($this->search)) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn ($query) => $query
                ->where('airports.name', 'like', $like)
                ->orWhere('airports.iata_code', 'like', $like)
                ->orWhere('airports.icao_code', 'like', $like)
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('cities')->whereColumn('cities.id', 'airports.city_id')->whereRaw(MasterData::nameSql('cities').' LIKE ?', [$like])));
        }

        if ($countryIds = array_filter(array_map('intval', $this->countryIds))) {
            $query->whereIn('country_id', $countryIds);
        }

        if ($this->type !== '' && isset(Airport::getTypeOptions()[$this->type])) {
            $query->where('type', $this->type);
        }

        match ($this->active) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => null,
        };

        if ($this->coordinates === 'missing') {
            $query->where(fn ($query) => $query->whereNull('lat')->orWhereNull('lng'));
        }

        $this->applyContinent($query, $this->continent);
        $this->applyCheck($query, $this->check);
        $this->applyFeature($query, $this->feature);

        $direction = $this->sortDirection();

        match ($this->sortColumn()) {
            'iata_code' => $query->orderBy('iata_code', $direction),
            'city' => $query->orderBy(City::query()->withTrashed()->selectRaw(MasterData::nameSql('cities'))->whereColumn('cities.id', 'airports.city_id')->limit(1), $direction),
            'country' => $query->orderBy(Country::query()->withTrashed()->selectRaw(MasterData::nameSql('countries'))->whereColumn('countries.id', 'airports.country_id')->limit(1), $direction),
            'type' => $query->orderBy('type', $direction),
            'airlines_count' => $query->orderBy('airlines_count', $direction),
            default => $query->orderBy('name', $direction),
        };

        return $query->orderBy('name')->orderBy('airports.id')->paginate(self::PER_PAGE);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.airports.index');
    }
}
