<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Jobs\SuggestRegionInfoJob;
use App\Models\CustomEvent;
use App\Models\RegionInfoRun;
use App\Services\DeepLTranslationService;
use App\Support\AdminV2\RegionInfo;
use Livewire\Attributes\Computed;

/**
 * Regionsinfos im Formular (Beschreibung, Reiseinfos, Fakten) – fuer Regionen
 * und ihre Teilgebiete: Monate, Pruefung, KI-Vorschlag im Hintergrund und
 * DeepL-Uebersetzung. Der Editor nennt mit suggestionRunAttributes(), wem der
 * KI-Lauf gilt, und liefert die Zeitzone des Landes (countryTimezone).
 */
trait EditsRegionInfo
{
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

    /**
     * Wem der KI-Lauf gilt, z. B. ['region_id' => 12].
     *
     * @return array<string, mixed>
     */
    abstract protected function suggestionRunAttributes(): array;

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
        unset($this->infoMeta['ai_filled'], $this->infoMeta['ai_created_at'], $this->infoMeta['last_reviewed_at'], $this->infoMeta['last_reviewed_by']);

        $this->dispatch('adminv2-toast', message: 'Als geprüft markiert – noch nicht gespeichert.');
    }

    /**
     * KI-Vorschlag im Hintergrund anfordern (dauert oft ueber eine Minute).
     */
    public function startSuggestion(): void
    {
        $this->modal('region-info-ai')->close();

        if (! $this->record) {
            $this->dispatch('adminv2-toast', message: 'Bitte zuerst speichern.', variant: 'danger');

            return;
        }

        $run = RegionInfoRun::create([
            'kind' => RegionInfoRun::KIND_SUGGEST,
            ...$this->suggestionRunAttributes(),
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
}
