<?php

namespace App\Filament\Resources\PluginClientResource\Pages;

use App\Filament\Resources\PluginClientResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPluginClient extends EditRecord
{
    protected static string $resource = PluginClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Ansehen'),
            DeleteAction::make()
                ->label('Löschen')
                ->modalHeading('Plugin-Kunde löschen')
                ->modalDescription('Sind Sie sicher, dass Sie diesen Plugin-Kunden löschen möchten? Alle zugehörigen Daten (Domains, API-Keys, Nutzungsstatistiken) werden ebenfalls gelöscht.')
                ->modalSubmitActionLabel('Ja, löschen')
                ->successNotificationTitle('Plugin-Kunde wurde gelöscht'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Plugin-Kunde wurde aktualisiert';
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\PluginClientResource\RelationManagers\DomainsRelationManager::class,
        ];
    }
}
