<?php

namespace App\Filament\Resources\PluginEmailVerificationResource\Pages;

use App\Filament\Resources\PluginEmailVerificationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListPluginEmailVerifications extends ListRecords
{
    protected static string $resource = PluginEmailVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cleanup')
                ->label('Alte Einträge bereinigen')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-trash')
                ->modalIconColor('danger')
                ->modalHeading('Alte Einträge bereinigen')
                ->modalDescription('Hiermit werden alle abgelaufenen und bereits verifizierten Einträge gelöscht, die älter als 24 Stunden sind.')
                ->modalSubmitActionLabel('Ja, bereinigen')
                ->action(function () {
                    $deleted = \App\Models\PluginEmailVerification::where(function ($query) {
                        $query->where('expires_at', '<', now()->subDay())
                            ->orWhere(function ($q) {
                                $q->whereNotNull('verified_at')
                                    ->where('verified_at', '<', now()->subDay());
                            });
                    })->delete();

                    \Filament\Notifications\Notification::make()
                        ->title('Bereinigung abgeschlossen')
                        ->body("{$deleted} Einträge wurden gelöscht.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
