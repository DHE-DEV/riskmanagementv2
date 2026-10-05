<?php

namespace App\Livewire\AdminV2\MasterData\Airlines;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\ManagesAirlineLinks;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
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
    use AuthorizesAdminV2, EditsMasterData, ManagesAirlineLinks, RunsAiChecks;

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

    protected function aiArea(): string
    {
        return 'airlines';
    }

    /**
     * Die aktuellen Formularwerte zu den Platzhaltern (siehe AiAreas).
     */
    protected function aiContext(): array
    {
        $classes = Airline::getCabinClassOptions();
        $baggage = [];
        foreach ($classes as $class => $label) {
            $dims = $this->handDimensions[$class] ?? [];
            $parts = array_filter([
                ($this->checkedBaggage[$class] ?? '') !== '' ? 'Freigepäck '.$this->checkedBaggage[$class] : null,
                ($this->handBaggage[$class] ?? '') !== '' ? 'Handgepäck '.$this->handBaggage[$class] : null,
                array_filter($dims) ? 'Maße '.implode(' × ', array_map(fn ($side) => ($dims[$side] ?? '') ?: '–', ['length', 'width', 'height'])).' cm' : null,
            ]);
            if ($parts) {
                $baggage[] = $label.': '.implode(', ', $parts);
            }
        }
        if ($this->handBaggageNotes !== '') {
            $baggage[] = 'Hinweise: '.$this->handBaggageNotes;
        }
        if ($this->handBaggageInfoUrl !== '') {
            $baggage[] = 'Info-URL: '.$this->handBaggageInfoUrl;
        }

        // Jedes Gepaeckfeld einzeln – so kann die KI je Feld antworten und der Vorschlag uebernommen werden.
        $baggageFields = ['baggage_notes' => $this->handBaggageNotes, 'baggage_info_url' => $this->handBaggageInfoUrl];
        foreach (array_keys($classes) as $class) {
            $baggageFields['baggage_checked_'.$class] = $this->checkedBaggage[$class] ?? '';
            $baggageFields['baggage_hand_'.$class] = $this->handBaggage[$class] ?? '';
            foreach (['length', 'width', 'height'] as $side) {
                $baggageFields['baggage_hand_'.$class.'_'.$side] = $this->handDimensions[$class][$side] ?? '';
            }
        }

        $pets = ['Erlaubt: '.($this->petsAllowed ? 'Ja' : 'Nein')];
        if ($this->petsAllowed) {
            $pets[] = 'In der Kabine: '.(($this->petCabin['allowed'] ?? false) ? 'Ja' : 'Nein').
                (($this->petCabin['max_weight'] ?? '') !== '' ? ', max. '.$this->petCabin['max_weight'] : '').
                (($this->petCabin['weight_includes_bag'] ?? false) ? ' inkl. Tasche' : '').
                (array_filter([$this->petCabin['carrier_length'] ?? '', $this->petCabin['carrier_width'] ?? '', $this->petCabin['carrier_height'] ?? '']) ? ', Transportbox '.($this->petCabin['carrier_length'] ?: '–').' × '.($this->petCabin['carrier_width'] ?: '–').' × '.($this->petCabin['carrier_height'] ?: '–').' cm' : '').
                (($this->petCabin['advance_notice_required'] ?? false) ? ', Voranmeldung erforderlich' : '').
                (($this->petCabin['notes'] ?? '') !== '' ? ' – '.$this->petCabin['notes'] : '');
            $pets[] = 'Im Frachtraum: '.(($this->petHold['allowed'] ?? false) ? 'Ja' : 'Nein').
                (($this->petHold['max_weight'] ?? '') !== '' ? ', max. '.$this->petHold['max_weight'] : '').
                (($this->petHold['advance_notice_required'] ?? false) ? ', Voranmeldung erforderlich' : '').
                (($this->petHold['notes'] ?? '') !== '' ? ' – '.$this->petHold['notes'] : '');
            if ($this->petRestrictions) {
                $pets[] = 'Einschränkungen: '.implode(', ', array_map(fn ($key) => self::PET_RESTRICTIONS[$key] ?? $key, $this->petRestrictions));
            }
            if ($this->petInfoUrl !== '') {
                $pets[] = 'Info-URL: '.$this->petInfoUrl;
            }
            if ($this->petNotes !== '') {
                $pets[] = 'Hinweise: '.$this->petNotes;
            }
        }

        return [
            'name' => $this->name,
            'iata_code' => $this->iataCode,
            'icao_code' => $this->icaoCode,
            'home_country' => $this->countryOptions->firstWhere('id', (int) $this->homeCountryId)?->getName('de'),
            'headquarters' => $this->headquarters,
            'is_active' => $this->isActive,
            'website' => $this->website,
            'booking_url' => $this->bookingUrl,
            'contact' => collect(self::CONTACT_FIELDS)->map(fn ($label, $field) => ($this->contact[$field] ?? '') !== '' ? $label.': '.$this->contact[$field] : null)->filter()->values()->all(),
            'cabin_classes' => array_values(array_intersect_key($classes, array_flip($this->cabinClasses))),
            'baggage' => $baggage,
            'pets' => $pets,
            ...$baggageFields,
            // Jedes Feld der Haustiermitnahme einzeln – auch solange sie nicht erlaubt ist,
            // damit die KI zu allen Feldern eine Angabe machen kann.
            'pets_allowed' => $this->petsAllowed,
            'pets_cabin_allowed' => (bool) ($this->petCabin['allowed'] ?? false),
            'pets_cabin_max_weight' => $this->petCabin['max_weight'] ?? '',
            'pets_cabin_weight_includes_bag' => (bool) ($this->petCabin['weight_includes_bag'] ?? false),
            'pets_cabin_carrier_length' => $this->petCabin['carrier_length'] ?? '',
            'pets_cabin_carrier_width' => $this->petCabin['carrier_width'] ?? '',
            'pets_cabin_carrier_height' => $this->petCabin['carrier_height'] ?? '',
            'pets_cabin_advance_notice_required' => (bool) ($this->petCabin['advance_notice_required'] ?? false),
            'pets_cabin_notes' => $this->petCabin['notes'] ?? '',
            'pets_hold_allowed' => (bool) ($this->petHold['allowed'] ?? false),
            'pets_hold_max_weight' => $this->petHold['max_weight'] ?? '',
            'pets_hold_advance_notice_required' => (bool) ($this->petHold['advance_notice_required'] ?? false),
            'pets_hold_notes' => $this->petHold['notes'] ?? '',
            'pets_restrictions' => array_values(array_intersect_key(self::PET_RESTRICTIONS, array_flip($this->petRestrictions))),
            'pets_info_url' => $this->petInfoUrl,
            'pets_notes' => $this->petNotes,
            'airports' => $this->links->map(fn ($airport) => $airport->name.($airport->iata_code ? ' ('.$airport->iata_code.')' : '').' – '.(MasterData::LINK_DIRECTIONS[$airport->pivot->direction] ?? $airport->pivot->direction).($airport->pivot->terminal ? ', Terminal '.$airport->pivot->terminal : ''))->all(),
        ];
    }

    protected function aiReviewHint(string $section): ?string
    {
        if ($section === 'baggage') {
            return 'Die Felder hängen zusammen: Bewerte nur die Kabinenklassen, die die Airline anbietet; für nicht angebotene Klassen ist der Status "ok" und das Feld bleibt leer. '
                .'Gib zu jeder angebotenen Klasse die zutreffende Angabe an – auch zu bisher leeren Feldern. '
                .'Freigepäck und Handgepäck so, wie es im Feld stehen soll, z. B. „1 × 23 kg“ bzw. „8 kg“; ist im günstigsten Tarif kein Aufgabegepäck enthalten, „nicht inklusive“. '
                .'Maße des Handgepäcks als Zahl in Zentimetern.';
        }

        if ($section !== 'pets') {
            return null;
        }

        return 'Die Felder hängen zusammen: Prüfe zuerst, ob die Airline Haustiere befördert („Haustiermitnahme erlaubt“). '
            .'Wenn ja, gib zu allen weiteren Feldern die zutreffende Angabe an – auch zu bisher leeren Feldern und auch dann, wenn die Mitnahme im Eintrag bisher nicht erlaubt ist. '
            .'Wenn nein, ist der Status der weiteren Felder "ok". '
            .'Gewichte mit Einheit (z. B. „8 kg“), Maße der Transportbox als Zahl in Zentimetern. '
            .'Für „Allgemeine Einschränkungen“ nur diese Werte verwenden, mit Komma getrennt: '.implode(', ', self::PET_RESTRICTIONS).' – trifft keiner zu: „keine“.';
    }

    /**
     * Vorschlag der KI zu einem Gepaeckfeld in das Formular schreiben.
     */
    protected function aiApplyBaggage(string $key, string $value): bool
    {
        if ($key === 'baggage_notes') {
            $this->handBaggageNotes = $value;

            return true;
        }

        if ($key === 'baggage_info_url') {
            $this->handBaggageInfoUrl = $value;

            return true;
        }

        foreach (array_keys(Airline::getCabinClassOptions()) as $class) {
            if ($key === 'baggage_checked_'.$class) {
                $this->checkedBaggage[$class] = mb_substr($value, 0, 100);

                return true;
            }

            if ($key === 'baggage_hand_'.$class) {
                $this->handBaggage[$class] = mb_substr($value, 0, 100);

                return true;
            }

            foreach (['length', 'width', 'height'] as $side) {
                if ($key === 'baggage_hand_'.$class.'_'.$side) {
                    $this->handDimensions[$class][$side] = $this->aiNumber($value);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vorschlag der KI zur Haustiermitnahme in das passende Feld schreiben.
     */
    protected function aiApplyPets(string $key, string $value): bool
    {
        $field = substr($key, 5);

        foreach (['cabin' => 'petCabin', 'hold' => 'petHold'] as $prefix => $property) {
            if (! str_starts_with($field, $prefix.'_')) {
                continue;
            }

            $name = substr($field, strlen($prefix) + 1);

            if (! array_key_exists($name, $this->{$property})) {
                return false;
            }

            $this->{$property}[$name] = match (true) {
                is_bool($this->{$property}[$name]) => $this->aiBool($value),
                str_starts_with($name, 'carrier_') => $this->aiNumber($value),
                $name === 'max_weight' => mb_substr($value, 0, 50),
                default => $value,
            };

            return true;
        }

        switch ($field) {
            case 'allowed': $this->petsAllowed = $this->aiBool($value);

                return true;
            case 'info_url': $this->petInfoUrl = $value;

                return true;
            case 'notes': $this->petNotes = $value;

                return true;
            case 'restrictions':
                $names = array_filter(array_map('trim', preg_split('/[,;\n]+/', $value) ?: []));
                $keys = [];

                foreach ($names as $name) {
                    $match = $this->aiMatch(array_keys(self::PET_RESTRICTIONS), $name, fn ($restriction) => self::PET_RESTRICTIONS[$restriction])
                        ?? $this->aiMatch(array_keys(self::PET_RESTRICTIONS), $name, fn ($restriction) => $restriction);

                    if ($match !== null) {
                        $keys[] = $match;
                    }
                }

                // "keine" leert die Auswahl; unbekannte Angaben lassen sich nicht zuordnen.
                if ($keys === [] && ! in_array(mb_strtolower(trim($value)), ['keine', 'keine.', '–', '-'], true)) {
                    return false;
                }

                $this->petRestrictions = array_values(array_unique($keys));

                return true;
        }

        return false;
    }

    /**
     * Vorschlag der KI-Feldpruefung in das Formular uebernehmen.
     */
    protected function aiApply(string $key, string $value): bool
    {
        if (str_starts_with($key, 'pets_')) {
            return $this->aiApplyPets($key, $value);
        }

        if (str_starts_with($key, 'baggage_')) {
            return $this->aiApplyBaggage($key, $value);
        }

        switch ($key) {
            case 'name': $this->name = $value;

                return true;
            case 'iata_code': $this->iataCode = mb_strtoupper($value);

                return true;
            case 'icao_code': $this->icaoCode = mb_strtoupper($value);

                return true;
            case 'headquarters': $this->headquarters = $value;

                return true;
            case 'is_active': $this->isActive = $this->aiBool($value);

                return true;
            case 'website': $this->website = $value;

                return true;
            case 'booking_url': $this->bookingUrl = $value;

                return true;
            case 'home_country':
                $country = $this->aiMatch($this->countryOptions, $value, fn ($country) => $country->getName('de'))
                    ?? $this->aiMatch($this->countryOptions, $value, fn ($country) => (string) $country->iso_code);
                if ($country) {
                    $this->homeCountryId = (string) $country->id;
                }

                return $country !== null;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.airlines.editor')
            ->title($this->record ? $this->record->name : 'Neue Airline');
    }
}
