<?php

namespace App\Filament\Resources\PluginEmailVerificationResource\Pages;

use App\Filament\Resources\PluginEmailVerificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPluginEmailVerification extends ViewRecord
{
    protected static string $resource = PluginEmailVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Löschen')
                ->modalHeading('Registrierungsversuch löschen')
                ->modalDescription('Sind Sie sicher, dass Sie diesen Registrierungsversuch löschen möchten? Der Nutzer muss sich erneut registrieren.')
                ->modalSubmitActionLabel('Ja, löschen')
                ->successNotificationTitle('Eintrag wurde gelöscht'),
        ];
    }
}
