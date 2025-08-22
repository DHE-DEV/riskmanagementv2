<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use App\Services\PassolutionApiService;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;

class PdsInfosystem extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationLabel = 'PDS Infosystem';
    
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.pds-infosystem';
    
    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }
    
    public function getTitle(): string
    {
        return 'PDS Infosystem';
    }
    
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    /**
     * Get API statistics
     */
    #[Computed]
    public function statistics(): array
    {
        $apiService = new PassolutionApiService();
        return $apiService->getStatistics();
    }

    /**
     * Get latest entries
     */
    #[Computed]
    public function latestEntries()
    {
        $apiService = new PassolutionApiService();
        return $apiService->getLatestEntries(10);
    }

    /**
     * Fetch API data
     */
    public function fetchApiData(): void
    {
        try {
            $apiService = new PassolutionApiService();
            $result = $apiService->fetchAndStore('de', 1);

            if ($result['success']) {
                Notification::make()
                    ->title('API Daten erfolgreich abgerufen!')
                    ->body("Anzahl gespeicherte Einträge: {$result['stored']}")
                    ->success()
                    ->send();

                session()->flash('api_message', "Erfolgreich {$result['stored']} Einträge gespeichert (von {$result['total_available']} verfügbaren)");
                session()->flash('api_success', true);
            } else {
                Notification::make()
                    ->title('Fehler beim Abrufen der API Daten')
                    ->body($result['error'] ?? 'Unbekannter Fehler')
                    ->danger()
                    ->send();

                session()->flash('api_message', $result['error'] ?? 'Unbekannter Fehler');
                session()->flash('api_success', false);
            }

            // Refresh computed properties
            $this->reset(['statistics', 'latestEntries']);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Systemfehler')
                ->body('Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage())
                ->danger()
                ->send();

            session()->flash('api_message', 'Systemfehler: ' . $e->getMessage());
            session()->flash('api_success', false);
        }
    }
}