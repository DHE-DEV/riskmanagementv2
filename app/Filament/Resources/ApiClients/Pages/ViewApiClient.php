<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Filament\Resources\ApiClients\ApiClientResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewApiClient extends ViewRecord
{
    protected static string $resource = ApiClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_token')
                ->label('API-Token generieren')
                ->icon('heroicon-o-key')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('API-Token generieren')
                ->modalDescription('Es wird ein neuer API-Token erstellt. Der Token wird nur einmal angezeigt. Bitte kopieren Sie ihn sofort.')
                ->action(function () {
                    $token = $this->record->createToken(
                        'api-token',
                        ['events:write'],
                        now()->addYear()
                    );

                    Notification::make()
                        ->title('API-Token erstellt')
                        ->body('Token: ' . $token->plainTextToken . "\n\nBitte kopieren Sie den Token jetzt. Er wird nicht erneut angezeigt.")
                        ->success()
                        ->persistent()
                        ->send();
                }),

            Action::make('revoke_tokens')
                ->label('Alle Tokens widerrufen')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Alle Tokens widerrufen')
                ->modalDescription('Alle aktiven API-Tokens für diesen Kunden werden sofort ungültig. Der Kunde kann keine API-Aufrufe mehr durchführen.')
                ->action(function () {
                    $count = $this->record->tokens()->count();
                    $this->record->tokens()->delete();

                    Notification::make()
                        ->title('Tokens widerrufen')
                        ->body("{$count} Token(s) wurden erfolgreich widerrufen.")
                        ->success()
                        ->send();
                }),

            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\ApiClients\RelationManagers\CustomEventsRelationManager::class,
        ];
    }
}
