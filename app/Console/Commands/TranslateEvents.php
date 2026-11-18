<?php

namespace App\Console\Commands;

use App\Models\CustomEvent;
use App\Services\EventTranslationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Übersetzt alle laufenden und künftigen globalen Ereignisse per DeepL in die
 * fehlenden Sprachen (EVENT_LANGUAGES, z. B. de, en, nl) und erneuert veraltete
 * Übersetzungen, die niemand von Hand geändert hat. Manuell gepflegte Übersetzungen
 * bleiben unangetastet; ein erneuter Lauf übersetzt nur, was noch offen ist.
 *
 *   php artisan events:translate --dry-run   (Anzahl und Zeichenmenge ansehen)
 *   php artisan events:translate             (übersetzen)
 */
class TranslateEvents extends Command
{
    protected $signature = 'events:translate
                            {--dry-run : Nur zählen und die zu übersetzende Zeichenmenge schätzen}
                            {--limit= : Höchstens so viele Ereignisse bearbeiten}';

    protected $description = 'Übersetzt laufende und künftige Ereignisse per DeepL in alle fehlenden Sprachen';

    public function handle(EventTranslationService $translator): int
    {
        $events = $this->eventsToTranslate();

        if ($this->option('limit') !== null) {
            $events = $events->take((int) $this->option('limit'));
        }

        $this->info($events->count().' Ereignis(se) mit fehlenden oder veralteten Übersetzungen ('.implode(', ', CustomEvent::translationLocales()).').');

        if ($this->option('dry-run')) {
            $this->line('Etwa '.number_format($events->sum(fn (CustomEvent $event) => $this->charactersToTranslate($event)), 0, ',', '.').' Zeichen gingen an DeepL.');

            return self::SUCCESS;
        }

        if ($events->isEmpty()) {
            return self::SUCCESS;
        }

        if (! $translator->isConfigured()) {
            $this->error('DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).');

            return self::FAILURE;
        }

        $translatedFields = 0;
        $failures = [];

        $this->withProgressBar($events, function (CustomEvent $event) use ($translator, &$translatedFields, &$failures) {
            $result = $translator->translateMissing($event);
            $translatedFields += $result['translated'];

            if ($result['errors'] !== []) {
                $failures[] = "#{$event->id}: ".implode(' | ', $result['errors']);
            }
        });

        $this->newLine(2);
        $this->info("{$translatedFields} Feld(er) übersetzt.");

        if ($failures !== []) {
            $this->warn(count($failures).' Ereignis(se) nicht vollständig übersetzt – ein erneuter Lauf versucht nur diese:');
            foreach ($failures as $failure) {
                $this->line('  '.$failure);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Freigegebene, aktive globale Ereignisse, die noch laufen oder erst beginnen,
     * und denen mindestens eine Übersetzung fehlt.
     *
     * @return \Illuminate\Support\Collection<int, CustomEvent>
     */
    private function eventsToTranslate(): \Illuminate\Support\Collection
    {
        return CustomEvent::query()
            ->active()
            ->notArchived()
            ->approved()
            ->global()
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', now()->startOfDay()))
            ->orderBy('start_date')
            ->get()
            ->filter(fn (CustomEvent $event) => $event->localesNeedingTranslation() !== [])
            ->values();
    }

    private function charactersToTranslate(CustomEvent $event): int
    {
        $source = CustomEvent::sourceLocale();
        $titles = $event->title_translations ?? [];
        $contents = $event->popup_content_translations ?? [];
        $characters = 0;

        $refresh = $event->autoRefreshableTranslationLocales();

        foreach ($event->localesNeedingTranslation() as $locale) {
            $overwrite = in_array($locale, $refresh, true);

            if ($overwrite || blank($titles[$locale] ?? null)) {
                $characters += mb_strlen((string) ($titles[$source] ?? ''));
            }
            if ($overwrite || blank($contents[$locale] ?? null)) {
                $characters += mb_strlen((string) ($contents[$source] ?? ''));
            }
        }

        return $characters;
    }
}
