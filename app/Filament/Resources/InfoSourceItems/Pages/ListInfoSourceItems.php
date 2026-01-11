<?php

namespace App\Filament\Resources\InfoSourceItems\Pages;

use App\Filament\Resources\InfoSourceItems\InfoSourceItemResource;
use App\Jobs\FetchInfoSourceJob;
use App\Models\InfoSource;
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
                ->modalDescription('Die Jobs werden in die Queue gestellt und im Hintergrund abgearbeitet. Fortschritt unter System → Queue.')
                ->action(function () {
                    $sources = InfoSource::active()->ordered()->get();

                    if ($sources->isEmpty()) {
                        Notification::make()
                            ->title('Keine aktiven Datenquellen')
                            ->body('Es gibt keine aktiven Datenquellen zum Abrufen.')
                            ->warning()
                            ->send();

                        return;
                    }

                    foreach ($sources as $source) {
                        dispatch(new FetchInfoSourceJob($source));
                    }

                    Notification::make()
                        ->title('Jobs in Queue gestellt')
                        ->body("{$sources->count()} Feed-Jobs wurden in die Queue gestellt.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
