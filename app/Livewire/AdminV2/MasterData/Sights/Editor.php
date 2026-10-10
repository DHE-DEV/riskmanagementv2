<?php

namespace App\Livewire\AdminV2\MasterData\Sights;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
use App\Models\City;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Region;
use App\Models\Sight;
use App\Services\DeepLTranslationService;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\SightInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Sehenswuerdigkeiten: eine Sehenswuerdigkeit oder Unternehmung
 * anlegen oder bearbeiten – Land, optional Region (z. B. Nationalparks) und Stadt.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, RunsAiChecks;

    /** @var array<string, string> Name je Sprache */
    public array $names = [];

    public string $countryId = '';

    public string $regionId = '';

    public string $cityId = '';

    public string $category = 'landmark';

    public bool $isHighlight = false;

    public string $address = '';

    public string $websiteUrl = '';

    public string $ticketUrl = '';

    /** Texte und Besuchsdauer als Formularwerte (SightInfo::toForm) */
    public array $info = [];

    /** ai_generated_at, ai_model, geocoded, reviewed_at, reviewed_by */
    public array $infoMeta = [];

    /** DeepL: bereits ausgefuellte Sprachen ueberschreiben */
    public bool $overwriteNoteTranslations = false;

    public function mount(?int $sight = null): void
    {
        $this->info = SightInfo::toForm(null);
        $this->names = array_fill_keys(SightInfo::locales(), '');

        if ($sight === null) {
            // Vorbelegt, wenn die Seite aus einer Stadt, Region oder einem Land heraus geoeffnet wird.
            if ($city = City::find((int) request()->query('city'))) {
                $this->cityId = (string) $city->id;
                $this->regionId = (string) $city->region_id;
                $this->countryId = (string) $city->country_id;
            } elseif ($region = Region::find((int) request()->query('region'))) {
                $this->regionId = (string) $region->id;
                $this->countryId = (string) $region->country_id;
            } elseif (Country::whereKey((int) request()->query('country'))->exists()) {
                $this->countryId = (string) (int) request()->query('country');
            }

            return;
        }

        $record = Sight::withTrashed()->findOrFail($sight);

        $this->recordId = $record->id;
        foreach (SightInfo::locales() as $locale) {
            $this->names[$locale] = (string) ($record->name_translations[$locale] ?? '');
        }
        $this->countryId = (string) $record->country_id;
        $this->regionId = (string) $record->region_id;
        $this->cityId = (string) $record->city_id;
        $this->category = (string) $record->category;
        $this->isHighlight = (bool) $record->is_highlight;
        $this->address = (string) $record->address;
        $this->websiteUrl = (string) $record->website_url;
        $this->ticketUrl = (string) $record->ticket_url;
        $this->info = SightInfo::toForm($record->info);
        $this->infoMeta = (array) ($record->info['meta'] ?? []);
        $this->fillCoordinates($record);
    }

    protected function masterDataModel(): string
    {
        return Sight::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.sights';
    }

    protected function createAnotherParameters(Model $record): array
    {
        return array_filter(['country' => $record->country_id, 'region' => $record->region_id]);
    }

    public function updatedCountryId(): void
    {
        unset($this->regionOptions, $this->cityOptions);

        if ($this->regionId !== '' && ! $this->regionOptions->contains('id', (int) $this->regionId)) {
            $this->regionId = '';
        }
        if ($this->cityId !== '' && ! $this->cityOptions->contains('id', (int) $this->cityId)) {
            $this->cityId = '';
        }
    }

    public function updatedRegionId(): void
    {
        unset($this->cityOptions);

        if ($this->cityId !== '' && ! $this->cityOptions->contains('id', (int) $this->cityId)) {
            $this->cityId = '';
        }
    }

    public function updatedCityId(): void
    {
        // Die Region folgt der Stadt.
        $city = $this->cityOptions->firstWhere('id', (int) $this->cityId);
        if ($city && $city->region_id) {
            $this->regionId = (string) $city->region_id;
        }
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()
            ->when($this->record?->country_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->orderByRaw(MasterData::nameSql('countries'))
            ->get(['id', 'iso_code', 'name_translations', 'timezone']);
    }

    #[Computed]
    public function regionOptions(): Collection
    {
        return $this->countryId === '' ? collect() : Region::query()->where('country_id', (int) $this->countryId)->orderByRaw(MasterData::nameSql('regions'))->get(['id', 'code', 'name_translations']);
    }

    /**
     * Staedte des Landes – mit gewaehlter Region nur die der Region.
     */
    #[Computed]
    public function cityOptions(): Collection
    {
        if ($this->countryId === '') {
            return collect();
        }

        return City::query()
            ->where('country_id', (int) $this->countryId)
            ->when($this->regionId !== '', fn ($query) => $query->where('region_id', (int) $this->regionId))
            ->orderByRaw(MasterData::nameSql('cities'))
            ->get(['id', 'region_id', 'name_translations']);
    }

    public function status(): string
    {
        return SightInfo::status(['meta' => $this->infoMeta]);
    }

    public function markReviewed(): void
    {
        $this->infoMeta['reviewed_at'] = now()->toIso8601String();
        $this->infoMeta['reviewed_by'] = (string) (auth('web')->user()?->name ?? '');

        $this->dispatch('adminv2-toast', message: 'Als geprüft markiert – noch nicht gespeichert.');
    }

    public function save(bool $another = false): void
    {
        $this->normalizeCoordinates();
        $this->websiteUrl = trim($this->websiteUrl);
        $this->ticketUrl = trim($this->ticketUrl);
        unset($this->regionOptions, $this->cityOptions);

        $source = CustomEvent::sourceLocale();
        $rules = [
            "names.{$source}" => ['required', 'string', 'max:255'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'countryId' => ['required', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'regionId' => ['nullable', Rule::in($this->regionOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'cityId' => ['nullable', Rule::in($this->cityOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'category' => ['required', Rule::in(array_keys(SightInfo::CATEGORIES))],
            'address' => ['nullable', 'string', 'max:500'],
            'websiteUrl' => ['nullable', 'url:http,https', 'max:500'],
            'ticketUrl' => ['nullable', 'url:http,https', 'max:500'],
            'info.visit_minutes' => ['nullable', 'regex:/^[0-9 .]*$/'],
        ];
        foreach (array_keys(SightInfo::TEXTS) as $field) {
            foreach (SightInfo::locales() as $locale) {
                $rules["info.texts.{$field}.{$locale}"] = ['nullable', 'string', 'max:'.SightInfo::limit($field)];
            }
        }

        $this->validate($rules + $this->coordinateRules(), [
            "names.{$source}.required" => 'Bitte den Namen in der Ausgangssprache angeben.',
            'countryId.required' => 'Bitte ein Land wählen.',
            'countryId.in' => 'Bitte ein Land wählen.',
            'regionId.in' => 'Diese Region gehört nicht zum gewählten Land.',
            'cityId.in' => 'Diese Stadt gehört nicht zum gewählten Land bzw. zur Region.',
            'websiteUrl.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'ticketUrl.url' => 'Bitte eine vollständige Adresse mit https:// angeben.',
            'info.visit_minutes.regex' => 'Bitte die Dauer in Minuten angeben.',
            'info.texts.*.*.max' => 'Der Text ist zu lang (höchstens :max Zeichen).',
        ] + $this->coordinateMessages());

        $record = $this->record ?? new Sight;
        $created = ! $record->exists;

        $record->fill([
            'name_translations' => array_filter(array_map(fn ($name) => trim((string) $name), $this->names), fn ($name) => $name !== ''),
            'country_id' => (int) $this->countryId,
            'region_id' => $this->regionId === '' ? null : (int) $this->regionId,
            'city_id' => $this->cityId === '' ? null : (int) $this->cityId,
            'category' => $this->category,
            'is_highlight' => $this->isHighlight,
            'address' => trim($this->address) ?: null,
            'website_url' => $this->websiteUrl ?: null,
            'ticket_url' => $this->ticketUrl ?: null,
            'info' => SightInfo::fromForm($this->info, $this->infoMeta),
        ] + $this->coordinateValues())->save();

        $this->finishSave($record, $created, $another);
    }

    /**
     * Texte per DeepL aus der Ausgangssprache uebersetzen (gespeichert wird mit "Speichern").
     */
    public function translateTexts(string $section): void
    {
        $this->modal('translate-'.$section)->close();

        $deepl = app(DeepLTranslationService::class);

        if (! $deepl->isConfigured()) {
            $this->dispatch('adminv2-toast', message: 'DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).', variant: 'danger');

            return;
        }

        set_time_limit(120);

        $source = CustomEvent::sourceLocale();
        $translated = 0;
        $errors = [];
        $translate = function (string $original, string $locale) use ($deepl, $source, &$translated, &$errors): ?string {
            try {
                $translated++;

                return $deepl->translate($original, $locale, $source);
            } catch (\Throwable $e) {
                $translated--;
                $errors[strtoupper($locale)] = strtoupper($locale).': '.$e->getMessage();

                return null;
            }
        };

        foreach (SightInfo::locales() as $locale) {
            if ($locale === $source) {
                continue;
            }

            if (filled($this->names[$source] ?? null) && ($this->overwriteNoteTranslations || blank($this->names[$locale] ?? null))) {
                $this->names[$locale] = $translate($this->names[$source], $locale) ?? ($this->names[$locale] ?? '');
            }

            foreach (array_keys(SightInfo::TEXTS) as $field) {
                $original = trim((string) ($this->info['texts'][$field][$source] ?? ''));
                if ($original !== '' && ($this->overwriteNoteTranslations || blank($this->info['texts'][$field][$locale] ?? null))) {
                    $this->info['texts'][$field][$locale] = $translate($original, $locale) ?? ($this->info['texts'][$field][$locale] ?? '');
                }
            }
        }

        $this->dispatch('adminv2-toast', ...match (true) {
            $errors !== [] => ['message' => 'Übersetzung teilweise fehlgeschlagen – '.implode(' | ', $errors), 'variant' => 'danger'],
            $translated > 0 => ['message' => $translated.' '.($translated === 1 ? 'Text' : 'Texte').' übersetzt – noch nicht gespeichert.'],
            default => ['message' => 'Nichts zu übersetzen – alle Sprachen waren bereits ausgefüllt oder die Ausgangssprache ist leer.'],
        });
    }

    protected function aiArea(): string
    {
        return 'sights';
    }

    protected function aiContext(): array
    {
        $source = CustomEvent::sourceLocale();

        return [
            'name' => $this->names[$source] ?? '',
            'name_en' => $this->names['en'] ?? '',
            'category' => SightInfo::categoryLabel($this->category),
            'country' => $this->countryOptions->firstWhere('id', (int) $this->countryId)?->getName('de'),
            'region' => $this->regionOptions->firstWhere('id', (int) $this->regionId)?->getName('de'),
            'city' => $this->cityOptions->firstWhere('id', (int) $this->cityId)?->getName('de'),
            'address' => $this->address,
            'website' => $this->websiteUrl,
            'short_description' => $this->info['texts']['short_description'][$source] ?? '',
            'description' => $this->info['texts']['description'][$source] ?? '',
            'opening_hours' => $this->info['texts']['opening_hours'][$source] ?? '',
            'admission' => $this->info['texts']['admission'][$source] ?? '',
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }

    protected function aiApply(string $key, string $value): bool
    {
        $source = CustomEvent::sourceLocale();

        switch ($key) {
            case 'name': $this->names[$source] = $value;

                return true;
            case 'name_en': $this->names['en'] = $value;

                return true;
            case 'address': $this->address = $value;

                return true;
            case 'website': $this->websiteUrl = $value;

                return true;
            case 'short_description':
            case 'description':
            case 'opening_hours':
            case 'admission':
                $this->info['texts'][$key][$source] = $value;

                return true;
            case 'lat': $this->lat = $this->aiNumber($value);

                return true;
            case 'lng': $this->lng = $this->aiNumber($value);

                return true;
            case 'category':
                $match = collect(SightInfo::CATEGORIES)->search(fn ($category, $categoryKey) => mb_strtolower($category[0]) === mb_strtolower($value) || $categoryKey === $value);
                if ($match !== false) {
                    $this->category = (string) $match;
                }

                return $match !== false;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.sights.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neue Sehenswürdigkeit');
    }
}
