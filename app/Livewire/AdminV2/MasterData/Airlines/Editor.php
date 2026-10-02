<?php

namespace App\Livewire\AdminV2\MasterData\Airlines;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\ManagesAirlineLinks;
use App\Models\Airline;
use App\Models\Country;
use App\Support\AdminV2\MasterData;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Airlines: eine Airline anlegen oder bearbeiten – Codes,
 * Kontakt, Kabinenklassen, Gepaeckregeln, Haustiermitnahme und die
 * Flughaefen, die sie anfliegt.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsMasterData, ManagesAirlineLinks;

    public const PET_RESTRICTIONS = [
        'specific_species' => 'Nur bestimmte Tierarten',
        'breed_restrictions' => 'Rasseeinschränkungen',
        'specific_routes' => 'Nur bestimmte Strecken',
        'temperature_restrictions' => 'Temperaturabhängige Einschränkungen',
        'service_animals_allowed' => 'Assistenztiere erlaubt',
    ];

    public const CONTACT_FIELDS = [
        'hotline' => 'Hotline',
        'email' => 'E-Mail',
        'chat_url' => 'Chat-URL',
        'help_url' => 'Hilfe-URL',
    ];

    public string $name = '';

    public string $iataCode = '';

    public string $icaoCode = '';

    public string $homeCountryId = '';

    public string $headquarters = '';

    public bool $isActive = true;

    public string $website = '';

    public string $bookingUrl = '';

    /** @var array<string, string> hotline, email, chat_url, help_url */
    public array $contact = ['hotline' => '', 'email' => '', 'chat_url' => '', 'help_url' => ''];

    /** @var array<int, string> */
    public array $cabinClasses = [];

    /** @var array<string, string> Freigepaeck je Klasse */
    public array $checkedBaggage = [];

    /** @var array<string, string> Handgepaeck (Gewicht) je Klasse */
    public array $handBaggage = [];

    /** @var array<string, array{length: string, width: string, height: string}> */
    public array $handDimensions = [];

    public string $handBaggageNotes = '';

    public string $handBaggageInfoUrl = '';

    public bool $petsAllowed = false;

    /** @var array<string, mixed> */
    public array $petCabin = ['allowed' => false, 'max_weight' => '', 'weight_includes_bag' => false, 'carrier_length' => '', 'carrier_width' => '', 'carrier_height' => '', 'advance_notice_required' => false, 'notes' => ''];

    /** @var array<string, mixed> */
    public array $petHold = ['allowed' => false, 'max_weight' => '', 'advance_notice_required' => false, 'notes' => ''];

    /** @var array<int, string> */
    public array $petRestrictions = [];

    public string $petInfoUrl = '';

    public string $petNotes = '';

    public function mount(?int $airline = null): void
    {
        $this->fillBaggage(null);

        if ($airline === null) {
            return;
        }

        $record = Airline::withTrashed()->findOrFail($airline);

        $this->recordId = $record->id;
        $this->name = (string) $record->name;
        $this->iataCode = (string) $record->iata_code;
        $this->icaoCode = (string) $record->icao_code;
        $this->homeCountryId = (string) $record->home_country_id;
        $this->headquarters = (string) $record->headquarters;
        $this->isActive = (bool) $record->is_active;
        $this->website = (string) $record->website;
        $this->bookingUrl = (string) $record->booking_url;

        foreach (array_keys(self::CONTACT_FIELDS) as $field) {
            $this->contact[$field] = (string) ($record->contact_info[$field] ?? '');
        }

        $this->cabinClasses = array_values(array_intersect(array_map('strval', $record->cabin_classes ?? []), array_keys(Airline::getCabinClassOptions())));
        $this->fillBaggage($record->baggage_rules);

        $pets = $record->pet_policy ?? [];
        $this->petsAllowed = (bool) ($pets['allowed'] ?? false);
        foreach ($this->petCabin as $field => $default) {
            $this->petCabin[$field] = is_bool($default) ? (bool) ($pets['in_cabin'][$field] ?? false) : (string) ($pets['in_cabin'][$field] ?? '');
        }
        foreach ($this->petHold as $field => $default) {
            $this->petHold[$field] = is_bool($default) ? (bool) ($pets['in_hold'][$field] ?? false) : (string) ($pets['in_hold'][$field] ?? '');
        }
        $this->petRestrictions = array_values(array_intersect(array_map('strval', $pets['restrictions'] ?? []), array_keys(self::PET_RESTRICTIONS)));
        $this->petInfoUrl = (string) ($pets['info_url'] ?? '');
        $this->petNotes = (string) ($pets['notes'] ?? '');
    }

    protected function fillBaggage(?array $rules): void
    {
        foreach (array_keys(Airline::getCabinClassOptions()) as $class) {
            $this->checkedBaggage[$class] = (string) ($rules['checked_baggage'][$class] ?? '');
            $this->handBaggage[$class] = (string) ($rules['hand_baggage'][$class] ?? '');
            foreach (['length', 'width', 'height'] as $side) {
                $this->handDimensions[$class][$side] = (string) ($rules['hand_baggage_dimensions'][$class][$side] ?? '');
            }
        }

        $this->handBaggageNotes = (string) ($rules['hand_baggage_notes'] ?? '');
        $this->handBaggageInfoUrl = (string) ($rules['hand_baggage_info_url'] ?? '');
    }

    protected function masterDataModel(): string
    {
        return Airline::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.airlines';
    }

    protected function linkRelation(): ?BelongsToMany
    {
        return $this->record?->airports();
    }

    protected function linkOptions(): array
    {
        return $this->airportOptions();
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()
            ->when($this->record?->home_country_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->orderByRaw(MasterData::nameSql('countries'))
            ->get(['id', 'iso_code', 'name_translations']);
    }

    public function save(bool $another = false): void
    {
        $this->iataCode = mb_strtoupper(trim($this->iataCode));
        $this->icaoCode = mb_strtoupper(trim($this->icaoCode));

        $record = $this->record;
        $unique = fn (string $column, string $value) => $value !== '' && (! $record || mb_strtoupper((string) $record->{$column}) !== $value)
            ? Rule::unique('airlines', $column)->ignore($this->recordId)
            : null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'iataCode' => array_filter(['nullable', 'alpha_num', 'size:2', $unique('iata_code', $this->iataCode)]),
            'icaoCode' => array_filter(['nullable', 'alpha', 'size:3', $unique('icao_code', $this->icaoCode)]),
            'homeCountryId' => ['nullable', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'headquarters' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'bookingUrl' => ['nullable', 'url', 'max:255'],
            'contact.hotline' => ['nullable', 'string', 'max:100'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.chat_url' => ['nullable', 'url', 'max:2000'],
            'contact.help_url' => ['nullable', 'url', 'max:2000'],
            'cabinClasses' => ['array'],
            'cabinClasses.*' => [Rule::in(array_keys(Airline::getCabinClassOptions()))],
            'checkedBaggage.*' => ['nullable', 'string', 'max:100'],
            'handBaggage.*' => ['nullable', 'string', 'max:100'],
            'handDimensions.*.*' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'handBaggageNotes' => ['nullable', 'string', 'max:5000'],
            'handBaggageInfoUrl' => ['nullable', 'url', 'max:2000'],
            'petCabin.max_weight' => ['nullable', 'string', 'max:50'],
            'petCabin.carrier_length' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'petCabin.carrier_width' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'petCabin.carrier_height' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'petCabin.notes' => ['nullable', 'string', 'max:5000'],
            'petHold.max_weight' => ['nullable', 'string', 'max:50'],
            'petHold.notes' => ['nullable', 'string', 'max:5000'],
            'petRestrictions.*' => [Rule::in(array_keys(self::PET_RESTRICTIONS))],
            'petInfoUrl' => ['nullable', 'url', 'max:2000'],
            'petNotes' => ['nullable', 'string', 'max:5000'],
        ], [
            'name.required' => 'Bitte den Namen der Airline angeben.',
            'iataCode.size' => 'Der IATA-Code hat 2 Zeichen, z. B. LH.',
            'iataCode.alpha_num' => 'Der IATA-Code besteht aus Buchstaben und Ziffern.',
            'iataCode.unique' => 'Diesen IATA-Code trägt bereits eine andere Airline (auch der Papierkorb zählt).',
            'icaoCode.size' => 'Der ICAO-Code hat 3 Buchstaben, z. B. DLH.',
            'icaoCode.alpha' => 'Der ICAO-Code besteht aus Buchstaben.',
            'icaoCode.unique' => 'Diesen ICAO-Code trägt bereits eine andere Airline (auch der Papierkorb zählt).',
            'homeCountryId.in' => 'Bitte ein Land wählen.',
            'website.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'bookingUrl.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'contact.email.email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'contact.chat_url.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'contact.help_url.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'handDimensions.*.*.numeric' => 'Maße in Zentimetern als Zahl angeben.',
            'handBaggageInfoUrl.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'petCabin.carrier_length.numeric' => 'Maße in Zentimetern als Zahl angeben.',
            'petCabin.carrier_width.numeric' => 'Maße in Zentimetern als Zahl angeben.',
            'petCabin.carrier_height.numeric' => 'Maße in Zentimetern als Zahl angeben.',
            'petInfoUrl.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
        ]);

        $record ??= new Airline;
        $created = ! $record->exists;
        $text = fn ($value) => trim((string) $value) === '' ? null : trim((string) $value);
        $number = fn ($value) => is_numeric($value) ? (float) $value : null;

        $baggage = ['hand_baggage' => [], 'checked_baggage' => [], 'hand_baggage_dimensions' => []];
        foreach (array_keys(Airline::getCabinClassOptions()) as $class) {
            $baggage['checked_baggage'][$class] = $text($this->checkedBaggage[$class] ?? '');
            $baggage['hand_baggage'][$class] = $text($this->handBaggage[$class] ?? '');
            foreach (['length', 'width', 'height'] as $side) {
                $baggage['hand_baggage_dimensions'][$class][$side] = $number($this->handDimensions[$class][$side] ?? '');
            }
        }
        $baggage['hand_baggage_notes'] = $text($this->handBaggageNotes);
        $baggage['hand_baggage_info_url'] = $text($this->handBaggageInfoUrl);

        $pets = ['allowed' => $this->petsAllowed];
        if ($this->petsAllowed) {
            $pets['in_cabin'] = [
                'allowed' => (bool) $this->petCabin['allowed'],
                'max_weight' => $text($this->petCabin['max_weight']),
                'weight_includes_bag' => (bool) $this->petCabin['weight_includes_bag'],
                'carrier_length' => $number($this->petCabin['carrier_length']),
                'carrier_width' => $number($this->petCabin['carrier_width']),
                'carrier_height' => $number($this->petCabin['carrier_height']),
                'advance_notice_required' => (bool) $this->petCabin['advance_notice_required'],
                'notes' => $text($this->petCabin['notes']),
            ];
            $pets['in_hold'] = [
                'allowed' => (bool) $this->petHold['allowed'],
                'max_weight' => $text($this->petHold['max_weight']),
                'advance_notice_required' => (bool) $this->petHold['advance_notice_required'],
                'notes' => $text($this->petHold['notes']),
            ];
            $pets['restrictions'] = array_values($this->petRestrictions);
            $pets['info_url'] = $text($this->petInfoUrl);
            $pets['notes'] = $text($this->petNotes);
        }

        $record->fill([
            'name' => trim($this->name),
            'iata_code' => $this->iataCode ?: null,
            'icao_code' => $this->icaoCode ?: null,
            'home_country_id' => $this->homeCountryId === '' ? null : (int) $this->homeCountryId,
            'headquarters' => $text($this->headquarters),
            'is_active' => $this->isActive,
            'website' => $text($this->website),
            'booking_url' => $text($this->bookingUrl),
            'contact_info' => array_map($text, array_intersect_key($this->contact, self::CONTACT_FIELDS)),
            'cabin_classes' => array_values($this->cabinClasses),
            'baggage_rules' => $baggage,
            'pet_policy' => $pets,
        ])->save();

        $this->finishSave($record, $created, $another);
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.airlines.editor')
            ->title($this->record ? $this->record->name : 'Neue Airline');
    }
}
