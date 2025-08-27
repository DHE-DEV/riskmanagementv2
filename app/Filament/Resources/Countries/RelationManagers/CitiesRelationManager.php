<?php

namespace App\Filament\Resources\Countries\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'cities';

    protected static ?string $title = 'Zugehörige Städte';

    protected static ?string $modelLabel = 'Stadt';

    protected static ?string $pluralModelLabel = 'Städte';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name_translations')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_translations')
            ->columns([
                Tables\Columns\TextColumn::make('city_name')
                    ->label('Stadt')
                    ->getStateUsing(fn ($record) => $record->getName('de'))
                    ->url(fn ($record): string => route('filament.admin.resources.cities.view', $record))
                    ->openUrlInNewTab()
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) {$direction}");
                    }),
                Tables\Columns\TextColumn::make('region_name')
                    ->label('Region')
                    ->getStateUsing(fn ($record) => $record->region ? $record->region->getName('de') : 'Keine Region')
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereHas('region', function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->join('regions', 'cities.region_id', '=', 'regions.id', 'left')
                                    ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(regions.name_translations, '$.de')) {$direction}");
                    }),
                Tables\Columns\IconColumn::make('is_capital')
                    ->label('Hauptstadt')
                    ->boolean(),
                Tables\Columns\TextColumn::make('population')
                    ->label('Bevölkerung')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state) : 'Unbekannt'),
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
                Tables\Filters\Filter::make('is_capital')
                    ->label('Nur Hauptstädte')
                    ->query(fn (Builder $query): Builder => $query->where('is_capital', true))
                    ->toggle(),
                Tables\Filters\Filter::make('has_region')
                    ->label('Mit Region')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('region_id'))
                    ->toggle(),
            ])
            ->headerActions([
                // Keine Create Actions, da Städte separat verwaltet werden
            ])
            ->actions([
                // Actions entfernt - Navigation über Spalten-URL wenn nötig
            ])
            ->recordTitleAttribute('city_name')
            ->recordTitle(fn ($record) => $record->getName('de'))
            ->bulkActions([
                // Keine Bulk Actions
            ])
            ->defaultSort('city_name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}