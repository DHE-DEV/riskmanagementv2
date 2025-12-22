<?php

namespace App\Filament\Resources\PluginClientResource\Pages;

use App\Filament\Resources\PluginClientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPluginClient extends ViewRecord
{
    protected static string $resource = PluginClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
