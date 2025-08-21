<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Benutzer löschen')
                ->icon('heroicon-o-trash')
                ->visible(fn () => $this->record->id !== auth()->id()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Stelle sicher, dass das Passwort gehashed wird, wenn es geändert wurde
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Entferne das Passwort-Feld, wenn es leer ist
            unset($data['password']);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Benutzer erfolgreich aktualisiert';
    }
}
