<?php

namespace App\Livewire\AdminV2\MasterData\Countries;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\CountryImage;
use App\Models\Currency;
use App\Models\CustomEvent;
use App\Models\MobileOperator;
use App\Models\Region;
use App\Models\TaxiApp;
use App\Services\CountryImageService;
use App\Services\DeepLTranslationService;
use App\Support\AdminV2\CountryRiskProfile;
use App\Support\AdminV2\CountryTravelInfo;
use App\Support\AdminV2\MasterData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Stammdaten > Laender: ein Land anlegen oder bearbeiten – Grunddaten,
 * Koordinaten und Risikoprofil; dazu, was am Land haengt (Regionen, Staedte,
 * Flughaefen) und die Laendergrenze zum Nachsehen.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, RunsAiChecks, WithFileUploads;

    /** Seitengroessen der Listen in der Seitenspalte; "all" = alle auf einmal. */
    public const RELATED_LIMITS = ['15', '30', '100', 'all'];

    public const RELATED_LISTS = ['regions', 'cities', 'airports'];

    /** @var array<string, string> Suchbegriff je Liste der Seitenspalte */
    public array $relatedSearch = ['regions' => '', 'cities' => '', 'airports' => ''];

    /** @var array<string, string> Seitengroesse je Liste, siehe RELATED_LIMITS */
    public array $relatedLimit = ['regions' => '15', 'cities' => '15', 'airports' => '15'];

    /** @var array<string, int> aktuelle Seite je Liste */
    public array $relatedPage = ['regions' => 1, 'cities' => 1, 'airports' => 1];

    public string $nameDe = '';

    public string $nameEn = '';

    /** @var array<int, array{code: string, name: string}> weitere Sprachen neben Deutsch und Englisch */
    public array $extraNames = [];

    public string $isoCode = '';

    public string $iso3Code = '';

    public string $continentId = '';

    public bool $isEuMember = false;

    public bool $isSchengenMember = false;

    public string $currencyCode = '';

    public string $currencyName = '';

    public string $currencySymbol = '';

    public string $phonePrefix = '';

    public string $timezone = '';

    public string $population = '';

    public string $areaKm2 = '';

    /** @var array<string, array<string, mixed>> Formularwerte, siehe CountryRiskProfile */
    public array $riskProfile = [];

    /** Beim Uebersetzen der Notizen bereits ausgefuellte Sprachen ueberschreiben */
    public bool $overwriteNoteTranslations = false;

    /** API-Test: Antwort von GET /v1/countries/{code} als formatiertes JSON */
    public ?string $apiPreview = null;

    public ?string $apiPreviewError = null;

    public string $apiPreviewLang = '';

    public string $apiPreviewYear = '';

    // Reiseinformationen (siehe CountryTravelInfo)
    public string $territoryType = 'sovereign';

    public string $parentCountryId = '';

    public string $drivingSide = '';

    /** @var array<string, mixed> Formularwerte, siehe CountryTravelInfo::toForm */
    public array $travelInfo = [];

    /** @var array<int, string> IDs der zugeordneten Taxi-Apps */
    public array $taxiAppIds = [];

    /** @var array<int, string> IDs der zugeordneten Mobilfunkanbieter */
    public array $mobileOperatorIds = [];

    /** Suche in den verfuegbaren Mobilfunkanbietern */
    public string $mobileOperatorSearch = '';

    // Feiertage
    /** Jahr, dessen Feiertage die Karte zeigt */
    public int $holidayYear = 0;

    /**
     * Feiertage im Formular: Feiertag-ID => date, name je Sprache, comment je
     * Sprache, is_national. Der Schluessel "new" ist die Zeile zum Anlegen.
     *
     * @var array<int|string, array<string, mixed>>
     */
    public array $holidayRows = [];

    // Bilder
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> gerade hochgeladene Dateien */
    public array $newImages = [];

    /**
     * Angaben zu den Bildern im Formular: Bild-ID => alt/caption je Sprache,
     * Urheber, Lizenz, Quelle, freigegeben.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $imageMeta = [];

    public function mount(?int $country = null): void
    {
        if ($country === null) {
            $this->riskProfile = CountryRiskProfile::toForm(null);
            $this->travelInfo = CountryTravelInfo::toForm(null);
            $continent = (int) request()->query('continent');
            $this->continentId = $continent && Continent::whereKey($continent)->exists() ? (string) $continent : '';

            return;
        }

        $record = Country::withTrashed()->findOrFail($country);

        $this->recordId = $record->id;

        // Schluessel mit Leerzeichen ("de ") stammen aus Altdaten und zaehlen zur jeweiligen Sprache.
        $names = [];
        foreach ($record->name_translations ?? [] as $language => $name) {
            $names[mb_strtolower(trim((string) $language))] ??= (string) $name;
        }

        $this->nameDe = $names['de'] ?? '';
        $this->nameEn = $names['en'] ?? '';
        $this->extraNames = collect($names)->except(['de', 'en'])
            ->map(fn (string $name, string $language) => ['code' => $language, 'name' => $name])
            ->values()->all();

        $this->isoCode = (string) $record->iso_code;
        $this->iso3Code = (string) $record->iso3_code;
        $this->continentId = (string) $record->continent_id;
        $this->isEuMember = (bool) $record->is_eu_member;
        $this->isSchengenMember = (bool) $record->is_schengen_member;
        $this->currencyCode = (string) $record->currency_code;
        $this->currencyName = (string) $record->currency_name;
        $this->currencySymbol = (string) $record->currency_symbol;
        $this->phonePrefix = (string) $record->phone_prefix;
        $this->timezone = (string) $record->timezone;
        $this->population = (string) $record->population;
        $this->areaKm2 = $record->area_km2 === null ? '' : rtrim(rtrim(number_format((float) $record->area_km2, 2, '.', ''), '0'), '.');
        $this->riskProfile = CountryRiskProfile::toForm($record->risk_profile);
        $this->territoryType = isset(CountryTravelInfo::TERRITORY_TYPES[$record->territory_type]) ? $record->territory_type : 'sovereign';
        $this->parentCountryId = (string) ($record->parent_country_id ?? '');
        $this->drivingSide = (string) ($record->driving_side ?? '');
        $this->travelInfo = CountryTravelInfo::toForm($record->travel_info);
        $this->taxiAppIds = $record->taxiApps()->pluck('taxi_apps.id')->map(fn ($id) => (string) $id)->all();
        $this->mobileOperatorIds = $record->mobileOperators()->pluck('mobile_operators.id')->map(fn ($id) => (string) $id)->all();
        $this->holidayYear = (int) now()->year;
        $this->fillHolidayRows();
        $this->fillImageMeta();
        $this->fillCoordinates($record);
    }

    /**
     * Alle Waehrungen fuer die Auswahlfelder.
     *
     * @return array<int, array{value: string, label: string, code: string}>
     */
    #[Computed]
    public function currencyOptions(): array
    {
        return Currency::options('de');
    }

    /**
     * Laender, die als Mutterland in Frage kommen – alle ausser dem eigenen.
     *
     * @return array<int, array{value: int, label: string, code: string}>
     */
    #[Computed]
    public function parentOptions(): array
    {
        return Country::query()
            ->when($this->recordId, fn ($query) => $query->whereKeyNot($this->recordId))
            ->orderByRaw(MasterData::nameSql('countries'))
            ->get(['id', 'iso_code', 'name_translations'])
            ->map(fn (Country $country) => ['value' => $country->id, 'label' => $country->getName('de'), 'code' => (string) $country->iso_code])
            ->all();
    }

    /**
     * Alle aktiven Taxi-Apps – plus bereits zugeordnete, auch wenn inaktiv.
     */
    #[Computed]
    public function taxiAppOptions(): Collection
    {
        $ids = array_filter(array_map('intval', $this->taxiAppIds));

        return TaxiApp::query()
            ->where(fn ($query) => $query->where('is_active', true)->when($ids, fn ($q) => $q->orWhereIn('id', $ids)))
            ->ordered()
            ->get();
    }

    /**
     * Die zugeordneten Apps in der Reihenfolge der Auswahl – fuer die Karte.
     */
    #[Computed]
    public function selectedTaxiApps(): Collection
    {
        $ids = array_map('intval', $this->taxiAppIds);

        return $this->taxiAppOptions->whereIn('id', $ids)->values();
    }

    /**
     * Eine Religion in der Reihenfolge nach vorn oder hinten schieben – die
     * Reihenfolge bestimmt die Darstellung fuer Kunden.
     */
    public function moveReligion(string $key, string $direction): void
    {
        $religions = CountryTravelInfo::religionKeys((array) ($this->travelInfo['religions'] ?? []));
        $index = array_search($key, $religions, true);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= count($religions)) {
            return;
        }

        [$religions[$index], $religions[$target]] = [$religions[$target], $religions[$index]];
        $this->travelInfo['religions'] = $religions;
    }

    // ------------------------------------------------------------------
    // Mobilfunkanbieter
    // ------------------------------------------------------------------

    /** Hoechstens so viele Treffer zeigt die Suche nach Anbietern. */
    public const MOBILE_OPERATOR_MATCHES = 24;

    /**
     * Die zugeordneten Anbieter – auch inaktive bleiben sichtbar.
     */
    #[Computed]
    public function selectedMobileOperators(): Collection
    {
        $ids = array_values(array_filter(array_map('intval', $this->mobileOperatorIds)));

        return $ids === [] ? collect() : MobileOperator::query()->whereIn('id', $ids)->ordered()->get();
    }

    /**
     * Aktive Anbieter, die zur Suche passen und noch nicht zugeordnet sind.
     */
    #[Computed]
    public function mobileOperatorMatches(): Collection
    {
        $term = trim($this->mobileOperatorSearch);
        $selected = array_values(array_filter(array_map('intval', $this->mobileOperatorIds)));

        return MobileOperator::query()
            ->active()
            ->when($selected, fn ($query) => $query->whereKeyNot($selected))
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->ordered()
            ->limit(self::MOBILE_OPERATOR_MATCHES)
            ->get();
    }

    #[Computed]
    public function mobileOperatorsTotal(): int
    {
        return MobileOperator::query()->active()->count();
    }

    public function updatedMobileOperatorSearch(): void
    {
        unset($this->mobileOperatorMatches);
    }

    public function addMobileOperator(int $operatorId): void
    {
        if (! MobileOperator::query()->active()->whereKey($operatorId)->exists()) {
            return;
        }

        if (! in_array((string) $operatorId, $this->mobileOperatorIds, true)) {
            $this->mobileOperatorIds[] = (string) $operatorId;
        }

        $this->mobileOperatorSearch = '';
        unset($this->selectedMobileOperators, $this->mobileOperatorMatches);
    }

    public function removeMobileOperator(int $operatorId): void
    {
        $this->mobileOperatorIds = array_values(array_diff($this->mobileOperatorIds, [(string) $operatorId]));
        unset($this->selectedMobileOperators, $this->mobileOperatorMatches);
    }

    public function removeTaxiApp(int $appId): void
    {
        $this->taxiAppIds = array_values(array_diff($this->taxiAppIds, [(string) $appId]));
        unset($this->selectedTaxiApps);
    }

    // ------------------------------------------------------------------
    // Feiertage
    // ------------------------------------------------------------------

    /**
     * Die Feiertage des gewaehlten Jahres.
     */
    #[Computed]
    public function holidays(): Collection
    {
        return $this->record ? $this->record->holidays()->with('regions')->inYear($this->holidayYear)->get() : collect();
    }

    /**
     * Regionen des Landes fuer die Auswahl "gilt nur in …".
     *
     * @return array<int, array{value: int, label: string}>
     */
    #[Computed]
    public function holidayRegionOptions(): array
    {
        return $this->record
            ? $this->record->regions()->orderByRaw(MasterData::nameSql('regions'))->get()->map(fn ($region) => ['value' => $region->id, 'label' => $region->getName('de')])->all()
            : [];
    }

    /**
     * Jahre, zu denen Feiertage hinterlegt sind – plus das aktuelle und das naechste.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function holidayYears(): array
    {
        // Ohne die Sortierung der Relation – DISTINCT vertraegt kein ORDER BY nach einer anderen Spalte.
        $years = $this->record ? $this->record->holidays()->reorder()->selectRaw('YEAR(date) as y')->distinct()->orderBy('y')->pluck('y')->map(fn ($y) => (int) $y)->all() : [];
        $years = array_unique([...$years, (int) now()->year, (int) now()->year + 1]);
        sort($years);

        return $years;
    }

    public function updatedHolidayYear(): void
    {
        $this->holidayYear = max(1900, min(2100, (int) $this->holidayYear));
        unset($this->holidays);
        $this->fillHolidayRows();
    }

    protected function fillHolidayRows(): void
    {
        $this->holidayRows = ['new' => $this->emptyHolidayRow()];

        foreach ($this->holidays as $holiday) {
            $row = ['date' => $holiday->date->format('Y-m-d'), 'region_ids' => $holiday->regions->map(fn ($region) => (string) $region->id)->all(), 'add_region' => '', 'name' => [], 'comment' => []];
            foreach (CountryTravelInfo::locales() as $locale) {
                $row['name'][$locale] = (string) ($holiday->name_translations[$locale] ?? '');
                $row['comment'][$locale] = (string) ($holiday->comment_translations[$locale] ?? '');
            }
            $this->holidayRows[$holiday->id] = $row;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyHolidayRow(): array
    {
        $row = ['date' => '', 'region_ids' => [], 'add_region' => '', 'name' => [], 'comment' => []];
        foreach (CountryTravelInfo::locales() as $locale) {
            $row['name'][$locale] = '';
            $row['comment'][$locale] = '';
        }

        return $row;
    }

    /**
     * Regeln fuer eine Feiertagszeile.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    protected function holidayRules(string $key): array
    {
        $source = CustomEvent::sourceLocale();

        return [[
            'holidayRows.'.$key.'.date' => ['required', 'date_format:Y-m-d'],
            'holidayRows.'.$key.'.region_ids' => ['array'],
            'holidayRows.'.$key.'.region_ids.*' => [Rule::exists('regions', 'id')->where('country_id', $this->recordId)->whereNull('deleted_at')],
            'holidayRows.'.$key.'.name.'.$source => ['required', 'string', 'max:255'],
            'holidayRows.'.$key.'.name.*' => ['nullable', 'string', 'max:255'],
            'holidayRows.'.$key.'.comment.*' => ['nullable', 'string', 'max:2000'],
        ], [
            'holidayRows.'.$key.'.date.required' => 'Bitte ein Datum angeben.',
            'holidayRows.'.$key.'.date.date_format' => 'Bitte ein gültiges Datum angeben.',
            'holidayRows.'.$key.'.name.'.$source.'.required' => 'Bitte den Namen in der Ausgangssprache angeben.',
            'holidayRows.'.$key.'.region_ids.*.exists' => 'Bitte Regionen dieses Landes wählen.',
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    protected function holidayAttributes(array $row): array
    {
        $clean = fn (array $texts) => array_filter(array_map(fn ($text) => trim((string) $text), $texts), fn ($text) => $text !== '');

        return [
            'date' => $row['date'],
            'name_translations' => $clean((array) ($row['name'] ?? [])),
            'comment_translations' => $clean((array) ($row['comment'] ?? [])) ?: null,
            'is_national' => ($row['region_ids'] ?? []) === [],
        ];
    }

    /**
     * Einen Feiertag anlegen – aus der Zeile "new".
     */
    public function addHoliday(): void
    {
        abort_unless($this->record, 404);

        [$rules, $messages] = $this->holidayRules('new');
        $this->validate($rules, $messages);

        $holiday = $this->record->holidays()->create($this->holidayAttributes($this->holidayRows['new']) + ['source' => CountryHoliday::SOURCE_MANUAL]);
        $holiday->regions()->sync(array_map('intval', (array) ($this->holidayRows['new']['region_ids'] ?? [])));

        $this->holidayYear = (int) $holiday->date->year;
        $this->refreshHolidays();
        $this->dispatch('adminv2-toast', message: 'Feiertag „'.$holiday->getName(CustomEvent::sourceLocale()).'“ angelegt.');
    }

    public function saveHoliday(int $holidayId): void
    {
        $holiday = $this->ownHoliday($holidayId);

        [$rules, $messages] = $this->holidayRules((string) $holidayId);
        $this->validate($rules, $messages);

        $holiday->update($this->holidayAttributes($this->holidayRows[$holidayId]));
        $holiday->regions()->sync(array_map('intval', (array) ($this->holidayRows[$holidayId]['region_ids'] ?? [])));

        $this->holidayYear = (int) $holiday->date->year;
        $this->refreshHolidays();
        $this->dispatch('adminv2-toast', message: 'Feiertag gespeichert.');
    }

    /**
     * Eine Region zur Zeile hinzufuegen (Auswahl "Region hinzufuegen") –
     * gespeichert wird mit dem Haken der Zeile bzw. mit "Anlegen".
     */
    public function addHolidayRegion(string $key): void
    {
        $regionId = (string) ($this->holidayRows[$key]['add_region'] ?? '');
        $this->holidayRows[$key]['add_region'] = '';

        if ($regionId === '' || in_array($regionId, $this->holidayRows[$key]['region_ids'] ?? [], true)) {
            return;
        }

        $this->holidayRows[$key]['region_ids'][] = $regionId;
    }

    public function removeHolidayRegion(string $key, int $regionId): void
    {
        $this->holidayRows[$key]['region_ids'] = array_values(array_diff((array) ($this->holidayRows[$key]['region_ids'] ?? []), [(string) $regionId]));
    }

    public function deleteHoliday(int $holidayId): void
    {
        $holiday = $this->ownHoliday($holidayId);
        $holiday->delete();

        $this->refreshHolidays();
        $this->dispatch('adminv2-toast', message: 'Feiertag gelöscht.');
    }

    protected function ownHoliday(int $holidayId): CountryHoliday
    {
        abort_unless($this->record, 404);

        return $this->record->holidays()->findOrFail($holidayId);
    }

    protected function refreshHolidays(): void
    {
        unset($this->holidays, $this->holidayYears);
        $this->fillHolidayRows();
    }

    // ------------------------------------------------------------------
    // Bilder
    // ------------------------------------------------------------------

    #[Computed]
    public function images(): Collection
    {
        return $this->record ? $this->record->images()->get() : collect();
    }

    protected function fillImageMeta(): void
    {
        $this->imageMeta = [];

        foreach ($this->images as $image) {
            $meta = ['alt' => [], 'caption' => [], 'credit' => (string) $image->credit, 'license' => (string) $image->license, 'source_url' => (string) $image->source_url, 'is_published' => (bool) $image->is_published];

            foreach (CountryTravelInfo::locales() as $locale) {
                $meta['alt'][$locale] = (string) ($image->alt_translations[$locale] ?? '');
                $meta['caption'][$locale] = (string) ($image->caption_translations[$locale] ?? '');
            }

            $this->imageMeta[$image->id] = $meta;
        }
    }

    /**
     * Hochgeladene Dateien sofort ablegen – ohne "Speichern"; das erste Bild
     * eines Landes wird sein Titelbild.
     */
    public function updatedNewImages(): void
    {
        if (! $this->record) {
            $this->newImages = [];

            return;
        }

        $this->validate([
            'newImages' => ['array', 'max:20'],
            'newImages.*' => ['file', 'mimetypes:'.implode(',', CountryImageService::MIME_TYPES), 'max:10240'],
        ], [
            'newImages.*.mimetypes' => 'Erlaubt sind JPEG, PNG, WebP und SVG.',
            'newImages.*.max' => 'Ein Bild darf höchstens 10 MB groß sein.',
        ]);

        $service = app(CountryImageService::class);
        $stored = 0;

        foreach ($this->newImages as $file) {
            $kind = $this->record->images()->where('kind', CountryImage::KIND_HERO)->exists() ? CountryImage::KIND_GALLERY : CountryImage::KIND_HERO;
            $service->store($this->record, $file, $kind, auth('web')->id());
            $stored++;
        }

        $this->newImages = [];
        $this->refreshImages();

        $this->dispatch('adminv2-toast', message: $stored === 1 ? '1 Bild hochgeladen.' : $stored.' Bilder hochgeladen.');
    }

    public function setHeroImage(int $imageId): void
    {
        $image = $this->ownImage($imageId);

        $this->record->images()->where('kind', CountryImage::KIND_HERO)->update(['kind' => CountryImage::KIND_GALLERY]);
        $image->update(['kind' => CountryImage::KIND_HERO]);

        $this->refreshImages();
        $this->dispatch('adminv2-toast', message: 'Titelbild gesetzt.');
    }

    /**
     * Ein Bild in der Galerie nach oben oder unten schieben.
     */
    public function moveImage(int $imageId, string $direction): void
    {
        $image = $this->ownImage($imageId);
        $ordered = $this->record->images()->where('kind', CountryImage::KIND_GALLERY)->get()->values();
        $index = $ordered->search(fn (CountryImage $item) => $item->id === $image->id);

        if ($index === false) {
            return;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $ordered->count()) {
            return;
        }

        $swapped = $ordered->all();
        [$swapped[$index], $swapped[$target]] = [$swapped[$target], $swapped[$index]];

        foreach ($swapped as $position => $item) {
            $item->update(['sort_order' => $position + 1]);
        }

        $this->refreshImages();
    }

    /**
     * Alt-Texte, Bildunterschriften, Urheber, Lizenz, Quelle und Freigabe eines Bildes speichern.
     */
    public function saveImage(int $imageId): void
    {
        $image = $this->ownImage($imageId);
        $meta = $this->imageMeta[$imageId] ?? [];

        $this->validate([
            'imageMeta.'.$imageId.'.alt.*' => ['nullable', 'string', 'max:255'],
            'imageMeta.'.$imageId.'.caption.*' => ['nullable', 'string', 'max:1000'],
            'imageMeta.'.$imageId.'.credit' => ['nullable', 'string', 'max:255'],
            'imageMeta.'.$imageId.'.license' => ['nullable', 'string', 'max:100'],
            'imageMeta.'.$imageId.'.source_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'imageMeta.'.$imageId.'.source_url.url' => 'Die Quelle muss eine vollständige Adresse sein (https://…).',
        ]);

        $clean = fn (array $texts) => array_filter(array_map(fn ($text) => trim((string) $text), $texts), fn ($text) => $text !== '');

        $image->update([
            'alt_translations' => $clean((array) ($meta['alt'] ?? [])) ?: null,
            'caption_translations' => $clean((array) ($meta['caption'] ?? [])) ?: null,
            'credit' => trim((string) ($meta['credit'] ?? '')) ?: null,
            'license' => trim((string) ($meta['license'] ?? '')) ?: null,
            'source_url' => trim((string) ($meta['source_url'] ?? '')) ?: null,
            'is_published' => (bool) ($meta['is_published'] ?? true),
        ]);

        $this->refreshImages();
        $this->dispatch('adminv2-toast', message: 'Bildangaben gespeichert.');
    }

    public function deleteImage(int $imageId): void
    {
        $image = $this->ownImage($imageId);
        $wasHero = $image->isHero();

        app(CountryImageService::class)->delete($image);

        // Ohne Titelbild rueckt das erste Galeriebild nach.
        if ($wasHero && ($next = $this->record->images()->first())) {
            $next->update(['kind' => CountryImage::KIND_HERO]);
        }

        $this->refreshImages();
        $this->dispatch('adminv2-toast', message: 'Bild gelöscht.');
    }

    protected function ownImage(int $imageId): CountryImage
    {
        abort_unless($this->record, 404);

        return $this->record->images()->findOrFail($imageId);
    }

    protected function refreshImages(): void
    {
        unset($this->images);
        $this->fillImageMeta();
    }

    protected function masterDataModel(): string
    {
        return Country::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.countries';
    }

    #[Computed]
    public function continents(): Collection
    {
        return Continent::query()->ordered()->get();
    }

    public function addName(): void
    {
        $this->extraNames[] = ['code' => '', 'name' => ''];
    }

    public function removeName(int $index): void
    {
        unset($this->extraNames[$index]);
        $this->extraNames = array_values($this->extraNames);
    }

    /**
     * Gesamt-Risiko aus den gerade eingetragenen Stufen – wie
     * Country::overall_risk_level die hoechste der drei Hauptstufen.
     */
    #[Computed]
    public function overallRisk(): ?int
    {
        $levels = array_filter([
            (int) ($this->riskProfile['security']['overall_risk_level'] ?? 0),
            (int) ($this->riskProfile['health']['health_risk_level'] ?? 0),
            (int) ($this->riskProfile['natural_hazards']['natural_hazard_level'] ?? 0),
        ]);

        return $levels ? max($levels) : null;
    }

    /**
     * Neue Suche oder Seitengroesse: zurueck auf Seite 1 der Liste.
     */
    public function updatedRelatedSearch(mixed $value, string $list): void
    {
        $this->relatedPage[$list] = 1;
        unset($this->related);
    }

    public function updatedRelatedLimit(mixed $value, string $list): void
    {
        $this->relatedPage[$list] = 1;
        unset($this->related);
    }

    /**
     * In einer Liste der Seitenspalte blaettern.
     */
    public function relatedGoto(string $list, int $page): void
    {
        if (! in_array($list, self::RELATED_LISTS, true)) {
            return;
        }

        $this->relatedPage[$list] = max(1, $page);
        unset($this->related);
    }

    /**
     * Was am Land haengt – je Liste mit Suche und Seiten.
     *
     * @return array{regions: array<string, mixed>, cities: array<string, mixed>, airports: array<string, mixed>, events: int}|null
     */
    #[Computed]
    public function related(): ?array
    {
        $country = $this->record;

        if (! $country) {
            return null;
        }

        // Wichtige Regionen zuerst, dann alphabetisch.
        $regions = fn () => $country->regions()->orderByDesc('is_major')->orderByRaw(MasterData::nameSql('regions'));
        // Hauptstadt und wichtige Staedte zuerst, dann die groessten.
        $cities = fn () => $country->cities()->orderByDesc('is_capital')->orderByDesc('is_major')->orderByDesc('population')->orderByRaw(MasterData::nameSql('cities'));
        $airports = fn () => $country->airports()->orderBy('name')->select(['id', 'name', 'iata_code', 'icao_code', 'country_id']);

        return [
            'regions' => $this->relatedList('regions', $regions, fn ($query, string $term) => MasterData::search($query, $term, ['code']), fn ($region) => $region->getName('de')),
            'cities' => $this->relatedList('cities', $cities, fn ($query, string $term) => MasterData::search($query, $term), fn ($city) => $city->getName('de')),
            'airports' => $this->relatedList('airports', $airports, function ($query, string $term) {
                $like = '%'.addcslashes(trim($term), '%_\\').'%';

                return $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('iata_code', 'like', $like)->orWhere('icao_code', 'like', $like));
            }, fn ($airport) => trim($airport->name.' '.($airport->iata_code ? '('.$airport->iata_code.')' : ''))),
            'events' => DB::table('country_custom_event')->where('country_id', $country->id)->distinct()->count('custom_event_id'),
        ];
    }

    /**
     * Eine Region als "Major Region" des Landes markieren – oder die Markierung
     * wieder aufheben. Wird sofort gespeichert, unabhaengig vom Formular.
     */
    public function toggleMajorRegion(int $regionId): void
    {
        $region = $this->record?->regions()->find($regionId);

        if (! $region) {
            return;
        }

        $region->update(['is_major' => ! $region->is_major]);
        MasterData::logChange($region, \App\Models\MasterDataChange::ACTION_UPDATED, ['is_major']);

        unset($this->related);
        $this->dispatch('adminv2-toast', message: $region->is_major
            ? '„'.$region->getName('de').'“ ist jetzt eine Major Region.'
            : '„'.$region->getName('de').'“ ist keine Major Region mehr.');
    }

    /**
     * Eine Stadt als "Major City" des Landes markieren – oder die Markierung
     * wieder aufheben.
     */
    public function toggleMajorCity(int $cityId): void
    {
        $city = $this->record?->cities()->find($cityId);

        if (! $city) {
            return;
        }

        $city->update(['is_major' => ! $city->is_major]);
        MasterData::logChange($city, \App\Models\MasterDataChange::ACTION_UPDATED, ['is_major']);

        unset($this->related);
        $this->dispatch('adminv2-toast', message: $city->is_major
            ? '„'.$city->getName('de').'“ ist jetzt eine Major City.'
            : '„'.$city->getName('de').'“ ist keine Major City mehr.');
    }

    /**
     * Eine Liste der Seitenspalte: Gesamtzahl, Treffer zur Suche, die Eintraege
     * der aktuellen Seite und Vorschlaege fuer die Autovervollstaendigung.
     *
     * @param  callable(): \Illuminate\Database\Eloquent\Relations\HasMany  $query  Grundabfrage mit Sortierung
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string): \Illuminate\Database\Eloquent\Builder  $search
     * @param  callable(\Illuminate\Database\Eloquent\Model): string  $label
     * @return array{count: int, found: int, items: Collection, suggestions: array<int, string>, page: int, last_page: int, limit: string, search: string, from: int, to: int}
     */
    protected function relatedList(string $list, callable $query, callable $search, callable $label): array
    {
        // Die Suche erwartet einen Eloquent-Builder, die Relation liefert ihn samt Land-Bedingung.
        $base = fn () => $query()->getQuery();
        $count = $base()->count();
        $term = trim((string) ($this->relatedSearch[$list] ?? ''));
        $limit = in_array($this->relatedLimit[$list] ?? '', self::RELATED_LIMITS, true) ? $this->relatedLimit[$list] : '15';

        $filtered = fn () => $term === '' ? $base() : $search($base(), $term);
        $found = $term === '' ? $count : $filtered()->count();

        $perPage = $limit === 'all' ? max(1, $found) : (int) $limit;
        $lastPage = max(1, (int) ceil($found / $perPage));
        $page = min(max(1, (int) ($this->relatedPage[$list] ?? 1)), $lastPage);

        $items = $filtered()->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Vorschlaege: die ersten Treffer zum Suchbegriff – fuer die Autovervollstaendigung des Feldes.
        $suggestions = $term === '' ? [] : $filtered()->take(12)->get()->map($label)->unique()->values()->all();

        return [
            'count' => $count,
            'found' => $found,
            'items' => $items,
            'suggestions' => $suggestions,
            'page' => $page,
            'last_page' => $lastPage,
            'limit' => $limit,
            'search' => $term,
            'from' => $found === 0 ? 0 : ($page - 1) * $perPage + 1,
            'to' => min($found, $page * $perPage),
        ];
    }

    /**
     * Die Laendergrenze – reine Anzeige; geschrieben wird sie ausschliesslich
     * vom Befehl countries:import-boundaries.
     *
     * @return array<string, string>|null
     */
    #[Computed]
    public function boundary(): ?array
    {
        $boundary = $this->record?->boundary;

        if (! $boundary) {
            return null;
        }

        $stats = $boundary->spatialStats();
        $number = fn (float|int $value, int $decimals = 0) => number_format($value, $decimals, ',', '.');
        $rows = [];

        $rows['Quelle'] = (string) $boundary->source;
        $rows['Bezeichnung in der Quelle'] = (string) $boundary->name;
        $rows['Codes in der Quelle'] = implode(' / ', array_filter([$boundary->iso_a2, $boundary->iso_a3]));

        if ($boundary->source_features > 1) {
            $rows['Quell-Einheiten'] = $boundary->source_features.' zusammengefasst (z. B. Landesteile oder Außengebiete)';
        }

        if ($stats['parts'] !== null) {
            $rows['Teilpolygone'] = $number($stats['parts']);
        }

        if ($stats['area_km2'] !== null) {
            $area = $number($stats['area_km2']).' km²';

            // Aus der Geometrie gerechnet – zum Vergleich mit dem Feld "Fläche".
            if ($this->record->area_km2 > 0) {
                $deviation = (($stats['area_km2'] - $this->record->area_km2) / $this->record->area_km2) * 100;
                $area .= ' (Stammdaten: '.$number((float) $this->record->area_km2).' km², Abweichung '.($deviation >= 0 ? '+' : '').$number($deviation, 1).' %)';
            }

            $rows['Fläche aus dem Polygon'] = $area;
        }

        if ($boundary->min_lat !== null) {
            $rows['Bounding-Box'] = $number($boundary->min_lat, 4).' bis '.$number($boundary->max_lat, 4).' Breite, '
                .$number($boundary->min_lng, 4).' bis '.$number($boundary->max_lng, 4).' Länge';
        }

        if ($stats['is_valid'] !== null) {
            $rows['Geometrie'] = $stats['is_valid'] ? 'gültig' : 'ungültig';
        }

        if ($stats['bytes'] !== null) {
            $rows['Datengröße'] = $number($stats['bytes'] / 1024, 1).' KB';
        }

        $rows['Stand'] = (string) $boundary->updated_at?->format('d.m.Y H:i');

        return array_filter($rows, fn (string $value) => $value !== '');
    }

    public function save(bool $another = false): void
    {
        $this->isoCode = mb_strtoupper(trim($this->isoCode));
        $this->iso3Code = mb_strtoupper(trim($this->iso3Code));
        $this->currencyCode = mb_strtoupper(trim($this->currencyCode));
        $this->population = str_replace(['.', ' '], '', trim($this->population));
        $this->areaKm2 = str_replace(',', '.', trim($this->areaKm2));
        $this->extraNames = array_values(array_filter(
            array_map(fn (array $row) => ['code' => mb_strtolower(trim((string) ($row['code'] ?? ''))), 'name' => trim((string) ($row['name'] ?? ''))], $this->extraNames),
            fn (array $row) => $row['code'] !== '' || $row['name'] !== '',
        ));
        $this->normalizeCoordinates();

        // Unveraenderte Codes werden nicht erneut auf Eindeutigkeit geprueft.
        $record = $this->record;
        $unique = fn (string $column, string $value) => ! $record || mb_strtoupper((string) $record->{$column}) !== $value
            ? Rule::unique('countries', $column)->ignore($this->recordId)
            : null;

        $this->validate([
            'nameDe' => ['required', 'string', 'max:255'],
            'nameEn' => ['required', 'string', 'max:255'],
            'extraNames.*.code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[a-z0-9]{2,4})?$/', 'distinct', Rule::notIn(['de', 'en'])],
            'extraNames.*.name' => ['required', 'string', 'max:255'],
            'isoCode' => array_filter(['required', 'alpha', 'size:2', $unique('iso_code', $this->isoCode)]),
            'iso3Code' => array_filter(['required', 'alpha', 'size:3', $unique('iso3_code', $this->iso3Code)]),
            'continentId' => ['required', Rule::exists('continents', 'id')->whereNull('deleted_at')],
            'currencyCode' => ['nullable', 'alpha', 'size:3'],
            'currencyName' => ['nullable', 'string', 'max:255'],
            'currencySymbol' => ['nullable', 'string', 'max:5'],
            'phonePrefix' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'population' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'areaKm2' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'riskProfile.entry.passport_validity_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'territoryType' => ['required', Rule::in(array_keys(CountryTravelInfo::TERRITORY_TYPES))],
            'parentCountryId' => ['nullable', Rule::exists('countries', 'id')->whereNull('deleted_at'), Rule::notIn([(string) $this->recordId])],
            'drivingSide' => ['nullable', Rule::in(array_keys(CountryTravelInfo::DRIVING_SIDES))],
            'taxiAppIds' => ['array'],
            'taxiAppIds.*' => [Rule::exists('taxi_apps', 'id')],
            'mobileOperatorIds' => ['array'],
            'mobileOperatorIds.*' => [Rule::exists('mobile_operators', 'id')],
        ] + CountryTravelInfo::rules('travelInfo', $this->travelInfo) + $this->coordinateRules(), [
            'nameDe.required' => 'Bitte den deutschen Namen angeben.',
            'nameEn.required' => 'Bitte den englischen Namen angeben.',
            'extraNames.*.code.required' => 'Bitte das Sprachkürzel angeben, z. B. fr.',
            'extraNames.*.code.regex' => 'Das Sprachkürzel besteht aus 2–3 Buchstaben, z. B. fr oder pt-br.',
            'extraNames.*.code.distinct' => 'Diese Sprache ist doppelt eingetragen.',
            'extraNames.*.code.not_in' => 'Deutsch und Englisch stehen in den Feldern oben.',
            'extraNames.*.name.required' => 'Bitte den Namen in dieser Sprache angeben.',
            'isoCode.required' => 'Bitte den ISO-Code mit 2 Buchstaben angeben.',
            'isoCode.alpha' => 'Der ISO-Code besteht aus 2 Buchstaben.',
            'isoCode.size' => 'Der ISO-Code besteht aus 2 Buchstaben.',
            'isoCode.unique' => 'Diesen ISO-Code trägt bereits ein anderes Land (auch der Papierkorb zählt).',
            'iso3Code.required' => 'Bitte den ISO-Code mit 3 Buchstaben angeben.',
            'iso3Code.alpha' => 'Der ISO3-Code besteht aus 3 Buchstaben.',
            'iso3Code.size' => 'Der ISO3-Code besteht aus 3 Buchstaben.',
            'iso3Code.unique' => 'Diesen ISO3-Code trägt bereits ein anderes Land (auch der Papierkorb zählt).',
            'continentId.required' => 'Bitte einen Kontinent wählen.',
            'continentId.exists' => 'Bitte einen Kontinent wählen.',
            'currencyCode.alpha' => 'Der Währungscode besteht aus 3 Buchstaben, z. B. EUR.',
            'currencyCode.size' => 'Der Währungscode besteht aus 3 Buchstaben, z. B. EUR.',
            'currencySymbol.max' => 'Das Währungssymbol darf höchstens 5 Zeichen haben.',
            'phonePrefix.max' => 'Die Vorwahl darf höchstens 10 Zeichen haben.',
            'population.integer' => 'Die Bevölkerung muss eine ganze Zahl sein.',
            'areaKm2.numeric' => 'Die Fläche muss eine Zahl sein.',
            'riskProfile.entry.passport_validity_months.integer' => 'Die Gültigkeit des Reisepasses ist eine ganze Zahl von Monaten.',
            'territoryType.in' => 'Bitte einen Gebietstyp wählen.',
            'parentCountryId.exists' => 'Bitte ein vorhandenes Land als Mutterland wählen.',
            'parentCountryId.not_in' => 'Ein Land kann nicht sein eigenes Mutterland sein.',
            'drivingSide.in' => 'Bitte Rechts- oder Linksverkehr wählen.',
        ] + CountryTravelInfo::messages() + $this->coordinateMessages());

        $record ??= new Country;
        $created = ! $record->exists;
        // Das Risikoprofil wird beim Speichern vereinheitlicht – nur inhaltliche Aenderungen zaehlen im Protokoll.
        $riskProfile = CountryRiskProfile::fromForm($this->riskProfile, $record->risk_profile);
        $unchanged = $record->exists && $riskProfile == CountryRiskProfile::fromForm(CountryRiskProfile::toForm($record->risk_profile), $record->risk_profile) ? ['risk_profile'] : [];
        $travelInfo = CountryTravelInfo::fromForm($this->travelInfo, $record->travel_info);
        if ($record->exists && $travelInfo == CountryTravelInfo::fromForm(CountryTravelInfo::toForm($record->travel_info), $record->travel_info)) {
            $unchanged[] = 'travel_info';
        }

        $names = ['de' => trim($this->nameDe), 'en' => trim($this->nameEn)];
        foreach ($this->extraNames as $row) {
            $names[$row['code']] = $row['name'];
        }

        $record->fill([
            'name_translations' => $names,
            'iso_code' => $this->isoCode,
            'iso3_code' => $this->iso3Code,
            'continent_id' => (int) $this->continentId,
            'is_eu_member' => $this->isEuMember,
            'is_schengen_member' => $this->isSchengenMember,
            'currency_code' => $this->currencyCode ?: null,
            'currency_name' => trim($this->currencyName) ?: null,
            'currency_symbol' => trim($this->currencySymbol) ?: null,
            'phone_prefix' => trim($this->phonePrefix) ?: null,
            'timezone' => trim($this->timezone) ?: null,
            'population' => $this->population === '' ? null : (int) $this->population,
            'area_km2' => $this->areaKm2 === '' ? null : (float) $this->areaKm2,
            'risk_profile' => $riskProfile,
            'territory_type' => $this->territoryType,
            // Ein souveraener Staat hat kein Mutterland.
            'parent_country_id' => $this->territoryType !== 'sovereign' && $this->parentCountryId !== '' ? (int) $this->parentCountryId : null,
            'driving_side' => $this->drivingSide ?: null,
            'travel_info' => $travelInfo,
        ] + $this->coordinateValues())->save();

        // Taxi-Apps: nur, was es gibt; die Zuordnung zaehlt im Protokoll als Aenderung.
        $taxiAppIds = TaxiApp::query()->whereIn('id', array_map('intval', $this->taxiAppIds))->pluck('id')->all();
        $syncedTaxiApps = $record->taxiApps()->sync($taxiAppIds);
        if ($syncedTaxiApps['attached'] !== [] || $syncedTaxiApps['detached'] !== []) {
            $record->syncChanges();
            MasterData::logChange($record, \App\Models\MasterDataChange::ACTION_UPDATED, ['taxi_apps']);
        }

        $operatorIds = MobileOperator::query()->whereIn('id', array_map('intval', $this->mobileOperatorIds))->pluck('id')->all();
        $syncedOperators = $record->mobileOperators()->sync($operatorIds);
        if ($syncedOperators['attached'] !== [] || $syncedOperators['detached'] !== []) {
            $record->syncChanges();
            MasterData::logChange($record, \App\Models\MasterDataChange::ACTION_UPDATED, ['mobile_operators']);
        }

        unset($this->related, $this->boundary, $this->overallRisk, $this->taxiAppOptions, $this->selectedTaxiApps, $this->selectedMobileOperators, $this->mobileOperatorMatches);

        $this->finishSave($record, $created, $another, $unchanged);
    }

    /**
     * Uebersetzt die Notizen des Risikoprofils per DeepL aus der Ausgangssprache
     * in die uebrigen Sprachen – im Formular, gespeichert wird erst mit "Speichern".
     */
    public function translateRiskNotes(): void
    {
        $deepl = app(DeepLTranslationService::class);
        $this->modal('translate-risk-notes')->close();

        if (! $deepl->isConfigured()) {
            $this->dispatch('adminv2-toast', message: 'DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).', variant: 'danger');

            return;
        }

        set_time_limit(120);

        $source = CustomEvent::sourceLocale();
        $translated = 0;
        $errors = [];

        foreach ($this->riskProfile as $category => $values) {
            foreach ($values['notes'] ?? [] as $field => $texts) {
                if (blank($texts[$source] ?? null)) {
                    continue;
                }

                foreach (CountryRiskProfile::noteLocales() as $locale) {
                    if ($locale === $source || (! $this->overwriteNoteTranslations && filled($texts[$locale] ?? null))) {
                        continue;
                    }

                    try {
                        $this->riskProfile[$category]['notes'][$field][$locale] = $deepl->translate(trim($texts[$source]), $locale, $source);
                        $translated++;
                    } catch (\Throwable $e) {
                        $errors[strtoupper($locale)] = strtoupper($locale).': '.$e->getMessage();
                    }
                }
            }
        }

        $this->dispatch('adminv2-toast', ...match (true) {
            $errors !== [] => ['message' => 'Übersetzung teilweise fehlgeschlagen – '.implode(' | ', $errors), 'variant' => 'danger'],
            $translated > 0 => ['message' => $translated.' '.($translated === 1 ? 'Notiz' : 'Notizen').' übersetzt – noch nicht gespeichert.'],
            default => ['message' => 'Nichts zu übersetzen – alle Sprachen waren bereits ausgefüllt.'],
        });
    }

    /**
     * Uebersetzt die mehrsprachigen Texte eines Abschnitts per DeepL aus der
     * Ausgangssprache in die uebrigen Sprachen – wie bei den Risiko-Notizen.
     *
     * details: Einleitung, "Bekannt fuer", Bezeichnung des Nationaltags.
     * power: Bemerkung zum Strom. tipping: Beschreibungen je Bereich. images: Alt-Texte und
     * Bildunterschriften, holidays: Namen und Kommentare der Feiertage des
     * gewaehlten Jahres – beide werden sofort gespeichert.
     */
    /**
     * API-Test: ruft die Laenderinformationen ueber den API-Controller ab
     * (dieselbe Antwort wie GET /v1/countries/{code}) und zeigt sie als JSON.
     */
    public function loadApiPreview(): void
    {
        $this->apiPreview = null;
        $this->apiPreviewError = null;

        if (! $this->record) {
            $this->apiPreviewError = 'Die API liefert nur gespeicherte Länder – bitte zuerst speichern.';

            return;
        }

        $query = array_filter([
            'lang' => $this->apiPreviewLang ?: null,
            'year' => trim($this->apiPreviewYear) !== '' ? (int) $this->apiPreviewYear : null,
        ]);

        $request = \Illuminate\Http\Request::create($this->apiPreviewUrl(), 'GET', $query);
        $request->headers->set('Accept', 'application/json');

        try {
            $response = app(\App\Http\Controllers\Api\V1\BaseDataController::class)->country($request, (string) $this->record->iso_code);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->apiPreviewError = implode(' ', array_map(fn (array $messages) => implode(' ', $messages), $exception->errors()));

            return;
        }

        $this->apiPreview = json_encode($response->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Oeffentliche Adresse des Endpunkts fuer dieses Land.
     */
    public function apiPreviewUrl(): string
    {
        $query = array_filter([
            'lang' => $this->apiPreviewLang ?: null,
            'year' => trim($this->apiPreviewYear) !== '' ? trim($this->apiPreviewYear) : null,
        ]);

        return 'https://'.config('app.api_domain').'/v1/countries/'.($this->record?->iso_code ?? '{code}').($query ? '?'.http_build_query($query) : '');
    }

    public function translateTexts(string $section): void
    {
        $this->modal('translate-'.$section)->close();

        $deepl = app(DeepLTranslationService::class);

        if (! $deepl->isConfigured()) {
            $this->dispatch('adminv2-toast', message: 'DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).', variant: 'danger');

            return;
        }

        set_time_limit(120);

        $translated = 0;
        $errors = [];

        switch ($section) {
            case 'description':
                foreach (array_keys(CountryTravelInfo::DESCRIPTION_TEXTS) as $field) {
                    $this->travelInfo['texts'][$field] = $this->translateMap((array) ($this->travelInfo['texts'][$field] ?? []), $deepl, $translated, $errors);
                }
                break;

            case 'details':
                foreach (array_keys(CountryTravelInfo::TEXTS) as $field) {
                    $this->travelInfo['texts'][$field] = $this->translateMap((array) ($this->travelInfo['texts'][$field] ?? []), $deepl, $translated, $errors);
                }
                $this->travelInfo['national_day']['name'] = $this->translateMap((array) ($this->travelInfo['national_day']['name'] ?? []), $deepl, $translated, $errors);
                break;

            case 'power':
                foreach (array_keys(CountryTravelInfo::POWER_TEXTS) as $field) {
                    $this->travelInfo['texts'][$field] = $this->translateMap((array) ($this->travelInfo['texts'][$field] ?? []), $deepl, $translated, $errors);
                }
                break;

            case 'tipping':
                foreach (array_keys(CountryTravelInfo::TIPPING_CATEGORIES) as $category) {
                    $this->travelInfo['tipping'][$category]['description'] = $this->translateMap((array) ($this->travelInfo['tipping'][$category]['description'] ?? []), $deepl, $translated, $errors);
                }
                break;

            case 'holidays':
                foreach ($this->holidays as $holiday) {
                    $before = $translated;
                    $this->holidayRows[$holiday->id]['name'] = $this->translateMap((array) ($this->holidayRows[$holiday->id]['name'] ?? []), $deepl, $translated, $errors);
                    $this->holidayRows[$holiday->id]['comment'] = $this->translateMap((array) ($this->holidayRows[$holiday->id]['comment'] ?? []), $deepl, $translated, $errors);

                    if ($translated > $before) {
                        $holiday->update($this->holidayAttributes($this->holidayRows[$holiday->id]));
                    }
                }
                unset($this->holidays);
                break;

            case 'images':
                foreach ($this->images as $image) {
                    $before = $translated;
                    $this->imageMeta[$image->id]['alt'] = $this->translateMap((array) ($this->imageMeta[$image->id]['alt'] ?? []), $deepl, $translated, $errors);
                    $this->imageMeta[$image->id]['caption'] = $this->translateMap((array) ($this->imageMeta[$image->id]['caption'] ?? []), $deepl, $translated, $errors);

                    if ($translated > $before) {
                        $clean = fn (array $texts) => array_filter(array_map(fn ($text) => trim((string) $text), $texts), fn ($text) => $text !== '');
                        $image->update([
                            'alt_translations' => $clean($this->imageMeta[$image->id]['alt']) ?: null,
                            'caption_translations' => $clean($this->imageMeta[$image->id]['caption']) ?: null,
                        ]);
                    }
                }
                unset($this->images);
                break;

            default:
                return;
        }

        $saved = in_array($section, ['images', 'holidays'], true);

        $this->dispatch('adminv2-toast', ...match (true) {
            $errors !== [] => ['message' => 'Übersetzung teilweise fehlgeschlagen – '.implode(' | ', $errors), 'variant' => 'danger'],
            $translated > 0 => ['message' => $translated.' '.($translated === 1 ? 'Text' : 'Texte').' übersetzt'.($saved ? ' und gespeichert.' : ' – noch nicht gespeichert.')],
            default => ['message' => 'Nichts zu übersetzen – alle Sprachen waren bereits ausgefüllt oder die Ausgangssprache ist leer.'],
        });
    }

    /**
     * Ein Text je Sprache: die Ausgangssprache in die uebrigen uebersetzen.
     * Ausgefuellte Sprachen bleiben, ausser "ueberschreiben" ist gewaehlt.
     *
     * @param  array<string, string>  $texts
     * @param  array<string, string>  $errors
     * @return array<string, string>
     */
    protected function translateMap(array $texts, DeepLTranslationService $deepl, int &$translated, array &$errors): array
    {
        $source = CustomEvent::sourceLocale();
        $original = trim((string) ($texts[$source] ?? ''));

        if ($original === '') {
            return $texts;
        }

        foreach (CountryTravelInfo::locales() as $locale) {
            if ($locale === $source || (! $this->overwriteNoteTranslations && filled($texts[$locale] ?? null))) {
                continue;
            }

            try {
                $texts[$locale] = $deepl->translate($original, $locale, $source);
                $translated++;
            } catch (\Throwable $e) {
                $errors[strtoupper($locale)] = strtoupper($locale).': '.$e->getMessage();
            }
        }

        return $texts;
    }

    protected function aiArea(): string
    {
        return 'countries';
    }

    /**
     * Die aktuellen Formularwerte zu den Platzhaltern (siehe AiAreas).
     */
    protected function aiContext(): array
    {
        // Jedes Feld des Risikoprofils einzeln – damit die Feldpruefung je Feld urteilen kann.
        $riskFields = [];
        foreach (CountryRiskProfile::categories() as $category => $definition) {
            foreach ($definition['fields'] as $field => $meta) {
                $riskFields[CountryRiskProfile::placeholderKey($category, $field)] = CountryRiskProfile::describe($meta, $this->riskProfile[$category][$field] ?? null);
            }
        }

        $travel = [
            'territory_type' => CountryTravelInfo::TERRITORY_TYPES[$this->territoryType] ?? $this->territoryType,
            'parent_country' => collect($this->parentOptions)->firstWhere('value', (int) $this->parentCountryId)['label'] ?? null,
            'driving_side' => CountryTravelInfo::DRIVING_SIDES[$this->drivingSide] ?? null,
            'plug_types' => implode(', ', (array) ($this->travelInfo['plug_types'] ?? [])) ?: null,
            'voltage' => $this->travelInfo['voltage'] ?: null,
            'frequency' => $this->travelInfo['frequency'] ?: null,
        ];
        foreach (CountryTravelInfo::TIPPING_CATEGORIES as $category => $label) {
            $row = $this->travelInfo['tipping'][$category] ?? [];
            $travel['tipping_'.$category.'_mode'] = CountryTravelInfo::TIPPING_MODES[$row['mode'] ?? 'range'] ?? null;
            $travel['tipping_'.$category.'_from'] = ($row['from'] ?? '') ?: null;
            $travel['tipping_'.$category.'_to'] = ($row['to'] ?? '') ?: null;
            $travel['tipping_'.$category.'_unit'] = CountryTravelInfo::TIPPING_UNITS[$row['unit'] ?? ''] ?? null;
            $travel['tipping_'.$category.'_currency'] = ($row['currency'] ?? '') ?: null;
            foreach (CountryTravelInfo::locales() as $locale) {
                $travel['tipping_'.$category.'_description_'.$locale] = ($row['description'][$locale] ?? '') ?: null;
            }
        }
        foreach (CountryTravelInfo::EMERGENCY as $key => $label) {
            $travel['emergency_'.$key] = ($this->travelInfo['emergency'][$key] ?? '') ?: null;
        }
        $travel['religions'] = implode(', ', array_map(fn ($key) => CountryTravelInfo::RELIGIONS[$key][0] ?? $key, (array) ($this->travelInfo['religions'] ?? []))) ?: null;
        $travel['national_day_date'] = ($this->travelInfo['national_day']['date'] ?? '') ?: null;
        foreach (CountryTravelInfo::locales() as $locale) {
            $travel['national_day_name_'.$locale] = ($this->travelInfo['national_day']['name'][$locale] ?? '') ?: null;
        }
        foreach (CountryTravelInfo::allTexts() as $field => [$label]) {
            foreach (CountryTravelInfo::locales() as $locale) {
                $travel[$field.'_'.$locale] = ($this->travelInfo['texts'][$field][$locale] ?? '') ?: null;
            }
        }

        $images = $this->images->map(fn (CountryImage $image) => ($image->isHero() ? 'Titelbild' : 'Galerie').': '
            .($image->original_name ?: basename($image->path)).' ('.$image->width.'×'.$image->height.')'
            .' – Alt: '.(json_encode($this->imageMeta[$image->id]['alt'] ?? [], JSON_UNESCAPED_UNICODE) ?: '{}')
            .', Bildunterschrift: '.(json_encode($this->imageMeta[$image->id]['caption'] ?? [], JSON_UNESCAPED_UNICODE) ?: '{}')
            .', Urheber: '.(($this->imageMeta[$image->id]['credit'] ?? '') ?: '–')
            .', Lizenz: '.(($this->imageMeta[$image->id]['license'] ?? '') ?: '–'))->values()->all();

        $holidays = $this->holidays->map(fn (CountryHoliday $holiday) => $holiday->date->format('d.m.Y').' ('.CountryTravelInfo::weekday($holiday->date).')'.($holiday->regions->isNotEmpty() ? ' [nur '.$holiday->regions->map(fn ($region) => $region->getName('de'))->implode(', ').']' : '').': '.json_encode($this->holidayRows[$holiday->id]['name'] ?? [], JSON_UNESCAPED_UNICODE)
            .(($this->holidayRows[$holiday->id]['comment'][CustomEvent::sourceLocale()] ?? '') !== '' ? ' – '.$this->holidayRows[$holiday->id]['comment'][CustomEvent::sourceLocale()] : ''))->values()->all();

        return $riskFields + $travel + [
            'holidays_year' => $this->holidayYear,
            'holidays_count' => $this->holidays->count(),
            'holidays' => $holidays,
            'taxi_apps' => $this->selectedTaxiApps->map(fn (TaxiApp $app) => $app->name.($app->website_url ? ' – '.$app->website_url : ''))->values()->all(),
            'taxi_apps_available' => $this->taxiAppOptions->pluck('name')->values()->all(),
            'mobile_operators' => $this->selectedMobileOperators->map(fn (MobileOperator $operator) => $operator->name.($operator->website_url ? ' – '.$operator->website_url : ''))->values()->all(),
            'mobile_operators_available' => MobileOperator::query()->active()->ordered()->pluck('name')->values()->all(),
            'flag' => $this->record?->flag_url,
            'images_count' => $this->images->count(),
            'images' => $images,
            'name' => $this->nameDe,
            'name_en' => $this->nameEn,
            'names' => collect($this->extraNames)->filter(fn (array $row) => ($row['name'] ?? '') !== '')->map(fn (array $row) => ($row['code'] ?? '').': '.$row['name'])->values()->all(),
            'iso_code' => $this->isoCode,
            'iso3_code' => $this->iso3Code,
            'continent' => $this->continents->firstWhere('id', (int) $this->continentId)?->getName('de'),
            'is_eu_member' => $this->isEuMember,
            'is_schengen_member' => $this->isSchengenMember,
            'currency_code' => $this->currencyCode,
            'currency_name' => $this->currencyName,
            'currency_symbol' => $this->currencySymbol,
            'phone_prefix' => $this->phonePrefix,
            'timezone' => $this->timezone,
            'population' => $this->population,
            'area_km2' => $this->areaKm2,
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }

    protected function aiReviewHint(string $section): ?string
    {
        return match ($section) {
            'description' => CountryTravelInfo::descriptionReviewHint(),
            'details' => CountryTravelInfo::reviewHint(),
            'tipping' => CountryTravelInfo::tippingReviewHint(),
            'power' => CountryTravelInfo::powerReviewHint(),
            default => null,
        };
    }

    /**
     * Die Begruendung der KI kommt in die Notiz des Punktes (Ausgangssprache);
     * eine vorhandene Notiz bleibt stehen, der Text wird angehaengt.
     */
    protected function aiApplyNote(string $key, string $text): bool
    {
        $resolved = CountryRiskProfile::resolvePlaceholder($key);

        if (! $resolved || ! CountryRiskProfile::hasNote($resolved[2])) {
            return false;
        }

        [$category, $field] = $resolved;
        $locale = CustomEvent::sourceLocale();
        $current = trim((string) ($this->riskProfile[$category]['notes'][$field][$locale] ?? ''));

        if (! str_contains($current, $text)) {
            $this->riskProfile[$category]['notes'][$field][$locale] = ltrim($current."\n".$text);
        }

        return true;
    }

    /**
     * Vorschlag der KI-Feldpruefung in das Formular uebernehmen.
     */
    protected function aiApply(string $key, string $value): bool
    {
        if ($resolved = CountryRiskProfile::resolvePlaceholder($key)) {
            [$category, $field, $meta] = $resolved;
            $parsed = CountryRiskProfile::parseSuggestion($meta, $value);

            if ($parsed === null) {
                return false;
            }

            $this->riskProfile[$category][$field] = $parsed;
            unset($this->overallRisk);

            return true;
        }

        if ($this->aiApplyTravel($key, $value)) {
            return true;
        }

        switch ($key) {
            case 'name': $this->nameDe = $value;

                return true;
            case 'name_en': $this->nameEn = $value;

                return true;
            case 'iso_code': $this->isoCode = mb_strtoupper($value);

                return true;
            case 'iso3_code': $this->iso3Code = mb_strtoupper($value);

                return true;
            case 'is_eu_member': $this->isEuMember = $this->aiBool($value);

                return true;
            case 'is_schengen_member': $this->isSchengenMember = $this->aiBool($value);

                return true;
            case 'currency_code': $this->currencyCode = mb_strtoupper($value);

                return true;
            case 'currency_name': $this->currencyName = $value;

                return true;
            case 'currency_symbol': $this->currencySymbol = $value;

                return true;
            case 'phone_prefix': $this->phonePrefix = $value;

                return true;
            case 'timezone': $this->timezone = $value;

                return true;
            case 'population': $this->population = $this->aiNumber($value);

                return true;
            case 'area_km2': $this->areaKm2 = $this->aiNumber($value);

                return true;
            case 'lat': $this->lat = $this->aiNumber($value);

                return true;
            case 'lng': $this->lng = $this->aiNumber($value);

                return true;
            case 'continent':
                $continent = $this->aiMatch($this->continents, $value, fn ($continent) => $continent->getName('de'))
                    ?? $this->aiMatch($this->continents, $value, fn ($continent) => $continent->getName('en'));
                if ($continent) {
                    $this->continentId = (string) $continent->id;
                }

                return $continent !== null;
        }

        return false;
    }

    /**
     * Vorschlaege zu den Reiseinformationen uebernehmen.
     */
    protected function aiApplyTravel(string $key, string $value): bool
    {
        if (str_starts_with($key, 'emergency_')) {
            $field = substr($key, strlen('emergency_'));
            if (! isset(CountryTravelInfo::EMERGENCY[$field])) {
                return false;
            }
            $this->travelInfo['emergency'][$field] = trim($value);

            return true;
        }

        if (preg_match('/^tipping_('.implode('|', array_keys(CountryTravelInfo::TIPPING_CATEGORIES)).')_(from|to|unit|mode|currency|description_([a-z]{2}))$/', $key, $match)) {
            [, $category, $field] = $match;
            $locale = $match[3] ?? null;

            if ($locale !== null) {
                if (! in_array($locale, CountryTravelInfo::locales(), true)) {
                    return false;
                }
                $this->travelInfo['tipping'][$category]['description'][$locale] = trim($value);

                return true;
            }

            if ($field === 'mode') {
                $mode = CountryTravelInfo::parseOption(CountryTravelInfo::TIPPING_MODES, $value)
                    ?? (str_contains(mb_strtolower($value), 'fest') || str_contains(mb_strtolower($value), 'fix') ? 'fixed' : (str_contains(mb_strtolower($value), 'bis') || str_contains(mb_strtolower($value), 'spann') ? 'range' : null));
                if ($mode) {
                    $this->travelInfo['tipping'][$category]['mode'] = $mode;
                }

                return $mode !== null;
            }

            if ($field === 'currency') {
                // Nur Codes, die es in der Waehrungstabelle gibt.
                preg_match_all('/\b([A-Za-z]{3})\b/', $value, $codes);
                foreach ($codes[1] as $code) {
                    if (Currency::query()->active()->whereKey(strtoupper($code))->exists()) {
                        $this->travelInfo['tipping'][$category]['currency'] = strtoupper($code);

                        return true;
                    }
                }

                return false;
            }

            if ($field === 'unit') {
                $unit = CountryTravelInfo::parseOption(CountryTravelInfo::TIPPING_UNITS, $value)
                    ?? (str_contains($value, '%') || str_contains(mb_strtolower($value), 'prozent') ? 'percent' : null);
                if ($unit) {
                    $this->travelInfo['tipping'][$category]['unit'] = $unit;
                }

                return $unit !== null;
            }

            $number = CountryTravelInfo::parseNumber(preg_replace('/[^0-9.,]/', '', $value));
            if ($number === null) {
                return false;
            }
            $this->travelInfo['tipping'][$category][$field] = CountryTravelInfo::number($number);

            return true;
        }

        if ($key === 'national_day_date') {
            $date = rescue(fn () => \Illuminate\Support\Carbon::parse(trim($value))->format('Y-m-d'), null, false);
            if ($date === null) {
                return false;
            }
            $this->travelInfo['national_day']['date'] = $date;

            return true;
        }

        if (preg_match('/^national_day_name_([a-z]{2})$/', $key, $match) && in_array($match[1], CountryTravelInfo::locales(), true)) {
            $this->travelInfo['national_day']['name'][$match[1]] = trim($value);

            return true;
        }

        foreach (CountryTravelInfo::allTexts() as $field => [, $type]) {
            foreach (CountryTravelInfo::locales() as $locale) {
                if ($key === $field.'_'.$locale) {
                    $this->travelInfo['texts'][$field][$locale] = $type === 'tags' ? implode(', ', CountryTravelInfo::tags($value)) : trim($value);

                    return true;
                }
            }
        }

        switch ($key) {
            case 'territory_type':
                $type = CountryTravelInfo::parseOption(CountryTravelInfo::TERRITORY_TYPES, $value);
                if ($type) {
                    $this->territoryType = $type;
                }

                return $type !== null;
            case 'parent_country':
                $option = $this->aiMatch($this->parentOptions, $value, fn (array $option) => $option['label'])
                    ?? $this->aiMatch($this->parentOptions, $value, fn (array $option) => $option['code']);
                if ($option) {
                    $this->parentCountryId = (string) $option['value'];
                }

                return $option !== null;
            case 'driving_side':
                $side = CountryTravelInfo::parseOption(CountryTravelInfo::DRIVING_SIDES, $value)
                    ?? (str_contains(mb_strtolower($value), 'link') ? 'left' : (str_contains(mb_strtolower($value), 'recht') ? 'right' : null));
                if ($side) {
                    $this->drivingSide = $side;
                }

                return $side !== null;
            case 'plug_types':
                $this->travelInfo['plug_types'] = CountryTravelInfo::parsePlugTypes($value);

                return true;
            case 'religions':
                $religions = CountryTravelInfo::parseReligions($value);
                if ($religions !== []) {
                    $this->travelInfo['religions'] = $religions;
                }

                return $religions !== [];
            case 'voltage':
            case 'frequency':
                $this->travelInfo[$key] = $this->aiNumber($value);

                return true;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.countries.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neues Land');
    }
}
