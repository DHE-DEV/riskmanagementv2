<?php

namespace App\Filament\Resources\Countries\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RegionsRelationManager extends RelationManager
{
    protected static string $relationship = 'regions';

    protected static ?string $title = 'Zugehörige Regionen';

    protected static ?string $modelLabel = 'Region';

    protected static ?string $pluralModelLabel = 'Regionen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('region_name')
                    ->label('Region')
                    ->getStateUsing(fn ($record) => $record->getName('de'))
                    ->url(fn ($record): string => route('filament.admin.resources.regions.view', $record))
                    ->openUrlInNewTab()
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) {$direction}");
                    }),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cities_count')
                    ->label('Städte')
                    ->getStateUsing(fn ($record) => $record->cities()->count())
                    ->sortable(),
                Tables\Columns\TextColumn::make('lat')
                    ->label('Breitengrad')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lng')
                    ->label('Längengrad')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Keine spezifischen Filter für Regionen
            ])
            ->headerActions([
                // Keine Create Actions, da Regionen separat verwaltet werden
            ])
            ->actions([
                // Actions entfernt - Navigation über Spalten-URL wenn nötig
            ])
            ->recordTitleAttribute('region_name')
            ->recordTitle(fn ($record) => $record->getName('de'))
            ->bulkActions([
                // Keine Bulk Actions
            ])
            ->defaultSort('region_name', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25);
    }
}