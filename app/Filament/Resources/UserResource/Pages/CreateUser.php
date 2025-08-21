<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Stelle sicher, dass das Passwort gehashed wird
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Benutzer erfolgreich erstellt';
    }
}
