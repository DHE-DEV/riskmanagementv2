<?php

namespace App\Livewire\AdminV2\MasterData\Airports;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsAirportExtras;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\ManagesAirlineLinks;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Support\AdminV2\AiAreas;
use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Flughaefen: einen Flughafen anlegen oder bearbeiten – samt
 * Lounges, Mobilitaet, Hotels und den Airlines, die ihn anfliegen.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsAirportExtras, EditsCoordinates, EditsMasterData, ManagesAirlineLinks, RunsAiChecks;

    public string $name = '';

    public string $countryId = '';

    public string $cityId = '';

    public string $iataCode = '';

    public string $icaoCode = '';

    public string $type = 'medium_airport';

    public string $website = '';

    public string $securityTimeslotUrl = '';

    public bool $isActive = true;

    public bool $operates24h = false;

    public string $altitude = '';

    public string $timezone = '';

    public string $dstTimezone = '';

    public function mount(?int $airport = null): void
    {
        if ($airport === null) {
            $this->fillAirportExtras(null);

            if ($city = City::find((int) request()->query('city'))) {
                $this->cityId = (string) $city->id;
                $this->countryId = (string) $city->country_id;
            } elseif (Country::whereKey((int) request()->query('country'))->exists()) {
                $this->countryId = (string) (int) request()->query('country');
            }

            return;
        }

        $record = Airport::withTrashed()->findOrFail($airport);

        $this->recordId = $record->id;
        $this->name = (string) $record->name;
        $this->countryId = (string) $record->country_id;
        $this->cityId = (string) $record->city_id;
        $this->iataCode = (string) $record->iata_code;
        $this->icaoCode = (string) $record->icao_code;
        $this->type = (string) ($record->type ?: 'medium_airport');
        $this->website = (string) $record->website;
        $this->securityTimeslotUrl = (string) $record->security_timeslot_url;
        $this->isActive = (bool) $record->is_active;
        $this->operates24h = (bool) $record->operates_24h;
        $this->altitude = $record->altitude === null ? '' : (string) $record->altitude;
        $this->timezone = (string) $record->timezone;
        $this->dstTimezone = (string) $record->dst_timezone;
        $this->fillCoordinates($record);
        $this->fillAirportExtras($record);
    }

    protected function masterDataModel(): string
    {
        return Airport::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.airports';
    }

    protected function createAnotherParameters(Model $record): array
    {
        return ['country' => $record->country_id];
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
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()
            ->when($this->record?->country_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->orderByRaw(MasterData::nameSql('countries'))
            ->get(['id', 'iso_code', 'name_translations']);
    }

    /**
     * Die Staedte des gewaehlten Landes.
     */
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
        $this->iataCode = mb_strtoupper(trim($this->iataCode));
        $this->icaoCode = mb_strtoupper(trim($this->icaoCode));
        $this->altitude = str_replace(['.', ' '], '', trim($this->altitude));
        $this->normalizeCoordinates();
        unset($this->cityOptions);

        $record = $this->record;
        $unique = fn (string $column, string $value) => ! $record || mb_strtoupper((string) $record->{$column}) !== $value
            ? Rule::unique('airports', $column)->ignore($this->recordId)
            : null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'countryId' => ['required', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'cityId' => ['required', Rule::in($this->cityOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'iataCode' => array_filter(['required', 'alpha_num', 'size:3', $unique('iata_code', $this->iataCode)]),
            'icaoCode' => array_filter(['required', 'alpha_num', 'size:4', $unique('icao_code', $this->icaoCode)]),
            'type' => ['required', Rule::in(array_keys(Airport::getTypeOptions()))],
            'website' => ['nullable', 'url', 'max:2000'],
            'securityTimeslotUrl' => ['nullable', 'url', 'max:2000'],
            'altitude' => ['nullable', 'integer', 'min:-500', 'max:10000'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'dstTimezone' => ['nullable', 'string', 'max:255'],
        ] + $this->coordinateRules() + $this->airportExtrasRules(), [
            'name.required' => 'Bitte den Namen angeben.',
            'countryId.required' => 'Bitte ein Land wählen.',
            'countryId.in' => 'Bitte ein Land wählen.',
            'cityId.required' => 'Bitte eine Stadt wählen – gibt es sie noch nicht, zuerst unter Städte anlegen.',
            'cityId.in' => 'Diese Stadt gehört nicht zum gewählten Land.',
            'iataCode.required' => 'Bitte den IATA-Code mit 3 Zeichen angeben.',
            'iataCode.size' => 'Der IATA-Code hat 3 Zeichen.',
            'iataCode.alpha_num' => 'Der IATA-Code besteht aus Buchstaben und Ziffern.',
            'iataCode.unique' => 'Diesen IATA-Code trägt bereits ein anderer Flughafen (auch der Papierkorb zählt).',
            'icaoCode.required' => 'Bitte den ICAO-Code mit 4 Zeichen angeben.',
            'icaoCode.size' => 'Der ICAO-Code hat 4 Zeichen.',
            'icaoCode.alpha_num' => 'Der ICAO-Code besteht aus Buchstaben und Ziffern.',
            'icaoCode.unique' => 'Diesen ICAO-Code trägt bereits ein anderer Flughafen (auch der Papierkorb zählt).',
            'website.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'securityTimeslotUrl.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'altitude.integer' => 'Die Höhe ist eine ganze Zahl in Metern.',
        ] + $this->coordinateMessages());

        $record ??= new Airport;
        $created = ! $record->exists;
        $unchanged = $this->unchangedAirportExtras($this->record);

        $record->fill([
            'name' => trim($this->name),
            'country_id' => (int) $this->countryId,
            'city_id' => (int) $this->cityId,
            'iata_code' => $this->iataCode,
            'icao_code' => $this->icaoCode,
            'type' => $this->type,
            'website' => trim($this->website) ?: null,
            'security_timeslot_url' => trim($this->securityTimeslotUrl) ?: null,
            'is_active' => $this->isActive,
            'operates_24h' => $this->operates24h,
            'altitude' => $this->altitude === '' ? null : (int) $this->altitude,
            'timezone' => trim($this->timezone) ?: null,
            'dst_timezone' => trim($this->dstTimezone) ?: null,
            'source' => $record->source ?? 'manual',
            'created_by' => $record->created_by ?? auth('web')->id(),
            'updated_by' => auth('web')->id(),
        ] + $this->coordinateValues() + $this->airportExtrasValues())->save();

        $this->fillAirportExtras($record);

        $this->finishSave($record, $created, $another, $unchanged);
    }

    protected function aiArea(): string
    {
        return 'airports';
    }

    /**
     * Die aktuellen Formularwerte zu den Platzhaltern (siehe AiAreas).
     */
    protected function aiContext(): array
    {
        return [
            'name' => $this->name,
            'iata_code' => $this->iataCode,
            'icao_code' => $this->icaoCode,
            'country' => $this->countryOptions->firstWhere('id', (int) $this->countryId)?->getName('de'),
            'city' => $this->cityOptions->firstWhere('id', (int) $this->cityId)?->getName('de'),
            'type' => Airport::getTypeOptions()[$this->type] ?? $this->type,
            'website' => $this->website,
            'security_timeslot_url' => $this->securityTimeslotUrl,
            'is_active' => $this->isActive,
            'operates_24h' => $this->operates24h,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'altitude' => $this->altitude,
            'timezone' => $this->timezone,
            'dst_timezone' => $this->dstTimezone,
        ] + $this->airportExtrasContext() + [
            'airlines' => $this->links->map(fn ($airline) => $airline->name.($airline->iata_code ? ' ('.$airline->iata_code.')' : '').' – '.(MasterData::LINK_DIRECTIONS[$airline->pivot->direction] ?? $airline->pivot->direction).($airline->pivot->terminal ? ', Terminal '.$airline->pivot->terminal : ''))->all(),
        ];
    }

    /**
     * Vorschlag der KI-Feldpruefung in das Formular uebernehmen.
     */
    /**
     * Lounges und Hotels werden je Eintrag und Feld geprueft.
     */
    protected function aiReviewLabels(string $section): array
    {
        return $this->airportExtrasReviewLabels($section) ?? AiAreas::placeholders($this->aiArea(), $section);
    }

    protected function aiReviewHint(string $section): ?string
    {
        return $this->airportExtrasReviewHint($section);
    }

    protected function aiApply(string $key, string $value): bool
    {
        if (($applied = $this->aiApplyAirportExtras($key, $value)) !== null) {
            return $applied;
        }

        switch ($key) {
            case 'name': $this->name = $value;

                return true;
            case 'iata_code': $this->iataCode = mb_strtoupper($value);

                return true;
            case 'icao_code': $this->icaoCode = mb_strtoupper($value);

                return true;
            case 'website': $this->website = $value;

                return true;
            case 'security_timeslot_url': $this->securityTimeslotUrl = $value;

                return true;
            case 'is_active': $this->isActive = $this->aiBool($value);

                return true;
            case 'operates_24h': $this->operates24h = $this->aiBool($value);

                return true;
            case 'lat': $this->lat = $this->aiNumber($value);

                return true;
            case 'lng': $this->lng = $this->aiNumber($value);

                return true;
            case 'altitude': $this->altitude = $this->aiNumber($value);

                return true;
            case 'timezone': $this->timezone = $value;

                return true;
            case 'dst_timezone': $this->dstTimezone = $value;

                return true;
            case 'type':
                $type = $this->aiMatch(array_keys(Airport::getTypeOptions()), $value, fn ($type) => Airport::getTypeOptions()[$type])
                    ?? $this->aiMatch(array_keys(Airport::getTypeOptions()), $value, fn ($type) => $type);
                if ($type) {
                    $this->type = $type;
                }

                return $type !== null;
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
        return view('livewire.admin-v2.master-data.airports.editor')
            ->title($this->record ? $this->record->name : 'Neuer Flughafen');
    }
}
