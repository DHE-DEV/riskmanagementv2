<?php

namespace App\Filament\Resources\PluginClientResource\Pages;

use App\Filament\Resources\PluginClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePluginClient extends CreateRecord
{
    protected static string $resource = PluginClientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        // Automatically generate an API key for the new client
        $this->record->generateKey();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Plugin-Kunde wurde erstellt';
    }
}
