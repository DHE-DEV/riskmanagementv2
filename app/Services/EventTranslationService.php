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
     * Übersetzt alle fehlenden Felder und speichert das Ergebnis, auch wenn einzelne
     * Sprachen fehlschlagen.
     *
     * @return array{translated: int, errors: array<int, string>}
     */
    public function translateMissing(CustomEvent $event): array
    {
        $source = CustomEvent::sourceLocale();
        $originalTitles = $event->title_translations ?? [];
        $originalContents = $event->popup_content_translations ?? [];
        $titles = $originalTitles;
        $contents = $originalContents;
        $translated = 0;
        $errors = [];

        foreach ($event->missingTranslationLocales() as $locale) {
            try {
                if (filled($titles[$source] ?? null) && blank($titles[$locale] ?? null)) {
                    $titles[$locale] = $this->deepl->translate($titles[$source], $locale, $source);
                    $translated++;
                }

                if (filled($contents[$source] ?? null) && blank($contents[$locale] ?? null)) {
                    $contents[$locale] = $this->deepl->translateHtml($contents[$source], $locale, $source);
                    $translated++;
                }
            } catch (\Throwable $e) {
                $errors[] = strtoupper($locale).': '.$e->getMessage();
            }
        }

        if ($titles !== $originalTitles || $contents !== $originalContents) {
            $event->update([
                'title_translations' => $titles,
                'popup_content_translations' => $contents,
            ]);
        }

        return ['translated' => $translated, 'errors' => $errors];
    }
}
