<?php

namespace App\Filament\Resources\PluginClientResource\Pages;

use App\Filament\Resources\PluginClientResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPluginClient extends ViewRecord
{
    protected static string $resource = PluginClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateKey')
                ->label('API-Key erneuern')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('warning')
                ->modalHeading('API-Key erneuern?')
                ->modalDescription('Achtung: Bei Erneuerung des API-Keys muss die Einbindung auf ALLEN registrierten Domains aktualisiert werden! Der alte Key wird sofort ungültig und die Darstellung funktioniert nicht mehr, bis der neue Key eingebunden wurde.')
                ->modalSubmitActionLabel('Ja, Key erneuern')
                ->action(function () {
                    $this->record->generateKey();
                    $this->refreshFormData(['activeKey']);
                })
                ->successNotificationTitle('Neuer API-Key wurde generiert'),
            EditAction::make()
                ->label('Bearbeiten'),
            DeleteAction::make()
                ->label('Löschen')
                ->modalHeading('Plugin-Kunde löschen')
                ->modalDescription('Sind Sie sicher, dass Sie diesen Plugin-Kunden löschen möchten? Alle zugehörigen Daten (Domains, API-Keys, Nutzungsstatistiken) werden ebenfalls gelöscht.')
                ->modalSubmitActionLabel('Ja, löschen')
                ->successNotificationTitle('Plugin-Kunde wurde gelöscht'),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\PluginClientResource\RelationManagers\DomainsRelationManager::class,
            \App\Filament\Resources\PluginClientResource\RelationManagers\UsageEventsRelationManager::class,
        ];
    }
}
