<?php

namespace App\Filament\Resources\InfosystemEntryResource\Pages;

use App\Filament\Resources\InfosystemEntryResource;
use App\Services\PassolutionApiService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInfosystemEntries extends ListRecords
{
    protected static string $resource = InfosystemEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncData')
                ->label('Daten synchronisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Daten synchronisieren')
                ->modalDescription('Möchten Sie die aktuellen Daten aus dem externen Infosystem abrufen?')
                ->modalSubmitActionLabel('Ja, synchronisieren')
                ->action(fn () => $this->fetchApiData()),

            Action::make('syncLast100')
                ->label('Letzte 100 Einträge abrufen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('100 Einträge abrufen')
                ->modalDescription('Möchten Sie die letzten 100 Einträge aus dem externen Infosystem abrufen? Dies kann einige Minuten dauern.')
                ->modalSubmitActionLabel('Ja, 100 Einträge abrufen')
                ->action(fn () => $this->fetchLast100Entries()),

            Actions\CreateAction::make(),
        ];
    }

    /**
     * Fetch API data
     */
    public function fetchApiData(): void
    {
        try {
            $apiService = new PassolutionApiService;

            // Check if API credentials are configured
            if (! $apiService->hasValidCredentials()) {
                Notification::make()
                    ->title('API-Konfiguration fehlt')
                    ->body('Bitte konfigurieren Sie PASSOLUTION_API_KEY in der .env Datei')
                    ->danger()
                    ->duration(10000)
                    ->send();

                return;
            }

            $result = $apiService->fetchAndStore('de', 1);

            if ($result['success']) {
                Notification::make()
                    ->title('Daten erfolgreich synchronisiert')
                    ->body("Es wurden {$result['stored']} Einträge aus dem externen Infosystem abgerufen und gespeichert.")
                    ->success()
                    ->duration(5000)
                    ->send();

                // Refresh the table
                $this->resetTable();
            } else {
                Notification::make()
                    ->title('Fehler beim Abrufen der Daten')
                    ->body($result['error'] ?? 'Die Verbindung zum externen Infosystem konnte nicht hergestellt werden.')
                    ->danger()
                    ->duration(10000)
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Systemfehler')
                ->body('Ein unerwarteter Fehler ist aufgetreten: '.$e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    /**
     * Fetch last 100 entries from API
     */
    public function fetchLast100Entries(): void
    {
        try {
            $apiService = new PassolutionApiService;

            // Check if API credentials are configured
            if (! $apiService->hasValidCredentials()) {
                Notification::make()
                    ->title('API-Konfiguration fehlt')
                    ->body('Bitte konfigurieren Sie PASSOLUTION_API_KEY in der .env Datei')
                    ->danger()
                    ->duration(10000)
                    ->send();

                return;
            }

            Notification::make()
                ->title('Abruf gestartet')
                ->body('Der Abruf der letzten 100 Einträge wurde gestartet. Dies kann einige Minuten dauern...')
                ->info()
                ->duration(5000)
                ->send();

            $result = $apiService->fetchAndStoreMultiple('de', 100);

            if ($result['success']) {
                $message = "Erfolgreich {$result['stored']} Einträge über {$result['pages_fetched']} Seiten abgerufen und gespeichert.";

                Notification::make()
                    ->title('100 Einträge erfolgreich abgerufen')
                    ->body($message)
                    ->success()
                    ->duration(10000)
                    ->send();

                // Refresh the table
                $this->resetTable();
            } else {
                $errorMessage = 'Fehler beim Abrufen der Daten.';
                if (! empty($result['errors'])) {
                    $errorMessage .= ' Details: '.implode(', ', $result['errors']);
                }

                Notification::make()
                    ->title('Fehler beim Abrufen der 100 Einträge')
                    ->body($errorMessage)
                    ->danger()
                    ->duration(10000)
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Systemfehler')
                ->body('Ein unerwarteter Fehler ist aufgetreten: '.$e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }
}