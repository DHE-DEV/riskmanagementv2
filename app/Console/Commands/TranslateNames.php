<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\Region;
use App\Services\DeepLTranslationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Ergänzt fehlende Namen von Ländern, Kontinenten, Regionen, Städten und Event-Typen
 * in allen Sprachen aus EVENT_LANGUAGES. Vorhandene Namen bleiben unangetastet.
 *
 * - Ländernamen kommen zuerst aus der ICU-Länderliste (PHP intl), sonst von DeepL.
 * - Alles andere übersetzt DeepL, gebündelt zu je 50 Namen und mit Kontext
 *   (z. B. "Städte in Frankreich"), damit Eigennamen nicht wörtlich übersetzt werden.
 *
 * Eine neue Sprache: EVENT_LANGUAGES ergänzen, dann
 *   php artisan names:translate --dry-run
 *   php artisan names:translate
 */
class TranslateNames extends Command
{
    private const TYPES = ['event-types', 'continents', 'countries', 'regions', 'cities'];

    protected $signature = 'names:translate
                            {--type=* : Nur diese Arten: event-types, continents, countries, regions, cities}
                            {--locale=* : Nur diese Zielsprachen (Standard: alle aus EVENT_LANGUAGES)}
                            {--dry-run : Nur zählen, nichts übersetzen}';

    protected $description = 'Übersetzt fehlende Namen von Ländern, Kontinenten, Regionen, Städten und Event-Typen (intl/DeepL)';

    /** @var array<int, string> */
    private array $failures = [];

    public function handle(DeepLTranslationService $deepl): int
    {
        $types = $this->option('type') ?: self::TYPES;
        $unknownTypes = array_diff($types, self::TYPES);
        if ($unknownTypes !== []) {
            $this->error('Unbekannte Art: '.implode(', ', $unknownTypes).'. Erlaubt: '.implode(', ', self::TYPES));

            return self::FAILURE;
        }

        $locales = $this->option('locale') ?: CustomEvent::translationLocales();
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $deepl->isConfigured()) {
            $this->error('DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).');

            return self::FAILURE;
        }

        $this->info('Zielsprachen: '.implode(', ', $locales));

        foreach (self::TYPES as $type) {
            if (in_array($type, $types, true)) {
                $this->translateType($type, $locales, $deepl, $dryRun);
            }
        }

        if (! $dryRun) {
            // Zwischengespeicherte Events tragen Länder- und Typnamen mit.
            Cache::forget('gtm_all_events');
        }

