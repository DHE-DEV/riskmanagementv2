<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ApiTokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $title = 'API Tokens';

    protected static ?string $modelLabel = 'API Token';

    protected static ?string $pluralModelLabel = 'API Tokens';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(function (string $state): string {
                        return Str::after($state, ':') ?: $state;
                    })
                    ->description(function ($record): string {
                        if (Str::startsWith($record->name, 'admin:')) {
                            return 'Vom Admin erstellt';
                        }

                        return 'Vom Kunden erstellt';
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('abilities')
                    ->label('Berechtigungen')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Zuletzt verwendet')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Nie verwendet')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Läuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Unbegrenzt')
                    ->color(fn ($record) => $record->expires_at?->isPast() ? 'danger' : null)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Token erstellen')
                    ->modalHeading('Neuen API Token erstellen')
                    ->modalSubmitActionLabel('Erstellen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->form([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Beschreibender Name, z.B. "Jack API", "CRM System"'),

                        CheckboxList::make('abilities')
                            ->label('Berechtigungen')
                            ->options([
                                'folder:import' => 'Folder Import',
                                'folder:read' => 'Folder Lesen',
                                'folder:write' => 'Folder Schreiben',
                                'gtm:read' => 'GTM Lesen',
                            ])
                            ->required()
                            ->columns(2),

                        DateTimePicker::make('expires_at')
                            ->label('Ablaufdatum')
                            ->helperText('Leer lassen für unbegrenzte Gültigkeit')
                            ->minDate(now()),
                    ])
                    ->using(function (array $data, RelationManager $livewire): void {
                        $customer = $livewire->getOwnerRecord();

                        $expiresAt = isset($data['expires_at'])
                            ? new \DateTimeImmutable($data['expires_at'])
                            : null;

                        $token = $customer->createToken(
                            'admin:' . $data['name'],
                            $data['abilities'],
                            $expiresAt,
                        );

                        $plainText = $token->plainTextToken;

                        $livewire->js("navigator.clipboard.writeText('{$plainText}')");

                        Notification::make()
                            ->title('API Token erstellt')
                            ->body("Der Token wurde in die Zwischenablage kopiert.\n\n**Token:** `{$plainText}`\n\nDieser Token wird nur einmal angezeigt!")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Widerrufen')
                    ->modalHeading('Token widerrufen')
                    ->modalDescription('Möchten Sie diesen Token wirklich widerrufen? Der API-Zugriff wird sofort gesperrt.')
                    ->modalSubmitActionLabel('Widerrufen')
                    ->modalCancelActionLabel('Abbrechen'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine API Tokens')
            ->emptyStateDescription('Erstellen Sie einen API Token für diesen Kunden.')
            ->emptyStateIcon('heroicon-o-key');
    }
}
