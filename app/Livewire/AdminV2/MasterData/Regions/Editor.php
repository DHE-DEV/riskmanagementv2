<?php

namespace App\Livewire\AdminV2\MasterData\Regions;

use App\Jobs\SuggestRegionInfoJob;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\EditsCoordinates;
use App\Livewire\AdminV2\Concerns\EditsMasterData;
use App\Livewire\AdminV2\Concerns\RunsAiChecks;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Services\DeepLTranslationService;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\RegionInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Stammdaten > Regionen: eine Region anlegen oder bearbeiten – mit den
 * Regionsinfos (Beschreibung, Reiseinfos, Fakten), die nur fuer diese Region
 * gelten (siehe RegionInfo) und sich per KI vorbefuellen lassen.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2, EditsCoordinates, EditsMasterData, RunsAiChecks;

    /** So viele Staedte zeigt die Seitenspalte. */
    public const RELATED_LIMIT = 12;

    public string $nameDe = '';

    public string $nameEn = '';

    public string $code = '';

    public string $countryId = '';

    /** Kommagetrennt */
    public string $keywords = '';

    /** Regionsinfos als Formularwerte (RegionInfo::toForm) */
    public array $info = [];

    /** Verwaltungsangaben der Regionsinfos: ai_generated_at, ai_model, reviewed_at, reviewed_by */
    public array $infoMeta = [];

    /** Laufender KI-Vorschlag (RegionInfoRun) */
    public ?int $suggestRunId = null;

    /** KI-Vorschlag: vorhandene Texte ueberschreiben statt nur Leeres zu fuellen */
    public bool $suggestOverwrite = false;

    /** DeepL: bereits ausgefuellte Sprachen ueberschreiben */
    public bool $overwriteNoteTranslations = false;

    public function mount(?int $region = null): void
    {
        $this->info = RegionInfo::toForm(null);

        if ($region === null) {
            // Vorbelegt, wenn die Seite aus einem Land heraus geoeffnet wird.
            $country = (int) request()->query('country');
            $this->countryId = $country && Country::whereKey($country)->exists() ? (string) $country : '';

            return;
        }

        $record = Region::withTrashed()->findOrFail($region);

        $this->recordId = $record->id;
        $this->nameDe = (string) ($record->name_translations['de'] ?? '');
        $this->nameEn = (string) ($record->name_translations['en'] ?? '');
        $this->code = (string) $record->code;
        $this->countryId = (string) $record->country_id;
        $this->keywords = implode(', ', $record->keywords ?? []);
        $this->info = RegionInfo::toForm($record->info);
        $this->infoMeta = (array) ($record->info['meta'] ?? []);
        $this->fillCoordinates($record);
    }

    protected function masterDataModel(): string
    {
        return Region::class;
    }

    protected function routeBase(): string
    {
        return 'adminv2.master-data.regions';
    }

    protected function createAnotherParameters(Model $record): array
    {
        return ['country' => $record->country_id];
    }

    #[Computed]
    public function countryOptions(): Collection
    {
        return Country::query()
            // Das Land eines Altbestands bleibt waehlbar, auch wenn es im Papierkorb liegt.
            ->when($this->record?->country_id, fn ($query, $id) => $query->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $id)))
            ->orderByRaw(MasterData::nameSql('countries'))
            ->get(['id', 'iso_code', 'name_translations']);
    }

    /**
     * Die Staedte der Region: Anzahl und die groessten fuer die Seitenspalte.
     *
     * @return array{count: int, items: Collection}|null
     */
    #[Computed]
    public function cities(): ?array
    {
        return $this->record ? [
            'count' => $this->record->cities()->count(),
            'items' => $this->record->cities()->orderByDesc('is_regional_capital')->orderByDesc('population')->limit(self::RELATED_LIMIT)->get(),
        ] : null;
    }

    public function save(bool $another = false): void
    {
        $this->code = trim($this->code);
        $this->normalizeCoordinates();

        $this->validate([
            'nameDe' => ['required', 'string', 'max:255'],
            'nameEn' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10'],
            'countryId' => ['required', Rule::in($this->countryOptions->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'info.population' => ['nullable', 'regex:/^[0-9 .]*$/'],
            'info.area_km2' => ['nullable', 'regex:/^[0-9 .]*$/'],
            'info.timezone' => ['nullable', Rule::in(\DateTimeZone::listIdentifiers())],
            'info.best_months.*' => ['integer', 'between:1,12'],
        ] + $this->infoTextRules() + $this->coordinateRules(), [
            'info.population.regex' => 'Bitte nur eine ganze Zahl angeben.',
            'info.area_km2.regex' => 'Bitte nur eine ganze Zahl angeben.',
            'info.timezone.in' => 'Bitte eine Zeitzone aus der Liste wählen.',
            'info.texts.*.*.max' => 'Der Text ist zu lang (höchstens :max Zeichen).',
            'nameDe.required' => 'Bitte den deutschen Namen angeben.',
            'code.required' => 'Bitte einen Code angeben, z. B. BY für Bayern.',
            'code.max' => 'Der Code darf höchstens 10 Zeichen haben.',
            'countryId.required' => 'Bitte ein Land wählen.',
            'countryId.in' => 'Bitte ein Land wählen.',
        ] + $this->coordinateMessages());

        $record = $this->record ?? new Region;
        $created = ! $record->exists;

        $record->fill([
            'name_translations' => $this->translations($record->name_translations, $this->nameDe, $this->nameEn),
            'code' => $this->code,
            'country_id' => (int) $this->countryId,
            'keywords' => $this->tags($this->keywords),
            'info' => RegionInfo::fromForm($this->info, Country::withTrashed()->find((int) $this->countryId), $this->infoMeta),
        ] + $this->coordinateValues())->save();

        unset($this->cities);

        $this->finishSave($record, $created, $another);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function infoTextRules(): array
    {
        $rules = [];

        foreach (array_keys(RegionInfo::allTexts()) as $field) {
            foreach (RegionInfo::locales() as $locale) {
                $rules["info.texts.{$field}.{$locale}"] = ['nullable', 'string', 'max:'.RegionInfo::limit($field)];
            }
        }

        return $rules;
    }

    /**
     * Zeitzone des Landes – die Region braucht nur eine eigene, wenn sie abweicht.
     */
    #[Computed]
    public function countryTimezone(): ?string
    {
        return $this->countryOptions->firstWhere('id', (int) $this->countryId)?->timezone ?: Country::withTrashed()->find((int) $this->countryId)?->timezone;
    }

    /**
     * Zeitzonen fuer die Auswahl, die des Landes zuerst.
     *
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function timezoneOptions(): array
    {
        return array_map(fn (string $zone) => ['value' => $zone, 'label' => str_replace('_', ' ', $zone)], \DateTimeZone::listIdentifiers());
    }

    public function toggleBestMonth(int $month): void
    {
        $months = RegionInfo::months((array) ($this->info['best_months'] ?? []));

        $this->info['best_months'] = in_array($month, $months, true)
            ? array_values(array_diff($months, [$month]))
            : RegionInfo::months([...$months, $month]);
    }

    public function infoStatus(): string
    {
        $current = RegionInfo::fromForm($this->info, null, $this->infoMeta);

        return RegionInfo::status($current);
    }

    /**
     * Den KI-Entwurf als geprueft kennzeichnen (wirksam mit "Speichern").
     */
    public function markReviewed(): void
    {
        $this->infoMeta['reviewed_at'] = now()->toIso8601String();
        $this->infoMeta['reviewed_by'] = (string) (auth('web')->user()?->name ?? '');

        $this->dispatch('adminv2-toast', message: 'Als geprüft markiert – noch nicht gespeichert.');
    }

    /**
     * KI-Vorschlag im Hintergrund anfordern (dauert oft ueber eine Minute).
     */
    public function startSuggestion(): void
    {
        $this->modal('region-info-ai')->close();

        if (! $this->record) {
            $this->dispatch('adminv2-toast', message: 'Bitte die Region zuerst speichern.', variant: 'danger');

            return;
        }

        $run = RegionInfoRun::create([
            'kind' => RegionInfoRun::KIND_SUGGEST,
            'region_id' => $this->record->id,
            'status' => RegionInfoRun::STATUS_RUNNING,
            'overwrite' => $this->suggestOverwrite,
            'started_by' => auth('web')->id(),
        ]);

        $this->suggestRunId = $run->id;

        SuggestRegionInfoJob::dispatch($run->id);

        $this->checkSuggestion();
    }

    /**
     * Fertigen KI-Vorschlag ins Formular uebernehmen (wird abgefragt, solange er laeuft).
     */
    public function checkSuggestion(): void
    {
        if (! $this->suggestRunId) {
            return;
        }

        $run = RegionInfoRun::query()->where('kind', RegionInfoRun::KIND_SUGGEST)->find($this->suggestRunId);

        if ($run?->isRunning()) {
            return;
        }

        $this->suggestRunId = null;
        $run?->delete();

        if ($run?->status !== RegionInfoRun::STATUS_DONE) {
            $this->dispatch('adminv2-toast', message: 'KI-Vorschlag fehlgeschlagen: '.($run?->error ?: 'unbekannter Fehler'), variant: 'danger');

            return;
        }

        $current = RegionInfo::fromForm($this->info, null) ?? [];
        $merged = RegionInfo::merge($current, (array) $run->result, $run->overwrite);

        if (($merged['timezone'] ?? null) === $this->countryTimezone) {
            unset($merged['timezone']);
        }

        $this->info = RegionInfo::toForm($merged);
        $this->infoMeta = ['ai_generated_at' => now()->toIso8601String(), 'ai_model' => (string) (\App\Support\AiSettings::model() ?? '')];

        $this->dispatch('adminv2-toast', message: 'KI-Vorschlag übernommen – bitte prüfen und speichern.');
    }

    public function cancelSuggestion(): void
    {
        if ($this->suggestRunId) {
            RegionInfoRun::query()->whereKey($this->suggestRunId)->delete();
        }

        $this->suggestRunId = null;
    }

    /**
     * Texte eines Abschnitts per DeepL aus der Ausgangssprache uebersetzen
     * (gespeichert wird erst mit "Speichern").
     */
    public function translateTexts(string $section): void
    {
        $this->modal('translate-'.$section)->close();

        $fields = match ($section) {
            'description' => RegionInfo::DESCRIPTION_TEXTS,
            'travel' => RegionInfo::TRAVEL_TEXTS,
            default => null,
        };

        if ($fields === null) {
            return;
        }

        $deepl = app(DeepLTranslationService::class);

        if (! $deepl->isConfigured()) {
            $this->dispatch('adminv2-toast', message: 'DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).', variant: 'danger');

            return;
        }

        set_time_limit(120);

        $source = CustomEvent::sourceLocale();
        $translated = 0;
        $errors = [];

        foreach (array_keys($fields) as $field) {
            $original = trim((string) ($this->info['texts'][$field][$source] ?? ''));

            if ($original === '') {
                continue;
            }

            foreach (RegionInfo::locales() as $locale) {
                if ($locale === $source || (! $this->overwriteNoteTranslations && filled($this->info['texts'][$field][$locale] ?? null))) {
                    continue;
                }

                try {
                    $this->info['texts'][$field][$locale] = $deepl->translate($original, $locale, $source);
                    $translated++;
                } catch (\Throwable $e) {
                    $errors[strtoupper($locale)] = strtoupper($locale).': '.$e->getMessage();
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
        return 'regions';
    }

    /**
     * Die aktuellen Formularwerte zu den Platzhaltern (siehe AiAreas).
     */
    protected function aiContext(): array
    {
        $cities = $this->cities;

        return [
            'name' => $this->nameDe,
            'name_en' => $this->nameEn,
            'code' => $this->code,
            'country' => $this->countryOptions->firstWhere('id', (int) $this->countryId)?->getName('de'),
            'description' => (string) ($this->info['texts']['short_description'][CustomEvent::sourceLocale()] ?? ''),
            'keywords' => $this->keywords,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'cities_count' => $cities['count'] ?? 0,
            'cities' => $cities ? $cities['items']->map(fn ($city) => $city->getName('de'))->all() : [],
        ];
    }

    /**
     * Vorschlag der KI-Feldpruefung in das Formular uebernehmen.
     */
    protected function aiApply(string $key, string $value): bool
    {
        switch ($key) {
            case 'name': $this->nameDe = $value;

                return true;
            case 'name_en': $this->nameEn = $value;

                return true;
            case 'code': $this->code = mb_strtoupper($value);

                return true;
            case 'description': $this->info['texts']['short_description'][CustomEvent::sourceLocale()] = $value;

                return true;
            case 'keywords': $this->keywords = $value;

                return true;
            case 'lat': $this->lat = $this->aiNumber($value);

                return true;
            case 'lng': $this->lng = $this->aiNumber($value);

                return true;
            case 'country':
                $country = $this->aiMatch($this->countryOptions, $value, fn ($country) => $country->getName('de'))
                    ?? $this->aiMatch($this->countryOptions, $value, fn ($country) => (string) $country->iso_code);
                if ($country) {
                    $this->countryId = (string) $country->id;
                }

                return $country !== null;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin-v2.master-data.regions.editor')
            ->title($this->record ? $this->record->getName('de') : 'Neue Region');
    }
}
