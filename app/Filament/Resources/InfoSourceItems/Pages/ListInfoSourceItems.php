<?php

namespace App\Filament\Resources\InfoSourceItems\Pages;

use App\Filament\Resources\InfoSourceItems\InfoSourceItemResource;
use App\Models\InfoSource;
use App\Services\FeedFetcherService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInfoSourceItems extends ListRecords
{
    protected static string $resource = InfoSourceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetch_all')
                ->label('Alle Feeds abrufen')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Alle aktiven Feeds abrufen?')
                ->modalDescription('Dies wird alle aktiven Datenquellen abrufen, die eine Aktualisierung benötigen.')
                ->action(function (FeedFetcherService $fetcher) {
                    $stats = $fetcher->fetchAll();

                    if ($stats['errors'] > 0) {
                        Notification::make()
                            ->title('Feeds abgerufen mit Fehlern')
                            ->body("Neu: {$stats['new']}, Aktualisiert: {$stats['updated']}, Fehler: {$stats['errors']}")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Feeds erfolgreich abgerufen')
                            ->body("Neu: {$stats['new']}, Aktualisiert: {$stats['updated']}")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
