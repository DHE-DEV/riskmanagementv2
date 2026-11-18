<?php

namespace App\Services;

use App\Models\CustomEvent;

/**
 * Ergänzt fehlende Übersetzungen eines Ereignisses (Titel und Beschreibung) per DeepL.
 * Vorhandene, auch manuell gepflegte Übersetzungen bleiben unangetastet.
 */
class EventTranslationService
{
    public function __construct(private DeepLTranslationService $deepl) {}

    public function isConfigured(): bool
    {
        return $this->deepl->isConfigured();
    }

    /**
     * Übersetzt fehlende Felder sowie veraltete Übersetzungen, die seit der letzten
     * maschinellen Übersetzung niemand von Hand geändert hat, und hält den übersetzten
     * Stand fest. Gespeichert wird auch, wenn einzelne Sprachen fehlschlagen.
     *
     * @return array{translated: int, errors: array<int, string>}
     */
    public function translateMissing(CustomEvent $event): array
    {
        $source = CustomEvent::sourceLocale();
        $titles = $event->title_translations ?? [];
        $contents = $event->popup_content_translations ?? [];
        $refresh = $event->autoRefreshableTranslationLocales();
        $done = [];
        $translated = 0;
        $errors = [];

        foreach ($event->localesNeedingTranslation() as $locale) {
            $overwrite = in_array($locale, $refresh, true);

            try {
                if (filled($titles[$source] ?? null) && ($overwrite || blank($titles[$locale] ?? null))) {
                    $titles[$locale] = $this->deepl->translate($titles[$source], $locale, $source);
                    $translated++;
                }

                if (filled($contents[$source] ?? null) && ($overwrite || blank($contents[$locale] ?? null))) {
                    $contents[$locale] = $this->deepl->translateHtml($contents[$source], $locale, $source);
                    $translated++;
                }

                $done[] = $locale;
            } catch (\Throwable $e) {
                $errors[] = strtoupper($locale).': '.$e->getMessage();
            }
        }

        if ($done !== []) {
            $event->fill([
                'title_translations' => $titles,
                'popup_content_translations' => $contents,
            ]);
            $event->markTranslated($done);
            $event->save();
        }

        return ['translated' => $translated, 'errors' => $errors];
    }
}
