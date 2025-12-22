<?php

namespace App\Filament\Resources\PluginClientResource\RelationManagers;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'Registrierte Domains';

    protected static string|BackedEnum|null $icon = 'heroicon-o-globe-alt';

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                TextInput::make('domain')
                    ->label('Domain')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('beispiel.de')
                    ->helperText('Ohne https:// oder http:// eingeben')
                    ->unique(ignoreRecord: true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Domain kopiert!'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hinzugefügt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Domain hinzufügen')
                    ->modalHeading('Neue Domain hinzufügen')
                    ->successNotificationTitle('Domain wurde hinzugefügt'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Entfernen')
                    ->modalHeading('Domain entfernen')
                    ->modalDescription('Sind Sie sicher, dass Sie diese Domain entfernen möchten?')
                    ->successNotificationTitle('Domain wurde entfernt'),
            ])
            ->emptyStateHeading('Keine Domains')
            ->emptyStateDescription('Für diesen Kunden sind keine Domains registriert.');
    }
}
