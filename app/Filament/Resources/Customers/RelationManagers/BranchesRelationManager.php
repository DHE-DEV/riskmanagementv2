<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\Branch;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $title = 'Filialen';

    protected static ?string $modelLabel = 'Filiale';

    protected static ?string $pluralModelLabel = 'Filialen';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('app_code')
                            ->label('App-Code')
                            ->disabled()
                            ->helperText('Wird automatisch generiert'),

                        TextInput::make('name')
                            ->label('Filialname')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('additional')
                            ->label('Zusatz')
                            ->maxLength(255),

                        Checkbox::make('is_headquarters')
                            ->label('Hauptsitz')
                            ->default(false),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('street')
                            ->label('Straße')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('house_number')
                            ->label('Hausnummer')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('postal_code')
                            ->label('PLZ')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('city')
                            ->label('Stadt')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label('Land')
                            ->required()
                            ->maxLength(255)
                            ->default('Deutschland'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('latitude')
                            ->label('Breitengrad')
                            ->numeric()
                            ->step(0.00000001)
                            ->helperText('Optional: Für Kartendarstellung'),

                        TextInput::make('longitude')
                            ->label('Längengrad')
                            ->numeric()
                            ->step(0.00000001)
                            ->helperText('Optional: Für Kartendarstellung'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('app_code')
                    ->label('App-Code')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Filialname')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('street')
                    ->label('Straße')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('house_number')
                    ->label('Nr.')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('postal_code')
                    ->label('PLZ')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Stadt')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_headquarters')
                    ->label('Hauptsitz')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_headquarters')
                    ->label('Hauptsitz')
                    ->placeholder('Alle Filialen')
                    ->trueLabel('Nur Hauptsitz')
                    ->falseLabel('Keine Hauptsitze'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Filiale hinzufügen')
                    ->modalHeading('Neue Filiale hinzufügen')
                    ->modalSubmitActionLabel('Erstellen')
                    ->modalCancelActionLabel('Abbrechen'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->label('Bearbeiten')
                    ->modalHeading('Filiale bearbeiten')
                    ->modalSubmitActionLabel('Speichern')
                    ->modalCancelActionLabel('Abbrechen'),

                \Filament\Actions\DeleteAction::make()
                    ->label('Löschen')
                    ->modalHeading('Filiale löschen')
                    ->modalDescription('Möchten Sie diese Filiale wirklich löschen?')
                    ->modalSubmitActionLabel('Löschen')
                    ->modalCancelActionLabel('Abbrechen'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen')
                        ->modalHeading('Filialen löschen')
                        ->modalDescription('Möchten Sie die ausgewählten Filialen wirklich löschen?')
                        ->modalSubmitActionLabel('Löschen')
                        ->modalCancelActionLabel('Abbrechen'),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->emptyStateHeading('Keine Filialen')
            ->emptyStateDescription('Fügen Sie Filialen für diesen Kunden hinzu.')
            ->emptyStateIcon('heroicon-o-building-office');
    }
}
