<?php

namespace App\Filament\Resources\PluginClientResource\RelationManagers;

use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Domain kopiert!'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Hinzugefügt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Domain hinzufügen')
                    ->modalHeading('Neue Domain hinzufügen')
                    ->successNotificationTitle('Domain wurde hinzugefügt'),
            ])
            ->actions([
                Action::make('toggle')
                    ->label(fn ($record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                    ->successNotificationTitle(fn ($record) => $record->is_active ? 'Domain aktiviert' : 'Domain deaktiviert'),
                DeleteAction::make()
                    ->label('Löschen')
                    ->modalHeading('Domain löschen')
                    ->modalDescription('Sind Sie sicher, dass Sie diese Domain endgültig löschen möchten?')
                    ->successNotificationTitle('Domain wurde gelöscht'),
            ])
            ->emptyStateHeading('Keine Domains')
            ->emptyStateDescription('Für diesen Kunden sind keine Domains registriert.');
    }
}
