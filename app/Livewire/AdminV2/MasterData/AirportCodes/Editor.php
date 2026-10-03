<?php

namespace App\Livewire\AdminV2\MasterData\AirportCodes;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsAirportExtras;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\ManagesAirlineLinks;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
use App\Models\AirportCode;
use App\Models\City;
use App\Models\Country;
use App\Support\AdminV2\Coordinates;
use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Flughafen-Codes: einen Eintrag des Verzeichnisses anlegen
 * oder bearbeiten.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsAirportExtras, EditsCoordinates, EditsMasterData, ManagesAirlineLinks, RunsAiChecks;

    public const SCHEDULED = ['yes' => 'Ja', 'no' => 'Nein'];

    public string $name = '';

    public string $countryId = '';

    public string $cityId = '';

    public string $isoCountry = '';

    public string $isoRegion = '';

    public string $municipality = '';

    public string $website = '';

    public string $securityTimeslotUrl = '';

    public bool $isActive = true;

    public bool $operates24h = false;

    public string $ident = '';

    public string $iataCode = '';

    public string $icaoCode = '';

    public string $gpsCode = '';

    public string $localCode = '';

    public string $type = 'medium_airport';

    public string $continent = '';

    public string $scheduledService = 'no';

    public string $elevationFt = '';

    public string $timezone = '';

    public string $dstTimezone = '';

    public string $homeLink = '';

    public string $wikipediaLink = '';

    public string $keywords = '';

    public string $source = '';

    public function mount(?int $airportCode = null): void
    {
        if ($airportCode === null) {
            $this->fillAirportExtras(null);

            return;
        }

        $record = AirportCode::withTrashed()->findOrFail($airportCode);

        $this->recordId = $record->id;
        $this->name = (string) $record->name;
        $this->countryId = (string) $record->country_id;
        $this->cityId = (string) $record->city_id;
        $this->isoCountry = (string) $record->iso_country;
        $this->isoRegion = (string) $record->iso_region;
        $this->municipality = (string) $record->municipality;
        $this->website = (string) $record->website;
        $this->securityTimeslotUrl = (string) $record->security_timeslot_url;
        $this->isActive = (bool) $record->is_active;
        $this->operates24h = (bool) $record->operates_24h;
        $this->ident = (string) $record->ident;
        $this->iataCode = (string) $record->iata_code;
        $this->icaoCode = (string) $record->icao_code;
        $this->gpsCode = (string) $record->gps_code;
        $this->localCode = (string) $record->local_code;
        $this->type = (string) ($record->type ?: 'medium_airport');
        $this->continent = (string) $record->continent;
        $this->scheduledService = $record->scheduled_service === 'yes' ? 'yes' : 'no';
        $this->elevationFt = $record->elevation_ft === null ? '' : (string) $record->elevation_ft;
        $this->timezone = (string) $record->timezone;
        $this->dstTimezone = (string) $record->dst_timezone;
        $this->homeLink = (string) $record->home_link;
        $this->wikipediaLink = (string) $record->wikipedia_link;
        $this->keywords = (string) $record->keywords;
        $this->source = (string) $record->source;
        $this->lat = Coordinates::format($record->latitude_deg);
        $this->lng = Coordinates::format($record->longitude_deg);
        $this->fillAirportExtras($record);
    }

    protected function masterDataModel(): string
    {
        return AirportCode::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.airport-codes';
    }

    protected function linkRelation(): ?BelongsToMany
    {
        return $this->record?->airlines();
    }

    protected function linkOptions(): array
    {
        return $this->airlineOptions();
    }

    public function updatedCountryId(): void
    {
        if ($this->cityId !== '' && ! $this->cityOptions->contains('id', (int) $this->cityId)) {
            $this->cityId = '';
        }

        // Der ISO-Code folgt dem verknuepften Land, solange er leer ist.
        if ($this->isoCountry === '' && ($country = $this->countryOptions->firstWhere('id', (int) $this->countryId))) {
            $this->isoCountry = (string) $country->iso_code;
        }
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()
            ->when($this->record?->country_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->orderByRaw(MasterData::nameSql('countries'))
            ->get(['id', 'iso_code', 'name_translations']);
    }

    #[Computed]
    public function cityOptions(): Collection
    {
        if ($this->countryId === '') {
            return collect();
        }

        return City::query()
            ->where('country_id', (int) $this->countryId)
            ->when($this->record?->city_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->orderByRaw(MasterData::nameSql('cities'))
            ->get(['id', 'name_translations', 'is_capital']);
    }

    public function save(bool $another = false): void
    {
        foreach (['ident', 'iataCode', 'icaoCode', 'gpsCode', 'localCode', 'isoCountry', 'isoRegion', 'continent'] as $property) {
            $this->{$property} = mb_strtoupper(trim($this->{$property}));
        }
        $this->elevationFt = str_replace(['.', ' '], '', trim($this->elevationFt));
        $this->normalizeCoordinates();
        unset($this->cityOptions);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'countryId' => ['nullable', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'cityId' => ['nullable', Rule::in($this->cityOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'isoCountry' => ['nullable', 'string', 'max:5'],
            'isoRegion' => ['nullable', 'string', 'max:10'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:2000'],
            'securityTimeslotUrl' => ['nullable', 'url', 'max:2000'],
            'ident' => ['required', 'string', 'max:10'],
            'iataCode' => ['nullable', 'string', 'max:10'],
            'icaoCode' => ['nullable', 'string', 'max:10'],
            'gpsCode' => ['nullable', 'string', 'max:10'],
            'localCode' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::in(array_keys(AirportCode::getTypeOptions()))],
            'continent' => ['nullable', Rule::in(array_keys(AirportCode::getContinentOptions()))],
            'scheduledService' => ['required', Rule::in(array_keys(self::SCHEDULED))],
            'elevationFt' => ['nullable', 'integer', 'min:-2000', 'max:30000'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'dstTimezone' => ['nullable', 'string', 'max:255'],
            'homeLink' => ['nullable', 'url', 'max:2000'],
            'wikipediaLink' => ['nullable', 'url', 'max:2000'],
            'keywords' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:50'],
        ] + $this->coordinateRules() + $this->airportExtrasRules(), [
            'name.required' => 'Bitte den Namen angeben.',
            'countryId.in' => 'Bitte ein Land wählen.',
            'cityId.in' => 'Diese Stadt gehört nicht zum gewählten Land.',
            'ident.required' => 'Bitte den Ident angeben – meist der ICAO-Code, sonst ein lokaler Code.',
            'website.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'securityTimeslotUrl.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'homeLink.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'wikipediaLink.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'elevationFt.integer' => 'Die Höhe ist eine ganze Zahl in Fuß.',
        ] + $this->coordinateMessages());

        $record = $this->record ?? new AirportCode;
        $created = ! $record->exists;
        $unchanged = $this->unchangedAirportExtras($this->record);
        $coordinates = $this->coordinateValues();

        $record->fill([
            'name' => trim($this->name),
            'country_id' => $this->countryId === '' ? null : (int) $this->countryId,
            'city_id' => $this->cityId === '' ? null : (int) $this->cityId,
            'iso_country' => $this->isoCountry ?: null,
            'iso_region' => $this->isoRegion ?: null,
            'municipality' => trim($this->municipality) ?: null,
            'website' => trim($this->website) ?: null,
            'security_timeslot_url' => trim($this->securityTimeslotUrl) ?: null,
            'is_active' => $this->isActive,
            'operates_24h' => $this->operates24h,
            'ident' => $this->ident,
            'iata_code' => $this->iataCode ?: null,
            'icao_code' => $this->icaoCode ?: null,
            'gps_code' => $this->gpsCode ?: null,
            'local_code' => $this->localCode ?: null,
            'type' => $this->type,
            'continent' => $this->continent ?: null,
            'scheduled_service' => $this->scheduledService,
            'latitude_deg' => $coordinates['lat'],
            'longitude_deg' => $coordinates['lng'],
            'elevation_ft' => $this->elevationFt === '' ? null : (int) $this->elevationFt,
            'timezone' => trim($this->timezone) ?: null,
            'dst_timezone' => trim($this->dstTimezone) ?: null,
            'home_link' => trim($this->homeLink) ?: null,
            'wikipedia_link' => trim($this->wikipediaLink) ?: null,
            'keywords' => trim($this->keywords) ?: null,
            'source' => trim($this->source) ?: ($created ? 'manual' : null),
        ] + $this->airportExtrasValues())->save();

        $this->fillAirportExtras($record);

        $this->finishSave($record, $created, $another, $unchanged);
    }

    protected function aiArea(): string
    {
        return 'airport-codes';
    }

    /**
     * Die aktuellen Formularwerte zu den Platzhaltern (siehe AiAreas).
     */
    protected function aiContext(): array
    {
        return [
            'name' => $this->name,
            'country' => $this->countryOptions->firstWhere('id', (int) $this->countryId)?->getName('de'),
            'city' => $this->cityOptions->firstWhere('id', (int) $this->cityId)?->getName('de'),
            'iso_country' => $this->isoCountry,
            'iso_region' => $this->isoRegion,
            'municipality' => $this->municipality,
            'website' => $this->website,
            'security_timeslot_url' => $this->securityTimeslotUrl,
            'is_active' => $this->isActive,
            'operates_24h' => $this->operates24h,
            'ident' => $this->ident,
            'iata_code' => $this->iataCode,
            'icao_code' => $this->icaoCode,
            'gps_code' => $this->gpsCode,
            'local_code' => $this->localCode,
            'type' => AirportCode::getTypeOptions()[$this->type] ?? $this->type,
            'continent' => AirportCode::getContinentOptions()[$this->continent] ?? $this->continent,
            'scheduled_service' => $this->scheduledService === 'yes',
            'lat' => $this->lat,
            'lng' => $this->lng,
            'elevation_ft' => $this->elevationFt,
            'timezone' => $this->timezone,
            'dst_timezone' => $this->dstTimezone,
            'home_link' => $this->homeLink,
            'wikipedia_link' => $this->wikipediaLink,
            'keywords' => $this->keywords,
            'source' => $this->source,
        ] + $this->airportExtrasContext() + [
            'airlines' => $this->links->map(fn ($airline) => $airline->name.($airline->iata_code ? ' ('.$airline->iata_code.')' : '').' – '.(MasterData::LINK_DIRECTIONS[$airline->pivot->direction] ?? $airline->pivot->direction).($airline->pivot->terminal ? ', Terminal '.$airline->pivot->terminal : ''))->all(),
        ];
    }

    /**
     * Vorschlag der KI-Feldpruefung in das Formular uebernehmen.
     */
    protected function aiApply(string $key, string $value): bool
    {
        switch ($key) {
            case 'name': $this->name = $value;

                return true;
            case 'iso_country': $this->isoCountry = mb_strtoupper($value);

                return true;
            case 'iso_region': $this->isoRegion = mb_strtoupper($value);

                return true;
            case 'municipality': $this->municipality = $value;

                return true;
            case 'website': $this->website = $value;

                return true;
            case 'security_timeslot_url': $this->securityTimeslotUrl = $value;

                return true;
            case 'is_active': $this->isActive = $this->aiBool($value);

                return true;
            case 'operates_24h': $this->operates24h = $this->aiBool($value);

                return true;
            case 'ident': $this->ident = mb_strtoupper($value);

                return true;
            case 'iata_code': $this->iataCode = mb_strtoupper($value);

                return true;
            case 'icao_code': $this->icaoCode = mb_strtoupper($value);

                return true;
            case 'gps_code': $this->gpsCode = mb_strtoupper($value);

                return true;
            case 'local_code': $this->localCode = mb_strtoupper($value);

                return true;
            case 'scheduled_service': $this->scheduledService = $this->aiBool($value) ? 'yes' : 'no';

                return true;
            case 'lat': $this->lat = $this->aiNumber($value);

                return true;
            case 'lng': $this->lng = $this->aiNumber($value);

                return true;
            case 'elevation_ft': $this->elevationFt = $this->aiNumber($value);

                return true;
            case 'timezone': $this->timezone = $value;

                return true;
            case 'dst_timezone': $this->dstTimezone = $value;

                return true;
            case 'home_link': $this->homeLink = $value;

                return true;
            case 'wikipedia_link': $this->wikipediaLink = $value;

                return true;
            case 'keywords': $this->keywords = $value;

                return true;
            case 'source': $this->source = $value;

                return true;
            case 'type':
                $type = $this->aiMatch(array_keys(AirportCode::getTypeOptions()), $value, fn ($type) => AirportCode::getTypeOptions()[$type])
                    ?? $this->aiMatch(array_keys(AirportCode::getTypeOptions()), $value, fn ($type) => $type);
                if ($type) {
                    $this->type = $type;
                }

                return $type !== null;
            case 'continent':
                $continent = $this->aiMatch(array_keys(AirportCode::getContinentOptions()), $value, fn ($code) => AirportCode::getContinentOptions()[$code])
                    ?? $this->aiMatch(array_keys(AirportCode::getContinentOptions()), $value, fn ($code) => $code);
                if ($continent) {
                    $this->continent = $continent;
                }

                return $continent !== null;
            case 'country':
                $country = $this->aiMatch($this->countryOptions, $value, fn ($country) => $country->getName('de'))
                    ?? $this->aiMatch($this->countryOptions, $value, fn ($country) => (string) $country->iso_code);
                if ($country) {
                    $this->countryId = (string) $country->id;
                    $this->updatedCountryId();
                }

                return $country !== null;
            case 'city':
                unset($this->cityOptions);
                $city = $this->aiMatch($this->cityOptions, $value, fn ($city) => $city->getName('de'))
                    ?? $this->aiMatch($this->cityOptions, $value, fn ($city) => $city->getName('en'));
                if ($city) {
                    $this->cityId = (string) $city->id;
                }

                return $city !== null;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.airport-codes.editor')
            ->title($this->record ? $this->record->name : 'Neuer Flughafen-Code');
    }
}
