<?php

namespace App\Filament\Resources\CustomEvents\Concerns;

use App\Models\CustomEvent;
use App\Services\DeepLTranslationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

/**
 * Stellt den "Übersetzen"-Header-Button für Create- und Edit-Seiten bereit.
 *
 * Übersetzt Titel und Beschreibung (popup_content) aus der Ausgangssprache in
 * alle konfigurierten Sprachen (per DeepL). Arbeitet ausschließlich auf dem
 * Formular-State ($this->data), sodass die Übersetzungen vor dem Speichern noch
 * manuell angepasst werden können.
 */
trait TranslatesEventContent
{
    protected function getTranslateEventAction(): Action
    {
        return Action::make('translate_event')
            ->label('Übersetzen')
            ->icon('heroicon-o-language')
            ->color('info')
            ->modalHeading('Felder übersetzen')
            ->modalDescription(
                'Übersetzt Titel und Beschreibung aus der Ausgangssprache ('
                . strtoupper(CustomEvent::sourceLocale())
                . ') per DeepL in alle konfigurierten Sprachen.'
            )
            ->modalSubmitActionLabel('Jetzt übersetzen')
            ->schema([
                Toggle::make('overwrite')
                    ->label('Bereits ausgefüllte Übersetzungen überschreiben')
                    ->helperText('Standardmäßig werden nur leere Felder ausgefüllt.')
                    ->default(false),
            ])
            ->action(function (array $data): void {
                $this->translateEventContent((bool) ($data['overwrite'] ?? false));
            });
    }

    protected function translateEventContent(bool $overwrite): void
    {
        /** @var DeepLTranslationService $service */
        $service = app(DeepLTranslationService::class);

        if (! $service->isConfigured()) {
            Notification::make()
                ->title('DeepL nicht konfiguriert')
                ->body('Bitte DEEPL_KEY in der .env hinterlegen.')
                ->danger()
                ->send();

            return;
        }

        $source = CustomEvent::sourceLocale();

        $sourceTitle = data_get($this->data, "title_translations.$source");
        $sourcePopup = data_get($this->data, "popup_content_translations.$source");

        if (blank($sourceTitle) && blank($sourcePopup)) {
            Notification::make()
                ->title('Keine Ausgangstexte')
                ->body('Bitte zuerst Titel/Beschreibung in der Ausgangssprache (' . strtoupper($source) . ') ausfüllen.')
                ->warning()
                ->send();

            return;
        }

        $translatedCount = 0;
        $errors = [];

        foreach (CustomEvent::translationLocales() as $locale) {
            if ($locale === $source) {
                continue;
            }

            try {
                if (filled($sourceTitle)) {
                    $existing = data_get($this->data, "title_translations.$locale");
                    if ($overwrite || blank($existing)) {
                        data_set($this->data, "title_translations.$locale", $service->translate($sourceTitle, $locale, $source));
                        $translatedCount++;
                    }
                }

                if (filled($sourcePopup)) {
                    $existing = data_get($this->data, "popup_content_translations.$locale");
                    if ($overwrite || blank($existing)) {
                        data_set($this->data, "popup_content_translations.$locale", $service->translateHtml($sourcePopup, $locale, $source));
                        $translatedCount++;
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = strtoupper($locale) . ': ' . $e->getMessage();
            }
        }

        if (! empty($errors)) {
            Notification::make()
                ->title('Übersetzung teilweise fehlgeschlagen')
                ->body(implode("\n", $errors))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($translatedCount > 0 ? 'Übersetzung abgeschlossen' : 'Nichts zu übersetzen')
            ->body($translatedCount > 0
                ? $translatedCount . ' Feld(er) übersetzt. Bitte vor dem Speichern prüfen.'
                : 'Alle Zielsprachen waren bereits ausgefüllt (Überschreiben war deaktiviert).')
            ->success()
            ->send();
    }
}
