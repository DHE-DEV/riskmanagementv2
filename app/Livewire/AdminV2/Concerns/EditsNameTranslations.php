<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Models\Country;
use App\Models\CustomEvent;
use App\Services\DeepLTranslationService;

/**
 * Namen in den Sprachen aus EVENT_LANGUAGES jenseits von Deutsch und Englisch
 * (die stehen in nameDe / nameEn der Stammdaten-Editoren). Fehlende Sprachen –
 * auch Englisch – lassen sich per DeepL ergänzen; gespeichert wird mit dem Formular.
 */
trait EditsNameTranslations
{
    /** @var array<string, string> Name je weiterer Sprache */
    public array $otherNames = [];

    /**
     * Sprachen aus EVENT_LANGUAGES ohne eigenes Feld (alles außer de und en).
     *
     * @return array<int, string>
     */
    public function otherNameLocales(): array
    {
        return array_values(array_diff(CustomEvent::translationLocales(), ['de', 'en']));
    }

    protected function fillOtherNames(?array $translations): void
    {
        foreach ($this->otherNameLocales() as $locale) {
            $this->otherNames[$locale] = (string) ($translations[$locale] ?? '');
        }
    }

    /**
     * Die weiteren Sprachen in die Übersetzungen übernehmen; ein geleertes Feld entfernt die Sprache.
     *
     * @return array<string, string>
     */
    protected function withOtherNames(array $translations): array
    {
        foreach ($this->otherNameLocales() as $locale) {
            $name = trim((string) ($this->otherNames[$locale] ?? ''));

            if ($name === '') {
                unset($translations[$locale]);
            } else {
                $translations[$locale] = $name;
            }
        }

        return $translations;
    }

    /**
     * Kontext für DeepL, damit Eigennamen nicht wörtlich übersetzt werden.
     */
    protected function nameTranslationContext(): string
    {
        $countryId = property_exists($this, 'countryId') ? (int) $this->countryId : 0;
        $country = $countryId > 0 ? Country::find($countryId)?->getName('en') : null;

        return 'Geographic proper name'.($country ? " in {$country}" : '').'. Use the established name in the target language, otherwise keep the name unchanged.';
    }

    /**
     * Leere Sprachen (Englisch und weitere) per DeepL füllen – ohne zu speichern.
     */
    public function translateNames(DeepLTranslationService $deepl): void
    {
        $german = trim($this->nameDe);
        $english = trim($this->nameEn);

        if ($german === '' && $english === '') {
            $this->addError('nameDe', 'Bitte zuerst den deutschen Namen eintragen.');

            return;
        }

        if (! $deepl->isConfigured()) {
            $this->dispatch('adminv2-toast', message: 'DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).', variant: 'danger');

            return;
        }

        $translated = 0;
        $errors = [];

        try {
            if ($english === '' && $german !== '') {
                $this->nameEn = $english = $deepl->translateBatch([$german], 'en', 'de', $this->nameTranslationContext())[0];
                $translated++;
            }
        } catch (\Throwable $e) {
            $errors[] = 'EN: '.$e->getMessage();
        }

        // Ortsnamen sind auf Englisch am vollständigsten – daraus übersetzen, sonst aus dem Deutschen.
        [$sourceText, $sourceLocale] = $english !== '' ? [$english, 'en'] : [$german, 'de'];

        foreach ($this->otherNameLocales() as $locale) {
            if (trim((string) ($this->otherNames[$locale] ?? '')) !== '') {
                continue;
            }

            try {
                $this->otherNames[$locale] = $deepl->translateBatch([$sourceText], $locale, $sourceLocale, $this->nameTranslationContext())[0];
                $translated++;
            } catch (\Throwable $e) {
                $errors[] = strtoupper($locale).': '.$e->getMessage();
            }
        }

        $this->dispatch('adminv2-toast', ...match (true) {
            $errors !== [] => ['message' => 'Übersetzung teilweise fehlgeschlagen – '.implode(' | ', $errors), 'variant' => 'danger'],
            $translated > 0 => ['message' => "{$translated} Sprache(n) übersetzt – bitte prüfen und speichern."],
            default => ['message' => 'Alle Sprachen sind bereits ausgefüllt.'],
        });
    }
}
