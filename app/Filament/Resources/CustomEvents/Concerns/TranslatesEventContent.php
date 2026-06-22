<?php

namespace App\Filament\Resources\CustomEvents\Concerns;

use App\Models\CustomEvent;
use App\Services\DeepLTranslationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

/**
 * Stellt den "Übersetzen"-Header-Button für die Edit-Seite bereit.
 *
 * Ablauf (bewusst über die Datenbank statt über den Formular-State, da der
 * RichEditor extern gesetzten State nicht zuverlässig übernimmt und beim
 * Speichern sonst überschreiben würde):
 *   1. Aktuellen Formularstand speichern (inkl. Ausgangssprache).
 *   2. Titel + Beschreibung aus dem gespeicherten Datensatz per DeepL in alle
 *      konfigurierten Sprachen übersetzen und persistieren.
 *   3. Edit-Seite neu laden, damit alle Felder (auch die RichEditor) die
 *      Übersetzungen anzeigen. Danach beliebig manuell editierbar.
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
                'Speichert das Event und übersetzt Titel und Beschreibung aus der Ausgangssprache ('
                . strtoupper(CustomEvent::sourceLocale())
                . ') per DeepL in alle konfigurierten Sprachen.'
            )
            ->modalSubmitActionLabel('Speichern & übersetzen')
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

        // 1. Aktuellen Formularstand speichern (ohne Redirect / ohne Standard-Notification).
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $record = $this->record->refresh();
        $source = CustomEvent::sourceLocale();

        $titleTranslations = $record->title_translations ?? [];
        $popupTranslations = $record->popup_content_translations ?? [];

        $sourceTitle = $titleTranslations[$source] ?? null;
        $sourcePopup = $popupTranslations[$source] ?? null;

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
                if (filled($sourceTitle) && ($overwrite || blank($titleTranslations[$locale] ?? null))) {
                    $titleTranslations[$locale] = $service->translate($sourceTitle, $locale, $source);
                    $translatedCount++;
                }

                if (filled($sourcePopup) && ($overwrite || blank($popupTranslations[$locale] ?? null))) {
                    $popupTranslations[$locale] = $service->translateHtml($sourcePopup, $locale, $source);
                    $translatedCount++;
                }
            } catch (\Throwable $e) {
                $errors[] = strtoupper($locale) . ': ' . $e->getMessage();
            }
        }

        // 2. Übersetzungen persistieren.
        $record->update([
            'title_translations' => $titleTranslations,
            'popup_content_translations' => $popupTranslations,
        ]);

        if (! empty($errors)) {
            Notification::make()
                ->title('Übersetzung teilweise fehlgeschlagen')
                ->body(implode("\n", $errors))
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title($translatedCount > 0 ? 'Übersetzung abgeschlossen' : 'Nichts zu übersetzen')
                ->body($translatedCount > 0
                    ? $translatedCount . ' Feld(er) übersetzt.'
                    : 'Alle Zielsprachen waren bereits ausgefüllt (Überschreiben war deaktiviert).')
                ->success()
                ->send();
        }

        // 3. Edit-Seite neu laden, damit die (RichEditor-)Felder die Übersetzungen zeigen.
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
    }
}