        if ($this->failures !== []) {
            $this->warn(count($this->failures).' Teil(e) fehlgeschlagen – ein erneuter Lauf versucht nur die fehlenden Namen:');
            foreach ($this->failures as $failure) {
                $this->line('  '.$failure);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $locales
     */
    private function translateType(string $type, array $locales, DeepLTranslationService $deepl, bool $dryRun): void
    {
        $records = $this->records($type);
        $changed = [];
        $summary = [];

        foreach ($locales as $locale) {
            $pending = $records->filter(fn (Model $record) => $this->needsName($type, $record, $locale))->values();
            $fromIntl = 0;
            $fromDeepl = 0;

            if ($type === 'countries') {
                foreach ($pending as $index => $country) {
                    $intlName = $this->intlCountryName($country, $locale);
                    if ($intlName !== null) {
                        $fromIntl++;
                        if (! $dryRun) {
                            $this->storeName($type, $country, $locale, $intlName);
                            $changed[$country->getKey()] = $country;
                        }
                        $pending->forget($index);
                    }
                }
            }

            foreach ($this->groups($type, $pending, $locale) as $group) {
                $fromDeepl += count($group['records']);

                if ($dryRun) {
                    continue;
                }

                foreach (array_chunk($group['records'], 50) as $chunk) {
                    try {
                        $names = $deepl->translateBatch(
                            array_map(fn (Model $record) => $this->sourceName($type, $record, $group['source']), $chunk),
                            $locale,
                            $group['source'],
                            $group['context'],
                        );
                    } catch (\Throwable $e) {
                        $this->failures[] = "{$type} → {$locale} ({$group['context']}): {$e->getMessage()}";

                        continue;
                    }

                    foreach ($chunk as $i => $record) {
                        if (filled($names[$i] ?? null)) {
                            $this->storeName($type, $record, $locale, trim($names[$i]));
                            $changed[$record->getKey()] = $record;
                        }
                    }
                }
            }

            if ($fromIntl + $fromDeepl > 0) {
                $summary[] = "{$locale}: ".($fromIntl > 0 ? "{$fromIntl} aus intl, " : '')."{$fromDeepl} per DeepL";
            }
        }

        foreach ($changed as $record) {
            $record->saveQuietly();
        }

        $this->line(sprintf('%-12s %s', $type, $summary === [] ? 'nichts fehlt' : implode(' · ', $summary).($dryRun ? ' (Probelauf)' : '')));
    }

    /**
     * @return Collection<int, Model>
     */
    private function records(string $type): Collection
    {
        return match ($type) {
            'event-types' => EventType::query()->orderBy('id')->get(),
            'continents' => Continent::query()->orderBy('id')->get(),
            'countries' => Country::query()->orderBy('id')->get(),
            'regions' => Region::query()->with('country:id,name_translations')->orderBy('id')->get(),
            'cities' => City::query()->with('country:id,name_translations')->orderBy('id')->get(),
        };
    }

    /**
     * @return array<string, ?string>
     */
    private function names(string $type, Model $record): array
    {
        if ($type === 'event-types') {
            return [CustomEvent::sourceLocale() => $record->name] + ($record->name_translations ?? []);
        }

        return $record->name_translations ?? [];
    }

    private function needsName(string $type, Model $record, string $locale): bool
    {
        $names = $this->names($type, $record);

        return blank($names[$locale] ?? null) && $this->sourceLocaleFor($type, $record, $locale) !== null;
    }

    /**
     * Ausgangssprache für die Übersetzung: bei Event-Typen Deutsch, bei Orten bevorzugt
     * Englisch (Ortsnamen sind dort am vollständigsten), sonst Deutsch.
     */
    private function sourceLocaleFor(string $type, Model $record, string $target): ?string
    {
        $names = $this->names($type, $record);
        $candidates = $type === 'event-types' ? [CustomEvent::sourceLocale()] : ['en', 'de'];

        foreach ($candidates as $candidate) {
            if ($candidate !== $target && filled($names[$candidate] ?? null)) {
                return $candidate;
            }
        }

        return null;
    }

    private function sourceName(string $type, Model $record, string $sourceLocale): string
    {
        return (string) $this->names($type, $record)[$sourceLocale];
    }

    private function storeName(string $type, Model $record, string $locale, string $name): void
    {
        $translations = $record->name_translations ?? [];
        $translations[$locale] = $name;
        $record->name_translations = $translations;
    }

    /**
     * Gruppen gleicher Ausgangssprache und gleichen Kontexts (Regionen und Städte je Land).
     *
     * @param  \Illuminate\Support\Collection<int, Model>  $pending
     * @return array<int, array{source: string, context: string, records: array<int, Model>}>
     */
    private function groups(string $type, $pending, string $locale): array
    {
        $groups = [];

        foreach ($pending as $record) {
            $source = $this->sourceLocaleFor($type, $record, $locale);
            $context = $this->deeplContext($type, $record);
            $key = $source.'|'.$context;

            $groups[$key] ??= ['source' => $source, 'context' => $context, 'records' => []];
            $groups[$key]['records'][] = $record;
        }

        return array_values($groups);
    }

    private function deeplContext(string $type, Model $record): string
    {
        $country = in_array($type, ['regions', 'cities'], true) ? $record->country?->getName('en') : null;

        return match ($type) {
            'event-types' => 'Categories of travel safety events shown to travellers, e.g. security, strike, health, entry requirements.',
            'continents' => 'Names of continents and world regions.',
            'countries' => 'Names of countries and territories.',
            'regions' => 'Geographic proper names of administrative regions'.($country ? " in {$country}" : '').'. Use the established name in the target language, otherwise keep the name unchanged.',
            'cities' => 'Geographic proper names of cities and towns'.($country ? " in {$country}" : '').'. Use the established name in the target language, otherwise keep the name unchanged.',
        };
    }

    /**
     * Ländername aus der ICU-Länderliste; null, wenn intl fehlt oder das Land nicht kennt.
     */
    private function intlCountryName(Country $country, string $locale): ?string
    {
        if (! class_exists(\Locale::class) || blank($country->iso_code)) {
            return null;
        }

        $name = \Locale::getDisplayRegion('-'.strtoupper($country->iso_code), $locale);

        return filled($name) && strcasecmp($name, $country->iso_code) !== 0 && ! str_starts_with($name, 'Unknown') ? $name : null;
    }
}
